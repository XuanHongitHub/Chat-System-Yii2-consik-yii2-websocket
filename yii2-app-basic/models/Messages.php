<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "messages".
 *
 * @property int $id
 * @property int $chat_room_id
 * @property int $user_id
 * @property int $recipient_id
 * @property string $content
 * @property int $created_at
 * @property int $updated_at
 *
 * @property ChatRooms $chatRoom
 * @property MessageStatus[] $messageStatuses
 * @property User $recipient
 */
class Messages extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'messages';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['content', 'user_id'], 'required'],
            [['user_id', 'recipient_id', 'chat_room_id', 'created_at', 'updated_at'], 'integer'],
            [['content'], 'string', 'max' => 255],
            [['recipient_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['recipient_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'chat_room_id' => 'Chat Room ID',
            'user_id' => 'User ID',
            'recipient_id' => 'Recipient ID',
            'content' => 'Content',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
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
     * Gets query for [[MessageStatuses]].
     *
     * @return \yii\db\ActiveQuery|\app\models\query\MessageStatusQuery
     */
    public function getMessageStatuses()
    {
        return $this->hasMany(MessageStatus::class, ['message_id' => 'id']);
    }

    /**
     * Gets query for [[Recipient]].
     *
     * @return \yii\db\ActiveQuery|\app\models\query\UserQuery
     */
    public function getRecipient()
    {
        return $this->hasOne(User::class, ['id' => 'recipient_id']);
    }
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
    /**
     * {@inheritdoc}
     * @return \app\models\query\MessagesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\models\query\MessagesQuery(get_called_class());
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = time();
            }
            $this->updated_at = time();
            return true;
        }
        return false;
    }
}