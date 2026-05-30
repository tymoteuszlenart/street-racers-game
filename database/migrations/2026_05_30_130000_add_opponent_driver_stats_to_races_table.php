<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->unsignedSmallInteger('opponent_stat_power')->default(1)->after('opponent_handling');
            $table->unsignedSmallInteger('opponent_stat_acceleration')->default(1)->after('opponent_stat_power');
            $table->unsignedSmallInteger('opponent_stat_grip')->default(1)->after('opponent_stat_acceleration');
            $table->unsignedSmallInteger('opponent_stat_handling')->default(1)->after('opponent_stat_grip');
        });
    }

    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropColumn([
                'opponent_stat_power',
                'opponent_stat_acceleration',
                'opponent_stat_grip',
                'opponent_stat_handling',
            ]);
        });
    }
};
