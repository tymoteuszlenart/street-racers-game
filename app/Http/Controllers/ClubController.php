<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClubRequest;
use App\Http\Requests\TransferClubOwnershipRequest;
use App\Http\Requests\UpdateClubMemberRoleRequest;
use App\Models\Club;
use App\Models\ClubMember;
use App\Services\ClubMembershipService;
use App\Services\ClubService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClubController extends Controller
{
    public function __construct(
        private readonly ClubService $clubService,
        private readonly ClubMembershipService $clubMembershipService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user()->load('clubMember.club');
        $membership = $user->clubMember;

        $clubs = Club::query()
            ->withCount('members')
            ->orderBy('name')
            ->paginate(20);

        return view('clubs.index', [
            'clubs' => $clubs,
            'membership' => $membership,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', Club::class);

        return view('clubs.create');
    }

    public function store(StoreClubRequest $request): RedirectResponse
    {
        $this->authorize('create', Club::class);

        $club = $this->clubService->create(
            $request->user(),
            $request->validated('name'),
            $request->validated('description'),
        );

        return redirect()
            ->route('clubs.show', $club)
            ->with('status', 'club-created');
    }

    public function show(Request $request, Club $club): View
    {
        $this->authorize('view', $club);

        $club->loadCount('members');
        $members = $club->members()
            ->with('user:id,name')
            ->orderByRaw("CASE role WHEN 'owner' THEN 1 WHEN 'manager' THEN 2 ELSE 3 END")
            ->orderBy('joined_at')
            ->get();

        $user = $request->user()->load('clubMember');
        $currentMembership = $user->clubMember?->club_id === $club->id
            ? $user->clubMember
            : null;

        $tournamentsUnlockLevel = (int) config('game.tournaments.unlock_level', 15);
        $canAccessTournament = $currentMembership !== null
            && ($user->playerProfile?->level ?? 1) >= $tournamentsUnlockLevel;

        return view('clubs.show', [
            'club' => $club,
            'members' => $members,
            'currentMembership' => $currentMembership,
            'canAccessTournament' => $canAccessTournament,
            'tournamentsUnlockLevel' => $tournamentsUnlockLevel,
        ]);
    }

    public function join(Request $request, Club $club): RedirectResponse
    {
        $this->authorize('join', $club);

        $this->clubMembershipService->join($request->user(), $club);

        return redirect()
            ->route('clubs.show', $club)
            ->with('status', 'club-joined');
    }

    public function leave(Request $request, Club $club): RedirectResponse
    {
        $this->authorize('leave', $club);

        $this->clubMembershipService->leave($request->user());

        return redirect()
            ->route('clubs.index')
            ->with('status', 'club-left');
    }

    public function kick(Request $request, Club $club, ClubMember $member): RedirectResponse
    {
        abort_unless($member->club_id === $club->id, 404);

        $this->authorize('kick', [$club, $member]);

        $this->clubMembershipService->kick($request->user(), $member);

        return redirect()
            ->route('clubs.show', $club)
            ->with('status', 'member-kicked');
    }

    public function updateMemberRole(UpdateClubMemberRoleRequest $request, Club $club, ClubMember $member): RedirectResponse
    {
        abort_unless($member->club_id === $club->id, 404);

        $this->authorize('manageRoles', $club);

        if ($request->validated('action') === 'promote') {
            $this->clubMembershipService->promote($request->user(), $member);
        } else {
            $this->clubMembershipService->demote($request->user(), $member);
        }

        return redirect()
            ->route('clubs.show', $club)
            ->with('status', 'member-role-updated');
    }

    public function transferOwnership(TransferClubOwnershipRequest $request, Club $club): RedirectResponse
    {
        $member = $request->targetMember();

        $this->authorize('transferOwnership', [$club, $member]);

        $this->clubMembershipService->transferOwnership($request->user(), $member);

        return redirect()
            ->route('clubs.show', $club)
            ->with('status', 'ownership-transferred');
    }

    public function destroy(Request $request, Club $club): RedirectResponse
    {
        $this->authorize('delete', $club);

        $this->clubService->dissolve($club);

        return redirect()
            ->route('clubs.index')
            ->with('status', 'club-dissolved');
    }
}
