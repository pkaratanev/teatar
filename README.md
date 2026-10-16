# Teatar

A Letterboxd-style app for Bulgarian theatre: browse the Sofia programme, rate and review what you saw,
keep a diary and a watchlist. **Where things stand: [docs/STATUS.md](docs/STATUS.md).** Plan: [docs/plan.md](docs/plan.md) · research: [docs/investigation.md](docs/investigation.md).

| Folder | What |
| --- | --- |
| `backend/` | Laravel 13: JSON API (`/api/v1`), Filament admin (`/admin`), programme adapters |
| `mobile/` | Expo SDK 57 app (TypeScript, Expo Router, TanStack Query, React Native Paper) |
| `docs/` | The investigation and the build plan |

## Run it locally

**Windows, quickest:** paste this into PowerShell. It installs Git, Node and Laravel Herd if missing,
puts the project in `%USERPROFILE%\Projects\teatar`, sets the backend up on SQLite (no Docker),
loads the demo data, and starts the API and the Expo dev server. Run it again to update and restart.

```powershell
irm https://raw.githubusercontent.com/pkaratanev/teatar/main/scripts/windows.ps1 | iex
```

**By hand:**
Needs PHP 8.4+, Composer, Node 22, and Docker (or Herd / Postgres.app) for Postgres.

```bash
docker compose up -d                 # Postgres 17 on :5432 (teatar / teatar)

cd backend
composer setup                       # install, .env, key, migrate --seed
php artisan serve --host=0.0.0.0     # API on :8000, admin on :8000/admin
php artisan test
```

`composer setup` creates the launch theatres and an admin `admin@teatar.test` / `password`.
Then, for a realistic test setup:

```bash
php artisan teatar:demo            # real programme from the theatres + 12 demo people with
                                   # ratings, reviews, diaries, watchlists, follows and likes
php artisan teatar:demo --offline  # same, with fake productions instead of the real programme
```

Demo people sign in as e.g. `maria.p@demo.teatar.test` / `password`. Commit `composer.lock`
after the first install.

```bash
cd mobile
cp .env.example .env                 # set EXPO_PUBLIC_API_URL to your computer's LAN IP
npm install
npx expo start                       # scan the QR code with Expo Go
```

## Screenshots

`mobile/e2e/screenshots.mjs` opens the web build in a phone-sized Chromium and saves a picture of every
screen to `mobile/shots/`. Start the API with demo data first (`php artisan teatar:demo`, `php artisan serve`),
then in `mobile/` (PowerShell):

```
npx.cmd expo export --platform web --output-dir web-dist
npx.cmd --yes serve@14 -s web-dist -l 8081          # leave running; the rest in a second window
npm.cmd install --no-save playwright@1
npx.cmd playwright install chromium
node e2e/screenshots.mjs
```

The same run is available on GitHub by hand: Actions → Screenshots → Run workflow (results on the
`screenshots` branch).

## Programme data

Programme data comes from public sources (see docs/plan.md). A theatre's `adapter` (set in
Filament) names a class in `config/teatar.php`:

- `csv` — the theatre sends a spreadsheet; save it as `backend/storage/app/private/programmes/{slug}.csv`
  (see `example.csv`).
- Per-site adapters go in `backend/app/Adapters`, one class per theatre, tested against saved HTML.

```bash
php artisan teatar:sync-programmes            # queue all theatres (runs nightly at 03:15 Sofia time)
php artisan teatar:sync-programmes slug --now # one theatre, right now
```

Every run is logged under **Данни → Синхронизации** in the admin.

Never scrape theatre.art.bg (see docs/plan.md).
