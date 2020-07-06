<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuizQuestionTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('question_ids');
        });

        Schema::table('special_quizzes', function (Blueprint $table) {
            $table->dropColumn('question_ids');
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->integer('quiz_id');
            $table->integer('question_id');
            $table->timestamps();
        });

        Schema::create('special_quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->integer('special_quiz_id');
            $table->integer('question_id');
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
        Schema::table('quizzes', function (Blueprint $table) {
            $table->json('question_ids')->after('date');
        });

        Schema::table('special_quizzes', function (Blueprint $table) {
            $table->json('question_ids')->after('date');
        });

        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('special_quiz_questions');
    }
}
