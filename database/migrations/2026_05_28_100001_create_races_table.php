<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('races', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('unlock_level')->default(1);
            $table->unsignedInteger('fuel_cost')->default(10);
            $table->unsignedInteger('cash_reward_win')->default(100);
            $table->unsignedInteger('cash_reward_loss')->default(25);
            $table->unsignedInteger('reputation_reward_win')->default(5);
            $table->unsignedInteger('reputation_reward_loss')->default(1);
            $table->unsignedBigInteger('experience_reward_win')->default(50);
            $table->unsignedBigInteger('experience_reward_loss')->default(10);
            $table->unsignedInteger('opponent_power');
            $table->unsignedInteger('opponent_acceleration');
            $table->unsignedInteger('opponent_grip');
            $table->unsignedInteger('opponent_handling');
            $table->unsignedTinyInteger('condition_damage_min')->default(1);
            $table->unsignedTinyInteger('condition_damage_max')->default(3);
            $table->decimal('random_factor_variance', 5, 4)->default(0.05);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('races');
    }
};
