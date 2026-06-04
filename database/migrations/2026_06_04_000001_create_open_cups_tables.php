<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('cups')->default(0)->after('cash');
        });

        Schema::create('open_cups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32);
            $table->unsignedBigInteger('entry_fee_cash');
            $table->json('host_snapshot');
            $table->timestamp('join_ends_at');
            $table->timestamp('settling_ends_at')->nullable();
            $table->unsignedBigInteger('champion_entry_id')->nullable();
            $table->timestamps();
        });

        Schema::create('open_cup_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_cup_id')->constrained('open_cups')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('display_name');
            $table->json('car_snapshot');
            $table->unsignedTinyInteger('solo_wins')->default(0);
            $table->unsignedTinyInteger('placement')->nullable();
            $table->boolean('rewards_applied')->default(false);
            $table->timestamps();

            $table->unique(['open_cup_id', 'user_id']);
        });

        Schema::create('open_cup_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_cup_id')->constrained('open_cups')->cascadeOnDelete();
            $table->string('phase', 32);
            $table->unsignedTinyInteger('round')->default(1);
            $table->unsignedTinyInteger('match_order')->default(0);
            $table->foreignId('entry_id')->nullable()->constrained('open_cup_entries')->nullOnDelete();
            $table->foreignId('entry_a_id')->nullable()->constrained('open_cup_entries')->nullOnDelete();
            $table->foreignId('entry_b_id')->nullable()->constrained('open_cup_entries')->nullOnDelete();
            $table->foreignId('race_result_id')->nullable()->constrained('race_results')->nullOnDelete();
            $table->foreignId('winner_entry_id')->nullable()->constrained('open_cup_entries')->nullOnDelete();
            $table->boolean('both_eliminated')->default(false);
            $table->timestamps();
        });

        Schema::table('race_results', function (Blueprint $table) {
            $table->foreignId('open_cup_id')
                ->nullable()
                ->after('club_tournament_id')
                ->constrained('open_cups')
                ->nullOnDelete();
        });

        Schema::table('open_cups', function (Blueprint $table) {
            $table->foreign('champion_entry_id')
                ->references('id')
                ->on('open_cup_entries')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('open_cups', function (Blueprint $table) {
            $table->dropForeign(['champion_entry_id']);
        });

        Schema::table('race_results', function (Blueprint $table) {
            $table->dropConstrainedForeignId('open_cup_id');
        });

        Schema::dropIfExists('open_cup_matches');
        Schema::dropIfExists('open_cup_entries');
        Schema::dropIfExists('open_cups');

        Schema::table('player_profiles', function (Blueprint $table) {
            $table->dropColumn('cups');
        });
    }
};
