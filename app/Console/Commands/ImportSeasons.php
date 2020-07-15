<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
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
            ->groupBy('league_id');
        $season = 1;
        foreach ($oldSeasons as $oldSeason) {
            $month = substr($oldSeason->first()->date, 0, 7);
            Season::create(['season' => $season]);

            $rounds = [];
            $offset = 0;
            for ($round = 1; $round <= 20; $round++) {
                $roundDate = Carbon::createFromFormat(
                    'Y-m-d',
                    $month.'-'.date('j', strtotime('first monday of ' . $month))
                )->addDays($offset)->format('Y-m-d');
                array_push($rounds, [
                    'season' => $season,
                    'round' => $round,
                    'date' => $roundDate,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
                if ($round % 5 === 0) {
                    $offset += 3;
                } else {
                    $offset++;
                }
            }
            Round::insert($rounds);

            $tier = 1;
            foreach ($oldSeason as $oldLeague) {
                League::create([
                    'season' => $season,
                    'tier' => $tier,
                    'user_ids' => json_decode($oldLeague->players),
                ]);
                $oldLeagueGames = []; 
                foreach ($oldGames[$oldLeague->id] as $oldGame) {
                    array_push($oldLeagueGames, [
                        'season' => $season,
                        'round' => $oldGame->round,
                        'user_id_1' => $oldGame->user_1_id,
                        'user_id_2' => $oldGame->user_2_id,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);
                }
                Game::insert($oldLeagueGames);
                $tier++;
            }

            $this->line('<fg=green>Imported:</> <fg=yellow>'.$season.'</> <fg=red>=></> '.$month);
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
