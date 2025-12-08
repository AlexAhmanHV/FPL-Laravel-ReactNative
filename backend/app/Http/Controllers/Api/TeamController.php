<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Player;
use App\Models\SquadSlot;
use App\Models\Team;
use App\Models\Gameweek;
use App\Services\FplSyncService;
use App\Services\Stats\ExpectedPointsService;

class TeamController extends Controller
{
    /**
     * Synka inloggad användares FPL-lag från FPL API via FplSyncService.
     */
    public function syncMyTeam(FplSyncService $syncService, Request $request)
    {
        $user = $request->user();

        if (! $user->fpl_entry_id) {
            return response()->json([
                'message' => 'User has no FPL entry ID linked.',
            ], 422);
        }

        $syncService->syncUserTeam($user);

        // Hämta senaste versionen av laget
        $team = $user->teams()
            ->with(['squadSlots.player.club'])
            ->first();

        return response()->json([
            'message' => 'Team synced from FPL.',
            'team'    => $team,
        ]);
    }

    /**
     * Dev-hjälp: skapa ett dummy-lag med alla spelare.
     */
    public function createDummyTeam(Request $request)
    {
        $user = $request->user();

        // Wipa gamla lag (dev)
        $user->teams()->delete();

        $team = Team::create([
            'user_id' => $user->id,
            'name'    => $user->name . "'s XI",
        ]);

        // Lägg in alla players som en "squad"
        $players = Player::all();

        foreach ($players as $index => $player) {
            SquadSlot::create([
                'team_id'     => $team->id,
                'player_id'   => $player->id,
                'position'    => $player->position,
                'is_starting' => true,
                'order'       => $index + 1,
            ]);
        }

        return response()->json([
            'message' => 'Dummy team created.',
            'team'    => $team->load('squadSlots.player.club'),
        ]);
    }

    /**
     * Mitt lag + expected points per spelare.
     *
     * Används av mobilen på /api/my-team
     */
    public function myTeam(Request $request, ExpectedPointsService $epService)
    {
        $user = $request->user();

        $team = $user->teams()
            ->with(['squadSlots.player.club'])
            ->first();

        if (! $team) {
            return response()->json([
                'message' => 'No team found for this user.',
            ], 404);
        }

        // Bestäm "aktuell" GW (current → next → senaste)
        $gw = Gameweek::where('is_current', true)->first()
            ?? Gameweek::where('is_next', true)->first()
            ?? Gameweek::orderBy('number')->latest('number')->first();

        // Bygg upp ett rent players-array
        $players = $team->squadSlots
            ->sortBy('order')
            ->map(function (SquadSlot $slot) use ($gw, $epService) {
                $player = $slot->player;
                $club   = $player->club;

                $ep = $gw
                    ? $epService->forPlayerAndGameweek($player, $gw)
                    : null;

                return [
                    'id'               => $player->id,
                    'web_name'         => $player->web_name,
                    'position'         => $player->position,
                    'price'            => $player->price,
                    'club_short_name'  => $club?->short_name,
                    'is_starting'      => (bool) $slot->is_starting,
                    'order'            => $slot->order,
                    'expected_points'  => $ep,
                ];
            })
            ->values();

        return response()->json([
            'team_id'      => $team->id,
            'name'         => $team->name,
            'fpl_entry_id' => $team->fpl_entry_id ?? $user->fpl_entry_id,
            'gameweek'     => $gw ? [
                'id'     => $gw->id,
                'number' => $gw->number,
                'name'   => $gw->name,
            ] : null,
            'players'      => $players,
        ]);
    }
}
