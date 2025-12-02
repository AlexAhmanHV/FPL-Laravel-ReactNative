<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\SquadSlot;
use App\Models\Team;
use Illuminate\Http\Request;
use App\Services\FplSyncService;


class TeamController extends Controller
{
public function syncMyTeam(FplSyncService $syncService, Request $request)
{
    $user = $request->user();

    $syncService->syncUserTeam($user);

    $team = $user->teams()
        ->with(['squadSlots.player.club'])
        ->first();

    return response()->json([
        'message' => 'Team synced from FPL.',
        'team'    => $team,
    ]);
}


    public function createDummyTeam(Request $request)
    {
        $user = $request->user();

        // Wipe old teams for dev
        $user->teams()->delete();

        $team = Team::create([
            'user_id' => $user->id,
            'name'    => $user->name . "'s XI",
        ]);

        // Just grab all players we have and add them as the squad
        $players = Player::all();

        foreach ($players as $index => $player) {
            SquadSlot::create([
                'team_id'    => $team->id,
                'player_id'  => $player->id,
                'position'   => $player->position,
                'is_starting'=> true,
                'order'      => $index + 1,
            ]);
        }

        return response()->json([
            'message' => 'Dummy team created.',
            'team'    => $team->load('squadSlots.player.club'),
        ]);
    }

    public function myTeam(Request $request)
    {
        $user = $request->user();

        $team = $user->teams()
            ->with(['squadSlots.player.club'])
            ->first();

        return response()->json($team);
    }
}
