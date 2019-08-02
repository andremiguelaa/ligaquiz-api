<?php

use Illuminate\Database\Seeder;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('permissions')->insert([
            ['slug' => 'user_list'],
            ['slug' => 'user_create'],
            ['slug' => 'user_edit'],
            ['slug' => 'user_delete'],

            ['slug' => 'league_list'],
            ['slug' => 'league_create'],
            ['slug' => 'league_edit'],
            ['slug' => 'league_delete'],

            ['slug' => 'quiz_list'],
            ['slug' => 'quiz_create'],
            ['slug' => 'quiz_edit'],
            ['slug' => 'quiz_delete'],
            ['slug' => 'quiz_correct'],
            ['slug' => 'quiz_play'],

            ['slug' => 'specialquiz_list'],
            ['slug' => 'specialquiz_create'],
            ['slug' => 'specialquiz_edit'],
            ['slug' => 'specialquiz_delete'],
            ['slug' => 'specialquiz_correct'],
            ['slug' => 'specialquiz_play'],

            ['slug' => 'ranking_manage'],

            ['slug' => 'question_search'],
        ]);
    }
}
