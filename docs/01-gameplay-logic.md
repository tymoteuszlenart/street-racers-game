# Gameplay Logic Plan

## Core Concept

The game is a browser-based street racing MMORPG where players collect cars, upgrade parts, race opponents, join clubs, compete in tournaments, and climb rankings.

The main loop should be simple:

1. Player logs in.
2. Player checks garage, fuel, money, and available races.
3. Player enters races using fuel.
4. Race result gives cash, reputation, XP, and sometimes parts.
5. Player upgrades cars.
6. Stronger cars unlock better races, tournaments, and club activities.

## Player Progression

Each player should have:

- Level
- Experience points
- Cash
- Premium currency, optional
- Reputation
- Regular fuel
- Premium fuel
- Garage slots
- Active car
- Club membership

Level should unlock new content, but car strength should remain the main performance factor.

Example unlocks:

- Level 1: starter races, starter cars
- Level 5: tuning shop
- Level 10: clubs
- Level 15: tournaments
- Level 20: marketplace

## Fuel System

Regular fuel controls normal activity pacing.

Recommended MVP values:

- Max fuel: 100
- Fuel regeneration: 1 fuel every 5 minutes
- Normal race cost: 10 fuel
- Tournament race cost: 20 fuel, if not using premium fuel
- Full refill time: 8 hours 20 minutes

Fuel should regenerate from timestamps, not from a constantly running process.

Suggested player fields:

```text
fuel_current
fuel_max
fuel_updated_at
```

When fuel is needed, calculate regeneration:

```text
minutes_passed = now - fuel_updated_at
fuel_to_add = floor(minutes_passed / 5)
fuel_current = min(fuel_max, fuel_current + fuel_to_add)
fuel_updated_at = fuel_updated_at + fuel_to_add * 5 minutes
```

## Premium Fuel

Premium fuel is a special resource for club tournaments, elite events, and high-value races.

Recommended rules:

- Used for club tournament entries.
- Does not regenerate like regular fuel.
- Players receive 1 free premium fuel per day.
- Can be earned from club rewards and special events.
- Can be purchased with real money.
- Should provide more attempts, not guaranteed wins.

Suggested MVP values:

- Free premium fuel storage: 5
- Paid premium fuel storage: 20
- Club tournament entry cost: 1 premium fuel
- Daily free premium fuel: 1

To avoid pay-to-win problems, club tournaments should count only a limited number of best results per player.

Example:

```text
Each player may race 20 times.
Only the best 10 results count for club points.
```

## Cars

Cars should use fictional names and original images or licensed assets.

Each car should have:

- Class
- Power
- Acceleration
- Weight
- Grip
- Handling
- Durability
- Upgrade slots
- Rarity
- Price

Example classes:

- D: starter cars
- C: street cars
- B: sport cars
- A: elite cars
- S: exotic or illegal race cars

## Upgrades

Upgrade slots:

- Engine
- Turbo
- Tires
- Suspension
- Gearbox
- Brakes
- Nitrous
- ECU

Each part should affect race stats.

Example:

```text
Engine: increases power
Turbo: increases acceleration
Tires: increases grip
Suspension: improves handling
Gearbox: improves launch and top speed
Brakes: improves consistency and safety
Nitrous: adds burst performance
ECU: small bonus to multiple stats
```

Part rarity:

- Stock
- Street
- Sport
- Racing
- Pro
- Elite
- Illegal

## Race Calculation

Race results should be understandable but not fully predictable.

Suggested formula:

```text
car_score =
  power * 0.35
  + acceleration * 0.25
  + grip * 0.20
  + handling * 0.10
  + driver_level_bonus
  + random_factor
  - condition_penalty
```

The random factor should be small enough that upgrades matter.

Recommended:

- Normal races: random factor around +/- 5%
- High-risk races: random factor around +/- 8%
- Ranked tournaments: random factor around +/- 3%

## Race Types

MVP race types:

- NPC street race
- PvP street race (instant, direct opponent)
- Club tournament race
- Daily event race

Later race types:

- Pink slip race
- Boss race
- Drag race
- Circuit race
- Time trial
- Underground illegal event

## PvP Races (MVP)

MVP PvP ships **after** the tuning shop so races reflect upgraded cars (equipped parts are included in snapshots). PvP is a **direct, instant** race against another player. There is no challenge inbox, accept/decline step, or defender confirmation.

Flow:

1. Challenger picks another player from a simple opponent list (all players with an active car, excluding self).
2. Server reads both players’ **active cars** (including equipped parts) and snapshots stats at race start.
3. Server resolves the race immediately (same calculation approach as NPC races).
4. Challenger sees the result; defender is not required to be online.
5. Defender can later view a read-only **PvP history** of races run against them (no notification in MVP).

MVP rules:

- Challenger spends regular fuel (same cost as a normal street race unless tuned later).
- Defender does **not** spend fuel and their car does **not** take condition damage.
- Both cars’ effective stats are frozen in snapshots at race start; later garage or tuning changes do not alter the result.
- Self-races are not allowed.
- PvP grants **no meaningful economy rewards** in MVP (no cash, reputation, or XP from PvP wins). Use PvP to test racing and social competition only.
- PvP results do **not** affect leaderboards, daily missions, club points, or club tournaments in MVP.
- Same-pair daily cap: **5 races per challenger/defender pair per calendar day** in the app timezone (enabled by default) to reduce spam while rewards are disabled.

Deferred to a later PvP phase:

- Matchmaking and opponent suggestions by rating
- Challenge/accept flows and notifications
- Ranked ladders, wagers, and competitive PvP rewards
- Win-trading prevention, smurf detection, and pair-based reward farming caps
- Club tournament or premium-fuel PvP modes

## Clubs

Clubs are the main MMORPG social system.

Club features:

- Club profile
- Members
- Club chat
- Club garage or bonuses
- Club tournaments
- Club rankings
- Weekly rewards

Club roles:

- Owner
- Manager
- Member

Club tournament scoring should reward participation and quality.

Example:

```text
win = 3 club points
loss = 1 club point
perfect race bonus = 2 points
only best limited results count
```

## Daily Retention

Daily systems:

- Daily login reward
- Daily free premium fuel
- Daily race missions
- Weekly club tournament
- Rotating car dealer offers
- Limited event parts

Daily rewards should help free players stay competitive without removing reasons to upgrade or play regularly.

