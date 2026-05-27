# Economy and Monetization Plan

## Economy Goal

The economy should make progress feel meaningful without making the game pay-to-win.

Players should earn money through racing, spend it on cars and upgrades, and return daily because fuel, tournaments, dealer offers, and club rewards create useful pacing.

## Currencies

Recommended currencies:

- Cash
- Reputation
- Regular fuel
- Premium fuel
- Premium credits, optional

Cash is the main upgrade currency.

Reputation is a progression and ranking currency.

Regular fuel limits normal race activity.

Premium fuel controls access to club tournaments and elite events.

Premium credits can exist later, but they are not required for the MVP.

## Cash

Cash sources:

- Normal races
- Daily missions
- Tournament rewards
- Club rewards
- Selling cars
- Selling parts, optional

Cash sinks:

- Buying cars
- Buying upgrades
- Repairs
- Race entry fees
- Garage slot expansion
- Marketplace fees

Cash must have enough sinks to prevent inflation.

## Reputation

Reputation represents player status.

Sources:

- Winning races
- Tournament performance
- Club achievements
- Daily missions
- Special events

Uses:

- Unlock race tiers
- Unlock dealer offers
- Unlock clubs or club roles
- Determine ranking

Reputation should be harder to farm than cash.

## Regular Fuel

Regular fuel is the normal activity limiter.

Rules:

- Regenerates over time.
- Used for normal races.
- Can be refilled with rewards or purchases.
- Should not be required for browsing, tuning, chatting, or managing the garage.

Suggested MVP values:

- Max fuel: 100
- Normal race cost: 10
- Regen: 1 fuel every 5 minutes
- Daily small refill reward: 20 fuel

Paid regular fuel is acceptable, but free players must still progress.

## Premium Fuel

Premium fuel should be special and controlled.

Uses:

- Club tournament races
- Elite events
- Special bosses
- High-value limited races

Sources:

- 1 free per day
- Weekly club rewards
- Event rewards
- Paid packs

Balance rules:

- Premium fuel gives entry attempts, not automatic wins.
- Tournament scoring should cap counted attempts.
- Free players should be able to participate regularly.

Recommended MVP:

```text
daily_free_premium_fuel = 1
free_storage_limit = 5
paid_storage_limit = 20
club_tournament_entry_cost = 1
```

## Repairs and Condition

Car condition creates a useful cash sink.

Each race should slightly reduce condition.

Example:

- Normal race: 1-3% condition loss
- High-risk race: 3-7% condition loss
- Tournament race: 2-5% condition loss

Low condition should reduce performance.

Example:

```text
condition >= 90%: no penalty
condition 70-89%: small penalty
condition 50-69%: medium penalty
condition < 50%: large penalty
```

Repair costs should scale with car class and damage.

## Cars and Upgrades Pricing

Pricing should create clear tiers.

Example car tiers:

```text
Class D: $2,000 - $10,000
Class C: $10,000 - $40,000
Class B: $40,000 - $150,000
Class A: $150,000 - $500,000
Class S: $500,000+
```

Upgrade prices should scale by slot, level, and rarity.

Example:

```text
Street part: cheap, early game
Sport part: moderate, mid game
Racing part: expensive, competitive
Elite part: rare, event or late game
Illegal part: powerful, limited or risky
```

## Real Money Purchases

Acceptable purchases:

- Regular fuel refills
- Premium fuel packs
- Cosmetics
- Garage themes
- Extra garage slots
- Battle pass or race pass
- Name change
- Club logo customization

Risky purchases:

- Direct best cars
- Direct best upgrades
- Unlimited ranked attempts
- Cash packs with no limits

If cash packs are added, keep them modest and less efficient than active play.

## Race Pass

A race pass can monetize without selling direct power.

Possible pass rewards:

- Cosmetics
- Fuel cans
- Premium fuel
- Special profile badges
- Garage backgrounds
- Limited but balanced parts

The free track should still include useful rewards.

## Anti-Pay-To-Win Rules

Recommended rules:

- Ranked and club tournaments count limited best attempts.
- Free players get daily premium fuel.
- Paid players can play more, but not bypass every limit.
- The best performance still requires good car building and progression.
- Cosmetics should be a major premium category.

## Economy Monitoring

Track these metrics:

- Average cash earned per player per day
- Average cash spent per player per day
- Fuel spent per day
- Premium fuel spent per day
- Race win rates by car class
- Upgrade purchase rates
- Tournament participation
- Paid refill usage

These numbers will show whether the economy is too slow, too generous, or too pay-heavy.

