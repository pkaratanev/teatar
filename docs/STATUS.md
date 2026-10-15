# Project status

**Last updated:** 2026-10-15 · **Stage:** building the MVP, locally · **Then:** Preslav tests → deploy → stores

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
- [ ] Sign up and sign in (email, Apple, Google); delete account
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

- [ ] Adapter: Младежки театър „Николай Бинев“ (`mlt.bg/programa.php` + `tickets.mlt.bg` for future months)

## Next up

Adapters in this order (easiest first, see Data sources):
- [ ] Театър 199 → Сатира
- [ ] Credits and posters from each theatre's production pages (second pass, after programmes)

## Build milestones

### 1. Setup and CI — done
- [x] Monorepo on GitHub: `backend/` (Laravel 13) + `mobile/` (Expo SDK 57)
- [x] CI: Pint, Larastan, migrations on Postgres 17, Pest; mobile `tsc`
- [x] Local Postgres via `compose.yaml`
- [ ] Preslav runs it locally once (`composer setup`, Expo Go on a phone); commit `composer.lock` + `package-lock.json`

### 2. Catalogue and data — in progress
- [x] Tables, Filament admin for catalogue, adapter framework, idempotent importer, nightly sync, CSV adapter
- [x] Seeder for the 12 launch theatres
- [x] Adapter: Българска армия (`TbaAdapter`)
- [x] Adapter: Народен театър (`NationalTheatreAdapter`: date/time from ticket link, author line, „Гостуващ“ label, skips building tours)
- [x] Adapter: Théatro (`TheatroAdapter`: one long page, year inferred, hidden promo rows and cancellation notices skipped)
- [x] Adapter: Възраждане (`VazrajdaneAdapter`: Entase-fed cards, guest category, several times per card, Entase ticket links)
- [x] Adapter: МГТ „Зад канала“ (`ZadKanalaAdapter`: Drupal table with ISO timestamps)
- [x] Adapter: Театър „София“ (`SofiaTheatreAdapter`: month pages, selected-month check, other venues as stage)
- [x] Adapter: Сълза и смях (`SalzaISmyahAdapter`: month calendar joined with per-stage lists by ticket id)
- [ ] Театър „София“ sold-out rows have no time or stage, so they're skipped; read them from the production pages (`/repertoires/{id}`) later
- [x] Shared date helper `BulgarianDate`: month names (full, abbreviated, English), weekday, year inference
- [x] Guest performances listed under the host theatre (`productions.is_guest` + optional `visiting_company`)
- [x] Programme sources researched for all 12 (Data sources table)
- [ ] Adapters for 2 more theatres: Младежки театър, Театър 199 (6 of the 8 easy ones done; Сатира is medium)
- [x] Director credits from programme listings where shown (Théatro, Възраждане)
- [ ] Cast and remaining credits from theatres' production pages
- [ ] Posters (theatres' own production pages)
- [ ] Admin dashboard widget: productions missing author / cast / poster
- [ ] Display casing for ALL-CAPS titles ("СЪН В ЗИМНА НОЩ", "ТЕАТЪР") — several sites shout; keep the source title, show a tidy one

### 3. Read-only app
- [x] API: programme, production, person, theatre, search
- [x] Screens: Programme, Search, Production, Person, Theatre (first pass)
- [x] "гостува …" label on visiting productions
- [ ] Theatre filter on the Programme screen
- [ ] Generate API types from Scramble and replace hand-written ones
- [ ] Deep links `teatar://production/{id}`

### 4. Accounts, rating, diary
- [x] Email register / login / logout / delete account (API)
- [ ] Sign in with Apple and Google
- [ ] Sign-in screens in the app
- [ ] Rate, review, "seen", watchlist — API + screens
- [ ] Profile: diary, stats, histogram

### 5. Social, push, moderation
- [x] Moderation queue in Filament, shadow ban
- [ ] Report a review (API + app)
- [ ] Follow, feed, likes
- [ ] Watchlist push (Expo Push)

### 6. Stabilise
- [ ] Run the nightly sync for a week; fix every failure
- [ ] Test pass on iOS and Android devices; fix crashes
- [ ] Seed a few real reviews so screens aren't empty

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
| 8 | Младежки театър „Николай Бинев“ | `mlt.bg/programa.php?year=&month=`; future months via `tickets.mlt.bg` | unpublished month silently returns another month — check the page heading; guests "– Гостува X"; Latin look-alikes | Easy | Now |
| 9 | Театър 199 „Валентин Стойчев“ | `theatre199.org/bg/schedule?page=N` | no year anywhere; one hall; Eventim venue page has full dates | Easy | |
| 10 | Сатиричен театър „Алеко Константинов“ | `satirata.bg/program` | only the current month in HTML (filter is JS); rows have day + weekday only; credits with roles on `/show/{slug}` | Medium | |
| 11 | Театрална работилница „Сфумато“ | `sfumato.info/programa.php?year=&month=` | **robots.txt disallows crawling**; same platform as theatre.art.bg | Blocked | See decisions |
| 12 | ~~Нов театър НДК~~ | — | reported closed since Aug 2024; its own site blocks crawlers | Drop | See decisions |

Findings that shape the code:
- Most sites print dates without a year (Народен, Théatro, Възраждане, Театър 199, Сатира). Needs one shared helper: infer the year from day + month (+ weekday when present) relative to today.
- Entase is not a shortcut: organiser pages load events by JavaScript and the API needs a key per organiser. Individual `entase.com/book?eid=` pages are readable, so ticket links can still be kept.
- tba.art.bg, mlt.bg and sfumato.info share URL patterns (`slug___id`) and ids with theatre.art.bg — the same vendor runs them. We take only bare facts from each theatre's own programme page.

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
- [ ] Store rules already covered by the MVP: in-app account deletion, Sign in with Apple, reporting user content
- [ ] Bulgarian store listings, screenshots, age rating
- [ ] TestFlight → external beta; Play internal → closed test (14 days) → production

## Open decisions

- [ ] Сфумато: its robots.txt asks bots not to crawl. Respect it (add Сфумато by hand in the admin / later contact) or not?
- [ ] Нов театър НДК looks closed: replace it in the launch set? Candidates: Театър „Азарян“ (НДК), Топлоцентрала, Столичен куклен театър

- [ ] App name (placeholder: Teatar)
- [ ] Store developer accounts: individual or company

## Log

Newest first. One line per working session.

- **2026-10-15** — Сълза и смях adapter (7/8): calendar gives dates, stage lists give stage + production page, joined on the ticket id. **Gate reached: 8 of 12 theatres have adapters.**
- **2026-10-15** — Театър „София“ adapter (6/8): `?page=N` doesn't page; `?year=&month=` returns the whole month. Sold-out rows have no time, skipped for now. 7 theatres now have adapters (gate is 8).
- **2026-10-15** — МГТ „Зад канала“ adapter (5/8): Drupal table with ISO timestamps, simplest so far. No ticket URLs on the site (popup only); app falls back to the theatre's box-office link.
- **2026-10-15** — Възраждане adapter (4/8). Importer now adds a director credit when a listing names one and the production has none (Théatro and Възраждане list directors).
- **2026-10-15** — Théatro adapter (3/8): the "stale block" is page-builder rows hidden at every screen size, skipped by class; cancellation notices skipped; year inferred with `BulgarianDate`.
- **2026-10-15** — Народен театър adapter (2/8 easy theatres): exact date and time from the ticket link; fills a missing author; guest shows are labelled without a company, so productions now have `is_guest`. Shared `BulgarianDate` helper with year inference.
- **2026-10-15** — Researched the 10 remaining theatres with 4 parallel agents: 8 easy server-HTML programmes, Сатира medium, Сфумато blocked by robots.txt, Нов театър НДК likely closed. Entase isn't a shortcut.
- **2026-10-15** — Reworked around MVP goals: stable MVP → Preslav tests → deploy and stores. Data: use any public source except theatre.art.bg; no outreach until after the MVP. Guest shows are in: listed under the host theatre with the visiting company; quote-safe title cleaning shared by all adapters.
- **2026-10-15** — Българска армия adapter (windows-1251 monthly page, Latin look-alike letters folded). tba.art.bg runs on Art.bg's platform; we take only facts from the theatre's page.
- **2026-10-15** — Hosting decided: Laravel Cloud, after the MVP.
- **2026-10-15** — Plan settled; scaffold pushed; CI green.
