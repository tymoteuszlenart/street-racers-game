<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('race_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('idempotency_key');
            $table->string('attempt_type');
            $table->foreignId('race_id')->nullable()->constrained('races')->nullOnDelete();
            $table->foreignId('defender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status');
            $table->foreignId('race_result_id')->nullable()->constrained('race_results')->nullOnDelete();
            $table->string('error_code')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'idempotency_key']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('race_attempts');
    }
};
