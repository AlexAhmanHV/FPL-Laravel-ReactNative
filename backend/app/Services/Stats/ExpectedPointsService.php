<?php

// app/Services/Stats/ExpectedPointsService.php
namespace App\Services\Stats;

use App\Models\Player;
use App\Models\Gameweek;
use App\Models\Fixture;

class ExpectedPointsService
{
    public function forPlayerAndGameweek(Player $player, Gameweek $gw): float
    {
        // 1. Hämta senaste upp till 4 GWs innan denna GW (för form/xG etc)
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
        $trendFactor       = $this->trendFactor($player, $gw);        // ~0.9–1.1 "hot/cold"

        // 3. Blanda ihop del-scorerna till en bas
        $base =
            $formScore    * 0.5 +  // form väger 50 %
            $fixtureScore * 0.2 +  // fixture väger 20 %
            $xgScore      * 0.3;   // xG/xA väger 30 %

        // 4. Justera för speltid, manuella flags och trend
        $minutesFactor = $minutesProb * $flagsFactor;

        $ep = $base * $minutesFactor * $trendFactor;

        return round(max($ep, 0), 2);
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

        return $xgPer90 + $xaPer90;
    }

    /**
     * Manuella flags: injury_risk och rotation_risk på spelaren (0–1).
     * Kräver relationen $player->flags (hasOne PlayerFlag).
     */
    protected function manualFlagsFactor(Player $player): float
    {
        $flags = $player->flags ?? null;

        if (! $flags) {
            return 1.0;
        }

        $injuryRisk   = min(max($flags->injury_risk ?? 0, 0), 1);
        $rotationRisk = min(max($flags->rotation_risk ?? 0, 0), 1);

        $injuryFactor   = 1 - $injuryRisk;
        $rotationFactor = 1 - $rotationRisk;

        return $injuryFactor * $rotationFactor;
    }

    /**
     * Trendfaktor: jämför poäng/90 senaste 2 matcher vs senaste 6 matcher innan aktuell GW.
     * Returnerar faktor ~0.9 (kall) – 1.1 (het). Neutral runt 1.0.
     */
    protected function trendFactor(Player $player, Gameweek $gw): float
    {
        // senaste 6 matcher innan gw
        $last6 = $player->gameweekStats()
            ->whereHas('gameweek', function ($q) use ($gw) {
                $q->where('number', '<', $gw->number);
            })
            ->orderByDesc('gameweek_id')
            ->take(6)
            ->get();

        if ($last6->isEmpty()) {
            return 1.0;
        }

        $last2 = $last6->take(2);

        $p90_6 = $this->pointsPer90($last6);
        $p90_2 = $this->pointsPer90($last2);

        if ($p90_6 <= 0) {
            // om baseline är 0 (typ ingen form), låt trend vara neutral
            return 1.0;
        }

        // relativ skillnad: (senaste2 - senaste6) / senaste6
        $delta = ($p90_2 - $p90_6) / $p90_6; // kan vara negativ

        // skala skillnaden försiktigt, t.ex. max ±20% effekt
        $factor = 1.0 + ($delta * 0.2);

        // clamp mellan 0.9 och 1.1
        return max(0.9, min($factor, 1.1));
    }
}
