<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayerGameweekStat extends Model
{
    use HasFactory;

    // Fyll på med de kolumner du vill mass-assigna (behövs inte för EP, men nice)
    protected $fillable = [
        'player_id',
        'gameweek_id',
        'minutes',
        'total_points',
        'goals_scored',
        'assists',
        'clean_sheets',
        'goals_conceded',
        'bonus',
        'xg',
        'xa',
        // lägg till fler här om du har dem i migrationen
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function gameweek()
    {
        return $this->belongsTo(Gameweek::class);
    }
}
