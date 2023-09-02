<?php

namespace app\controller;

use support\Request;

class ChatController
{
    private static 
        $parametersView = [
        'title' => [
            'index' => 'Чат'
            ]
        ];
        
    public function index(Request $request)
    {
        $title = [
            "Будь собой",
            "Птица мечты",
            "Подняться выше",
            "Вы имеете значение",
            "Ты сможешь",
            "Прими себя",
            "Доверяй себе",
            "Постоянство = успех",
            "Оставайся сфокусированным",
            "Двигаться вперед",
            "Попробуйте еще раз",
        ];
        $host = explode(":", $request->header()['host']);
        $session = $request->session();

        return view('chat/view', [
            'id_user' => $session->get('id_user'), 
            'ip' => $host[0].":40991", 
            'title' => $title[rand(0, count($title)-1)],
            'maxChar' => config('app.max_char_msg', 100)
            ]);
    }

    public function view(Request $request)
    {
        //return view('view', ['name' => 'webman']);
    }

    public function json(Request $request)
    {
        return json(['code' => 0, 'msg' => 'ok']);
    }
}
