<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Log;
use Auth;
use App\Http\Controllers\WeChat\BaseController as WeChat;
use App\Http\Controllers\Api\RedisController;
use App\Models\Work;

class WechatUser extends Authenticatable
{
    use HasApiTokens, Notifiable;
    public $viewOpenid = false;
    /*
     * 添加属性
     */
    protected $appends = ['rank','top_work','have_work'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['openid', 'nickname', 'sex', 'language', 'province', 'city', 'country', 'headimgurl', 'privilege', 'unionid', ];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    // protected $hidden = [ 'openid', 'language', 'province', 'city', 'country', 'privilege', 'unionid', ];
    protected $visible = ['id', 'openid', 'nickname', 'headimgurl', 'sex', 'rank', 'score_catcall','score_call','score_total','top_work','have_work'];
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
        return Work::where('wechat_user_id',$this->id)->count() > 0 ;
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
       $topWork = RedisController::readUserTopWorkId($this->id);
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
        $rank = RedisController::readUserRankOrder($this->id);

        // 如果redis里面没排名，从数据库里面取数更新
        if(!$rank){
            // $works = Game::where('wechat_user_id', $this->wechat_user_id)->get();
            // foreach ($works as $key => $work) {
                // RedisController::saveUserWorkScore($this->wechat_user_id, $work->id, $work->score);
            // }
            // $rank = RedisController::readUserRankOrder($this->id);
        }
        return $rank;
    }
    /**
     * 微信头像处理
     */
    public function getHeadimgurlAttribute($val)
    {
        $tmp = explode('/mmopen/', $val);
        if(isset($tmp[1])){
            return [
                'local'  => env('APP_URL').'/mmopen/'.$tmp[1],
                'origin' => $val
            ];
        }else{
            return [
                'local'  => null,
                'origin' => $val
            ];
        }
    }
    /**
     * 可以认证字段
     *
     */
    public function findForPassport($username)
    {
        return $this->orWhere('openid', $username)->orWhere('nickname', $username)->first();
    }

}
