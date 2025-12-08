<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FplClient
{
    protected string $baseUrl = 'https://fantasy.premierleague.com/api';

    /**
     * Huvud-bootstrap: lag, spelare, events (gameweeks).
     */
    public function bootstrap(): array
    {
        $response = Http::get($this->baseUrl . '/bootstrap-static/');
        return $response->json();
    }

    /**
     * Info om ett FPL-lag (manager/entry).
     */
    public function entrySummary(int $entryId): array
    {
        $response = Http::get("{$this->baseUrl}/entry/{$entryId}/");
        return $response->json();
    }

    /**
     * Picks för ett lag i en given gameweek.
     */
    public function entryPicks(int $entryId, int $event): array
    {
        $response = Http::get("{$this->baseUrl}/entry/{$entryId}/event/{$event}/picks/");
        return $response->json();
    }

    /**
     * Alla fixtures. Optionellt filtrera på event (gameweek-nummer).
     */
    public function fixtures(?int $event = null): array
    {
        $url = $this->baseUrl . '/fixtures/';

        if ($event !== null) {
            $url .= '?event=' . $event;
        }

        $response = Http::get($url);

        return $response->json();
    }

    /**
     * Spelar-historik + future fixtures för ett element (FPL-player-id).
     */
    public function playerSummary(int $elementId): array
    {
        $response = Http::get("{$this->baseUrl}/element-summary/{$elementId}/");
        return $response->json();
    }
}
