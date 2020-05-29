<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class RolesPermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('roles_permissions')->insert([
            [
                'role' => 'national_ranking_manager',
                'permissions' => json_encode((object) [
                    'national_ranking_create' => true,
                    'national_ranking_edit' => true,
                    'national_ranking_delete' => true,
                    'individual_quiz_player_create' => true,
                    'individual_quiz_player_edit' => true,
                    'individual_quiz_player_delete' => true,
                ]),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
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
