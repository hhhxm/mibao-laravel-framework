<?php

namespace Mibao\LaravelFramework\Controllers\Admin;

use Illuminate\Http\Request;
use Mibao\LaravelFramework\Controllers\Controller;
use Mibao\LaravelFramework\Models\Admin;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Auth;
use Log;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $admin = Admin::with('roles');
        $data = $this->paginateApiDate($request, $admin);
        return responder()->success($data);
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = Auth::user();
        $res = $id==$user->id ? array(
            'roles' => ['admin'],
            'info' => $user,
            'permissions' => $user->getAllPermissions(),
        ) : [] ;
        return responder()->success($res);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
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
        foreach ($admin->getRoleNames() as $key => $roleName) {
            $admin->removeRole($roleName);
          }
          $admin->assignRole($request->role_ids);
          return responder()->success();
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
