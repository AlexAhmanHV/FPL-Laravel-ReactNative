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
    Schema::create('players', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('fpl_player_id')->unique();
        $table->string('first_name');
        $table->string('second_name');
        $table->string('web_name'); // FPL display name
        $table->enum('position', ['GKP', 'DEF', 'MID', 'FWD']);
        $table->foreignId('club_id')->constrained('clubs');
        $table->decimal('price', 5, 1)->default(0); // e.g. 7.5
        $table->boolean('is_active')->default(true);
        $table->integer('selected_by_percent')->nullable(); // or decimal
        $table->string('status', 10)->nullable(); // 'a', 'i', 'd', etc from FPL
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
