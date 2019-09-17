<?php

namespace Mibao\LaravelFramework\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class RedisHelper
{

    /**
     * 当天剩余的秒数
     */
    protected function restSecondOfDay()
    {
        return now()->endOfDay()->diffInSeconds();
    }
    /**
     * 当天日期字符
     */
    protected function dateString()
    {
        return now()->toDateString();
    }
    /**
     * 当天日期字符
     * @param $prefix      string 前缀
     * @param $keyName     string 键名
     * @param $modelType   string 模型类型
     * @param $modelId     string 模型id
     * @param $requestDate boolen 是否加入当天日期

     */
    protected function getRedisKey($keyName, $modelType=null, $modelId=null, $requestDate=false)
    {
        $key = env('REDIS_KEY','mibao').":";
        // $key .= "$prefix:";
        !$requestDate ?: $key .= $this->dateString().":";
        $key .= "$keyName:";
        !$modelType ?: $key .= "$modelType:";
        !$modelId ?: $key .= "$modelId:";
        return $key;
    }
    /**
     * 保存缓存信息
     * @param $modelType string 模型类型
     * @param $modelId   string 模型id
     * @param $keyName   string 键名
     * @param $value     string 值
     */
    public function setValue($keyName, $value, $modelType=null, $modelId=null, $requestDate=false)
    {
        $key = $this->getRedisKey($keyName, $modelType, $modelId, $requestDate);
        // 当天24点后过期
        Redis::setex($key, $this->restSecondOfDay(), $value);
    }
    /**
     * 获取缓存信息
     * @param $modelType string 模型类型
     * @param $modelId   string 模型id
     * @param $keyName   string 键名
     */
    public function getValue($keyName, $modelType=null, $modelId=null, $requestDate=false)
    {
        $key = $this->getRedisKey($keyName, $modelType, $modelId, $requestDate);
        return Redis::get($key);
    }
}
