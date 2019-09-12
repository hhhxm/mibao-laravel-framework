<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Api\RedisController;
use App\Models\Admin\Vote;
use Auth;
use DB;

class VoteController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        set_time_limit(-1);
        ini_set("memory_limit","516M");
        $db = Vote::select()->with(['work','wechat_user'])->where('wechat_user_id','!=',0);

        if($request->union==='true'){
            $db->addSelect(DB::raw('count(*) as vote_count'))
               ->groupBy('work_id')
               ->groupBy('wechat_user_id')
               ;
        }
        if($request->worker){
            $db->whereHas('work' , function ($query) use ($request){
                $query->whereHas('wechat_user' , function ($query2) use ($request){
                    $query2->where('nickname','LIKE','%'.$request->worker.'%');
                    $query2->orWhere('openid',$request->worker);
                });
            });
        }
        if($request->voter){
            $db->WhereHas('wechat_user' , function ($query) use ($request){
                $query->where('nickname','LIKE','%'.$request->voter.'%');
                $query->orWhere('openid',$request->voter);
            });
        }

        $res = $this->paginateApiDate($request, $db);
        return $this->success($res);
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
        //
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
