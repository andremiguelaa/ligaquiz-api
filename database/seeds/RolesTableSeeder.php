<?php

use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('roles')->insert([
            ['slug' => 'admin'],
            ['slug' => 'ranking_manager'],
            ['slug' => 'quiz_editor'],
            ['slug' => 'special_quiz_editor'],
            ['slug' => 'regular_player'],
            ['slug' => 'special_quiz_player'],
        ]);
    }
}
