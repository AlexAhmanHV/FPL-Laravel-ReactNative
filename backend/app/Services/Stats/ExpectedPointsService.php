<?php

// app/Services/Stats/ExpectedPointsService.php
namespace App\Services\Stats;

use App\Models\Player;
use App\Models\Gameweek;
use App\Models\Fixture;

class ExpectedPointsService
{
    /**
     * Huvudmetod: beräkna EP för en given spelare i en given GW.
     */
    public function forPlayerAndGameweek(Player $player, Gameweek $gw): float
    {
        // 1. Hämta senaste upp till 4 GWs innan denna GW
        $recentStats = $player->gameweekStats()
            ->whereHas('gameweek', function ($q) use ($gw) {
                $q->where('number', '<', $gw->number);
            })
            ->orderByDesc('gameweek_id')
            ->take(4)
            ->get();

        if ($recentStats->isEmpty()) {
            return 0.0;
        }

        // 2. Del-scorer
        $formScore         = $this->pointsPer90($recentStats);        // FPL-form, poäng/90
        $fixtureDifficulty = $this->fixtureDifficulty($player, $gw);  // 1 (lätt) - 5 (svår)
        $fixtureScore      = 6 - $fixtureDifficulty;                  // gör om: högre = bättre
        $xgScore           = $this->xgScore($recentStats);            // xG + xA per 90
        $minutesProb       = $this->minutesProbability($recentStats); // 0–1 baserat på minuter
        $flagsFactor       = $this->manualFlagsFactor($player);       // 0–1 injury/rotation

        // 3. Blanda ihop del-scorerna till en bas
        //    (justera vikterna efter smak senare)
        $base =
            $formScore    * 0.5 +  // form väger 50 %
            $fixtureScore * 0.2 +  // fixture väger 20 %
            $xgScore      * 0.3;   // xG/xA väger 30 %

        // 4. Justera för speltid och manuella flags
        $minutesFactor = $minutesProb * $flagsFactor;

        $ep = $base * $minutesFactor;

        return round(max($ep, 0), 2);
    }

    /**
     * Helper: beräkna EP för "den här veckan" (current/next GW).
     */
    public function forPlayerThisWeek(Player $player): float
    {
        $gw = Gameweek::where('is_current', true)->first()
            ?? Gameweek::where('is_next', true)->first()
            ?? Gameweek::orderBy('number')->latest('number')->first();

        if (! $gw) {
            return 0.0;
        }

        return $this->forPlayerAndGameweek($player, $gw);
    }

    /**
     * FPL-form: poäng per 90 min över senaste matcherna.
     */
    protected function pointsPer90($statsCollection): float
    {
        $mins   = $statsCollection->sum('minutes');
        $points = $statsCollection->sum('total_points');

        if ($mins === 0) {
            return 0.0;
        }

        return ($points / $mins) * 90;
    }

    /**
     * Fixture difficulty: 1–5, fallback 3 om vi inte hittar någon match.
     */
    protected function fixtureDifficulty(Player $player, Gameweek $gw): float
    {
        if (! $player->club_id) {
            return 3.0; // neutral
        }

        /** @var Fixture|null $fixture */
        $fixture = Fixture::where('gameweek_id', $gw->id)
            ->where(function ($q) use ($player) {
                $q->where('home_club_id', $player->club_id)
                  ->orWhere('away_club_id', $player->club_id);
            })
            ->first();

        if (! $fixture) {
            return 3.0;
        }

        // Om spelaren är i hemmalaget, ta home_difficulty, annars away_difficulty
        return $fixture->home_club_id == $player->club_id
            ? ($fixture->home_difficulty ?? 3)
            : ($fixture->away_difficulty ?? 3);
    }

    /**
     * Speltids-sannolikhet: snitt-minuter / 90, clampat 0–1.
     */
    protected function minutesProbability($statsCollection): float
    {
        $mins = $statsCollection->avg('minutes') ?? 0;

        return max(0.0, min($mins / 90, 1.0));
    }

    /**
     * xG/xA-score: enkel modell baserad på xG + xA per 90 min.
     * (används bara om du fyller xg/xa-kolumnerna i player_gameweek_stats)
     */
    protected function xgScore($statsCollection): float
    {
        $totalXg = $statsCollection->sum('xg');
        $totalXa = $statsCollection->sum('xa');
        $mins    = $statsCollection->sum('minutes');

        if ($mins === 0 || ($totalXg === null && $totalXa === null)) {
            return 0.0;
        }

        $xgPer90 = $mins > 0 ? ($totalXg / $mins) * 90 : 0;
        $xaPer90 = $mins > 0 ? ($totalXa / $mins) * 90 : 0;

        // xG + xA per 90 – du kan skala detta senare om det blir för stort
        return $xgPer90 + $xaPer90;
    }

    /**
     * Manuella flags: injury_risk och rotation_risk på spelaren (0–1).
     * Kräver relationen $player->flags (hasOne PlayerFlag) för att påverka.
     * Finns inte relationen → neutral effekt (1.0).
     */
    protected function manualFlagsFactor(Player $player): float
    {
        // Om modellen inte ens har relationen definierad → ingen påverkan.
        if (! method_exists($player, 'flags')) {
            return 1.0;
        }

        $flags = $player->flags ?? null;

        if (! $flags) {
            return 1.0;
        }

        $injuryRisk   = min(max($flags->injury_risk   ?? 0, 0), 1);
        $rotationRisk = min(max($flags->rotation_risk ?? 0, 0), 1);

        $injuryFactor   = 1 - $injuryRisk;
        $rotationFactor = 1 - $rotationRisk;

        // t.ex. injury 0.3 och rotation 0.2 → faktor = 0.7 * 0.8 = 0.56
        return $injuryFactor * $rotationFactor;
    }
}
