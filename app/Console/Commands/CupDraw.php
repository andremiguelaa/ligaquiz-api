<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Cup;
use App\CupGame;

class CupDraw extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cup:draw';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cup Draw';

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
     *
     * @return mixed
     */
    public function handle()
    {
        $lastCup = Cup::orderBy('season_id', 'desc')->first();
        if ($lastCup) {
            $rounds = $lastCup->getData();
            foreach ($rounds as $key => $round) {
                $games = $round->games;
                if (!$games->count()) {
                    $lastRoundGames = $rounds[$key - 1]->games;
                    $winners = [];
                    foreach ($rounds[$key - 1]->games as $game) {
                        if ($game->corrected) {
                            array_push($winners, $game->winner);
                        }
                    }
                    if (count($winners) === $lastRoundGames->count()) {
                        shuffle($winners);
                        for ($i=0; $i < count($winners)/2; $i++) {
                            $game = CupGame::create([
                                'cup_round_id' => $round->id,
                                'user_id_1' => $winners[$i*2],
                                'user_id_2' => $winners[$i*2+1]
                            ]);
                        }
                    }
                    break;
                }
            }
        }
    }
}
