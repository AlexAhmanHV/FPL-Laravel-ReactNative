<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FplClient
{
    protected string $baseUrl = 'https://fantasy.premierleague.com/api';

    public function bootstrap(): array
    {
        // Main FPL metadata: players, teams, events, etc.
        $response = Http::get($this->baseUrl . '/bootstrap-static/');

        return $response->json();
    }

    public function entrySummary(int $entryId): array
    {
        // Summary info about a manager (team name, overall rank, etc.)
        $response = Http::get("{$this->baseUrl}/entry/{$entryId}/");

        return $response->json();
    }

    public function entryPicks(int $entryId, int $event): array
    {
        // Picks for a specific gameweek
        $response = Http::get("{$this->baseUrl}/entry/{$entryId}/event/{$event}/picks/");

        return $response->json();
    }
}
