<?php

namespace Mibao\LaravelFramework\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Mibao\LaravelFramework\Models\Logs as LogsModel;


trait Utils
{
    /**
     * 验证提交参数
     *
     * @return 成功与否
     */
    protected function validatorParams($request, $roles)
    {
        Validator::extend('mobile', function($attribute, $value, $parameters) {
            return preg_match('/^((13[0-9])|(14[5,7,9])|(15[^4])|(18[0-9])|(17[0,1,3,5,6,7,8]))\d{8}$/', $value);
        });
        $validator = Validator::make($request->all(), $roles);
        return $validator;
    }
    /**
     * 数据分页统一封装
     *
     * @return \Illuminate\Http\Response
     */
    public function conditionsPaginate($model, $request)
    {
        $paging    = $request->paging == 'false' ? false: true;
        $limit     = $request->limit ? $request->limit : 30;
        $orderName = $request->orderName;
        $order     = $request->order ? : 'DESC';
        $startDate = $request->startDate;
        $endDate   = $request->endDate;

        if($startDate){
            $model->where('created_at', '>=',now()->parse($startDate)->startOfDay());
        }
        if($endDate){
            $model->where('created_at', '<=',now()->parse($endDate)->endOfDay());
        }
        if($orderName){
            $model->orderBy($orderName, $order);
        }
        // dd($paging);
        // return [$paging,$limit];
        if($paging){
            ini_set('memory_limit', '512M');
            return $model->paginate($limit);
        }else{
            return $this->noPaginate($model);
            // return $model->get();
        }
    }
    /**
     * 分组导出所有数据
     *
     * @return Array
     */
    public function noPaginate($model)
    {
        ini_set('memory_limit', '-1');
        // ini_set ('memory_limit', '2G');
        return $model->get();

        // 分组处理太慢了
        // $datas=[];
        // $model->chunk(50000, function($items) use(&$datas) {
            // $datas =  array_merge($items->all(), $datas);
        // });
        // return $datas;
    }
}
