<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeFieldsUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->primary('id');
            $table->string('id', 36)->change();
            $table->string('phone')->unique()->nullable()->after('email_verified_at');
            $table->string('avatar')->nullable()->after('password');
            $table->string('active')->default(0)->after('avatar');
        });
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->string('model_id', 36)->change();
        });
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->string('model_id', 36)->change();
        });
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->string('user_id', 36)->change();
        });
        Schema::table('oauth_auth_codes', function (Blueprint $table) {
            $table->string('user_id', 36)->change();
        });
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->string('user_id', 36)->change();
        });
        Schema::table('media', function (Blueprint $table) {
            $table->string('model_id', 36)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {

            // $table->bigIncrements('id')->change(); // 有数据之后，因为值是字符，所以不能恢复为整数类型，否则出错
            $table->dropColumn('phone');
            $table->dropColumn('avatar');
            $table->dropColumn('active');
        });
        
        /* 
        // 有数据之后，因为值是字符，所以不能恢复为整数类型，否则出错
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->bigIncrements('model_id')->change();
        });
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->bigIncrements('model_id')->change();
        });
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->bigIncrements('user_id')->change();
        });
        Schema::table('oauth_auth_codes', function (Blueprint $table) {
            $table->bigIncrements('user_id')->change();
        });
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->bigIncrements('user_id')->change();
        }); */
    }
}
