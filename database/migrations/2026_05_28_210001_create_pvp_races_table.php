<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pvp_races', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenger_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('defender_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('challenger_car_id')->nullable()->constrained('cars')->nullOnDelete();
            $table->foreignId('defender_car_id')->nullable()->constrained('cars')->nullOnDelete();
            $table->json('challenger_snapshot');
            $table->json('defender_snapshot');
            $table->foreignId('race_result_id')->nullable()->constrained('race_results')->nullOnDelete();
            $table->timestamps();

            $table->index(['challenger_user_id', 'created_at'], 'pvp_races_challenger_history_idx');
            $table->index(['defender_user_id', 'created_at'], 'pvp_races_defender_history_idx');
            $table->index(['challenger_user_id', 'defender_user_id', 'created_at'], 'pvp_races_pair_forward_idx');
            $table->index(['defender_user_id', 'challenger_user_id', 'created_at'], 'pvp_races_pair_reverse_idx');
        });

        Schema::table('race_results', function (Blueprint $table) {
            $table->foreign('pvp_race_id')->references('id')->on('pvp_races')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('race_results', function (Blueprint $table) {
            $table->dropForeign(['pvp_race_id']);
        });

        Schema::dropIfExists('pvp_races');
    }
};
