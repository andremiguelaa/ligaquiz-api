<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

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
                    'individual_quiz_list' => true,
                    'individual_quiz_create' => true,
                    'individual_quiz_edit' => true,
                    'individual_quiz_type_list' => true,
                    'individual_quiz_player_list' => true,
                    'individual_quiz_player_create' => true,
                    'individual_quiz_result_list' => true,
                    'individual_quiz_result_create' => true,
                    'individual_quiz_result_edit' => true,
                ]),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ],
            /*
            [
                'role' => 'quiz_editor',
                'permissions' => json_encode((object) [
                    'quiz_list' => true,
                    'quiz_create' => true,
                    'quiz_edit' => true,
                    'question_search' => true,
                ]),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ],
            [
                'role' => 'specialquiz_editor',
                'permissions' => json_encode((object) [
                    'specialquiz_list' => true,
                    'specialquiz_create' => true,
                    'specialquiz_edit' => true,
                    'user_list' => true,
                ]),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ],
            [
                'role' => 'regular_player',
                'permissions' => json_encode((object) [
                    'league_list' => true,
                    'quiz_list' => true,
                    'quiz_play' => true,
                    'specialquiz_play' => true,
                ]),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ],
            [
                'role' => 'specialquiz_player',
                'permissions' => json_encode((object) [
                    'specialquiz_list' => true,
                    'specialquiz_play' => true,
                ]),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ],
            */
        ]);
    }
}
