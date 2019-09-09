<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()['cache']->forget('spatie.permission.cache');

        // create permissions
        Permission::create(['guard_name' => 'admin', 'name' => 'add user']);
        Permission::create(['guard_name' => 'admin', 'name' => 'delete user']);
        Permission::create(['guard_name' => 'admin', 'name' => 'edit user']);
        Permission::create(['guard_name' => 'admin', 'name' => 'view user']);

        // create roles and assign created permissions
        $model = Role::create(['guard_name' => 'admin', 'name' => 'admin']);
        $model->givePermissionTo(['add user', 'delete user', 'edit user', 'view user',]);

        $model = Role::create(['guard_name' => 'admin', 'name' => 'super-admin']);
        $model->givePermissionTo(Permission::all());
    }
}
