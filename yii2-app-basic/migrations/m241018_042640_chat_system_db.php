<?php

use yii\db\Migration;

class m240000_000000_create_users_table extends Migration
{
    public function up()
    {
        $this->createTable('users', [
            'id' => $this->primaryKey(),
            'username' => $this->string()->notNull(),
            'password' => $this->string()->notNull(),
            'email' => $this->string()->notNull(),
            'avatar' => $this->string(),
            'created_at' => $this->integer(11),
            'updated_at' => $this->integer(11),
        ]);
    }

    public function down()
    {
        $this->dropTable('users');
    }
}

class m240000_000001_create_chat_rooms_table extends Migration
{
    public function up()
    {
        $this->createTable('chat_rooms', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'created_at' => $this->integer(11),
            'updated_at' => $this->integer(11),
            'visibility' => $this->boolean(),
        ]);
    }

    public function down()
    {
        $this->dropTable('chat_rooms');
    }
}

class m240000_000002_create_chat_room_user_table extends Migration
{
    public function up()
    {
        $this->createTable('chat_room_user', [
            'id' => $this->primaryKey(),
            'chat_room_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'joined_at' => $this->integer(11),
            'FOREIGN KEY (chat_room_id) REFERENCES chat_rooms(id)',
            'FOREIGN KEY (user_id) REFERENCES users(id)',
        ]);
    }

    public function down()
    {
        $this->dropTable('chat_room_user');
    }
}

class m240000_000003_create_contacts_table extends Migration
{
    public function up()
    {
        $this->createTable('contacts', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'contact_id' => $this->integer()->notNull(),
            'created_at' => $this->integer(11),
            'FOREIGN KEY (user_id) REFERENCES users(id)',
            'FOREIGN KEY (contact_id) REFERENCES users(id)',
        ]);
    }

    public function down()
    {
        $this->dropTable('contacts');
    }
}

class m240000_000004_create_messages_table extends Migration
{
    public function up()
    {
        $this->createTable('messages', [
            'id' => $this->primaryKey(),
            'chat_room_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'content' => $this->text(),
            'created_at' => $this->integer(11),
            'FOREIGN KEY (chat_room_id) REFERENCES chat_rooms(id)',
            'FOREIGN KEY (user_id) REFERENCES users(id)',
        ]);
    }

    public function down()
    {
        $this->dropTable('messages');
    }
}

class m240000_000005_create_message_status_table extends Migration
{
    public function up()
    {
        $this->createTable('message_status', [
            'id' => $this->primaryKey(),
            'message_id' => $this->integer()->notNull(),
            'status' => "ENUM('sent', 'delivered', 'read')",
            'updated_at' => $this->integer(11),
            'FOREIGN KEY (message_id) REFERENCES messages(id)',
        ]);
    }

    public function down()
    {
        $this->dropTable('message_status');
    }
}

class m240000_000006_create_migration_table extends Migration
{
    public function up()
    {
        $this->createTable('migration', [
            'id' => $this->primaryKey(),
            'migration' => $this->string()->notNull(),
            'batch' => $this->integer()->notNull(),
        ]);
    }

    public function down()
    {
        $this->dropTable('migration');
    }
}