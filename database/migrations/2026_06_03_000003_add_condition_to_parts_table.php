<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->unsignedTinyInteger('condition_current')->default(100)->after('purchase_price');
            $table->unsignedTinyInteger('condition_max')->default(100)->after('condition_current');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            if (Schema::hasColumn('parts', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            $table->dropColumn(['condition_current', 'condition_max']);
        });
    }
};
