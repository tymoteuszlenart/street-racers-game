<?php

namespace App\Console\Commands;

use App\Models\Club;
use App\Models\User;
use App\Services\ClubMembershipService;
use Illuminate\Console\Command;
use Throwable;

class IntegrationClubJoinCommand extends Command
{
    protected $signature = 'clubs:integration-join {userId} {clubId}';

    protected $description = 'Run a club join for MySQL integration concurrency tests';

    public function handle(ClubMembershipService $clubMembershipService): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('This command is only available in local and testing environments.');

            return self::FAILURE;
        }

        $user = User::query()->findOrFail((int) $this->argument('userId'));
        $club = Club::query()->findOrFail((int) $this->argument('clubId'));

        try {
            $member = $clubMembershipService->join($user, $club);

            $this->line(json_encode([
                'status' => 'ok',
                'club_member_id' => $member->id,
            ], JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->line(json_encode([
                'status' => 'error',
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ], JSON_THROW_ON_ERROR));

            return self::FAILURE;
        }
    }
}
