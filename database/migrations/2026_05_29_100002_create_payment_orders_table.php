<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shop_product_id')->constrained()->restrictOnDelete();
            $table->string('status');
            $table->unsignedInteger('amount_cents');
            $table->string('provider_checkout_session_id')->nullable();
            $table->string('provider_payment_intent_id')->nullable();
            $table->string('provider_event_id')->nullable()->unique();
            $table->json('granted_payload')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('provider_checkout_session_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_orders');
    }
};
