<?php

namespace Mibao\LaravelFramework\Middleware;

use Closure;
use Illuminate\Support\Facades\Request;

class WechatRemotePremission
{
    /**
     *
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $ips = explode(',',env('WECHAT_OFFICIAL_IP_WHITE_LIST'));
        Request::setTrustedProxies(['192.168.1.4'], -1);
        if(in_array($request->getClientIp(), $ips)){
            return $next($request);
        }else{
            return responder()->error('no_in_white_list')->respond(401);
        }
    }
}
