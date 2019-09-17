<?php

namespace Mibao\LaravelFramework\Tests\Middleware;

use Closure;
use Overtrue\Socialite\User as SocialiteUser;
class WechatDev
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
        $wechatDevUser = config('mibao-framework.wechatDevUser');
        $socialiteUser = new SocialiteUser([
                        'id' => $wechatDevUser['openid'],
                        'name' => $wechatDevUser['nickname'],
                        'nickname' => $wechatDevUser['nickname'],
                        'avatar' => $wechatDevUser['headimgurl'],
                        'email' => null,
                        'original' => $wechatDevUser,
                        'token' => null,
                        'provider' => 'WeChat',
                        ]);
        session()->flash('wechat.oauth_user.default', $socialiteUser);
        return $next($request);
    }
}
