<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Player;
use App\Models\Gameweek;
use Illuminate\Support\Facades\DB;
use App\Services\FplSyncService;


class SyncController extends Controller
{   
    public function syncBootstrap(FplSyncService $syncService)
{
    $syncService->syncBootstrap();

    return response()->json(['message' => 'FPL bootstrap data synced']);
}

}
