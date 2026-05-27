# Street Racers Game

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
```

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
php artisan test --configuration=phpunit.mysql.xml

# Before merging race/PvP work (Phase 3 / 4b), run both commands above

# Lint (Pint)
./vendor/bin/pint --test
```

See `docs/04-technical-plan.md` (Testing strategy) and `docs/05-mvp-roadmap.md` for per-phase test requirements.

For non-default MySQL credentials, edit `phpunit.mysql.xml` or use `.env.testing` (see `.env.testing.example`) after removing the `DB_*` `<env>` entries from `phpunit.mysql.xml` — PHPUnit env vars take precedence over `.env.testing`.
