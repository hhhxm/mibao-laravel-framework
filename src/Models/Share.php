<?php

namespace Mibao\LaravelFramework\Models;

use Illuminate\Database\Eloquent\Model;

class Share extends Model
{
    protected $appends = ['nickname', 'openid'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'wechat_user_id', 'work_id', 'ip', 'link', 'created_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $visible = [ 'id', 'nickname', 'openid', 'ip', 'link', 'created_at' ];
    public function getNicknameAttribute()
    {
        return $this->wechat_user->nickname;
    }
    public function getOpenidAttribute()
    {
        return $this->wechat_user->openid;
    }
    /**
     * 获得分享的用户信息。
     */
    public function wechat_user()
    {
        return $this->belongsTo('Mibao\LaravelFramework\Models\WechatUser', 'wechat_user_id', 'id');
    }
}
