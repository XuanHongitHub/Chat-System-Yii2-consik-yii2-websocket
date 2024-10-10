<?php

namespace app\daemons;

use consik\yii2websocket\WebSocketServer;
use Ratchet\ConnectionInterface;

class CommandsServer extends WebSocketServer
{

    /**
     * override method getCommand( ... )
     *
     * For example, we think that all user's message is a command
     */
    protected function getCommand(ConnectionInterface $from, $msg)
    {
        return $msg;
    }

    /**
     * Implement command's method using "command" as prefix for method name
     *
     * method for user's command "ping"
     */
    function commandPing(ConnectionInterface $client, $msg)
    {
        $client->send('Pong');
    }
}