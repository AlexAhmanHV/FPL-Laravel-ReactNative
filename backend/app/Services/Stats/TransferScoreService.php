<?php

namespace App\Services\Stats;

use App\Models\Player;
use App\Models\Gameweek;
use App\Models\Fixture;

class TransferScoreService
{
    public function __construct(
        protected ExpectedPointsService $epService
    ) {}

    /**
     * Beräkna ett "transfer score" för en spelare, med blick över flera GWs framåt.
     *
     * Returnerar en array med:
     *  - ep_next
     *  - ep_horizon_total
     *  - ep_horizon_avg
     *  - fixture_run_score
     *  - value_score (EP / pris)
     *  - minutes_stability (andel 60+ min)
     *  - consistency (andel matcher med ≥5p)
     *  - upside (max-poäng senaste matcher, skalat 0–1)
     *  - transfer_score (sammanvägd)
     */
    public function forPlayer(
        Player $player,
        ?Gameweek $fromGw = null,
        int $horizon = 3
    ): array {
        // 1. Bestäm start-GW
        if (! $fromGw) {
            $fromGw = Gameweek::where('is_current', true)->first()
                ?? Gameweek::where('is_next', true)->first()
                ?? Gameweek::orderBy('number')->latest('number')->first();
        }

        if (! $fromGw) {
            return $this->emptyScores($player, 0, $horizon);
        }

        // 2. Hämta kommande GWs (inkl fromGw), begränsa till horizon
        $futureGws = Gameweek::where('number', '>=', $fromGw->number)
            ->where('is_finished', false)
            ->orderBy('number')
            ->take($horizon)
            ->get();

        if ($futureGws->isEmpty()) {
            return $this->emptyScores($player, $fromGw->number, $horizon);
        }

        $epPerGw         = [];
        $difficultyPerGw = [];

        foreach ($futureGws as $gw) {
            $epPerGw[$gw->number]         = $this->epService->forPlayerAndGameweek($player, $gw);
            $difficultyPerGw[$gw->number] = $this->fixtureDifficulty($player, $gw);
        }

        $firstGwNumber  = $futureGws->first()->number;
        $epNext         = $epPerGw[$firstGwNumber] ?? 0.0;
        $epHorizonTotal = array_sum($epPerGw);
        $gwCount        = max(count($epPerGw), 1);
        $epHorizonAvg   = $epHorizonTotal / $gwCount;

        $avgDifficulty   = array_sum($difficultyPerGw) / $gwCount;
        $fixtureRunScore = 6 - $avgDifficulty; // 1–5 diff → 5–1 score

        // "Value": EP över horisonten per miljon
        $price      = (float) $player->price;
        $valueScore = $price > 0 ? $epHorizonTotal / $price : 0.0;

        // Minutes-stability: hur ofta spelar han 60+ min senaste 4 GWs innan fromGw
        $minutesStability = $this->minutesStability($player, $fromGw);

        // Consistency & upside baserat på senaste 6 GWs innan fromGw
        $consistency = $this->consistencyScore($player, $fromGw);
        $upside      = $this->upsideScore($player, $fromGw);

        // 3. Slutligt transfer score (tweak vikter efter smak)
        // Här blandar vi in fler parametrar:
        //  - EP-snitt är fortfarande viktigast
        //  - fixtures & value väger tungt
        //  - minutes_stability, consistency och upside ger profil
        $transferScore =
            $epHorizonAvg     * 0.40 +
            $fixtureRunScore  * 0.15 +
            $valueScore       * 0.15 +
            $minutesStability * 0.10 +
            $consistency      * 0.10 +
            $upside           * 0.10;

        return [
            'player_id'          => $player->id,
            'from_gw'            => $fromGw->number,
            'horizon'            => $horizon,
            'ep_next'            => round($epNext, 2),
            'ep_horizon_total'   => round($epHorizonTotal, 2),
            'ep_horizon_avg'     => round($epHorizonAvg, 2),
            'fixture_run_score'  => round($fixtureRunScore, 2),
            'value_score'        => round($valueScore, 2),
            'minutes_stability'  => round($minutesStability, 2),
            'consistency'        => round($consistency, 2),
            'upside'             => round($upside, 2),
            'transfer_score'     => round(max($transferScore, 0), 2),
        ];
    }

    protected function emptyScores(Player $player, int $fromGwNumber, int $horizon): array
    {
        return [
            'player_id'          => $player->id,
            'from_gw'            => $fromGwNumber,
            'horizon'            => $horizon,
            'ep_next'            => 0.0,
            'ep_horizon_total'   => 0.0,
            'ep_horizon_avg'     => 0.0,
            'fixture_run_score'  => 0.0,
            'value_score'        => 0.0,
            'minutes_stability'  => 0.0,
            'consistency'        => 0.0,
            'upside'             => 0.0,
            'transfer_score'     => 0.0,
        ];
    }

    /**
     * Fixture difficulty: 1–5 (fallback 3), kopierad från EP-servicen.
     */
    protected function fixtureDifficulty(Player $player, Gameweek $gw): float
    {
        if (! $player->club_id) {
            return 3.0;
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
     * Minutes-stability: andel matcher med 60+ min senaste 4 GWs innan fromGw.
     */
    protected function minutesStability(Player $player, Gameweek $fromGw): float
    {
        $recentStats = $player->gameweekStats()
            ->whereHas('gameweek', function ($q) use ($fromGw) {
                $q->where('number', '<', $fromGw->number);
            })
            ->orderByDesc('gameweek_id')
            ->take(4)
            ->get();

        if ($recentStats->isEmpty()) {
            return 0.0;
        }

        $games     = $recentStats->count();
        $sixtyPlus = $recentStats->where('minutes', '>=', 60)->count();

        return $games > 0 ? $sixtyPlus / $games : 0.0;
    }

    /**
     * Consistency: andel matcher med t.ex. ≥5 poäng senaste 6 GWs innan fromGw.
     */
    protected function consistencyScore(Player $player, Gameweek $fromGw): float
    {
        $recentStats = $player->gameweekStats()
            ->whereHas('gameweek', function ($q) use ($fromGw) {
                $q->where('number', '<', $fromGw->number);
            })
            ->orderByDesc('gameweek_id')
            ->take(6)
            ->get();

        if ($recentStats->isEmpty()) {
            return 0.0;
        }

        $games    = $recentStats->count();
        $returns  = $recentStats->where('total_points', '>=', 5)->count(); // "return"-gräns

        return $games > 0 ? $returns / $games : 0.0; // 0–1
    }

    /**
     * Upside: max-poäng senaste 6 GWs, skalar 0–1 där 15+ poäng ~1.0.
     */
    protected function upsideScore(Player $player, Gameweek $fromGw): float
    {
        $recentStats = $player->gameweekStats()
            ->whereHas('gameweek', function ($q) use ($fromGw) {
                $q->where('number', '<', $fromGw->number);
            })
            ->orderByDesc('gameweek_id')
            ->take(6)
            ->get();

        if ($recentStats->isEmpty()) {
            return 0.0;
        }

        $maxPoints = (float) $recentStats->max('total_points');

        // skala: 0–15p -> 0–1 (15+ räknas som full "ceiling")
        if ($maxPoints <= 0) {
            return 0.0;
        }

        return max(0.0, min($maxPoints / 15.0, 1.0));
    }
}
