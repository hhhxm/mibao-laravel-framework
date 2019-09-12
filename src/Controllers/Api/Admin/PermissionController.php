<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Log;
use Auth;

class PermissionController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $permissions = Permission::with('roles');
        if($request->guard_name){
            $permissions->where('guard_name',$request->guard_name);
        }
        if($request->keyword){
            $permissions->where('name','LIKE', '%'.$request->keyword.'%');
        }
        $data = $this->paginateApiDate($request, $permissions);
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
            // 'role_ids'   => 'required|array',
        ]);
        if($validator !== true){
            return $this->failed(1002, $validator);
        }
        $permission = new Permission;
        $permission->name = $request->name;
        $permission->guard_name = $request->guard_name;
        $permission->save();
        $permission->syncRoles($request->role_ids);
        return $this->success($permission);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function show(Permission $permission)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function edit(Permission $permission)
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
    public function update(Permission $permission, Request $request )
    {
        $permission->name = $request->name;
        $permission->syncRoles($request->role_ids);
        $permission->save();
        return $this->success();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        Permission::destroy($request->ids);
        return $this->success();
    }
    /**
     * 批量创建角色
     *
     * @param  Array  $role_ids
     * @param  String  $guard_name
     * @return Boolen
     */
    public function create_roles($role_ids, $guard_name)
    {
        foreach ($role_ids as $id) {
            $role = Role::find($id);
            if($role->guard_name != $guard_name){
                //
            }
        }
    }
}
