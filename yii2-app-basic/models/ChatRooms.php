<?php

namespace app\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "chat_rooms".
 *
 * @property int $id
 * @property string $name
 * @property boolean $visibility
 * @property int $created_by
 * @property int $created_at
 * @property int $updated_at
 *
 * @property ChatRoomUser[] $chatRoomUsers
 * @property User $createdBy
 * @property Messages[] $messages
 */
class ChatRooms extends \yii\db\ActiveRecord
{
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value' => date('Y-m-d H:i:s'),
            ],
        ];
    }
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chat_rooms';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'created_by'], 'required'],
            [['created_by'], 'integer'],
            [['visibility'], 'boolean'],
            [['name'], 'string', 'max' => 255],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'created_by' => 'Created By',
            'created_at' => 'Created At',
            'visibility' => 'Visibility',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[ChatRoomUser]].
     *
     * @return \yii\db\ActiveQuery|\app\models\query\ChatRoomUserQuery
     */
    public function getChatRoomUser()
    {
        return $this->hasMany(ChatRoomUser::class, ['chat_room_id' => 'id']);
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery|\app\models\query\UserQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * Gets query for [[Messages]].
     *
     * @return \yii\db\ActiveQuery|\app\models\query\MessagesQuery
     */
    public function getMessages()
    {
        return $this->hasMany(Messages::class, ['chat_room_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\models\query\ChatRoomsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\models\query\ChatRoomsQuery(get_called_class());
    }

    public function getChatRoomUserCount()
    {
        return $this->getChatRoomUser()->count();
    }

    // public function getUsers()
    // {
    //     return $this->hasMany(User::class, ['id' => 'user_id'])
    //         ->viaTable(
    //             'chat_room_user',
    //             ['chat_room_id' => 'id'],
    //             ['user_id' => 'id']
    //         );
    // }
    public function getUsers()
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])
            ->viaTable('chat_room_user', ['chat_room_id' => 'id'], null);
    }

    public function getLastMessage()
    {
        return Messages::find()
            ->where(['chat_room_id' => $this->id])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(1)
            ->one();
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_by = Yii::$app->user->id;
            }
            return true;
        }
        return false;
    }
}