<?php

// app/Models/ExternalPlayerId.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalPlayerId extends Model
{
    protected $fillable = ['player_id', 'provider', 'external_id'];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
