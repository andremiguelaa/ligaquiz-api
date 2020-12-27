<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCupRelatedTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cups', function (Blueprint $table) {
            $table->id();
            $table->integer('season_id');
            $table->json('tiebreakers');
            $table->timestamps();
        });

        Schema::create('cup_rounds', function (Blueprint $table) {
            $table->id();
            $table->integer('cup_id');
            $table->integer('cup_round');
            $table->integer('round_id');
            $table->timestamps();
        });

        Schema::create('cup_games', function (Blueprint $table) {
            $table->id();
            $table->integer('cup_round_id');
            $table->integer('user_id_1')->nullable();
            $table->integer('user_id_2')->nullable();
            $table->json('parent_cup_game_ids')->nullable();
            $table->timestamps();
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->integer('cup_points')->nullable()->after('points');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cups');
        Schema::dropIfExists('cup_rounds');
        Schema::dropIfExists('cup_games');
        Schema::table('answers', function (Blueprint $table) {
            $table->dropColumn('cup_points');
        });
    }
}
