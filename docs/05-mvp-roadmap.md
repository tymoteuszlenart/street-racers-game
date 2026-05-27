# MVP Roadmap

## MVP Goal

Build the smallest playable version of the street racing MMORPG.

The first version should prove that the main loop is fun:

```text
register -> get starter car -> race -> earn cash -> upgrade -> race stronger opponents
```

Do not start with every MMORPG feature. Add clubs, tournaments, marketplace, and payments after the core loop feels good.

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

## Phase 2: Garage and Cars

Deliverables:

- Car model data
- Starter car selection
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

## Suggested First Development Order

1. Laravel project and auth
2. Player profile
3. Dashboard layout
4. Car models and garage
5. Fuel service
6. Race service
7. Tuning shop
8. Leaderboard
9. Clubs
10. Premium fuel tournaments
11. Payments

## Not Included in MVP

These should wait:

- Realtime chat
- Marketplace
- Complex PvP matchmaking
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

