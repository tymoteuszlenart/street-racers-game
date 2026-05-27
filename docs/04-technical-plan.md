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

1. Resolve or create a `race_attempts` row from the idempotency key.
2. If the attempt already succeeded, return the stored result and stop.
3. If the attempt is pending, return a conflict response and stop.
4. Lock rows in a fixed order (see Lock order).
5. Regenerate fuel from `fuel_updated_at` and persist updated fuel values.
6. Validate race eligibility (fuel, active car, race unlock rules).
7. Spend fuel.
8. Calculate scores and determine the winner.
9. Apply cash, reputation, and XP to `player_profiles`.
10. Apply condition damage to the active `cars` row.
11. Insert `race_results` and related `transactions` rows.
12. Mark the `race_attempts` row as succeeded and store `race_result_id`.
13. Commit the transaction.

If any step fails, roll back the transaction and mark the attempt as failed when appropriate.

### Lock order

Always lock rows in this order to reduce deadlocks:

1. `race_attempts` (by idempotency key lookup or insert)
2. `player_profiles` (for the authenticated user)
3. Active `cars` row (the player's `active_car_id`)
4. `races` definition row, if needed for validation

Use `SELECT … FOR UPDATE` on these rows inside the transaction. Currency fields (`cash`, `reputation`, `experience`, fuel) live on `player_profiles` for MVP; there are no separate balance tables.

### Idempotency

Clients send an idempotency key with every race start request (for example a UUID generated when the player clicks "Race"). The server stores attempts in a dedicated table.

Suggested table:

```text
race_attempts
  id
  user_id
  idempotency_key
  race_id
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

- Key: authenticated `user_id` (not IP alone).
- Suggested limit: 30 requests per minute per user (tune in config).
- Return `429 Too Many Requests` when exceeded.

Rate limiting complements idempotency; it does not replace it.

### Testing

Concurrency behavior must be verified with a MySQL-backed test (not SQLite alone):

- Two parallel race start requests with the same idempotency key: one succeeds, one returns the stored result or a conflict.
- Two parallel requests with different keys but insufficient fuel: only one race consumes fuel.

Integration tests for `RaceService` should cover double-submit and parallel-submit scenarios before closing the race execution work.

## PvP Races (MVP)

MVP PvP uses a dedicated `pvp_races` table so challenge metadata, snapshots, and future PvP features stay separate from NPC `races` definitions.

### Product scope

Included in MVP:

- Direct instant race: challenger selects `defender_user_id`, race runs in one request
- Active car snapshots for challenger and defender at race start
- Server-side resolution via `RaceService` (or a thin `PvpRaceService` that delegates to it)
- Linked `race_results` row for history and UI

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
- `(challenger_user_id, defender_user_id, created_at)` if enforcing a daily pair cap

### Execution flow

Every PvP start runs inside one MySQL transaction, reusing the same concurrency patterns as NPC races where applicable:

1. Resolve or create a `race_attempts` row from the idempotency key (same rules as NPC races).
2. Lock `player_profiles` for the challenger (and optionally check pair cooldown).
3. Regenerate fuel, validate fuel, spend fuel on the challenger only.
4. Load challenger active car and defender active car; build `challenger_snapshot` and `defender_snapshot`.
5. Insert `pvp_races` with snapshots.
6. Run race calculation from snapshots (not live car rows).
7. Insert `race_results` (and link `pvp_races.race_result_id`).
8. Apply condition damage to the **challenger’s** car only; do not modify defender car condition.
9. Do **not** grant cash, reputation, or XP for MVP PvP unless explicitly enabled later behind config.
10. Mark `race_attempts` succeeded and commit.

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
- Enforce optional same-pair daily cap (configurable)

`RaceService` should accept snapshot payloads for opponent scoring so PvP does not duplicate formula logic.

### MVP anti-abuse (minimal)

Until competitive PvP rewards exist, abuse surface is limited. Still enforce:

- Server-side snapshots only; never trust client stats
- Self-race blocked
- Idempotency on race start
- Rate limiting on PvP start endpoint (same pattern as NPC races)
- Optional: max N `pvp_races` per `(challenger_user_id, defender_user_id)` per calendar day

Defer win-trading, smurfing, and reward-farming rules until PvP grants economy or ranking value.

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

