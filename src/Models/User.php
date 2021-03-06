<?php

namespace Mibao\LaravelFramework\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use SMartins\PassportMultiauth\HasMultiAuthApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Webpatser\Uuid\Uuid;
use Overtrue\EasySms\PhoneNumber;

class User extends Authenticatable
{
    use HasMultiAuthApiTokens, Notifiable;
    use HasRoles;
    public $incrementing = false;
    protected $guard_name = 'api';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'phone'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    // protected $hidden = [
    //     'password', 'remember_token',
    // ];
    protected $visible = [ 'id', 'avatar', 'name', 'email', 'phone'];
    /**
     * 生成新模型时，生成uuid
     */
    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model{$model->getKeyName()} = (string) Uuid::generate(4);
        });
    }
    /**
     * 除了邮件还可以用用户名认证
     *
     */
    public function findForPassport($username)
    {
        return $this->orWhere('email', $username)->orWhere('name', $username)->first();
    }
    /**
     * 获取微信帐号信息（包含公众号、小程序等）
     */
    public function wechatUser()
    {
         return $this->hasMany('Mibao\LaravelFramework\Models\WechatUser', 'user_id', 'id');
    }
    public function routeNotificationForEasySms($notification)
    {
        return new PhoneNumber($this->phone, '86');
    }
}
