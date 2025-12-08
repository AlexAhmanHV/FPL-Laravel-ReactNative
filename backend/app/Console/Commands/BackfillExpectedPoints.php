<?php

namespace App\Console\Commands;

use App\Models\PlayerGameweekStat;
use App\Models\Gameweek;
use App\Services\Stats\ExpectedPointsService;
use Illuminate\Console\Command;

class BackfillExpectedPoints extends Command
{
    protected $signature = 'fpl:backfill-ep {gw : Gameweek-nummer att räkna EP för}';
    protected $description = 'Beräknar expected points för alla spelare i en given GW och sparar i player_gameweek_stats.expected_points';

    public function handle(ExpectedPointsService $epService): int
    {
        $gwNumber = (int) $this->argument('gw');
        $gw = Gameweek::where('number', $gwNumber)->first();

        if (! $gw) {
            $this->error("Hittar ingen gameweek med number = {$gwNumber}");
            return self::FAILURE;
        }

        $this->info("Beräknar EP för GW {$gwNumber}...");

        // Hämta alla stats-rader för den GW:n
        $stats = PlayerGameweekStat::where('gameweek_id', $gw->id)
            ->with('player')
            ->get();

        $count = 0;

        foreach ($stats as $row) {
            $player = $row->player;
            if (! $player) {
                continue;
            }

            $ep = $epService->forPlayerAndGameweek($player, $gw);

            $row->expected_points = $ep;
            $row->save();

            $count++;
        }

        $this->info("Uppdaterade expected_points för {$count} rader i GW {$gwNumber}.");

        return self::SUCCESS;
    }
}
