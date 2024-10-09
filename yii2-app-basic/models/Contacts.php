<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "contacts".
 *
 * @property int $id
 * @property int $user_id
 * @property int $contact_user_id
 * @property int $created_at
 * @property int $updated_at
 *
 * @property User $contactUser
 * @property User $user
 */
class Contacts extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'contacts';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'contact_user_id', 'created_at', 'updated_at'], 'required'],
            [['user_id', 'contact_user_id', 'created_at', 'updated_at'], 'integer'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['contact_user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['contact_user_id' => 'id']],
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
            'contact_user_id' => 'Contact User ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[ContactUser]].
     *
     */
    public function getContactUser()
    {
        return $this->hasOne(User::class, ['id' => 'contact_user_id']);
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
     * @return \app\models\query\ContactsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\models\query\ContactsQuery(get_called_class());
    }
    public function getChatRoom()
    {
        return $this->hasOne(ChatRooms::class, ['id' => 'chat_room_id']);
    }
    public function getLastMessage()
    {
        return Messages::find()
            ->where([
                'or',
                ['user_id' => Yii::$app->user->id, 'recipient_id' => $this->contact_user_id],
                ['user_id' => $this->contact_user_id, 'recipient_id' => Yii::$app->user->id]
            ])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(1)
            ->one();
    }

    public function getMessages()
    {
        return $this->hasMany(Messages::class, ['recipient_id' => 'id']);
    }
}