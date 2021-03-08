<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\User;

class UpdateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $users = User::all();
        foreach ($users as $user) {
            $user->reminders = [
                'quiz' => [
                    'daily' => true,
                    'deadline' => $user->reminders['quiz']['deadline'] ? 22 : false
                ],
                'special_quiz' => [
                    'daily' => true,
                    'deadline' => $user->reminders['special_quiz']['deadline'] ? 22 : false
                ]
            ];
            $user->timestamps = false;
            $user->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $users = User::all();
        foreach ($users as $user) {
            $user->reminders = [
                'quiz' => [
                    'daily' => true,
                    'deadline' => $user->reminders['quiz']['deadline'] ? true : false
                ],
                'special_quiz' => [
                    'daily' => true,
                    'deadline' => $user->reminders['special_quiz']['deadline'] ? true : false
                ]
            ];
            $user->timestamps = false;
            $user->save();
        }
    }
}
