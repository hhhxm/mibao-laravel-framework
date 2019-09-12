<?php
namespace Mibao\LaravelFramework\Controllers\Auth;

use Mibao\LaravelFramework\Models\WechatUser;
use Auth;
use Carbon\Carbon;
use GuzzleHttp\Client as Http;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Laravel\Passport\Client;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\ResourceServer;
// use League\OAuth2\Server\Exception\OAuthServerException;
use Mibao\LaravelFramework\Controllers\Helper\RedisController;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Validator;

class AuthenticateController
{
    use AuthenticatesUsers;
    protected $username;
    protected $password;
    protected $guardName = 'api';
    protected $guardProvider = 'users';

    public function __construct()
    {
        // $this->middleware('auth:api,admin')->only([
        //     'logout'
        // ]);
    }
    /**
     * 用户密码登录
     * @param  Request $request
     * @return Response
     */
    public function login(Request $request)
    {
        $this->guardName = $request->type ? $request->type :'api';
        $this->guardProvider = config('auth.guards.'.$this->guardName.'.provider');
        $validator = Validator::make($request->all(), [
            'name'    => 'required|exists:'.$this->guardProvider,
            'password' => 'required|between:6,64',
        ]);
        if ($validator->fails()) {
            return responder()->error('param_error')->data($validator->errors()->all());
        }

        $this->username=$request->name;
        $this->password=$request->password;

        $res = $this->authenticateClient();
        return $res ? 
                responder()->success(['accessToken' => $res]) : 
                responder()->error('认证出错');
    }
    /**
     * 使用ticket登录
     * @param  Request $request
     * @return Response
     */
    public function loginByTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket' => 'required|string',
        ]);
        if ($validator->fails()) {
            return responder()->error('param_error')->data($validator->errors()->all());
        }
        $res = RedisController::get_wechat_login_ticket($request->ticket);
        return responder()->success(['token' => $res]);
    }
    /**
     * 登出
     * @param  Request $request
     * @return Response
     */
    public function logout(Request $request)
    {
        if (Auth::guard($this->guardName)->check()){

            Auth::guard($this->guardName)->user()->token()->revoke();
        }
        return $this->message('注销成功');
    }
    /**
     * 微信登录
     * @param  String  $openid 用户的微信openid
     * @return String          token
     */
    public function wechatLogin($openid)
    {
        $this->username=$openid;
        $this->password=$openid;
        $this->guardProvider = config('auth.guards.wechat.provider');
        // $res = $this->authenticateClient();
        $wechatUser = WechatUser::where('openid',$openid)->first();
        $res = $this->authenticateClientPersonal($wechatUser, "wechatUser:$openid");
        return $res ? $res : '认证出错';
    }
    //调用认证接口获取授权token
    protected function authenticateClient()
    {
        // 个人感觉通过.env配置太复杂，直接从数据库查更方便
        $password_client = Client::query()->where('password_client',1)->latest()->first();
        $client = new Http();
        $url = env('APP_URL') . '/do/wechat/token';
        $data = [
            'form_params' => [
                'grant_type' => 'password',
                'client_id' => $password_client->id,
                'client_secret' => $password_client->secret,
                'username' => $this->username,
                'password' => $this->password,
                'scope' => '',
                'provider' => $this->guardProvider,
            ],
        ];
        try {
            $res = $client->request('POST', $url, $data);
        } catch (\Exception $e) {
            throw  new UnauthorizedHttpException('', '账号验证失败1');
            return false;
        }
        if (!isset($res) || $res->getStatusCode() == 401) {
            throw  new UnauthorizedHttpException('', '账号验证失败2');
            return false;
        }else{
            $resJson = json_decode((string)$res->getBody(), true);
            // dd($data, $resJson);
            return $resJson['access_token'];
        }
    }

    // 私人访问令牌，不会超时，一直有效
    protected function authenticateClientPersonal($user, $tokenName, $scope=[])
    {
        // 把api默认的提供者users改为wechat_users
        Config::set('auth.guards.api.provider', 'wechat_users');
        return $user->createToken($tokenName, $scope)->accessToken;
    }
    protected function authenticated(Request $request)
    {
        // 密码授权令牌
        return $this->authenticateClient($request);
    }

    protected function sendLoginResponse(Request $request)
    {
        $this->clearLoginAttempts($request);
        return $this->authenticated($request);
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        $msg = $request['errors'];
        $code = $request['code'];
        return $this->failed($code, $msg);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function validate_token(Request $request, ResourceServer $server, TokenRepository $tokens)
    {
        $psr = (new DiactorosFactory)->createRequest($request);

        try {
            $psr = $server->validateAuthenticatedRequest($psr);
        } catch (OAuthServerException $e) {
            throw new AuthenticationException;
        }

        // $this->validateScopes($psr, $scopes);

        // return $next($request);

        $psr = (new DiactorosFactory)->createRequest($request);
        $psr = $server->validateAuthenticatedRequest($psr);
        $token = $tokens->find($psr->getAttribute('oauth_access_token_id'));
        // $user = Auth::user();
        // $date_now=Carbon::now();
        // $tokendate=$token->expires_at;
        // print_r($psr);
        print_r($psr);

/*
        if ($tokendate->lt($date_now)) {
            return $next($request);
        } else {
            return response()->json(401);
        }
*/
    }

}
