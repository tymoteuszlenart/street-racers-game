<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('car_model_id')->constrained()->cascadeOnDelete();
            $table->string('nickname');
            $table->unsignedTinyInteger('condition_current')->default(100);
            $table->unsignedTinyInteger('condition_max')->default(100);
            $table->string('acquired_via');
            $table->unsignedBigInteger('purchase_price')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('car_model_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
