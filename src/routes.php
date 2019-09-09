<?php
/**
 * routes.
 *
 * @author mibao <hhhxm@tom.com>
 */
namespace Mibao\LaravelFramework;

use Barryvdh\Cors\HandleCors;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;
use Mibao\LaravelFramework\Tests\Middleware\WechatDev;
use Overtrue\LaravelWeChat\Middleware\OAuthAuthenticate as WechatOAuthAuthenticate;
use SMartins\PassportMultiauth\Http\Middleware\AddCustomProvider;
use SMartins\PassportMultiauth\Http\Middleware\ConfigAccessTokenCustomProvider;

// 路由的命令究竟
$appNamespace = 'Mibao\LaravelFramework\Controllers';

// 添加多用户访问中间件
$this->app->router->pushMiddlewareToGroup('custom-provider', AddCustomProvider::class);
$this->app->router->pushMiddlewareToGroup('custom-provider', ConfigAccessTokenCustomProvider::class);
$this->app->router->pushMiddlewareToGroup('api', 'custom-provider');
// 添加api访问跨域访问中间件
$this->app->router->pushMiddlewareToGroup('api', HandleCors::class);
// 添加微信谁中间件
$this->app->router->aliasMiddleware('wechat.oauth', WechatOAuthAuthenticate::class);

// 获取微信参数
$accout = config('mibao-framework.wechatAccout');
$scope = config('mibao-framework.wechatScope');
// 本地测试时，通过中间件注入微信用户模拟数据，避免跳转到微信服务器认证
if(env('APP_ENV') === 'local'){
    $wechatMiddlewareBase = ["web", WechatDev::class, "wechat.oauth:$accout"];
    $wechatMiddlewareUserINfo = ["web", WechatDev::class, "wechat.oauth:$accout,$scope"];
}else{
    $wechatMiddlewareBase = ["web", "wechat.oauth:$accout"];
    $wechatMiddlewareUserINfo = ["web", "wechat.oauth:$accout,$scope"];
}

// 修改系统本来的web路由，添加前缀，默认为"do"
Route::middleware('web')
        ->prefix(getRoutePrefix())
        ->namespace('App\Http\Controllers')
        ->group(base_path('routes/web.php'));

        //  修改系统本来的api路由，添加前缀
Route::middleware('api')
        ->prefix(getRoutePrefix('api'))
        ->namespace('App\Http\Controllers')
        ->group(base_path('routes/api.php'));

// Passport身份认证的路由
Route::middleware([AddCustomProvider::class])
        ->prefix(getRoutePrefix())
        ->group(function() {
        Passport::routes(function ($router) {
            return $router->forAccessTokens();
        });
    });

// 微信登录的路由
// 给微信登录用的，其它登录方式使用会出错，因为没有guard
Route::post(getRoutePrefix('wechat/token'))
        ->uses('\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken')
    //  ->middleware(['oauth.providers','throttle:999999999,1']) // api访问限制
        ->middleware(['oauth.providers'])
        ->name('wechat.passport.token')
    ;

// 只获取微信用户OPENID
Route::prefix(getRoutePrefix())
        ->middleware($wechatMiddlewareBase)
        ->namespace($appNamespace)
        ->group(function() {
        Route::get('wechat/oauth', 'WeChatController@oauth')->name('wechat.oauth');
    });
// 获取微信用户信息
Route::prefix(getRoutePrefix())
        ->middleware($wechatMiddlewareUserINfo)
        ->namespace($appNamespace)
        ->group(function() {
        Route::get('wechat/oauth/userinfo', 'WeChatController@oauth')->name('wechat.oauth.snsapi_userinfo');
    });

/**
 * 指定路由前缀
 * @param  string $path 路径
 * @return string       最终路径
 */
function getRoutePrefix($path=null)
{
    return config('mibao-framework.routePrefix') . $path;
}
