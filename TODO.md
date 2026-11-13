# Affi Campbellpur Rides (ACR App) — Build Checklist

## Phase 1: Project setup & foundation
- [x] Install latest stable Laravel in the project root.
- [x] Configure `.env.example`: Asia/Karachi timezone, database, database sessions, sync queue, and database/file cache.
- [x] Install and configure Tailwind CSS, Alpine.js, and Leaflet through Vite.
- [x] Build the mobile app shell, bottom navigation, touch-friendly buttons, cards, and toasts.
- [x] Add English and Urdu language files, RTL switching, and a cached `Setting` helper.
- [x] Configure `storage:link`.
- Completed: Laravel 13 foundation, locally bundled Tailwind/Alpine/Leaflet assets, shared mobile shell, locale switch, and storage symlink verified.

## Phase 2: Migrations, models, seeders
- [x] Add users, rider profiles, wallet transactions, rides, and settings migrations with required fields, constraints, and indexes.
- [x] Add models, relationships, decimal-safe casts, scopes (`online()`, `approved()`, `nearby($lat, $lng, $km)`), and factories.
- [x] Add settings defaults and local-only seed data: one super admin, five demo riders, and three demo passengers.
- [x] Run `migrate:fresh --seed` successfully.
- Completed: normalized schema, enum-backed model states, Haversine query scope, cached defaults, and local demo fixtures verified on SQLite.

## Phase 3: Authentication & roles
- [x] Implement phone/password registration, login, and logout with CSRF protection and password hashing.
- [x] Add role middleware and role-based post-login redirects.
- [x] Block suspended accounts and throttle login attempts.
- Completed: phone-based passenger auth, per-role dashboards, role isolation, suspended-account blocking, CSRF, password hashing, and login throttling verified.

## Phase 4: Rider module
- [x] Implement rider registration with validated private KYC file uploads.
- [x] Add KYC status screen and admin note.
- [x] Add dashboard with guarded online/offline toggle, wallet balance, today's earnings, and trip history.
- [x] Implement settings-based geolocation polling, movement threshold, wake lock, and clear GPS/offline error states.
- [x] Add wallet page, payment details/QR, recharge form, and transaction history.
- Completed: KYC upload validation/private storage, rider availability and GPS controls, wallet ledger/recharge request, and the dashboard passed feature tests.

## Phase 5: Passenger module
- [x] Build Leaflet pickup/drop-off selection, address fields, GPS support, and fare estimation.
- [x] Implement configurable fare calculation using Haversine distance, road factor, and PKR rounding.
- [x] Add booking, searching/cancellation, rider tracking, trip steps, rider details, and ride history.
- [x] Prevent multiple active rides per passenger.
- Completed: map-based booking and server-side decimal fare quoting, active-ride guard, passenger cancellation, tracking polling, and ride history passed tests.

## Phase 6: Ride engine (core logic)
- [x] Find eligible nearby riders with SQL Haversine distance, wallet/KYC/online/active-ride/location freshness rules.
- [x] Add AJAX rider request polling, sound/vibration alert, and request expiration.
- [x] Implement race-safe ride acceptance and status transitions/cancellation with reasons.
- [x] Complete rides transactionally: calculate commission/earnings, deduct commission, write ledger row, and auto-offline riders under the wallet limit.
- [x] Show a retry option when no rider accepts.
- Completed: SQL eligibility, lock-guarded acceptance, lifecycle transitions, timeout cron tasks, rider alerts, and atomic commission ledger/auto-offline verified.

## Phase 7: Semi-automated wallet recharge & admin alarm
- [x] Implement unique-TID recharge requests with positive amounts and pending status.
- [x] Add admin polling for pending deposits and KYC, alarm enable control, audio/toast alerts, and persistent badge.
- [x] Implement idempotent transactional approve/reject with notes and wallet crediting.
- [x] Add manual wallet adjustments with mandatory notes.
- Completed: TID-unique rider requests, enabled-by-gesture HTML alarm, persistent count/toasts, idempotent wallet review, and audited manual adjustments passed tests.

## Phase 8: Super admin panel
- [x] Add mobile-friendly dashboard statistics.
- [x] Add KYC review and rider/passenger management, including suspension and wallet ledger.
- [x] Add wallet request queue and live ride/rider map monitor.
- [x] Add ride list filters and settings management, including payment QR/details.
- [x] Add earnings/commission reports with date filters and CSV export.
- Completed: Admin dashboard metrics and queue, KYC/private-document review, account/ledger controls, live Leaflet monitor, filtered ride list, cached settings/QR management, and date-filtered CSV reports verified with focused feature tests.

## Phase 9: PWA & UI polish
- [x] Add manifest, app icons, theme colour, standalone display, and service worker caching only for shell/static assets.
- [x] Add install prompt, offline fallback, metadata, loading/empty states, and error toasts.
- [x] Apply consistent brand styling.
- Completed: Standalone manifest/icon, install prompt, offline page, and service worker that caches only versioned/static assets and never stores navigation/API responses verified; production assets build cleanly.

## Phase 10: Security & performance hardening
- [x] Authorize every route and file access; restrict KYC document access to admins.
- [x] Use Form Requests, mass-assignment protection, throttling, security headers, and safe output handling.
- [x] Optimize queries with indexes, eager loading, and cached settings.
- [x] Keep location updates lightweight and add audit logging for wallet/ride state changes.
- [x] Document production `APP_DEBUG=false` configuration.
- Completed: Added explicit ride/KYC policies, secure private storage, Form Request validation, request throttles, no-store/security headers, debug-disabled environment defaults, cached settings, and audit events; 34 tests pass.

## Phase 11: Deployment documentation
- [x] Write `DEPLOYMENT.md` for shared hosting and `acrapp.affiliachy.com`, including document root, environment, migrations, storage link, cron, permissions, and HTTPS.
- [x] Document a VPS upgrade path for future Reverb/WebSockets.
- Completed: Added shared-hosting and HTTPS deployment instructions, production settings/migrations/storage/cron guidance, a secure interactive first-admin command, and a VPS scaling path; route caching and the command interface were verified.

## Phase 12: Final testing & finalisation
- [x] Add automated tests for auth/roles, distance/fare, nearby rider eligibility, race-safe acceptance, ride lifecycle/commission, wallet requests, settings, and private uploads/access.
- [x] Run the complete test suite and resolve all failures (34 tests, 191 assertions).
- [x] Run Pint, route listing, config/route/view cache checks and clear caches, and the production Vite build.
- [ ] Perform and report the full manual QA rider/passenger/admin flow through commission deduction and auto-offline. The equivalent end-to-end feature flow passes automatically, but a full human-driven browser walkthrough remains outstanding.
- [x] Remove debug calls, TODO comments, unused files, and hard-coded secrets (source scan clean; deployment placeholders are examples).
- [x] Update `README.md` with setup, seeding, demo credentials, and test instructions.
- [ ] Complete the outstanding manual QA, then report deliverables, results, limitations, and next steps.

## Assumptions
- [x] ACR app created in `/root/acr-app` because no Laravel project or repository was checked out in this session.
