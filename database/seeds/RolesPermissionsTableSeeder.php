<?php

use Illuminate\Database\Seeder;

class RolesPermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('roles_permissions')->insert([
            [
                'role' => 'ranking_manager',
                'permissions' => json_encode((object) [
                    'user_list' => true,
                    'ranking_manage' => true,
                ])
            ],
            [
                'role' => 'quiz_editor',
                'permissions' => json_encode((object) [
                    'quiz_list' => true,
                    'quiz_create' => true,
                    'quiz_edit' => true,
                    'question_search' => true,
                ])
            ],
            [
                'role' => 'specialquiz_editor',
                'permissions' => json_encode((object) [
                    'specialquiz_list' => true,
                    'specialquiz_create' => true,
                    'specialquiz_edit' => true,
                    'user_list' => true,
                ])
            ],
            [
                'role' => 'regular_player',
                'permissions' => json_encode((object) [
                    'league_list' => true,
                    'quiz_list' => true,
                    'quiz_play' => true,
                    'specialquiz_play' => true,
                ])
            ],
            [
                'role' => 'specialquiz_player',
                'permissions' => json_encode((object) [
                    'specialquiz_list' => true,
                    'specialquiz_play' => true,
                ])
            ],
        ]);
    }
}
