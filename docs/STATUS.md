# Project status

**Last updated:** 2026-10-15 (end of day) · **Stage:** building the MVP, locally · **Then:** Preslav tests → deploy → stores

Screen map of the app (what each screen shows and where it leads): https://claude.ai/artifact/SHD5q6aU93xAYBA9YTG3XH

This is the single source of truth for the project. Update it in the same commit as the work: tick what's
done, move "Now", add what you found, add a Log line. Plan and rationale: [plan.md](plan.md).

**Order of work:** build a stable MVP → Preslav tests it → only then pay for hosting and store accounts.
No deploy work before the MVP goals below are met.

## MVP goals

The MVP is done when every box here is ticked. These are the acceptance criteria for Preslav's testing.

**Catalogue (what's on)**
- [ ] Programme for Sofia covers at least 8 of the 12 launch theatres, kept fresh automatically
- [ ] Guest performances are listed too, under the host theatre, marked with the visiting company
- [ ] Every listed production has a title and theatre; most have author, director and cast
- [ ] Browse tonight / this week / this month; filter by theatre; search productions, people and theatres
- [ ] Production, person and theatre pages; "Билети" opens the theatre's own box office

**Your theatre life**
- [ ] Sign up and sign in (email for testing; Apple and Google before the store release); delete account
- [ ] Rate ½–5★, write a review with a spoiler flag, mark "seen" with a date, watchlist
- [ ] Profile: diary by date, stats for the year, ratings histogram

**Social and safety**
- [ ] Follow users and people; feed of followed users' reviews; like a review
- [ ] Report a review; moderation in the admin (hide, shadow ban)
- [ ] Push reminder when a watchlisted production plays within 7 days

**Stable**
- [ ] Runs end to end on a laptop and on a real phone (Expo Go / dev build) against the local API
- [ ] Nightly sync runs for a week with no unexplained failures in the admin's sync log
- [ ] CI green; every adapter has a saved-HTML test; core flows have API tests
- [ ] No known crash or data-loss bug open

## Now

- [ ] Pre-test hardening: API checks for every screen's data (programme day grouping, empty states), and a review of what a first-time user sees

**Data work is paused (Preslav, 2026-10-16):** we have enough theatres for now. No new adapters; the
cast/poster pass is parked under "Later" below.

**Waiting on Preslav:** the two open decisions below (Сфумато, Нов театър НДК), and running it locally once.

## Next up

- [ ] Following people: notify / highlight new productions with people you follow (nice-to-have)
- [ ] Младежки театър: Cloudflare bot check blocks GitHub's servers (fine from a normal connection). Re-test from the production host when we deploy; fallback is CSV / manual entry in the admin. Never work around the check.

## Build milestones

### 1. Setup and CI — done
- [x] Monorepo on GitHub: `backend/` (Laravel 13) + `mobile/` (Expo SDK 57)
- [x] CI: Pint, Larastan, migrations on Postgres 17, Pest; mobile `tsc`
- [x] Local Postgres via `compose.yaml`
- [ ] Preslav runs it locally once (`composer setup`, `php artisan teatar:demo`, Expo Go on a phone); commit `composer.lock` + `package-lock.json`

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
- [x] Theatre filter on the Programme screen (chips)
- [ ] Generate API types from Scramble and replace hand-written ones
- [ ] Deep links `teatar://production/{id}`

### 4. Accounts, rating, diary
- [x] Email register / login / logout / delete account (API)
- [x] Sign-in / sign-up screen (email), token in SecureStore, auth context
- [x] Rate, review, "seen" diary, watchlist, one-tap log — API with tests (`ActivityController`, `TheatreLog`)
- [x] Profile API: public profile with stats (this year, histogram, favourite theatre, most-seen actor); me/diary, me/watchlist, me/ratings
- [x] Same in the app: "Видях" sheet (date, ½–5★ tap input, review, spoiler switch), watchlist toggle, my rating and dates on the production page, review feed with hidden spoilers
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
- [x] `php artisan teatar:demo`: real programme + 12 demo people with ratings, reviews (some spoilers), diaries, watchlists, follows, likes; admin's own Feed/Diary/Profile filled too

## Data sources

Use whatever public source works: each theatre's own site first, then public ticketing listings
(Entase, Eventim, Bilet.bg, ePayGO) and other public pages. Take bare facts only (title, date, time,
stage, cast, author). Contact theatres only for what we can't find — decide that after the MVP.

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

Findings that shape the code:
- Most sites print dates without a year (Народен, Théatro, Възраждане, Театър 199). Needs one shared helper: infer the year from day + month (+ weekday when present) relative to today.
- Entase is not a shortcut: organiser pages load events by JavaScript and the API needs a key per organiser. Individual `entase.com/book?eid=` pages are readable, so ticket links can still be kept.
- tba.art.bg, mlt.bg and sfumato.info share URL patterns (`slug___id`) and ids with theatre.art.bg — the same vendor runs them. We take only bare facts from each theatre's own programme page.

## Later (parked)

- [ ] Cast, director and poster from each theatre's production pages (Сатира's `__NEXT_DATA__` has a full credits block with roles; Народен, МГТ, Театър „София“, Théatro, Възраждане, Сълза и смях, 199 have production pages). Directors already come from Théatro and Възраждане listings; admins can fill the rest in Filament
- [ ] Posters: a rights question too (they belong to the theatres) — decide whether to link, ask for press kits, or use placeholders
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
- [ ] Error tracking, uptime check, backups

### Store releases
- [ ] Apple Developer Program (99 USD/year; company enrolment needs a D-U-N-S number)
- [ ] Google Play Console (25 USD once); new personal accounts must run a closed test with 12+ testers for 14 days before production
- [ ] Final app name, icon, splash, bundle id (placeholder `bg.teatar.app`)
- [ ] `eas.json` profiles (`development`, `preview`, `production`); EAS Build, Submit, Update
- [ ] Privacy policy and terms in Bulgarian; App Store privacy label; Play Data safety form
- [ ] Sign in with Apple and Google: needs the Apple Developer account and a Google OAuth client, so it comes with the store accounts. Backend `auth/social` verifies the provider token with Socialite; app buttons via `expo-apple-authentication` and Google sign-in. Apple sign-in is required by the App Store once Google is offered
- [ ] Store rules already covered by the MVP: in-app account deletion, reporting user content
- [ ] Bulgarian store listings, screenshots, age rating
- [ ] TestFlight → external beta; Play internal → closed test (14 days) → production

## Open decisions

- [ ] Сфумато: its robots.txt asks bots not to crawl. Respect it (add Сфумато by hand in the admin / later contact) or not?
- [ ] Нов театър НДК looks closed: replace it in the launch set? Candidates: Театър „Азарян“ (НДК), Топлоцентрала, Столичен куклен театър

- [ ] App name (placeholder: Teatar)
- [ ] Store developer accounts: individual or company

## Log

Newest first. One line per working session.

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
