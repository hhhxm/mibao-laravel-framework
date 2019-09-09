<?php

namespace Mibao\LaravelFramework;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mibao\LaravelFramework\Tests\RouteService;
use Mibao\LaravelFramework\Controllers\WeChatController;
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
        // dd($this->app);

        // 监听微信用户登录
        Event::listen(WeChatUserAuthorized::class, function ($event) {
            $user = $event->user;
            $isNew = $event->isNewSession;
            // $event->account;
            // dd($user);
            WeChatController::register($event->user);
        });


        // dd($this->app);
        // $route = new RouteService();
        // $route->register($this->app);

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
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $configPath = realpath(__DIR__.'/config.php');
        
        if ($this->app->runningInConsole()) {
            // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
            $this->publishes([ $configPath => config_path('mibao-framework.php') ], 'config');
            $this->publishes([ __DIR__.'/../database/migrations' => $this->app->databasePath()."/migrations" ], 'migrations');
            $this->publishes([ __DIR__.'/../database/seeds' => $this->app->databasePath()."/seeds" ], 'seeds');
            $this->publishes([ __DIR__.'/Models' => $this->app->path()."/Models" ], 'models');
        }
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
        // 增加管理员的提供者
        Config::set('auth.providers.admins', [
            'driver' => 'eloquent',
            'model' => \App\Models\Admin::class,
        ]);
        // 增加微信用户的提供者
        Config::set('auth.providers.wechat_users', [
            'driver' => 'eloquent',
            'model' => \App\Models\WechatUser::class,
        ]);

    }
}
