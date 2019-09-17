<?php

use Illuminate\Http\Request;
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
Route::group(['middleware' => ['throttle:10,1']], function () {
  // 帐号密码登录
  Route::post('auth/login', 'Auth\AuthenticateController@login')->name('api.login');
  // 第三方谁跳转后，使用一次性ticket登录
  Route::get('auth/ticket', 'Auth\AuthenticateController@getTokenByTicket')->name('api.login.ticket');
  // 注册
  // Route::post('register', 'Admin\RegisterController@register')')->name('api.register');
});

/*
  微信用户API
*/// Route::group(['middleware' => ['auth:wechat']], function () {
Route::group(['middleware' => ['multiauth:wechat']], function () {
  // 用户个人信息
  Route::post('wechat/local/upload_avatar', 'Api\Wechat\UserController@uploadAvatar');
  Route::apiResource('wechat/local/user', 'Api\Wechat\UserController', ['only' => ['index','show']]);
  // 记录分享
  Route::post('wechat/local/share', 'Api\Wechat\ShareController@store')->name('api.wechat.share');
  // 微信JSSDK
  Route::post('wechat/local/jssdk', 'Api\Wechat\BaseController@getJssdk')->name('api.wechat.jssdk');
  // 错误记录接口
  Route::apiResource('logging', 'LoggingController', ['only' => ['store']]);
  // 登出
  Route::get('auth/logout', 'Auth\AuthenticateController@logout')->name('api.logout');
});

Route::group(['middleware' => ['multiauth:api']], function () {
  // 用户个人信息
//   Route::apiResource('wechat/user', 'Api\Wechat\UserController', ['only' => ['index','show']]);
    Route::get('user/info', function(Request $request){
        // dd(Auth::user());
        return responder()->success();
    });

});

/*
  后台管理员API
*/
Route::group(['middleware' => ['multiauth:admin']], function () {
  // Route::get('admin/admin/info', 'Admin\AdminController@user_info');
  // Route::apiResource('admin/admin', 'Admin\AdminController');

  // Route::delete('admin/role', 'Admin\RoleController@destroy');
  // Route::apiResource('admin/role', 'Admin\RoleController');

  // Route::delete('admin/permission', 'Admin\PermissionController@destroy');
  // Route::apiResource('admin/permission', 'Admin\PermissionController');

  // Route::get('admin/count', 'Admin\WorkController@count');
  
});