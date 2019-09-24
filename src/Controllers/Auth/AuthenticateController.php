<?php
namespace Mibao\LaravelFramework\Controllers\Auth;

use Auth;
use GuzzleHttp\Client as Http;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\Client;
use Mibao\LaravelFramework\Controllers\Controller;
use Mibao\LaravelFramework\Models\WechatUser;

use function GuzzleHttp\json_decode;

class AuthenticateController extends Controller
{
    public function __construct()
    {
    }
    /**
     * 用户密码登录
     * @param  Request $request
     * @return Response
     */
    public function login(Request $request)
    {
        $guardName = $request->type ? $request->type :'api';
        $guardProvider = config('auth.guards.'.$guardName.'.provider');
        Validator::make($request->all(), [
            'name'    => 'required|exists:'.$guardProvider,
            'password' => 'required|between:6,64',
        ])->validate();
        $res = $this->authenticateByPassowrd($request->name, $request->password, $guardProvider);
        return $res ? 
                responder()->success(['accessToken' => $res]) : 
                responder()->error('auth_fail');
    }
    /**
     * 微信登录
     * @param  String  $openid 用户的微信openid
     * @return String          token
     */
    public function loginByWechatOpenid($openid)
    {
        $wechatUser = WechatUser::where('openid',$openid)->first();
        $token = $this->authenticateClientPersonal($wechatUser, 'wechat_users', "wechatOpenid:$openid");
        $ticket = $this->setLoginTicket($token,  $wechatUser->id);
        return (object) [
            'token'  => $token,
            'ticket' => $ticket,
        ];
    }
    /**
     * 使用ticket登录
     * @param  Request $request
     * @return Response
     */
    public function getTokenByTicket(Request $request)
    {
        Validator::make($request->all(), [
            'ticket' => 'required|string',
        ])->validate();
        $res = $this->getContentByTicket($request->ticket);
        return $res ? responder()->success(['token' => $res]) : responder()->error('ticket_is_timeout');
    }
    /**
     * 使用ticket获取用户信息
     * @param  Request $request
     * @return Response
     */
    public function getUserInfoByTicket(Request $request)
    {
        Validator::make($request->all(), [
            'ticket' => 'required|string',
        ])->validate();
        $res = $this->getContentByTicket($request->ticket);
        return $res ? responder()->success(['user' => json_decode($res)]) : responder()->error('ticket_is_timeout');
    }
    /**
     * 登出
     * @return Response
     */
    public function logout()
    {
        // if (Auth::check()){
            Auth::user()->token()->revoke();
        // }
        return responder()->success();
    }

    /**
     * 调用密码认证接口获取用户token 
     * 
     */
    protected function authenticateByPassowrd($username, $password, $provider)
    {
        // 个人感觉通过.env配置太复杂，直接从数据库查更方便
        $client = Client::query()->where('password_client',1)->latest()->first();
        $url = env('APP_URL') . '/'.config('mibao-framework.routePrefix').'oauth/token/unlimit';
        $data = [
            'form_params' => [
                'grant_type' => 'password',
                'client_id' => $client->id,
                'client_secret' => $client->secret,
                'username' => $username,
                'password' => $password,
                'scope' => '',
                'provider' => $provider,
            ],
        ];
        try {
            $http = new Http();
            $res = $http->request('POST', $url, $data);
            return $res->getStatusCode() === 401 ? false : json_decode((string)$res->getBody(), true)['access_token'];
        } catch (\Exception $e) {
            Log::info('登录出错,请看日志');
            return false;
        }
    }

    /**
     * 私人访问令牌，过期设置在ServiceProvider.php里面
     */
    protected function authenticateClientPersonal($user, $provider, $tokenName, $scope=[])
    {
        // 把api默认的提供者users改为wechat_users
        // Config::set('auth.guards.api.provider', $provider);
        return $user->createToken($tokenName, $scope)->accessToken;
    }
    /**
     * 生成登录电子票
     * @param $token     string 模型类型
     * @param $modelId   string 模型id
     */
    public function setLoginTicket($token, $modelId)
    {
        $ticket = md5($modelId . time());
        $key = env('REDIS_KEY','mibao').":loginTicket:".$ticket;
        Redis::setex($key, 60, $token);
        return $ticket;
    }
    /**
     * 用电子票获取内容
     * @param $ticket     string 电子票
     */
    protected function getContentByTicket($ticket)
    {
        $key = env('REDIS_KEY','mibao').":loginTicket:".$ticket;
        $res = Redis::get($key);
        Redis::del($key);
        return $res;
    }
}
