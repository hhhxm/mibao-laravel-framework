<?php

namespace Mibao\LaravelFramework\Helpers;

use Log;
use DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Mibao\LaravelFramework\Controllers\Controller;
use Carbon\Carbon;

class unionPaginate extends Controller
{
    protected $database;
    protected $tablePrefix;
    protected $request;
    protected $model;
    protected $dateField;
    protected $dateFormat;

    public function __construct($database, $tablePrefix, $request, $dateField='created_at', $dateFormat='Ym')
    {
        $this->database    = $database;
        $this->tablePrefix = $tablePrefix;
        $this->request     = $request;
        $this->dateField   = $dateField;
        $this->dateFormat  = $dateFormat;
        $this->model       = null;
        $this->where       = null;
    }
    public function where($where)
    {
       $this->where .= $where.' ';
    }
    public function unionSelect()
    {
        $startDate = $this->request->startDate ? Carbon::parse($this->request->startDate) : now();
        $endDate   = $this->request->endDate ? Carbon::parse($this->request->endDate) : now();
        $currentDate = $startDate->copy();
        while ($this->dateString($currentDate) <= $this->dateString($endDate)) {
            $tableName = $this->tablePrefix . $this->dateString($currentDate);
            if(!$this->model){
                $this->model = DB::connection($this->database)->table($tableName);
            }else{
                $this->model = DB::connection($this->database)->table($tableName)->union($this->model);
            }
            !$this->where ? : $this->model->whereRaw($this->where);
            $this->model->where($this->dateField, '>=',Carbon::parse($startDate)->startOfDay());
            $this->model->where($this->dateField, '<=',Carbon::parse($endDate)->endOfDay());
            // $this->model->orderby($this->dateField, 'DESC');
            switch ($this->dateFormat) {
                case 'Ym':
                    $currentDate = $currentDate->addMonth();
                    break;
                case 'Ymd':
                    $currentDate = $currentDate->addDay();
                    break;
                default:
                    $currentDate = $currentDate->addYear();
            }
        }
        $this->model->orderby($this->dateField, 'DESC');
        return $this->model;
    }
    public function dateString($date)
    {
        return (int) $date->format($this->dateFormat);
    }
    public function paginate()
    {
        $this->unionSelect();
        $limit = $this->request->limit ? : 30;
        $page = $this->request->page ? : 1;
        $items = $this->model->get();
        $slice = array_slice($items->toArray(), $limit * ($page - 1), $limit);
        return new LengthAwarePaginator($slice, count($items), $limit, $page, ['path' => $this->request->url(), 'query'=>$this->request->query()]);
        // return new LengthAwarePaginator($slice, count($items), $limit, $page, []);
    }
}
