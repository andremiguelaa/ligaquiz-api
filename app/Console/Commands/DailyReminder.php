<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Round;
use App\Game;
use App\User;
use App\Mail\Reminder;

class DailyReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a mail reminder to all users with a new game to play';

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
        $today = Carbon::now()->format('Y-m-d');
        $todayRound = Round::where('date', $today)->first();
        if(!$todayRound){
            return;
        }
        $games = Game::where('round_id', $todayRound->id)->get();
        $users = User::all()->keyBy('id');;
        foreach ($games as $game) {
            $user1 = $users[$game->user_id_1];
            $user2 = $users[$game->user_id_2];
            if($game->user_id_1 !== $game->user_id_2) {
                Mail::to($user1->email)
                    ->locale(config('mail.default_locale'))
                    ->send(new Reminder($user1, $user2, null, null));
                usleep(1000000/config('mail.send_rate'));
                Mail::to($user2->email)
                    ->locale(config('mail.default_locale'))
                    ->send(new Reminder($user2, $user1, null, null));
            }
            else {
                Mail::to($user1->email)
                    ->locale(config('mail.default_locale'))
                    ->send(new Reminder($user1, null, null, null));
            }
            usleep(1000000/config('mail.send_rate'));
        }
    }
}
