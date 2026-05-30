<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('player_profiles', function (Blueprint $table) {
            $table->unsignedSmallInteger('stat_power')->default(1)->after('experience');
            $table->unsignedSmallInteger('stat_acceleration')->default(1)->after('stat_power');
            $table->unsignedSmallInteger('stat_grip')->default(1)->after('stat_acceleration');
            $table->unsignedSmallInteger('stat_handling')->default(1)->after('stat_grip');
        });

        DB::table('player_profiles')->update([
            'stat_power' => 1,
            'stat_acceleration' => 1,
            'stat_grip' => 1,
            'stat_handling' => 1,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('player_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'stat_power',
                'stat_acceleration',
                'stat_grip',
                'stat_handling',
            ]);
        });
    }
};
