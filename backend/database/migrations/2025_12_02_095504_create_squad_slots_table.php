<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('squad_slots', function (Blueprint $table) {
        $table->id();
        $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
        $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
        $table->enum('position', ['GKP', 'DEF', 'MID', 'FWD']);
        $table->boolean('is_starting')->default(true);
        $table->unsignedTinyInteger('order')->default(1); // for bench order later
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('squad_slots');
    }
};
