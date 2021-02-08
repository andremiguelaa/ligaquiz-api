<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpecialquizProposalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('special_quiz_proposals', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('subject');
            $table->text('description')->nullable();
            $table->text('content_1');
            $table->text('answer_1');
            $table->integer('media_1_id')->nullable();
            $table->text('content_2');
            $table->text('answer_2');
            $table->integer('media_2_id')->nullable();
            $table->text('content_3');
            $table->text('answer_3');
            $table->integer('media_3_id')->nullable();
            $table->text('content_4');
            $table->text('answer_4');
            $table->integer('media_4_id')->nullable();
            $table->text('content_5');
            $table->text('answer_5');
            $table->integer('media_5_id')->nullable();
            $table->text('content_6');
            $table->text('answer_6');
            $table->integer('media_6_id')->nullable();
            $table->text('content_7');
            $table->text('answer_7');
            $table->integer('media_7_id')->nullable();
            $table->text('content_8');
            $table->text('answer_8');
            $table->integer('media_8_id')->nullable();
            $table->text('content_9');
            $table->text('answer_9');
            $table->integer('media_9_id')->nullable();
            $table->text('content_10');
            $table->text('answer_10');
            $table->integer('media_10_id')->nullable();
            $table->text('content_11');
            $table->text('answer_11');
            $table->integer('media_11_id')->nullable();
            $table->text('content_12');
            $table->text('answer_12');
            $table->integer('media_12_id')->nullable();
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
        Schema::dropIfExists('special_quiz_proposals');
    }
}
