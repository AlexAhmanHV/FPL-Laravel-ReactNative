<?php

// database/migrations/xxxx_xx_xx_add_fotmob_id_to_players_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('fotmob_id')->nullable()->after('fpl_player_id');
            $table->index('fotmob_id');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('fotmob_id');
        });
    }
};
