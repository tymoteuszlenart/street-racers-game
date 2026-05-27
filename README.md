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

# Run tests
php artisan test

# Lint (Pint)
./vendor/bin/pint --test
```
