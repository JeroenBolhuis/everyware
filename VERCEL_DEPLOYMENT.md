# Vercel Deployment

This project is configured to deploy on Vercel with the PHP community runtime and a separate frontend asset build.

## What is pinned in the repo

- PHP is pinned to `^8.4` in `composer.json`
- Node is pinned to `22.x` in `package.json`
- Local Node tooling can read `.node-version`
- Vercel uses `vercel-php@0.8.0` through `vercel.json`

## Build flow on Vercel

`vercel.json` now enforces this order:

1. `composer install --no-dev --prefer-dist --optimize-autoloader`
2. `npm ci`
3. `composer run vercel-build`

The `vercel-build` script does this:

1. `npm run build`
2. `php artisan config:cache`
3. `php artisan route:cache`
4. `php artisan view:cache`

That gives Vercel a predictable install/build order and avoids relying on framework auto-detection.

## Required Vercel environment variables

Set these in the Vercel project before deploying:

- `APP_KEY`
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain.vercel.app`
- `LOG_CHANNEL=stderr`
- `DB_CONNECTION=mysql`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `CACHE_STORE=database`
- `SESSION_DRIVER=database`
- `QUEUE_CONNECTION=sync`

`QUEUE_CONNECTION=sync` is recommended on Vercel unless you add a separate worker setup.

## First deploy checklist

1. Push these repo changes.
2. Import the repo in Vercel.
3. Confirm the project uses the repo root.
4. Add the environment variables above.
5. Trigger a new deployment.
6. Run database migrations against the production database from a trusted machine:

```bash
php artisan migrate --force
```

Do not bake production migrations into the Vercel build automatically.

## Local parity

Use Node 22 locally to match Vercel:

```bash
node -v
```

If needed on Windows:

```bash
winget install OpenJS.NodeJS.22
```
