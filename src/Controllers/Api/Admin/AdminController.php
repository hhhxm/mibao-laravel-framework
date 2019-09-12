<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Auth;
use Log;

class AdminController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $admin = Admin::with('roles');
        $data = $this->paginateApiDate($request, $admin);
        return $this->success($data);
    }
    public function user_info(Request $request)
    {
        $user = Auth::user();
        $res = array(
            'roles' => ['admin'],
            'info' => $user,
            // 'permissions' => $user->getAllPermissions(),
        );
        return $this->success($res);
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Admin  $admin
     * @return \Illuminate\Http\Response
     */
    public function show(Admin $admin)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Admin  $admin
     * @return \Illuminate\Http\Response
     */
    public function edit(Admin $admin)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admin  $admin
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Admin $admin)
    {
        foreach ($admin->getRoleNames() as $key => $roleName) {
          $admin->removeRole($roleName);
        }
        $admin->assignRole($request->role_ids);
        return $this->success(1);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Admin  $admin
     * @return \Illuminate\Http\Response
     */
    public function destroy(Admin $admin)
    {
        //
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function guards()
    {
        return array_keys(config('auth.guards'));
    }
    public function allRolePermission(Request $request)
    {
        $permissions = Permission::with('roles');
        if($request->guard_name){
            $permissions->where('guard_name',$request->guard_name);
        }
        $roles = Role::with('permissions');
        if($request->guard_name){
            $roles->where('guard_name',$request->guard_name);
        }
        return $this->success(array('permissions'=>$permissions->get(), 'roles'=>$roles->get(), 'guards'=>$this->guards()));
    }
}
