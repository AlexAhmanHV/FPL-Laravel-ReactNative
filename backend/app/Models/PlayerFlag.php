<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayerFlag extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'injury_risk',
        'rotation_risk',
        'note',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
