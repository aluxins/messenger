<?php
namespace app\middleware;

use ReflectionClass;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use support\View;

class WrapView implements MiddlewareInterface
{
    //Добавляем header и footer к страницам сайта.
    /**
     * @param Request $request
     * @param callable $handler
     * @return Response
     * @throws \ReflectionException
     */
    public function process(Request $request, callable $handler): Response
    {
        $host = explode(":", $request->header()['host']);
        $header = view('template/header', ['ip' => $host[0].":40991", 'controller' => $request->controller])->rawBody();
        $footer = view('template/footer')->rawBody();
        
        //Считываем параметры в свойстве контроллера parametersView, которые необходимо вставить в шаблон.
        $controller = new ReflectionClass($request->controller);
        $parametersView = $controller->getDefaultProperties()['parametersView'] ?? [];
        
        if(count($parametersView) > 0){
            foreach ($parametersView as $key => $val){
                if(array_key_exists($request->action, $val)){
                    $header = str_replace("{".$key."}", $val[$request->action], $header);
                    $footer = str_replace("{".$key."}", $val[$request->action], $footer);
                }
            }
        }
        
        $header = preg_replace('/{(\w+)}/', '', $header);
        $footer = preg_replace('/{(\w+)}/', '', $footer);   
        
        View::assign(['header' => $header, 'footer' => $footer]);

        return $handler($request);
    }
}