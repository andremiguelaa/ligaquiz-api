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
            ['slug' => 'quiz_player'],
            ['slug' => 'special_quiz_player'],
            ['slug' => 'quiz_editor'],
            ['slug' => 'special_quiz_editor'],
            ['slug' => 'ranking_manager'],
        ]);
    }
}
