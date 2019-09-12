<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Api\RedisController;
use App\Models\Admin\Work;
use App\Models\Admin\WechatUser;
use App\Models\Admin\Share;
use App\Models\Admin\Vote;
use Log;
use Auth;
use DB;
use Carbon\Carbon;

class WorkController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $db = Work::with('wechat_user');
        if($request->keyword){
            $db->whereHas('wechat_user' , function ($query) use ($request){
                $query->where('nickname','LIKE','%'.$request->keyword.'%');
                $query->orWhere('openid',$request->keyword);
            });
        }
        $res = $this->paginateApiDate($request, $db);
        return $this->success($res);
    }

    public function voteCount(Request $request){
        // 验证
        $valid = $this->validatorParams($request, [
            'time_size'  => 'required|string|in:year,month,week,day,hour,minute,second',
            'start_date' => 'required|date',
            'end_date'   => 'required|date',
            'fields'   => 'required|array',
            'work_id'   => 'required|integer',
        ]);
        if($valid!==true){
            return $this->failed(1003, $valid);
        }
        // 处理mysql参数
        $time_size  = $request->time_size;
        $start_date = $request->start_date;
        $end_date   = $request->end_date;
        $types      = ['year','month','week','day','hour','minute','second'];
        $index      = array_search($time_size, $types);
        $datas = array();

        $db_arr = array(
            'vote' => ['table' => 'votes', 'model' => Vote::select(), 'whereRaw' => 'work_id = '.$request->work_id],
            // 'vote' => ['table' => 'votes', 'model' => Vote::select(), 'whereRaw' => null],
        );
        // 统计各项总数
        $res = $this->totalCount(array(), $db_arr, $request);
        // 统计各项分析数据
        $res = $this->groupCount($res, $db_arr, $request, $types, $index);
        $res['nickname'] = Work::with('wechat_user')->find($request->work_id)->wechat_user->nickname;
        return $this->success($res); 
   }
    public function count(Request $request)
    {
        // 验证
        $valid = $this->validatorParams($request, [
            'time_size'  => 'required|string|in:year,month,week,day,hour,minute,second',
            'start_date' => 'required|date',
            'end_date'   => 'required|date',
            'fields'   => 'required|array',
        ]);
        if($valid!==true){
            return $this->failed(1003, $valid);
        }
        // 处理mysql参数
        $time_size  = $request->time_size;
        $start_date = $request->start_date;
        $end_date   = $request->end_date;
        $types      = ['year','month','week','day','hour','minute','second'];
        $index      = array_search($time_size, $types);
        $datas = array();

        $db_arr = array(
            'wechat_user' => ['table' => 'wechat_users', 'model' => WechatUser::select(), 'whereRaw' => null ],
            'work'        => ['table' => 'works', 'model' => Work::select(), 'whereRaw' => null ],
            'work_user'   => ['table' => 'works', 'model' => Work::select(), 'whereRaw' => null, 'groupBy' => 'wechat_user_id' ],
            'vote'        => ['table' => 'votes', 'model' => Vote::select(), 'whereRaw' => null ],
            'share'       => ['table' => 'shares', 'model' => Share::select(), 'whereRaw' => null ],
        );
        // 统计各项总数
        $res = $this->totalCount(array(), $db_arr, $request);
        // 统计各项分析数据
        $res = $this->groupCount($res, $db_arr, $request, $types, $index);
        return $this->success($res);
    }
    /**
     * 统计各项总数
     * @param  array  $res     上一手处理的结果
     * @param  array  $arr     统计项数组
     * @param  object $request 请求数据
     * @return array
     */
    public function totalCount($res=array() ,$arr, $request){
        foreach ($arr as $key => $item) {
            if($request->start_date){
                // 开始日期
                $item['model']->where('created_at','>',Carbon::parse($request->start_date));
            }
            if($request->end_date){
                // 结束日期
                $item['model']->where('created_at','<',Carbon::parse($request->end_date));
            }
            if($item['whereRaw']){
                // 附加条件
                $item['model']->whereRaw($item['whereRaw']);
            }
            if(isset($item['groupBy']) && $item['groupBy']){
                // 有grounp by字段的统计
                $item['model']->groupBy($item['groupBy']);
                $res[$key] = count($item['model']->get());
            }else{
                $res[$key] = $item['model']->count();
            }
        }
        return $res;
    }
    /**
     * 统计各项分析数据
     * @param  array  $res     上一手处理的结果
     * @param  array  $arr     统计项数组
     * @param  object $request 请求数据
     * @param  string $types   时间粒度
     * @param  string $index   索引号，统计多少个时间单位
     * @return array
     */
    public function groupCount($res=array(), $arr, $request, $types, $index){
        $datas = array();
        foreach ($request->fields as $key => $table) {
            $setting = $arr[$table];
            if(isset($setting['groupBy']) && $setting['groupBy']){
                // 有grounp by字段的统计
                $subQuery =  DB::table($setting['table']);
                $subQuery->groupBy($setting['groupBy']);
                $db = DB::table(DB::raw("({$subQuery->toSql()}) as sub"));
            }else{
                $db = DB::table($setting['table']);
            }

            if($request->start_date){
                // 开始日期
                $db->where('created_at','>',Carbon::parse($request->start_date));
            }
            if($request->end_date){
                // 结束日期
                $db->where('created_at','<',Carbon::parse($request->end_date));
            }
            if($setting && $setting['whereRaw'] ){
                // 附加条件
                $db->whereRaw($setting['whereRaw']);
            }

            $db->orderBy('created_at','ASC');
            $datas[$table] = $this->groupByTime($db, $types, $index);
        }
        $res['data'] = $datas;
        return $res;
    }
    /**
     * 获取时间分组数据
     *
     * @return \Illuminate\Http\Response
     */
    public function groupByTime($db, $types, $index)
    {

        // 用户请求的要显示的字段，并计算平均值
        $raw=[];
        $raw[]=DB::raw('COUNT(id) as count');
        $db->addSelect($raw);

        // 按时间粒度分组
        for ($i=0; $i <= $index ; $i++) {
            $type = $types[$i];
            $db->groupBy($type);
            $db->addSelect(DB::raw(strtoupper($type).'(created_at) as '.$type));
        }
        // 加入时间字段
        $res = $db->get();

        // $data = $this->success($res);
        // $data->end_date = $request->end_date;

        return $res;
    }
    public function on_rank_count()
    {
        $scores = RedisController::readWorkRankScores();

        foreach ($scores as $user => $score) {
            if($score==0){
                unset($scores[$user]);
            }
        }
        return count($scores);
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
