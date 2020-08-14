<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Round;
use App\Game;
use App\User;
use App\Quiz;
use App\SpecialQuiz;
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
        $todayQuiz = Quiz::where('date', $today)->first();
        $todaySpecialQuiz = SpecialQuiz::where('date', $today)->first();
        if (!$todayQuiz && !$todaySpecialQuiz) {
            return null;
        }

        $users = User::all()->keyBy('id');

        if ($todaySpecialQuiz && $todaySpecialQuiz->user_id) {
            $todaySpecialQuiz->author =
                $users[$todaySpecialQuiz->user_id]->name
                .' '
                .$users[$todaySpecialQuiz->user_id]->surname;
        }

        $todayRound = Round::where('date', $today)->first();
        if ($todayRound) {
            $games = Game::where('round_id', $todayRound->id)->get();
            $opponents = (object) $games->reduce(function ($carry, $game) {
                if ($game->user_id_1 !== $game->user_id_2) {
                    $carry[$game->user_id_1] = $game->user_id_2;
                    $carry[$game->user_id_2] = $game->user_id_1;
                }
                return $carry;
            }, []);
        } else {
            $opponents = (object) [];
        }
        
        foreach ($users as $user) {
            if (
                (
                    $todayQuiz &&
                    $user->hasPermission('quiz_play') &&
                    $user->reminders['quiz']['daily']
                ) ||
                (
                    $todaySpecialQuiz &&
                    $user->hasPermission('specialquiz_play') &&
                    $user->reminders['special_quiz']['daily']
                )
            ) {
                $opponent = isset($opponents->{$user->id}) ? $users[$opponents->{$user->id}] : null;
                Mail::to($user->email)
                    ->locale(config('mail.default_locale'))
                    ->send(
                        new Reminder(
                            'daily',
                            $user,
                            $opponent,
                            $user->hasPermission('quiz_play') &&
                                $user->reminders['quiz']['daily'] ?
                                    $todayQuiz : null,
                            $user->hasPermission('specialquiz_play') &&
                                $user->reminders['special_quiz']['daily'] ?
                                    $todaySpecialQuiz : null
                        )
                    );
                usleep(1000000/config('mail.send_rate'));
            }
        }
    }
}
