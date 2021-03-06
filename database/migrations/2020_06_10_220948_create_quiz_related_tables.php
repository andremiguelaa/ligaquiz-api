<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuizRelatedTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('content')->nullable();
            $table->text('answer')->nullable();
            $table->integer('media_id')->nullable();
            $table->integer('genre_id')->nullable();
            $table->timestamps();
        });
        Schema::create('quizzes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date')->nullable();;
            $table->json('question_ids');
            $table->timestamps();
        });
        Schema::create('special_quizzes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date')->nullable();;
            $table->json('question_ids');
            $table->integer('user_id')->nullable();
            $table->string('subject')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::create('answers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('question_id')->index();
            $table->integer('user_id');
            $table->text('text')->nullable();
            $table->integer('points');
            $table->boolean('correct');
            $table->boolean('corrected');
            $table->boolean('submitted');
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
        Schema::dropIfExists('questions');
        Schema::dropIfExists('quizzes');
        Schema::dropIfExists('special_quizzes');
        Schema::dropIfExists('answers');
    }
}
