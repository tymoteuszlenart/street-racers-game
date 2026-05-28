<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reward_type');
            $table->date('claim_date');
            $table->json('granted_payload');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'reward_type', 'claim_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_rewards');
    }
};
