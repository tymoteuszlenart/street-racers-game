<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('class', 1);
            $table->string('rarity')->default('common');
            $table->string('image_path')->nullable();
            $table->unsignedSmallInteger('power');
            $table->unsignedSmallInteger('acceleration');
            $table->unsignedSmallInteger('weight');
            $table->unsignedSmallInteger('grip');
            $table->unsignedSmallInteger('handling');
            $table->unsignedSmallInteger('durability');
            $table->json('upgrade_slots')->nullable();
            $table->unsignedBigInteger('price')->default(0);
            $table->boolean('starter')->default(false);
            $table->unsignedSmallInteger('unlock_level')->default(1);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_models');
    }
};
