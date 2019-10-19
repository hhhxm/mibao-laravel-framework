<?php

namespace Mibao\LaravelFramework\Controllers\Auth;

use EasyWeChat;
use EasyWeChat\Factory;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Mibao\LaravelFramework\Controllers\Auth\WeChatController;

class MiniProgramController
{
    public function login(Request $request)
    {
        Validator::make($request->all(), [
            'code'          => 'required|string',
            'iv'            => 'required|string',
            'encryptedData' => 'required|string',
        ])->validate();

        $miniProgram = EasyWeChat::miniProgram();
        // 获取用户session_key
        $session_key = $miniProgram->auth->session($request->code)['session_key'];
        // 解码加密的用户信息
        $userData = $miniProgram->encryptor->decryptData($session_key, $request->iv, $request->encryptedData);
        $userData['nickname'] = $userData['nickName'];
        $userData['openid'] = $userData['openId'];
        $userData['sex'] = $userData['gender'];
        $userData['headimgurl'] = $userData['avatarUrl'];
        unset($userData['watermark']);

        // 检查用户是否注册，未注册的用户会自动新建，并把登录ticket传入session，
        $res = (new WeChatController)->register($userData, 'default', $apptype='mini_program');
        return responder()->success([ 
            'token' => $res->token,
            'userId'  => $res->user->id,
        ]);
    }
}
