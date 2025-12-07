# DeliverEase Security Review

## Scope
- Reviewed authentication flow (`routes/sso-auth.php`, `app/Http/Controllers/Auth/LoginController.php`, login view).
- Audited Livewire components for authorization and state handling (Dashboard, Runs components, Driver flows).
- Checked policy usage, route middleware, import/export surfaces, seed data, and key security-related config defaults.

## Findings

### High
- Missing login throttling: `app/Http/Controllers/Auth/LoginController.php:18-34` authenticates without any rate limiting or lockout, so credential stuffing/brute-force attempts are unbounded. Add a login rate limiter (e.g., `RateLimiter::for('login', ...)` plus `->middleware('throttle:login')` on the POST route) and surface lockout messaging.
- Weak/unthrottled driver PIN: Driver access relies on a 4-digit PIN stored in plaintext (`app/Models/Run.php` fillable) and displayed in share modals (`resources/views/livewire/runs/index.blade.php`). `app/Livewire/Driver/AccessRun.php:21-30` accepts unlimited attempts with no delay or logging, making brute-force feasible if a link leaks. Use a longer PIN or token, hash it at rest (check with `Hash::check`), and throttle attempts per run + IP with lockout/alerting.
- PIN gate bypass on Livewire postbacks: The driver page checks the session flag only in `mount` (`app/Livewire/Driver/ActiveRun.php:14-22`), but actions (`startRun`, `completeDelivery`) do not re-check. A crafted Livewire POST without the session flag could operate on the run if a snapshot is obtained. Add a guard in each action (or a `booted`-style guard) that aborts when `session()->missing('driver_run_'.$this->run->uuid)` before mutating state.
- Import component missing authorization plumbing: `app/Livewire/Runs/Import.php` calls `$this->authorize()` without the `AuthorizesRequests` trait, causing a fatal error and, once fixed, there is still no authorization check in `import()`. Add the trait and re-authorize in the mutation path so only the owning business can import/update deliveries.

### Medium
- Session cookie hardening not enforced by config: `config/session.php` leaves `SESSION_SECURE_COOKIE`, `SESSION_SAME_SITE`, and `SESSION_ENCRYPT` to environment defaults. Ensure production sets `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=strict` (or `lax` if compatibility is needed), and HTTP-only already defaults to true. Consider HSTS and CSP at the web server / middleware layer.
- Driver share lifecycle: Driver links are long-lived UUIDs with static PINs; there is no expiry/rotation beyond manual regeneration. Consider expiring/rotating PINs after use or when runs complete, and optionally signing driver URLs to reduce risk if a link is forwarded.
- Seed data with known credentials: `database/seeders/TestDataSeeder.php` seeds `test@example.com` with password `secret` and runs with PINs `1234`/`5678`. Ensure this seeder is never used in non-development environments and remove test accounts before demos.

### Low / Observations
- Positive controls: Owner Livewire components generally enforce `RunPolicy` (`Index`, `Create`, `Edit`, `Show`, `Export`) and scope queries by `business_id`; mails are queued.
- Run export writes to a temp file and deletes after send; continue to keep the temp directory non-web-accessible.

## Recommendations
- Implement and test login throttling; add user-facing lockout feedback.
- Strengthen driver authentication: longer secrets, hashing at rest, throttling, and per-action session checks.
- Fix authorization on import and re-verify other Livewire mutations include server-side guards.
- Set secure session/env flags and server headers before client demos.
- Purge or isolate seeded credentials from any environment shown to clients.***
