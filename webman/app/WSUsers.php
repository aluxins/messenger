<?php
namespace app;

use app\model\Users;
use app\model\Messages;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;
use support\Log;
use DateTime;

class WSUsers{
    private static array
        $array_user = [],// { id_user = { connection1, connection2, ... } }
        $array_id = [], // { Sec_WebSocket_Key = id_user }
        $array_users_info = []; /* id_user => { 'name' => , 'avatar' => , 'lastmess' => , 'unreadmess' => , 'status' => , } */
                   
    //Добавляем пользователей в массив $array_id

    /**
     * @param $connection
     * @return void
     */
    public static function AddId($connection): void
    {
        $id_user = $connection->session->get('id_user');
        $Sec_WebSocket_Key = $connection->Sec_WebSocket_Key;
        Log::info('User ID: '. $id_user);
        self::$array_user[$id_user][] = $connection;

        //Получаем данные пользователя из БД
        $userDB = Users::where('id', $id_user)->first();
        self::$array_users_info[$id_user] = [
                'name' => $userDB['name'],
                'avatar' => $userDB['avatar'],
                'lastmess' => '',
                'unreadmess' => '',
                'status' => microtime(true),
            ];

        //Добавляем пользователя с его WebSocket_Key
        self::$array_id[$Sec_WebSocket_Key] = $id_user;

        //Рассылаем новый контакт
        self::MessageSys('contacts', [$id_user => self::$array_users_info[$id_user]]);
    }
  
    //Удаляем пользователя из всех массивов

    /**
     * @param $key
     * @return void
     */
    public static function UnsetUser($key): void
    {
        if(array_key_exists($key, self::$array_id)){
            $id_unset = self::$array_id[$key];
            
            //Удаляем из массива $array_user сеанс Connection
            if(array_key_exists($id_unset, self::$array_user)){
                foreach (self::$array_user[$id_unset] as $array_key => $cocketConnection)
                    if($cocketConnection->Sec_WebSocket_Key == $key)
                        unset(self::$array_user[$id_unset][$array_key]);
            
                if(count(self::$array_user[$id_unset]) == 0)
                    unset(self::$array_user[$id_unset]);
            }
                        
            //Удаляем из массива $array_id ключ Sec_WebSocket_Key
            unset(self::$array_id[$key]);
                
            //Удаляем из массива $array_users_info id пользователя, если нет активных сансов
            if(!array_key_exists($id_unset, self::$array_user) and array_key_exists($id_unset, self::$array_users_info))
                unset(self::$array_users_info[$id_unset]);
            
            //Рассылаем удаление контакта    
            self::MessageSys('delete', $id_unset);
        }
    }
    
    //Обработка сообщений от пользователей

    /**
     * @param $data
     * @param $connection
     * @return void
     */
    public static function Message($data, $connection): void
    {
        $data_obj = json_decode($data, true);
        if(!empty($connection->session) and !empty($data_obj)){

            $date_time = new DateTime();
            $id_user = $connection->session->get('id_user');

            try { //Проверка валидности данных
                $data_obj = v::input($data_obj, [
                    'fId' => v::digit(),
                    'msg' => v::length(1, config('app.max_char_msg', 100)),
                    'type' => v::length(1, 10)->containsAny(['archive', 'msg', 'start'])
                ]);
    
                $data_obj["fId"] = (int) ($data_obj["fId"] == '') ? 0 : $data_obj["fId"];
                
                switch ($data_obj["type"]) {
                    
                    //Сообщения пользователям
                    case "msg":    
                        if($data_obj["fId"] != 0){
                            
                            $msg = self::JsonEncode($id_user, $data_obj["msg"], $data_obj["fId"], $date_time->format('U.u'));
        
                            //Свое сообщение
                            if(array_key_exists($id_user, self::$array_user)){
                                foreach (self::$array_user[$id_user] as $cocketConnection)
                                    $cocketConnection->send($msg);
                            }
        
                            //Сообщение собеседнику
                            if(array_key_exists($data_obj["fId"], self::$array_user)){
                                foreach (self::$array_user[$data_obj["fId"]] as $cocketConnection)
                                    $cocketConnection->send($msg);
                            }
                            
                            //Сообщение от echo-bot
                            if(config('app.echo_bot_id') and config('app.echo_bot_id') == $data_obj["fId"])
                                $connection->send(
                                        self::JsonEncode(config(
                                            'app.echo_bot_id'), 
                                            "Тест ОК \n" . $date_time->format("Y-m-d H:i:s") . " \n" . 
                                                strlen($data_obj["msg"]) . " - " . hash( 'crc32', $data_obj["msg"]),
                                            $data_obj["fId"], $date_time->format('U.u').'0'
                                        )
                                );
                            //Добавляем сообщения в БД, кроме сообщений echo-bot
                            else
                                Messages::insert([
                                    'uid' => $id_user, 
                                    'fid' => $data_obj["fId"],
                                    'message' => $data_obj["msg"],
                                    'microtime' => $date_time->format("Y-m-d H:i:s.u")
                                ]);
                        }
                        break;

                        //Список контактов
                        case "start":
                            //Добавляем контакт echo-bot
                            if(config('app.echo_bot_id'))
                                self::$array_users_info[config('app.echo_bot_id')] = [
                                        'name' => 'Echo-Bot', 
                                        'avatar' => 'avatar-bot.webp', 
                                        'lastmess' => '', 
                                        'unreadmess' => '', 
                                        'status' => microtime(true),
                                ];
                            
                            $connection->send(self::JsonEncode($id_user, ['contacts' => self::$array_users_info], 0, $date_time->format('U.u'), 'contacts'));
                            break;
                        //Архив сообщений
                        case "archive":
                            $archive_obj = Messages::selectRaw('uid as uId, message as msg, fid as fId, UNIX_TIMESTAMP(microtime) as time')->where([
                                    ['uid', '=', $id_user],
                                    ['fid', '=', $data_obj["fId"]],
                                ])->orWhere([
                                    ['uid', '=', $data_obj["fId"]],
                                    ['fid', '=', $id_user],
                                ])->orderBy('microtime', 'desc')->limit(10)->get();
                            
                            $messages = [];
                            foreach ($archive_obj as $message) {
                                $messages[] = $message;
                            }
                            //sleep(2);
                            $connection->send(self::JsonEncode($id_user,  ['archive' => $messages], $data_obj["fId"], $date_time->format('U.u'), 'archive'));
                            break;
                        default:
                           //
                    }
            } catch (ValidationException $e) { //Если данные не валидны
                $connection->send(self::JsonEncode($id_user, ['error' => 'not valid: ' . $e->getCode()], 0, $date_time->format('U.u'), 'error'));
            }
        }
    }
    
    //Рассылка системных сообщений всем подключенным к веб-сокету

    /**
     * @param $type
     * @param $data
     * @return void
     */
    public static function MessageSys($type, $data): void
    {
        foreach(self::$array_user as $user => $allConnection)
            foreach($allConnection as $cocketConnection)
            $cocketConnection->send(self::JsonEncode($user, [$type => $data], 0, microtime(true), $type));       
    }
    
    //Упаковка сообщения в Json

    /**
     * @param $id
     * @param $msg
     * @param $fId
     * @param $time
     * @param string $type
     * @return false|string
     */
    private static function JsonEncode($id, $msg, $fId, $time, string $type = 'msg'): false|string
    {
        $data = [
            "uId" => (int) $id,
            "msg" => $msg,
            "fId" => (int) $fId,
            "time" => $time,
            "type" => $type,
        ];
        return json_encode($data);
    }

}
