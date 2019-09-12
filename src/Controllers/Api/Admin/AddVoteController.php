<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Api\RedisController;
use App\Models\Admin\AddVote;
use App\Models\Admin\WechatUser;
use App\Models\Admin\Work;
use App\Models\Admin\Vote;
use Auth;
use Log;
use DB;

class AddVoteController extends ApiController
{
    /**
     * 定时加票
     */
    public static function cron()
    {
    //    Log::info('add vote');
    //    Log::info(now());
       static::cronHandle();
    }
    public static function cronHandle()
    {
       $addVotes = AddVote::with(['wechat_user'])->get();
       foreach ($addVotes as $key => $addVote) {
            // 0代表不加票
            if($addVote->daily_add === 0){
                continue;
            }
            // 当天已加票数
            $dailyFinishCount = static::addVoteDailyFinishCount($addVote->work_id);
            // 加够了就不要再加
            if($dailyFinishCount>$addVote->daily_add){
                continue;
            }
            static::randomVote($addVote, $dailyFinishCount);
       }
    }
    public static function randomVote($addVote, $dailyFinishCount){
        $endOfDay      = now()->EndOfday();
        $diffInMinutes = now()->diffInMinutes($endOfDay);
        // 还要加多少票
        $restVoteCount = $addVote->daily_add - $dailyFinishCount;
        if($restVoteCount <= 0){
            // 没票可以加了
            return;
        }
        // 加票的概率，按现在到今天结束有多少分数来计算
        // 越接近结束时间，越大概率加票
        $ratio =  $restVoteCount / $diffInMinutes;
        $change = $ratio < 1 ? mt_rand(1, 1 / $ratio) : 1;
        // print_r($diffInMinutes."\r\n");
        // print_r($restVoteCount."\r\n");
        // print_r($ratio."\r\n");
        // print_r($change."\r\n");
        if($ratio>=1 || $change===1){
            $addTotal = ceil($ratio) * ($ratio>1 ? 2 : 1);
            // print_r($addTotal."---\r\n");
            static::addManyVotes($addVote, mt_rand($addTotal/2, $addTotal));
        }
    }
    public static function addManyVotes($addVote, $addTotal){
        Log::info($addVote->work_id.':'.$addTotal);
        for ($i=0; $i < $addTotal ; $i++) { 
            static::addVoteHandle($addVote,mt_rand(1, 2));
        }
    }
    public static function addVoteDailyFinishCount($work_id){
        return Vote::where('work_id', $work_id)
                ->where('wechat_user_id',0)
                ->where('created_at', ">=", now()->StartOfday())
                ->where('created_at', "<=", now()->EndOfday())
                ->count();
    }
    public static function addVoteHandle($addVote, $type)
    {
        DB::beginTransaction(); 
        try {
            $vote = Vote::create([
                'wechat_user_id' => 0,
                'work_id'        => $addVote->work_id,
                'type'           => $type,
                ]);
                if($type===1){
                $typeName='score_catcall';
            }else if($type===2){
                $typeName='score_call';
            }
            
            $addVote->increment('total');

            $work = Work::find($addVote->work_id);
            $work->increment($typeName);
            $work->increment('score_total');
            $workUser = WechatUser::find($work->wechat_user_id);
            $workUser->increment($typeName);
            $workUser->increment('score_total');

            RedisController::addUserVote($workUser->id, $addVote->work_id, $type);
            RedisController::saveUserWorkScore($workUser->id, $addVote->work_id, $work->score_total);

            $workUser->rank = RedisController::readUserRankOrder($workUser->id);
            $workUser->save();
            DB::commit(); 
        } catch (Exception $e) { 
            DB::rollBack(); 
        }
    }

    public function adminAddVote(Request $request)
    {
        $valid = $this->validatorParams($request, [
            'id'   => 'required|Integer',
            'vote' => 'required|Integer',
        ]);
        if($valid!==true){
            return $this->failed(1003, $valid);
        }

        $addVote = AddVote::with(['wechat_user'])->where('id',$request->id)->first();
        static::addManyVotes($addVote, $request->vote);
        return $this->success();

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $db = AddVote::with(['wechat_user'])->with(['work']);
        if($request->keyword){
            $db->whereHas('wechat_user' , function ($query) use ($request){
                $query->where('nickname','LIKE','%'.$request->keyword.'%');
                $query->orWhere('openid',$request->keyword);
            });
        }
        $res = $this->paginateApiDate($request, $db);
        return $this->success($res);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function voteIndex(Request $request)
    {
        $db = Vote::with(['work','wechat_user'])->where('wechat_user_id',0);
        if($request->keyword){
            $db->whereHas('wechat_user' , function ($query) use ($request){
                $query->where('nickname','LIKE','%'.$request->keyword.'%');
                $query->orWhere('openid',$request->keyword);
            });
        }
        $res = $this->paginateApiDate($request, $db);
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
            'work_id'   => 'required|Integer',
            'daily_add' => 'required|Integer',
        ]);
        if($valid!==true){
            return $this->failed(1003, $valid);
        }
        if(!WechatUser::where('openid',$request->openid)->first()){
            return $this->failed(1003, '找不到这个用户');
        }
        if(!Work::where('id',$request->work_id)->first()){
            return $this->failed(1003, '找不到这个作品');
        }
        if(AddVote::where('work_id',$request->work_id)->first()){
            return $this->failed(1003, '这个作品已经在加票');
        }
        $user = Auth::user();
        $addVote=AddVote::withTrashed()->where('work_id',$request->work_id)->first();
        if($addVote){
            $addVote->restore();
            $addVote->daily_add = $request->daily_add;
            $addVote->admin_id  = $user->id;
            $addVote->save();
        }else{
            AddVote::create(
                [
                    'openid'    => $request->openid,
                    'work_id'   => $request->work_id,
                    'daily_add' => $request->daily_add,
                    'work_id'   => $request->work_id,
                    'admin_id'  => $user->id,
                ]
            );
        }
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
        $valid = $this->validatorParams($request, [
            'daily_add' => 'required|Integer',
        ]);
        if($valid!==true){
            return $this->failed(1003, $valid);
        }
        $addVote = AddVote::find($id);
        if(!$addVote){
            return $this->failed(1003, '找不到这个用户');
        }
        $addVote->daily_add = $request->daily_add;
        $addVote->save();
        return $this->success();
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
        AddVote::destroy($request->ids);
        return $this->success();

    }
}
