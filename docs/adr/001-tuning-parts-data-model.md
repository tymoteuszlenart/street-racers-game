# ADR 001: Tuning parts — inventory, equipment, and stat aggregation

**Status:** Accepted  
**Date:** 2026-05-28  
**Resolves:** GitHub issue #3 (Design tuning inventory and equipment data model)  
**Implements:** `docs/05-mvp-roadmap.md` Phase 4 (Tuning Shop); prerequisite for Phase 4b (PvP snapshots)

## Context

The game loop requires buying parts, equipping them per car slot, and using **effective stats** in `RaceService`. The codebase already has `car_models.upgrade_slots` (nullable JSON) and race scoring on base model stats only. PvP (Phase 4b) must snapshot tuned stats at race start.

This ADR fixes naming, lifecycle, constraints, and a single stat aggregation path before implementation.

## Decision summary

| Topic | Decision |
| --- | --- |
| Catalog vs owned | `part_models` (catalog) + `parts` (player-owned instances), mirroring `car_models` / `cars` |
| Equipped state | `parts.car_id` nullable — `NULL` = inventory, set = equipped on that car |
| Same part on multiple cars | **No** — at most one `car_id` per part row |
| Equip consumes part | **No** — equip/unequip only changes `car_id` |
| Slot uniqueness | **One equipped part per slot per car** — enforced in app + DB unique on `(car_id, slot)` when `car_id` is set |
| Multiple copies of same catalog part | **Yes** — separate `parts` rows (e.g. two engines for two cars) |
| Stat source of truth | **`CarStatAggregator`** — base `car_models` stats + sum of equipped part bonuses; condition % separate |
| Cached stats on `cars` | **No** for MVP — compute on read inside transactions after row locks |
| Sell parts | **Out of Phase 4 MVP** — buy + equip + unequip only; sell can follow in a later phase |
| Tuning shop access | Player **`level >= 5`** (`docs/01-gameplay-logic.md`) |
| PvP snapshots | Store **aggregated race stats** (+ optional part audit list); never re-read live parts after snapshot |

---

## Tables

### `part_models` (catalog)

Game data; seeded for MVP. Admin-managed later.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | bigint PK | |
| `name` | string | Display name |
| `slot` | string | One of `PartSlot` enum (see below) |
| `rarity` | string | One of `PartRarity` enum |
| `image_path` | string, nullable | |
| `power_bonus` | unsigned small int | Default `0` |
| `acceleration_bonus` | unsigned small int | Default `0` |
| `grip_bonus` | unsigned small int | Default `0` |
| `handling_bonus` | unsigned small int | Default `0` |
| `price` | unsigned bigint | Cash price in shop |
| `unlock_level` | unsigned small int | Min **player** level to **purchase** |
| `min_car_class` | string(1) | Min **car** class to **equip** (`CarClass` enum) |
| `active` | boolean | Default `true` |
| `created_at` / `updated_at` | timestamps | |

Indexes: `(slot, active)`, `(unlock_level, active)`.

**Not in MVP:** `max_car_class`, reputation gates, event-only flags (add columns later without breaking equip flow).

### `parts` (owned instances)

| Column | Type | Notes |
| --- | --- | --- |
| `id` | bigint PK | |
| `user_id` | FK → `users` | Owner |
| `part_model_id` | FK → `part_models` | |
| `car_id` | FK → `cars`, nullable | `NULL` = inventory; set = equipped on this car |
| `slot` | string | **Denormalized** from `part_models.slot` at row creation (immutable) |
| `acquired_via` | string | `PartAcquiredVia` enum: `shop`, `reward`, `admin` |
| `purchase_price` | unsigned bigint, nullable | Audit for shop purchases |
| `created_at` / `updated_at` | timestamps | |

Indexes:

- `user_id`
- `car_id`
- Unique: **`(car_id, slot)`** — MySQL allows multiple rows with `car_id = NULL`; only equipped rows participate in uniqueness.

Foreign keys:

- `user_id` → `users`, cascade on delete
- `part_model_id` → `part_models`, restrict on delete
- `car_id` → `cars`, **null on delete** (if a car is removed later, parts return to inventory)

---

## Enums

### `PartSlot` (upgrade slot)

Aligned with `docs/01-gameplay-logic.md`. Use a backed string enum (`PartSlot::Engine = 'engine'`, etc.) — same lowercase snake-free style as `RaceAttemptType` / `TransactionType`.

Values:

`engine`, `turbo`, `tires`, `suspension`, `gearbox`, `brakes`, `nitrous`, `ecu`

`car_models.upgrade_slots` JSON, `parts.slot`, and `part_models.slot` must all use these exact strings (not display titles like `Engine`).

### `PartRarity`

`stock`, `street`, `sport`, `racing`, `pro`, `elite`, `illegal`

(Used for UI and future economy rules; does not change aggregation formula in MVP.)

### `PartAcquiredVia`

`shop`, `reward`, `admin`

### `TransactionType` (extend existing enum)

Add `TransactionType::PartPurchase = 'part_purchase'` (same PascalCase case + snake_case value pattern as `NpcRace = 'npc_race'`). Log via `TransactionService` on shop buy.

---

## `car_models.upgrade_slots`

JSON array of **slot names** this car model supports, e.g.:

```json
["engine","turbo","tires","suspension","gearbox","brakes","nitrous","ecu"]
```

Rules:

- If `upgrade_slots` is **`null`**, treat as **all eight slots** enabled (MVP default after seeder update).
- Equip validation: `part.slot` must be listed in the owning car’s `car_model.upgrade_slots` (resolved list).
- Seeder should set the full list on all current `car_models` rows (same lowercase strings as `PartSlot` enum values).

---

## Lifecycle: owned vs equipped

```text
buy (shop)     → parts row: user_id set, car_id NULL, slot copied from part_model
equip          → set parts.car_id = target car (see swap order below)
unequip        → set parts.car_id NULL
sell (later)   → delete part row + credit cash (not Phase 4)
```

Rules:

1. Only the **part owner** (`parts.user_id`) may equip/unequip.
2. Only the **car owner** may receive equipped parts (`cars.user_id` must match).
3. **One car per part row:** equipping sets `parts.car_id` to the target car and implicitly unequips that part from any other car (a single `UPDATE` is enough; no duplicate rows).
4. **Equip** must run in a **DB transaction** with `SELECT … FOR UPDATE` on `player_profiles`, target `cars`, the `parts` row being equipped, and the incumbent part in that `(car_id, slot)` if any.
5. **Slot swap order** (avoids unique `(car_id, slot)` violations): within the transaction, **first** set `car_id = NULL` on the incumbent part for that slot (if any), **then** set `car_id` on the part being equipped. Never assign the new part while the incumbent still holds the same `(car_id, slot)`.
6. **Buy** must run in a transaction: lock `player_profiles`, validate cash and `unlock_level`, deduct cash, insert `parts`, record `transactions`.
7. Client never sends stat bonuses — only `part_model_id` / `part_id` / `car_id`.

---

## Compatibility rules

| Rule | MVP behavior |
| --- | --- |
| Player level (shop) | `player_profiles.level >= part_models.unlock_level` to **buy** |
| Tuning shop page | `player_profiles.level >= 5` to access shop UI and buy endpoints |
| Car class (equip) | Car’s `car_models.class` **rank** must be `>= part_models.min_car_class` rank |
| Slot enabled | `part.slot` ∈ resolved `upgrade_slots` for that car model |
| Duplicate slot on car | Unique `(car_id, slot)`; service **unequips incumbent** (`car_id = NULL`) **then** equips new part (see lifecycle swap order) |
| Part already on another car | Allowed: equip moves `car_id` to the new car (rule 3 above) |

**Class rank** (implement on `CarClass` enum):

```text
D = 1, C = 2, B = 3, A = 4, S = 5
```

Example: `min_car_class = B` cannot equip on a Class D car.

---

## Stat aggregation (single source of truth)

### Service: `CarStatAggregator`

Location: `app/Services/CarStatAggregator.php`

**Input:** `Car` with `carModel` and equipped `parts` loaded (`parts` where `car_id = car.id`).

**Output:** race stat array (same shape `RaceService` uses today):

```php
[
    'power' => int,
    'acceleration' => int,
    'grip' => int,
    'handling' => int,
    'condition_percent' => float,
]
```

**Formula:**

```text
effective_stat = car_models.{stat}
               + sum(part_models.{stat}_bonus for each equipped part on this car)
```

Bonuses come from `part_models` via `parts.part_model_id` (equipped rows where `parts.car_id = cars.id`). The `parts` table does not store bonus columns.

for `stat` ∈ { power, acceleration, grip, handling }.

```text
condition_percent = (cars.condition_current / cars.condition_max) * 100
```

- **Weight / durability** are not used in `RaceScoreCalculator` today — part bonuses do not modify them in Phase 4.
- **Condition penalty** stays in `RaceScoreCalculator::conditionPenalty()` — not in the aggregator.

### Consumers

| Consumer | Usage |
| --- | --- |
| `RaceService::statsFromCar()` | Delegate to `CarStatAggregator` |
| Garage / tuning UI | Show base stats vs effective stats (optional `+N` from parts) |
| Phase 4b PvP snapshot | Persist `CarStatAggregator` output at race start |

### PvP snapshot JSON (Phase 4b — shape only)

Stored on `pvp_races.challenger_snapshot` / `defender_snapshot`:

```json
{
  "car_id": 12,
  "car_model_id": 3,
  "nickname": "Rusty",
  "condition_current": 88,
  "condition_max": 100,
  "stats": {
    "power": 58,
    "acceleration": 62,
    "grip": 51,
    "handling": 49,
    "condition_percent": 88.0
  },
  "equipped_parts": [
    { "part_id": 4, "part_model_id": 2, "slot": "engine", "name": "Street Block" }
  ]
}
```

`RaceService` scores PvP from `stats` only; `equipped_parts` is audit/UI.

---

## Application services and HTTP surface (Phase 4)

| Service | Responsibility |
| --- | --- |
| `TuningShopService` | List buyable `part_models`; `purchase(user, partModel)` |
| `PartEquipService` | `equip(user, part, car)` (incumbent `car_id = NULL` first, then assign); `unequip(user, part)` |
| `CarStatAggregator` | Effective race stats |

Suggested routes (names illustrative):

| Method | Route | Action |
| --- | --- | --- |
| GET | `/tuning` | Shop catalog (level ≥ 5) |
| POST | `/tuning/{partModel}` | Buy part |
| GET | `/garage/{car}/upgrades` | Slots, equipped, inventory fit for car |
| POST | `/garage/{car}/upgrades/{part}` | Equip |
| DELETE | `/garage/{car}/upgrades/{part}` | Unequip |

Policies: `PartPolicy` — user owns part; car belongs to user; equip rules in service.

---

## Phase 4 implementation checklist

Use this as the PR breakdown after this ADR merges. Check items off in the Phase 4 implementation PR(s).

### Schema and seed data

- [ ] Migration: `create_part_models_table`
- [ ] Migration: `create_parts_table` with unique `(car_id, slot)` and FKs above
- [ ] `PartSlot`, `PartRarity`, `PartAcquiredVia` enums
- [ ] `PartModel` and `Part` Eloquent models + factories
- [ ] `CarClass::rank(): int` helper
- [ ] `PartModelSeeder` — at least 2–3 parts per slot tier for testing; mix of rarities/classes
- [ ] Update `CarModelSeeder`: set `upgrade_slots` to full 8-slot array on all models
- [ ] Register seeder in `DatabaseSeeder`
- [ ] Extend `TransactionType` with `PartPurchase = 'part_purchase'`

### Domain services

- [ ] `CarStatAggregator` + `tests/Unit/CarStatAggregatorTest.php` (base, bonuses, condition %; no double-count)
- [ ] `TuningShopService::purchase()` — transaction, cash, `parts` insert, `TransactionService` log
- [ ] `PartEquipService::equip()` / `unequip()` — locks, slot swap, compatibility checks
- [ ] Refactor `RaceService::statsFromCar()` to use `CarStatAggregator`
- [ ] `tests/Unit/RaceServiceTest.php` — case: equipped parts increase score (fixed RNG)

### HTTP, auth, policies

- [ ] `PartPolicy` (`view`, `update` for equip/unequip)
- [ ] Middleware or controller guard: tuning routes require `playerProfile.level >= 5`
- [ ] `TuningShopController` — index, store (buy)
- [ ] `CarUpgradeController` (or extend `GarageController`) — show, equip, unequip
- [ ] Form requests for buy/equip validation (ids exist, owned, active catalog)

### UI

- [ ] `resources/views/tuning/index.blade.php` — catalog, buy forms, level gate message
- [ ] `resources/views/garage/upgrades.blade.php` (or extend `garage/show`) — 8 slots, equipped + inventory
- [ ] Replace disabled **Tune** on `garage/show` with link to upgrades (level ≥ 5)
- [ ] Nav: **Tuning** link when `level >= 5` (`layouts/navigation.blade.php`)
- [ ] Show effective stats on car detail (`CarStatAggregator` + optional delta display)

### Feature tests

- [ ] `tests/Feature/TuningShopPurchaseTest.php` — success, insufficient cash, below unlock_level, below player level 5
- [ ] `tests/Feature/PartEquipTest.php` — equip, unequip, swap slot, wrong owner, class too low, slot not on car model
- [ ] `tests/Feature/RaceStartTest.php` (extend) — race score reflects equipped part (win/loss path optional)

### Docs and issue closure

- [x] Link this ADR from `docs/04-technical-plan.md` (Tuning section) — done in PR #21
- [x] Open a **Phase 4: Tuning Shop implementation** GitHub issue referencing this ADR checklist — [#22](https://github.com/tymoteuszlenart/street-racers-game/issues/22)
- [ ] Merge ADR PR; **#3** closes on merge (`Closes #3` — design complete; sell deferred per decision summary)

### Explicitly deferred (not blocking #3 or Phase 4 “done”)

- [ ] Sell part / refund
- [ ] Repair integration with tuning UI
- [ ] Admin CRUD for `part_models`
- [ ] `max_car_class` / reputation gates on parts
- [ ] Part drops from race rewards

---

## Acceptance criteria (issue #3)

- [x] Owned vs equipped lifecycle documented (buy → inventory → equip → unequip; **sell** documented in lifecycle but deferred past Phase 4 MVP, matching issue scope for design-only closure)
- [x] Slot uniqueness and compatibility rules defined
- [x] Stat aggregation formula documented (`CarStatAggregator`)
- [x] Migration/schema sketch added (this ADR + technical plan link)

---

## Consequences

- **Positive:** One aggregation path for NPC races, UI, and future PvP snapshots; mirrors existing `cars` / `car_models` patterns.
- **Negative:** `slot` denormalized on `parts` — must be set only from `part_models` at insert; migrations must not allow changing `part_models.slot` on rows already owned without a data migration.
- **Follow-up:** Phase 4b implements `pvp_races` and snapshot builder calling `CarStatAggregator` inside the PvP transaction.

## References

- `docs/01-gameplay-logic.md` — Upgrades, level 5 tuning unlock
- `docs/04-technical-plan.md` — Tuning and parts (summary + link)
- `docs/05-mvp-roadmap.md` — Phase 4 / 4b
- `app/Services/RaceService.php` — `statsFromCar()` integration point
