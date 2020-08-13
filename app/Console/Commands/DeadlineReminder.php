<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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

    }
}
