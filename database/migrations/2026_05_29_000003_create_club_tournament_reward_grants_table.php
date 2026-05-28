<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_tournament_reward_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('granted_payload');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['club_tournament_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_tournament_reward_grants');
    }
};
