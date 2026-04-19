# Developer Guide

## Project Layout
- `app/`: core runtime, auth, DB connector, repositories, rendering helpers
- `app/repositories/`: query and domain logic by module
- `app/views/layouts/`: layout templates (`app`, `auth`, `landing`, `error`)
- `public/`: web root and route-like PHP page controllers
- `public/api/`: JSON API endpoints
- `database/migrations/`: incremental SQL migrations
- `docs/`: technical documentation

## Local Startup
1. Import schema and seed:
```bash
mysql -h 127.0.0.1 -P 3306 -u mobilis_app -p mobilis_db < mobilis_sql.sql
```
2. Configure environment variables (or `.env`).
3. Start app:
```bash
php -S localhost:8000 -t public
```

## Coding Conventions in This Repo
- Function-first PHP style (`if (!function_exists(...)) { ... }`).
- Repository functions return arrays; page controllers orchestrate UI concerns.
- Defensive DB fallback is present in many read functions.

## Common Extension Points
- Add new module repository under `app/repositories/` and include it in `app/repository.php`.
- Add page in `public/RoleName/` and gate with `requireAuth([...])`.
- Add navigation entries via `navSections()` in `app/view_helpers.php`.
- Add schema changes via new migration files and reflect in `mobilis_sql.sql` if baseline should include them.

## Quality and Verification Checklist
- Syntax checks:
```bash
php -l app/repositories/<file>.php
php -l public/<file>.php
```
- DB connectivity smoke test:
```bash
php -r 'require "app/bootstrap.php"; var_export(dbConnected());'
```
- Basic route checks (with server running):
```bash
curl -i http://127.0.0.1:8000/login.php
curl -i http://127.0.0.1:8000/api/tracking.php
```

## Known Gaps / Follow-Ups
- CSRF protections are not yet added for all write forms.
- Some columns are maintained with runtime DDL guards; a stricter migration policy may be preferred for production.
