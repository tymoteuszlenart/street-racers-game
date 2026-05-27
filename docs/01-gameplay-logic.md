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
- PvP challenge
- Club tournament race
- Daily event race

Later race types:

- Pink slip race
- Boss race
- Drag race
- Circuit race
- Time trial
- Underground illegal event

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

