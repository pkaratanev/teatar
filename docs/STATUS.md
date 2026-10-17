# Project status

**Last updated:** 2026-10-17 · **Stage:** 12 of 26 MVP goals ticked; backend deploy and closed Play test prepared · **Next:** Preslav deploys ([deploy.md](deploy.md)); box-office links, then city from location

Screen map of the app (what each screen shows and where it leads): https://claude.ai/artifact/SHD5q6aU93xAYBA9YTG3XH

This is the single source of truth for the project. Update it in the same commit as the work: tick what's
done, move "Now", add what you found, add a Log line. Plan and rationale: [plan.md](plan.md). How we test: [testing.md](testing.md).

**Order of work** (changed by Preslav 2026-10-17): deploy the backend now and put the app on a closed Google Play
test, keep building the MVP, and go public only once the MVP goals are ticked and the theatres have agreed to posters.
Deploy steps and the rules that keep the production database: [deploy.md](deploy.md).

## MVP goals

The MVP is done when every box here is ticked. A box is ticked only after Preslav has tried it (or watched
Claude try it in the web build). The note after each goal says where the build stands.

**Catalogue (what's on)**
- [x] Programme for Sofia covers at least 8 of the 12 launch theatres, kept fresh automatically — *checked 2026-10-16: 10 adapters*
- [x] Guest performances are listed too, under the host theatre, marked with the visiting company — *checked 2026-10-16*
- [x] A second city, so switching cities can be tested: Varna with 2 theatres; the app remembers the chosen city — *checked 2026-10-16*
- [ ] Every listed production has a title and theatre; most have author, director and cast — *titles, theatres and most authors yes; directors for some; cast mostly missing → cast reader (Next up 4): built 2026-10-17 for Зад канала, Възраждане, Българска армия, Théatro; tick when production shows cast for most of them*
- [x] Manual review in the admin, productions only for now — *checked 2026-10-16 (Каталог → За преглед; milestone 8)*
- [ ] Productions have a short description — *skipped for now (Preslav, 2026-10-16). When picked up: theatres' own text for testing, permission before launch (option c); the app already shows `synopsis` when set*
- [x] Browse tonight / this week / this month; filter by theatre; search productions, people and theatres — *checked 2026-10-16*
- [ ] On first start the app picks the nearest city from the phone's location (asks permission; София if refused or far from every city); the user can still switch — *not built; `expo-location` is in (Preslav, 2026-10-16), ready to start (Next up 2)*
- [x] Production, person and theatre pages; "Билети" opens the theatre's own box office — *checked 2026-10-16; per-show ticket links for 7 theatres; box-office links for the rest are Next up 1*

**Your theatre life**
- [x] Sign up and sign in with email; delete account — *checked 2026-10-16 (web build)*
- [ ] Sign in with Google — *not started; waits for Preslav's Google OAuth key. Free Google Cloud OAuth client (Android, iOS, web ids); backend `auth/social` verifies the token with Socialite (already installed, no route yet); testing needs a development build (`eas init`, free)*
- [x] Rate ½–5★, write a review with a spoiler flag, mark "seen" with a date, watchlist — *checked 2026-10-16 (web build)*
- [x] Profile: diary by date, stats for the year, ratings histogram — *checked 2026-10-16 (web build)*

**Social and safety**
- [x] Follow users and people; feed of followed users' reviews; like a review — *checked 2026-10-16 (web build)*
- [x] Report a review; moderation in the admin (hide, shadow ban) — *checked 2026-10-16 (web build + admin)*
- [ ] Push reminder when a watchlisted production plays within 7 days — *built; testable only with a development build*

**From the feature research** (Preslav, 2026-10-16: MVP, not after; details under "MVP features from the research")
- [ ] A moved show keeps its performance id; a dropped show is marked cancelled — *built and tested (milestone 7); tick when a real move or cancellation shows in the sync log ("Преместени" / "Отменени")*
- [ ] "Already seen" onboarding: a grid of the city's most-reviewed productions to mark seen (diary entries without a date) — *not started; fallback question left for later*
- [ ] "Отивам": add a performance to the phone's calendar; the next day one push "Как беше …?" opens the log sheet — *not started; three open questions*
- [ ] Yearly theatre goal: how many shows to see this year, progress from the diary — *not started; three open questions*
- [ ] Weekly "new in your city" push; the chosen city is saved on the account — *not started; four open questions*
- [ ] Tags on productions and a "similar productions" section — *not started; three open questions; the biggest of the six*

**Stable**
- [x] Runs end to end on a laptop and on a real phone (Expo Go / dev build) against the local API — *done 2026-10-16*
- [ ] Nightly sync runs for a week with no unexplained failures in the admin's sync log — *needs a machine running the scheduler for a week (in practice: production)*
- [ ] CI green; every adapter has a saved-HTML test; core flows have API tests — *true in CI; tick at sign-off*
- [ ] No known crash or data-loss bug open — *none known*

## Now

**Deploy (Preslav):** Laravel Cloud environment set up (Frankfurt, Postgres, bucket); set its branch to `prod`, turn on
the scheduler and a queue worker, deploy, then the first-deploy commands in [deploy.md](deploy.md). After that: an EAS
preview APK, then the closed Play test.

**Next to build:** box-office links (Next up 1), then city from location (Next up 2; `expo-location` is installed).

**Not testable yet:** push reminders (development build), the week of nightly syncs (scheduler running a week),
moved/cancelled shows (a real change on a theatre's site), and the sign-off goals under Stable.

**How to run it (Windows):** `irm https://raw.githubusercontent.com/pkaratanev/teatar/main/scripts/windows.ps1 | iex`
pulls the code, runs migrations, starts the API in its own window and Expo in this one. Fresh programmes and
posters: `php artisan teatar:demo` in `backend/`. Admin sync buttons need `php artisan queue:work` (no sync runs
locally otherwise, by decision). Browser build: `npm.cmd run web:local` in `mobile/`, then http://localhost:8000/app.
Sign in as `admin@teatar.test` / `password` or `maria.p@demo.teatar.test` / `password`.

**Waiting on Preslav:** the Google OAuth key; the research features' open questions; app name and store
account type (Open decisions); the Сфумато email (Smaller to-dos).

**Screenshots:** `.github/workflows/screens.yml` by hand (Actions → Screenshots) or locally (README) → `screenshots` branch.

## Test findings

- [x] First run and walk-through, 2026-10-16: 10 findings, all fixed (Windows `npm.ps1`, Expo Go crash on `expo-notifications`, `babel-preset-expo`, restyle, `teatar:demo` second run on SQLite, back label, `/app` deep links, repeated demo reviews, "Запази" in График, theatre chip names). Details in the Log
- [ ] Web build only: posters don't load (poster URLs use the LAN address in `APP_URL`, which the browser pane blocks). Not an issue on the phone

## Next up

Build order for the unbuilt MVP goals (Preslav, 2026-10-16; he can reorder):

1. [ ] **Box-office links in the seeder** · Size S — Decided 2026-10-16 (Preslav): Claude adds them.
   - Set `box_office_url` in `LaunchTheatresSeeder`, plus a migration that fills it on existing databases (as `short_name` did), never over an admin's value:
     - Младежки театър: `https://tickets.mlt.bg/` (own shop for all performances, ePay)
     - Театър 199: `https://www.eventim.bg/city/%D1%81%D0%BE%D1%84%D0%B8%D1%8F-7510/venue/teatur199-94261/` (Eventim venue page)
     - МГТ „Зад канала“: `https://zadkanala.bg/programa`. Its per-row "Купи билет" popup pointed to a login page when checked; Preslav checks the popup by hand before any per-show link
     - Държавен куклен театър Варна: `https://vnpuppet.com/купи-онлайн/` (own shop, ePay; the feed has no per-event link)
     - Българска армия: no verified online shop (http-only site, couldn't be checked). Leave empty; see Smaller to-dos
   - Done when: every launch theatre except Българска армия shows "Билети" in the app; a test checks the seeded links
2. [ ] **City from location** · Size S — `expo-location` + a centre point per city (`lat`/`lng` per city; theatres have no coordinates). София if permission is refused or the phone is far from every city. Works in Expo Go
3. [ ] **"Already seen" onboarding** · Size S — research feature 6. The fallback question (few reviews at launch) is left for later; build with a stated provisional fallback and flag it to Preslav
4. [ ] **Cast and credits from production pages** · Size M — Decided 2026-10-16 (Preslav): un-park the production-page reader; admins fill the rest in Filament.
   - **Built 2026-10-17** (`App\Services\CastSync`, readers in `app/Adapters/Cast`): Зад канала, Възраждане, Българска армия, Théatro. Runs after each sync for productions without actors, 25 pages per theatre, re-checks empty ones after 30 days; Зад канала waits 10 s per page (robots.txt). Label → role mapping and name cleaning shared with the admin paste box (`App\Services\Credits`). Posters from these pages are not taken (permission rule). The sync log shows "Екип (нови)"
   - Still to do from the first pass below: Сатира, Народен, Театър „София“ (Младежки stays blocked)
   - One shared reader for labelled credit blocks ("Режисьор", "Автор", "Участват", "В ролите" / "Ролите изпълняват", "Премиера", "Продължителност") plus a small per-theatre selector. Cast stored as people linked to the production, with roles where given
   - Runs once when a production first appears, then monthly (not nightly); one request per production. Every row sets `source` and `source_url`. Never overwrites admin data. New or changed credits flag the production in the review list
   - First pass:
     - Сатира: `satirata.bg/show/{slug}`, role – actor lines under "Ролите изпълняват:"
     - Младежки: `mlt.bg/{slug}___{id}`, roles under "В ролите". Blocked by Cloudflare from GitHub, like its programme
     - Народен: `/bg/predstavlenie/{slug}`, names under "С участието на"; also premiere, duration, age advisory
     - Театър „София“: `/repertoires/{id}/{slug}`, names in capitals to re-case. The programme links `/program/{id}`, so one hop or a title match
     - Зад канала: `/spektakli/{slug}`, names under "Участват"; the same page has the poster, so take it too
   - Quick win, no new requests: "Участват:" in the Драматичен театър Варна programme rows the adapter already downloads. It's cut off with "…", so mark it partial
   - Second pass (Later): 199, Théatro, Възраждане, Сълза и смях (names only; Сълза и смях has messy dashes and nicknames). Българска армия after its probe run
   - Each theatre gets a fixture test with saved HTML
5. [ ] **Weekly push, yearly goal, "Отивам", tags** once their open questions are answered
6. [ ] **Google sign-in** once the OAuth key exists
7. [ ] **Descriptions**, when Preslav picks them up again (option c: theatre's own text as `synopsis` with its source URL, never over an admin's text)

Smaller to-dos:
- [ ] Email Сфумато for written permission to read its programme page (robots.txt disallows crawling). If they agree, add an adapter
- [ ] Българска армия: check online ticket sales with a probe run (tba.art.bg is http-only; production pages checked 2026-10-17)
- [ ] Check robots.txt with our user agent for Топлоцентрала and Столичен куклен театър before any adapter
- [ ] Read Eventim's and Entase's terms of use before using their pages for anything beyond outbound links
- [ ] Code style: Pint fails in CI but is non-blocking (`continue-on-error`), so CI looks green. Run `vendor/bin/pint` once on a machine with Composer, commit, then make the step blocking
- [ ] Live sources writes one annotation per theatre and GitHub keeps 10 per step, so with 12 theatres one is cut. Write one annotation or the job summary instead
- [ ] Following people: notify / highlight new productions with people you follow (nice-to-have)
- [ ] Младежки театър: Cloudflare blocks GitHub's servers (fine from a normal connection). Re-test from the production host when we deploy; fallback is CSV / manual entry. Never work around the check

## Build milestones

1. **Setup and CI — done.** Monorepo (`backend/` Laravel 13, `mobile/` Expo SDK 57); CI: Pint (non-blocking), Larastan, migrations on Postgres 17, Pest, Filament assets, `tsc`, Android Metro bundle; Live sources workflow (manual + weekly); `compose.yaml`; lock files from Preslav's install (PHP 8.4).
2. **Catalogue and data — enough for the MVP.** Tables, Filament catalogue, adapter framework, idempotent importer, nightly sync, CSV adapter, seeder; 12 adapters (10 Sofia + 2 Varna, see Data sources) with saved-HTML tests; shared `BulgarianDate` and `GuestTitle`; guest productions; directors from listings (Théatro, Възраждане); theatres' own posters (Народен, Драма Варна; permission before launch); dashboard and admin smoke tests. Open:
   - [ ] Display casing for ALL-CAPS titles ("СЪН В ЗИМНА НОЩ"): keep the source title, show a tidy one
3. **Read-only app.** API and screens for programme, production, person, theatre, search; guest label; theatre chips; infinite scroll; deep links with share. Open:
   - [ ] Generate API types from Scramble and replace the hand-written ones
4. **Accounts, rating, diary — done.** Email auth (SecureStore), rate/review/diary/watchlist/one-tap log with tests, "Видях" sheet, profile with stats and edit, Diary and Profile tabs, public profiles.
5. **Social, push, moderation.** Moderation queue and shadow ban; reporting from the app; follows, feed, likes; watchlist push (daily 10:00 Sofia, once per 3 weeks). Open:
   - [ ] Push needs `eas init` (project id) and a development build to test on a phone
6. **Stabilise.** `teatar:demo` (real programme + 12 demo people); pre-test fixes done. Open:
   - [ ] Run the nightly sync for a week; fix every failure
   - [ ] Test pass on iOS and Android devices; fix crashes
7. **Sync management — done 2026-10-16.** All starts go through `ProgrammeSync` (skips a theatre already queued or running); "Обнови всички"; stuck runs closed (job timeout 600 s, `failed()`, `AdapterRun::markStale()` every 10 min); `SyncAlerts` widget + menu badge; "График" sets time and pause (`settings` table, `SyncSchedule::register()`), next run shown. `ProgrammeImporter::reconcile()`: a moved show keeps its row and id (per-show ticket link, or same production, stage and day with one old and one new time); unmatched rows cancelled and restored if listed again; admin rows untouched; a run that would cancel more than 5 shows and over half cancels none. Decided 2026-10-16 (Preslav): no sync runs locally; `teatar:demo` fills the data.
8. **Review list and walk-through fixes — done 2026-10-16.** "За преглед" (`ProductionReviewResource`; `needs_review`, `review_reason`, `reviewed_at`, `reviewed_by`; importer flags on create and when it fills data; 9 tests); `theatres.short_name` for chips; web build served at `/app`; back label, demo reviews, "Запази"; poster file names use the slug.

## Data sources

Use whatever public source works: each theatre's own site first, then public ticketing listings
(Entase, Eventim, Bilet.bg, ePayGO) and other public pages. Take bare facts only (title, date, time,
stage, cast, author). Exception: posters and short descriptions, imported for testing only, with permission
asked before launch. Never theatre.art.bg (terms forbid copying; EU database right, see investigation).

| # | Theatre | Programme source | Notes | Verdict | Status |
| --- | --- | --- | --- | --- | --- |
| 1 | Театър „Българска армия“ | `tba.art.bg/programabg.php?year=&month=` | 3 stages; guests "– Гостува X" | Easy | **Adapter done** |
| 2 | Народен театър „Иван Вазов“ | `nationaltheatre.bg/bg/programa?day=&month=&year=` | 6 stages; guests „Гостуващ“; credits on `/bg/predstavlenie/{slug}` | Easy | **Adapter done** |
| 3 | Théatro | `theatro.bg/program/` (one long page) | no year in dates; hidden stale block; guests in title text; Eventim/Entase ticket links | Easy | **Adapter done** |
| 4 | Театър „Възраждане“ | `theatrevazrajdane.bg/programa/?date=YYYYMMDD-YYYYMMDD` | WordPress; one venue; several times per entry | Easy | **Adapter done** |
| 5 | МГТ „Зад канала“ | `zadkanala.bg/programa/YYYY-MM` | full dates; one stage; credits + posters on `/spektakli/{slug}` | Easy | **Adapter done** |
| 6 | Театър „София“ | `sofiatheatre.eu/program?year=Y&month=M` | per-performance pages `/program/{id}`; 2 stages | Easy | **Adapter done** (sold-out rows skipped) |
| 7 | Открита сцена „Сълза и смях“ | `salzaismyah.bg/site/calendar/YYYY/MM` + `/site/events/{1,2,3}` | calendar + stage lists joined on ticket id; many visiting companies | Easy | **Adapter done** |
| 8 | Младежки театър „Николай Бинев“ | `mlt.bg/programa.php?year=&month=` | unpublished month returns another month (heading checked); **Cloudflare blocks datacenter IPs** | Easy | **Adapter done**, blocked live |
| 9 | Театър 199 „Валентин Стойчев“ | `theatre199.org/bg/schedule?page=N` | no year anywhere; one hall; Eventim venue page has full dates | Easy | **Adapter done** |
| 10 | Сатиричен театър „Алеко Константинов“ | `satirata.bg/program?f:month=YYYY-MM` | month in `__NEXT_DATA__` JSON (UTC times, € prices, ePayGO links, age rating) | Easy | **Adapter done** |
| 11 | Театрална работилница „Сфумато“ | `sfumato.info/programa.php?year=&month=` | **robots.txt disallows crawling**; same platform as theatre.art.bg | Blocked | No adapter: robots.txt; permission asked (decided 2026-10-16, Preslav: respect robots.txt, no manual entry for the MVP) |
| 12 | ~~Нов театър НДК~~ | — | NDK said in Jan 2025 the space was never legalised as a theatre and is registered as a shop | Drop | Dropped 2026-10-16: registered as a shop, no reopening date (Preslav; no MVP replacement, the 8-of-12 goal is met) |

**Varna** (second city):

| # | Theatre | Programme source | Notes | Verdict | Status |
| --- | --- | --- | --- | --- | --- |
| V1 | Драматичен театър „Стоян Бъчваров“ | `dramavarna.com/index.php/bg/program-and-tickets?month=M&year=Y` | Joomla; author, director, cast (cut off), ticket links; about 6 weeks ahead | Easy | **Adapter done** (`dramavarna`) |
| V2 | Държавен куклен театър – Варна | `vnpuppet.com/wp-json/tribe/events/v1/events?start_date=&per_page=100` | public JSON; robots.txt Crawl-delay 300, so one request per run; a few weeks ahead | Easy | **Adapter done** (`vnpuppet`) |
| — | Държавна опера Варна | `operavarna.bg/програма/месечна-програма` | opera and ballet; monthly page still shows January 2012 | Skip for now | — |

Findings that shape the code:
- Most sites print dates without a year: `BulgarianDate` infers it from day + month (+ weekday).
- Entase organiser pages load by JavaScript and its API needs a key per organiser; single `entase.com/book?eid=` pages are readable, so ticket links are kept.
- tba.art.bg, mlt.bg and sfumato.info run on theatre.art.bg's vendor platform (`slug___id`); we take only bare facts from each theatre's own programme page.
- Every theatre publishes cast on a production page, mostly as names under "Участват". Only Сатира and Младежки give role → actor pairs. No site publishes the cast for a specific night.

## Later (parked)

- [ ] Топлоцентрала (first post-launch expansion; independent and impro stages for the younger audience): `toplocentrala.bg/program/performance/YYYY/MM`, full dates with year, halls "Зала 1/2". Theatre is mixed with films, concerts and talks with no category field, so it needs keyword filtering and an admin check. Size M
- [ ] Столичен куклен театър: `sofiapuppet.com/programa.php?year=&month=`, same `slug___id` scheme as Българска армия and Младежки (likely a near-copy of those adapters); titles tagged "детска", "семейна" or "за възрастни". Mostly children's shows. Size S
- [ ] Театър „Азарян“ (НДК, Зала 2): closed by fire-safety order; NDK's ticket centre lists no performances there for Oct 2026. Recheck next season
- [ ] Per-show "Билети" for Театър 199 from its Eventim venue page (match title + date, keep the numeric event id)
- [ ] Entase production pages as a credits fallback for visiting companies at Théatro and Възраждане
- [ ] Cast reader, second pass: 199, Сълза и смях (Théatro, Възраждане, Българска армия done 2026-10-17; see Next up 4)
- [ ] Posters for the rest: Сатира, Театър „София“ and others show them only on production pages (one extra request per production; could ride on the cast reader's request); Възраждане's are filled by JavaScript; Куклен театър Варна's crawl delay rules out extra requests. Зад канала's come with the cast reader (Next up 4)
- [ ] Театър „София“ sold-out rows (no time in the listing)

## Deploy and stores

Moved up from "after the MVP" (Preslav, 2026-10-17). Steps: [deploy.md](deploy.md).

### Preslav tests
- [ ] Go through every MVP goal on a real phone; log issues under Test findings
- [ ] Fix round; re-test

### Deploy — Laravel Cloud (account exists; now)
- [ ] Application in the EU region, linked to `pkaratanev/teatar`, root `backend/`
- [ ] Postgres in the same region (`pg_trgm` enabled by the migration)
- [ ] Environment variables from `backend/.env.example`
- [ ] Object storage for posters and headshots — *bucket attached as the `public` disk; S3 package pushed 2026-10-17; check posters after the first sync*
- [ ] Scheduler (nightly sync) and a queue worker
- [ ] Deploy runs `php artisan migrate --force`; Laravel Cloud deploys from the `prod` branch (`git push origin main:prod` when CI is green on `main`) — *branch created 2026-10-17*
- [ ] Domain and HTTPS for API and admin — *for now the Laravel Cloud domain https://teatar-production-8awteq.laravel.cloud (Preslav, 2026-10-17); a custom domain later, keeping this one attached for older builds*
- [ ] First production admin user — *`php artisan teatar:make-admin <email>` (built 2026-10-17)*
- [ ] Email for password-reset codes (SMTP, e.g. Resend free tier) — *password reset built 2026-10-17*
- [ ] Web links for sharing (`https://<domain>/production/{id}`): iOS Universal Links + Android App Links, plus a simple web page for people without the app. Until then shared links are `teatar://…`
- [ ] Error tracking, uptime check, backups

### Store releases
- [ ] Apple Developer Program (99 USD/year; company enrolment needs a D-U-N-S number)
- [ ] Google Play Console (25 USD once); new personal accounts must run a closed test with 12+ testers for 14 days before production
- [ ] Final app name, icon, splash, bundle id (placeholder `bg.teatar.app`)
- [ ] `eas.json` profiles — *`preview` (APK) and `production` (Play bundle) added 2026-10-17; `development` comes with Google sign-in*; `eas init`, API address as an EAS environment variable
- [ ] Firebase (FCM) key for push notifications in real Android builds
- Budget (checked 2026-10-16): ≈ 124 USD the first year (Play 25 once + Apple 99/year), 99 USD/year after. Expo Free plan is enough; no Mac needed (iOS builds on EAS). Hosting is separate
- [ ] Before the public launch: ask each theatre whose posters and descriptions we import for permission; remove any that say no
- [ ] Privacy policy and terms in Bulgarian; App Store privacy label; Play Data safety form — */privacy and /delete-account pages built 2026-10-17 (a starting draft, not legal advice); terms still to write; update /privacy when location or analytics arrive*
- [ ] Sign in with Apple before the first iOS submission — Decided 2026-10-16 (Preslav): build it once the Apple Developer account exists, so the first iOS build isn't rejected under guideline 4.8. Socialite on the backend, `expo-apple-authentication` in the app
- [ ] Store rules already covered by the MVP: in-app account deletion, reporting user content
- [ ] Bulgarian store listings, screenshots, age rating
- [ ] TestFlight → external beta; Play internal → closed test (14 days) → production

### Launch (from the original plan, not yet scheduled)
- [ ] Analytics: PostHog EU cloud — log rate, review rate, D7 retention from day one (needs a privacy-policy line and consent)
- [ ] Founding group: 50–100 reviews from friends of the theatre before the public launch, so the app isn't empty
- [ ] Talk to the theatres (posters, programme feeds, cast) and, early, to Art.bg Net, which sells tickets for many of them

## MVP features from the research

From the feature research (Letterboxd, Goodreads, StoryGraph, Stagedoor, Show-Score, Songkick and others).
Part of the MVP (Preslav, 2026-10-16); each has a goal under MVP goals. Push features are built and tested as if
push works; Preslav tests them on a development build later.

- [x] **1. Performance ids survive a nightly import** — *built 2026-10-16 (milestone 7)*. Still true: if a theatre changes a title's spelling (beyond case and quotes), the import creates a new production, so its ids change.

- [ ] **6. "Already seen" onboarding for the user's city** — *not started* · Size: S
  - Goal: after sign-up and city choice, a grid of that city's productions; tap to mark seen, optionally rate.
  - Decided 2026-10-16 (Preslav): the grid shows the most-reviewed productions in the city. Marks are real diary entries with no date (the user can add it later): listed in the diary under "Без дата", shown as seen on the production page, counted in all-time stats (most-seen actor, favourite theatre, histogram), not in the year's stats.
  - Open, to decide later: fallback while a city has few reviews (most performances since the import began, or an admin-picked list)? Count reviews only, or all "seen" logs? Can onboarding be skipped and reached later from the profile?
  - Depends on: the user's city on the server (see 5); `diary_entries.seen_on` nullable, with stats, the diary list and the "Видях" sheet handling a missing date.

- [ ] **4. "Отивам": add to the phone calendar, then a next-day "How was it?" push** — *not started* · Size: S–M
  - Goal: "Add to calendar" on a performance saves a planned visit; the day after, one push "Как беше <production>?" opens the log sheet with the date filled in. No push before the show.
  - Decided 2026-10-16 (Preslav): `expo-calendar` if needed (Preslav updates the npm lock file).
  - Open: cancelled calendar event (probably ignore; remove the plan in the app)? Cancelled performance: drop the plan or tell the user? Planned visits visible to followers or private?
  - Depends on: a new "planned visits" table (follows a moved show automatically, since the row keeps its id); push (development build to test).

- [ ] **2. Yearly theatre goal** — *not started* · Size: S
  - Goal: the user sets how many performances to see this year; progress comes from diary entries.
  - Open: profile only, or also the Програма screen? Keep past years' goals and results? Reset on 1 January or ask again?
  - Depends on: `ProfileStats` (`seen_this_year`, built); a goal per user per year.

- [ ] **5. Weekly "new in your city" push** — *not started* · Size: S
  - Goal: once a week, new productions and premieres in the user's city since last week.
  - Decided 2026-10-16 (Preslav): save the city on the user's account; the app sends it when the user picks or changes a city.
  - Open: day and time? Own on/off switch, separate from watchlist reminders? Skip when nothing is new (likely yes)? Do guest productions count?
  - Depends on: "first appeared" = `productions.created_at` (also counts admin-added productions; a renamed title would look new); push (development build).

- [ ] **3. Tags for productions, to find similar ones** — *not started* · Size: M
  - Goal: a fixed tag list (comedy, drama, musical, heavy, light, for teens, short, long) to show "similar productions".
  - Open: who sets tags (admin, users after rating, or both)? How "similar" ranks (shared tags, same director or actors, same theatre)? Tag names fixed in code or editable in the admin?
  - Depends on: a tags table; the review list if admins tag while reviewing; enough tagged productions before the section is shown.

## Open decisions

- [ ] App name (placeholder: Teatar)
- [ ] Store developer accounts: individual or company
- [ ] Българска армия box office: no verified online shop, so it shows no "Билети" for now. Revisit after the probe run (Smaller to-dos)

## Log

Newest first. One line per working session.

- **2026-10-17** — Cast reader for Зад канала, Възраждане, Българска армия, Théatro (probe of 3 pages each, saved as fixtures); after each sync, productions without actors get team and cast from their own page. Sync log error column shows the full message on hover.
- **2026-10-17** — Production page: "Внеси екип" pastes team and cast as text (labels like Режисьор/Актьори, characters in brackets); people matched by name in one place (`App\Services\People`), shared with the importer.
- **2026-10-17** — Хора: "Слей в…" merges a duplicate person (credits and followers move, the duplicate is deleted); # column for ids.
- **2026-10-17** — First production sync: Théatro failed with "value too long" on an Eventim ticket URL (Postgres enforces varchar(255), SQLite doesn't). Imported text columns widened to text, slugs capped at 80, odd age labels dropped. Младежки 403 in production too (Cloudflare, known block).
- **2026-10-17** — First production sync: one theatre failed with "value too long" (Postgres enforces varchar limits, SQLite doesn't). Imported text columns widened to text, slugs capped at 80, odd age labels dropped.
- **2026-10-17** — `npm run start:prod`: Expo Go against the Laravel Cloud API.
- **2026-10-17** — Production address: https://teatar-production-8awteq.laravel.cloud (temporary Laravel Cloud domain), set as the API address in the EAS builds.
- **2026-10-17** — S3 package pushed (lock from Preslav's PC); `prod` branch for Laravel Cloud deploys; stale-run check hourly so the app can scale to zero; bucket used as the `public` disk.
- **2026-10-17** — Decided (Preslav): deploy the backend to Laravel Cloud now, closed Play test, public after the MVP. Built for it: posters on a bucket (`MEDIA_DISK`), `teatar:make-admin`, forgotten password with an emailed code (API + app), `eas.json`, /privacy and /delete-account, docs/deploy.md. CLAUDE.md spending rule updated.
- **2026-10-16** — Data-source decisions (Preslav): cast reader un-parked (Next up 4), Нов театър НДК dropped (registered as a shop), Сфумато: respect robots.txt and ask for permission, box-office links to the seeder (Next up 1; Българска армия open). Build order set; new to-dos and parked expansions (Топлоцентрала, Столичен куклен театър, Азарян). STATUS condensed: done milestones and fixed findings summarised, research item 1 trimmed, superseded notes dropped.
- **2026-10-16** — Preslav added `expo-location` (lock file from his machine); city from location ready to build.
- **2026-10-16** — Decided (Preslav): skip descriptions for now; city from location is next.
- **2026-10-16** — Review list, chip names, back label and web-build deep links checked in the browser pane.
- **2026-10-16** — Built the admin review list ("За преглед"; 9 tests). Walk-through fixes: short theatre names for chips (`theatres.short_name`), back label "Назад", varied demo reviews, `/app` deep links tested in CI, poster file names use the slug.
- **2026-10-16** — How we test written down in `docs/testing.md`. Queued syncs ran fine with `queue:work` locally.
- **2026-10-16** — Walk-through with Preslav: catalogue goals checked on his phone; accounts, rating, diary, profile, social and moderation checked by Claude in the web build (served at `/app`) while he watched. Findings: back label read "(tabs), back"; `/app` deep URLs 404'd under `php artisan serve`; same demo review on every production; "Изпрати" in График; chips showed the quoted name ("Иван Вазов"). All fixed.
- **2026-10-16** — STATUS restructured: the six research features are MVP goals (Preslav); "Next up" is the build order.
- **2026-10-16** — Sync management built: "Обнови всички", stuck runs closed, dashboard alerts with a menu badge, nightly time and pause in the admin (15 tests). Importer keeps a moved show's id and cancels dropped shows (8 tests). Live sources: both Varna adapters fine (32 and 5 performances).
- **2026-10-16** — Research features scoped with Preslav: moved shows keep their id; city saved on the user; onboarding = most-reviewed productions as undated diary entries; `expo-calendar` if needed; push features built as if working. Decided: no local sync (`teatar:demo` seeds the data); sync management scope (sync all, stuck runs, alerts, schedule).
- **2026-10-16** — Decided (Preslav): Sign in with Apple before the first iOS submission. PM review: plan.md and CLAUDE.md caught up with the new MVP goals.
- **2026-10-16** — Preslav added MVP goals: Google sign-in, city from location, descriptions (option c), manual review list.
- **2026-10-16** — Docs review against the original plan: README, plan.md ("changed since the original plan"), backend README; analytics, founding reviews and theatre outreach under Launch.
- **2026-10-16** — Posters: the importer downloads the theatre's own poster when the programme shows one (Народен, Драма Варна), once per production.
- **2026-10-16** — City switcher always shown (it hid itself when the city list failed to load) and reloads the list when opened.
- **2026-10-16** — Fix: `teatar:demo` crashed on a second run on SQLite (a `date` cast is stored as "Y-m-d 00:00:00"); `DiaryEntry::record()` matches with whereDate, for the app too.
- **2026-10-16** — Screenshot workflow runs by hand only (slow).
- **2026-10-16** — Second city: Varna with 2 theatres, `GET /cities`, city switcher. Both sites allow our bot; the puppet theatre's crawl delay means one request per run.
- **2026-10-16** — Launch budget researched (Store releases).
- **2026-10-16** — Lock files committed from Preslav's Windows install; they pin Symfony 8.1, so PHP 8.4 everywhere.
- **2026-10-16** — Restyle: React Native Paper → NativeWind 4 + own components (`src/ui`), dark theme, typographic posters; checked on the phone ("looks great"). Windows fix: `babel-preset-expo` as a direct devDependency.
- **2026-10-16** — First phone run: Windows script stopped at `npm.ps1` (now `npm.cmd`/`npx.cmd`); app crashed in Expo Go on `expo-notifications` (now loaded lazily, outside Expo Go only).
- **2026-10-16** — `scripts/windows.ps1`: one-line setup and start on Windows. Profile editing and deep links with share.
- **2026-10-16** — CI bundles the Android app with Metro. MVP feature-complete; handed over for the first test run. Setup publishes Filament assets; lists use infinite scroll; `teatar:demo` added.
- **2026-10-16** — Social (follows, feed, likes), theatre filter chips, watchlist push. Email sign-in for the MVP; Apple/Google later.
- **2026-10-16** — Your-theatre-life API (16 tests) and app screens: sign in/up, log-a-visit sheet, watchlist, reviews with spoilers and reporting, Diary and Profile tabs.
- **2026-10-16** — Preslav: stop adding adapters, enough data for now.
- **2026-10-15** — Admin dashboard and smoke tests; they caught 3 bugs (Filament closures must name `$query`, lazy widgets, `canAccessPanel` null). Noted in CLAUDE.md.
- **2026-10-15** — Live check green after the encoding fix: 717 performances across the 9 reachable theatres; Младежки blocked by Cloudflare from GitHub, its ticket shop needs a login.
- **2026-10-15** — Adapters for 10 of 12 launch theatres: Българска армия, Народен, Théatro, Възраждане, Зад канала, Театър „София“, Сълза и смях, Младежки, Театър 199, Сатира. Research: 8 easy, Сатира medium, Сфумато robots.txt, НДК likely closed; Entase isn't a shortcut.
- **2026-10-15** — Reworked around MVP goals (stable MVP → Preslav tests → deploy and stores); any public source except theatre.art.bg; guest shows in. Hosting: Laravel Cloud, after the MVP.
- **2026-10-15** — Plan settled; scaffold pushed; CI green.
