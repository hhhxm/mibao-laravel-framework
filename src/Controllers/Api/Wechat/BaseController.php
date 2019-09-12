<?php

namespace Mibao\LaravelFramework\Controllers\Api\Wechat;

use Mibao\LaravelFramework\Models\WechatUser;
use EasyWeChat;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Mibao\LaravelFramework\Controllers\Auth\AuthenticateController;
use Mibao\LaravelFramework\Controllers\Helper\RedisController;

class BaseController
{
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
