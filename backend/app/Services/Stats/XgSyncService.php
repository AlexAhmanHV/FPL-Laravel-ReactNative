<?php

// app/Services/Stats/XgSyncService.php
namespace App\Services\Stats;

use App\Models\Player;
use App\Models\Gameweek;
use App\Models\PlayerGameweekStat;
use App\Services\External\FotmobClient;

class XgSyncService
{
    public function __construct(
        protected FotmobClient $client
    ) {}

    /**
     * Synca xG/xA för EN spelare (FotMob) in i player_gameweek_stats.
     */
    public function syncForPlayer(Player $player): void
    {
        $fotmobId = $player->externalIds()
            ->where('provider', 'fotmob')
            ->value('external_id');

        if (! $fotmobId) {
            throw new \RuntimeException("Player {$player->id} saknar fotmob-external-id.");
        }

        $data = $this->client->playerMatches((int) $fotmobId);

        // Här beror allt på hur JSON:en från FotMob ser ut.
        // Du får justera nycklarna när du sett strukturen.
        foreach ($data['matches'] ?? [] as $matchRow) {
            // Exempel på hur du kan mappa:
            // $gwNumber = $matchRow['round'] ?? null;
            // $xg       = $matchRow['xg'] ?? null;
            // $xa       = $matchRow['xa'] ?? null;

            $gwNumber = $matchRow['round'] ?? null;
            $xg       = $matchRow['xg'] ?? null;
            $xa       = $matchRow['xa'] ?? null;

            if (! $gwNumber || ($xg === null && $xa === null)) {
                continue;
            }

            $gw = Gameweek::where('number', $gwNumber)->first();
            if (! $gw) {
                continue;
            }

            PlayerGameweekStat::where('player_id', $player->id)
                ->where('gameweek_id', $gw->id)
                ->update([
                    'xg' => $xg,
                    'xa' => $xa,
                ]);
        }
    }
}
