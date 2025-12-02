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
    Schema::create('clubs', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('fpl_team_id')->unique(); // ID from FPL API
        $table->string('name');
        $table->string('short_name', 10);
        $table->string('code', 10)->nullable(); // e.g. 'MCI', 'LIV'
        $table->string('logo_url')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};
