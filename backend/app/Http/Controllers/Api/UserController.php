<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Koppla ett FPL entry ID till inloggad användare.
     *
     * Accepterar:
     *  - fpl_entry_id (föredragen)
     *  - entry_id (fallback, t.ex. om clienten redan skickar det)
     */
    public function linkFpl(Request $request)
    {
        $data = $request->validate([
            'fpl_entry_id' => ['nullable', 'integer', 'min:1'],
            'entry_id'     => ['nullable', 'integer', 'min:1'],
        ]);

        $entryId = $data['fpl_entry_id'] ?? $data['entry_id'] ?? null;

        if (! $entryId) {
            return response()->json([
                'message' => 'Missing fpl_entry_id or entry_id',
            ], 422);
        }

        $user = $request->user();
        $user->fpl_entry_id = $entryId;
        $user->save();

        return response()->json([
            'message'      => 'FPL entry ID linked.',
            'fpl_entry_id' => $user->fpl_entry_id,
            'user'         => $user,
        ]);
    }

    /**
     * Hög-nivå sammanfattning för onboarding / app state.
     */
    public function summary(Request $request)
    {
        $user = $request->user();

        // Har användaren verifierat sin email?
        $emailVerified = method_exists($user, 'hasVerifiedEmail')
            ? $user->hasVerifiedEmail()
            : ! is_null($user->email_verified_at);

        // Har de sparat ett FPL entry id?
        $hasFplEntryId = ! empty($user->fpl_entry_id);

        // Har de något lag synkat?
        $team = $user->teams()
            ->withCount('squadSlots')
            ->first();

        $hasTeam = $team !== null;

        return response()->json([
            'user' => $user, // respekterar $hidden på modellen (password etc.)

            'email_verified'   => $emailVerified,
            'has_fpl_entry_id' => $hasFplEntryId,
            'has_team'         => $hasTeam,

            'fpl_entry_id'     => $user->fpl_entry_id,

            'team' => $team ? [
                'id'                => $team->id,
                'name'              => $team->name,
                'squad_slots_count' => $team->squad_slots_count,
            ] : null,
        ]);
    }
}
