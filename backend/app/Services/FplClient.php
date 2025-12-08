<?php

// app/Services/FplClient.php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FplClient
{
    protected string $baseUrl = 'https://fantasy.premierleague.com/api';

    public function bootstrap(): array
    {
        $response = Http::get($this->baseUrl . '/bootstrap-static/');
        return $response->json();
    }

    public function entrySummary(int $entryId): array
    {
        $response = Http::get("{$this->baseUrl}/entry/{$entryId}/");
        return $response->json();
    }

    public function entryPicks(int $entryId, int $event): array
    {
        $response = Http::get("{$this->baseUrl}/entry/{$entryId}/event/{$event}/picks/");
        return $response->json();
    }

    // 🔽 NYTT: fixtures
    public function fixtures(): array
    {
        $response = Http::get("{$this->baseUrl}/fixtures/");
        return $response->json();
    }

    // 🔽 NYTT: spelarsummering (historik / future fixtures)
    public function playerSummary(int $elementId): array
    {
        $response = Http::get("{$this->baseUrl}/element-summary/{$elementId}/");
        return $response->json();
    }
}

