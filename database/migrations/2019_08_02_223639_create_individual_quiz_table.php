<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIndividualQuizTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('individual_quizzes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('individual_quiz_type');
            $table->date('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('individual_quizzes');
    }
}
