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
        $paging   = $request->input('paging') ? : true;
        $limit    = $request->input('limit') ? : 30;
        $orderBy  = $request->input('orderBy') ? : null;
        $sort     = $request->input('sort') ? : 'DESC';
        $timeDiff = $request->input('timeDiff') ? : null;

        if(isset($timeDiff[0])){
            $model->where('created_at', '>=',now()->parse($timeDiff[0])->startOfDay());
        }
        if(isset($timeDiff[1])){
            $model->where('created_at', '<=',now()->parse($timeDiff[1])->endOfDay());
        }
        if($orderBy){
            $model->orderBy($orderBy, $sort);
        }
        if($paging){
            return $model->paginate($limit);
        }else{
            return $this->noPaginate($model);
        }
    }
    /**
     * 分组导出所有数据
     *
     * @return Array
     */
    public function noPaginate($model)
    {
        ini_set ('memory_limit', '512M');
        $datas = [];
        $model->chunk(5000, function($items) use($datas) {
            $datas =  array_merge($items->all(), $datas);
        });
        return $datas;
    }
}
