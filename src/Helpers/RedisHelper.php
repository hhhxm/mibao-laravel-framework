<?php

namespace Mibao\LaravelFramework\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class RedisHelper
{

    /**
     * Create a new RedisHelper instance.
     *
     * @return void
     */
    public function __construct()
    {
        Redis::enableEvents();
    }
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
    public function setValue($keyName, $value, $modelType=null, $modelId=null, $requestDate=false, $timeout=null)
    {
        $key = $this->getRedisKey($keyName, $modelType, $modelId, $requestDate);
        // 默认当天24点后过期
        $timeout = $timeout ?: $this->restSecondOfDay();
        Redis::setex($key, $timeout, $value);
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
    /**
     * 删除缓存信息
     * @param $modelType string 模型类型
     * @param $modelId   string 模型id
     * @param $keyName   string 键名
     */
    public function delValue($keyName, $modelType=null, $modelId=null, $requestDate=false)
    {
        $key = $this->getRedisKey($keyName, $modelType, $modelId, $requestDate);
        Redis::del($key);
    }
}
