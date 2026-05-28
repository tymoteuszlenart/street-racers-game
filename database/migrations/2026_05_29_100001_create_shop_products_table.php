<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('type');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('price_cents');
            $table->string('stripe_price_id')->nullable();
            $table->string('grant_mode')->nullable();
            $table->unsignedInteger('grant_amount')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_products');
    }
};
