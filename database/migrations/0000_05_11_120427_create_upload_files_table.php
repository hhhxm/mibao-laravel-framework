<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUploadFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::create('upload_files', function (Blueprint $table) {
        //     $table->increments('id');
        //     $table->integer('guard'); // 用户类型
        //     $table->integer('guard_user_id'); // 用户id
        //     $table->string('disk'); // 存放磁盘
        //     $table->string('name')->nullable(); // 图片名称
        //     $table->string('size')->nullable(); // 图片大小
        //     $table->integer('width')->nullable(); // 图片宽度
        //     $table->integer('height')->nullable(); // 图片高度
        //     $table->string('type')->nullable(); // 图片类型
        //     $table->string('ip')->nullable(); // 用户IP
        //     $table->timestamps();
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::dropIfExists('upload_files');
    }
}
