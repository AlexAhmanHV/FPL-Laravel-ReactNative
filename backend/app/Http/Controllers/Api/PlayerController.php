<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Gameweek;
use App\Services\ExpectedPointsService;

class PlayerController extends Controller
{
    /**
     * Lista alla spelare.
     * (Senare kan du lägga till pagination/filters.)
     */
    public function index()
    {
        $players = Player::with('club')
            ->orderBy('web_name')
            ->get();

        return response()->json($players);
    }

    /**
     * Beräknade expected points för en specifik spelare i en given gameweek.
     *
     * Route-exempel:
     * GET /api/players/{player}/expected-points/{gameweek}
     */
    public function expectedPoints(
        Player $player,
        Gameweek $gameweek,
        ExpectedPointsService $expectedPointsService
    ) {
        $ep = $expectedPointsService->forPlayerAndGameweek($player, $gameweek);

        return response()->json([
            'player_id'       => $player->id,
            'gameweek_id'     => $gameweek->id,
            'expected_points' => $ep,
        ]);
    }
}
