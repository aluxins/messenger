<?php

namespace app\controller;

use support\Request;

class IndexController
{
    private static 
        $parametersView = [
        'title' => [
            'index' => 'Демоверсия мессенджера'
            ]
        ];
        
    public function index(Request $request)
    {
        $host = explode(":", $request->header()['host']);
        return view('index/view', ['title' => self::$parametersView['title']['index']]);
    }

    public function view(Request $request)
    {
        return view('index/view', ['title' => self::$parametersView['title']['index']]);
    }

    public function json(Request $request)
    {
        return json(['code' => 0, 'msg' => 'ok']);
    }
    
}
