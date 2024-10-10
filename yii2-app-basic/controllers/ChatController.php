<?php

namespace app\controllers;

use app\models\User;
use app\models\Contacts;
use app\models\Messages;
use yii\web\Response;
use yii\web\Controller;
use app\models\ChatRooms;
use app\models\ChatRoomUser;
use app\app\models\ChatRooms as ChatRoomsSearch;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii;
use Pusher\Pusher;
use consik\yii2websocket\events\WSClientMessageEvent;
// use consik\yii2websocket\WebSocketServer;

class ChatController extends Controller
{
    public function actionSearchUser($username)
    {
        $users = User::find()
            ->where(['like', 'username', $username])
            ->all();

        // Lấy danh sách ID của các liên hệ đã được thêm
        $contactIds = Contacts::find()
            ->select('contact_user_id')
            ->where(['user_id' => Yii::$app->user->id])
            ->column(); // Sử dụng column() để lấy danh sách ID

        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'id' => $user->id,
                'username' => $user->username,
                // 'avatar' => $user->avatar,
                'isAdded' => in_array($user->id, $contactIds), // Kiểm tra xem người dùng đã được thêm vào danh sách liên hệ
            ];
        }

        return $this->asJson($result);
    }

    public function actionGetAddedContacts()
    {
        $userId = Yii::$app->user->id;
        $contacts = Contacts::find()->where(['user_id' => $userId])->with('contactUser')->all();

        $addedContacts = [];
        foreach ($contacts as $contact) {
            $addedContacts[] = [
                'id' => $contact->contact_user_id,
                'username' => $contact->contactUser->username,
                'avatar' => $contact->contactUser->avatar,
            ];
        }

        return $this->asJson($addedContacts);
    }

    public function actionGetContacts()
    {
        $userId = Yii::$app->user->id;
        $contacts = Contacts::find()->where(['user_id' => $userId])->with('contactUser')->all();

        $contactData = [];
        foreach ($contacts as $contact) {
            $contactUser = User::findOne($contact->contact_user_id);
            if ($contactUser) {
                $contactData[] = [
                    'id' => $contactUser->id,
                    'username' => $contactUser->username
                ];
            }
        }

        return $this->asJson($contactData);
    }

    public function actionAddContact()
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            $contactUserId = Yii::$app->request->post('contact_user_id');

            Yii::info("Contact User ID: $contactUserId", __METHOD__);

            $model = new Contacts();
            $model->contact_user_id = $contactUserId;
            $model->user_id = Yii::$app->user->id;
            $model->created_at = time();
            $model->updated_at = time();

            if ($model->save()) {
                $reverseModel = new Contacts();
                $reverseModel->contact_user_id = Yii::$app->user->id;
                $reverseModel->user_id = $contactUserId;
                $reverseModel->created_at = time();
                $reverseModel->updated_at = time();

                if ($reverseModel->save()) {
                    return $this->asJson(['success' => true]);
                } else {
                    return $this->asJson(['success' => false, 'error' => $reverseModel->getErrors()]);
                }
            } else {
                return $this->asJson(['success' => false, 'error' => $model->getErrors()]);
            }
        }

        throw new BadRequestHttpException('Invalid request.');
    }

    public function actionAddRoom()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $roomName = Yii::$app->request->post('room_name');
        $members = json_decode(Yii::$app->request->post('members'), true);

        if (empty($roomName)) {
            Yii::error('Tên phòng không hợp lệ.', __METHOD__);
            return ['status' => 'error', 'message' => 'Tên phòng không hợp lệ.'];
        }

        $chatRoom = new ChatRooms();
        $chatRoom->name = $roomName;
        $chatRoom->visibility = Yii::$app->request->post('visibility', false);
        $chatRoom->created_by = Yii::$app->user->id;
        $chatRoom->created_at = time();
        $chatRoom->updated_at = time();

        if ($chatRoom->save()) {
            $creatorChatRoomUser = new ChatRoomUser();
            $creatorChatRoomUser->chat_room_id = $chatRoom->id;
            $creatorChatRoomUser->user_id = Yii::$app->user->id;
            $creatorChatRoomUser->joined_at = time();

            if (!$creatorChatRoomUser->save()) {
                Yii::error('Failed to save creator to chat room user: ' . json_encode($creatorChatRoomUser->getErrors()), __METHOD__);
            } else {
                Yii::info('Creator ID: ' . Yii::$app->user->id . ' added successfully to chat room ID: ' . $chatRoom->id, __METHOD__);
            }

            if (!empty($members) && is_array($members)) {
                foreach ($members as $member) {
                    if (isset($member['id']) && is_int($member['id'])) {
                        $chatRoomUser = new ChatRoomUser();
                        $chatRoomUser->chat_room_id = $chatRoom->id;
                        $chatRoomUser->user_id = (int)$member['id'];
                        $chatRoomUser->joined_at = time();

                        if (!$chatRoomUser->save()) {
                            Yii::error('Failed to save chat room user: ' . json_encode($chatRoomUser->getErrors()), __METHOD__);
                        } else {
                            Yii::info('Member ID: ' . $member['id'] . ' added successfully to chat room ID: ' . $chatRoom->id, __METHOD__);
                        }
                    } else {
                        Yii::error('Invalid member data: ' . json_encode($member), __METHOD__);
                    }
                }
            } else {
                Yii::info('No members to add for chat room ID: ' . $chatRoom->id, __METHOD__);
            }

            return ['status' => 'success', 'message' => 'Phòng chat đã được thêm!'];
        } else {
            Yii::error('Failed to save chat room: ' . json_encode($chatRoom->getErrors()), __METHOD__);
            return ['status' => 'error', 'message' => 'Có lỗi xảy ra khi thêm phòng chat.'];
        }
    }

    public function actionMessages($id)
    {
        $currentUserId = Yii::$app->user->id;

        Yii::debug("Current User ID: " . $currentUserId);

        // Kiểm tra nếu $id là một contact
        $contact = Contacts::findOne($id);

        if ($contact) {
            // Lấy tin nhắn giữa người dùng hiện tại và contact
            $messages = Messages::find()
                ->where([
                    'or',
                    [
                        'user_id' => $currentUserId,
                        'recipient_id' => $contact->contact_user_id
                    ],
                    [
                        'user_id' => $contact->contact_user_id,
                        'recipient_id' => $currentUserId
                    ]
                ])
                ->with('user')
                ->orderBy(['id' => SORT_DESC])
                ->all();

            $data = [];
            foreach ($messages as $message) {
                $data[] = [
                    'id' => $message->id,
                    'content' => $message->content,
                    'created_at' => date('Y-m-d H:i:s', $message->created_at),
                    'user' => [
                        'id' => $message->user->id,
                        'avatar' => $message->user->avatar ?: 'default-avatar.png',
                    ],
                    'isMine' => ($message->user_id === $currentUserId),
                ];
            }

            return $this->asJson(['messages' => $data]);
        }

        // Nếu không tìm thấy contact, kiểm tra xem có phải là room hay không
        $chatRoom = ChatRooms::findOne($id);

        if ($chatRoom) {
            // Lấy tin nhắn trong phòng chat
            $messages = Messages::find()
                ->where(['chat_room_id' => $chatRoom->id])
                ->with('user')
                ->orderBy(['id' => SORT_DESC])
                ->all();

            $data = [];
            foreach ($messages as $message) {
                $data[] = [
                    'id' => $message->id,
                    'content' => $message->content,
                    'created_at' => date('Y-m-d H:i:s', $message->created_at),
                    'user' => [
                        'id' => $message->user->id,
                        'avatar' => $message->user->avatar ?: 'default-avatar.png',
                    ],
                    'isMine' => ($message->user_id === $currentUserId),
                ];
            }

            return $this->asJson(['messages' => $data]);
        }

        // Nếu không phải là contact hay room
        return $this->asJson(['messages' => [], 'error' => 'Contact or Room not found.']);
    }


    // public function actionSendMessage()
    // {
    //     Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

    //     $request = Yii::$app->request;
    //     $chatId = $request->post('chatId');
    //     $messageContent = $request->post('message');
    //     $isRoom = $request->post('isRoom') === 'true';

    //     if (empty($messageContent)) {
    //         return [
    //             'success' => false,
    //             'errors' => ['message' => ['Nội dung tin nhắn không được để trống.']]
    //         ];
    //     }

    //     $message = new Messages();
    //     $message->content = $messageContent;
    //     $message->user_id = Yii::$app->user->id;

    //     if ($isRoom) {
    //         $message->chat_room_id = $chatId;
    //         $message->recipient_id = null;

    //         if (!ChatRooms::find()->where(['id' => $chatId])->exists()) {
    //             return [
    //                 'success' => false,
    //                 'errors' => ['chat_room_id' => ['ID phòng chat không hợp lệ.']]
    //             ];
    //         }
    //     } else {
    //         $contact = Contacts::find()->where(['id' => $chatId])->one();

    //         if ($contact) {
    //             $recipientId = $contact->contact_user_id;

    //             if (!User::find()->where(['id' => $recipientId])->exists()) {
    //                 return [
    //                     'success' => false,
    //                     'errors' => ['recipient_id' => ['ID người nhận không hợp lệ.']]
    //                 ];
    //             }

    //             $message->recipient_id = $recipientId;
    //             $message->chat_room_id = null;
    //         } else {
    //             return [
    //                 'success' => false,
    //                 'errors' => ['recipient_id' => ['Liên hệ không tìm thấy.']]
    //             ];
    //         }
    //     }

    //     $message->created_at = time();
    //     $message->updated_at = time();

    //     if ($message->save()) {
    //         $data = [
    //             'message' => $messageContent,
    //             'chatId' => $chatId,
    //             'senderId' => [
    //                 'id' => Yii::$app->user->id,
    //                 'avatar' => Yii::$app->user->identity->avatar,
    //                 'username' => Yii::$app->user->identity->username
    //             ]
    //         ];

    //         // Gửi tin nhắn đến tất cả người dùng kết nối qua WebSocket
    //         foreach ($this->getWebSocketServer()->clients as $client) {
    //             $client->send(json_encode($data));
    //         }

    //         return [
    //             'success' => true,
    //             'data' => $message,
    //         ];
    //     } else {
    //         return [
    //             'success' => false,
    //             'errors' => $message->getErrors(),
    //         ];
    //     }
    // }

    // private function getWebSocketServer()
    // {
    //     return new \consik\yii2websocket\WebSocketServer();
    // }
    public function actionGetSenderId($chatId)
    {
        $contact = Contacts::find()->where(['id' => $chatId])->one();

        if ($contact) {
            $userId = $contact->user_id; // Lấy user_id từ bản ghi
            return $this->asJson(['user_id' => $userId]);
        } else {
            return $this->asJson(['error' => 'Chat not found']);
        }
    }
    // public function actionGetAllUsers()
    // {
    //     $users = User::find()->all(); 

    //     return $this->asJson($users);
    // }
}