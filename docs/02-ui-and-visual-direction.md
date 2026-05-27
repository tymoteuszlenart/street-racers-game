# UI and Visual Direction Plan

## UI Goal

The UI should feel like an underground racing dashboard: dark, photo-heavy, fast to understand, and built around the player's garage.

The game should work well as a classic browser MMORPG, so pages should be clear and quick rather than cinematic or overly complex.

## Recommended Style

Use a dark racing theme:

- Dark backgrounds
- Neon accent colors
- Garage, street, and night city imagery
- Card-based layouts
- Large car photos
- Clear stat blocks
- Strong call-to-action buttons

Suggested colors:

- Background: near black or dark gray
- Primary accent: red, orange, or electric blue
- Secondary accent: silver or neon green
- Success: green
- Warning: yellow or orange
- Danger: red

## Main Layout

Recommended layout:

- Top bar: cash, fuel, premium fuel, level, reputation, notifications
- Left sidebar: main navigation
- Main content: selected page
- Right panel, optional: active car, timers, online friends, club status

Main navigation:

- Dashboard
- Garage
- Race
- Tuning
- Dealer
- Market
- Club
- Rankings
- Profile

## Dashboard

The dashboard is the player's home screen.

It should show:

- Active car photo
- Current car stats
- Fuel status
- Premium fuel status
- Available races
- Daily missions
- Club tournament status
- Recent race results

Primary actions:

- Race now
- Upgrade car
- Repair car
- Visit dealer

## Garage UI

The garage should be the most important visual page.

Recommended elements:

- Large active car image
- Car name and class
- Power, acceleration, grip, handling, condition
- Equipped parts
- Upgrade buttons
- Repair button
- Sell button
- Set active car button

Garage card example:

```text
[Car Image]
Shadow GT
Class: C
Power: 210
Grip: 66
Condition: 92%

[Set Active] [Upgrade] [Repair]
```

## Tuning UI

Tuning should feel like RPG equipment.

Upgrade slots:

- Engine
- Turbo
- Tires
- Suspension
- Gearbox
- Brakes
- Nitrous
- ECU

Each slot should show:

- Current part image
- Part rarity
- Stat bonuses
- Upgrade cost
- Required level, if any

Part card example:

```text
[Engine Photo]
Street Engine Stage 2
Rarity: Sport
+45 Power
+8 Acceleration
Cost: $12,000

[Buy]
```

## Race UI

The race page should show race choices as cards.

Each race card:

- Opponent name
- Opponent car image
- Fuel cost
- Difficulty
- Prize
- Reputation reward
- Win chance indicator, optional

Example:

```text
[Opponent Car]
Night Runner
Difficulty: Medium
Cost: 10 fuel
Prize: $2,500
Reward: 20 reputation

[Start Race]
```

## Race Result UI

Race results should be exciting but fast.

Show:

- Win or loss
- Player car score
- Opponent score
- Rewards
- Fuel spent
- Car condition damage
- New XP or level progress

Later, this can include a short animated race timeline.

## Club UI

Club pages should make the game feel multiplayer.

Club page should show:

- Club name and logo
- Members
- Club level
- Club points
- Active tournament
- Club chat
- Weekly ranking

Club tournament page:

- Tournament timer
- Premium fuel required
- Club points earned
- Member contribution leaderboard
- Rewards table

## Photo and Asset Strategy

The game can use many photos, but assets must be legally safe.

Safe options:

- Licensed stock photos
- Original photography
- AI-generated images checked for consistency
- 3D renders
- Custom illustrations

Avoid:

- Copying car photos from existing games
- Using real manufacturer logos without permission
- Copying real car names as the main brand identity
- Scraping images from search engines

Recommended approach:

- Use fictional car names.
- Use fictional part brands.
- Keep real-world inspiration broad.
- Store image paths in the database so assets can be replaced later.

## Responsive Design

The game should work on desktop first, but mobile should still be usable.

Desktop:

- Sidebar navigation
- Multi-column cards
- Large garage view

Mobile:

- Bottom navigation or collapsed menu
- Single-column cards
- Smaller car previews
- Sticky top resource bar

## MVP Screens

First version screens:

- Login and register
- Dashboard
- Garage
- Dealer
- Tuning shop
- Race list
- Race result
- PvP opponent list (instant race, no accept step; after tuning shop is live)
- PvP race result
- PvP history (defender read-only list)
- Leaderboard
- Profile

Second version screens:

- Club overview
- Club tournament
- Club chat
- Marketplace
- Daily missions

