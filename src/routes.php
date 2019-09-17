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
use Illuminate\Support\Facades\File;
use Laravel\Passport\Passport;
use Mibao\LaravelFramework\Tests\Middleware\WechatDev;
use Mibao\LaravelFramework\Middleware\WechatRemotePremission;
use Overtrue\LaravelWeChat\Middleware\OAuthAuthenticate as WechatOAuthAuthenticate;
use SMartins\PassportMultiauth\Http\Middleware\AddCustomProvider;
use SMartins\PassportMultiauth\Http\Middleware\ConfigAccessTokenCustomProvider;
use SMartins\PassportMultiauth\Http\Middleware\MultiAuthenticate;
// 路由的命令究竟
$appNamespace = 'Mibao\LaravelFramework\Controllers';
// dump($this->app);
// 添加微信谁中间件别名
$this->app->router->aliasMiddleware('wechat.oauth', WechatOAuthAuthenticate::class);
$this->app->router->aliasMiddleware('wechat.dev', WechatDev::class);
// 添加多用户认证中间件别名
$this->app->router->aliasMiddleware('multiauth', MultiAuthenticate::class);
$this->app->router->aliasMiddleware('oauth.providers', AddCustomProvider::class);
// 添加多用户访问中间件
$this->app->router->pushMiddlewareToGroup('custom-provider', 'oauth.providers');
$this->app->router->pushMiddlewareToGroup('custom-provider', ConfigAccessTokenCustomProvider::class);
// $this->app->router->pushMiddlewareToGroup('api', 'custom-provider');
// 添加api访问跨域访问中间件
$this->app->router->pushMiddlewareToGroup('api', HandleCors::class);
// 微信公众号远程调用中间件
// $this->app->router->pushMiddlewareToGroup('wechat-remote', WechatRemotePremission::class);
    // $this->app->router->pushMiddlewareToGroup('wechat-remote', 'throttle:20,1');
// 获取微信参数
$accout = config('mibao-framework.wechatAccout');
$scope = config('mibao-framework.wechatScope');

// 自定义路由路径
$webApiPath = base_path('routes/mibao-web.php');
$apiApiPath = base_path('routes/mibao-api.php');

// 本地测试时，通过中间件注入微信用户模拟数据，避免跳转到微信服务器认证
if(env('APP_ENV') === 'local'){
    $wechatMiddlewareBase = ["web", 'wechat.dev', "wechat.oauth:$accout"];
    $wechatMiddlewareUserINfo = ["web", 'wechat.dev', "wechat.oauth:$accout,$scope"];
}else{
    $wechatMiddlewareBase = ["web", "wechat.oauth:$accout"];
    $wechatMiddlewareUserINfo = ["web", "wechat.oauth:$accout,$scope"];
}

/**
 * 指定路由前缀
 * @param  string $path 路径
 * @return string       最终路径
 */
// function getRoutePrefix($path=null)
// {
//     return config('mibao-framework.routePrefix') . $path;
// }

if(File::exists($webApiPath)){
    // 修改系统本来的web路由，添加前缀，默认为"do"
    Route::middleware('web')
            ->prefix(config('mibao-framework.routePrefix'))
            ->namespace($appNamespace)
            // 使用自定义的路由文件
            ->group($webApiPath);
}

if(File::exists($apiApiPath)){
    //  修改系统本来的api路由，添加前缀
    Route::middleware('api')
        ->prefix(config('mibao-framework.routePrefix').'api')
        ->namespace($appNamespace)
        // 使用自定义的路由文件
        ->group($apiApiPath);
}

// Passport身份认证的路由
// Passport::routes(null, ['prefix' => getRoutePrefix()]);
Passport::tokensExpireIn(now()->addDays(15));
Passport::refreshTokensExpireIn(now()->addDays(30));
Route::middleware(['oauth.providers'])
        ->prefix(config('mibao-framework.routePrefix'))
        ->group(function() {
        Passport::routes(function ($router) {
            return $router->forAccessTokens();
        });
    });


// 微信登录的路由
// 给微信登录用的，其它登录方式使用会出错，因为没有guard
Route::post(config('mibao-framework.routePrefix').'oauth/token/unlimit')
        ->uses('\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken')
        // ->middleware(['oauth.providers','throttle:999999999,1']) // api访问限制
        ->middleware(['oauth.providers'])
        ->name('passport.token.unlimit')
    ;

// 只获取微信用户OPENID
Route::prefix(config('mibao-framework.routePrefix'))
        ->middleware($wechatMiddlewareBase)
        ->namespace($appNamespace)
        ->group(function() {
            Route::get('wechat/oauth', 'Auth\WeChatController@oauth')->name('wechat.oauth');
        });

// 获取微信用户信息
Route::prefix(config('mibao-framework.routePrefix'))
        ->middleware($wechatMiddlewareUserINfo)
        ->namespace($appNamespace)
        ->group(function() {
            Route::get('wechat/oauth/userinfo', 'Auth\WeChatController@oauth')->name('wechat.oauth.userinfo');
        });


/*
  微信公众号远程调用
*/
Route::prefix(config('mibao-framework.routePrefix').'api')
        ->middleware([WechatRemotePremission::class])
        ->namespace($appNamespace)
        ->group(function() {
            Route::get('wechat/remote/official/access_token', 'Auth\WeChatController@getOffciaAccoutAccessToken')->name('api.wechat.access_token');
            Route::post('wechat/remote/official/jssdk', 'Api\Wechat\BaseController@getJssdk')->name('api.wechat.jssdk');
        });

