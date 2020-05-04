<?php

namespace Mibao\LaravelFramework\Models;

use Log;
use Auth;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Mibao\LaravelFramework\Helpers\Redis;
// use Mibao\LaravelFramework\Models\Work;
use SMartins\PassportMultiauth\HasMultiAuthApiTokens;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\Models\Media;
use Spatie\Permission\Traits\HasRoles;
use Webpatser\Uuid\Uuid;

class WechatUser extends Authenticatable implements HasMedia
{
    use HasMultiAuthApiTokens, Notifiable;
    use HasRoles;
    use HasMediaTrait;

    public $incrementing = false;
    public $viewOpenid = false;

    /*
     * 添加属性
     */
    protected $appends = ['avatarUrl'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['appid', 'app_type', 'openid', 'nickname', 'sex', 'language', 'province', 'city', 'country', 'headimgurl', 'privilege', 'unionid', ];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    // protected $hidden = [ 'openid', 'language', 'province', 'city', 'country', 'privilege', 'unionid', ];
    protected $visible = ['id','nickname', 'headimgurl', 'sex', 'user', 'created_at'];
    /**
     * 应该被转化为原生类型的属性
     *
     * @var array
     */

    protected $casts = [
        'privilege' => 'array',
    ];
    /**
     * 获取用户是否关注公众号
     */
    public function getIsSubscribeAttribute()
    {
        $this->viewOpenid = true;
        $openid= $this->openid;
        $app = app('wechat.official_account');
        $user = $app->user->get($openid);
        return isset($user['subscribe']) ? $user['subscribe'] : null;
    }
    public function getHaveWorkAttribute()
    {
        // return Work::where('wechat_user_id',$this->id)->count() > 0 ;
        // return Work::where('wechat_user_id',$this->id)->get() ;
    }
    /**
     * 作者openid
     *
     * @return bool
     */
/*     public function getOpenidAttribute($value)
    {
        // return Auth::user()->can('view data') || $this->viewOpenid ? $value : null;
        return $value;
    } */
    /**
     * 为用户获取用户性别
     *
     * @return bool
     */
    public function getSexAttribute($value)
    {
        switch ($value) {
            case '1':
                return '男';
                break;
            case '2':
                return '女';
                break;
            default:
                return '';
                break;
        }
    }
    /**
     * 获取用户最高分的作品
     */
    public function getTopWorkAttribute()
    {
       $topWork = Redis::readUserTopWorkId($this->id);
        if($topWork){
            $work = Work::find($topWork['work_id']);
        }else{
            $work = Work::where('wechat_user_id', $this->id)->orderby('created_at', 'DESC')->first();
        }
        if($work){
            $work->wechat_user = [
                'nickname'=> $this->nickname,
                'headimgurl'=> $this->headimgurl,
            ];
            return $work;
        }
        return null;
    }
    /**
     * 获取用户排名
     */
    public function getRankAttribute()
    {
        $rank = Redis::readUserRankOrder($this->id);

        // 如果redis里面没排名，从数据库里面取数更新
        if(!$rank){
            // $works = Game::where('wechat_user_id', $this->wechat_user_id)->get();
            // foreach ($works as $key => $work) {
                // Redis::saveUserWorkScore($this->wechat_user_id, $work->id, $work->score);
            // }
            // $rank = Redis::readUserRankOrder($this->id);
        }
        return $rank;
    }
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
     * 可以认证字段
     *
     */
    public function findForPassport($username)
    {
        return $this->orWhere('openid', $username)->orWhere('nickname', $username)->first();
    }
    /**
     * 获取手机对应的用户
     */
    public function user(){
        return $this->belongsTo('Mibao\LaravelFramework\Models\User', 'user_id', 'id');
        // return $this->hasOne('Mibao\LaravelFramework\Models\User', 'user_id', 'id');
        // return $this->hasOne('Mibao\LaravelFramework\Models\User', 'id', 'user_id');
        // return $this->hasMany('Mibao\LaravelFramework\Models\WechatUser', 'id', 'user_id');
    }

}
