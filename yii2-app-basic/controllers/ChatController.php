<?php

namespace app\controllers;

use app\models\User;
use app\models\Contacts;
use app\models\Messages;
use app\models\MessageStatus;
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
        $currentUserId = Yii::$app->user->id;

        $users = User::find()
            ->where(['like', 'username', $username])
            ->andWhere(['!=', 'id', $currentUserId])
            ->all();

        $contactIds = Contacts::find()
            ->select('contact_user_id')
            ->where(['user_id' => $currentUserId])
            ->column();

        $currentRoomId = Yii::$app->request->get('room_id');
        $currentRoomMembers = ChatRoomUser::find()
            ->select('user_id')
            ->where(['chat_room_id' => $currentRoomId])
            ->column();

        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'id' => $user->id,
                'username' => $user->username,
                'avatar' => $user->avatar,
                'isAdded' => in_array($user->id, $contactIds),
                'isMember' => in_array($user->id, $currentRoomMembers)
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
        $contacts = Contacts::find()->with('contactUser')->all();

        $contactData = [];
        foreach ($contacts as $contact) {
            $user = User::findOne($contact->contact_user_id);
            $avatarUrl = !empty($user->avatar) ? $user->avatar : 'https://icons.veryicon.com/png/o/miscellaneous/common-icons-30/my-selected-5.png';

            $contactData[] = [
                'id' => $contact->id,
                'avatarUrl' => $avatarUrl,
                'username' => $user ? $user->username : 'Unknown',
                'recipientId' => $contact->contact_user_id,
            ];
        }

        return $this->asJson($contactData);
    }

    public function actionAddContact()
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            $contactUserId = Yii::$app->request->post('contact_user_id');
            $currentUserId = Yii::$app->user->id;

            Yii::info("Contact User ID: $contactUserId", __METHOD__);

            $existingContact = Contacts::find()
                ->where(['user_id' => $currentUserId, 'contact_user_id' => $contactUserId])
                ->orWhere(['user_id' => $contactUserId, 'contact_user_id' => $currentUserId])
                ->exists();

            if ($existingContact) {
                return $this->asJson(['success' => false, 'error' => 'Liên kết này đã tồn tại.']);
            }

            $model = new Contacts();
            $model->contact_user_id = $contactUserId;
            $model->user_id = $currentUserId;
            $model->created_at = time();
            $model->updated_at = time();

            if ($model->save()) {
                $reverseModel = new Contacts();
                $reverseModel->contact_user_id = $currentUserId;
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
                        $chatRoomUser->user_id = (int) $member['id'];
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
    public function actionMessages($id, $lastMessageContentId)
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

                if ($lastMessageContentId) {
                    $this->updateMessageStatus($lastMessageContentId, $currentUserId);
                }
            }

            return $this->asJson(['messages' => $data]);
        }

        $chatRoom = ChatRooms::findOne($id);

        if ($chatRoom) {
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

                if ($lastMessageContentId) {
                    $this->updateMessageStatus($lastMessageContentId, $currentUserId);
                }
            }

            return $this->asJson(['messages' => $data]);
        }

        return $this->asJson(['messages' => [], 'error' => 'Contact or Room not found.']);
    }
    public function actionGetSenderId($userId)
    {
        $user = User::find()->where(['id' => $userId])->one();

        if ($user) {
            $userId = $user->id;
            $userName = $user->username;
            $avatar = $user->avatar;

            return $this->asJson([
                'user_id' => $userId,
                'username' => $userName,
                'avatar' => $avatar
            ]);
        } else {
            return $this->asJson(['error' => 'User not found']);
        }
    }

    public function actionSearchRoom($roomName)
    {
        $rooms = ChatRooms::find()
            ->where(['like', 'name', $roomName])
            ->all();

        $result = [];
        foreach ($rooms as $room) {
            $result[] = [
                'id' => $room->id,
                'name' => $room->name,
                'visibility' => $room->visibility ? '1' : '0',
            ];
        }

        return $this->asJson($result);
    }
    public function actionJoinRoom()
    {
        $roomId = Yii::$app->request->post('roomId');

        if (!$roomId) {
            return $this->asJson(['status' => 'error', 'message' => 'Missing required parameters: roomId']);
        }

        $currentUserId = Yii::$app->user->id;
        Yii::error("Current User ID: " . $currentUserId, __METHOD__);
        Yii::error("Attempting to join room with ID: " . $roomId, __METHOD__);

        $chatRoom = ChatRooms::findOne($roomId);
        if (!$chatRoom) {
            return $this->asJson(['status' => 'error', 'message' => 'Phòng chat không tồn tại.']);
        }

        $existingMember = ChatRoomUser::findOne(['chat_room_id' => $roomId, 'user_id' => $currentUserId]);
        if ($existingMember) {
            return $this->asJson(['status' => 'info', 'message' => 'Bạn đã ở trong phòng chat này.']);
        }

        $chatRoomUser = new ChatRoomUser();
        $chatRoomUser->chat_room_id = $roomId;
        $chatRoomUser->user_id = $currentUserId;
        $chatRoomUser->joined_at = time();

        if ($chatRoomUser->save()) {
            return $this->asJson(['status' => 'success', 'message' => 'Tham gia phòng thành công.']);
        } else {
            Yii::error('Failed to join chat room: ' . json_encode($chatRoomUser->getErrors()), __METHOD__);
            return $this->asJson(['status' => 'error', 'message' => 'Có lỗi xảy ra khi tham gia phòng.']);
        }
    }
    public function actionGetChatRoomUsers($roomId)
    {
        $chatRoom = ChatRooms::find()->where(['id' => $roomId])->with('users')->one();

        if (!$chatRoom) {
            return $this->asJson(['status' => 'error', 'message' => 'Room not found.']);
        }

        return $this->asJson(['status' => 'success', 'users' => $chatRoom->users]);
    }
    public function actionAddMember()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $chatRoomId = Yii::$app->request->post('roomId');
        $members = json_decode(Yii::$app->request->post('members'), true);

        $chatRoom = ChatRooms::findOne($chatRoomId);
        if (!$chatRoom) {
            return ['status' => 'error', 'message' => 'Phòng chat không tồn tại!'];
        }

        // Thêm các thành viên
        if (!empty($members) && is_array($members)) {
            foreach ($members as $member) {
                if (isset($member['id']) && is_int($member['id'])) {
                    $chatRoomUser = new ChatRoomUser();
                    $chatRoomUser->chat_room_id = $chatRoom->id;
                    $chatRoomUser->user_id = (int) $member['id'];
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

        return ['status' => 'success', 'message' => 'Thêm thành viên thành công!'];
    }
    public function actionDeleteMember()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $memberId = Yii::$app->request->post('id');
        $roomId = Yii::$app->request->post('roomId');

        $chatRoomUser = ChatRoomUser::find()
            ->where(['user_id' => $memberId, 'chat_room_id' => $roomId])
            ->one();

        if ($chatRoomUser) {
            if ($chatRoomUser->delete()) {
                Yii::info('Member ID: ' . $memberId . ' removed successfully from chat room ID: ' . $roomId, __METHOD__);
                return ['status' => 'success', 'message' => 'Thành viên đã được xóa!'];
            } else {
                Yii::error('Failed to delete chat room user: ' . json_encode($chatRoomUser->getErrors()), __METHOD__);
                return ['status' => 'error', 'message' => 'Xóa thành viên không thành công!'];
            }
        } else {
            return ['status' => 'error', 'message' => 'Không tìm thấy thành viên trong phòng chat!'];
        }
    }
    public function actionDeleteContact()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $request = Yii::$app->request;
        $contactId = $request->post('contactId');
        $userId = Yii::$app->user->id;

        $contact1 = Contacts::find()->where(['user_id' => $userId, 'contact_user_id' => $contactId])->one();
        $contact2 = Contacts::find()->where(['user_id' => $contactId, 'contact_user_id' => $userId])->one();

        $contact1 = Contacts::find()->where(['user_id' => $userId, 'contact_user_id' => $contactId])->one();
        $contact2 = Contacts::find()->where(['user_id' => $contactId, 'contact_user_id' => $userId])->one();

        if ($contact1 && $contact2) {
            $contact1->delete();
            $contact2->delete();

            return [
                'success' => true,
                'message' => 'Liên hệ đã được xóa thành công.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Liên hệ không tồn tại hoặc đã bị xóa.'
            ];
        }
    }

    public function actionLeaveChatRoom()
    {
        $request = Yii::$app->request;
        $roomId = $request->post('roomId');
        $userId = Yii::$app->user->id;

        $chatRoomUser = ChatRoomUser::findOne(['chat_room_id' => $roomId, 'user_id' => $userId]);

        if ($chatRoomUser) {
            if ($chatRoomUser->delete()) {
                return $this->asJson(['status' => 'success']);
            } else {
                return $this->asJson(['status' => 'error', 'message' => 'Không thể rời khỏi phòng chat.']);
            }
        } else {
            return $this->asJson(['status' => 'error', 'message' => 'Bạn không phải là thành viên của phòng này.']);
        }
    }
    protected function updateMessageStatus($lastMessageContentId, $currentUserId)
    {
        $messageStatus = MessageStatus::findOne(['message_id' => $lastMessageContentId, 'user_id' => $currentUserId, 'read_at' => NULL]);

        if ($messageStatus) {
            $messageStatus->read_at = time();
            $messageStatus->save();
        }
    }

}