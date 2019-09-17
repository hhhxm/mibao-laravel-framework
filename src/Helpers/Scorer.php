<?php

namespace Mibao\LaravelFramework\Helpers;

use Illuminate\Support\Facades\Redis;
use Mibao\LaravelFramework\Helpers\RedisHelper;

class Scorer extends RedisHelper
{
    /**
     * 保存用户作品列表
     * @param $modelType string  模型类型
     * @param $modelId   string  模型id
     * @param $workId    string  作品id
     * @param $score     interge 获得分数
     */
    public function setModelWorkScore($modelType, $modelId, $workId, $score)
    {
        $key = $this->getRedisKey($modelType, $modelId, 'UserWork');
        Redis::zadd($key, $score, $workId);
        $this->setModelRankData($modelType, $modelId);
    }
    /**
     * 更新用户在排行榜的最高分数的作品数据
     */
    public function setModelRankData($modelType, $modelId, $workType='WorkRank')
    {
        $work = $this->getModelTopWorkItem($modelType, $modelId);
        // 更新用户的最高分
        if($work){
            $key = $this->getRedisKey($workType.":Score");
            Redis::zadd($key, $work['score'], "$modelType:$modelId");
            
            // 更新用户的最高分作品id
            $key = $this->getRedisKey($workType.":Id");
            Redis::zadd($key, $work['id'], "$modelType:$modelId");
        }
    }
    /**
     * 读取用户最高分作品
     */
    public function getModelTopWorkItem($modelType, $modelId)
    {
        $key = $this->getRedisKey($modelType, $modelId, 'UserWork');
        $res = Redis::ZREVRANGE($key, 0, 0, 'WITHSCORES');
        if ($res) {
            $id = array_keys($res)[0];
            $score = $res[$id];
            return array(
                'id' => $id,
                'score' => $score,
            );
        } else {
            return null;
        }
    }
    /**
     * 读取用户排行榜的排名
     */
    public function getModelRankNumber($modelType, $modelId, $workType='WorkRank')
    {
        $key = $this->getRedisKey($workType.":Score");
        $res = Redis::ZREVRANK($key, "$modelType:$modelId");
        return is_numeric($res) ? $res += 1 : null;
    }
    /**
     * 读取某个排名的用户
     */
    public function getModelByRankNumber($rank=1, $workType='WorkRank')
    {
        $key = $this->getRedisKey($workType.":Score");
        $user = Redis::ZREVRANGE($key, $rank - 1, $rank - 1, 'WITHSCORES');
        return key($user);
    }
    /**
     * 读取用户排行榜的作品id数组
     */
    public function getWorkRankIds($workType='WorkRank')
    {
        $key = $this->getRedisKey($workType.":Id");
        $ids = Redis::ZRANGE($key, 0, -1, 'WITHSCORES');
        if (!$ids) {
            $this->initRankFromDatabase();
            $ids = Redis::ZRANGE($key, 0, -1, 'WITHSCORES');
        }
        return $ids;
    }
    /**
     * 读取用户排行榜的作品分数数组
     */
    public function getWorkRankScores($workType='WorkRank')
    {
        $key = $this->getRedisKey($workType.":Score");
        $scores = Redis::ZRANGE($key, 0, -1, 'WITHSCORES');
        if (!$scores) {
            $this->initRankFromDatabase();
            $scores = Redis::ZRANGE($key, 0, -1, 'WITHSCORES');
        }
        return $scores;
    }
    /**
     * 当天单个用户，对单个作品的加分记录
     */
    public function increaseWorkScroeDaily($modelType, $modelId, $workId, $type=null, $workType='UserWork')
    {
        $keyName = "$workType:Scroe:$workId:$type";
        $score = $this->getWorkScroeDaily($modelType, $modelId, $workId, $type) + 1;
        $this->setValue($keyName, $score, $modelType, $modelId, true);
    }
    /**
     * 读取当天单个用户，对单个作品的加分记录
     */
    public function getWorkScroeDaily($modelType, $modelId, $workId, $type, $workType='UserWork')
    {
        $keyName = "$workType:Scroe:$workId:$type";
        $score = $this->getValue($keyName, $modelType, $modelId, true);
        if (!$score) {
            $score = $this->getWorkScroeDailyFromDatabase($modelType, $modelId, $workId, $type);
        }
        return (int) $score;
    }

    public function getWorkScroeDailyFromDatabase($modelType, $modelId, $workId, $type=null)
    {
        // $score = Vote::where('wechat_user_id', $modelId)
        // ->where('work_id', $workId)
        // ->where('type', $type)
        // ->where('created_at', '>=', now()->startOfDay())
        // ->where('created_at', '<=', now()->endOfDay())
        // ->lockForUpdate()
        // ->count();
        return 0;
    }

    /**
     * 清空redis，重新统计数据
     */
    public function resetRedis()
    {
        $keys = Redis::keys("*".env('REDIS_KEY','mibao*').":".'*');
        // dd($keys);
        foreach ($keys as $key) {
            // larave用keys导出的数据都加了前置名laravel_database_
            // 但使用del的时候，程序会再加上前置名，所以重复了，删除不了。
            $tmp = explode('laravel_database_', $key);
            Redis::del($tmp[1]);
        }
        // $keys ? Redis::del($keys) : null;
        // $this->initRankFromDatabase();
    }
    /**
     * 根据数据库，重置所有用户的作品和排名
     */
    public function initRankFromDatabase()
    {
        // $keys = Redis::keys($this->$base_key . 'MyWork:');
        // $keys ? Redis::del($keys) : null;
        // $keys = Redis::keys($this->$base_key . 'WorkRankScore');
        // $keys ? Redis::del($keys) : null;
        // $keys = Redis::keys($this->$base_key . 'WorkRankId');
        // $keys ? Redis::del($keys) : null;
        // $works = Work::get();
        // $blackList = self::getBlackList();

        // foreach ($works as $work) {
        //     // 把每个用户最高分的作品写入redis
        //     if ($work->score_total > 0 && !in_array($work->wechat_user_id, $blackList)) {
        //         // print_r($work->wechat_user_id);
        //         // print_r($work->id.":");
        //         // print_r($work->score_total."\r\n");
        //         // $this->setModelWorkScore($work->wechat_user_id, $work->id, $work->score_total);
        //     }
        // }
    }
}
