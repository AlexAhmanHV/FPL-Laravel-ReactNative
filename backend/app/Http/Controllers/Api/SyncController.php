<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FplSyncService;

class SyncController extends Controller
{   
    /**
     * Synca grunddatan från FPL bootstrap-static:
     * - clubs/teams
     * - players
     * - gameweeks/events
     */
    public function syncBootstrap(FplSyncService $syncService)
    {
        $syncService->syncBootstrap();

        return response()->json([
            'message' => 'FPL bootstrap data synced',
        ]);
    }

    /**
     * Synca fixtures (matcher) från FPL:
     * - kopplar mot dina Club + Gameweek modeller
     */
    public function syncFixtures(FplSyncService $syncService)
    {
        $syncService->syncFixtures();

        return response()->json([
            'message' => 'FPL fixtures synced',
        ]);
    }

    /**
     * Synca spelarnas gameweek-historik:
     * - fyller/uppdaterar player_gameweek_stats
     */
    public function syncPlayerHistory(FplSyncService $syncService)
    {
        $syncService->syncPlayerHistory();

        return response()->json([
            'message' => 'FPL player history synced',
        ]);
    }
}
