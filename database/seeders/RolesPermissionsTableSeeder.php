<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RolesPermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('roles_permissions')->truncate();
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
            [
                'role' => 'quiz_editor',
                'permissions' => json_encode((object) [
                    'quiz_create' => true,
                    'quiz_edit' => true,
                    'quiz_delete' => true,
                ]),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ],
            [
                'role' => 'special_quiz_editor',
                'permissions' => json_encode((object) [
                    'specialquiz_create' => true,
                    'specialquiz_edit' => true,
                    'specialquiz_delete' => true,
                    'specialquiz_proposal_list' => true,
                ]),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ],
            [
                'role' => 'answer_reviewer',
                'permissions' => json_encode((object) [
                    'answer_correct' => true,
                ]),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ],
            [
                'role' => 'regular_player',
                'permissions' => json_encode((object) [
                    'quiz_play' => true,
                    'specialquiz_play' => true,
                    'specialquiz_proposal_create' => true,
                ]),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ],
            [
                'role' => 'special_quiz_player',
                'permissions' => json_encode((object) [
                    'specialquiz_play' => true,
                    'specialquiz_proposal_create' => true,
                ]),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ],
        ]);
    }
}
