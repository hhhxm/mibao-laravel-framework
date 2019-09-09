<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;


class Admin extends Authenticatable
{
    use HasApiTokens, Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 'name', 'email', 'phone', 'password', ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
/*
    protected $hidden = [
        'password', 'remember_token',
    ];
*/
    protected $visible = ['id', 'name', 'email', 'phone', 'created_at', 'roles'];
    /**
     * 除了邮件还可以用用户名认证
     *
     */
    public function findForPassport($username)
    {
        return $this->orWhere('email', $username)->orWhere('name', $username)->first();
    }
}
