<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SquadSlot extends Model
{
    protected $guarded = [];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
