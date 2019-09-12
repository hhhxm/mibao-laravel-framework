<?php

namespace Mibao\LaravelFramework\Helper;

use Carbon\Carbon;
// use App\Models\Vote;
// use App\Models\Work;
use \Illuminate\Support\Facades\Redis;

class RedisController
{
    protected static $base_key='2019_tmxw_love_sound_';
    /**
     * 黑名单
     */
    public static function setBlackList($wechat_user_id)
    {
        $key = static::$base_key."BlackList";
        Redis::sadd($key, $wechat_user_id);
    }
    public static function removeBlackList($wechat_user_ids)
    {
        $key = static::$base_key."BlackList";
        Redis::srem($key, $wechat_user_ids);
    }
    public static function getBlackList()
    {
        $key = static::$base_key."BlackList";
        return Redis::smembers($key);
    }
    /**
     * 保存当天的用户信息
     */
    public static function setTodayUserValue($wechat_user_id, $keyName, $value)
    {
        $today = now()->toDateString();
        $key = static::$base_key."TodayUserValue:$today:$wechat_user_id:$keyName";
        Redis::set($key, 1);
        // 设置一天后过期
        Redis::expire($key, 60*60*24);
    }
    public static function getTodayUserValue($wechat_user_id, $keyName)
    {
        $today = now()->toDateString();
        $key = static::$base_key."TodayUserValue:$today:$wechat_user_id:$keyName";
        return Redis::get($key);
    }
    /**
     * 设置用户的私人值
     */
    public static function set_user_value($key, $wechat_user_id)
    {
        $key = static::$base_key."$key:$wechat_user_id";
        Redis::set($key, 1);
    }
    public static function get_user_value($key, $wechat_user_id)
    {
        $key = static::$base_key."$key:$wechat_user_id";
        return Redis::get($key);
    }
    public static function del_user_value($key, $wechat_user_id)
    {
        $key = static::$base_key."$key:$wechat_user_id";
        Redis::del($key);
    }
    /**
     * 微信登录电子票
     */
    public static function set_wechat_login_ticket($token, $user)
    {
        $ticket = md5($user->openid.time());
        $key = static::$base_key.'wechat_login_ticket_'.$ticket;
        Redis::set($key, $token);
        Redis::expire($key, 120);
        return (Object) [
                   'token'=>$token,
                   'ticket'=>$ticket,
               ];
    }
    public static function get_wechat_login_ticket($ticket)
    {
        $key = static::$base_key.'wechat_login_ticket_'.$ticket;
        $token = Redis::get($key);
        Redis::del($key);
        return $token;
    }
    /**
     * 设置网站参数
     */
    public static function set_site_value($name, $value)
    {
        $key = static::$base_key.'site_value:'.$name;
        Redis::set($key, $value);
    }
    public static function get_site_value($name)
    {
        $key = static::$base_key.'site_value:'.$name;
        return Redis::get($key);
    }
    /**
     * 更新用户在排行榜的最高分数的作品数据
     */
    public static function saveUserRankData($wechat_user_id)
    {
        $res = static::readUserTopWorkId($wechat_user_id);
        // 更新用户的最高分
        $key = static::$base_key."WorkRankScore";
        Redis::zadd($key, $res['vote'], $wechat_user_id);

        // 更新用户的最高分作品id
        $key = static::$base_key."WorkRankId";
        Redis::zadd($key, $res['work_id'], $wechat_user_id);

    }
    /**
     * 读取用户排行榜的排名
     */
    public static function readUserRankOrder($wechat_user_id)
    {
        $key = static::$base_key."WorkRankScore";
        $res = Redis::ZREVRANK($key, $wechat_user_id);
        if(is_numeric($res)){
          $res += 1;
        }
        return $res;
    }
    /**
     * 读取某个排名的用户
     */
    public static function readUserByRank($rank=1)
    {
        $key = static::$base_key."WorkRankScore";
        $user = Redis::ZREVRANGE($key, $rank-1, $rank-1, 'WITHSCORES');
        // 输出用户id
        return key($user);
    }
    /**
     * 读取用户排行榜的作品id数组
     */
    public static function readWorkRankIds()
    {
        $key = static::$base_key."WorkRankId";
        $ids = Redis::ZRANGE($key, 0, -1, 'WITHSCORES');
        if(!$ids){
            static::resetRankFromMysql();
            $ids = Redis::ZRANGE($key, 0, -1, 'WITHSCORES');
        }
        return $ids;
    }
    /**
     * 读取用户排行榜的作品分数数组
     */
    public static function readWorkRankScores()
    {
        $key = static::$base_key."WorkRankScore";
        $scores = Redis::ZRANGE($key, 0, -1, 'WITHSCORES');
        if(!$scores){
            static::resetRankFromMysql();
            $scores = Redis::ZRANGE($key, 0, -1, 'WITHSCORES');
        }
        return $scores;
    }
    /**
     * 保存用户作品列表
     */
    public static function saveUserWorkScore($wechat_user_id, $work_id, $vote)
    {
        $key = static::$base_key."MyWork:$wechat_user_id";
        Redis::zadd($key, $vote, $work_id);
        static::saveUserRankData($wechat_user_id);
    }
    /**
     * 保存当天投票信息
     */
    public static function addUserVote($wechat_user_id, $work_id, $type)
    {
        $today = now()->toDateString();
        $key = static::$base_key."UserWorkVote:$today:$work_id:$type:$wechat_user_id";
        $vote = static::readUserVote($wechat_user_id, $work_id, $type) + 1;
        // 加票
        Redis::set($key, $vote);
        // 设置一天后过期
        Redis::expire($key, 60*60*24);
    }
    /**
     * 读取当天投票信息
     */
    public static function readUserVote($wechat_user_id, $work_id, $type)
    {
        $today = now()->toDateString();
        $key = static::$base_key."UserWorkVote:$today:$work_id:$type:$wechat_user_id";
        $vote = Redis::get($key);
        if(!$vote){
          $vote = Vote::where('wechat_user_id', $wechat_user_id)
                    ->where('work_id', $work_id)
                    ->where('type', $type)
                    ->where('created_at', '>=', Carbon::today()->startOfDay())
                    ->where('created_at', '<=',Carbon::today()->endOfDay())
                    ->lockForUpdate()
                    ->count()
                    ;
        }
        return (integer) $vote;
    }
    /**
     * 读取用户最高分作品
     */
    public static function readUserTopWorkId($wechat_user_id)
    {
        $key = static::$base_key."MyWork:$wechat_user_id";
        
        $res = Redis::ZREVRANGE($key, 0, 0, 'WITHSCORES');
        if($res){
          $work_id = array_keys($res)[0];
          $vote = $res[$work_id];
          return array(
            'work_id'=>$work_id,
            'vote'=>$vote,
          );
        }else{
          return false;
        }
    }
    /**
     * 清空redis，重新统计数据
     */
    public static function resetRedis()
    {
        $keys = Redis::keys(static::$base_key.'*');
        /* foreach ($keys as $key) {
            // larave用keys导出的数据都加了前置名laravel_database_
            // 但使用del的时候，程序会再加上前置名，所以重复了，删除不了。
            $tmp = explode('laravel_database_', $key);
            Redis::del($tmp[1]);
        } */
        $keys ? Redis::del($keys) : null;
        static::resetRankFromMysql();
        /*Redis::del(static::$base_key.'WorkRankScore');
        Redis::del(static::$base_key.'WorkRankId');
        // 删除用户的作品信息
        $keys = Redis::keys(static::$base_key.'MyWork*');
        $keys ? Redis::del($keys) : null;
        // 删除用户的投票信息
        $keys = Redis::keys(static::$base_key.'UserWorkVote*');

        static::readWorkRankIds();
        dd(Redis::ZRANGE(static::$base_key.'WorkRankScore', 0, -1, 'WITHSCORES'));*/
    }
    /**
     * 根据数据库，重置所有用户的作品和排名
     */
    public static function resetRankFromMysql()
    {
        $keys = Redis::keys(static::$base_key.'MyWork:');
        $keys ? Redis::del($keys) : null;
        $keys = Redis::keys(static::$base_key.'WorkRankScore');
        $keys ? Redis::del($keys) : null;
        $keys = Redis::keys(static::$base_key.'WorkRankId');
        $keys ? Redis::del($keys) : null;
        $works = Work::get();
        $blackList = RedisController::getBlackList();

        foreach ($works as $work) {
            // 把每个用户最高分的作品写入redis
            if($work->score_total>0 && !in_array($work->wechat_user_id, $blackList)){
                // print_r($work->wechat_user_id);
                // print_r($work->id.":");
                // print_r($work->score_total."\r\n");
                static::saveUserWorkScore($work->wechat_user_id, $work->id, $work->score_total);
            }
        }
    }
}
