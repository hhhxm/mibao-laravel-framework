<?php

namespace Mibao\LaravelFramework\Controllers\Api\Wechat;

use EasyWeChat;
use Illuminate\Http\Request;

class BaseController
{
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
        $url   = $request->url;
        $apis  = $request->apis ? $request->apis : ['onMenuShareTimeline', 'onMenuShareAppMessage', 'onMenuShareQQ',];
        $debug = $request->debug ? true : false;

        if($url) $officialAccount->jssdk->setUrl($url);
        $jssdk = $officialAccount->jssdk->buildConfig($apis, $debug, false, false);

        // 加入code字段，兼容旧接口
        return responder()->success([ 'jssdk' => $jssdk ])->meta(['code' => 200]);
    }
}
