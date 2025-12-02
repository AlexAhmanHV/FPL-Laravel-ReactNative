<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;

class PlayerController extends Controller
{
    public function index()
    {
        // Later we can add pagination + filters.
        $players = Player::with('club')
            ->orderBy('web_name')
            ->get();

        return response()->json($players);
    }
}
