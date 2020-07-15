<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeagueRelatedTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->integer('season')->unique();
            $table->timestamps();
        });
        Schema::create('rounds', function (Blueprint $table) {
            $table->id();
            $table->integer('season_id');
            $table->integer('round');
            $table->date('date');
            $table->timestamps();
        });
        Schema::create('leagues', function (Blueprint $table) {
            $table->id();
            $table->integer('season_id');
            $table->integer('tier');
            $table->json('user_ids');
            $table->timestamps();
        });
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->integer('round_id');
            $table->integer('user_id_1');
            $table->integer('user_id_2');
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
        Schema::dropIfExists('seasons');
        Schema::dropIfExists('rounds');
        Schema::dropIfExists('leagues');
        Schema::dropIfExists('games');
    }
}
