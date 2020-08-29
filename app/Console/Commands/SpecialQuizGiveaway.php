<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\User;

class SpecialQuizGiveaway extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'giveaway:specialquiz';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Special Quiz Giveaway';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $users = User::all();
        foreach ($users as $key => $user) {
            if(!$user->getRoles()){
                $roles = $user->roles;
                $roles['special_quiz_player'] = '2020-09-05';
                $user->roles = $roles;
                $user->save();
            }
        }
    }
}
