<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('users')->truncate();
        DB::table('users')->insert([
            [
                'name' => 'admin name',
                'surname' => 'admin surname',
                'email' => 'admin@ligaquiz.pt',
                'password' => bcrypt('secret'),
                'roles' => json_encode((object) [
                    'admin' => true,
                ]),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ],
        ]);
        DB::table('users')->insert([
            [
                'name' => 'ranking_manager name',
                'surname' => 'ranking_manager surname',
                'email' => 'ranking_manager@ligaquiz.pt',
                'password' => bcrypt('secret'),
                'roles' => json_encode((object) [
                    'national_ranking_manager' => true,
                ]),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ],
        ]);
        for ($i = 0; $i < 10; ++$i) {
            DB::table('users')->insert([
                [
                    'name' => 'user name ' . $i,
                    'surname' => 'user surname ' . $i,
                    'email' => 'user' . $i . '@ligaquiz.pt',
                    'password' => bcrypt('secret'),
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ],
            ]);
        }
    }
}
