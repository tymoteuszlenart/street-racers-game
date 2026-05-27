# AGENTS.md

## Cursor Cloud specific instructions

### System dependencies

This project requires PHP 8.3+, Composer, Node.js 22+, npm, and MySQL 8.0. These are **not** installed by the update script and must be present in the VM image or installed during initial setup.

### MySQL setup

MySQL must be started manually before running the application or tests:

```bash
sudo mkdir -p /var/run/mysqld && sudo chown mysql:mysql /var/run/mysqld
sudo mysqld --user=mysql &
```

Wait a few seconds for MySQL to start, then verify with `sudo mysql -u root -e "SELECT 1;"`.

The database and user are created with:

```bash
sudo mysql -u root -e "CREATE DATABASE IF NOT EXISTS street_racers; CREATE USER IF NOT EXISTS 'laravel'@'localhost' IDENTIFIED BY 'password'; GRANT ALL PRIVILEGES ON street_racers.* TO 'laravel'@'localhost'; FLUSH PRIVILEGES;"
```

### Running the application

See `README.md` for standard commands. Key points:

- **Vite dev server** (`npm run dev`) must run alongside `php artisan serve` for CSS/JS hot reload during development.
- **Laravel dev server**: `php artisan serve --host=0.0.0.0 --port=8000`
- After pulling new code, always run `php artisan migrate` to apply pending migrations.

### Testing

- **Lint**: `./vendor/bin/pint --test`
- **Tests**: `php artisan test` (uses SQLite in-memory by default via `phpunit.xml`)
- The technical plan (`docs/04-technical-plan.md`) specifies that race concurrency tests must run against MySQL, not SQLite. For those tests, override `DB_CONNECTION=mysql` in phpunit.xml or use a dedicated test suite.

### Gotchas

- The `.env` file is not committed to git. Copy `.env.example` to `.env` and run `php artisan key:generate` if `.env` is missing.
- PlayerProfile is auto-created via a UserObserver when a new User is registered.
- The default starter cash for new players is $5,000 (set in the migration default).
