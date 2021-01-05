<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteQuestionsTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('questions_translations');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('questions_translations', function (Blueprint $table) {
            $table->id();
            $table->integer('question_id');
            $table->text('content')->nullable();
            $table->text('answer')->nullable();
            $table->boolean('used')->default(0);
            $table->integer('user_id');
            $table->timestamps();
        });
    }
}
