# Project status

**Last updated:** 2026-10-15 · **Phase:** 2 — Catalogue + Filament · **Target launch:** ~1 March 2027 (Sofia)

This is the single source of truth for where the MVP stands. Update it in the same commit as the work:
tick what's done, add what you found, move the "Now" marker. Plan and rationale: [plan.md](plan.md).

## Now

- [ ] First real theatre adapter: Театър „Българска армия“ (tba.art.bg), with saved-HTML fixture test

## Next up

- [ ] Adapter: Народен театър „Иван Вазов“ (nationaltheatre.bg)
- [ ] Adapter: a third launch theatre
- [ ] Outreach email to theatres asking for a monthly programme feed / spreadsheet
- [ ] Confirm the programme source for each of the 12 launch theatres (table below)

## Milestones

### 1. Setup and CI — done (2026-10-15)
- [x] Monorepo on GitHub: `backend/` (Laravel 13) + `mobile/` (Expo SDK 57)
- [x] CI on GitHub Actions: Pint, Larastan, migrations on Postgres 17, Pest; mobile `tsc`
- [x] Local Postgres via `compose.yaml`
- [ ] Run it locally once (`composer setup`, Expo Go on a phone) and commit `composer.lock` + `package-lock.json`

### 2. Catalogue + Filament — in progress (target 15 Nov)
- [x] Migrations: theatres, stages, productions, people, credits, performances, adapter_runs
- [x] Filament admin: theatres, productions (credits + performances), people, adapter run log
- [x] Adapter framework: `TheatreAdapter`, idempotent `ProgrammeImporter`, nightly `teatar:sync-programmes`
- [x] CSV adapter (theatre sends a spreadsheet)
- [x] Seeder for the 12 launch theatres
- [ ] 3 site adapters filling a month of real performances
- [ ] Admin dashboard widget: productions missing author / cast / poster

### 3. Gate: 8 of 12 theatres — target 15 Nov
- [ ] 8 theatres with a usable programme source (site, Entase, or spreadsheet). Below 8 → manual entry in Filament.

### 4. Read-only app — target 13 Dec
- [x] API: programme (tonight/week/month), production, person, theatre, search
- [x] Screens: Programme, Search, Production, Person, Theatre (first pass)
- [ ] Theatre and genre filters on the Programme screen
- [ ] Real posters and headshots (press kits from theatres)
- [ ] Generate API types from Scramble (`npm run api:types`) and replace hand-written ones
- [ ] Deep links `teatar://production/{id}`

### 5. Accounts, rating, diary — target 17 Jan
- [x] Email register / login / logout / delete account (API)
- [ ] Sign in with Apple and Google (Socialite `userFromToken`)
- [ ] Sign-in screens in the app
- [ ] Rate (½–5★), review with spoiler flag, log a visit ("seen"), watchlist — API + screens
- [ ] Profile: diary by date, stats, ratings histogram

### 6. Social, push, moderation — target 7 Feb
- [x] Moderation queue in Filament (hide / restore / dismiss reports), shadow ban
- [ ] Report a review (API + app)
- [ ] Follow users and people; feed of followed users' reviews; like a review
- [ ] Watchlist push 7 days before a date (Expo Push)

### 7. Beta + founding reviews — target 28 Feb
- [ ] All launch adapters live
- [ ] 50–100 reviews from a founding group

## Hosting — Laravel Cloud (account already set up)

Start early (during phase 4) so the app is tested against a real server, not just a laptop.
- [ ] Create the `teatar` application in Laravel Cloud, EU region (Frankfurt), linked to `pkaratanev/teatar`, root `backend/`
- [ ] Serverless Postgres in the same region; confirm `pg_trgm` is enabled (migration creates it)
- [ ] Environment variables from `backend/.env.example` (`APP_THEATRE_TIMEZONE`, mail, `APP_KEY`)
- [ ] Object storage bucket for posters and headshots; point `FILESYSTEM_DISK` / medialibrary at it
- [ ] Scheduler on (nightly `teatar:sync-programmes`) and a queue worker
- [ ] Deploy command: `php artisan migrate --force`; deploy on push to `main` once CI is green
- [ ] Two environments: `staging` (TestFlight / Play internal builds) and `production`
- [ ] Custom domain for the API (e.g. `api.<app-domain>`) and admin; HTTPS
- [ ] First admin user in production (`php artisan make:filament-user`, then set `is_admin`)
- [ ] Error tracking and uptime check; daily database backups confirmed

## Store releases — Google Play and App Store

Accounts and builds (start in phase 5, since review and setup take days):
- [ ] Apple Developer Program enrolment (99 USD/year; individual or company — company needs a D-U-N-S number)
- [ ] Google Play Console account (25 USD once); new personal accounts must run a closed test with 12+ testers for 14 days before production
- [ ] Final app name, icon, splash and bundle id (replace placeholder `bg.teatar.app`)
- [ ] `eas.json` with `development`, `preview` (staging API) and `production` (production API) profiles
- [ ] EAS Build for iOS and Android; EAS Submit to App Store Connect and Play Console
- [ ] OTA updates with EAS Update, channels matching the build profiles

Listing and compliance:
- [ ] Privacy policy and terms pages (hosted on Laravel Cloud), in Bulgarian
- [ ] App Store privacy "nutrition label" and Play Data safety form (email, reviews, push token; no ads)
- [ ] In-app account deletion (already in the API) — required by both stores
- [ ] Sign in with Apple present whenever Google sign-in is offered (App Store rule)
- [ ] Moderation and reporting for user reviews (App Store guideline for user-generated content)
- [ ] Store listings in Bulgarian: description, keywords, screenshots (6.7" iPhone, Android phone), age rating questionnaire

Release path:
- [ ] TestFlight internal → external beta group (founding reviewers)
- [ ] Play internal testing → closed testing (14 days, 12+ testers) → production
- [ ] Submit both for review ~2 weeks before the target launch date

## Launch theatres

| # | Theatre | Programme source | Adapter | Status |
| --- | --- | --- | --- | --- |
| 1 | Театър „Българска армия“ | tba.art.bg | — | Next |
| 2 | Народен театър „Иван Вазов“ | nationaltheatre.bg | — | |
| 3 | Открита сцена „Сълза и смях“ | to confirm | — | |
| 4 | Младежки театър „Николай Бинев“ | to confirm | — | |
| 5 | Театър 199 „Валентин Стойчев“ | to confirm | — | |
| 6 | Театрална работилница „Сфумато“ | to confirm | — | |
| 7 | Сатиричен театър „Алеко Константинов“ | to confirm | — | |
| 8 | Малък градски театър „Зад канала“ | to confirm | — | |
| 9 | Театър „София“ | to confirm | — | |
| 10 | Театър „Възраждане“ | to confirm | — | |
| 11 | Нов театър НДК | to confirm | — | |
| 12 | Театро | to confirm | — | |

## Open decisions

- [ ] App name and bundle id (placeholder: Teatar, `bg.teatar.app`)
- [ ] Timeline assumes full-time work; stretch it if this is evenings and weekends
- [ ] Store developer accounts: individual or company (see Store releases)
- [ ] Guest performances (e.g. „… - Гостува ДТ-Пловдив“ on the ТБА programme): skip for MVP, or list them under the host theatre?

## Log

Newest first. One line per working session: what changed, anything surprising.

- **2026-10-15** — Hosting decided: Laravel Cloud for the backend. Added hosting and store-release checklists.
- **2026-10-15** — Plan settled (Bulgarian only, ½-star ratings, mobile + Filament, Laravel, Expo, solo). Scaffold pushed; CI green after fixing Larastan types, an npm peer conflict and a tab-icon type.
