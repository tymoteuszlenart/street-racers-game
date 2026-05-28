<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('race_attempts', function (Blueprint $table) {
            $table->foreignId('club_tournament_id')
                ->nullable()
                ->after('defender_user_id')
                ->constrained('club_tournaments')
                ->nullOnDelete();
        });

        Schema::table('race_results', function (Blueprint $table) {
            $table->foreignId('club_tournament_id')
                ->nullable()
                ->after('pvp_race_id')
                ->constrained('club_tournaments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('race_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('club_tournament_id');
        });

        Schema::table('race_results', function (Blueprint $table) {
            $table->dropConstrainedForeignId('club_tournament_id');
        });
    }
};
