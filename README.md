# mibao-laravel-framework

快速开发H5项目框架

## 框架要求

Laravel >= 5.8

## 安装

```shell
composer require "mibao/laravel-framework"
```

## 配置

### Laravel 应用

<!-- 1. 在 `config/app.php` 注册 ServiceProvider 和 Facade (Laravel 5.5 + 无需手动注册)

```php
'providers' => [
    // ...
    Overtrue\LaravelWeChat\ServiceProvider::class,
],
'aliases' => [
    // ...
    'EasyWeChat' => Overtrue\LaravelWeChat\Facade::class,
],
``` -->

#### 微信配置

```shell
php artisan vendor:publish --provider="Overtrue\LaravelWeChat\ServiceProvider"
```

#### 排除微信相关的路由

:rotating_light: 在中间件 `App\Http\Middleware\VerifyCsrfToken` 排除微信相关的路由，如：

```php
protected $except = [
    // ...
    'wechat',
];
```

#### OAuth 中间件

1. 在 `app/Http/Kernel.php` 中添加路由中间件：

```php
protected $routeMiddleware = [
    // easywechat的中间件
    'wechat.oauth' => \Overtrue\LaravelWeChat\Middleware\OAuthAuthenticate::class,
    // 本地测试用，带微信用户
    'wechat.test' => \Overtrue\LaravelWeChat\Middleware\WeChatTest::class,
];
```

2. 在路由中添加中间件：

```php
//...
Route::group(['middleware' => ['web', 'wechat.oauth']], function () {
    Route::get('/user', function () {
        $user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        dd($user);
    });
});
```


## License

MIT