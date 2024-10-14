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

    public function actionStart($port = null)
    {

        $server = new ChatServer();

        if ($port) {

            $server->port = $port;
        }

        $server->start();
    }
}