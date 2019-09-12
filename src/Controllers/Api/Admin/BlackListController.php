<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Api\RedisController;
use App\Models\Admin\WechatUser;
use Log;
use DB;

class BlackListController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $ids = RedisController::getBlackList();
        $model = WechatUser::whereIn("id", $ids);
        $res = $this->paginateApiDate($request, $model, true);
        return $this->success($res);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $valid = $this->validatorParams($request, [
            'openid'    => 'required|String',
        ]);
        if($valid!==true){
            return $this->failed(1003, $valid);
        }
        $wechat_user = WechatUser::where('openid',$request->openid)->first();
        if(!$wechat_user){
            return $this->failed(1003, '找不到这个用户');
        }
        RedisController::setBlackList($wechat_user->id);
        RedisController::resetRankFromMysql();
        return $this->success();
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $valid = $this->validatorParams($request, [
            'ids'       => 'required|Array',
        ]);
        if($valid!==true){
            return $this->failed(1003, $valid);
        }
        RedisController::removeBlackList($request->ids);
        RedisController::resetRankFromMysql();
        return $this->success();

    }
}
