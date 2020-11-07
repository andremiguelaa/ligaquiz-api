<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->string('code');
            $table->primary('code');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('region')->after('avatar')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('regions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('region');
        });
    }
}
