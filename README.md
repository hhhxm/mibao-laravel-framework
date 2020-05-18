<!--
 * @Description: In User Settings Edit
 * @Author: your name
 * @Date: 2019-09-05 22:11:39
 * @LastEditTime: 2019-09-05 22:11:39
 * @LastEditors: your name
 -->
# mibao-laravel-framework

快速开发H5项目框架

1. 多用户认证
2. api响应支持
3. 微信easywechat框架
4. 分组权限
5. api跨域访问
6. 用户id使用uuid
7. passport的client_id使用uuid
8. 第三方服务器远程调用微信公众号功能（需要在微信后台添加授权域名）

## 框架要求

Laravel >= 5.8

## 安装

### 安装laravel

```shell
composer create-project --prefer-dist laravel/laravel api "5.8.*"
```

### 安装米宝插件

```shell
composer require "mibao/laravel-framework"
```

### 如出现文件不存在，用touch新建后，再执行后面的流程

## 配置

### 相关配置文件

* Mibao设置

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
// 自定义路由
php artisan vendor:publish --provider="Mibao\LaravelFramework\ServiceProvider" --tag="routes"
```

* 微信Easywechat

```php
php artisan vendor:publish --provider="Overtrue\LaravelWeChat\ServiceProvider"
```

* 跨域Cros

```php
php artisan vendor:publish --provider="Barryvdh\Cors\ServiceProvider"
```

* 权限管理

```php
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="migrations"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="config"
```

* api响应管理

```php
php artisan vendor:publish --provider="Flugg\Responder\ResponderServiceProvider"
```

* 媒体管理

```php
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="migrations"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="config"
```

* laravel 通知数据库

```php
php artisan notifications:table
```

### 数据迁移与填充

通过上面的命令，已经把相关文件copy到对应的目录

* database\\* -> \database
* resources\\* -> \resources
* routes\\* -> \routes

通过下面的命令把迁移数据，并填充数据

```php
// 只有migrate:refresh才能使用--seeder，来指定class
php artisan migrate:refresh --seeder=MibaoDatabaseSeeder
// 安装passport
php artisan passport:install
```

### 测试数据生成

在php artisan tinx里面，可以直接生成

```php
factory(Mibao\LaravelFramework\Models\WechatUser::class,50)->create()
```

### 排除微信相关的路由

在中间件 `App\Http\Middleware\VerifyCsrfToken` 排除微信相关的路由，如：

```php
protected $except = [
    // ...
    'wechat',
];
```

### 修改原laravel框架的配置

* 关闭默认路由，否则会出现双重路由，把/config/app.php里面的RouteServiceProvider注释掉

```php
        /*
         * Application Service Providers...
         */
        ...
        App\Providers\EventServiceProvider::class,
        // App\Providers\RouteServiceProvider::class,
```

* 修改异常输出，替换/app/Exceptions/Handler.php里的异常类

```php
// 注释原框架的异常处理类
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
// 替换成Responder的异常处理类
use Flugg\Responder\Exceptions\Handler as ExceptionHandler;
```

* 修改/app/Exceptions/Handler.php，过滤掉oauth2认证失败后的日志报错，否则用户登录时输入错密码，日志会产生大量出错记录。

```php
    public function report(Exception $exception)
    {
        if ($exception instanceof \League\OAuth2\Server\Exception\OAuthServerException && $exception->getCode() == 6) {
            return;
        }
        parent::report($exception);
    }
```

* 修改/config/app.php，让faker支持中文。

```php
    'faker_locale' => 'zh_CN',
```

## 第三方组件说明参考

<https://github.com/overtrue/laravel-wechat>
<https://github.com/barryvdh/laravel-cors>
<https://docs.spatie.be/laravel-permission/v3/installation-laravel/>
<https://github.com/sfelix-martins/passport-multiauth>
<https://github.com/flugger/laravel-responder>
<https://docs.spatie.be/laravel-medialibrary/v7/>

## 相关参考

[Laravel 使用 UUID 作为用户表主键并使用自定义用户表字段](https://nova.moe/laravel-use-uuid-as-primary-key-with-custom-authentication-fields/)
[Implement UUID on Authentication Built-in Laravel 5.7](https://medium.com/@didin.ahmadi/implement-uuid-on-authentication-built-in-laravel-5-7-e289e6a5a9a5)
[Error Log Problems When Using Laravel Passport for User Login Authentication](https://laracasts.com/discuss/channels/laravel/error-log-problems-when-using-laravel-passport-for-user-login-authentication?page=1)
[Using UUID Client IDs in Laravel Passport](https://mlo.io/blog/2018/08/17/laravel-passport-uuid/)

## License

MIT
