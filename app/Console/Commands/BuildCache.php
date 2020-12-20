<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\League;
use App\SpecialQuiz;

class BuildCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'content-cache:build';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build Content Cache';

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
        $leagues = League::all();
        foreach ($leagues as $league) {
            $league->getData(true, $startTime);
            $this->info('Cache built for league: '.$league->id);
        }
        $specialQuizzes = SpecialQuiz::all();
        foreach ($specialQuizzes as $specialQuiz) {
            $specialQuiz->getResult(true, $startTime);
            $this->info('Cache built for special quiz: '.$specialQuiz->date);
        }
    }
}
