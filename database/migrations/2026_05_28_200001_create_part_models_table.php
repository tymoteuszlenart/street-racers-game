<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slot');
            $table->string('rarity');
            $table->string('image_path')->nullable();
            $table->unsignedSmallInteger('power_bonus')->default(0);
            $table->unsignedSmallInteger('acceleration_bonus')->default(0);
            $table->unsignedSmallInteger('grip_bonus')->default(0);
            $table->unsignedSmallInteger('handling_bonus')->default(0);
            $table->unsignedBigInteger('price');
            $table->unsignedSmallInteger('unlock_level')->default(1);
            $table->string('min_car_class', 1);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['slot', 'active']);
            $table->index(['unlock_level', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_models');
    }
};
