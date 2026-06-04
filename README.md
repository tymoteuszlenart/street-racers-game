# Street Racers Game

[![Tests](https://github.com/tymoteuszlenart/street-racers-game/actions/workflows/tests.yml/badge.svg)](https://github.com/tymoteuszlenart/street-racers-game/actions/workflows/tests.yml)

Browser-based street racing MMORPG project built with Laravel, MySQL, Blade, and Tailwind CSS.

## Planning

Game design and implementation plans are available in [docs](./docs/README.md).

## Requirements

- PHP 8.3+
- Composer
- Node.js 22+ & npm
- MySQL 8.0+

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

The car catalog (`CarModelSeeder`) is required for starter cars on registration and the dealer.

`RaceSeeder` seeds three NPC street races — **Amateur**, **Semi-Pro**, and **Pro** — each with its own car stats and opponent driver stats.

After pulling changes that add columns or tables, run migrations:

```bash
php artisan migrate
```

## Game features (dev)

- **Racer profiles** — `/players/{user}` shows level, reputation, driver stats, active car, and club. Linked from rankings, PvP, clubs, and the dashboard.
- **Driver stats** — Force, Reaction, Control, and Technique add a weighted bonus to race scores. Level-ups grant **3 unspent stat points** to allocate on your own profile (`POST /players/stats`).
- **NPC races** — **Amateur**, **Semi-Pro**, and **Pro** are always available; opponents scale to your level’s expected build (tuning and upgrades improve your odds), with tier-specific target difficulty.
- **Score breakdown** — NPC, PvP, and tournament result pages show car base, driver bonus, luck, condition, and driver stats used (older results still show legacy `driver_level_bonus` as driver bonus).

## Development

```bash
# Start Vite dev server (CSS/JS hot reload)
npm run dev

# Start Laravel dev server
php artisan serve

# Run tests (default suite uses SQLite in-memory)
php artisan test

# MySQL integration tests (race concurrency, locking) — requires local MySQL
# Create street_racers_test first (see AGENTS.md), then:
composer test:integration

# Before merging race/PvP work (Phase 3 / 4b), run both commands above

# Lint (Pint)
./vendor/bin/pint --test
```

On every push to `main` and on pull requests, [GitHub Actions](https://github.com/tymoteuszlenart/street-racers-game/actions/workflows/tests.yml) runs Composer/npm audits, builds frontend assets, checks `/up`, runs the default test suite (SQLite) and Pint, plus a separate **integration** job that migrates MySQL and runs `phpunit.mysql.xml` (race concurrency, locking, idempotency). Dependabot proposes weekly dependency updates. Run the MySQL suite locally before merging race/PvP work if you change integration tests or database behavior.

See `docs/04-technical-plan.md` (Testing strategy) and `docs/05-mvp-roadmap.md` for per-phase test requirements.

For non-default MySQL credentials, edit `phpunit.mysql.xml` or use `.env.testing` (see `.env.testing.example`) after removing the `DB_*` `<env>` entries from `phpunit.mysql.xml` — PHPUnit env vars take precedence over `.env.testing`.

## Stripe (test mode)

Paid fuel packs use [Stripe Checkout](https://stripe.com/docs/checkout). Set test keys in `.env`:

```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

`STRIPE_WEBHOOK_SECRET` is required for webhook fulfillment (#35). For local webhook testing, forward events to the app:

```bash
stripe listen --forward-to http://localhost:8000/webhooks/stripe
```

Use the signing secret printed by `stripe listen` as `STRIPE_WEBHOOK_SECRET`. Checkout success/cancel URLs are `/shop/success` and `/shop/cancel`; rewards are granted only after a verified webhook, not on the success page alone.
