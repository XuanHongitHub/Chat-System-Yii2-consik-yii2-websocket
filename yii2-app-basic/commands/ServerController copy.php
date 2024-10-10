<?php

namespace app\commands;

use consik\yii2websocket\events\WSClientMessageEvent;
use consik\yii2websocket\WebSocketServer;
use yii\console\Controller;
use app\daemons\ChatServer;
use app\models\User;
use app\models\Contacts;
use app\models\Messages;
use yii\web\Response;
use app\models\ChatRooms;
use app\models\ChatRoomUser;
use Codeception\Lib\Interfaces\Web;
use yii;

class ServerController extends Controller
{
    public function actionStart()
    {
        $server = new WebSocketServer();
        $server->port = 4000;

        $server->on(WebSocketServer::EVENT_WEBSOCKET_OPEN_ERROR, function ($e) use ($server) {
            echo "Error opening port " . $server->port . "\n";
            $server->port += 1;
            $server->start();
        });

        $server->on(WebSocketServer::EVENT_WEBSOCKET_OPEN, function ($e) use ($server) {
            echo "Server started at port " . $server->port . "\n";
        });

        $server->on(WebSocketServer::EVENT_CLIENT_MESSAGE, function (WSClientMessageEvent $e) use ($server) {
            $message = $e->message;

            $messageData = json_decode($message, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo "Lỗi trong quá trình giải mã JSON: " . json_last_error_msg();
                return;
            }

            echo "Chat ID: " . $messageData['chatId'] . "\n";
            echo "Message: " . $messageData['message'] . "\n";
            echo "Is Room: " . ($messageData['isRoom'] ? 'true' : 'false') . "\n";

            $messageModel = new Messages();
            $messageModel->content = $messageData['message'];
            $messageModel->user_id = 1;
            if ($messageData['isRoom']) {
                $messageModel->chat_room_id = $messageData['chatId'];
                $messageModel->recipient_id = null;
            } else {
                $contact = Contacts::find()->where(['id' => $messageData['chatId']])->one();
                if ($contact) {
                    $messageModel->recipient_id = $contact->contact_user_id;
                    $messageModel->chat_room_id = null;
                } else {
                    echo "Liên hệ không tìm thấy.\n";
                    return;
                }
            }
            $messageModel->created_at = time();
            $messageModel->updated_at = time();

            if ($messageModel->save()) {
                echo "Message saved successfully.\n";
            } else {
                echo "Error saving message: " . implode(", ", $messageModel->getErrors()) . "\n";
                return;
            }

            // $e->client->send($message);

            foreach ($this->clients as $chatClient) {
                $chatClient->send($messageData);
                echo "Client" . $chatClient;
            }
            // foreach ($e->clients as $client) {
            //     $client->send($message);
            // }
            // foreach ($server->clients as $client) {
            //     if ($client !== $e->client && $client->isWritable) {
            //         try {
            //             $client->send(json_encode($messageData));
            //         } catch (\Exception $ex) {
            //             echo "Error sending message to client: " . $ex->getMessage() . "\n";
            //             $client->close();
            //         }
            //     }
            // }
        });

        $server->start();
    }
}
