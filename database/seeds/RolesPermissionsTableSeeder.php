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
                'role' => 'special_quiz_editor',
                'permissions' => json_encode((object) [
                    'users_list' => true,
                ])
            ],
            [
                'role' => 'ranking_manager',
                'permissions' => json_encode((object) [
                    'users_list' => true,
                ])
            ],
        ]);
    }
}
