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
use App\Answer;
use App\Mail\Reminder;

class DeadlineReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:deadline';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a mail reminder to all users with a new game to play 2 hours before deadline';

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
        if ($todayQuiz) {
            $todayQuizQuestionIds = $todayQuiz->questions()->get()->pluck('question_id')->toArray();
        }
        if ($todaySpecialQuiz) {
            $todaySpecialQuizQuestionIds = $todaySpecialQuiz->questions()->get()->pluck('question_id')->toArray();
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
            $currentHour = intval(Carbon::now()->format('H'));
            $quizDeadline = $user->reminders['quiz']['deadline'] === true ?
                22 : $user->reminders['quiz']['deadline'];
            $specialQuizDeadline = $user->reminders['special_quiz']['deadline'] === true ?
                22 : $user->reminders['special_quiz']['deadline'];

            $sendQuizDeadlineReminder = $quizDeadline && intval($quizDeadline) === $currentHour;
            $sendSpecialQuizDeadlineReminder = $specialQuizDeadline &&
                intval($specialQuizDeadline) === $currentHour;

            if (
                (
                    $todayQuiz &&
                    $user->hasPermission('quiz_play') &&
                    $sendQuizDeadlineReminder
                ) ||
                (
                    $todaySpecialQuiz &&
                    $user->hasPermission('specialquiz_play') &&
                    $sendSpecialQuizDeadlineReminder
                )
            ) {
                $opponent = isset($opponents->{$user->id}) ? $users[$opponents->{$user->id}] : null;

                $todayQuizSubmitted = $todayQuiz ? boolval(
                    Answer::whereIn('question_id', $todayQuizQuestionIds)
                        ->where('user_id', $user->id)
                        ->where('submitted', 1)
                        ->first()
                ) : true;

                $todaySpecialQuizSubmitted = $todaySpecialQuiz ? boolval(
                    Answer::whereIn('question_id', $todaySpecialQuizQuestionIds)
                        ->where('user_id', $user->id)
                        ->where('submitted', 1)
                        ->first()
                ) : true;

                if (
                    (!$todayQuizSubmitted && $sendQuizDeadlineReminder) ||
                    (!$todaySpecialQuizSubmitted && $sendSpecialQuizDeadlineReminder)
                ) {
                    Mail::to($user->email)
                        ->locale(config('mail.default_locale'))
                        ->send(
                            new Reminder(
                                'deadline',
                                $user,
                                $opponent,
                                $user->hasPermission('quiz_play') && $sendQuizDeadlineReminder &&
                                    !$todayQuizSubmitted ? $todayQuiz : null,
                                $user->hasPermission('specialquiz_play') &&
                                    $sendSpecialQuizDeadlineReminder &&
                                    !$todaySpecialQuizSubmitted ? $todaySpecialQuiz : null
                            )
                        );
                    usleep(1000000/config('mail.send_rate'));
                }
            }
        }
    }
}
