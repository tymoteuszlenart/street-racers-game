<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('season_key', 16)->unique();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_tournaments');
    }
};
