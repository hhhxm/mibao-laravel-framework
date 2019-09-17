<?php

namespace Mibao\LaravelFramework\Controllers\Api\Wechat;

use EasyWeChat;
use Illuminate\Http\Request;

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

        return responder()->success($res);
    }
}
