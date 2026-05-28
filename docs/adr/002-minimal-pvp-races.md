# ADR 002: Minimal PvP races — snapshots, attempts, and result links

**Status:** Accepted  
**Date:** 2026-05-28  
**Implements:** GitHub issue #11 (Implement Phase 4b: Minimal PvP races)  
**Depends on:** ADR 001 (`CarStatAggregator` and tuned car stats)

## Context

Phase 4b adds instant direct PvP after the tuning shop. A challenger selects another player, the server snapshots both active cars, resolves the race immediately, and stores the result. There is no challenge inbox, accept/decline state, matchmaking, ranked ladder, wager, or PvP economy reward in MVP.

The two main implementation risks are:

- Duplicating NPC race idempotency and attempt handling in a new PvP service.
- Creating inconsistent links between `pvp_races`, `race_results`, and `race_attempts`.

This ADR makes those decisions explicit before implementation.

## Decision summary

| Topic | Decision |
| --- | --- |
| PvP style | Direct instant race; challenger picks `defender_user_id` |
| Defender consent | None in MVP; defender can view read-only history later |
| Attempt handling | Shared race-attempt/idempotency service used by NPC and PvP starts |
| Scoring | Reuse `RaceScoreCalculator`; PvP scores from stored snapshots, not live cars |
| Snapshots | Store aggregated race stats from `CarStatAggregator` for both cars |
| Fuel | Challenger spends regular fuel; defender spends no fuel |
| Condition damage | Challenger car only; defender car is unchanged |
| Rewards | No cash, reputation, XP, leaderboards, missions, or club points from PvP MVP |
| Daily pair cap | `10` PvP races total per unordered player pair per app-timezone day |
| Result link | `race_results.pvp_race_id` and `pvp_races.race_result_id` are set in one DB transaction |

---

## Tables

### `pvp_races`

| Column | Type | Notes |
| --- | --- | --- |
| `id` | bigint PK | |
| `challenger_user_id` | FK -> `users` | Initiating player |
| `defender_user_id` | FK -> `users` | Opponent |
| `challenger_car_id` | FK -> `cars`, nullable on delete | Active car at snapshot time |
| `defender_car_id` | FK -> `cars`, nullable on delete | Active car at snapshot time |
| `challenger_snapshot` | json | Frozen challenger race stats |
| `defender_snapshot` | json | Frozen defender race stats |
| `race_result_id` | FK -> `race_results`, nullable | Set after result row is created |
| `created_at` / `updated_at` | timestamps | |

Indexes:

- `(challenger_user_id, created_at)` for challenger history.
- `(defender_user_id, created_at)` for defender history.
- `(challenger_user_id, defender_user_id, created_at)` and `(defender_user_id, challenger_user_id, created_at)` for same-pair cap checks.

### Existing tables

`race_attempts` already stores:

- `attempt_type`
- `race_id`
- `defender_user_id`
- `race_result_id`

`race_results` already stores:

- `attempt_type`
- `race_id`
- `pvp_race_id`

MVP implementation must keep the invariant:

- NPC attempts/results use `attempt_type = npc`, set `race_id`, and leave PvP columns null.
- PvP attempts/results use `attempt_type = pvp`, leave `race_id` null, and set the defender / PvP link columns.
- A `race_results` row must never reference both `race_id` and `pvp_race_id`.

---

## Snapshot JSON

PvP snapshots store the exact values needed by `RaceScoreCalculator`, plus lightweight audit data for display and debugging.

Minimum shape:

```json
{
  "car_id": 1,
  "car_model_id": 1,
  "car_name": "Rust Runner",
  "stats": {
    "power": 82,
    "acceleration": 74,
    "grip": 66,
    "handling": 61,
    "condition_percent": 95.0
  }
}
```

The result calculation reads `snapshot.stats`. It must not re-read live `cars`, `car_models`, or equipped `parts` after the snapshot has been written.

---

## Execution flow

Every PvP start runs in one DB transaction with the same duplicate-request behavior as NPC race starts:

1. Resolve or create `race_attempts` with `attempt_type = pvp`, `race_id = null`, and `defender_user_id`.
2. If the attempt already succeeded, return the stored result.
3. If the attempt is pending, return conflict.
4. Lock the attempt, then both player profiles and active cars in stable ascending `user_id` order.
5. Validate challenger and defender are different users and both active cars still exist.
6. Enforce the same-pair daily cap of `10` total races for the unordered pair in `config('app.timezone')`.
7. Regenerate and spend challenger fuel only.
8. Build challenger and defender snapshots with `CarStatAggregator`.
9. Create `pvp_races` with both snapshots.
10. Score the race from snapshot stats via `RaceScoreCalculator`.
11. Create `race_results` with `attempt_type = pvp` and `pvp_race_id`.
12. Set both `race_attempts.race_result_id` and `pvp_races.race_result_id` to the new result id.
13. Apply challenger condition damage only.
14. Mark the attempt succeeded and commit.

If validation fails after a new attempt is created, mark the attempt failed so the same idempotency key cannot be silently retried with different server state.

---

## Application services

### Shared race attempt handling

Create a small service around `RaceAttempt` that owns:

- resolving or creating attempts by `(user_id, idempotency_key)`
- conflict checks when the same key is reused for a different race target
- replay / pending / failed / expired behavior
- marking pending attempts failed after validation errors

NPC and PvP services should both use this service. This avoids two implementations of the same idempotency contract.

### `PvpRaceService`

Responsibilities:

- Validate opponent and active cars.
- Orchestrate the PvP transaction.
- Build snapshots.
- Enforce same-pair daily cap.
- Spend challenger fuel and damage challenger car.
- Create `pvp_races` and `race_results`.

`RaceService` remains responsible for NPC races. Shared scoring should stay in `RaceScoreCalculator`; shared attempt handling should live outside `RaceService`.

---

## HTTP surface

Suggested routes:

| Method | Route | Purpose |
| --- | --- | --- |
| GET | `/pvp` | Opponent list |
| POST | `/pvp/{defender}` | Start instant PvP race |
| GET | `/pvp/results/{raceResult}` | Challenger result page |
| GET | `/pvp/history` | Defender read-only history |

Opponent list:

- Include users with an active car.
- Exclude the authenticated user.
- Paginate by username or recent creation; matchmaking is out of scope.

---

## Phase 4b implementation checklist

### Risk-reduction first

- [ ] Add this ADR.
- [ ] Extract shared race-attempt/idempotency handling from `RaceService`.
- [ ] Add `pvp_races` schema/model and relationships so result links have a clear shape.

### PvP service

- [ ] `PvpRaceService` transaction flow.
- [ ] Snapshot builder using `CarStatAggregator`.
- [ ] Same-pair daily cap of `10` unordered pair races per app-timezone day.
- [ ] PvP result scoring from snapshot stats.

### HTTP and UI

- [ ] `PvpRaceController` index, store, show, history.
- [ ] Start request with idempotency key validation.
- [ ] Opponent list view.
- [ ] PvP result/history views.
- [ ] Navigation link.

### Tests

- [ ] Feature: successful PvP start.
- [ ] Feature: self-race blocked.
- [ ] Feature: opponent without active car blocked.
- [ ] Feature: defender spends no fuel and takes no condition damage.
- [ ] Feature: no PvP economy rewards.
- [ ] Feature: same-pair daily cap blocks the 11th mixed-direction race.
- [ ] Feature: snapshots remain stable after later garage/tuning changes.
- [ ] Integration: same idempotency key concurrency.
- [ ] Integration: different keys with fuel for only one race.

---

## Explicitly deferred

- Matchmaking and rating-based opponent suggestions.
- Challenge inbox, accept/decline flows, and notifications.
- Ranked ladders, wagers, leaderboards, missions, club points, and tournament integration.
- Advanced win-trading or smurf detection beyond the MVP same-pair daily cap.
