<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\ApiController;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Log;
use Auth;

class RoleController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $roles = Role::with('permissions');
        if($request->guard_name){
            $roles->where('guard_name',$request->guard_name);
        }
        if($request->keyword){
            $roles->where('name','LIKE', '%'.$request->keyword.'%');
        }
        $data = $this->paginateApiDate($request, $roles);
        return $this->success($data);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = $this->validatorParams($request,[
            'name'       => 'required',
            'guard_name' => 'required',
        ]);
        if($validator !== true){
            return $this->failed(1002, $validator);
        }
        $role = new Role;
        $role->name = $request->name;
        $role->guard_name = $request->guard_name;
        $role->save();
        return $this->success($role);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function edit(Role $role)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function update(Role $role, Request $request)
    {
        $role->name = $request->name;
        $role->syncPermissions($request->permission_ids);
        $role->save();

        return $this->success($role);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        Role::destroy($request->ids);
        return $this->success();
    }
}
