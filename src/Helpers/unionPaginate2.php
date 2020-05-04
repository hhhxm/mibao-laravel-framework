<?php

namespace App\Http\Controllers\Tools;
use Log;
use DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

ini_set ('memory_limit', '256M');

class unionPaginate
{
    protected $database;
    protected $tablePrefix;
    protected $request;
    protected $model;
    protected $tables;
    public $dateFieldName='created_at';
    public $startDate;
    public $endDate;
 
    public function __construct($database, $tablePrefix, $request=null)
    {
        $this->database    = $database;
        $this->tablePrefix = $tablePrefix;
        $this->request     = $request;
        $this->model       = null;
        $this->where       = null;
        $this->tables      = [];
        $this->startDate   = $request && $request->timeDiff[0] ? Carbon::parse($request->timeDiff[0]): null;
        $this->endDate     = $request && $request->timeDiff[1] ? Carbon::parse($request->timeDiff[1]): null;
    }

    public function where($where)
    {
       $this->where .= $where.' ';
    }

    public function unionSelect()
    {
        $this->setTables();
        foreach ($this->tables as $table) {
            $model = DB::connection($this->database)->table($table);
            !$this->where     ?: $model->whereRaw($this->where);
            !$this->startDate ?: $model->where($dateFieldName, '>=',Carbon::parse($this->startDate)->startOfDay());
            !$this->endDate   ?: $model->where($dateFieldName, '<',Carbon::parse($this->endDate)->endOfDay());
            $this->model = !$this->model ? $model : $this->model->union($model);
        }
        $this->model->orderby($dateFieldName, 'DESC');
        return $this->model;
    }
    public function setTables()
    {
        $tables = $this->sortTables();
        if(!$this->startDate && !$this->endDate){
            $this->tables=array_slice($tables,-1,1);
        }else{
            $startPos = 0;
            $endPos = count($tables)-1;
            if($this->startDate){
                $table = $this->tablePrefix.static::yearMonth($this->startDate);
                $startPos = array_search($table, $tables);
            }
            if($this->endDate){
                $table = $this->tablePrefix.static::yearMonth($this->endDate);
                $pos = array_search($table, $tables);
                !$pos > -1 ? : $endPos = $pos;
            }
            $this->tables=array_slice($tables,$startPos,$endPos - $startPos + 1);
            // dd($tables,$this->tables,$startPos,$endPos);
        }
    }
    /**
     * 把同类表名排序
     */
    public function sortTables()
    {
        // 使用getDoctrineSchemaManager，会导致数据库读写分离的操作为写操作，如果写服务器不在线就会出错。
        // $tables = DB::connection($this->database)->getDoctrineSchemaManager()->listTableNames();
        $tables = array_map('reset', DB::connection($this->database)->select('SHOW TABLES'));
        $arr = [];
        foreach ($tables as $table) {
            strpos($table, $this->tablePrefix) > -1 ? $arr[]=$table : null;
        }
        sort($arr);
        return $arr;
    }
    public static function yearMonth($date)
    {
        return (int) $date->format('Ym');
    }
    public function paginate()
    {
        $this->unionSelect();
        $limit = $this->request->limit ? : 30;
        $page = $this->request->page ? : 1;
        $items = $this->model->get();
        $slice = array_slice($items->toArray(), $limit * ($page - 1), $limit);
        return new LengthAwarePaginator($slice, count($items), $limit, $page, ['path' => $this->request->url(), 'query'=>$this->request->query()]);
    }
    public function count()
    {
        $this->unionSelect();
        return $this->model->count();
    }

    public function get($limit=null)
    {
        $this->unionSelect();
        !$limit ? : $this->model->limit($limit);
        return $this->model->get();
    }

    public static function getMotionModelFromFilePath($database, $tablePrefix, $path)
    {
        $tableName = static::getTableNameFromFilePath($tablePrefix, $path);
        if(!Schema::connection($database)->hasTable($tableName)){
            Schema::connection($database)->create($tableName, function ($table) {
                $table->increments('id');
                $table->string('uuid');
                $table->string('file');
                $table->timestamps();
            });
        }
        return DB::connection($database)->table($tableName);
    }
    public static function getPanoramaModelFromFilePath($database, $tablePrefix, $path)
    {
        $tableName = static::getTableNameFromFilePath($tablePrefix, $path);
        // dd($tableName);
        if(!Schema::connection($database)->hasTable($tableName)){
            Schema::connection($database)->create($tableName, function ($table) {
                $table->increments('id');
                $table->string('uuid')->nullable();
                $table->string('ff')->nullable();
                $table->string('fl')->nullable();
                $table->string('ll')->nullable();
                $table->string('bl')->nullable();
                $table->string('bb')->nullable();
                $table->string('br')->nullable();
                $table->string('rr')->nullable();
                $table->string('fr')->nullable();
                $table->string('panorama')->nullable();
                $table->integer('is_black')->default(0);
                $table->integer('is_stitched')->default(0);
                $table->integer('is_handle')->default(0);
                $table->timestamps();
            });
        }
        return DB::connection($database)->table($tableName);
    }
    public static function getTableNameFromFilePath($tablePrefix, $path)
    {
        $tmp = explode('/', $path);
        $year = $tmp[1];
        $month = str_pad($tmp[2],2,"0",STR_PAD_LEFT);
        return "$tablePrefix$year$month";
    }
}