<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $guarded = [];

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

public function gameweekStats()
{
    return $this->hasMany(\App\Models\PlayerGameweekStat::class);
}

public function flags()
{
    return $this->hasOne(\App\Models\PlayerFlag::class);
}

public function externalIds()
{
    return $this->hasMany(\App\Models\ExternalPlayerId::class);
}

}
