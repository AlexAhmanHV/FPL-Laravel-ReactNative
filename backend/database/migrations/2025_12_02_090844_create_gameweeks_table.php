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
    Schema::create('gameweeks', function (Blueprint $table) {
        $table->id();
        $table->unsignedInteger('number')->unique();
        $table->string('name'); // "Gameweek 1"
        $table->dateTime('deadline_at');
        $table->boolean('is_current')->default(false);
        $table->boolean('is_next')->default(false);
        $table->boolean('is_finished')->default(false);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gameweeks');
    }
};
