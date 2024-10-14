<?php

namespace app\daemons;



use consik\yii2websocket\events\WSClientEvent;

use consik\yii2websocket\WebSocketServer;

use Ratchet\ConnectionInterface;

use Yii;

class ChatServer extends WebSocketServer

{



    public function init()
    {
        parent::init();

        $this->on(self::EVENT_CLIENT_CONNECTED, function (WSClientEvent $e) {

            $e->client->name = null;
        });
    }

    protected function getCommand(ConnectionInterface $from, $msg)
    {
        $request = json_decode($msg, true);

        return !empty($request['action']) ? $request['action'] : parent::getCommand($from, $msg);
    }



    public function commandChat(ConnectionInterface $client, $msg)

    {

        $request = json_decode($msg, true);

        $result = ['message' => ''];



        if (!$client->name) {

            $result['message'] = Yii::t('app', 'You need to be logged for chatting');
        } elseif (!empty($request['message']) && $message = trim($request['message'])) {

            foreach ($this->clients as $chatClient) {

                $chatClient->send(json_encode([

                    'type' => 'chat',

                    'from' => $client->name,

                    'date' => date('Y-m-d H:i:s'),

                    'message' => $message

                ]));
            }
        } else {

            $result['message'] = Yii::t('app', 'Enter a message');
        }



        $client->send(json_encode($result));
    }



    public function commandSetName(ConnectionInterface $client, $msg)

    {

        $request = json_decode($msg, true);

        $result = ['message' => Yii::t('app', 'User updated')];



        if (!empty($request['name']) && $name = trim($request['name'])) {

            $usernameFree = true;

            foreach ($this->clients as $chatClient) {

                if ($chatClient != $client && $chatClient->name == $name) {

                    $result['message'] = Yii::t('app', 'This user is logged in other computer and is chatting, you can´t use it');

                    $usernameFree = false;

                    break;
                }
            }



            if ($usernameFree) {

                $client->name = $name;
            }
        } else {

            $result['message'] = Yii::t('app', 'Invalid username');
        }



        $client->send(json_encode($result));
    }
}