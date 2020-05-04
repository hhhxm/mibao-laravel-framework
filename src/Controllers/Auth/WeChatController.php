<?php

namespace Mibao\LaravelFramework\Controllers\Auth;

use Mibao\LaravelFramework\Models\WechatUser;
use EasyWeChat;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
// use Illuminate\Support\Facades\Log;
use Mibao\LaravelFramework\Controllers\Auth\AuthenticateController;
use Overtrue\LaravelWeChat\Events\WeChatUserAuthorized;

class WeChatController
{
    use RegistersUsers;

    /**
     * 检查微信用户是否已经注册
     * 未注册：生成新用户
     * 已注册：更新数据
     * 以openid为用户凭证，发放登录电子票，让客户端认证登录
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkUser(WeChatUserAuthorized $event)
    {
        if($event->isNewSession || env('APP_DEBUG')){
            $this->register($event->user->getOriginal(), $event->account);
        };
    }
    public function register($userData, $account='default', $apptype='official_account')
    {
        if(isset($userData['openid'])){
            DB::beginTransaction();

            // 判断openid是否存在用户存，有就更新，没就新建
            $wechatUser = WechatUser::lockForUpdate()->firstOrCreate([
                'openid' => $userData['openid'],
                'appid'  => config("wechat.$apptype.$account.app_id"),
                'app_type'   => $apptype,
            ]);
            $wechatUser->fill($userData);
            $wechatUser->save();
            // 广播添加新用户
            !$wechatUser->wasRecentlyCreated ? : event(new Registered($wechatUser));
            if(strpos(Route::currentRouteName(), 'wechat.remote') === false){
                // 获取用户token
                $res = (new AuthenticateController)->loginByWechatOpenid($userData['openid']);
                $ticket = $res->ticket;
                $token = $res->token;
            }else{
                // 远程获取用户信息
                $wechatUser->makeVisible(["openid", "language", "province", "city", "country", "privilege"]);
                $ticket = (new AuthenticateController)->setUserTicket($wechatUser, $userData['openid']);
            }
            // 缓存一次用户的apiTicket
            session()->flash('apiTicket', $ticket);
            session()->flash('userId', $wechatUser->id);

            DB::commit();
            return (Object) [
                'user' => $wechatUser,
                'token' => $token,
                'ticket' => $ticket,
            ];
        }
    }
    /**
     * 微信公众号认证
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function oauth(Request $request)
    {
        $ticket = session('apiTicket');
        $userId = session('userId');
        // 回调地址加上ticket
        $redirectUrl  = $request->redirectUrl ? : env("APP_URL");
        $redirectUrl .= strpos($request->redirectUrl, '?') ? '&' : '?';
        $redirectUrl .= 'ticket='.$ticket;
        $redirectUrl .= '&userId='.$userId;
        return redirect($redirectUrl);
    }

}
