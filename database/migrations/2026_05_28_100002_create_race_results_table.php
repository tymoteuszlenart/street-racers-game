<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('race_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('attempt_type');
            $table->foreignId('race_id')->nullable()->constrained('races')->nullOnDelete();
            $table->unsignedBigInteger('pvp_race_id')->nullable();
            $table->boolean('won');
            $table->decimal('player_score', 12, 4);
            $table->decimal('opponent_score', 12, 4);
            $table->json('score_breakdown')->nullable();
            $table->decimal('random_factor', 8, 6);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('race_results');
    }
};
