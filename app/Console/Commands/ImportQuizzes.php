<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Quiz;
use App\Question;
use App\QuizQuestion;
use App\Answer;
use App\Media;
use App\SpecialQuiz;
use App\SpecialQuizQuestion;
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
        $mimeTypeMap = [
            'mp4' => 'video',
            'mp3' => 'audio',
            'jpg' => 'image',
            'jpeg' => 'image',
            'gif' => 'image',
            'png' => 'image'
        ];
        Question::query()->truncate();
        Answer::query()->truncate();
        Media::query()->truncate();
        Quiz::query()->truncate();
        QuizQuestion::query()->truncate();
        SpecialQuiz::query()->truncate();
        SpecialQuizQuestion::query()->truncate();
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
        $insertedQuizzes = [];
        foreach ($oldQuestions as $oldQuestion) {
            $year = $oldQuestion->year;
            $month = str_pad($oldQuestion->month, 2, '0', STR_PAD_LEFT);
            $day = str_pad($oldQuestion->day, 2, '0', STR_PAD_LEFT);
            $date = $year.'-'.$month.'-'.$day;
            if (isset($insertedQuizzes[$date])) {
                $quizId = $insertedQuizzes[$date];
            } else {
                $quiz = Quiz::create(['date' => $date]);
                $insertedQuizzes[$date] = $quiz->id;
                $quizId = $quiz->id;
            }
            if ($oldQuestion->filename) {
                $url = 'https://ligaquiz.pt/files/'.$oldQuestion->filename;
                $file = file_get_contents($url);
                $extension = strtolower(pathinfo($oldQuestion->filename, PATHINFO_EXTENSION));
                $filename = 'media/'.pathinfo($oldQuestion->filename, PATHINFO_FILENAME)
                    .'_'.round(microtime(true) * 1000)
                    .'.'.$extension;
                $mimeType = $mimeTypeMap[$extension];
                $storedFile = Storage::put($filename, $file);
                $media = Media::create([
                    'filename' => $filename,
                    'type' => $mimeType
                ]);
            }
            $genreId = isset($genreMap[$oldQuestion->subgenre_id]) ?
                $genreMap[$oldQuestion->subgenre_id] : null;
            $question = Question::create([
                'content' => $oldQuestion->question,
                'answer' => $oldQuestion->answer,
                'media_id' => $oldQuestion->filename ? $media->id : null,
                'genre_id' => $genreId,
            ]);
            QuizQuestion::create([
                'quiz_id' => $quizId,
                'question_id' => $question->id
            ]);
            if (isset($oldAnswers[$oldQuestion->id])) {
                $oldQuestionAnswers = $oldAnswers[$oldQuestion->id]
                    ->map(
                        function ($item) use ($question, $date) {
                            return [
                                'question_id' => $question->id,
                                'user_id' => $item->user_id,
                                'text' => $item->answer,
                                'correct' => $item->correct,
                                'corrected' => $item->corrected,
                                'points' => $item->points,
                                'submitted' => 1,
                                'created_at' => Carbon::parse($date)->midDay(),
                                'updated_at' => Carbon::parse($date)->midDay()
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
        $oldSpecialQuizzes = DB::connection('mysql_old')
            ->table('specialquizzes')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('day')
            ->get();
        $oldSpecialQuestions = DB::connection('mysql_old')
            ->table('specialquestions')
            ->get()
            ->groupBy('quiz_id');
        $oldSpecialAnswers = DB::connection('mysql_old')
            ->table('specialanswers')
            ->where('submitted', 1)
            ->get()
            ->groupBy('question_id');
        foreach ($oldSpecialQuizzes as $oldSpecialQuiz) {
            $year = $oldSpecialQuiz->year;
            $month = str_pad($oldSpecialQuiz->month, 2, '0', STR_PAD_LEFT);
            $day = str_pad($oldSpecialQuiz->day, 2, '0', STR_PAD_LEFT);
            $date = $year.'-'.$month.'-'.$day;
            $specialQuiz = SpecialQuiz::create([
                'subject' => $oldSpecialQuiz->subject,
                'description' => $oldSpecialQuiz->description,
                'user_id' => $oldSpecialQuiz->user_id,
                'date' => $date
            ]);
            foreach ($oldSpecialQuestions[$oldSpecialQuiz->id] as $oldSpecialQuestion) {
                if ($oldSpecialQuestion->filename) {
                    $url = 'https://ligaquiz.pt/files/'.$oldSpecialQuestion->filename;
                    $file = file_get_contents($url);
                    $extension = strtolower(
                        pathinfo($oldSpecialQuestion->filename, PATHINFO_EXTENSION)
                    );
                    $filename = 'media/'.pathinfo($oldSpecialQuestion->filename, PATHINFO_FILENAME)
                        .'_'.round(microtime(true) * 1000)
                        .'.'.$extension;
                    $mimeType = $mimeTypeMap[$extension];
                    $storedFile = Storage::put($filename, $file);
                    $media = Media::create([
                        'filename' => $filename,
                        'type' => $mimeType
                    ]);
                }
                $question = Question::create([
                    'content' => $oldSpecialQuestion->question,
                    'answer' => $oldSpecialQuestion->answer,
                    'media_id' => $oldSpecialQuestion->filename ? $media->id : null,
                    'genre_id' => null,
                ]);
                SpecialQuizQuestion::create([
                    'special_quiz_id' => $specialQuiz->id,
                    'question_id' => $question->id
                ]);
                if (isset($oldSpecialAnswers[$oldSpecialQuestion->id])) {
                    $oldSpecialQuestionAnswers = $oldSpecialAnswers[$oldSpecialQuestion->id]
                        ->map(
                            function ($item) use ($question, $date) {
                                return [
                                    'question_id' => $question->id,
                                    'user_id' => $item->user_id,
                                    'text' => $item->answer,
                                    'correct' => $item->correct,
                                    'corrected' => $item->corrected,
                                    'points' => $item->banker,
                                    'submitted' => 1,
                                    'created_at' => Carbon::parse($date)->midDay(),
                                    'updated_at' => Carbon::parse($date)->midDay()
                                ];
                            }
                        )
                        ->toArray();
                    Answer::insert($oldSpecialQuestionAnswers);
                }
                $this->line(
                    '<fg=green>Imported:</> <fg=yellow>'
                        .$question->id.'</> <fg=red>=></> '
                        .$specialQuiz->date
                );
            }
        }
        $elapsedTime = microtime(true) - $startTime;
        $this->line('');
        $this->line(
            '<fg=green>Success:</> <fg=yellow>'
                .$oldQuestions->count()/8
                .' regular quizzes and '
                .$oldSpecialQuizzes->count()
                .' special quizzes imported ('
                .abs(round($elapsedTime*100))/100
                .'s)</>'
        );
        $this->line('');
    }
}
