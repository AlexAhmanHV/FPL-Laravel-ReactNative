<?php

namespace App\Console\Commands;

use App\Services\FplSyncService;
use Illuminate\Console\Command;

class SyncFplFixtures extends Command
{
    protected $signature = 'fpl:sync-fixtures {--event=}';
    protected $description = 'Syncar fixtures från FPL API till fixtures-tabellen';

    public function handle(FplSyncService $syncService): int
    {
        $event = $this->option('event');

        if ($event !== null) {
            $this->info("Synkar fixtures för event (GW) {$event}...");
        } else {
            $this->info('Synkar ALLA fixtures...');
        }

        // Just nu ignorerar vi event-parametern i servicen, men vi kan lägga till stöd senare.
        $syncService->syncFixtures();

        $this->info('Klar med fixtures-sync.');

        return self::SUCCESS;
    }
}
