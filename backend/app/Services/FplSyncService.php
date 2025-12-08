<?php

namespace App\Services;

use App\Models\User;
use App\Models\Team;
use App\Models\SquadSlot;
use App\Models\Gameweek;
use App\Models\Player;
use App\Models\Club;
use App\Models\Fixture;
use App\Models\PlayerGameweekStat;

class FplSyncService
{
    public function __construct(
        protected FplClient $client
    ) {}

    // ──────────────────────────
    // Synca användarens FPL-lag
    // ──────────────────────────

    public function syncUserTeam(User $user): void
    {
        if (! $user->fpl_entry_id) {
            throw new \RuntimeException('User has no FPL entry ID');
        }

        // Get current gameweek (fallback: first not finished)
        $currentGw = Gameweek::where('is_current', true)->first()
            ?? Gameweek::where('is_finished', false)->orderBy('number')->first();

        if (! $currentGw) {
            throw new \RuntimeException('No current or upcoming gameweek found.');
        }

        $data = $this->client->entryPicks($user->fpl_entry_id, $currentGw->number);

        if (! isset($data['picks']) || ! is_array($data['picks'])) {
            throw new \RuntimeException('Unexpected FPL response for entry picks.');
        }

        // Create or update the team row
        $team = $user->teams()->firstOrCreate(
            ['fpl_entry_id' => $user->fpl_entry_id],
            ['name' => $data['entry']['name'] ?? ($user->name . "'s XI")]
        );

        // Clear previous squad
        $team->squadSlots()->delete();

        foreach ($data['picks'] as $pick) {
            // FPL element id for the player
            $fplPlayerId = $pick['element'] ?? null;
            if (! $fplPlayerId) continue;

            $player = Player::where('fpl_player_id', $fplPlayerId)->first();
            if (! $player) continue;

            $position   = $pick['position'] ?? 1;       // 1–15
            $isStarting = $position <= 11;

            $team->squadSlots()->create([
                'player_id'   => $player->id,
                'position'    => $player->position,
                'is_starting' => $isStarting,
                'order'       => $position,
            ]);
        }
    }

    // ──────────────────────────
    // Bootstrap-sync (har du redan)
    // ──────────────────────────

    public function syncBootstrap(): void
    {
        $data = $this->client->bootstrap();

        // --- Teams → clubs ---
        foreach ($data['teams'] as $team) {
            Club::updateOrCreate(
                ['fpl_team_id' => $team['id']],
                [
                    'name'       => $team['name'],
                    'short_name' => $team['short_name'],
                    'code'       => (string) $team['code'],
                    'logo_url'   => null,
                ]
            );
        }

        // --- Elements → players ---
        foreach ($data['elements'] as $elem) {
            $club = Club::where('fpl_team_id', $elem['team'])->first();

            Player::updateOrCreate(
                ['fpl_player_id' => $elem['id']],
                [
                    'first_name'          => $elem['first_name'],
                    'second_name'         => $elem['second_name'],
                    'web_name'            => $elem['web_name'],
                    'position'            => $this->mapElementType($elem['element_type']),
                    'club_id'             => $club?->id,
                    'price'               => $elem['now_cost'] / 10,
                    'is_active'           => true,
                    'selected_by_percent' => (float) $elem['selected_by_percent'],
                    'status'              => $elem['status'],
                ]
            );
        }

        // --- Events → gameweeks ---
        foreach ($data['events'] as $event) {
            Gameweek::updateOrCreate(
                ['number' => $event['id']],
                [
                    'name'        => $event['name'],
                    'deadline_at' => $event['deadline_time'],
                    'is_current'  => $event['is_current'],
                    'is_next'     => $event['is_next'],
                    'is_finished' => $event['finished'],
                ]
            );
        }
    }

    // ──────────────────────────
    // NY: synca fixtures
    // ──────────────────────────

    public function syncFixtures(): void
    {
        $fixtures = $this->client->fixtures();

        foreach ($fixtures as $fx) {
            // vissa fixtures kan sakna event om de är långt fram
            if (empty($fx['event'])) {
                continue;
            }

            $gw = Gameweek::where('number', $fx['event'])->first();
            if (! $gw) {
                continue;
            }

            $homeClub = Club::where('fpl_team_id', $fx['team_h'])->first();
            $awayClub = Club::where('fpl_team_id', $fx['team_a'])->first();

            if (! $homeClub || ! $awayClub) {
                continue;
            }

            Fixture::updateOrCreate(
                [
                    'gameweek_id'  => $gw->id,
                    'home_club_id' => $homeClub->id,
                    'away_club_id' => $awayClub->id,
                ],
                [
                    'kickoff_at'      => $fx['kickoff_time'] ?? null,
                    'home_difficulty' => $fx['team_h_difficulty'] ?? null,
                    'away_difficulty' => $fx['team_a_difficulty'] ?? null,
                    'finished'        => $fx['finished'] ?? false,
                ]
            );
        }
    }

    // ──────────────────────────
    // NY: synca player history
    // ──────────────────────────

    public function syncPlayerHistory(): void
    {
        $players = Player::whereNotNull('fpl_player_id')->get();

        foreach ($players as $player) {
            $summary = $this->client->playerSummary($player->fpl_player_id);

            if (! isset($summary['history']) || ! is_array($summary['history'])) {
                continue;
            }

            foreach ($summary['history'] as $row) {
                // 'round' är GW-numret i FPL
                $gw = Gameweek::where('number', $row['round'])->first();
                if (! $gw) {
                    continue;
                }

                PlayerGameweekStat::updateOrCreate(
                    [
                        'player_id'   => $player->id,
                        'gameweek_id' => $gw->id,
                    ],
                    [
                        'minutes'        => $row['minutes'],
                        'total_points'   => $row['total_points'],
                        'goals_scored'   => $row['goals_scored'],
                        'assists'        => $row['assists'],
                        'clean_sheets'   => $row['clean_sheets'],
                        'goals_conceded' => $row['goals_conceded'],
                        'bonus'          => $row['bonus'],
                        // xg/xa kan fyllas senare från ett annat API
                    ]
                );
            }
        }
    }

    protected function mapElementType(int $type): string
    {
        return match ($type) {
            1 => 'GKP',
            2 => 'DEF',
            3 => 'MID',
            4 => 'FWD',
            default => 'MID',
        };
    }
}
