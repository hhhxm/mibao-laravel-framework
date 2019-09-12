<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Api\RedisController;
use App\Models\Admin\WechatUser;
use Log;
use Auth;

class WechatUserController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $wechat_user = WechatUser::select();

        if($request->keyword){
            $wechat_user->where('nickname','LIKE','%'.$request->keyword.'%');
            $wechat_user->orWhere('openid',$request->keyword);
        }
        $data = $this->paginateApiDate($request, $wechat_user);
        // $data->makeVisible(['created_at','openid']);
        return $this->success($data);
    }
    /**
     * 中奖用户
     * @param  Request $request
     * @return json
     */
    public function goal_user(Request $request)
    {
        $black_list = array(1832, 1369, 30, 43, 25, 880, 1206, 464,
                            72, 70, 20, 497, 4, 258, 2086, 10);
        static::update_user_rank();
        $wechat_user = WechatUser::select();
        $wechat_user
            ->addSelect('created_at')
            ->where('rank','>',0)
            ->where('rank','<=',100)
            ->whereNotIn('id', $black_list)
            ;

        if($request->keyword){
            $wechat_user->where('nickname','LIKE','%'.$request->keyword.'%');
            $wechat_user->orWhere('openid',$request->keyword);
        }
        $data = $this->paginateApiDate($request, $wechat_user);
        $data->makeVisible(['is_prizedsipatch','has_prize_user']);
        return $this->success($data);
    }
    /**
     * 更新数据库里的排名数据
     */
    public function update_user_rank()
    {
        $ids = RedisController::readWorkRankIds();
        foreach ($ids as $user_id => $work_id) {
            $user = WechatUser::find($user_id);
            $user->rank = RedisController::readUserRankOrder($user_id);
            $user->save();
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
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
}
