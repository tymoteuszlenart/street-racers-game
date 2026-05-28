# Technical Plan

## Recommended Stack

Use Laravel and MySQL for the first version.

Recommended MVP stack:

- Laravel
- MySQL
- Blade
- Tailwind CSS
- Laravel queues using the database driver
- Laravel scheduler
- Laravel Breeze or built-in auth scaffolding

Redis is not required for the MVP. It can be added later for caching, queues, chat, and high-traffic leaderboards.

## Application Style

Start as a server-rendered browser game.

This keeps the project simpler:

- Faster MVP development
- Easier database-driven pages
- Easier auth and sessions
- Simple deployment
- Good fit for classic MMORPG browser gameplay

Interactive parts can be added later with:

- Alpine.js
- Livewire
- Laravel Echo, if realtime is needed

## Core Laravel Models

Suggested models:

- User
- PlayerProfile
- Car
- CarModel
- Part
- PartModel
- Race
- RaceAttempt
- RaceResult
- PvpRace
- Club
- ClubMember
- ClubTournament
- ClubTournamentEntry
- Transaction
- DailyReward

## Database Tables

Suggested core tables:

```text
users
player_profiles
car_models
cars
part_models
parts
races
race_attempts
race_results
pvp_races
clubs
club_members
club_tournaments
club_tournament_entries
transactions
daily_rewards
```

## Player Profile Fields

Suggested fields:

```text
user_id
cash
reputation
level
experience
fuel_current
fuel_max
fuel_updated_at
premium_fuel_current
premium_fuel_max
premium_fuel_claimed_at
active_car_id
```

Keep game-specific data separate from the default `users` table.

## Garage and Cars

Phase 2 introduces owned cars, the dealer, and the active car selection used by races.

### Table: `car_models`

`car_models` is the catalog of available cars. It is managed as game data and can be seeded for MVP.

Suggested fields:

```text
id
name
class                 D | C | B | A | S
rarity
image_path
power
acceleration
weight
grip
handling
durability
upgrade_slots        json, optional until tuning is implemented
price
starter              boolean
unlock_level
active               boolean
created_at
updated_at
```

Rules:

- Use fictional car names and legally safe image assets.
- `acceleration` is a first-class stat and should be included anywhere car performance is shown or calculated.
- `unlock_level` is the MVP dealer requirement. Reputation-based, class-based, or event unlocks can be added later.
- Starter cars are `class = D`, `starter = true`, low priced or free for initial assignment, and available at `unlock_level = 1`.

### Table: `cars`

`cars` stores player-owned car instances. A player can own multiple copies of the same `car_model`.

Suggested fields:

```text
id
user_id
car_model_id
nickname
condition_current
condition_max
acquired_via          starter | dealer | admin | reward
purchase_price        nullable, stored for audit
created_at
updated_at
```

Rules:

- `nickname` is required for every owned car.
- Default `condition_current` and `condition_max` to `100`.
- Clamp condition between `0` and `condition_max`.
- Repairs and race condition damage are not required to be functional in Phase 2, but the fields should exist so Phase 3 can apply damage without a schema redesign.
- Add indexes for `user_id` and `car_model_id`.

### Starter Car Flow

For MVP, a new player receives one starter car automatically.

Flow:

1. When a `PlayerProfile` is created, select the default active `car_models` row where `starter = true` and `unlock_level = 1`.
2. Create a `cars` row for the user with a generated nickname.
3. Set `player_profiles.active_car_id` to that owned car.
4. Do not charge cash for the automatic starter car.

The starter car should become active immediately so the dashboard, garage, and later race flows can assume the player has a usable car after registration.

### Dealer Rules

The dealer shows active car models where `unlock_level <= player_profiles.level`.

Purchase rules:

- Validate the car model is active and unlocked for the player's level.
- Validate the player has enough cash.
- Subtract the car price server-side.
- Create a new `cars` row with the required nickname.
- Allow buying the same car model multiple times.
- Do not automatically change the active car after dealer purchases unless the player has no active car.

Rotating dealer offers can be added later. Phase 2 can use the full unlocked catalog.

### Active Car Rules

`player_profiles.active_car_id` points to the currently selected owned car.

Rules:

- A player can only set a car they own as active.
- The first starter car is selected automatically.
- If the active car is ever removed or sold in a later phase, require selecting another owned car before racing.
- Race and PvP systems must read the active owned car from `player_profiles.active_car_id`, not from client-provided input.

### Garage Pages

Phase 2 pages:

- Garage: list owned cars and highlight the active car.
- Car detail: show nickname, model, class, image, stats, condition, and ownership metadata.
- Dealer: list unlocked car models and allow purchases.

Sell, repair, and tuning buttons may be visible as disabled or deferred actions until their later phases.

## Tuning and parts

**Authoritative design:** [ADR 001: Tuning parts — inventory, equipment, and stat aggregation](./adr/001-tuning-parts-data-model.md) (resolves GitHub issue #3).

Summary for implementers:

- **Catalog:** `part_models` — slot, rarity, stat bonuses, price, `unlock_level`, `min_car_class`, `active`.
- **Owned:** `parts` — `user_id`, `part_model_id`, nullable `car_id` (inventory vs equipped), denormalized `slot`, `acquired_via`, `purchase_price`.
- **Slots:** Eight `PartSlot` values; which slots a car supports come from `car_models.upgrade_slots` (JSON array; `null` = all slots).
- **Equip rules:** One part per slot per car; swap clears incumbent `car_id` before assigning the new part; equipping moves a part off any prior car; class and slot checks server-side; tuning shop requires player **level ≥ 5**.
- **Stats:** `CarStatAggregator` = base `car_models` stats + sum of `part_models` bonuses on equipped `parts`; `RaceService` and PvP snapshots must use this, not raw model stats.
- **Phase 4 MVP:** buy, equip, unequip only (no sell). See ADR checklist for files and migrations.

## Fuel Calculation

Fuel regeneration should happen when needed:

- When the dashboard loads
- Before starting a race
- Before showing fuel-sensitive actions

No background process is needed for every player.

Create a service class:

```text
FuelService
```

Responsibilities:

- Regenerate regular fuel from timestamp
- Check if player has enough fuel
- Spend fuel
- Refill fuel
- Claim daily premium fuel

## Race Calculation

Create a service class:

```text
RaceService
```

Responsibilities:

- Validate race entry
- Regenerate and spend fuel inside the same database transaction
- Calculate player score
- Calculate opponent score
- Determine winner
- Apply rewards
- Apply car condition damage
- Create race result record

Race calculation should be deterministic enough to test.

Use random values carefully and store the final values used in `race_results`.

## Race Execution and Concurrency

Race start must be atomic. A double-click or parallel request must not spend fuel twice, grant rewards twice, or apply inconsistent condition damage.

### Transaction flow

Every race start runs inside one MySQL transaction:

1. Resolve or create a `race_attempts` row from the idempotency key (`attempt_type = npc`, `race_id` set, `defender_user_id` null).
2. If the attempt already succeeded, return the stored result and stop.
3. If the attempt is pending, return a conflict response and stop.
4. Lock rows in a fixed order (see Lock order).
5. Regenerate fuel from `fuel_updated_at` and persist updated fuel values.
6. Validate race eligibility (fuel, active car, race unlock rules).
7. Spend fuel.
8. Calculate scores and determine the winner.
9. Apply cash, reputation, and XP to `player_profiles`.
10. Apply condition damage to the active `cars` row.
11. Insert `race_results` with `attempt_type = npc`, `race_id` set, `pvp_race_id` null, and related `transactions` rows.
12. Mark the `race_attempts` row as succeeded and store `race_result_id`.
13. Commit the transaction.

If any step fails, roll back the transaction and mark the attempt as failed when appropriate.

### Lock order

Always lock rows in this order to reduce deadlocks:

1. `race_attempts` (by idempotency key lookup or insert)
2. For **each distinct `user_id`** involved in the race, in **ascending `user_id` order**:
   - `player_profiles` for that user
   - Active `cars` row (`player_profiles.active_car_id`) for that user
3. `races` definition row (NPC only), if needed for validation

NPC races involve only the initiating player in step 2. PvP races involve challenger and defender; sorting by `user_id` prevents deadlock when A races B and B races A at the same time (both transactions lock the lower `user_id` first).

Use `SELECT … FOR UPDATE` on these rows inside the transaction. Currency fields (`cash`, `reputation`, `experience`, fuel) live on `player_profiles` for MVP; there are no separate balance tables.

### Idempotency

Clients send an idempotency key with every race start request (for example a UUID generated when the player clicks "Race"). The server stores attempts in a dedicated table.

Suggested table:

```text
race_attempts
  id
  user_id
  idempotency_key
  attempt_type        npc | pvp
  race_id             nullable, required when attempt_type = npc
  defender_user_id    nullable, required when attempt_type = pvp
  status              pending | succeeded | failed
  race_result_id      nullable, set on success
  error_code          nullable, set on failure
  created_at
  updated_at
  expires_at
```

Constraints and rules:

- Unique index on `(user_id, idempotency_key)`.
- TTL: expire or ignore keys older than 24 hours (configurable).
- One idempotency key maps to at most one race outcome.
- `race_id` is null for PvP attempts; `defender_user_id` is null for NPC attempts.
- Check constraint or application validation: `attempt_type = npc` implies `race_id` is set; `attempt_type = pvp` implies `defender_user_id` is set.

Duplicate request behavior:

| Attempt status | Behavior |
| --- | --- |
| `pending` | Return `409 Conflict` (or equivalent). Do not start a second transaction for the same key. |
| `succeeded` | Return the same `race_result` payload as the original request. Do not re-run the race. |
| `failed` | Allow retry only with a new idempotency key. Do not reuse a failed key for a new race. |

The first request for a key creates a `pending` row before locking player state. This prevents two parallel requests from both executing the race.

### Fuel inside the transaction

`FuelService` regeneration must run inside the race transaction, after `player_profiles` is locked and before fuel is spent. Do not regenerate fuel in a separate committed transaction and then spend it in another; that allows parallel requests to both see enough fuel.

### Rate limiting

Apply Laravel rate limiting to the race start endpoint:

- Key: authenticated `user_id` (not IP alone), implemented as `race-start:{userId}` via `RaceService::raceStartRateLimitKey()`.
- Limit: `config('game.race.start_rate_limit_per_minute')` (default **30** successful new starts per rolling 60 seconds).
- Return `429 Too Many Requests` when exceeded (HTML clients get `TooManyRequestsHttpException`; JSON clients get `Retry-After`).

**Semantics (NPC race start):**

- Only **new** race starts that commit successfully increment the counter (`RateLimiter::hit` runs after the DB transaction, and only when `replayed` is false).
- **Idempotent replays** of the same key (already `succeeded`) do not consume quota.
- **Failed** attempts (`validation_failed`, etc.) and **rate-limited** attempts that roll back do not increment the counter.
- Rate limiting is checked **inside** the race transaction (after the idempotency row is resolved) so a rejected start does not leave a dangling `pending` attempt.

Rate limiting complements idempotency; it does not replace it.

**Deployment (multi-instance):**

Laravel’s `RateLimiter` stores counters in the **default cache store** (`CACHE_STORE` in `.env`). With `file` or `array`, each app server maintains its own counters, so the effective limit is multiplied by the number of nodes. For production with more than one PHP worker or host, use a **shared** cache backend (typically **Redis**: `CACHE_STORE=redis` and a reachable `REDIS_*` connection) before relying on race-start throttling in live traffic.

### Testing (race concurrency)

See **Testing strategy** for layers, file layout, and how to run the MySQL suite.

Race concurrency must be verified with a MySQL-backed test (not SQLite alone):

- Two parallel race start requests with the same idempotency key: one succeeds, one returns the stored result or a conflict.
- Two parallel requests with different keys but insufficient fuel: only one race consumes fuel.

`RaceService` integration tests in `tests/Integration/` should cover double-submit and parallel-submit scenarios before closing the race execution work.

### Race results

Store one row per completed race (NPC or PvP). Both `race_attempts` and `pvp_races` may reference the same `race_results.id` on success; `race_attempts.race_result_id` is the canonical pointer for idempotent replay.

Suggested table:

```text
race_results
  id
  user_id                 challenger / initiating player
  attempt_type            npc | pvp
  race_id                 nullable, FK to races when attempt_type = npc
  pvp_race_id             nullable, FK to pvp_races when attempt_type = pvp
  won                     boolean, from initiating player's perspective
  player_score
  opponent_score
  score_breakdown         json, optional audit of formula inputs
  random_factor           stored value used in calculation
  created_at
  updated_at
```

Rules:

- NPC rows set `race_id`; PvP rows set `pvp_race_id`.
- Do not set both `race_id` and `pvp_race_id` on the same row.
- PvP rows still use `user_id` for the challenger so race history queries stay consistent.

## PvP Races (MVP)

Implement minimal PvP **after** the tuning shop (Phase 4) so snapshots include equipped parts and upgraded stats. See `docs/05-mvp-roadmap.md` Phase 4b.

MVP PvP uses a dedicated `pvp_races` table so challenge metadata, snapshots, and future PvP features stay separate from NPC `races` definitions.

### Product scope

Included in MVP:

- Direct instant race: challenger selects `defender_user_id`, race runs in one request
- Active car snapshots for challenger and defender at race start
- Server-side resolution via `RaceService` (or a thin `PvpRaceService` that delegates to it)
- Linked `race_results` row for history and UI
- Defender read-only PvP history list

Not included in MVP (later phase):

- Matchmaking, ranked ladders, wagers
- Defender accept/decline or pending challenge state
- Meaningful PvP economy rewards
- Leaderboard, reputation, daily mission, or club tournament integration
- Advanced anti-abuse beyond basic validation and optional pair cooldowns

### Table: `pvp_races`

```text
pvp_races
  id
  challenger_user_id
  defender_user_id
  challenger_car_id
  defender_car_id
  challenger_snapshot    json   frozen stats at race start
  defender_snapshot      json   frozen stats at race start
  race_result_id         nullable, set on success
  created_at
  updated_at
```

Snapshot JSON should include everything `RaceService` needs to score the race (for example power, acceleration, grip, handling, condition, equipped part bonuses, and any driver level bonus). Do not read live `cars` rows for scoring after the snapshot is written.

Constraints and validation:

- `challenger_user_id` must not equal `defender_user_id`
- Both users must have an active car at race start
- `defender_car_id` is the defender’s `active_car_id` at snapshot time (stored for audit; scoring uses `defender_snapshot`)

Indexes (suggested):

- `(challenger_user_id, created_at)` for race history
- `(defender_user_id, created_at)` for “raced against me” queries
- `(challenger_user_id, defender_user_id, created_at)` and `(defender_user_id, challenger_user_id, created_at)` or a generated pair key if enforcing the daily pair cap (see MVP anti-abuse)

### Opponent list (MVP)

The opponent picker is a simple paginated list, not matchmaking:

- Include all registered players except the challenger.
- Exclude players without an active car.
- Sort by recent activity or username (configurable); no rating-based ranking in MVP.
- Page size around 20–50; search by username is optional for MVP.

### Execution flow

Every PvP start runs inside one MySQL transaction, reusing the same concurrency patterns as NPC races where applicable. Follow the **duplicate-request behavior** table in Race Execution and Concurrency after step 1 (return stored result on `succeeded`, `409` on `pending`, and so on).

1. Resolve or create a `race_attempts` row with `attempt_type = pvp`, `defender_user_id`, and `race_id = null`.
2. If the attempt already `succeeded`, return the stored `race_result` and stop.
3. If the attempt is `pending`, return `409 Conflict` and stop.
4. Lock rows using **Lock order** (`race_attempts`, then both players’ profiles and active cars in ascending `user_id`). Defender rows are read for snapshots only; still lock them for consistency under concurrency.
5. Enforce same-pair daily cap (see MVP anti-abuse).
6. Regenerate fuel, validate fuel, spend fuel on the challenger only.
7. Build `challenger_snapshot` and `defender_snapshot` from the locked car rows (including equipped parts).
8. Insert `pvp_races` with snapshots.
9. Run race calculation from snapshots (not live car rows).
10. Insert `race_results` with `attempt_type = pvp` and `pvp_race_id`; set `race_attempts.race_result_id` and `pvp_races.race_result_id` to the same id.
11. Apply condition damage to the **challenger’s** car only; do not modify defender car condition.
12. Do **not** grant cash, reputation, or XP for MVP PvP unless explicitly enabled later behind config.
13. Mark `race_attempts` as `succeeded` and commit.

Idempotency keys apply to PvP start the same way as NPC race start.

### Service responsibilities

Suggested class:

```text
PvpRaceService
```

Responsibilities:

- Validate opponent (exists, not self, has active car)
- Build car stat snapshots
- Orchestrate transaction, fuel spend, and `RaceService` scoring
- Create `pvp_races` and `race_results` records
- Enforce same-pair daily cap (default on; see MVP anti-abuse)

`RaceService` should accept snapshot payloads for opponent scoring so PvP does not duplicate formula logic.

### Defender visibility (MVP)

- Challenger always lands on the PvP race result page after start.
- Defender does not need to be online; no push notification in MVP.
- Defender can open a read-only **PvP history** list (races where they were `defender_user_id`) with outcome and timestamp.

### MVP anti-abuse (minimal)

Until competitive PvP rewards exist, abuse surface is limited. Still enforce:

- Server-side snapshots only; never trust client stats
- Self-race blocked
- Idempotency on race start
- Rate limiting on PvP start endpoint (same pattern as NPC races)
- Same-pair daily cap: **enabled by default**, max **10** `pvp_races` **total** per unordered player pair per calendar day in `config('app.timezone')` (configurable). Count rows where the two users are challenger and defender in either direction (for example `WHERE (challenger_user_id, defender_user_id) IN ((a, b), (b, a))` or `LEAST(challenger_user_id, defender_user_id)` / `GREATEST(...)` with a shared pair key).

Defer win-trading, smurfing, and reward-farming rules until PvP grants economy or ranking value.

### Testing (PvP concurrency)

See **Testing strategy**. Add MySQL-backed concurrency tests in `tests/Integration/` (same requirement as NPC races):

- Parallel PvP start with the same idempotency key: one outcome, one replay or conflict.
- Parallel PvP starts with different keys when challenger has fuel for only one race: only one race consumes fuel.

## Economy Transactions

Use a transaction log for important currency changes.

Suggested table:

```text
transactions
  id
  user_id
  type
  currency
  amount
  balance_after
  source_type
  source_id
  created_at
```

This helps debug economy problems and payment issues.

## Payments

Payments should not be part of the first prototype.

When ready, use a real payment provider such as Stripe.

Payment flow should:

1. Create payment intent.
2. Wait for payment webhook.
3. Verify webhook signature.
4. Grant purchased item server-side.
5. Log transaction.

Never trust the browser to grant paid rewards.

## Admin Tools

The game will need admin tools early.

Useful admin features:

- Manage car models
- Manage part models
- Manage users
- Adjust player balances
- View race logs
- View transaction logs
- Configure events
- Configure club tournaments

For MVP, this can be simple Laravel CRUD behind an admin role.

## Scheduled Jobs

Use Laravel scheduler for:

- Daily reward reset
- Weekly club tournament closing
- Weekly club reward distribution
- Cleanup old notifications
- Rotating dealer offers

MySQL is enough for these jobs at MVP scale.

## Testing strategy

Automated tests protect core economy and race flows from regressions. Per-phase minimum tests are listed in `docs/05-mvp-roadmap.md`; this section defines layers, database policy, determinism, and suggested test classes.

### Test harness (Phase 1)

The project uses PHPUnit via Laravel:

- Default suite: `php artisan test` (SQLite in-memory, see `phpunit.xml`).
- Lint: `./vendor/bin/pint --test`.
- First **game** tests ship with Phase 3 (`FuelService`, `RaceService`), not Phase 1.
- Phase 1 is complete when auth/profile feature tests pass and the harness is documented in `README.md`.

Continuous integration (`.github/workflows/tests.yml`): the `tests` job runs `php artisan test` (SQLite) and Pint; the `integration` job runs `composer test:integration` against a MySQL 8.0 service container on every push/PR to `main`.

### Test layers

| Layer | Location | Purpose |
| --- | --- | --- |
| Unit | `tests/Unit/` | Pure service logic with mocked or in-memory dependencies where possible |
| Feature | `tests/Feature/` | HTTP endpoints, auth, validation, redirects, session |
| Integration | `tests/Integration/` | Full DB transactions, locking, idempotency, concurrency (MySQL) |

### Database policy

- **SQLite (default):** Most unit and feature tests. Fast, no external MySQL required for local dev; default CI job uses SQLite in-memory.
- **MySQL (required):** Race and PvP concurrency, row locking (`SELECT … FOR UPDATE`), and any behavior SQLite does not model reliably. Place these in `tests/Integration/` and extend `Tests\Integration\TestCase` (uses MySQL; see below).

Run suites:

```bash
# Default (SQLite) — Unit + Feature only
php artisan test

# MySQL integration / concurrency (separate config; does not run with default suite)
composer test:integration
```

Local MySQL for integration tests: prefer dedicated `street_racers_test` (see `AGENTS.md` setup). `phpunit.mysql.xml` sets default `DB_*` values; PHPUnit env vars override `.env.testing` for the same keys — edit `phpunit.mysql.xml` or drop those `<env>` entries and use `.env.testing` from `.env.testing.example` for custom credentials. A smoke test lives in `tests/Integration/MysqlConnectionTest.php`.

Before marking Phase 3 or 4b race work done, run both `php artisan test` and `composer test:integration`. Do not run integration tests in parallel against one shared MySQL database (PHPUnit `--parallel` can flake with database migrations and shared state).

### Determinism (races)

Race calculation should be deterministic enough to test:

- Inject or seed the RNG used inside `RaceService` in unit tests (fixed seed or mocked random source).
- Assert on stored audit fields in `race_results`: `random_factor`, `player_score`, `opponent_score`, and optional `score_breakdown` JSON.
- Do not assert on values the client could influence; build scores only from server-side models and snapshots.

### Service and flow coverage

**Unit — `FuelService` (`tests/Unit/FuelServiceTest.php`)**

- Regeneration from `fuel_updated_at` when time has passed
- Cap at `fuel_max`
- No regen above cap when already full
- Spend fuel succeeds and fails when insufficient
- Refill edge cases (paid refill when implemented)
- Premium daily claim increments and respects cap (Phase 7)

**Unit — `RaceService` (`tests/Unit/RaceServiceTest.php`)**

- Score calculation within documented bounds
- Win vs loss reward amounts (cash, reputation, XP)
- Condition damage applied to active car
- Fuel deduction amount matches race config
- Opponent scoring from snapshot payload (PvP)

**Unit — stat aggregation (`tests/Unit/CarStatAggregatorTest.php` or equivalent)**

- Base car model stats
- Equipped part bonuses
- Condition penalty on performance

**Unit — daily claim (`tests/Unit/DailyRewardServiceTest.php`)**

- First claim in period grants rewards
- Duplicate claim in same period is idempotent (no double grant)
- Claim after reset grants again

**Feature — NPC race (`tests/Feature/RaceStartTest.php`)**

- Authenticated player can start a race with valid idempotency key
- Insufficient fuel returns error without side effects
- Missing active car is rejected

**Integration — NPC race (`tests/Integration/NpcRaceConcurrencyTest.php`)**

- Full flow in one transaction: fuel down, cash/XP up on win, `race_results` row created, `transactions` logged
- Same idempotency key twice: second response returns stored result or `409` while pending
- Parallel starts with different keys and fuel for one race: only one consumes fuel

**Integration — PvP (`tests/Integration/PvpRaceConcurrencyTest.php`)**

- Same idempotency and parallel-fuel scenarios as NPC races
- Defender car condition unchanged; challenger fuel spent

**Feature — payments (Phase 8, `tests/Feature/PaymentWebhookTest.php`)**

- Valid webhook signature grants purchase once
- Replay of same event id does not double-grant
- Invalid signature does not grant

### Suggested file map

```text
tests/
  Unit/
    FuelServiceTest.php
    RaceServiceTest.php
    CarStatAggregatorTest.php
    DailyRewardServiceTest.php
  Feature/
    RaceStartTest.php
    PaymentWebhookTest.php
  Integration/
    TestCase.php              # MySQL connection, DatabaseMigrations
    MysqlConnectionTest.php   # harness smoke test
    NpcRaceConcurrencyTest.php
    PvpRaceConcurrencyTest.php
```

Add tests in the same PR as the service or endpoint they cover; do not defer Phase 3 concurrency tests past the race execution milestone.

## Security

Important rules:

- All race calculations must happen server-side.
- All currency changes must happen server-side.
- Validate fuel before every race, inside the same transaction that spends fuel.
- Use a single database transaction for the full race execution flow (see Race Execution and Concurrency).
- Require idempotency keys on race start and return the same result for duplicate submits.
- Do not trust client-provided car stats.
- Protect admin routes with roles.
- Rate-limit sensitive actions.

## Future Scaling

Add Redis later if needed for:

- Cache
- Queues
- Realtime chat
- Fast leaderboards
- Rate limits
- Session storage

Potential future additions:

- WebSockets for chat
- API for mobile app
- Separate worker server
- CDN for images
- Object storage for uploaded club logos

