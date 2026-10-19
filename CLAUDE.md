# Teatar — notes for Claude

Letterboxd-style app for Bulgarian theatre. Solo developer (Preslav), familiar with Laravel.

## Start and end of every session

1. Read `docs/STATUS.md` first. It is the task list and the source of truth for what's done and what's next.
2. Before finishing, update `docs/STATUS.md` in the same commit as the work: tick items, move "Now",
   add new tasks you discovered, and add one line to the Log.

## Testing a feature by hand

Follow [docs/testing.md](docs/testing.md): phone checks one at a time with Preslav, everything else driven by
Claude in the desktop app's browser pane against the web build at http://localhost:8000/app.

## Layout

- `backend/` — Laravel 13: API `/api/v1` (Sanctum), Filament 5 admin `/admin`, adapters in `app/Adapters`.
- `mobile/` — Expo SDK 57, Expo Router, TanStack Query, NativeWind + own components in `mobile/src/ui`. UI text is Bulgarian.
- `docs/` — `STATUS.md` (tasks), `plan.md` (decisions), `investigation.md` (market and data research).

## Rules

- Never scrape or copy from theatre.art.bg (terms of use + EU database right). Everything else public is
  fair game for bare facts (title, date, time, stage, credits): theatres' own sites first, then public
  ticketing pages. Every catalogue row sets `source` and `source_url`. Posters and short descriptions
  are the exception: imported for testing only, and need each theatre's permission before launch.
- Never work around bot checks (Cloudflare challenges, CAPTCHAs) or robots.txt. Report the site as blocked.
- Ratings are stored as `half_stars` 1–10; the API and app show stars.
- Programme times are entered in Europe/Sofia and stored in UTC.
- Don't add new theatre adapters unless Preslav asks (he said there's enough data for now).
- No spending beyond what Preslav has approved. Approved 2026-10-17: Laravel Cloud for the backend and the Google
  Play account for a closed test. No public store release before the MVP goals are ticked.
- The production database is never reset: deploy runs only `migrate --force`, and migrations are only added,
  never edited once deployed ([docs/deploy.md](docs/deploy.md)).
- Laravel Cloud deploys from the `prod` branch. Work goes to `main`; move `prod` (`git push origin main:prod`) only
  when Preslav asks for a deploy and CI is green on `main`.
- Each site adapter gets a test against saved HTML in `backend/tests/Fixtures`, so a site redesign
  fails a test instead of importing wrong data.

## Filament gotcha

Filament fills closure parameters **by name**. Query closures must name the parameter `$query`
(`modifyQueryUsing(fn (Builder $query) => …)`, filter `query()` / `queries()`, `relationship(…, fn (Builder $query) …)`).
Any other name gets a blank builder with no model, which crashes with "… on null".
Plain Eloquent closures (`whereHas`, `withCount`) can use any name.

## Working in the cloud sandbox

Packagist and npm are blocked there, so `composer install` / `npm install` can't run. Push and let CI
verify: failures appear as annotations, readable with
`gh api repos/pkaratanev/teatar/check-runs/<job id>/annotations`.

- Lock files (`backend/composer.lock`, `mobile/package-lock.json`) come from Preslav's working install
  (PHP 8.4). Adding a package needs a lock update, which only a machine with Composer/npm can make.
- Theatre sites aren't reachable from the sandbox either. To look at a site, push a temporary workflow to a
  `probe` branch that curls the pages (with our user agent) and force-pushes them to `probe-out`. Ask Preslav to delete the probe branches afterwards (the sandbox can't delete branches).
- Screenshots: run `.github/workflows/screens.yml` by hand (`gh workflow run` needs GraphQL, which is
  blocked; use `gh api repos/pkaratanev/teatar/actions/workflows/screens.yml/dispatches -f ref=main`),
  then `git fetch origin screenshots`.
