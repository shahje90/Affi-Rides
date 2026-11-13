# Affi Campbellpur Rides (ACR App)

A mobile-first Laravel PWA for local motorcycle rides in Attock and nearby areas. The app includes passenger bookings, rider onboarding/KYC, wallet recharges, ride dispatch and tracking, and a super-admin panel. It uses Blade, Alpine.js, Tailwind CSS, Leaflet/OpenStreetMap, MySQL in production, and AJAX polling rather than WebSockets.

## Requirements

- PHP 8.3+ and Composer 2 (Laravel 13's current dependency requirement).
- Node.js/npm for local frontend builds.
- MySQL for production. Local development can use SQLite.

## Local setup

```sh
composer install
cp .env.example .env
php artisan key:generate
```

For a quick SQLite setup, set these values in `.env`:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_LOCALE=en
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
SESSION_SECURE_COOKIE=false
```

Then initialize the database, seed local fixtures, and build assets:

```sh
touch database/database.sqlite
php artisan migrate:fresh --seed
php artisan storage:link
npm install
npm run build
```

Start the app with `php artisan serve`. For frontend iteration, run `npm run dev` in a second terminal. The seeded administrator, five riders, and three passengers exist only in `local`/`testing`:

| Account | Phone | Password |
| --- | --- | --- |
| Super admin | `03000000001` | `password` |
| Riders | `03000000011`–`03000000015` | `password` |
| Passengers | `03000000021`–`03000000023` | `password` |

These are local demo credentials only. Never use them in production.

## Tests and code style

```sh
php artisan test
./vendor/bin/pint
npm run build
```

The feature and unit suites cover authentication/role access, fare and nearby-rider calculations, ride lifecycle and commission accounting, wallet review, admin settings and reports, private KYC access, and PWA assets.

## Production

Deploy with the subdomain document root set to `public/`, HTTPS enabled, `APP_DEBUG=false`, database sessions/cache, and the one-minute Laravel scheduler cron. Production admin accounts are created with the interactive `php artisan acr:create-admin` command. Follow [DEPLOYMENT.md](DEPLOYMENT.md) for shared-hosting setup, permissions, backups, and the VPS upgrade path.

## Current limitations

- UI copy supports English and Urdu locale/RTL switching; some less-common messages still fall back to English.
- Recharge verification is manual; online payment gateway callbacks and push notifications are not integrated.
- Location/trip updates use polling. Reverb/WebSockets are a future VPS option.
- The production code requires PHP 8.3 or newer; PHP 8.2-only hosting is not compatible with the current Laravel release.
