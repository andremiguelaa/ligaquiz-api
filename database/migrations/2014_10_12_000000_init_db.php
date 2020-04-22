<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InitDB extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('email')->unique();
            $table->string('name');
            $table->string('surname');
            $table->string('password');
            $table->json('roles')->nullable();
            $table->string('avatar')->nullable();
            $table->json('reminders')->nullable();
            $table->timestamps();
        });

        Schema::create('password_resets', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->index();
            $table->string('token');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->string('slug');
            $table->primary('slug');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->string('slug');
            $table->primary('slug');
            $table->timestamps();
        });

        Schema::create('roles_permissions', function (Blueprint $table) {
            $table->string('role');
            $table->primary('role');
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        Schema::create('individual_quizzes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('individual_quiz_type');
            $table->date('date');
            $table->timestamps();
        });

        Schema::create('individual_quiz_types', function (Blueprint $table) {
            $table->string('slug');
            $table->primary('slug');
            $table->timestamps();
        });

        Schema::create('individual_quiz_results', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('individual_quiz_id');
            $table->integer('individual_quiz_player_id');
            $table->integer('result');
            $table->timestamps();
        });

        Schema::create('individual_quiz_players', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('surname')->nullable();
            $table->integer('user_id')->nullable();
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
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles_permissions');
        Schema::dropIfExists('individual_quizzes');
        Schema::dropIfExists('individual_quiz_types');
        Schema::dropIfExists('individual_quiz_results');
        Schema::dropIfExists('individual_quiz_players');
    }
}
