<?php

use App\Models\Club;
use App\Models\User;
use App\Services\ClubTournamentRaceService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$userId = (int) ($argv[1] ?? 0);
$clubId = (int) ($argv[2] ?? 0);
$suffix = (string) ($argv[3] ?? '0');

$user = User::query()->findOrFail($userId);
$club = Club::query()->findOrFail($clubId);

try {
    app(ClubTournamentRaceService::class)->start(
        $user,
        $club,
        (string) Str::uuid(),
    );
    echo json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    echo json_encode([
        'status' => 'error',
        'message' => $exception->getMessage(),
    ], JSON_THROW_ON_ERROR);
}
