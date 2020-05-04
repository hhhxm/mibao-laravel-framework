<?php

use Illuminate\Http\Request;
use Mibao\LaravelFramework\RouteRegistrar;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*
  认证管理
*/
Route::group(['middleware' => ['api','throttle:100,1']], function () {
  // 帐号密码登录
  Route::post('auth/login', 'Auth\AuthenticateController@login')->name('api.login');
  // 第三方谁跳转后，使用一次性ticket获取token
  Route::get('auth/ticket', 'Auth\AuthenticateController@getTokenByTicket')->name('api.login.ticket');
  // 注册
  Route::post('auth/register', 'Auth\RegisterController@register')->name('api.register');
  // 微信小程序登录
  Route::post('auth/miniProgram/login', 'Auth\MiniProgramController@login')->name('api.miniProgram.login');
  // 检查验证码
  Route::post('sms/code/check', 'Auth\AuthenticateController@checkSmsVerificationCodeByApi')->name('api.register.smsCode');
});
Route::group(['middleware' => ['api','throttle:100,1']], function () {
  // 注册手机验证码
  Route::post('sms/code/register', 'Auth\AuthenticateController@smsVerificationCodeByNoModel')->name('api.register.smsCode');
});

/*
  微信用户API
*/
Route::group(['middleware' => ['multiauth:wechat']], function () {
  // 用户个人信息
  Route::post('wechat/setPhoneNumber', 'Api\Wechat\UserController@setPhoneNumber');
  Route::post('wechat/upload_avatar', 'Api\Wechat\UserController@uploadAvatar');
  Route::apiResource('wechat/user', 'Api\Wechat\UserController', ['only' => ['show']]);
  // 微信JSSDK
  Route::post('wechat/jssdk', 'Api\Wechat\BaseController@getJssdk')->name('api.wechat.jssdk');
  // 错误记录接口
  Route::apiResource('logging', 'LoggingController', ['only' => ['store']]);
  // 登出，必须登录后才能调用，否则出错。
  Route::get('auth/logout', 'Auth\AuthenticateController@logout')->name('api.logout');
});

Route::group(['middleware' => ['multiauth:api']], function () {
  // 用户个人信息
  // Route::apiResource('wechat/user', 'Api\Wechat\UserController', ['only' => ['index','show']]);
    Route::get('user/info', function(Request $request){
        // dd(Auth::user());
        return responder()->success();
    });

});
/*
微信公众号远程调用
*/
Route::group(['middleware' => [Mibao\LaravelFramework\Middleware\WechatRemotePremission::class]], function () {
      // 获取access_token，不安全，最好不要远程调用
      // Route::get('wechat/remote/official/access_token', 'Api\Wechat\BaseController@getOffciaAccoutAccessToken')->name('api.wechat.remote.access_token');
      // 使用一次性电子票获取用户信息，不需要api token
      Route::get('wechat/remote/official/userinfo', 'Auth\AuthenticateController@getUserInfoByTicket')->name('api.wechat.remote.userinfo');
      Route::post('wechat/remote/official/jssdk', 'Api\Wechat\BaseController@getJssdk')->name('api.wechat.remote.jssdk');
    });
/*
  后台管理员API
*/
Route::group(['middleware' => ['multiauth:admin']], function () {
    // Route::get('admin/user/info', 'Admin\UserController@user_info');
    Route::apiResource('admin/user', 'Admin\UserController');
    Route::apiResource('wechat/user', 'Api\Wechat\UserController', ['only' => ['index']]);

  // Route::delete('admin/role', 'Admin\RoleController@destroy');
  // Route::apiResource('admin/role', 'Admin\RoleController');

  // Route::delete('admin/permission', 'Admin\PermissionController@destroy');
  // Route::apiResource('admin/permission', 'Admin\PermissionController');

  // Route::get('admin/count', 'Admin\WorkController@count');

});

Route::post('admin/model/summary', 'Admin\SummaryController@modelSummaryByTimeSize');
