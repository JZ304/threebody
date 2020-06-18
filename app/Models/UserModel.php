<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class UserModel extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * 表名
     * @var string
     */
    protected $table = 'user_info';

    /**
     * 批量赋值字段
     * @var array
     */
    protected $fillable = ['name', 'tel', 'password'];

    /**
     * 隐藏字段
     * @var array
     */
    protected $hidden = ['password', 'created_at', 'updated_at', 'deleted_at',''];

    /**
     * 数据库时间格式
     * @var string
     */
    protected $dateFormat = 'U';


    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
