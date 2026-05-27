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
        Schema::create('player_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->bigInteger('cash')->default(5000);
            $table->integer('reputation')->default(0);
            $table->integer('level')->default(1);
            $table->bigInteger('experience')->default(0);
            $table->integer('fuel_current')->default(100);
            $table->integer('fuel_max')->default(100);
            $table->timestamp('fuel_updated_at')->nullable();
            $table->integer('premium_fuel_current')->default(0);
            $table->integer('premium_fuel_max')->default(10);
            $table->timestamp('premium_fuel_claimed_at')->nullable();
            $table->foreignId('active_car_id')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_profiles');
    }
};
