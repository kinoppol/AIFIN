# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

**AIPRO Contracts** — a working PHP 8 / MariaDB web app for selling prepaid "AI Pro" access as contracts. It was built from a Claude Design handoff (the original prototype lives in `project/`; see [README.md](README.md)). The app is framework-free plain PHP (no Composer/vendor) so it runs on stock XAMPP.

Domain rule that drives everything: **1 unit "M" = 30 days of access** (value fixed system-wide; only per-M price varies). Customers buy units into a **contract** (1-year term, extendable up to **+6 months** total), units sit in the contract wallet until **redeemed** against an email, which enqueues provisioning. Redeem amount is capped by both remaining units and remaining contract days (`contract_max_redeem()`).

**GPU rental** is a parallel resource on the same contracts (migration `003`): **1 unit "G" = one GPU card** (`contracts.gpu_total`/`gpu_remaining`). Cards come from dedicated GPU packages (`packages.kind='gpu'`) or are bundled free with an AI package (`packages.bonus_gpu`, admin-set). **1 card = 1 API key**: the customer requests a key (consumes a card), the admin provisions it with a **BASE URL + API key** (`api_keys` table), then the customer sees the active credentials; marking a not-yet-active key `failed` refunds the card. GPU logic lives in `ContractService` (`purchaseGpu`, `requestApiKey`, `provisionApiKey`, `setApiKeyStatus`); admin page is `admin/gpu`.

## Run / install

- Serve the repo root under Apache (`http://localhost/AIFIN/`) or via the dev server: `php -S 127.0.0.1:8000 -t .` (use full path `C:\xampp\php\php.exe` — `php` isn't on PATH).
- First run redirects to **`install.php`**. It is **re-runnable**: creates the DB, writes `config/config.php` (gitignored — holds DB creds + `app_key`), runs migrations, seeds packages + the admin account (credentials set during install), optional demo data, and has a "fresh install" wipe checkbox.
- Lint everything: `find . -name '*.php' -not -path './project/*' | while read f; do C:/xampp/php/php.exe -l "$f"; done`
- There is no automated test suite; verify by driving the HTTP flow (install → login → admin/customer actions).

## ⚠️ MariaDB thread-pool crash (important)

XAMPP's MariaDB 10.4 on Windows defaults to `thread_handling=pool-of-threads`, which **intermittently segfaults on DDL** (`CREATE TABLE`/`ALTER`) run over a client connection — the crash stack is in `pool_of_threads_scheduler`/`tp_callback`. This can make `install.php` or a migration run fail with **"MySQL server has gone away"** and leave an orphaned `data/<db>/*.ibd` tablespace (which then blocks `DROP DATABASE` with "directory not empty").

Recovery: restart mysqld, delete the orphaned `.ibd`, `DROP DATABASE`, retry — it usually succeeds on retry since it's timing-dependent. A permanent fix is setting `thread_handling=one-thread-per-connection` in `my.ini`, but that edits the user's server config — **ask first.**

## Architecture

Front controller `index.php` → `App\Core\Router` (matches `$_SERVER['PATH_INFO']`, so URLs look like `/AIFIN/index.php/admin/contracts` — **no mod_rewrite needed**; build links with the `url()` helper). `app/bootstrap.php` wires the autoloader (`app/helpers.php`), loads config, starts the session, and returns whether the app is installed.

Layers (all under `app/`):
- **Core/** — `Router`, `Controller`, `View` (plain-PHP templates + layouts), `Model` (tiny active-record base), `Database` (PDO singleton), `Config`, `Auth` (session), `Csrf`, `Migrator`.
- **Models/** — thin table classes (`User`, `Contract`, `Package`, `UnitLedger`, `Redemption`, `ExtensionRequest`, `Setting`).
- **Services/ContractService.php** — **all business rules live here**, inside transactions: `purchase`, `redeem`, `requestExtension`/`approveExtension`/`rejectExtension`, redemption status. Enforces unit balances, ledger consistency, and the 6-month extension cap (over-quota requests are flagged/blocked, not accepted). Controllers stay thin — put new domain logic here.
- **Controllers/** — `LandingController`, `AuthController`, `Customer\AccountController`, `Admin\*` (Dashboard, Contract, Wallet, Redeem, Extension, Package, Migration).
- **Views/** — `layouts/app.php` is the admin shell (the default layout, used only by admin); landing/customer/auth use their own layouts. `partials/head.php` and `partials/flash.php` are shared. Design tokens are in `assets/css/app.css` (ported from the prototype's CSS variables, incl. light/dark).

Record IDs are passed as `?id=` query params (the router matches only the static path). CSRF: every POST form includes `csrf_field()` and its controller calls `Csrf::verify()`.

## Database & migrations

Schema is defined by numbered files in `migrations/` (`NNN_description.php` returning `['name'=>…, 'up'=> string|string[]|callable(PDO)]`). Applied files are tracked in the `migrations` table, so the runner is idempotent. **To change schema for the future: add a new `NNN_*.php` file, then apply it via the Admin → "Migrations ฐานข้อมูล" menu (or re-run `install.php`).** Never edit an already-applied migration — add a new one.

## Conventions

- UI copy and seed data are **Thai**; keep new user-facing strings Thai to match.
- Helpers in `app/helpers.php`: `url()`, `asset()`, `e()`, `config()`, `csrf_field()`, `units()`, `baht()`, `thai_date()` (Buddhist-era), `pill()`/`status_pill()` (status → CSS pill + Thai label).
- `project/` is the original design reference (read-only handoff bundle) — not part of the running app.
