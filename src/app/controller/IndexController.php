<?php

namespace app\controller;

use support\Request;
use support\Response;

class IndexController
{
    /**
     * @var array|array[]
     */
    private static array
        $parametersView = [
        'title' => [
            'index' => 'Демоверсия мессенджера'
            ]
        ];

    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return view('index/view', ['title' => self::$parametersView['title']['index']]);

    }

    /**
     * @param Request $request
     * @return Response
     */
    public function view(Request $request): Response
    {
        return view('index/view', ['title' => self::$parametersView['title']['index']]);
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
