<?php

namespace Mibao\LaravelFramework\Tests;

use Barryvdh\Cors\HandleCors;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;
use Mibao\LaravelFramework\Tests\Middleware\WechatDev;
use Overtrue\LaravelWeChat\Middleware\OAuthAuthenticate as WechatOAuthAuthenticate;
use SMartins\PassportMultiauth\Http\Middleware\AddCustomProvider;
use SMartins\PassportMultiauth\Http\Middleware\ConfigAccessTokenCustomProvider;

/**
 * Class ServiceProvider.
 *
 * @author mibao <hhhxm@tom.com>
 */
class RouteService
{
    protected $appNamespace = 'Mibao\LaravelFramework\Controllers';
    /**
     * Boot the provider.
     */
    public function boot()
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
        $this->mapPassportRoutes();
        $this->mapWechatRoutes();
    }
    /**
     * Register the provider.
     */
    public function register($app)
    {
        // 添加多用户访问中间件
        $app->router->pushMiddlewareToGroup('custom-provider', AddCustomProvider::class);
        $app->router->pushMiddlewareToGroup('custom-provider', ConfigAccessTokenCustomProvider::class);
        $app->router->pushMiddlewareToGroup('api', 'custom-provider');
        // 添加api访问跨域访问中间件
        $app->router->pushMiddlewareToGroup('api', HandleCors::class);
        // 添加微信谁中间件
        $app->router->aliasMiddleware('wechat.oauth', WechatOAuthAuthenticate::class);
        $this->boot();

    }
    /**
     * 修改系统本来的web路由，添加前缀，默认为"do"
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->prefix($this->getRoutePrefix())
             ->namespace('App\Http\Controllers')
             ->group(base_path('routes/web.php'));
    }
    /**
     * 修改系统本来的api路由，添加前缀
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::middleware('api')
             ->prefix($this->getRoutePrefix('api'))
             ->namespace('App\Http\Controllers')
             ->group(base_path('routes/api.php'));
    }
    /**
     * 增加Passport身份认证的路由
     *
     * @return void
     */
    protected function mapPassportRoutes()
    {
        Route::middleware([AddCustomProvider::class])
             ->prefix($this->getRoutePrefix())
             ->group(function() {
                Passport::routes(function ($router) {
                    return $router->forAccessTokens();
                });
            });
    }
    /**
     * 增加微信登录的路由
     *
     * @return void
     */
    protected function mapWechatRoutes()
    {
        // 给微信登录用的，其它登录方式使用会出错，因为没有guard
        Route::post($this->getRoutePrefix('wechat/token'))
             ->uses('\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken')
            //  ->middleware(['oauth.providers','throttle:999999999,1']) // api访问限制
             ->middleware(['oauth.providers'])
             ->name('wechat.passport.token')
            ;
        // 获取微信谁参数
        $accout = config('mibao-framework.wechatAccout');
        $scope = config('mibao-framework.wechatScope');
        // 本地测试时，通过中间件注放模拟数据，避免跳转到微信服务器认证
        if(env('APP_ENV') === 'local'){
            $middlewareBase = ["web", WechatDev::class, "wechat.oauth:$accout"];
            $middlewareUserINfo = ["web", WechatDev::class, "wechat.oauth:$accout,$scope"];
        }else{
            $middlewareBase = ["web", "wechat.oauth:$accout"];
            $middlewareUserINfo = ["web", "wechat.oauth:$accout,$scope"];
        }
        // // 只获取微信用户OPENID
        // Route::prefix($this->getRoutePrefix())
        //      ->middleware($middlewareBase)
        //      ->namespace($this->appNamespace)
        //      ->group(function() {
        //         Route::get('wechat/oauth', 'WeChatController@oauth')->name('wechat.oauth');
        //     });
        // // 获取微信用户信息
        // Route::prefix($this->getRoutePrefix())
        //      ->middleware($middlewareUserINfo)
        //      ->namespace($this->appNamespace)
        //      ->group(function() {
        //         Route::get('wechat/oauth/userinfo', 'WeChatController@oauth')->name('wechat.oauth.userinfo');
        //     });
    }
    /**
     * 判断环境，再给出相应路径
     * @param  string $path 路径
     * @return string       最终路径
     */
    protected function getRoutePrefix($path=null)
    {
        return  config('mibao-framework.routePrefix') . $path;
    }
}
