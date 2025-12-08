<?php

// app/Console/Commands/SyncFotmobIds.php
namespace App\Console\Commands;

use App\Models\Player;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncFotmobIds extends Command
{
    protected $signature = 'fpl:sync-fotmob-ids';
    protected $description = 'Mappar FPL-spelare till FotMob IDs via FPL-ID-Map Master.csv';

    public function handle(): int
    {
        $url = 'https://raw.githubusercontent.com/ChrisMusson/FPL-ID-Map/refs/heads/main/Master.csv';

        $this->info("Hämtar Master.csv från {$url}...");

        $response = Http::get($url);

        if (! $response->ok()) {
            $this->error('Kunde inte hämta Master.csv');
            return self::FAILURE;
        }

        $csv = $response->body();

        // Splitta till rader
        $rows = preg_split('/\r\n|\r|\n/', trim($csv));

        if (count($rows) <= 1) {
            $this->error('Master.csv ser tom/konstig ut.');
            return self::FAILURE;
        }

        // Första raden är header → ta reda på kolumnindex
        $header = str_getcsv(array_shift($rows));

        // Försök hitta kolumner som innehåller "fpl" och "fotmob" i namnet (case-insensitive)
        $fplIndex    = $this->findHeaderIndex($header, 'fpl');
        $fotmobIndex = $this->findHeaderIndex($header, 'fotmob');

        if ($fplIndex === null || $fotmobIndex === null) {
            $this->error('Hittar inte någon header-kolumn som innehåller "fpl" och/eller "fotmob".');
            $this->info('Header var: ' . json_encode($header));
            return self::FAILURE;
        }

        $this->info("Använder FPL-kolumn index {$fplIndex} och FotMob-kolumn index {$fotmobIndex}.");

        $updated = 0;
        $skipped = 0;

        foreach ($rows as $line) {
            if (trim($line) === '') {
                continue;
            }

            $cols = str_getcsv($line);

            $fplId    = $cols[$fplIndex]    ?? null;
            $fotmobId = $cols[$fotmobIndex] ?? null;

            if (! $fplId || ! $fotmobId) {
                $skipped++;
                continue;
            }

            /** @var Player|null $player */
            $player = Player::where('fpl_player_id', (int) $fplId)->first();

            if (! $player) {
                $skipped++;
                continue;
            }

            $player->fotmob_id = (string) $fotmobId;
            $player->save();

            $updated++;
        }

        $this->info("Klart! Uppdaterade FotMob ID på {$updated} spelare, hoppade över {$skipped}.");

        return self::SUCCESS;
    }

    /**
     * Försök hitta index för den kolumn i headern som innehåller $needle (case-insensitive).
     */
    protected function findHeaderIndex(array $header, string $needle): ?int
    {
        $needle = strtolower($needle);

        foreach ($header as $i => $col) {
            $normalized = strtolower(trim($col));

            if (str_contains($normalized, $needle)) {
                return $i;
            }
        }

        return null;
    }
}
