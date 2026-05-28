# MVP Roadmap

## MVP Goal

Build the smallest playable version of the street racing MMORPG.

The first version should prove that the main loop is fun:

```text
register -> get starter car -> race -> earn cash -> upgrade -> race stronger opponents
```

Do not start with every MMORPG feature. Add clubs, tournaments, marketplace, and payments after the core loop feels good.

## Testing and “done”

Each phase below includes **minimum automated tests** required before the phase is considered done. Full coverage targets, file names, and MySQL vs SQLite policy are in `docs/04-technical-plan.md` (**Testing strategy**).

```bash
php artisan test                                    # default suite (SQLite)
composer test:integration  # MySQL concurrency / DB integration
```

## Phase 1: Project Foundation

Deliverables:

- Laravel project
- MySQL database
- Authentication
- Base layout
- Dark racing theme
- Player profile creation
- Basic dashboard

Key pages:

- Register
- Login
- Dashboard
- Profile

Done when:

- A new user can register.
- A player profile is created automatically.
- The dashboard shows player cash, level, fuel, and active car area.
- `php artisan test` passes (auth, registration, profile feature tests).
- `README.md` documents how to run tests and Pint.

## Phase 2: Garage and Cars

Deliverables:

- Car model data
- Automatic starter car assignment
- Player garage
- Active car selection
- Car condition

Key pages:

- Garage
- Car detail
- Dealer

Done when:

- Player can receive or buy a starter car.
- Player can view owned cars.
- Player can set an active car.
- Feature tests cover starter car assignment on registration and setting active car (authorized owner only).
- Dealer purchase feature test: insufficient cash rejected; successful purchase creates `cars` row and deducts cash.

## Phase 3: Fuel and Basic Races

Deliverables:

- Regular fuel system
- Fuel regeneration from timestamp
- NPC race list
- Race calculation
- Race result page
- Cash and XP rewards
- Condition damage

Key pages:

- Race list
- Race result

Done when:

- Player can spend fuel to race.
- Fuel regenerates over time.
- Race result is stored.
- Player receives rewards after a win.
- Unit tests: `FuelService` (regen, cap, spend); `RaceService` (scores, rewards, condition damage, fuel cost) with deterministic RNG.
- Feature test: race start with valid idempotency key; insufficient fuel and missing active car rejected.
- Integration tests (MySQL): full NPC race transaction; duplicate idempotency key; parallel starts with fuel for only one race.

## Phase 4: Tuning Shop

Deliverables:

- Part model data
- Upgrade slots
- Buying upgrades
- Equipping upgrades
- Updated car performance score

Key pages:

- Tuning shop
- Car upgrades

Done when:

- Player can buy and equip parts.
- Upgrades affect race performance.
- Upgrade costs remove cash correctly.
- Unit test: stat aggregation reflects equipped parts and condition penalty.
- Feature tests: buy/equip flow; race score changes when parts are equipped (via `RaceService` or HTTP race start).

## Phase 4b: Minimal PvP Races

Start after Phase 4 so snapshots include equipped parts and tuned stats.

Deliverables:

- `pvp_races` table and model
- `race_attempts` and `race_results` PvP fields (`attempt_type`, `defender_user_id`, `pvp_race_id`)
- Opponent list (simple player picker, not matchmaking)
- Instant PvP race start (no accept/decline)
- Active car snapshots for challenger and defender (including equipped parts)
- PvP race result page linked to `race_results`
- Defender read-only PvP history (“raced against me”)
- Reuse `RaceService` for scoring from snapshots

Key pages:

- PvP opponent list
- PvP race result
- PvP history (defender view)

Done when:

- Challenger can race another player’s active car in one action.
- Both cars are snapshotted at race start (with current upgrades); result does not change if either player changes garage later.
- Challenger spends fuel; defender does not spend fuel and takes no condition damage.
- PvP grants no meaningful cash, reputation, or XP in MVP.
- PvP does not affect leaderboards, daily missions, or club tournaments.
- Same-pair daily cap is enforced (default **10 races total** per two-player pair per calendar day in app timezone; both directions count toward the limit).
- Integration tests (MySQL): PvP idempotency and parallel-fuel scenarios; defender condition unchanged.
- Feature test: same-pair daily cap returns error after 10 races between two users (including mixed challenger/defender roles).

## Phase 5: Progression and Rankings

Deliverables:

- Player levels
- Reputation
- Leaderboard
- Race history
- Daily rewards

Key pages:

- Rankings
- Race history
- Daily rewards

Done when:

- Players can compare rankings.
- Race results are visible.
- Daily rewards encourage returning.
- Unit test: `DailyRewardService` first claim grants; duplicate claim in same period is idempotent.
- Feature tests: leaderboard ordering; race history lists stored results.

## Phase 6: Clubs

Deliverables:

- Club creation
- Club joining
- Club roles
- Club member list
- Club ranking

Key pages:

- Club overview
- Club members
- Club rankings

Done when:

- Player can create or join a club.
- Clubs can gain points.
- Clubs appear on rankings.
- Feature tests: create/join club; club points update from tournament or configured source; club ranking page reflects points.

## Phase 7: Premium Fuel and Club Tournaments

Deliverables:

- Premium fuel system
- Daily premium fuel claim
- Club tournament entries
- Tournament scoring
- Tournament rewards

Key pages:

- Club tournament
- Tournament result
- Tournament rewards

Done when:

- Player can claim daily premium fuel.
- Player can spend premium fuel on club tournament races.
- Club points are awarded.
- Weekly rewards can be distributed.
- Unit test: premium fuel daily claim (first claim, duplicate idempotent, cap).
- Feature tests: tournament entry spends premium fuel; club points and weekly reward distribution (scheduler or command covered by test).

## Phase 8: Monetization

Only start this phase after the game loop works.

Deliverables:

- Payment provider integration
- Paid fuel refill
- Premium fuel packs
- Purchase history
- Server-side payment webhooks
- Admin purchase visibility

Done when:

- Payment is verified by webhook.
- Rewards are granted server-side.
- Transactions are logged.
- Failed payments do not grant rewards.
- Feature tests: valid webhook signature grants once; replay does not double-grant; invalid signature does not grant; failed payment state does not grant.

## Suggested First Development Order

1. Laravel project and auth
2. Player profile
3. Dashboard layout
4. Car models and garage
5. Fuel service
6. Race service
7. Tuning shop
8. Minimal PvP races (`pvp_races`, snapshots, instant flow)
9. Leaderboard
10. Clubs
11. Premium fuel tournaments
12. Payments

## Not Included in MVP

These should wait:

- Realtime chat
- Marketplace
- Complex PvP matchmaking, ranked PvP ladders, and competitive PvP rewards
- PvP challenge accept/decline flows
- PvP wagers and economy farming controls (beyond the MVP same-pair daily cap)
- Animated race simulation
- Mobile app
- Redis
- Advanced anti-cheat systems
- User-uploaded images

## MVP Success Criteria

The MVP is successful if:

- A player understands what to do in under 1 minute.
- The race and upgrade loop feels rewarding.
- Fuel pacing creates return visits without feeling hostile.
- Free players can make visible progress.
- Club tournaments create competition.
- The admin can tune cars, parts, and rewards without code changes.

