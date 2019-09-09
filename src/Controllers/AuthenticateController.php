<?php
namespace Mibao\LaravelFramework\Controllers;

use Validator;
use Auth;
use Log;
use DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Laravel\Passport\Client;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\ResourceServer;
// use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use GuzzleHttp\Client as Http;
// use App\Http\Controllers\ApiController;
// use App\Models\WechatUser;

use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

// class AuthenticateController extends ApiController
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
    // 登录
    public function login(Request $request)
    {
        $this->guardName = $request->type ? $request->type :'api';
        $this->guardProvider = config('auth.guards.'.$this->guardName.'.provider');
        $validator = Validator::make($request->all(), [
            'name'    => 'required|exists:'.$this->guardProvider,
            'password' => 'required|between:5,32',
        ]);
        if ($validator->fails()) {
            $request->request->add([
                'errors' => $validator->errors(),
                'code' => 401,
            ]);
            return $this->sendFailedLoginResponse($request);
        }
        $this->username=$request->name;
        $this->password=$request->password;

        $res = $this->authenticateClient();
        return $res ? $this->success($res) : $this->failed(401, '认证出错');
    }

    // 退出登录
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
        $res = $this->authenticateClient();
        return $res ? $res : '认证出错';
    }
    //调用认证接口获取授权token
    protected function authenticateClient()
    {
        // 个人感觉通过.env配置太复杂，直接从数据库查更方便
        $password_client = Client::query()->where('password_client',1)->latest()->first();
        $client = new Http();
        if($this->guardProvider=='wechat_users'){
            $url = env('APP_URL') . '/do/oauth/token/wechat';
        }else{
        }
        // $url = env('APP_URL') . '/do/wechat/token';
        $url = 'https://www.163.com/';
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
        // dd($data);
        try {
            $res = $client->request('POST', $url, $data);
        } catch (\Exception $e) {
            throw  new UnauthorizedHttpException('', '账号验证失败');
            return false;
        }
        if (!isset($res) || $res->getStatusCode() == 401) {
            throw  new UnauthorizedHttpException('', '账号验证失败');
            return false;
        }else{
            $resJson = json_decode((string)$res->getBody(), true);
            return $resJson['access_token'];
        }
    }

    // 私人访问令牌，不会超时，一直有效
    protected function authenticateClientPersonal($user, $token_name='wechat_user', $scope=array('wechat'))
    {
        // 把api默认的提供者users改为wechat_users
        Config::set('auth.guards.api.provider', 'wechat_users');
        return $user->createToken($token_name, $scope)->accessToken;
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
