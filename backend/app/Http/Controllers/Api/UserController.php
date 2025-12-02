<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function linkFpl(Request $request)
    {
        $data = $request->validate([
            'entry_id' => 'required|integer',
        ]);

        $user = $request->user();
        $user->fpl_entry_id = $data['entry_id'];
        $user->save();

        return response()->json([
            'message' => 'FPL entry ID linked.',
            'user'    => $user,
        ]);
    }

    public function summary(Request $request)
    {
        $user = $request->user();

        // Has user verified their email?
        $emailVerified = method_exists($user, 'hasVerifiedEmail')
            ? $user->hasVerifiedEmail()
            : ! is_null($user->email_verified_at);

        // Have they saved an FPL entry id?
        $hasFplEntryId = ! empty($user->fpl_entry_id);

        // Do they have a team synced?
        $team = $user->teams()
            ->withCount('squadSlots')
            ->first();

        $hasTeam = $team !== null;

        return response()->json([
            'user' => $user, // will respect $hidden on the model (password etc.)

            'email_verified'   => $emailVerified,
            'has_fpl_entry_id' => $hasFplEntryId,
            'has_team'         => $hasTeam,

            'team' => $team ? [
                'id'                => $team->id,
                'name'              => $team->name,
                'squad_slots_count' => $team->squad_slots_count,
            ] : null,
        ]);
    }
}
