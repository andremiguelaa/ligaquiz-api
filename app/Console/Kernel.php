<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\DailyReminder::class,
        Commands\DeadlineReminder::class,
        Commands\CleanMedia::class,
        Commands\UpdateCache::class,
        Commands\SpecialQuizGiveaway::class,
        Commands\CupDraw::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('reminder:daily')->dailyAt('00:00');
        $schedule->command('reminder:deadline')->hourly();
        // $schedule->command('clean:media')->dailyAt('04:00');
        $schedule->command('clean:logs')->dailyAt('05:00');
        $schedule->command('content-cache:update')->everyFiveMinutes();
        $schedule->command('cup:draw')->everySixHours();;
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
