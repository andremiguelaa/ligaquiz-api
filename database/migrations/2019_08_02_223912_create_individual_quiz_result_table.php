<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIndividualQuizResultTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('individual_quiz_results', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('individual_quiz_id');
            $table->integer('individual_quiz_player_id');
            $table->integer('result');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('individual_quiz_results');
    }
}
