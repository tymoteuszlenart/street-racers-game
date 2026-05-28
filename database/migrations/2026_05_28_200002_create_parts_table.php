<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('part_model_id')->constrained()->restrictOnDelete();
            $table->foreignId('car_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slot');
            $table->string('acquired_via');
            $table->unsignedBigInteger('purchase_price')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('car_id');
            $table->unique(['car_id', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
