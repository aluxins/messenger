<?php
namespace app\controller;

use support\Request;
use support\Log;
use app\model\Users;
use Intervention\Image\ImageManagerStatic as Image;
use support\Response;
use Webman\Captcha\CaptchaBuilder;
use Webman\Captcha\PhraseBuilder;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;

class UserController
{
    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return redirect(config('app.base_url').'/index');
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function quit(Request $request): Response
    {
        $request->session()->flush();
        return redirect(config('app.base_url').'/index');
    }


    /**
     * @var array|int[]
     */
    private static array $parametersForm = [
        'maxLength' => 64,
        'maxAvatarDefault' => 16,
        'maxSizeFile' => 4 //Mb
    ];

    /**
     * @var array|array[]
     */
    private static array $parametersView = [
        'title' => [
            'login' => 'Авторизация пользователя',
            'register' => 'Регистрация нового пользователя'
            ]
        ];

    /**
     * @param Request $request
     * @return Response
     */
    public function login(Request $request): Response
    {
        $error = ['msg' => '', 'description' => ''];
        if (count($request->post()) > 0){
            try { //Проверка валидности данных
                $data = v::input($request->post(), [
                    'loginName' => v::length(1, self::$parametersForm['maxLength'])->setName('loginName'),
                    'loginPassword' => v::length(1, self::$parametersForm['maxLength'])->setName('loginPassword')
                ]);
                
                if($request->post('loginCheck'))
                    $data = v::input($request->post(), [
                        'loginCheck' => v::length(0, 1)->setName('loginCheck')
                    ]);
                    
                //Авторизация
                $id_user = Users::authentication($data['loginName'], $data['loginPassword']);
                if($id_user){
                    $session = $request->session();
                    $session->set('id_user', $id_user);
                    Log::info('Login user '. $id_user);
                    return redirect(config('app.base_url').'/chat');
                }
                else $error['msg'] = 'Пользователь с таким никнеймом или паролем не найден';

            } catch (ValidationException $e) { //Если данные не валидны
                $error['validate'] = $e->getParams();
                $error['msg'] = 'Данные введены некорректно!';
            }
        }
        
        return view('user/auth', [
            'type' => 'login',
            'title' => 'Авторизоваться или зарегистрироваться',
            'error' => $error,
            'parametersForm' => self::$parametersForm,
            'data' => self::getViewData($request)
            ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function register(Request $request): Response
    {
        $session = $request->session();
        $error = ['msg' => '', 'description' => ''];
        $registerCheck = 0;
        $data = $request->post();
        if (count($data) > 0){
            //Удаление пробелов в начале и конце строки, указанных полей формы.
            $data = self::getTrimData($data, ['registerName','registerUsername']);
            try { //Проверка валидности данных
                $data = v::input($data, [
                    'registerUsername' => v::length(1, self::$parametersForm['maxLength'])->setName('registerUsername'),
                    'registerName' => v::length(1, self::$parametersForm['maxLength'])->setName('registerName'),
                    'registerAvatarDefault' => v::length(1, self::$parametersForm['maxLength'])->setName('registerAvatarDefault'),
                    'registerPassword' => v::length(1, self::$parametersForm['maxLength'])->setName('registerPassword'),
                    'registerRepeatPassword' => v::length(1, self::$parametersForm['maxLength'])
                                                ->equals($request->post('registerPassword'))->setName('registerRepeatPassword'),
                    'registerCaptcha' => v::length(5, 5)->contains(strtolower($session->get('captcha')))->setName('registerCaptcha'),
                    'registerCheck' => v::length(0, 1)->setName('registerCheck')
                ]);
                
                $session->set('captchaValidate', $data['registerCaptcha']);
                
                //Проверка загруженного файла изображения
                $dataUpload = $request->file();
                if($dataUpload['registerAvatar'] && $dataUpload['registerAvatar']->isValid()){
                    v::input($dataUpload, [
                        'registerAvatar' => v::image()->size(null, self::$parametersForm['maxSizeFile'].'MB')->setName('registerAvatar')
                    ]);

                    //Изменяем размер и тип изображения
                    $size = 100;
                    $fileName = md5(microtime(true).'.'.rand(0,10000)).'.webp';
                    $img = Image::make($dataUpload['registerAvatar']->getPathName())->fit($size)->encode('webp', 100);
                    $img->save(public_path().'/upload/'.$fileName);
                    $data['avatarName'] = $fileName;
                }
                //Если файл не загружен, получаем имя файла аватара из стандарных.
                else{
                    $data['avatarName'] = $data['registerAvatarDefault'] == 'avatar-0.webp'? 
                        'avatar-' . rand(1, self::$parametersForm['maxAvatarDefault']) . '.webp' :
                        $data['registerAvatarDefault'];
                }
                
                //Регистрация. Проверка на занятость никнейма.
                if(Users::nickCheck($data['registerUsername']))
                    $error = [  'msg' =>'Пользователь с таким никнеймом зарегистрирован',
                                'description' => 'Пожалуйста, укажите другой никнейм.'
                            ];
                else{
                    //Выполняем регистрацию при успешной проверке данных регистрационной формы.
                    $id_user = Users::userRegister($data);
                    if($id_user){
                        $session->set('id_user', $id_user);
                        $session->delete('captchaValidate');
                        $session->delete('captcha');
                        return redirect(config('app.base_url').'/chat');
                    }
                }
                    
            } catch (ValidationException $e) { //Если данные не валидны
                $error['validate'] = $e->getParams();
                $error['msg'] = 'Данные введены некорректно!';
                $error['description'] = match ($error['validate']['name']) {
                    'registerAvatar'        => 'Файл должен являться изображением, напр. jpeg, pgn, webp и пр. ' .
                                            'Размер файла не более ' . self::$parametersForm['maxSizeFile'] . ' Мб.',
                    'registerRepeatPassword'=> 'Введенные пароли не совпадают.',
                    'registerCheck'         => 'Примите условия и соглашения.',
                    'registerCaptcha'       => 'Некорректно введен проверочный код (Captcha).',
                    default => '',
                };
                
            }
        }
        else{
            $session->delete('captchaValidate');
            $session->delete('captcha');
            $registerCheck = 1;
        }
        

        return view('user/auth', [
            'type' => 'register',
            'title' => 'Авторизоваться или зарегистрироваться',
            'error' => $error,
            'parametersForm' => self::$parametersForm,
            'data' => self::getViewData($request, $registerCheck)
        ]);
    }

    /**
     * @param $request
     * @param int $registerCheck
     * @return array
     */
    private static function getViewData($request, int $registerCheck = 1): array
    {
        $session = $request->session();
        return [
            'registerName' => trim($request->post('registerName', '')),
            'registerUsername' => trim($request->post('registerUsername', '')),
            'registerAvatarDefault' => $request->post('registerAvatarDefault'),
            'registerPassword' => $request->post('registerPassword'),
            'registerRepeatPassword' => $request->post('registerRepeatPassword'),
            'registerCaptcha' => (!empty($session->get('captchaValidate')))?$session->get('captchaValidate'):$request->post('registerCaptcha'),
            'registerCheck' => $request->post('registerCheck', $registerCheck),
            'loginName' => trim($request->post('loginName', '')),
            'loginPassword' => $request->post('loginPassword'),
            'loginCheck' => $request->post('loginCheck'),
            'loginCaptcha' => $request->post('loginCaptcha'),
            'imgCaptcha' => self::getCaptcha($request)
        ];
    }

    private static function getTrimData($data, $arrValue = []){
        foreach ($data as $key => $val)
            if(in_array($key, $arrValue))
                $data[$key] = trim($val);
        return $data;
    }

    /**
     * @param $request
     * @param int $length
     * @param string $chars
     * @return string
     */
    private static function getCaptcha($request, int $length = 5, string $chars = '123456789ABCDGHKLMNPRSTUVWXYZ'): string
    {
        $session = $request->session();
        if(!empty($session->get('captchaValidate')))
            $text = strtoupper($session->get('captchaValidate'));
        else
            $text = null;
            
        $builder_set = new PhraseBuilder($length, $chars);
        $builder = new CaptchaBuilder($text, $builder_set);
        $builder->build(150, 40);
        $img_content = $builder->get();
        $img_content_str = strtolower($builder->getPhrase());
        $session->set('captcha', $img_content_str);
        
        return base64_encode($img_content);
    }
}