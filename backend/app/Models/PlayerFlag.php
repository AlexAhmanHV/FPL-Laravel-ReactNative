<?php
class PlayerFlag extends Model
{
    protected $fillable = ['player_id', 'injury_risk', 'rotation_risk', 'note'];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
