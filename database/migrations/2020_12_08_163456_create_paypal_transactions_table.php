<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaypalTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paypal_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('token');
            $table->string('url');
            $table->string('status');
            $table->string('period');
            $table->string('ammount');
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
        Schema::dropIfExists('paypal_transactions');
    }
}
