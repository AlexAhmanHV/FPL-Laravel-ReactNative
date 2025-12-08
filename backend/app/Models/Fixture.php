<?php

// app/Models/Fixture.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fixture extends Model
{
    protected $fillable = [
        'gameweek_id',
        'home_club_id',
        'away_club_id',
        'kickoff_at',
        'home_difficulty',
        'away_difficulty',
        'finished',
    ];

    public function gameweek()
    {
        return $this->belongsTo(Gameweek::class);
    }

    public function homeClub()
    {
        return $this->belongsTo(Club::class, 'home_club_id');
    }

    public function awayClub()
    {
        return $this->belongsTo(Club::class, 'away_club_id');
    }
}
