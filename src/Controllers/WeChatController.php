<?php

namespace Mibao\LaravelFramework\Controllers;

use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;

use Carbon\Carbon;
// use App\Http\Controllers\ApiController;
use Mibao\LaravelFramework\Controllers\AuthenticateController;
use App\Http\Controllers\Api\RedisController;
use App\Models\WechatUser;
use EasyWeChat;
use Illuminate\Support\Facades\Hash;

// // class WeChatController extends ApiController
class WeChatController
{
    use RegistersUsers;

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
        $redirect_url  = $request->redirect_url ? : '/';
        $redirect_url .= strpos($request->redirect_url, '#/?') ? '&' : '?';
        $redirect_url .= 'ticket='.$ticket;
        // return redirect()->intended($redirect_url);
        // 把ticket放到客户端cookie，给api访问使用
        $cookie = cookie('ticket', $ticket, 60*24*30);
        return redirect()->intended($redirect_url)->cookie($cookie);
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
    /**
     * 引用微信用户注册的注册类
     * 重写注册流程.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    // public static function register(SocialiteUser $socialteuse)
    public static function register($socialteuse)
    {
        // 微信用户数据
        $wechatUserData = $socialteuse->getOriginal();

        if($wechatUserData && isset($wechatUserData['openid'])){
            // 判断openid是否存在用户存，有就更新，没就新建
            $wechatUser = WechatUser::firstOrNew([
                'openid' => $wechatUserData['openid'],
            ]);
            $wechatUser->fill($wechatUserData);
            $wechatUser->password = Hash::make($wechatUserData['openid']);
            $wechatUser->save();
                        
            if($wechatUser->wasRecentlyCreated){
                event(new Registered($wechatUser));
            }
            $authenticate = new AuthenticateController();
            $token = $authenticate->wechatLogin($wechatUserData['openid']);

            // 记录token到redis，用ticket取用
            $res = RedisController::set_wechat_login_ticket($token, $wechatUserData);

            session(['wechat.oauth_user.api_ticket' => $res->ticket]);
        }
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
    public static function headimgurl_handle($headimgurl)
    {
        $url_132 = preg_replace("/\/0$/","/132",$headimgurl);
        $local_132 = preg_replace("/^http\:\/\/thirdwx\.qlogo.cn\/mmopen/", "/mmopen",$url_132);
        return array(
            'url_132'=>$url_132,
            'local_132'=>$local_132,
        );
    }
}
