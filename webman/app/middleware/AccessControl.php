<?php
namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class AccessControl implements MiddlewareInterface
{
    //Проверка аутентификации пользователя для доступа к закрытым ресурсам
    /**
     * @param Request $request
     * @param callable $handler
     * @return Response
     */
    public function process(Request $request, callable $handler): Response
    {
        if (session('id_user')) 
            return $handler($request);
        else
            return redirect('/user/login');
    }
}
