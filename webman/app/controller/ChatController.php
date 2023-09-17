<?php

namespace app\controller;

use support\Request;
use support\Response;

class ChatController
{
    /**
     * @var array|array[]
     */
    private static array
        $parametersView = [
        'title' => [
            'index' => 'Мессенджер'
            ]
        ];

    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
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

        return view('chat/view', [
            'expression' => $expression[rand(0, count($expression)-1)],
            'maxChar' => config('app.max_char_msg', 100),
            'title' => self::$parametersView['title']['index']
            ]);
    }

    /**
     * @param Request $request
     * @return void
     */
    public function view(Request $request)
    {

    }

    /**
     * @param Request $request
     * @return Response
     */
    public function json(Request $request): Response
    {
        return json(['code' => 0, 'msg' => 'ok']);
    }
}
