<?php

namespace Mibao\LaravelFramework\Controllers\Admin;

use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Mibao\LaravelFramework\Controllers\Controller;
use Mibao\LaravelFramework\Models\Admin;

class SummaryController extends Controller
{
    /**
     * 模型按时间粒度统计数据
     *
     * @param Eloquent\Model $model
     * @param Request $request
     * @return void
     */
    public function modelSummaryByTimeSize(Request $request)
    {
        // 验证字段
        Validator::make($request->all(), [
            'table'     => 'required|string',
            'timeSize'  => 'required|string|in:year,month,day,hour,minute',
            'startDate' => 'required|date',
            'endDate'   => 'required|date',
        ])->validate();

        $model = DB::table($request->table);
        $timeSize = $request->timeSize;
        $startDate = $request->startDate;
        $endDate = $request->endDate;

        if($startDate){
            // 开始日期
            $model->where('created_at','>',Carbon::parse($startDate));
        }
        if($endDate){
            // 结束日期
            $model->where('created_at','<',Carbon::parse($endDate));
        }
        // 正序排序
        $model->orderBy('created_at','ASC');
        // 统计总数
        $total = $model->count();
        // 按时间粒度统计
        $timeString = $this->getTimeSizeString($timeSize);
        $model->addSelect(DB::raw('COUNT(id) as count'));
        $model->addSelect(DB::raw("DATE_FORMAT(created_at, '$timeString') as time"));
        $model->groupBy('time');
        // DB生成的数据中含有stdClass Object，success输出时需要Array，所以需要转换一下
        $data = json_decode(json_encode($model->get()), true);
        return responder()->success($data);
    }
    /**
     * 按照时间关键字，获取时间粒度字段
     *
     * @param [string] $type
     * @return void
     */
    public function getTimeSizeString($type)
    {
        switch ($type) {
            case 'year':
                $string = "%Y";
                break;
            case 'month':
                $string = "%Y-%m";
                break;
            case 'day':
                $string = "%Y-%m-%d";
                break;
            case 'hour':
                $string = "%Y-%m-%d %H:00";
                break;
            case 'minute':
                $string = "%Y-%m-%d %H:%i";
                break;
            default:
                $string = "%Y";
        }
        return $string;
    }
}
