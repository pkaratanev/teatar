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
- [ ] Hosting: EU VPS (Forge) or Laravel Cloud EU; Redis + Horizon for queues
- [ ] 50–100 reviews from a founding group
- [ ] TestFlight + Play internal testing; store listings

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
- [ ] Apple Developer Program (99 USD/yr) and Google Play (25 USD) accounts

## Log

Newest first. One line per working session: what changed, anything surprising.

- **2026-10-15** — Plan settled (Bulgarian only, ½-star ratings, mobile + Filament, Laravel, Expo, solo). Scaffold pushed; CI green after fixing Larastan types, an npm peer conflict and a tab-icon type.
