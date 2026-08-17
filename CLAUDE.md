# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

**AIPRO Contracts** — a working PHP 8 / MariaDB web app for selling prepaid "AI Pro" access as contracts. It was built from a Claude Design handoff (the original prototype lives in `project/`; see [README.md](README.md)). The app is framework-free plain PHP (no Composer/vendor) so it runs on stock XAMPP.

Domain rule that drives everything: **1 unit "M" = 30 days of access** (value fixed system-wide; only per-M price varies). Customers buy units into a **contract** (1-year term, extendable up to **+6 months** total), units sit in the contract wallet until **redeemed** against an email, which enqueues provisioning. Redeem rules: at most **`app.max_redeem_units` (12) units per request** (`contract_max_redeem()`); the access clock (units × 30 days) starts when the admin **provisions** the redemption (`expires_at` set then, not at request), so a seat can outlive the contract; once the contract **expires** no more units may be redeemed but already-provisioned access runs to completion.

**Payment/approval** (migration `007`): a customer-created contract starts `payment_status='unpaid'` (รอการชำระเงิน) — units sit in the wallet but redeem/API-key are blocked. The customer views a **quotation**, then notifies payment with a proof file (`payments` table, stored under `storage/uploads/payments/`, served auth-gated via `account/proof`) → `submitted`. An admin verifies on `admin/payments` and approves → `paid` (usable) or rejects → back to `unpaid`. Admin-created contracts and the demo seed pass `'paid'`. `contract_status_pill()` surfaces the payment stage in listings. Logic: `ContractService::{submitPayment, approvePayment, rejectPayment}`.

**Registered emails** (migrations `009`/`010`): a customer must register the emails that may be bound to an AI Pro seat (`customer_emails`, unique per user+email; managed at `account/emails` — search, edit, ระงับ/เปิดใช้งาน via `status`, delete). Redeem forms — customer modal and the admin "แลกหน่วยแทนลูกค้า" form — only offer `status='active'` addresses, and `ContractService::redeem()` rejects anything else (`CustomerEmail::isRegistered()` also requires active). An address already used by a redemption can't be edited or deleted (suspend it instead). A customer may also restrict registration to their organisation's domains (`customer_email_domains`, migration `014`): while the list is empty any domain is accepted; otherwise the address must sit on a listed domain or one of its subdomains (`CustomerEmailDomain::allows()`, enforced when adding/editing an email).

**Monthly redeem cap** (migration `013`): the customer can cap how many units may be redeemed per calendar month per contract (`contracts.monthly_redeem_limit`, 0 = ไม่จำกัด; set on the contract page → `ContractService::setMonthlyRedeemLimit()`). `redeem()` enforces it against `Redemption::unitsInMonth()`, and `contract_max_redeem()` folds the month's remaining allowance into the per-request maximum shown in the UI.

**AI plans** (migration `012`): `ai_plans` lists the Claude-Pro-tier monthly plans (Claude Pro, ChatGPT Plus, Gemini AI Pro, …, seeded by the migration) that a redeemed seat is provisioned on. The customer picks one in the redeem form; `ContractService::redeem($contractId, $email, $units, $planId)` requires an **active** plan and snapshots `redemptions.plan_id` + `plan_name` (the name is kept even if the plan is later renamed/removed). Admin CRUD lives at `admin/plans` (`Admin\AiPlanController`) — add/edit/ระงับ/delete, with delete blocked once a redemption references the plan.

**Assistant users** (migration `011`): a customer can add helper logins at `account/team` (`users.parent_user_id` → owner, `users.status` gates login). Assistants sign in with their own email but work on the **owner's** data: `Auth::login()` stores `owner_id`/`owner_name`, and every customer-side query uses **`Auth::ownerId()` / `Auth::ownerName()` — never `Auth::id()`** (that's the rule to follow when adding customer features). `Auth::isAssistant()` hides team management; only the owner may add/edit/suspend/delete assistants (`Customer\TeamController`).

**Contract term start**: the 1-year term is counted from the day the admin **approves the payment** — `approvePayment()` rewrites `start_date`/`base_end_date`/`end_date` (re-applying `extension_months_used`). Dates written at purchase time are provisional and the customer page shows "รออนุมัติ" until then. Admin-created contracts (created `paid`) keep their creation date.

**GPU rental** is a parallel resource on the same contracts (migration `003`): **1 unit "G" = one GPU card**, and **1 G = 30 days of API access** (`contracts.gpu_total`/`gpu_remaining`). Cards come from dedicated GPU packages (`packages.kind='gpu'`) or are bundled free with an AI package (`packages.bonus_gpu`, admin-set). To get an **API key** the customer picks how many G to spend (default 1); the key spends that many cards (`api_keys.gpu_units`) and is valid for `gpu_units * 30` days (`api_keys.days`) **counted from the provision date** (`api_keys.expires_at`, migrations `005`/`006`). The admin provisions it with a **BASE URL + API key**, then the customer sees the credentials; marking a not-yet-active key `failed` refunds its cards. GPU logic lives in `ContractService` (`purchaseGpu`, `requestApiKey`, `provisionApiKey`, `setApiKeyStatus`); admin page is `admin/gpu`.

## Run / install

- Serve the repo root under Apache (`http://localhost/AIFIN/`) or via the dev server: `php -S 127.0.0.1:8000 -t .` (`php` isn't on PATH — use a full path; this checkout lives under `C:\xampp2\htdocs`, so prefer `C:\xampp2\php\php.exe`).
- First run redirects to **`install.php`**. It is **re-runnable**: creates the DB, writes `config/config.php` (gitignored — holds DB creds + `app_key`), runs migrations, seeds packages + the admin account (credentials set during install), optional demo data, and has a "fresh install" wipe checkbox.
- Lint everything (Bash tool, not PowerShell): `find . -name '*.php' -not -path './project/*' | while read f; do C:/xampp2/php/php.exe -l "$f"; done`
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
- **Controllers/** — `LandingController`, `AuthController`, `Customer\AccountController`, `Admin\*` (Dashboard, Contract, Wallet, Redeem, Extension, Package, Payment, ApiKey, Migration). The full route table lives in `index.php` — read it first to find which controller owns a URL.
- **Views/** — `layouts/app.php` is the admin shell (the default layout, used only by admin); landing/customer/auth use their own layouts. `partials/head.php` and `partials/flash.php` are shared. Design tokens are in `assets/css/app.css` (ported from the prototype's CSS variables, incl. light/dark).

Printable documents (quotation, receipt) render through `layouts/plain.php`; their markup lives in `Views/partials/{quotation,receipt}.php` and is shared by the on-screen page and the print view — edit the partial, not both pages.

Record IDs are passed as `?id=` query params (the router matches only the static path). CSRF: every POST form includes `csrf_field()` and its controller calls `Csrf::verify()`.

## Database & migrations

Schema is defined by numbered files in `migrations/` (`NNN_description.php` returning `['name'=>…, 'up'=> string|string[]|callable(PDO)]`). Applied files are tracked in the `migrations` table, so the runner is idempotent. **To change schema for the future: add a new `NNN_*.php` file, then apply it via the Admin → "Migrations ฐานข้อมูล" menu (or re-run `install.php`).** Never edit an already-applied migration — add a new one.

## Conventions

- UI copy and seed data are **Thai**; keep new user-facing strings Thai to match.
- Helpers in `app/helpers.php`: `url()`, `asset()`, `e()`, `config()`, `csrf_field()`, `units()`, `baht()`, `thai_date()` (Buddhist-era), `pill()`/`status_pill()` (status → CSS pill + Thai label).
- `project/` is the original design reference (read-only handoff bundle) — not part of the running app.
