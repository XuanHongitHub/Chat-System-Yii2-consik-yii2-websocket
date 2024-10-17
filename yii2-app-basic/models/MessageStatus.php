<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "message_status".
 *
 * @property int $id
 * @property int $message_id
 * @property int $user_id
 * @property int $read_at
 *
 * @property Messages $message
 * @property User $user
 */
class MessageStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'message_status';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['message_id', 'user_id'], 'required'],
            [['message_id', 'user_id'], 'integer'],
            [['read_at'], 'integer'],
            [['read_at'], 'default', 'value' => null],
            [['message_id'], 'exist', 'skipOnError' => true, 'targetClass' => Messages::class, 'targetAttribute' => ['message_id' => 'id']],
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
            'message_id' => 'Message ID',
            'user_id' => 'User ID',
            'read_at' => 'Read At',
        ];
    }

    /**
     * Gets query for [[Message]].
     *
     * @return \yii\db\ActiveQuery|\app\models\query\MessagesQuery
     */
    public function getMessage()
    {
        return $this->hasOne(Messages::class, ['id' => 'message_id']);
    }

    /**
     * Gets query for [[User]].
     *
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\models\query\MessageStatusQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\models\query\MessageStatusQuery(get_called_class());
    }
}