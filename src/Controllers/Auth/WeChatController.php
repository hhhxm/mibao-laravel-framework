<?php

namespace Mibao\LaravelFramework\Controllers\Auth;

use Mibao\LaravelFramework\Models\WechatUser;
use EasyWeChat;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
use Mibao\LaravelFramework\Controllers\Auth\AuthenticateController;

class WeChatController
{
    use RegistersUsers;

    public function WeChatUserAuthorizedListener($event)
    {
        $user = $event->user;
        $isNew = $event->isNewSession;
        // $event->account;
        // static::checkUser($event->user);
    }
    /**
     * 检查微信用户是否已经注册
     * 未注册：生成新用户
     * 已注册：更新数据
     * 以openid为用户凭证，发放登录电子票，让客户端认证登录
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public static function checkUser($socialteuse)
    {
        // 微信用户数据
        $data = $socialteuse->getOriginal();
        if(isset($data['openid'])){
            DB::beginTransaction();

            // 判断openid是否存在用户存，有就更新，没就新建
            $wechatUser = WechatUser::lockForUpdate()->firstOrNew([
                'openid' => $data['openid'],
            ]);
            $wechatUser->fill($data);
            $wechatUser->exists ?: $wechatUser->password = Hash::make(strrev($data['openid']));
            $wechatUser->save();
            // 广播添加新用户
            !$wechatUser->wasRecentlyCreated ? : event(new Registered($wechatUser));
            // 获取用户token
            $res = (new AuthenticateController)->loginByWechatOpenid($data['openid']);
            // 缓存一次用户的apiTicket
            session()->flash('apiTicket', $res->ticket);

            DB::commit();
    }
    }
    /**
     * 微信认证
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function oauth(Request $request)
    {
        $ticket = session('apiTicket');
        // 回调地址加上ticket
        $redirectUrl  = $request->redirectUrl ? : env("APP_URL");
        $redirectUrl .= strpos($request->redirectUrl, '#/?') ? '&' : '?';
        $redirectUrl .= 'ticket='.$ticket;
        return responder()->success([$redirectUrl]);
    }
    /**
     * 获取微信access_token
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getOffciaAccoutAccessToken(Request $request)
    {
        return responder()->success(EasyWeChat::officialAccount()->access_token->getToken());
    }
    /**
     * 获取微信JSSDK.
     *
     * @return \Illuminate\Http\Response
     */
    public function getJssdk(Request $request)
    {
        $officialAccount = EasyWeChat::officialAccount(); // 公众号
        $apis = $request->apis ? $request->apis : ['onMenuShareTimeline', 'onMenuShareAppMessage', 'onMenuShareQQ',];
        $debug = $request->debug ? : false;

        !$request->url ? : $officialAccount->jssdk->setUrl($request->url);
        $jssdk = $officialAccount->jssdk->buildConfig($apis, $debug, false, false);

        return $this->success([ 'jssdk' => $jssdk ]);
    }
}
