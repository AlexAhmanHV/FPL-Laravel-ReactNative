<?php

namespace App\Console\Commands;

use App\Models\Gameweek;
use App\Models\Player;
use App\Models\PlayerGameweekStat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportPlayerGameweekStats extends Command
{
    /**
     * Exempel:
     *  php artisan fpl:import-gw-stats 2025-2026 2
     *  php artisan fpl:import-gw-stats 2025-2026 2 --tournament="Premier League"
     */
    protected $signature = 'fpl:import-gw-stats 
                            {season : T.ex. 2025-2026} 
                            {gw : Gameweek-nummer, t.ex. 2} 
                            {--tournament=Premier League : Turneringens namn i FPL-Elo-Insights (mappnamn)}';

    protected $description = 'Importerar player_gameweek_stats.csv från FPL-Elo-Insights in i player_gameweek_stats-tabellen';

    public function handle(): int
    {
        $season    = $this->argument('season');
        $gwNumber  = (int) $this->argument('gw');
        $tournament = $this->option('tournament');

        // Hitta motsvarande Gameweek i din DB
        $gw = Gameweek::where('number', $gwNumber)->first();

        if (! $gw) {
            $this->error("Hittar ingen Gameweek med number = {$gwNumber} i databasen.");
            return self::FAILURE;
        }

        $encodedTournament = rawurlencode($tournament);
        $url = "https://raw.githubusercontent.com/olbauday/FPL-Elo-Insights/main/data/{$season}/By%20Tournament/{$encodedTournament}/GW{$gwNumber}/player_gameweek_stats.csv";

        $this->info("Hämtar player_gameweek_stats.csv från:");
        $this->line($url);

        $response = Http::get($url);

        if (! $response->ok()) {
            $this->error("Kunde inte hämta CSV (HTTP {$response->status()}).");
            return self::FAILURE;
        }

        $csv = $response->body();

        $rows = preg_split('/\r\n|\r|\n/', trim($csv));

        if (count($rows) <= 1) {
            $this->error('CSV:n verkar tom/konstig.');
            return self::FAILURE;
        }

        // Header-rad
        $header = str_getcsv(array_shift($rows));

        // Hjälpfunktion: hitta index för en exakt kolumn, fallback: innehåller substring
        $idx = function (string $name) use ($header): ?int {
            $nameLower = strtolower($name);

            // exakt match först
            foreach ($header as $i => $col) {
                if (strtolower(trim($col)) === $nameLower) {
                    return $i;
                }
            }

            // fallback: innehåller substring
            foreach ($header as $i => $col) {
                if (str_contains(strtolower(trim($col)), $nameLower)) {
                    return $i;
                }
            }

            return null;
        };

        // Nyckelkolumner vi bryr oss om
        $idIndex          = $idx('id');             // FPL player id
        $minutesIndex     = $idx('minutes');
        $eventPointsIndex = $idx('event_points') ?? $idx('total_points');
        $goalsIndex       = $idx('goals_scored');
        $assistsIndex     = $idx('assists');
        $csIndex          = $idx('clean_sheets');
        $gcIndex          = $idx('goals_conceded');
        $bonusIndex       = $idx('bonus');

        // FPL 24/25+ använder expected_goals/expected_assists etc
        $xgIndex          = $idx('expected_goals');
        $xaIndex          = $idx('expected_assists');

        if ($idIndex === null) {
            $this->error('Hittar ingen kolumn för "id" (FPL player id) i headern.');
            $this->info('Header: ' . json_encode($header));
            return self::FAILURE;
        }

        $this->info('Header tolkad så här:');
        $this->line('  id: ' . $idIndex);
        $this->line('  minutes: ' . var_export($minutesIndex, true));
        $this->line('  event_points/total_points: ' . var_export($eventPointsIndex, true));
        $this->line('  goals_scored: ' . var_export($goalsIndex, true));
        $this->line('  assists: ' . var_export($assistsIndex, true));
        $this->line('  clean_sheets: ' . var_export($csIndex, true));
        $this->line('  goals_conceded: ' . var_export($gcIndex, true));
        $this->line('  bonus: ' . var_export($bonusIndex, true));
        $this->line('  expected_goals (xg): ' . var_export($xgIndex, true));
        $this->line('  expected_assists (xa): ' . var_export($xaIndex, true));

        $updated = 0;
        $skipped = 0;

        foreach ($rows as $line) {
            if (trim($line) === '') {
                continue;
            }

            $cols = str_getcsv($line);

            $fplId = $cols[$idIndex] ?? null;
            if (! $fplId) {
                $skipped++;
                continue;
            }

            /** @var Player|null $player */
            $player = Player::where('fpl_player_id', (int) $fplId)->first();

            if (! $player) {
                $skipped++;
                continue;
            }

            // Plocka värden, med fallback 0/null
            $minutes      = $minutesIndex     !== null ? (int) ($cols[$minutesIndex]     ?? 0) : 0;
            $totalPoints  = $eventPointsIndex !== null ? (int) ($cols[$eventPointsIndex] ?? 0) : 0;
            $goals        = $goalsIndex       !== null ? (int) ($cols[$goalsIndex]       ?? 0) : 0;
            $assists      = $assistsIndex     !== null ? (int) ($cols[$assistsIndex]     ?? 0) : 0;
            $cleanSheets  = $csIndex          !== null ? (int) ($cols[$csIndex]          ?? 0) : 0;
            $goalsConceded= $gcIndex          !== null ? (int) ($cols[$gcIndex]          ?? 0) : 0;
            $bonus        = $bonusIndex       !== null ? (int) ($cols[$bonusIndex]       ?? 0) : 0;
            $xg           = $xgIndex          !== null ? (float)($cols[$xgIndex]         ?? 0) : null;
            $xa           = $xaIndex          !== null ? (float)($cols[$xaIndex]         ?? 0) : null;

            PlayerGameweekStat::updateOrCreate(
                [
                    'player_id'   => $player->id,
                    'gameweek_id' => $gw->id,
                ],
                [
                    'minutes'        => $minutes,
                    'total_points'   => $totalPoints,
                    'goals_scored'   => $goals,
                    'assists'        => $assists,
                    'clean_sheets'   => $cleanSheets,
                    'goals_conceded' => $goalsConceded,
                    'bonus'          => $bonus,
                    'xg'             => $xg,
                    'xa'             => $xa,
                ]
            );

            $updated++;
        }

        $this->info("Klart! Uppdaterade/skapade stats för {$updated} player_gameweek_stats-rader. Skippade {$skipped}.");

        return self::SUCCESS;
    }
}
