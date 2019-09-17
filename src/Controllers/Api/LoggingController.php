<?php

namespace Mibao\LaravelFramework\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Mibao\LaravelFramework\Controllers\Controller;
use Mibao\LaravelFramework\Models\Logging;
use Auth;

class LoggingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $model = Logging::select();
        return responder()->success($this->conditionsPaginate($model, $request));
    }

    /**
     * 保存客户端的出错日志
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'type'    => 'required|string',
            'content' => 'required|string',
        ])->validate();
        
        $user = Auth::user();
        $data = $request->all();
        $data['model_type'] = get_class($user);
        $data['model_id']   = $user->id;
        $data['ip']         = $request->getClientIp();
        
        Logging:: create($data);
        
        return responder()->success();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
