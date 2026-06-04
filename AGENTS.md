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

The development and integration-test databases and user are created with:

```bash
sudo mysql -u root -e "
  CREATE DATABASE IF NOT EXISTS street_racers;
  CREATE DATABASE IF NOT EXISTS street_racers_test;
  CREATE USER IF NOT EXISTS 'laravel'@'localhost' IDENTIFIED BY 'password';
  CREATE USER IF NOT EXISTS 'laravel'@'127.0.0.1' IDENTIFIED BY 'password';
  GRANT ALL PRIVILEGES ON street_racers.* TO 'laravel'@'localhost';
  GRANT ALL PRIVILEGES ON street_racers_test.* TO 'laravel'@'localhost';
  GRANT ALL PRIVILEGES ON street_racers.* TO 'laravel'@'127.0.0.1';
  GRANT ALL PRIVILEGES ON street_racers_test.* TO 'laravel'@'127.0.0.1';
  FLUSH PRIVILEGES;
"
```

`phpunit.mysql.xml` sets `street_racers_test` and other `DB_*` values by default. PHPUnit env vars override `.env.testing` for the same keys — change `phpunit.mysql.xml` (or remove those `<env>` entries and use `.env.testing` from `.env.testing.example`) for non-default MySQL credentials.

### Running the application

See `README.md` for standard commands. Key points:

- **Vite dev server** (`npm run dev`) must run alongside `php artisan serve` for CSS/JS hot reload during development.
- **Laravel dev server**: `php artisan serve --host=0.0.0.0 --port=8000`
- After pulling new code, always run `php artisan migrate` to apply pending migrations.

### Testing

- **CI**: GitHub Actions workflow `.github/workflows/tests.yml` runs on push to `main` and on all pull requests. The `tests` job runs `composer validate`, `composer audit`, `npm audit --audit-level=high`, `npm run build`, a `/up` health check, `php artisan test` (SQLite), and `./vendor/bin/pint --test`. The `integration` job starts MySQL 8.0, runs `php artisan migrate --force` against `street_racers_test`, then `composer test:integration` (no secrets required — credentials match `phpunit.mysql.xml`). Dependabot (`.github/dependabot.yml`) opens weekly update PRs for Composer, npm, and GitHub Actions. `.github/workflows/dependabot-auto-merge.yml` approves Dependabot PRs and enables squash auto-merge after CI passes for patch/minor updates and all `github-actions` bumps; semver-major Composer/npm updates stay manual.
- **Lint**: `./vendor/bin/pint --test`
- **Tests**: `php artisan test` (uses SQLite in-memory by default via `phpunit.xml`)
- **Integration tests**: `composer test:integration` (MySQL required locally; same suite runs in CI; extend `Tests\Integration\TestCase`). Do not use `php artisan test --configuration=…` — Collision always passes `phpunit.xml` and PHPUnit exits with a duplicate-configuration error.
- Before closing Phase 3 or 4b race work, run **both** `php artisan test` and `composer test:integration`.
- Per-phase minimum tests are in `docs/05-mvp-roadmap.md`; full strategy in `docs/04-technical-plan.md` (Testing strategy).

### Pull requests

When opening a pull request:

- Create a **normal (ready for review) PR**, not a draft PR.
- In the PR description, include `Closes #<issue-number>` (for example `Closes #7`) so GitHub automatically closes the linked issue when the PR merges into the default branch.
- Use one `Closes` line per issue when the PR fully resolves it; use `Refs #<issue-number>` if the PR only partially addresses an issue (for example documentation for #7 while CI remains a follow-up issue).

### Gotchas

- The `.env` file is not committed to git. Copy `.env.example` to `.env` and run `php artisan key:generate` if `.env` is missing.
- PlayerProfile is auto-created via a UserObserver when a new User is registered.
- The default starter cash for new players is $5,000 (set in the migration default).
- **Race start rate limits** use Laravel `RateLimiter` (see `docs/04-technical-plan.md`, Rate limiting). Production deployments with multiple app servers must use a shared cache (`CACHE_STORE=redis`); `file`/`array` only rate-limit per process.
