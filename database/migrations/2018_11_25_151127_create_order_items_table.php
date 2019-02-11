<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('meal_id');
            $table->tinyInteger('quantity')->default(1);
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('meal_id')->references('id')->on('meals');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_items');
    }
}
