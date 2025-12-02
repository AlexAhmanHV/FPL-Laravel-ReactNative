<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gameweek extends Model
{
    protected $guarded = [];

    protected $casts = [
        'deadline_at' => 'datetime',
        'is_current'  => 'boolean',
        'is_next'     => 'boolean',
        'is_finished' => 'boolean',
    ];
}
