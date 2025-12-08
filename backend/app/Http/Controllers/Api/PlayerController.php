<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Gameweek;
use App\Services\Stats\ExpectedPointsService;
use App\Services\Stats\TransferScoreService;
use Illuminate\Http\Request;

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

    /**
     * Transfer targets: sorterar spelare på "transfer_score".
     *
     * Exempel:
     *  GET /api/players/transfer-targets?gw=3&horizon=3&position=MID&max_price=8.0&limit=50
     */
    public function transferTargets(Request $request, TransferScoreService $transferService)
    {
        $gwNumber = $request->integer('gw');
        $horizon  = $request->integer('horizon', 3);
        $position = $request->query('position');      // "GKP","DEF","MID","FWD"
        $maxPrice = $request->query('max_price');     // t.ex. 8.0
        $limit    = $request->integer('limit', 50);

        // 1. Bestäm fromGw
        if ($gwNumber) {
            $fromGw = Gameweek::where('number', $gwNumber)->first();
        } else {
            $fromGw = Gameweek::where('is_current', true)->first()
                ?? Gameweek::where('is_next', true)->first()
                ?? Gameweek::orderBy('number')->latest('number')->first();
        }

        if (! $fromGw) {
            return response()->json(['message' => 'No gameweek found'], 404);
        }

        // 2. Hämta kandidater (aktiva spelare med historik)
        $query = Player::query()
            ->where('is_active', true)
            ->whereHas('gameweekStats', function ($q) use ($fromGw) {
                $q->whereHas('gameweek', function ($q2) use ($fromGw) {
                    $q2->where('number', '<', $fromGw->number);
                })->where('minutes', '>', 0);
            })
            ->with('club');

        if ($position) {
            $query->where('position', $position);
        }

        if ($maxPrice !== null) {
            $query->where('price', '<=', (float) $maxPrice);
        }

        $players = $query->get();

        // 3. Beräkna transfer-score per spelare
        $data = $players->map(function (Player $p) use ($fromGw, $horizon, $transferService) {
                $scores = $transferService->forPlayer($p, $fromGw, $horizon);

                return [
                    'id'        => $p->id,
                    'web_name'  => $p->web_name,
                    'position'  => $p->position,
                    'price'     => $p->price,
                    'club'      => optional($p->club)->short_name,
                    // EP-komponenter:
                    'ep_next'           => $scores['ep_next'],
                    'ep_horizon_total'  => $scores['ep_horizon_total'],
                    'ep_horizon_avg'    => $scores['ep_horizon_avg'],
                    // meta:
                    'fixture_run_score' => $scores['fixture_run_score'],
                    'value_score'       => $scores['value_score'],
                    'minutes_stability' => $scores['minutes_stability'],
                    'consistency'       => $scores['consistency'],
                    'upside'            => $scores['upside'],
                    // slutscore:
                    'transfer_score'    => $scores['transfer_score'],
                ];
            })
            ->sortByDesc('transfer_score')
            ->values()
            ->take($limit);

        return response()->json([
            'from_gameweek' => [
                'id'     => $fromGw->id,
                'number' => $fromGw->number,
                'name'   => $fromGw->name,
            ],
            'horizon' => $horizon,
            'players' => $data,
        ]);
    }
}
