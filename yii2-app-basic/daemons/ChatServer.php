<?php

namespace app\daemons;

use app\models\Messages;
use app\models\Contacts;
use consik\yii2websocket\events\WSClientEvent;
use consik\yii2websocket\WebSocketServer;
use consik\yii2websocket\events\WSClientMessageEvent;
use Ratchet\ConnectionInterface;
use Yii;
use Exception;
use yii\console\Controller;
use app\models\User;
use yii\web\Response;
use app\models\ChatRooms;
use app\models\ChatRoomUser;
use Codeception\Lib\Interfaces\Web;


class ChatServer extends WebSocketServer
{
    public function init()
    {
        parent::init();

        $this->on(self::EVENT_CLIENT_CONNECTED, function (WSClientEvent $e) {
            $e->client->name = null;
        });

        // $this->on(self::EVENT_CLIENT_CONNECTED, function (WSClientEvent $e) {
        //     $e->client->name = null;
        //     $e->client->clientId = uniqid();
        //     $e->client->send(json_encode([
        //         'type' => 'init',
        //         'clientId' => $e->client->clientId
        //     ]));
        // });

    }

    protected function getCommand(ConnectionInterface $from, $msg)
    {
        $request = json_decode($msg, true);

        if (empty($request['action'])) {
            return parent::getCommand($from, $msg);
        }

        return $request['action'];
    }
    public function commandAuthenticate(ConnectionInterface $client, $msg)
    {
        $request = json_decode($msg, true);
        if (!empty($request['userId'])) {
            $user = User::findOne($request['userId']);
            if ($user) {
                $client->userId = $user->id;
                echo "Client authenticated: User ID = " . $client->userId . "\n";
            } else {
                $client->send(json_encode(['error' => 'Invalid User ID.']));
            }
        } else {
            $client->send(json_encode(['error' => 'User ID is required.']));
        }
    }


    public function commandChat(ConnectionInterface $client, $msg)
    {
        try {
            $request = json_decode($msg, true);
            $clientId = $request['chatId'];
            $message = $request['message'];
            $userId = $request['userId'];
            $recipientId = $request['recipientId'];
            $isRoom = $request['isRoom'];
            $relatedId = $request['relatedId'];

            // echo "Client ID: $clientId, Message: $message\n";
            // echo "Recipient ID: " . $recipientId . "\n"; // In ra recipientId
            // echo "Is Room: " . json_encode($request['isRoom']) . "\n";
            // echo "relatedId ID: " . $relatedId . "\n"; // In ra relatedIdId

            if (!$client->name) {
                throw new Exception(Yii::t('app', 'You need to be logged in to chat.'));
            }

            if (!empty($request['message']) && $message = trim($request['message'])) {

                $messagesModel = new Messages();
                $messagesModel->content = $message;
                $messagesModel->user_id = $userId;

                if ($request['isRoom']) {
                    $messagesModel->chat_room_id = $request['chatId'];
                    $messagesModel->recipient_id = null;
                } else {
                    $contact = Contacts::find()->where(['id' => $request['chatId']])->one();
                    if ($contact) {
                        $messagesModel->recipient_id = $contact->contact_user_id; // Gán ID người nhận
                        $messagesModel->chat_room_id = null; // Không có phòng chat
                    } else {
                        throw new Exception("Liên hệ không tìm thấy.");
                    }
                }

                $messagesModel->created_at = time();
                $messagesModel->updated_at = time();

                if (!$messagesModel->save()) {
                    throw new Exception("Error saving message: " . implode(", ", $messagesModel->getErrors()));
                }

                if ($request['isRoom']) {
                    $roomId = $request['chatId'];
                    $roomUsers = ChatRoomUser::find()
                        ->select('user_id')
                        ->where(['chat_room_id' => $roomId])
                        ->column();
                    echo "Members: " . json_encode($roomUsers) . "\n";
                    echo "Room ID: " . json_encode($roomId) . "\n";

                    foreach ($this->clients as $chatClient) {
                        echo "Member ID: " . $chatClient->userId . "\n";
                        foreach ($roomUsers as $roomUser) {
                            if (intval($chatClient->userId) == intval($roomUser)) {
                                $chatClient->send(json_encode([
                                    'type' => 'chat',
                                    'from' => $client->name,
                                    'date' => date('Y-m-d H:i:s'),
                                    'message' => $message,
                                    'chatId' => $request['chatId'] ?? null,
                                    'isRoom' => $request['isRoom'] ?? true,
                                    'recipientId' => $request['recipientId'] ?? true,
                                    'relatedId' => $request['relatedId'] ?? true,
                                    'userId' => $request['userId'] ?? true,
                                ]));
                            }
                        }
                    }
                } else {
                    foreach ($this->clients as $chatClient) {
                        if ($chatClient->userId == $recipientId || $chatClient == $client) {
                            echo "Client ID: $chatClient->userId, Message: $message\n";
                            echo "Recipient ID: " . $recipientId . "\n"; // In ra recipientId


                            $chatClient->send(json_encode([
                                'type' => 'chat',
                                'from' => $client->name,
                                'date' => date('Y-m-d H:i:s'),
                                'message' => $message,
                                'chatId' => $request['chatId'] ?? null,
                                'isRoom' => $request['isRoom'] ?? false,
                                'recipientId' => $request['recipientId'] ?? true,
                                'relatedId' => $request['relatedId'] ?? true,
                                'userId' => $request['userId'] ?? true,
                            ]));
                        }
                    }
                }
            } else {
                throw new Exception(Yii::t('app', 'Message content cannot be empty.'));
            }
        } catch (Exception $e) {
            $client->send(json_encode(['error' => $e->getMessage()]));
        }
    }



    public function commandSetName(ConnectionInterface $client, $msg)
    {
        try {
            $request = json_decode($msg, true);
            if (!empty($request['name']) && $name = trim($request['name'])) {
                foreach ($this->clients as $chatClient) {
                    if ($chatClient != $client && $chatClient->name == $name) {
                        throw new Exception(Yii::t('app', 'This username is already in use.'));
                    }
                }
                $client->name = $name;
                // $client->send(json_encode(['message' => Yii::t('app', 'Username set successfully.')]));
            } else {
                throw new Exception(Yii::t('app', 'Invalid username.'));
            }
        } catch (Exception $e) {
            $client->send(json_encode(['error' => $e->getMessage()]));
        }
    }
}