<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Quiz;
use App\Question;
use App\QuizQuestion;
use App\Answer;
use App\Media;
use Storage;
use Image;

class ImportQuizzes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:quizzes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import quizzes from old app';

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
        $startTime = microtime(true);
        $genreMap = [
            1 => 9,
            2 => 10,
            3 => 41,
            4 => 11,
            5 => 31,
            6 => 32,
            7 => 33,
            8 => 15,
            9 => 16,
            10 => 12,
            11 => 13,
            12 => 20,
            13 => 17,
            14 => 18,
            15 => 14,
            16 => 19,
            17 => 34,
            18 => 35,
            19 => 42,
            20 => 36,
            21 => 21,
            22 => 22,
            23 => 23,
            24 => 24,
            25 => 25,
            26 => 43,
            28 => 26,
            29 => 44,
            30 => 28,
            31 => 29,
            33 => 37,
            34 => 30,
            35 => 38,
            36 => 39,
            37 => null,
            38 => null,
            39 => 27,
            41 => 40,
            42 => 45,
            43 => 46,
            44 => 47
        ];
        $oldQuestions = DB::connection('mysql_old')
            ->table('questions')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('day')
            ->get();
        $oldAnswers = DB::connection('mysql_old')
            ->table('answers')
            ->where('submitted', 1)
            ->get()
            ->groupBy('question_id');
        Quiz::query()->truncate();
        Question::query()->truncate();
        QuizQuestion::query()->truncate();
        Answer::query()->truncate();
        $insertedQuizzes = [];
        foreach ($oldQuestions as $oldQuestion) {
            $year = $oldQuestion->year;
            $month = str_pad($oldQuestion->month, 2, '0', STR_PAD_LEFT);
            $day = str_pad($oldQuestion->day, 2, '0', STR_PAD_LEFT);
            if (isset($insertedQuizzes[$year.'-'.$month.'-'.$day])) {
                $quizId = $insertedQuizzes[$year.'-'.$month.'-'.$day];
            } else {
                $quiz = Quiz::create(['date' => $year.'-'.$month.'-'.$day]);
                $insertedQuizzes[$year.'-'.$month.'-'.$day] = $quiz->id;
                $quizId = $quiz->id;
            }
            // todo: get and store question media
            $question = Question::create([
                'content' => $oldQuestion->question,
                'answer' => $oldQuestion->answer,
                'media_id' => null,
                'genre_id' => $genreMap[$oldQuestion->subgenre_id],
            ]);
            QuizQuestion::create([
                'quiz_id' => $quizId,
                'question_id' => $question->id
            ]);
            if (isset($oldAnswers[$oldQuestion->id])) {
                $oldQuestionAnswers = $oldAnswers[$oldQuestion->id]
                    ->map(
                        function ($item) use ($question) {
                            return [
                                'question_id' => $question->id,
                                'user_id' => $item->user_id,
                                'text' => $item->answer,
                                'correct' => $item->correct,
                                'corrected' => $item->corrected,
                                'points' => $item->points,
                                'submitted' => 1
                            ];
                        }
                    )
                    ->toArray();
                Answer::insert($oldQuestionAnswers);
            }
            $this->line(
                '<fg=green>Imported:</> <fg=yellow>'.$question->id.'</> <fg=red>=></> '.$quiz->date
            );
        }
        $endTime = microtime(true);
        $timeDiff = $endTime - $startTime;
        $this->line('');
        $this->line(
            '<fg=green>Success:</> <fg=yellow>'
                .$oldQuestions->count()/8
                .' regular quizzes imported ('
                .abs(round($timeDiff*100))/100
                .'s)</>'
        );
        $this->line('');
    }
}
