<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
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
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ],
        ]);
        DB::table('users')->insert([
            [
                'name' => 'ranking_manager name',
                'surname' => 'ranking_manager surname',
                'email' => 'ranking_manager@ligaquiz.pt',
                'password' => bcrypt('secret'),
                'roles' => json_encode((object) [
                    'ranking_manager' => true,
                ]),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ],
        ]);
        for ($i = 0; $i < 10; $i++) {
            DB::table('users')->insert([
                [
                    'name' => 'player name ' . $i,
                    'surname' => 'player surname ' . $i,
                    'email' => 'player' . $i . '@ligaquiz.pt',
                    'password' => bcrypt('secret'),
                    'roles' => json_encode((object) [
                        'player' => true,
                    ]),
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                ],
            ]);
        }
    }
}
