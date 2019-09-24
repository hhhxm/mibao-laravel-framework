<?php

namespace Mibao\LaravelFramework\Controllers\Auth;

use Mibao\LaravelFramework\Models\WechatUser;
use EasyWeChat;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
use Mibao\LaravelFramework\Controllers\Auth\AuthenticateController;

class UserController
{
    /**
     * api注册普通用户
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        event(new Registered($user = $this->create($request->all())));

        $this->guard()->login($user);

        return $this->registered($request, $user)
                        ?: redirect($this->redirectPath());
    }

    public static function checkUser($socialteuse)
    {
        // 微信用户数据
        $data = $socialteuse->getOriginal();
        if(isset($data['openid'])){
            DB::beginTransaction();

            // 判断openid是否存在用户存，有就更新，没就新建
            $wechatUser = WechatUser::lockForUpdate()->firstOrNew([
                'openid' => $data['openid'],
            ]);
            $wechatUser->fill($data);
            $wechatUser->exists ?: $wechatUser->password = Hash::make(strrev($data['openid']));
            $wechatUser->save();
            // 广播添加新用户
            !$wechatUser->wasRecentlyCreated ? : event(new Registered($wechatUser));
            // 获取用户token
            $res = (new AuthenticateController)->loginByWechatOpenid($data['openid']);
            // 缓存一次用户的apiTicket
            session()->flash('apiTicket', $res->ticket);

            DB::commit();
        }
    }

}
