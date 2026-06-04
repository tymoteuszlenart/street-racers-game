<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_models', function (Blueprint $table) {
            $table->unsignedSmallInteger('block_level')->default(6)->after('unlock_level');
        });
    }

    public function down(): void
    {
        Schema::table('car_models', function (Blueprint $table) {
            $table->dropColumn('block_level');
        });
    }
};
