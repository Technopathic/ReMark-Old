<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('subscriptions', function (Blueprint $table) {
        $table->increments('id');
        $table->string('subscriptionContact')->nullable();
        $table->string('subscriptionType');
        $table->string('subscriptionFrequency')->default('Weekly');
        $table->string('subscriptionID');
        $table->boolean('subscriptionActive')->default(0);
        $table->timestamps(3);
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::drop('subscriptions');
    }
}
