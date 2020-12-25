<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Cache;
use App\Answer;
use App\QuizQuestion;
use App\SpecialQuizQuestion;
use App\Quiz;
use App\Round;
use App\League;
use App\SpecialQuiz;

class UpdateCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'content-cache:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Content Cache';

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
        $startTime = Carbon::now();
        $lastCacheRebuildTime = Cache::orderBy('updated_at', 'desc')
            ->first()
            ->updated_at;
        // TODO: Ignore questions created today
        $updatedQuestionsIds = Answer::where('updated_at', '>', $lastCacheRebuildTime)
            ->get()
            ->pluck('question_id')
            ->unique()
            ->toArray();
        if (count($updatedQuestionsIds)) {
            $quizIds = QuizQuestion::whereIn('question_id', $updatedQuestionsIds)
                ->get()
                ->pluck('quiz_id')
                ->unique()
                ->toArray();;
            if(count($quizIds)){
                $quizDates = Quiz::whereIn('id', $quizIds)
                    ->get()
                    ->pluck('date')
                    ->unique()
                    ->toArray();
                $seasonIds = Round::whereIn('date', $quizDates)
                    ->get()
                    ->pluck('season_id')
                    ->toArray();
                $leagues = League::whereIn('season_id', $seasonIds)->get();
                foreach ($leagues as $league) {
                    $league->getData(true, $startTime);
                }
            }
            $specialQuizzesIds = SpecialQuizQuestion::whereIn('question_id', $updatedQuestionsIds)
                ->get()
                ->pluck('special_quiz_id')
                ->unique()
                ->toArray();
            if(count($specialQuizzesIds)){
                $specialQuizzes = SpecialQuiz::whereIn('id', $specialQuizzesIds)->get();
                foreach ($specialQuizzes as $specialQuiz) {
                    $specialQuiz->getResult(true, $startTime);
                }
            }
        }
    }
}
