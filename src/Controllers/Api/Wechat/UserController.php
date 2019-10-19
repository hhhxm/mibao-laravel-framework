<?php

namespace Mibao\LaravelFramework\Controllers\Api\Wechat;

// use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mibao\LaravelFramework\Controllers\Controller;
use Mibao\LaravelFramework\Models\WechatUser;
use Mibao\LaravelFramework\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $model = WechatUser::orderby('created_at','DESC');
        return responder()->success($this->conditionsPaginate($model, $request));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = Auth::user();
        // dd($user->toArray());
        if($id == $user->id){
            $data = WechatUser::with('user')->find($id);
            return responder()->success($data);
        }else{
            return responder()->error()->respond(401);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function uploadAvatar(Request $request)
    {
        // dd($request);
        $user = Auth::user();
        $res = $user->addMedia($request->file('avatar'))->toMediaCollection('avatar');
        return responder()->success([$res]);
    }
    public function setPhoneNumber(Request $request)
    {
        Validator::make($request->all(), [
            'phone'          => 'required|mobile',
        ])->validate();
        $wechatUser = Auth::user();
        // 检查是否有该手机的用户
        $user = User::where('phone', $request->phone)->first();
        if(!$user){
            // 没有这个手机的用户，就创建一个
            $user = $wechatUser->user()->create([
                'name' => $request->phone,
                'password' => Hash::make(time()),
                'phone' => $request->phone,
            ]);
        }
        // 关联到微信帐号
        $wechatUser->user()->associate($user);
        $wechatUser->save();

        

        return responder()->success($wechatUser);

    }
}
