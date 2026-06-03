<?php

use App\Models\Car;
use App\Models\CarModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        CarModel::query()->update(['durability' => (int) config('game.condition.car_max', 999)]);

        Schema::table('cars', function (Blueprint $table) {
            $table->unsignedSmallInteger('condition_current')->default(999)->change();
            $table->unsignedSmallInteger('condition_max')->default(999)->change();
        });

        $carMax = (int) config('game.condition.car_max', 999);

        Car::query()->eachById(function (Car $car) use ($carMax): void {
            $percent = $car->condition_max > 0
                ? $car->condition_current / $car->condition_max
                : 1.0;

            $car->update([
                'condition_max' => $carMax,
                'condition_current' => (int) round($percent * $carMax),
            ]);
        });

        $partMax = (int) config('game.condition.part_max', 200);

        Schema::table('parts', function (Blueprint $table) use ($partMax) {
            $table->unsignedSmallInteger('condition_current')->default($partMax)->change();
            $table->unsignedSmallInteger('condition_max')->default($partMax)->change();
        });

        DB::table('parts')->orderBy('id')->eachById(function (object $part) use ($partMax): void {
            $percent = $part->condition_max > 0
                ? $part->condition_current / $part->condition_max
                : 1.0;

            DB::table('parts')->where('id', $part->id)->update([
                'condition_max' => $partMax,
                'condition_current' => (int) round($percent * $partMax),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->unsignedTinyInteger('condition_current')->default(100)->change();
            $table->unsignedTinyInteger('condition_max')->default(100)->change();
        });

        Schema::table('parts', function (Blueprint $table) {
            $table->unsignedTinyInteger('condition_current')->default(100)->change();
            $table->unsignedTinyInteger('condition_max')->default(100)->change();
        });
    }
};
