<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id')->nullable();  // 用户ID
            $table->string('password');
            $table->string('appid')->nullable();  // 公众号id
            $table->string('openid')->unique();  // 用户的唯一标识
            $table->string('nickname')->nullable();  // 用户昵称
            $table->string('sex')->nullable();  // 用户的性别，值为1时是男性，值为2时是女性，值为0时是未知
            $table->string('language')->nullable();  // 语言
            $table->string('province')->nullable();  // 用户个人资料填写的省份
            $table->string('city')->nullable();  // 用户个人资料填写的城市
            $table->string('country')->nullable();  // 国家，如中国为CN
            $table->string('headimgurl')->nullable();  // 用户头像，最后一个数值代表正方形头像大小（有0、46、64、96、132数值可选，0代表640*640正方形头像），用户没有头像时该项为空。若用户更换头像，原有头像URL将失效。
            $table->string('privilege')->nullable();  // 用户特权信息，json 数组，如微信沃卡用户为（chinaunicom）
            $table->string('unionid')->nullable();  // 只有在用户将公众号绑定到微信开放平台帐号后，才会出现该字段。
            $table->timestamps();
            $table->index(['user_id','openid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wechat_users');
    }
}
