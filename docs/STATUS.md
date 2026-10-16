# Project status

**Last updated:** 2026-10-16 · **Stage:** MVP runs on Preslav's phone (restyle, Varna, posters done) · **Next:** Preslav walks the MVP goals · **Then:** fixes → deploy → stores

Screen map of the app (what each screen shows and where it leads): https://claude.ai/artifact/SHD5q6aU93xAYBA9YTG3XH

This is the single source of truth for the project. Update it in the same commit as the work: tick what's
done, move "Now", add what you found, add a Log line. Plan and rationale: [plan.md](plan.md).

**Order of work:** build a stable MVP → Preslav tests it → only then pay for hosting and store accounts.
No deploy work before the MVP goals below are met.

## MVP goals

The MVP is done when every box here is ticked. These are the acceptance criteria for Preslav's testing,
so a box is ticked only after he has tried it. The note after each goal says where the build stands.

**Catalogue (what's on)**
- [x] Programme for Sofia covers at least 8 of the 12 launch theatres, kept fresh automatically — *checked by Preslav 2026-10-16: 10 adapters, month view loads more on scroll; the week-long nightly run is its own goal under Stable*
- [x] Guest performances are listed too, under the host theatre, marked with the visiting company — *checked by Preslav 2026-10-16*
- [x] A second city, so switching cities can be tested: Varna with 2 theatres; the app remembers the chosen city — *checked by Preslav 2026-10-16, including after closing the app*
- [ ] Every listed production has a title and theatre; most have author, director and cast — *partly: titles and theatres yes, authors for most, directors for some, cast mostly missing (decision below)*
- [ ] Manual review in the admin, productions only for now — *not started (Preslav, 2026-10-16: don't launch with wrong data). A production is flagged "needs review" when something is missing (author, director, cast, poster, description) or was just created by an importer; the admin gets a "За преглед" list with what's missing per row, an edit form to fill it in, and a "Прегледано" action that records who checked it and when. A later import that changes or adds data flags it again. Starting point: the dashboard's existing "needs data" widget (productions playing in the next 30 days missing author, director, cast or poster)*
- [ ] Productions have a short description — *not started. Decided 2026-10-16 (option c): import the theatres' own descriptions for testing, like posters, and ask permission before launch. The app already shows `synopsis` when set*
- [x] Browse tonight / this week / this month; filter by theatre; search productions, people and theatres — *checked by Preslav 2026-10-16 (chip names: see Test findings)*
- [ ] On first start the app picks the nearest city from the phone's location (asks permission; София if refused or far from every city); the user can still switch — *not built: `expo-location` + a centre point per city (theatres have no coordinates yet), works in Expo Go*
- [ ] Production, person and theatre pages; "Билети" opens the theatre's own box office — *built; per-show ticket links for 7 theatres, the rest need a box-office link (Open decisions)*

**Your theatre life**
- [ ] Sign up and sign in with email; delete account — *built*
- [ ] Sign in with Google — *not started; Preslav gets the Google OAuth key later. Plan: free Google Cloud OAuth client (Android, iOS and web client ids), backend `auth/social` verifying the Google token with Socialite (`laravel/socialite` is already in composer.json and the lock file; no route yet), app button with Google sign-in. Native Google sign-in doesn't run in Expo Go, so testing it needs a development build (`eas init`, free plan). Apple sign-in still comes with the store accounts, since the App Store requires it once Google is offered*
- [ ] Rate ½–5★, write a review with a spoiler flag, mark "seen" with a date, watchlist — *built*
- [ ] Profile: diary by date, stats for the year, ratings histogram — *built*

**Social and safety**
- [ ] Follow users and people; feed of followed users' reviews; like a review — *built*
- [ ] Report a review; moderation in the admin (hide, shadow ban) — *built*
- [ ] Push reminder when a watchlisted production plays within 7 days — *built; testable only with a development build*

**Added from the feature research** (Preslav, 2026-10-16: these are MVP, not after; details and open questions under "MVP features from the research")
- [ ] A moved show keeps its performance id; a dropped show is marked cancelled — *built 2026-10-16 (importer matching, see Next up)*
- [ ] "Already seen" onboarding: after sign-up, a grid of the city's most-reviewed productions to mark seen (diary entries without a date) — *not started; one open question (fallback while there are few reviews)*
- [ ] "Отивам": add a performance to the phone's calendar; the next day one push "Как беше …?" opens the log sheet — *not started; needs `expo-calendar` and three answers*
- [ ] Yearly theatre goal: set how many shows to see this year, progress from the diary — *not started; three open questions*
- [ ] Weekly "new in your city" push; the chosen city is saved on the account — *not started; four open questions*
- [ ] Tags on productions and a "similar productions" section — *not started; three open questions; the biggest of the six*

**Stable**
- [x] Runs end to end on a laptop and on a real phone (Expo Go / dev build) against the local API — *done 2026-10-16: Windows PC + Android phone in Expo Go*
- [ ] Nightly sync runs for a week with no unexplained failures in the admin's sync log — *needs a machine running the scheduler for a week*
- [ ] CI green; every adapter has a saved-HTML test; core flows have API tests — *done in CI; tick at sign-off*
- [ ] No known crash or data-loss bug open — *none known; the first test will tell*

## Now

**Preslav walks through the MVP goals on his phone** and ticks the ones that work; anything broken goes
under "Test findings" (or just tell Claude). Not testable yet: push reminders (need a development build) and
"nightly sync for a week" (needs a machine running the scheduler).

**How to run it (Windows):** `irm https://raw.githubusercontent.com/pkaratanev/teatar/main/scripts/windows.ps1 | iex`
pulls the code, runs migrations, starts the API in its own window and Expo in this one. A re-run doesn't download
the programme again: for fresh programmes and posters run `php artisan teatar:demo` in `backend/` (second window).
Sign in as `admin@teatar.test` / `password` or `maria.p@demo.teatar.test` / `password`. Screen map: link at the top.

**Built 2026-10-16, to check:**
- [x] Phone: tap **📍 София** at the top right of Програма → Варна (Драматичен театър „Стоян Бъчваров“, Държавен куклен театър)
- [ ] Phone: posters from Народен театър and Драматичен театър Варна (others keep the typographic poster) — Varna checked
- [ ] Admin (`/admin`): Синхронизации has "Обнови всички" and "График" (time, pause) at the top and the next run under
  the title; the dashboard shows "Проблеми със синхронизацията" only when a theatre's last sync failed, found nothing or
  is over 36 hours old, and the menu item gets a red count. Locally the buttons only queue jobs: run
  `php artisan queue:work` in another window to see them go through
- [x] Restyle (NativeWind + own components, dark theme): checked, looks good

**Not started, in the MVP:** manual review list, descriptions, city from location, Google sign-in (waiting on the
key), and five of the six research features (onboarding, "Отивам", yearly goal, weekly push, tags). Order under Next up.

**Waiting on Preslav:** the walk-through; the Google OAuth key; the research features' open questions; the open
decisions below (cast data, Сфумато, Нов театър НДК, box-office links, app name, store accounts).

**Screenshots:** `.github/workflows/screens.yml` runs by hand (Actions → Screenshots) or locally (README):
web build + API with demo data + Playwright → phone-sized screenshots on the `screenshots` branch.

## Test findings

First run, 2026-10-16 (Windows PC, Android phone, Expo Go):
- [x] Windows script stopped at `npm` (execution policy blocks `npm.ps1`) — fixed, uses `npm.cmd` / `npx.cmd`
- [x] App crashed on start in Expo Go on Android: importing `expo-notifications` throws there, which broke every screen — fixed, notifications load only in dev/store builds
- [x] After the restyle, Metro failed on Windows ("reading 'transformFile'"): the real error was `Cannot find module 'babel-preset-expo'` — our new babel.config uses it, so it is now a direct devDependency
- [x] After the fix: the app works; the styling is the problem (generic Material look) — restyled
- [x] Running `php artisan teatar:demo` a second time crashed on SQLite (duplicate diary entry): SQLite stores a `date` cast as "Y-m-d 00:00:00", so the same-day lookup missed. Same bug would hit logging a show twice on one day in the app. Fixed with `DiaryEntry::record()` (matches with whereDate)
- [x] Restyle checked on the phone: looks good (tab labels clipped only in the web screenshots, not on the phone)

Walk-through, 2026-10-16:
- [ ] Theatre chips on Програма use the quoted part of the name, so Народен театър shows as "Иван Вазов" (also Младежки → "Николай Бинев", 199 → "Валентин Стойчев", Сатира → "Алеко Константинов", Драма Варна → "Стоян Бъчваров"). Fix: a `short_name` column on theatres, filled by the seeder (Народен театър, Младежки театър, Театър 199, Сатира, …), editable in the admin, sent by the API and used for chips

## Next up

Proposed order for the unbuilt MVP goals (Preslav can reorder). The first three need no new decisions:

1. [ ] **Manual review list** in the admin (spec under MVP goals). Do first: it is the safety net for wrong data, and later items (descriptions, tags) feed into it
2. [ ] **Descriptions** (option c): read each theatre's own description where its programme or production page has one, store it as `synopsis` with its source URL, never over an admin's text; productions without one appear in the review list
3. [ ] **City from location**: needs `expo-location` (a new npm package: Claude can add it with a one-off workflow that runs `npx expo install` and commits the lock file, or Preslav runs it) and a centre point per city
4. [ ] **"Already seen" onboarding** once the fallback question is answered (MVP features from the research, 6)
5. [ ] **Weekly push, yearly goal, "Отивам", tags** once their open questions are answered
6. [ ] **Google sign-in** once the OAuth key exists

Smaller to-dos:
- [ ] Code style: Pint fails in CI but is non-blocking (`continue-on-error`), so CI looks green. Run `vendor/bin/pint` once on a machine with Composer, commit, then make the step blocking
- [ ] Live sources shows results as annotations, and GitHub keeps only 10 notices per step, so with 12 theatres one result is cut (Театър „София“ this time). Write the results to one annotation or the job summary instead
- [ ] Cast for most productions: waiting on the decision under Open decisions
- [ ] Following people: notify / highlight new productions with people you follow (nice-to-have)
- [ ] Младежки театър: Cloudflare bot check blocks GitHub's servers (fine from a normal connection). Re-test from the production host when we deploy; fallback is CSV / manual entry in the admin. Never work around the check.

## Build milestones

### 1. Setup and CI — done
- [x] Monorepo on GitHub: `backend/` (Laravel 13) + `mobile/` (Expo SDK 57)
- [x] CI: Pint, Larastan, migrations on Postgres 17, Pest, Filament assets check; mobile `tsc` + Metro bundle of the Android app
- [x] **Live sources** workflow: runs every adapter against the real sites (manual + weekly)
- [x] Local Postgres via `compose.yaml`
- [x] Preslav runs it locally once (`composer setup`, `php artisan teatar:demo`, Expo Go on a phone)
- [x] `backend/composer.lock` and `mobile/package-lock.json` committed from Preslav's working install, so every install (and CI) gets the same versions

### 2. Catalogue and data — enough for the MVP (paused)
- [x] Tables, Filament admin for catalogue, adapter framework, idempotent importer, nightly sync, CSV adapter
- [x] Seeder for the 12 launch theatres
- [x] Adapter: Българска армия (`TbaAdapter`)
- [x] Adapter: Народен театър (`NationalTheatreAdapter`: date/time from ticket link, author line, „Гостуващ“ label, skips building tours)
- [x] Adapter: Théatro (`TheatroAdapter`: one long page, year inferred, hidden promo rows and cancellation notices skipped)
- [x] Adapter: Възраждане (`VazrajdaneAdapter`: Entase-fed cards, guest category, several times per card, Entase ticket links)
- [x] Adapter: МГТ „Зад канала“ (`ZadKanalaAdapter`: Drupal table with ISO timestamps)
- [x] Adapter: Театър „София“ (`SofiaTheatreAdapter`: month pages, selected-month check, other venues as stage)
- [x] Adapter: Сълза и смях (`SalzaISmyahAdapter`: month calendar joined with per-stage lists by ticket id)
- [x] Adapter: Младежки театър (`MladezhkiAdapter`: windows-1251 table, month heading check; Nov–Dec published, so no ticket-shop fallback needed). Guest-title parsing shared in `GuestTitle`
- [x] Adapter: Театър 199 (`Theatre199Adapter`: pages until empty, featured block + cards, year inferred)
- [x] Adapter: Сатира (`SatirataAdapter`: Next.js `__NEXT_DATA__` JSON per month; prices in €, ePayGO ticket links, age rating)
- [x] Shared date helper `BulgarianDate`: month names (full, abbreviated, English), weekday, year inference
- [x] Guest performances listed under the host theatre (`productions.is_guest` + optional `visiting_company`)
- [x] Programme sources researched for all 12 (Data sources table)
- [x] `teatar:check-sources` + **Live sources** workflow (manual and weekly): runs every adapter against the real sites without saving
- [x] Live check green: 9 of 10 adapters return real programmes (717 performances across 9 theatres, ~6–10 weeks ahead); Младежки театър reported as known-blocked
- [x] Adapters for 10 of 12 launch theatres (only Сфумато and Нов театър НДК left, both waiting on decisions)
- [x] Director credits from programme listings where shown (Théatro, Възраждане)
- [x] Admin dashboard: headline stats, sync health per theatre (last run, upcoming count, programme known until), "needs data" list for productions playing in 30 days
- [x] Admin smoke tests: dashboard and every resource page load for an admin; non-admins get 403
- [ ] Display casing for ALL-CAPS titles ("СЪН В ЗИМНА НОЩ", "ТЕАТЪР") — several sites shout; keep the source title, show a tidy one

### 3. Read-only app
- [x] API: programme, production, person, theatre, search
- [x] Screens: Programme, Search, Production, Person, Theatre (first pass)
- [x] "гостува …" label on visiting productions
- [x] Theatre filter on the Programme screen (chips, hides theatres with nothing scheduled)
- [x] Infinite scroll on Programme, Diary and Feed
- [ ] Generate API types from Scramble and replace hand-written ones
- [x] Deep links: `teatar://production/{id}`, `/theatre/{id}`, `/person/{id}`, `/user/{username}`; share icon on those pages; Back from a deep-linked page goes to the tabs; dead links show "not found"

### 4. Accounts, rating, diary
- [x] Email register / login / logout / delete account (API)
- [x] Sign-in / sign-up screen (email), token in SecureStore, auth context
- [x] Rate, review, "seen" diary, watchlist, one-tap log — API with tests (`ActivityController`, `TheatreLog`)
- [x] Profile API: public profile with stats (this year, histogram, favourite theatre, most-seen actor); me/diary, me/watchlist, me/ratings
- [x] Same in the app: "Видях" sheet (date, ½–5★ tap input, review, spoiler switch), watchlist toggle, my rating and dates on the production page, review feed with hidden spoilers
- [x] Edit profile (name, bio up to 500 chars): `PATCH /me` with tests, "Редактирай профила" screen; username and email stay fixed
- [x] Diary tab (grouped by month) and Profile tab: stats tiles, ratings histogram, favourite theatre, most-seen actor, watchlist with next date, sign out, delete account; public `/user/[username]`

### 5. Social, push, moderation
- [x] Moderation queue in Filament, shadow ban
- [x] Report a review (API)
- [x] Report a review from the app (reason menu)
- [x] Follow users and people, feed of followed users' reviews, likes (API with tests + app: Лента tab, Следвай buttons, ♥ on reviews)
- [x] Watchlist push: daily job (10:00 Sofia) for shows within 7 days, once per 3 weeks; token registered after the first "Искам да гледам"; tapping opens the production
- [ ] Push needs `eas init` (project id) and a development build to test on a phone — Expo Go can't receive pushes

### 6. Stabilise
- [ ] Run the nightly sync for a week; fix every failure
- [ ] Test pass on iOS and Android devices; fix crashes
- [x] Pre-test fixes: lists stopped at the first page; admin had no CSS/JS on a fresh install
- [x] `php artisan teatar:demo`: real programme + 12 demo people with ratings, reviews (some spoilers), diaries, watchlists, follows, likes; admin's own Feed/Diary/Profile filled too

### 7. Sync management — done 2026-10-16
- [x] "Обнови всички" on the sync log and the dashboard's sync table. Every start (nightly command, buttons) goes through `ProgrammeSync`, which writes a "queued" run first and skips a theatre that is already queued or running
- [x] Stuck runs: job `$timeout` 600 s (queue `retry_after` 900 s so a long sync isn't started twice), `failed()` closes the run, and every 10 minutes `AdapterRun::markStale()` fails runs "running" over 30 minutes ("Прекъснато") or "queued" over 2 hours ("no queue worker")
- [x] Alerts: `SyncAlerts` widget above the sync table (hidden when all is well) lists failed, empty and over-36-hour theatres with the reason; red count on the Синхронизации menu item
- [x] Schedule: "График" on the sync log (a modal) sets the daily time and pause; stored in the `settings` table, read by `SyncSchedule::register()` in `routes/console.php`. Next run shown on the sync log and the dashboard
- [x] Moved and dropped performances: after each import, `ProgrammeImporter::reconcile()` checks the theatre's future adapter rows between the first and last day the source listed. A moved show keeps its row and id (per-show ticket link, or same production, stage and day with one old and one new time); unmatched rows are marked cancelled (hidden in the app) and come back if the source lists them again. Admin-entered rows are never touched. A run that would cancel more than 5 shows and over half of them cancels none and says so in the sync log
- [x] Live sources re-run: both Varna adapters work against the real sites (Драматичен театър 32 performances to 30 Nov, Куклен театър 5 to 3 Nov); Младежки still 403 from GitHub
- Decided 2026-10-16 (Preslav): no sync running locally; `php artisan teatar:demo` fills the data on a PC. The nightly run and the admin "Обнови" buttons are for production

## Data sources

Use whatever public source works: each theatre's own site first, then public ticketing listings
(Entase, Eventim, Bilet.bg, ePayGO) and other public pages. Take bare facts only (title, date, time,
stage, cast, author). Exception: posters and short descriptions, imported for testing only, with permission
asked before launch. Contact theatres only for what we can't find — decide that after the MVP.

The one source to keep out is theatre.art.bg itself: its terms forbid copying and its catalogue is
protected by the EU database right (see investigation). Everything else is fair game for facts.

| # | Theatre | Programme source | Notes | Verdict | Status |
| --- | --- | --- | --- | --- | --- |
| 1 | Театър „Българска армия“ | `tba.art.bg/programabg.php?year=&month=` | 3 stages; guests "– Гостува X" | Easy | **Adapter done** |
| 2 | Народен театър „Иван Вазов“ | `nationaltheatre.bg/bg/programa?day=&month=&year=` | UTF-8; Oct–Dec published; 6 stages; guests labelled „Гостуващ“; credits on `/bg/predstavlenie/{slug}` | Easy | **Adapter done** |
| 3 | Théatro | `theatro.bg/program/` (one long page) | no year in dates; stale block at top to skip; guests in title text; Eventim/Entase ticket links | Easy | **Adapter done** |
| 4 | Театър „Възраждане“ | `theatrevazrajdane.bg/programa/?date=YYYYMMDD-YYYYMMDD` | WordPress; Oct–Dec; one venue; several times per entry | Easy | **Adapter done** |
| 5 | МГТ „Зад канала“ | `zadkanala.bg/programa/YYYY-MM` | full dates; Oct–Dec; one stage; credits + posters on `/spektakli/{slug}` | Easy | **Adapter done** |
| 6 | Театър „София“ | `sofiatheatre.eu/program?year=Y&month=M` | per-performance pages `/program/{id}` with price; 2 stages | Easy | **Adapter done** (sold-out rows skipped) |
| 7 | Открита сцена „Сълза и смях“ | `salzaismyah.bg/site/calendar/YYYY/MM` + stage lists `/site/events/{1,2,3}` | calendar has dates, stage lists have stage — join both; many visiting companies | Easy | **Adapter done** |
| 8 | Младежки театър „Николай Бинев“ | `mlt.bg/programa.php?year=&month=` | unpublished month silently returns another month — check the page heading; guests "– Гостува X"; Latin look-alikes. **Cloudflare blocks datacenter IPs** (GitHub); `tickets.mlt.bg` needs a login | Easy | **Adapter done**, blocked live |
| 9 | Театър 199 „Валентин Стойчев“ | `theatre199.org/bg/schedule?page=N` | no year anywhere; one hall; Eventim venue page has full dates | Easy | **Adapter done** |
| 10 | Сатиричен театър „Алеко Константинов“ | `satirata.bg/program?f:month=YYYY-MM` | Next.js; the whole month is in `__NEXT_DATA__` JSON (UTC times, stage keys, € prices, ePayGO links); age rating in the title | Easy (was Medium) | **Adapter done** |
| 11 | Театрална работилница „Сфумато“ | `sfumato.info/programa.php?year=&month=` | **robots.txt disallows crawling**; same platform as theatre.art.bg | Blocked | See decisions |
| 12 | ~~Нов театър НДК~~ | — | reported closed since Aug 2024; its own site blocks crawlers | Drop | See decisions |

**Varna** (second city):

| # | Theatre | Programme source | Notes | Verdict | Status |
| --- | --- | --- | --- | --- | --- |
| V1 | Драматичен театър „Стоян Бъчваров“ | `dramavarna.com/index.php/bg/program-and-tickets?month=M&year=Y` | Joomla; one `li-YYYY-MM-DD` per day, stage, time range, author line, director, cast, ticket links (ФКЦ, Eventim, Grabo); tours are a separate filter; published about 6 weeks ahead | Easy | **Adapter done** (`dramavarna`) |
| V2 | Държавен куклен театър – Варна | `vnpuppet.com/wp-json/tribe/events/v1/events?start_date=&per_page=100` | The Events Calendar's public JSON; English copies under `/en/` skipped; guests in the title ("Лазарица/гостува ДТ-Русе"); robots.txt: Crawl-delay 300, so one request per run; publishes only a few weeks ahead | Easy | **Adapter done** (`vnpuppet`) |
| — | Държавна опера Варна | `operavarna.bg/програма/месечна-програма` | opera and ballet, not drama; the monthly page still shows January 2012 | Skip for now | — |

Findings that shape the code:
- Most sites print dates without a year (Народен, Théatro, Възраждане, Театър 199). Needs one shared helper: infer the year from day + month (+ weekday when present) relative to today.
- Entase is not a shortcut: organiser pages load events by JavaScript and the API needs a key per organiser. Individual `entase.com/book?eid=` pages are readable, so ticket links can still be kept.
- tba.art.bg, mlt.bg and sfumato.info share URL patterns (`slug___id`) and ids with theatre.art.bg — the same vendor runs them. We take only bare facts from each theatre's own programme page.

## Later (parked)

- [ ] Cast, director and poster from each theatre's production pages (Сатира's `__NEXT_DATA__` has a full credits block with roles; Народен, МГТ, Театър „София“, Théatro, Възраждане, Сълза и смях, 199 have production pages). Directors already come from Théatro and Възраждане listings; admins can fill the rest in Filament
- [x] Posters, decided 2026-10-16 (Preslav): import the theatres' own posters now for testing; ask permission before launch (task under Store releases). Built for Народен театър and Драматичен театър Варна, whose programme pages show them. Stored once per production with `source_url`; never replaces an admin upload; productions without one keep the typographic poster
- [ ] Posters for the rest: Зад канала, Сатира, Театър „София“ and others show them only on production pages (one extra request per production); Вазраждане's are filled by JavaScript. Куклен театър Варна has them in its feed but its 300 s crawl delay rules out extra requests
- [ ] Театър „София“ sold-out rows (no time in the listing)

## After the MVP (not before)

### Preslav tests
- [ ] Go through every MVP goal on a real phone; log issues here
- [ ] Fix round; re-test

### Deploy — Laravel Cloud (account exists)
- [ ] Application in the EU region, linked to `pkaratanev/teatar`, root `backend/`
- [ ] Postgres in the same region (`pg_trgm` enabled by the migration)
- [ ] Environment variables from `backend/.env.example`
- [ ] Object storage for posters and headshots
- [ ] Scheduler (nightly sync) and a queue worker
- [ ] Deploy runs `php artisan migrate --force`; deploy on push to `main` when CI is green
- [ ] Domain and HTTPS for API and admin; first production admin user
- [ ] Web links for sharing (`https://<domain>/production/{id}`): iOS Universal Links + Android App Links, plus a simple web page for people without the app. Until then shared links are `teatar://…` and only open where the app is installed
- [ ] Error tracking, uptime check, backups

### Store releases
- [ ] Apple Developer Program (99 USD/year; company enrolment needs a D-U-N-S number)
- [ ] Google Play Console (25 USD once); new personal accounts must run a closed test with 12+ testers for 14 days before production
- [ ] Final app name, icon, splash, bundle id (placeholder `bg.teatar.app`)
- [ ] `eas.json` profiles (`development`, `preview`, `production`); EAS Build, Submit, Update
- Budget to launch on both stores (checked 2026-10-16): Google Play 25 USD once + Apple 99 USD/year (local equivalent, about €99) ≈ 124 USD the first year, 99 USD/year after. Expo Free plan is enough (15 Android + 15 iOS cloud builds a month, 1,000 update users; slow queue) — Starter (19 USD/month) only if the queue gets annoying. No Mac needed: iOS builds run on EAS. Hosting (Laravel Cloud) is separate
- [ ] Before the public launch: ask each theatre whose posters and descriptions we import for permission (most want the promotion); remove any that say no
- [ ] Privacy policy and terms in Bulgarian; App Store privacy label; Play Data safety form
- [ ] Sign in with Apple, before the first iOS submission — Decided 2026-10-16 (Preslav): build it as soon as the Apple Developer account exists, so the first iOS build isn't rejected under Apple's guideline 4.8 (Google sign-in requires an equivalent privacy-focused login). Android isn't affected. Backend `auth/social` verifies the provider token with Socialite; app button via `expo-apple-authentication`
- [ ] Store rules already covered by the MVP: in-app account deletion, reporting user content
- [ ] Bulgarian store listings, screenshots, age rating
- [ ] TestFlight → external beta; Play internal → closed test (14 days) → production

### Launch (from the original plan, not yet scheduled)
- [ ] Analytics: PostHog EU cloud — log rate, review rate, D7 retention from day one (needs a privacy-policy line and consent)
- [ ] Founding group: 50–100 reviews from friends of the theatre before the public launch, so the app isn't empty
- [ ] Talk to the theatres (posters, programme feeds, cast) and, early, to Art.bg Net, which sells tickets for many of them

## MVP features from the research

From the feature research (Letterboxd, Goodreads, StoryGraph, Stagedoor, Show-Score, Songkick and others).
**Part of the MVP** (Preslav, 2026-10-16: these were meant for the MVP, not after); each has a goal under MVP goals.
A feature is built once its open questions are answered. Order: 1 (done), 6, 5, 2, 4, 3.

- [x] **1. Performance ids survive a nightly import** — *built 2026-10-16* · Size: S · Needed by 4 and 5
  - Goal: know whether a performance keeps its id across nightly imports, so plans and pushes can point at it.
  - Checked from the code 2026-10-16 (PM, not yet tried against a real site change):
    - The import upserts (`Performance::updateOrCreate` in `ProgrammeImporter`), it never deletes and recreates. Key: production + start time (unique `production_id, starts_at`); the stage is not part of the key.
    - Stage, ticket link or price changes: same row, same id (update).
    - Time or date change: a new row with a new id; the old row stays listed, not cancelled (same bug as the Next up to-do "Removed or moved performances stay listed").
    - Production: matched by normalised title + guest flag + visiting company among the theatre's running productions. If the theatre changes the title's spelling (beyond case and quotes), the import creates a new production, so every id under it changes.
    - Diary entries keep working if a performance row is ever deleted (`performance_id` is nullable, `nullOnDelete`).
  - Built 2026-10-16: the matching below (see Next up, "Removed or moved performances"). Still open: planned visits from 4 don't exist yet, so nothing else needs to follow a moved row.
  - Decided 2026-10-16 (Preslav): after each sync, match performances against what the source now lists, so a moved show updates its existing row (same id) instead of leaving the old one and adding a new one. Rows the source no longer lists, with no match, are marked cancelled, not deleted.
  - How to match (built as proposed): use a per-show id from the source where there is one (ticket ids at Сълза и смях, Entase/ePayGO links); otherwise same production, same stage, same day, exactly one old and one new time. Anything ambiguous is cancelled + created, and shows up in the admin review list.

- [ ] **6. "Already seen" onboarding for the user's city** — *not started* · Size: S
  - Goal: after sign-up and city choice, a grid of long-running productions in that city; tap to mark seen, optionally rate.
  - Decided 2026-10-16 (Preslav): onboarding marks are real diary entries with no date (replaces the "seen before" flag idea and its two follow-ups). The user can add the date later. Undated entries can't count in a year's stats; the diary lists them in their own group ("Без дата"). Otherwise they count as seen like any diary entry: the production shows as seen on its page, and they count in the all-time stats (most-seen actor, favourite theatre, ratings histogram).
  - Decided 2026-10-16 (Preslav): the grid shows the most-reviewed productions in that city (the ones people are most likely to have seen).
  - Open, to decide later (Preslav, 2026-10-16): at launch almost nothing has reviews (only the founding reviews), so the grid would be empty or random. Fallback while a city has few reviews: most performances since the import began, or an admin-picked list? Count reviews only, or all "seen" logs (more of them)? Can onboarding be skipped and reached later from the profile?
  - Depends on: one-tap log (built); the user's city on the server (see 5); `diary_entries.seen_on` made nullable, with stats, the diary list and the "Видях" sheet handling a missing date.

- [ ] **4. "I'm going": add to the phone calendar, then a next-day "How was it?" push** — *not started* · Size: S–M
  - Goal: "Add to calendar" on a performance saves a planned visit; the day after, one push "Как беше <production>?" opens the log sheet with the date filled in. No push before the show; the calendar handles that.
  - Decided 2026-10-16 (Preslav): use `expo-calendar` if the feature needs it (new npm package: Preslav updates `mobile/package-lock.json` on his machine). Push is built and covered by tests as if it works; Preslav tests it on a development build later.
  - Open: cancelled calendar event (probably ignore; remove the plan in the app)? Performance cancelled: drop the plan or tell the user? (A moved show keeps its row once item 1's matching exists, so the plan moves with it.) Show "planned" visits to followers or keep them private?
  - Depends on: item 1's matching; a new "planned visits" table (nothing like it exists; the diary is for seen shows); push (development build to test).

- [ ] **2. Yearly theatre goal** — *not started* · Size: S
  - Goal: the user sets how many performances to see this year; progress comes from diary entries.
  - Open: profile only, or also the Програма screen? Past years: keep each year's goal and result, or only the current one? Does the goal reset on 1 January or ask again?
  - Depends on: yearly stats (built: `ProfileStats` counts `seen_this_year` by `seen_on`); a goal per user per year (new column or table).

- [ ] **5. Weekly "new in your city" push** — *not started* · Size: S
  - Goal: once a week, new productions and premieres in the user's city since last week.
  - Open: day and time? Its own on/off switch, separate from watchlist reminders? Skip the push when there's nothing new (likely yes)? Does a guest production count as new?
  - Decided 2026-10-16 (Preslav): save the city on the user's account (today the app keeps it on the phone only); the app sends it when the user picks or changes a city. Push is built and tested as if it works; Preslav tests it on a development build later.
  - Depends on: item 1 (a renamed title would look "new"); "first appeared" = `productions.created_at`, which also counts productions an admin adds; push (development build).

- [ ] **3. Tags for productions, to find similar ones** — *not started* · Size: M
  - Goal: a fixed tag list (comedy, drama, musical, heavy, light, for teens, short, long) to show "similar productions".
  - Open: who sets tags: admin in Filament, users after rating, or both (and how user votes turn into a tag)? Ranking of "similar": shared tags, same director or actors, same theatre? Bulgarian tag names fixed in code or editable in the admin?
  - Depends on: new tags table; the manual review list (MVP goal) if admins tag while reviewing; enough tagged productions before the section is worth showing.

## Open decisions

- [ ] Cast data: fill the most-watched shows in the admin, or un-park the production-page reader for the 3–4 biggest theatres (Народен, Сатира, Българска армия, Театър „София“)?
- [ ] Сфумато: its robots.txt asks bots not to crawl. Respect it (add Сфумато by hand in the admin / later contact) or not?
- [ ] Нов театър НДК looks closed: replace it in the launch set? Candidates: Театър „Азарян“ (НДК), Топлоцентрала, Столичен куклен театър
- [ ] App name (placeholder: Teatar)
- [ ] Store developer accounts: individual or company
- [ ] Box-office links: theatres without per-show ticket links (Българска армия, Младежки, 199, Зад канала, Куклен театър Варна) show no "Билети" button. Claude looks up each theatre's ticket page and adds it to the seeder, or Preslav fills "Каса / билети" in the admin

## Log

Newest first. One line per working session.

- **2026-10-16** — STATUS restructured: the six research features are MVP goals (Preslav: meant for the MVP, not after); "Next up" is now the build order; finished sync work moved to milestone 7; "Now" rewritten.

- **2026-10-16** — Sync management built: "Обнови всички", stuck runs closed, dashboard alerts with a menu badge, nightly time and pause in the admin. Importer tests: 8 new; sync tests: 15 new. Importer now keeps a moved show's id and cancels shows the source dropped. Live sources re-run: both Varna adapters fine.

- **2026-10-16** — Decided (Preslav): onboarding grid = most-reviewed productions in the city.
- **2026-10-16** — Decided (Preslav): onboarding logs real diary entries with no date (date can be added later), instead of a "seen before" flag. They count as seen everywhere except the year's stats.
- **2026-10-16** — Decided (Preslav): "seen before" marks don't show on the production page and don't count towards most-seen actor.
- **2026-10-16** — Exploration list added (6 post-MVP candidates). Decided (Preslav): match moved performances after each sync instead of new rows; city saved on the user; onboarding uses a "seen before" flag (to confirm); `expo-calendar` if needed; push features built as if working, tested later.
- **2026-10-16** — Decided (Preslav): no local sync, `teatar:demo` seeds the data. Sync management scoped: sync-all button, stuck runs, dashboard alerts, schedule time and pause in the admin. Four to-dos under Next up, not started.
- **2026-10-16** — Decided (Preslav): Sign in with Apple before the first iOS submission. To-dos: manage the sync from the admin (scope open); cancel performances a source no longer lists (found while reviewing the sync).
- **2026-10-16** — PM review: docs match the code (new MVP goals all unbuilt; CI green on main). plan.md and CLAUDE.md now record descriptions, Google sign-in, location city and the review list; Apple sign-in timing added to Open decisions; Live sources re-run for Varna added to Next up.
- **2026-10-16** — To-do (not started): manual review queue in the admin for productions with missing data.
- **2026-10-16** — To-dos only, nothing started: descriptions (option c: theatres' own text for testing, permission before launch), Google sign-in (Preslav gets the key later), Apple sign-in after the App Store launch.
- **2026-10-16** — Preslav added three MVP goals: Google sign-in, nearest city from the phone's location, production descriptions. Descriptions need a decision (theatre text is copyrighted prose).
- **2026-10-16** — Docs review against the original plan: README, plan.md (with a "changed since the original plan" section), backend README, STATUS "Now" rewritten; analytics, founding reviews and theatre outreach added under Launch; box-office links added to Open decisions.
- **2026-10-16** — Posters: the importer downloads the theatre's own poster when the programme shows one (Народен театър, Драматичен театър Варна), once per production; resized copy made right away (no queue worker locally). Permission from theatres is a pre-launch task.
- **2026-10-16** — City switcher is now always shown (it hid itself when the city list failed to load, which looked like it was missing on Preslav's phone) and reloads the list when opened.
- **2026-10-16** — Fix: `teatar:demo` crashed on a second run on SQLite (diary dates); `DiaryEntry::record()` used by the app and the seeder.
- **2026-10-16** — Screenshot workflow no longer runs on every push (slow); run it by hand or locally (README → Screenshots).
- **2026-10-16** — Second city: Varna with 2 theatres (Драматичен театър „Стоян Бъчваров“, Държавен куклен театър), `GET /cities`, city switcher in the Programme header. Both sites allow our bot; the puppet theatre's crawl delay means one request per run.
- **2026-10-16** — Launch budget researched: about 124 USD the first year (Play 25 once, Apple 99/year), Expo Free plan is enough. Noted under Store releases.
- **2026-10-16** — Lock files committed, copied from Preslav's working Windows install. They pin Symfony 8.1, which needs PHP 8.4, so CI, the Windows script and the README now require PHP 8.4.
- **2026-10-16** — Preslav: the restyled app looks great on his phone. Restyle closed; the phone run goal ticked; next is his walk through the MVP goals.
- **2026-10-16** — Windows bundling fix: `babel-preset-expo` added as a direct devDependency (npm had nested it under `expo/`, out of Babel's reach).
- **2026-10-16** — Restyle round 2: typographic posters, day headings, fixes; ready for Preslav to check on the phone.
- **2026-10-16** — Restyle started: React Native Paper → NativeWind 4 + own components (`src/ui`), dark theme, new fonts and icons; web build + CI screenshot workflow so Claude can see the screens.
- **2026-10-16** — First phone run: the app crashed at start in Expo Go on Android (expo-notifications import). Push code now loads lazily and only outside Expo Go.
- **2026-10-16** — First real run of the Windows script: backend set up fine; it stopped at `npm` because Windows blocks `npm.ps1` by default. Now calls `npm.cmd` / `npx.cmd`.
- **2026-10-16** — `scripts/windows.ps1`: one-line setup and start on Windows (installs Git/Node/Herd, SQLite backend, demo data, API + Expo). CI parses it.
- **2026-10-16** — Profile editing (name, bio; `PATCH /me` + 3 tests) and deep links with a share icon on production, theatre, person and user pages. Reminders now also open the production when the tap starts the app; 404s show "not found" instead of retrying.
- **2026-10-16** — STATUS review: each MVP goal now says where the build stands; cast data added to open decisions.
- **2026-10-16** — CI now also bundles the Android app with Metro. MVP feature-complete; handed over for the first test run.
- **2026-10-16** — Setup fix: `composer setup` now publishes Filament's CSS/JS and links storage; without it the admin would load unstyled. CI checks the assets are published.
- **2026-10-16** — Pre-test fix: Programme, Diary and Feed only showed the first API page (Месец stopped at 50 performances); now infinite scroll. Theatre chips hide theatres with nothing scheduled.
- **2026-10-16** — `teatar:demo` for testing: real programme plus demo people and activity. The default seeder no longer mixes fake productions into the real theatres.
- **2026-10-16** — Published a screen map of the app (link at the top) for Preslav, who hasn't run the app yet.
- **2026-10-16** — Theatre filter chips on the Programme screen. Watchlist push reminders (backend job + app registration); testable only with a dev build.
- **2026-10-16** — Social: follow users and people, feed, likes (counts recomputed from the table). Polymorphic columns now use short names (user, person, production, theatre).
- **2026-10-16** — Apple/Google sign-in moved to Store releases: Sign in with Apple needs the paid developer account, which comes after the MVP. Email sign-in is enough for testing. Social (follow, feed, likes) is Now.
- **2026-10-16** — App side of your theatre life: sign in/up, log-a-visit sheet, watchlist, review feed with spoilers and reporting, Diary and Profile tabs, public profiles. Typechecked in CI; not yet run on a phone.
- **2026-10-16** — Your-theatre-life API with 16 feature tests: ratings, one editable review per show (hidden stays hidden), diary visits, watchlist, one-tap log, review feed and reports, profile stats. New accounts limited to 10 reviews/hour.
- **2026-10-16** — Preslav: stop adding adapters, enough data for now. Cast/poster pass parked; moving to accounts, ratings, reviews, diary and watchlist.
- **2026-10-15** — Admin dashboard (3 widgets) and the first admin smoke tests. The tests caught 3 real bugs: Filament closures must name their parameter `$query` (the reviews list crashed), widgets were lazy, and `canAccessPanel` returned null for new users. Noted in CLAUDE.md.
- **2026-10-15** — Live check green after the encoding fix: Българска армия 144 performances (18 guest); 717 performances across the 9 reachable theatres. (Earlier entries miscounted theatres by one from Театър „София“ on, and I first reported 817 — corrected here.)
- **2026-10-15** — First live run from GitHub Actions: 9 of 11 adapters OK (e.g. Народен 160 performances to 31 Dec, Сатира 93, Възраждане 70). Българска армия: UTF-8 body with a windows-1251 meta tag got converted twice — fixed. Младежки театър: Cloudflare blocks datacenter IPs; its ticket shop needs a login, so no alternative public source.
- **2026-10-15** — Сатира adapter: turned out easy — `?f:month=` is server-rendered and the month is in the page's Next.js JSON. Importer now also fills age rating. 10 of 12 launch theatres have adapters.
- **2026-10-15** — Театър 199 adapter: all 8 easy theatres done; 9 of 12 launch theatres now have adapters. Left: Сатира (medium), Сфумато (robots.txt, decision pending), Нов театър НДК (closed, decision pending).
- **2026-10-15** — Младежки театър adapter: same Art.bg platform as ТБА; a month that isn't published returns another month, so the heading is checked and such pages yield nothing. **8 theatres have adapters — gate reached.**
- **2026-10-15** — Сълза и смях adapter (7/8): calendar gives dates, stage lists give stage + production page, joined on the ticket id. 7 theatres have adapters.
- **2026-10-15** — Театър „София“ adapter (6/8): `?page=N` doesn't page; `?year=&month=` returns the whole month. Sold-out rows have no time, skipped for now. 6 theatres have adapters.
- **2026-10-15** — МГТ „Зад канала“ adapter (5/8): Drupal table with ISO timestamps, simplest so far. No ticket URLs on the site (popup only); app falls back to the theatre's box-office link.
- **2026-10-15** — Възраждане adapter (4/8). Importer now adds a director credit when a listing names one and the production has none (Théatro and Възраждане list directors).
- **2026-10-15** — Théatro adapter (3/8): the "stale block" is page-builder rows hidden at every screen size, skipped by class; cancellation notices skipped; year inferred with `BulgarianDate`.
- **2026-10-15** — Народен театър adapter (2/8 easy theatres): exact date and time from the ticket link; fills a missing author; guest shows are labelled without a company, so productions now have `is_guest`. Shared `BulgarianDate` helper with year inference.
- **2026-10-15** — Researched the 10 remaining theatres with 4 parallel agents: 8 easy server-HTML programmes, Сатира medium, Сфумато blocked by robots.txt, Нов театър НДК likely closed. Entase isn't a shortcut.
- **2026-10-15** — Reworked around MVP goals: stable MVP → Preslav tests → deploy and stores. Data: use any public source except theatre.art.bg; no outreach until after the MVP. Guest shows are in: listed under the host theatre with the visiting company; quote-safe title cleaning shared by all adapters.
- **2026-10-15** — Българска армия adapter (windows-1251 monthly page, Latin look-alike letters folded). tba.art.bg runs on Art.bg's platform; we take only facts from the theatre's page.
- **2026-10-15** — Hosting decided: Laravel Cloud, after the MVP.
- **2026-10-15** — Plan settled; scaffold pushed; CI green.
