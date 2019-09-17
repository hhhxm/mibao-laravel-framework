<?php
namespace Mibao\LaravelFramework\Tests;

use GuzzleHttp\Client as Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Mibao\LaravelFramework\Controllers\Controller;

class TestApi extends Controller
{
    public function __construct()
    {
    }
    /**
     * 调用密码认证接口获取用户token 
     * 
     */
    public static function test()
    {
        $n=0;
        for ($i=0; $i < 1000 ; $i++) { 
            static::getApi();
            $n++;
            print_r("\r\n$n\r\n");
        }

    }
    public static function getApi()
    {
        // 个人感觉通过.env配置太复杂，直接从数据库查更方便
        // $client = Client::query()->where('password_client',1)->latest()->first();
        $url = env('APP_URL') . '/'.config('mibao-framework.routePrefix').'oauth/token/unlimit';
        $url = 'https://test.mibao.ltd/do/api/wechat/remote/official/jssdk';
        $data = [
            'form_params' => [
                'grant_type' => 'password',
                // 'client_id' => $client->id,
                // 'client_secret' => $client->secret,
                // 'username' => $username,
                // 'password' => $password,
                'scope' => '',
                // 'provider' => $provider,
            ],
        ];
        $http = new Http();
        $res = $http->request('POST', $url, $data);
        print_r($res->getStatusCode());
        // return $res->getStatusCode() === 401 ? false : json_decode((string)$res->getBody(), true)['access_token'];

    }
}
