<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('users', function (Blueprint $table) {
         $table->increments('id');
         $table->string('name', 16);
         $table->string('email', 64);
         $table->string('password', 128);
         $table->string('displayName', 16)->nullable();
         $table->string('profileTitle', 80)->nullable();
         $table->longText('avatar');
         $table->longText('banner')->nullable();
         $table->longText('aboutMe')->nullable();
         $table->string('location')->nullable();
         $table->string('website')->nullable();
         $table->boolean('emailDigest')->default(1);
         $table->boolean('emailMessage')->default(1);
         $table->boolean('emailReply')->default(1);
         $table->integer('topics')->default(0);
         $table->integer('replies')->default(0);
         $table->integer('votesGiven')->default(0);
         $table->integer('votesReceived')->default(0);
         $table->integer('badges')->default(0);
         $table->string('role');
         $table->rememberToken();
         $table->string('key', 128)->default('0');
         $table->boolean('ban')->default(0);
         $table->boolean('activated')->default(0);
         $table->string('activation_token', 32);
         $table->timestamp('last_login');
         $table->timestamps();
     });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users');
    }
}
