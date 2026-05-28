<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_tournament_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('race_result_id')->nullable()->constrained('race_results')->nullOnDelete();
            $table->unsignedSmallInteger('points');
            $table->boolean('counts_toward_club')->default(false);
            $table->timestamps();

            $table->index(['club_tournament_id', 'user_id']);
            $table->index(['club_id', 'club_tournament_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_tournament_entries');
    }
};
