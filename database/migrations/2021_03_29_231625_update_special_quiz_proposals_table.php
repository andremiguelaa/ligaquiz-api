<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSpecialQuizProposalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('special_quiz_proposals', function (Blueprint $table) {
            $table->string('subject')->nullable()->change();
            $table->text('content_1')->nullable()->change();
            $table->text('answer_1')->nullable()->change();
            $table->text('content_2')->nullable()->change();
            $table->text('answer_2')->nullable()->change();
            $table->text('content_3')->nullable()->change();
            $table->text('answer_3')->nullable()->change();
            $table->text('content_4')->nullable()->change();
            $table->text('answer_4')->nullable()->change();
            $table->text('content_5')->nullable()->change();
            $table->text('answer_5')->nullable()->change();
            $table->text('content_6')->nullable()->change();
            $table->text('answer_6')->nullable()->change();
            $table->text('content_7')->nullable()->change();
            $table->text('answer_7')->nullable()->change();
            $table->text('content_8')->nullable()->change();
            $table->text('answer_8')->nullable()->change();
            $table->text('content_9')->nullable()->change();
            $table->text('answer_9')->nullable()->change();
            $table->text('content_10')->nullable()->change();
            $table->text('answer_10')->nullable()->change();
            $table->text('content_11')->nullable()->change();
            $table->text('answer_11')->nullable()->change();
            $table->text('content_12')->nullable()->change();
            $table->text('answer_12')->nullable()->change();
            $table->boolean('draft')->default(0)->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('special_quiz_proposals', function (Blueprint $table) {
            $table->dropColumn('draft');
            $table->string('subject')->nullable(false)->change();
            $table->text('content_1')->nullable(false)->change();
            $table->text('answer_1')->nullable(false)->change();
            $table->text('content_2')->nullable(false)->change();
            $table->text('answer_2')->nullable(false)->change();
            $table->text('content_3')->nullable(false)->change();
            $table->text('answer_3')->nullable(false)->change();
            $table->text('content_4')->nullable(false)->change();
            $table->text('answer_4')->nullable(false)->change();
            $table->text('content_5')->nullable(false)->change();
            $table->text('answer_5')->nullable(false)->change();
            $table->text('content_6')->nullable(false)->change();
            $table->text('answer_6')->nullable(false)->change();
            $table->text('content_7')->nullable(false)->change();
            $table->text('answer_7')->nullable(false)->change();
            $table->text('content_8')->nullable(false)->change();
            $table->text('answer_8')->nullable(false)->change();
            $table->text('content_9')->nullable(false)->change();
            $table->text('answer_9')->nullable(false)->change();
            $table->text('content_10')->nullable(false)->change();
            $table->text('answer_10')->nullable(false)->change();
            $table->text('content_11')->nullable(false)->change();
            $table->text('answer_11')->nullable(false)->change();
            $table->text('content_12')->nullable(false)->change();
            $table->text('answer_12')->nullable(false)->change();
        });
    }
}
