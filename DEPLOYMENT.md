# Shared-hosting deployment

This guide deploys the ACR app at `https://acrapp.affiliachy.com`. Point the subdomain's document root directly to the Laravel project's `public/` directory. Do not expose the project root, `.env`, `storage/`, or `vendor/` through the web server.

## Requirements

- PHP **8.3 or newer** with the extensions required by Laravel and `pdo_mysql`.
- Composer 2, MySQL 8+ (or a compatible MySQL/MariaDB release), and HTTPS.
- Node.js and npm only on the build machine if Node is not available on the host.

The current Laravel 13 dependency requires PHP 8.3; a host limited to PHP 8.2 cannot run this build. The app uses database-backed sessions/cache, synchronous requests, and AJAX polling; it does not require Redis, WebSockets, or a queue worker.

## First deployment

1. Create a MySQL database and a dedicated database user with privileges limited to that database. Upload the project outside the public web root, or clone it into a private directory.
2. Install PHP dependencies and build the frontend. Run the Vite build on your workstation/CI if the host does not provide Node:

   ```sh
   composer install --no-dev --optimize-autoloader --no-interaction
   npm ci
   npm run build
   ```

3. Create `.env` from `.env.example`, then set the production values. Keep passwords and keys out of source control:

   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://acrapp.affiliachy.com
   APP_TIMEZONE=Asia/Karachi
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database
   DB_USERNAME=your_database_user
   DB_PASSWORD=your_database_password
   SESSION_DRIVER=database
   SESSION_SECURE_COOKIE=true
   CACHE_STORE=database
   QUEUE_CONNECTION=sync
   FILESYSTEM_DISK=local
   ```

   Set `APP_KEY` once with `php artisan key:generate`. Do not regenerate it during routine deployments. Set `LOG_LEVEL=warning` or another operationally appropriate level.
4. Run schema and initial settings setup. This does not create demo users in production:

   ```sh
   php artisan migrate --force
   php artisan db:seed --class=SettingsSeeder --force
   php artisan acr:create-admin
   ```

   The last command securely prompts for an administrator name, phone, and password; it does not echo the password. Store that password in your approved password manager. Never use the demo password in production.
5. Link public assets and warm Laravel's caches:

   ```sh
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

   If your hosting panel prohibits the Artisan symlink command, create `public/storage` as a symlink to the project's `storage/app/public` directory using the panel or SSH. Rider KYC and recharge documents are stored under the private local disk in `storage/app/private`; do not symlink that directory.

## Web root, permissions, and scheduled tasks

- Set the subdomain document root to `<project>/public`.
- Make `storage/` and `bootstrap/cache/` writable by the PHP/web-server account. Prefer owner/group permissions such as `775` (or the narrowest host-specific equivalent); do not use world-writable `777`.
- Allow uploads up to 5 MB in PHP (`upload_max_filesize` and `post_max_size` should be at least 6 MB).
- Enable HTTPS and force HTTP-to-HTTPS redirects at the hosting panel. The PWA service worker and secure session cookie require HTTPS in production.
- Configure the host's cron panel to run Laravel's scheduler once per minute, using the actual project and PHP paths:

   ```cron
   * * * * * cd /home/ACCOUNT/acrapp && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
   ```

   The scheduler expires unanswered ride requests and takes riders with stale location updates offline. No long-running worker is needed.

Back up the MySQL database and both public and private storage. Restrict backups of KYC/recharge documents to authorized operators and encrypt them at rest.

## Routine updates

Build assets and install dependencies before switching traffic to an updated release. Then run:

```sh
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Do not run `migrate:fresh` on a live database. `DatabaseSeeder` intentionally creates its demo super admin, five riders, and three passengers only in `local` and `testing` environments; production seeding should be limited to `SettingsSeeder`.

## VPS growth path

Keep the current polling implementation on shared hosting. On a VPS, introduce a process manager and queue backend only when needed, then add Laravel Reverb/WebSockets for rider locations and ride-state updates. Move sessions/cache to Redis if multiple app instances require shared state, and consider private object storage for KYC files. Any real-time migration should preserve current authorization, ride locking, and wallet transaction boundaries.
