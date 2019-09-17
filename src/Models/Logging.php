<?php

namespace Mibao\LaravelFramework\Models;

use Illuminate\Database\Eloquent\Model;

class Logging extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['model_type', 'model_id', 'server', 'request', 'content','created_at'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $visible = ['id', 'server', 'request', 'content','created_at'];
}
