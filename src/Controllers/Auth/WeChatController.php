<?php

namespace Mibao\LaravelFramework\Controllers\Auth;

use Mibao\LaravelFramework\Models\WechatUser;
use EasyWeChat;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Mibao\LaravelFramework\Controllers\Auth\AuthenticateController;
use Mibao\LaravelFramework\Controllers\Helper\RedisController;

class WeChatController
{
    use RegistersUsers;

    /**
     * 引用微信用户注册的注册类
     * 重写注册流程.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public static function checkUser($socialteuse)
    {
        // 微信用户数据
        $data = $socialteuse->getOriginal();
        if(isset($data['openid'])){
            // 判断openid是否存在用户存，有就更新，没就新建
            $wechatUser = WechatUser::firstOrNew([
                'openid' => $data['openid'],
            ]);
            $wechatUser->fill($data);
            $wechatUser->password = Hash::make($data['openid']);
            $wechatUser->save();
            
            // 广播添加新用户
            !$wechatUser->wasRecentlyCreated ? : event(new Registered($wechatUser));

            // 获取用户token
            $authenticate = new AuthenticateController();
            $token = $authenticate->wechatLogin($data['openid']);

            // 记录token到redis，用ticket取用
            $res = RedisController::set_wechat_login_ticket($token, $wechatUser);

            session(['wechat.oauth_user.api_ticket' => $res->ticket]);
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
        $ticket = session('wechat.oauth_user.api_ticket');
        // 回调地址加上ticket
        $redirectUrl  = $request->redirectUrl ? : env("APP_URL");
        $redirectUrl .= strpos($request->redirectUrl, '#/?') ? '&' : '?';
        $redirectUrl .= 'ticket='.$ticket;
        // 把ticket放到客户端cookie，给api访问使用
        $cookie = cookie('ticket', $ticket, 60*24*30);
        // return $this->success($redirectUrl);
        return responder()->success([$redirectUrl]);
        // return redirect()->intended($redirectUrl)->cookie($cookie);
    }
    /**
     * 使用ticket登录
     * @param  Request $request
     * @return json
     */
    public function login_by_ticket(Request $request)
    {
        $valid = $this->validatorParams($request, [
            'ticket' => 'required|string',
        ]);
        if($valid!==true){
            return $this->failed(1003, $valid);
        }
        $res = RedisController::get_wechat_login_ticket($request->ticket);
        return $this->success($res);
    }
    /**
     * 获取微信JSSDK.
     *
     * @return \Illuminate\Http\Response
     */
    public function getJssdk(Request $request)
    {
        $officialAccount = EasyWeChat::officialAccount(); // 公众号
        $url = $request->url;
        $apis = $request->apis ? $request->apis : ['onMenuShareTimeline', 'onMenuShareAppMessage', 'onMenuShareQQ',];
        $debug = $request->debug ? true : false;

        if($url) $officialAccount->jssdk->setUrl($url);
        $jssdk = $officialAccount->jssdk->buildConfig($apis, $debug, false, false);

        $res = [
            'jssdk' => $jssdk,
        ];

        return $this->success($res);
    }
}
