<?php
/**
 * routes.
 *
 * @author mibao <hhhxm@tom.com>
 */
namespace Mibao\LaravelFramework;

use Barryvdh\Cors\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Contracts\Routing\Registrar as Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Laravel\Passport\Passport;
use Mibao\LaravelFramework\Tests\Middleware\WechatDev;
use Overtrue\LaravelWeChat\Middleware\OAuthAuthenticate as WechatOAuthAuthenticate;
use SMartins\PassportMultiauth\Http\Middleware\AddCustomProvider;
use SMartins\PassportMultiauth\Http\Middleware\ConfigAccessTokenCustomProvider;
use SMartins\PassportMultiauth\Http\Middleware\MultiAuthenticate;
use Socialite;

class RouteRegistrar
{
    /**
     * The router implementation.
     *
     * @var \Illuminate\Contracts\Routing\Registrar
     */
    protected $router;

    // 路由的命名空间
    protected $namespace = 'Mibao\LaravelFramework\Controllers';

    /**
     * Create a new route registrar instance.
     *
     * @param  \Illuminate\Contracts\Routing\Registrar  $router
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }
    /**
     * 指定路由前缀
     * @param  string $path 路径
     * @return string       最终路径
     */
    public static function getRoutePrefix($path=null)
    {
        return config('mibao-framework.routePrefix') . $path;
    }
    /**
     * Register routes for transient tokens, clients, and personal access tokens.
     *
     * @return void
     */
    public function all()
    {
        $this->modifyMiddleware();
        $this->forPublic();
        $this->forWechat();
        $this->forPassPort();
        // $this->forTest();
    }
    /**
     * 中间件修改、注册.
     *
     * @return void
     */
    protected function modifyMiddleware()
    {
        // 微信认证中间件别名
        $this->router->aliasMiddleware('wechat.oauth', WechatOAuthAuthenticate::class);
        $this->router->aliasMiddleware('wechat.dev', WechatDev::class);

        // 多用户认证中间件别名
        $this->router->aliasMiddleware('multiauth', MultiAuthenticate::class);
        $this->router->aliasMiddleware('oauth.providers', AddCustomProvider::class);

        // 多用户访问中间件
        $this->router->pushMiddlewareToGroup('custom-provider', 'oauth.providers');
        $this->router->pushMiddlewareToGroup('custom-provider', ConfigAccessTokenCustomProvider::class);
        // $this->router->pushMiddlewareToGroup('api', 'custom-provider');

        // 添加api访问跨域访问中间件
        $this->router->pushMiddlewareToGroup('api', HandleCors::class);

    }
     /**
     * 公开路由
     *
     * @return void
     */
    public function forPublic()
    {
        // 自定义路由路径
        $webApiPath = base_path('routes/mibao-web.php');
        $apiApiPath = base_path('routes/mibao-api.php');

        if(File::exists($webApiPath)){
            Route::middleware('web')
                ->prefix(static::getRoutePrefix())
                ->namespace($this->namespace)
                // 使用自定义的路由文件
                ->group($webApiPath);
        }
        
        if(File::exists($apiApiPath)){
            Route::middleware('api')
                ->prefix(static::getRoutePrefix('api'))
                ->namespace($this->namespace)
                // 使用自定义的路由文件
                ->group($apiApiPath);
        }
    }
     /**
     * 微信相关路由
     *
     * @return void
     */
    public function forPassPort()
    {
        // Passport身份认证的路由
        Passport::routes();
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Route::middleware(['oauth.providers'])
            ->prefix(static::getRoutePrefix())
            ->group(function() {
            Passport::routes(function ($router) {
                return $router->forAccessTokens();
            });
        });

        // 给微信登录用的，其它登录方式使用会出错，因为没有guard
        Route::post(static::getRoutePrefix('oauth/token/unlimit'))
            ->uses('\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken')
            ->middleware(['oauth.providers'])
            ->name('passport.token.unlimit')
        ;
    }
   /**
     * 微信相关路由
     *
     * @return void
     */
    protected function forWechat()
    {
        // 获取微信参数
        $accout = config('mibao-framework.wechatAccout');
        $scope = config('mibao-framework.wechatScope');

        // 本地测试时，通过中间件注入微信用户模拟数据，避免跳转到微信服务器认证
        if(env('APP_ENV') === 'local'){
            $wechatMiddlewareBase = ["web", 'wechat.dev', "wechat.oauth:$accout"];
            $wechatMiddlewareUserInfo = ["web", 'wechat.dev', "wechat.oauth:$accout,$scope"];
        }else{
            $wechatMiddlewareBase = ["web", "wechat.oauth:$accout"];
            $wechatMiddlewareUserInfo = ["web", "wechat.oauth:$accout,$scope"];
        }
        // 只获取微信用户OPENID
        Route::prefix(static::getRoutePrefix())
            ->middleware($wechatMiddlewareBase)
            ->namespace($this->namespace)
            ->group(function() {
                Route::get('wechat/oauth', 'Auth\WeChatController@oauth')->name('wechat.oauth');
                Route::get('wechat/remote/oauth', 'Auth\WeChatController@oauth')->name('wechat.remote.oauth');
            });
        // 获取微信用户信息
        Route::prefix(static::getRoutePrefix())
            ->middleware($wechatMiddlewareUserInfo)
            ->namespace($this->namespace)
            ->group(function() {
                Route::get('wechat/oauth/userinfo', 'Auth\WeChatController@oauth')->name('wechat.oauth.userinfo');
                Route::get('wechat/remote/oauth/userinfo', 'Auth\WeChatController@oauth')->name('wechat.remote.oauth.userinfo');
            });
    }
   /**
     * 测试相关路由
     *
     * @return void
     */
    protected function forTest()
    {
        Route::get('login/github', function(){
            // 将用户重定向到Github认证页面
            return Socialite::driver('github')->redirect();
        })->middleware(['web']);
        
        Route::get('login/github/callback', function(){
            // 从Github获取用户信息
            $user = Socialite::driver('github')->user();
            dd($user);
        })->middleware(['web']);
        
        Route::get('/passport', function () {
            return view('passport');
        });
        
        Route::get('/redirect', function (){
            $query = http_build_query([
                'client_id' => '3',
                'redirect_uri' => 'https://test.mibao.ltd/auth/callback',
                'response_type' => 'code',
                'scope' => '',
            ]);
        
            return redirect('https://test.mibao.ltd/oauth/authorize?' . $query);
        });
        
        Route::get('/auth/callback2', function (Request $request){
            if ($request->get('code')) {
                return 'Login Success';
            } else {
                return 'Access Denied';
            }
        });   
        
        Route::get('/auth/callback', function (Request $request) {
            $http = new \GuzzleHttp\Client;
            $response = $http->post('https://test.mibao.ltd/oauth/token?provider=users', [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'client_id' => '3',  // your client id
                    'client_secret' => 'JwKX50fcPbXy6yrKDrL4YO026Z08UrSvIRzwykAd',   // your client secret
                    'redirect_uri' => 'https://test.mibao.ltd/auth/callback',
                    'code' => $request->code,
                ],
            ]);
            return json_decode((string) $response->getBody(), true);
        });
    }
}

