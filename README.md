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

#### 相关配置文件

1. Mibao设置

```php
// 统一执行
php artisan vendor:publish --provider="Mibao\LaravelFramework\ServiceProvider"
```

```php
// 分类执行

// 设置文件
php artisan vendor:publish --provider="Mibao\LaravelFramework\ServiceProvider" --tag="config"
// Models文件
php artisan vendor:publish --provider="Mibao\LaravelFramework\ServiceProvider" --tag="models"
// 数据迁移
php artisan vendor:publish --provider="Mibao\LaravelFramework\ServiceProvider" --tag="migrations"
// 数据填充
php artisan vendor:publish --provider="Mibao\LaravelFramework\ServiceProvider" --tag="seeds"
```

2. 微信Easywechat

```php
php artisan vendor:publish --provider="Overtrue\LaravelWeChat\ServiceProvider"
```

3. 跨域Cros

```php
php artisan vendor:publish --provider="Barryvdh\Cors\ServiceProvider"
```

4. 权限管理

```php
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="migrations"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="config"
```

#### 数据迁移与填充

通过上面的命令，已经把相关文件copy到对应的目录

- models -> \app\Models
- migrations -> \data\migrations
- seeds -> \data\seeds

通过下面的命令把迁移数据，并填充数据

```php
// 只有migrate:refresh才能使用--seeder，来指定class
php artisan migrate:refresh --seeder=MibaoDatabaseSeeder
// 安装passport
php artisan passport:install
```

#### 排除微信相关的路由

在中间件 `App\Http\Middleware\VerifyCsrfToken` 排除微信相关的路由，如：

```php
protected $except = [
    // ...
    'wechat',
];
```

#### 路由前缀设置

1. 关闭默认路由，否则会出现双重路由，把/config/app.php里面的RouteServiceProvider注释掉

```php
        /*
         * Application Service Providers...
         */
        ...
        App\Providers\EventServiceProvider::class,
        // App\Providers\RouteServiceProvider::class,
```

2. 改用Mibao的路由设置，路径前缀都改为"do/"







## 第三方组件说明参考

[https://github.com/overtrue/laravel-wechat](https://github.com/overtrue/laravel-wechat)
[https://github.com/barryvdh/laravel-cors](https://github.com/barryvdh/laravel-cors)
[https://docs.spatie.be/laravel-permission/v3/installation-laravel/](https://docs.spatie.be/laravel-permission/v3/installation-laravel/)

## License

MIT