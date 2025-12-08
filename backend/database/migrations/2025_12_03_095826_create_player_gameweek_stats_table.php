<?php

// database/migrations/xxxx_xx_xx_create_player_gameweek_stats_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('player_gameweek_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gameweek_id')->constrained()->cascadeOnDelete();

            $table->unsignedInteger('minutes')->default(0);
            $table->integer('total_points')->default(0);
            $table->unsignedInteger('goals_scored')->default(0);
            $table->unsignedInteger('assists')->default(0);
            $table->unsignedInteger('clean_sheets')->default(0);
            $table->unsignedInteger('goals_conceded')->default(0);
            $table->unsignedInteger('bonus')->default(0);

            // plats för xG/xA sen om du vill mata in från annat API
            $table->decimal('xg', 5, 2)->nullable();
            $table->decimal('xa', 5, 2)->nullable();

            $table->timestamps();

            $table->unique(['player_id', 'gameweek_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_gameweek_stats');
    }
};
