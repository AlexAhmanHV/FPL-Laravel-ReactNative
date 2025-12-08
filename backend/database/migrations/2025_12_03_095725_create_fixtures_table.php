<?php

// database/migrations/xxxx_xx_xx_create_fixtures_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fixtures', function (Blueprint $table) {
            $table->id();

            $table->foreignId('gameweek_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_club_id')->constrained('clubs')->cascadeOnDelete();
            $table->foreignId('away_club_id')->constrained('clubs')->cascadeOnDelete();

            $table->dateTime('kickoff_at')->nullable();

            // FDR eller motsvarande från FPL (1–5)
            $table->unsignedTinyInteger('home_difficulty')->nullable();
            $table->unsignedTinyInteger('away_difficulty')->nullable();

            $table->boolean('finished')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixtures');
    }
};
