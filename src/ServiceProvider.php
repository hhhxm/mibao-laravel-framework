<?php

namespace Mibao\LaravelFramework;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Bridge\PersonalAccessGrant;
use League\OAuth2\Server\AuthorizationServer;
use Mibao\LaravelFramework\Listeners\WeChatUserAuthorizedListener;
use Overtrue\LaravelWeChat\Events\WeChatUserAuthorized;

/**
 * Class ServiceProvider.
 *
 * @author mibao <hhhxm@tom.com>
 */
class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Boot the provider.
     */
    public function boot()
    {
        $this->setupConfig();
        (new RouteRegistrar($this->app->router))->all();

        // 监听微信用户登录
        Event::listen(WeChatUserAuthorized::class, WeChatUserAuthorizedListener::class);

        // Passport Personal Access Token 过期时间设定为一周
        // 参考https://github.com/overtrue/blog/blob/master/_app/_posts/2018-11-01-set-expired-at-for-laravel-passport-personal-access-token.md
        $this->app->get(AuthorizationServer::class)->enableGrantType(new PersonalAccessGrant(), new \DateInterval('P3D'));

        // dd($this->app);

        $this->modifyAuthConfig();
    }
    
    /**
     * Register the provider.
     */
    public function register()
    {

    }
    /**
     * Setup the config.
     */
    protected function setupConfig()
    {
        $configPath = realpath(__DIR__.'/config.php');
        
        if ($this->app->runningInConsole()) {
            // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
            $this->publishes([ $configPath => config_path('mibao-framework.php') ], 'config');
            $this->publishes([ __DIR__.'/../database' => database_path("/") ], 'database');
            $this->publishes([ __DIR__.'/../routes' => base_path('routes') ], 'routes');
            $this->publishes([ __DIR__.'/../resources' => base_path('resources') ], 'resources');
        }
        // $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->mergeConfigFrom($configPath, 'mibao-framework');
    }
    protected function modifyAuthConfig()
    {
        // 把api换成passport认证
        Config::set('auth.guards.api', [
            'driver' => 'passport',
            'provider' => 'users',
            'hash' => false,
        ]);
        // 把增加admin守卫，并使用passport认证
        Config::set('auth.guards.admin', [
            'driver' => 'passport',
            'provider' => 'admins',
        ]);
        // 把增加wechat守卫，并使用passport认证
        Config::set('auth.guards.wechat', [
            'driver' => 'passport',
            'provider' => 'wechat_users',
        ]);
        // 修改普通用户的提供者
        Config::set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => \Mibao\LaravelFramework\Models\User::class,
        ]);
        // 增加管理员的提供者
        Config::set('auth.providers.admins', [
            'driver' => 'eloquent',
            'model' => \Mibao\LaravelFramework\Models\Admin::class,
        ]);
        // 增加微信用户的提供者
        Config::set('auth.providers.wechat_users', [
            'driver' => 'eloquent',
            'model' => \Mibao\LaravelFramework\Models\WechatUser::class,
        ]);

    }
}
