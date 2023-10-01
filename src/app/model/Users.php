<?php

namespace app\model;

use support\Model;

class Users extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    protected $fillable = ['name'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
    
    /*public function add($name)
    {
        return $this->insert(['name' => $name]);
    }*/
    
    public static function authentication($nick, $password){
        $user = self::where('nick', $nick)->first();
        if(!empty($user) and password_verify($password, $user['password'])){
            return $user['id'];            
        }
        return false;
    }

    /**
     * @param $nick
     * @return bool
     */
    public static function nickCheck($nick): bool
    {
        if(self::where('nick', $nick)->count() > 0)
            return true;
        else 
            return false;
    }

    /**
     * @param $data
     * @return int
     */
    public static function userRegister($data): int
    {
        return self::insertGetId([
            'name' => $data['registerName'], 
            'nick' => $data['registerUsername'],
            'password' => password_hash($data['registerPassword'], PASSWORD_DEFAULT),
            'avatar' => $data['avatarName']
        ]);
    }
}