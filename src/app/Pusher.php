<?php
namespace app;

require 'WSUsers.php';

use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Session;


class Pusher
{
    /**
     * @param TcpConnection $connection
     * @return void
     */
    public function onConnect(TcpConnection $connection): void
    {
	    //Передаем в массив $array_id ключ Sec_WebSocket_Key и идентификатор пользователя при удачном подлючении клиента к Веб-сокету
    	$connection->onWebSocketConnect = function($connection){
    	    //Определяем идентификатор сессии из COOKIE и присваеваем к текущему сеансу
            if(!empty($_SERVER['HTTP_COOKIE']) and preg_match('/^PHPSID=([0-9a-z]+)/', $_SERVER['HTTP_COOKIE'], $matches)){
                $session = new Session($matches[1]);
                $connection->session = $session;
                $connection->Sec_WebSocket_Key = $_SERVER['HTTP_SEC_WEBSOCKET_KEY'];
                WSUsers::AddId($connection);
            }
        };
        
    }

    /**
     * @param TcpConnection $connection
     * @param $http_buffer
     * @return void
     */
    public function onWebSocketConnect(TcpConnection $connection, $http_buffer): void
    {
        //echo "onWebSocketConnect\n" . $http_buffer;
    }

    /**
     * @param TcpConnection $connection
     * @param $data
     * @return void
     */
    public function onMessage(TcpConnection $connection, $data): void
    {
	    WSUsers::Message($data, $connection);
    }

    /**
     * @param TcpConnection $connection
     * @return void
     */
    public function onClose(TcpConnection $connection): void
    {
        if(!empty($connection->Sec_WebSocket_Key))
            WSUsers::UnsetUser($connection->Sec_WebSocket_Key);
    }
}