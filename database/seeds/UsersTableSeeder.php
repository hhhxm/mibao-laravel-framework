<?php

use Illuminate\Database\Seeder;
use App\Models\Admin;
use App\Models\User;
// use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      Admin::query()->truncate();
      User::query()->truncate();
      $model = new Admin;
      $model->name = 'michael';
      $model->email = 'michael@localhost.com';
      $model->phone = '13888888888';
      $model->password = Hash::make('media999!@#');
      $model->save();
      $model->assignRole('super-admin');

      // $model = new Admin;
      // $model->name = 'loveAdmin';
      // $model->email = 'loveAdmin@localhost.com';
      // $model->phone = '13888888889';
      // $model->password = Hash::make('%^$025!@#');
      // $model->save();
      // $model->assignRole('data admin');

      $user = new User;
      $user->name = 'michael2';
      $user->email = 'michael2@localhost.com';
      $user->password = Hash::make('11111111');
      $user->save();
    }
}
