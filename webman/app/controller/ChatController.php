<?php

namespace app\controller;

use support\Request;

class ChatController
{
    private static 
        $parametersView = [
        'title' => [
            'index' => 'Мессенджер'
            ]
        ];
        
    public function index(Request $request)
    {
        $expression = [
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

        return view('chat/view', [
            'expression' => $expression[rand(0, count($expression)-1)],
            'maxChar' => config('app.max_char_msg', 100),
            'title' => self::$parametersView['title']['index']
            ]);
    }

    public function view(Request $request)
    {

    }

    public function json(Request $request)
    {
        return json(['code' => 0, 'msg' => 'ok']);
    }
}
