<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Season;
use App\League;
use App\Round;
use App\Game;

class ImportSeasons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:seasons';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import seasons from old app';

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
        Season::query()->truncate();
        League::query()->truncate();
        Round::query()->truncate();
        Game::query()->truncate();
        $oldSeasons = DB::connection('mysql_old')
            ->table('leagues')
            ->orderBy('date')
            ->orderBy('leaguename_id')
            ->get()
            ->groupBy('date', 'leaguename_id');
        $oldGames = DB::connection('mysql_old')
            ->table('games')
            ->orderBy('league_id')
            ->orderBy('round')
            ->get()
            ->groupBy('league_id', 'round');
        $season = 1;
        foreach ($oldSeasons as $oldSeason) {
            Season::create(['season' => $season]);
            $tier = 1;
            foreach ($oldSeason as $league) {
                League::create([
                    'season' => $season,
                    'tier' => $tier,
                    'user_ids' => json_decode($league->players),
                ]);
                // todo: import rounds and games
                $tier++;
            }
            $this->line(
                '<fg=green>Imported:</> <fg=yellow>'
                    .$season.'</> <fg=red>=></> '
                    .substr($oldSeason->first()->date, 0, 7)
            );
            $season++;
        }
        $elapsedTime = microtime(true) - $startTime;
        $this->line('');
        $this->line(
            '<fg=green>Success:</> <fg=yellow>'
                .$oldSeasons->count()
                .' seasons imported ('
                .abs(round($elapsedTime*100))/100
                .'s)</>'
        );
        $this->line('');
    }
}
