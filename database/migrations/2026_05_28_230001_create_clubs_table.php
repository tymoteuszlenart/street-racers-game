<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64);
            $table->string('slug', 64)->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('points')->default(0);
            $table->unsignedSmallInteger('level')->default(1);
            $table->timestamps();

            $table->index(['points', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};
