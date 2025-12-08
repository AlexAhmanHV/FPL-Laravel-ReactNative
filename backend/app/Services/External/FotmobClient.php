<?php

// app/Services/External/FotmobClient.php
namespace App\Services\External;

use Illuminate\Support\Facades\Http;

class FotmobClient
{
    protected string $baseUrl = 'https://www.fotmob.com';

    public function playerMatches(int $fotmobPlayerId): array
    {
        // TODO: byt till exakt endpoint du sniffar fram
        // Exempel, du fyller rätt URL när du hittat den:
        $url = "{$this->baseUrl}/path/to/player/matches?playerId={$fotmobPlayerId}";

        return Http::get($url)->json();
    }

    public function matchDetails(int $matchId): array
    {
        // Om du behöver matchbaserat xG
        $url = "{$this->baseUrl}/path/to/match/details?matchId={$matchId}";

        return Http::get($url)->json();
    }
}
