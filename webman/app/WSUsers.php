<?php
namespace app\MyProject;
//use support\Db;
use app\model\Users;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;

class WSUsers{
    private static  $array_user = [],// { id_user = { connection1, connection2, ... } }
                    $array_id = [], // { Sec_WebSocket_Key = id_user }
                    $array_users_info = []; /*{ 'name' => , 'avatar' => , 'lastmess' => , 'unreadmess' => , 'status' => , };*/
                   
    //Добавляем пользователей в массив $array_id
    public static function AddId($connection){
        $id_user = $connection->session->get('id_user');
        $Sec_WebSocket_Key = $connection->Sec_WebSocket_Key;

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
    public static function UnsetUser($key){
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
    public static function Message($data, $connection){
        $data_obj = json_decode($data, true);
        if(!empty($connection->session) and !empty($data_obj)){
            $unix_time = microtime(true);
            $id_user = $connection->session->get('id_user');
            
            try { //Проверка валидности данных
                $data_obj = v::input($data_obj, [
                    'fId' => v::digit(),
                    'msg' => v::length(1, config('app.max_char_msg', 100))
                ]);
    
                $data_obj["fId"] = (int) ($data_obj["fId"] == '') ? 0 : $data_obj["fId"];
                
                //Сообщения пользователям
                if($data_obj["fId"] != 0){
                    
                    $msg = self::JsonEncode($id_user, $data_obj["msg"], $data_obj["fId"], $unix_time);
                    
                    //Свое сообщение
                    if(array_key_exists($id_user, self::$array_user)){
                        foreach (self::$array_user[$id_user] as $cocketConnection)
                            $cocketConnection->send($msg);
                    }

                    //Приватное сообщение
                    if(array_key_exists($data_obj["fId"], self::$array_user)){
                        foreach (self::$array_user[$data_obj["fId"]] as $cocketConnection)
                            $cocketConnection->send($msg);
                    }
                }
                
                //Системные сообщения
                else{
                    switch ($data_obj["msg"]) {
                        //Список контактов
                        case "start":
                            $connection->send(self::JsonEncode($id_user, ['contacts' => self::$array_users_info], 0, $unix_time));
                            break;
                        case "status":
                            //
                            break;
                        default:
                           //
                    }
                }
            } catch (ValidationException $e) { //Если данные не валидны
                $connection->send(self::JsonEncode($id_user, ['error' => 'not valid'], 0, $unix_time));
            }
        }
    }
    
    //Рассылка системных сообщений всем подключенным к веб-сокету
    public static function MessageSys($type, $data){
        foreach(self::$array_user as $user => $allConnection)
            foreach($allConnection as $cocketConnection)
            $cocketConnection->send(self::JsonEncode($user, [$type => $data], 0, microtime(true)));       
    }
    
    //Упаковка сообщения в Json
    private static function JsonEncode($id, $msg, $fId, $time){
        $data = [
            "uId" => (int) $id,
            "msg" => $msg,
            "fId" => (int) $fId,
            "time" => $time,
        ];
        return json_encode($data);
    }

}
