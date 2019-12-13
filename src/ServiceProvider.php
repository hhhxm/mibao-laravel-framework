<?php
/**
 * TODO:
 * FIXME:
 */
namespace Mibao\LaravelFramework;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\Client;

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
        // 设置参数
        $this->setupConfig();

        // 设置路由
        (new RouteRegistrar($this->app->router))->all();

        // 设置认证
        $this->modifyAuthConfig();

        // dd($this->app);
        // 因为passport client采用了uuid，所以需要取消自增长id，改为实时生成uuid
        /* Client::creating(function (Client $client) {
            $client->incrementing = false;
            $client->id = \Ramsey\Uuid\Uuid::uuid4()->toString();
        }); */

        // 增加手机检验规则
        Validator::extend('mobile', function($attribute, $value, $parameters) {
            return preg_match('/^((13[0-9])|(14[5,7,9])|(15[^4])|(18[0-9])|(17[0,1,3,5,6,7,8]))\d{8}$/', $value);
        });

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
