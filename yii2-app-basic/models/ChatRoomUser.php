<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "chat_room_user".
 *
 * @property int $id
 * @property int $user_id
 * @property int $chat_room_id
 * @property int $joined_at
 *
 * @property ChatRooms $chatRoom
 * @property User $user
 */
class ChatRoomUser extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chat_room_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'chat_room_id'], 'required'],
            [['user_id', 'chat_room_id', 'joined_at'], 'integer'],
            [['chat_room_id'], 'exist', 'skipOnError' => true, 'targetClass' => ChatRooms::class, 'targetAttribute' => ['chat_room_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'chat_room_id' => 'Chat Room ID',
            'joined_at' => 'Joined At',
        ];
    }

    /**
     * Gets query for [[ChatRoom]].
     *
     * @return \yii\db\ActiveQuery|\app\models\query\ChatRoomsQuery
     */
    public function getChatRoom()
    {
        return $this->hasOne(ChatRooms::class, ['id' => 'chat_room_id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery|\app\models\query\UserQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\models\query\ChatRoomUserQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\models\query\ChatRoomUserQuery(get_called_class());
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->joined_at = time();
        }

        return parent::beforeSave($insert);
    }
}
