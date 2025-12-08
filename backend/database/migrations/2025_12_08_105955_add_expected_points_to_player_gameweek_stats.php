<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('player_gameweek_stats', function (Blueprint $table) {
            $table->decimal('expected_points', 5, 2)->nullable()->after('total_points');
        });
    }

    public function down(): void
    {
        Schema::table('player_gameweek_stats', function (Blueprint $table) {
            $table->dropColumn('expected_points');
        });
    }
};
