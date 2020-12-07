<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExternalQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('external_questions', function (Blueprint $table) {
            $table->id();
            $table->text('formulation');
            $table->text('answer')->nullable();
            $table->string('media')->nullable();
            $table->string('genre')->nullable();
            $table->string('origin');
            $table->boolean('used')->default(0);
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
        Schema::dropIfExists('external_questions');
    }
}
