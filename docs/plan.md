# MVP build plan

The original plan (15 October 2026) is a shared doc: https://claude.ai/code/artifact/90314828-97b1-45d6-b53d-5a091cde0ff9.
This file is the current version; where they disagree, this file wins. Tasks and progress: [STATUS.md](STATUS.md).

## Decisions that shape this codebase

- Bulgarian only (nullable `title_en` kept for later); ratings are 5 stars with halves, stored as `half_stars` 1–10.
- Mobile app only (Expo, iOS and Android), plus a Filament admin panel. No public web pages. The app's web
  build exists only to take screenshots.
- Laravel API (`/api/v1`, Sanctum tokens) + Filament + Postgres with `pg_trgm`. Local Windows setup runs on SQLite.
- App UI: NativeWind (Tailwind) with our own small component kit in `mobile/src/ui/`, dark theme only.
- Hosting: Laravel Cloud (EU region) for the API, admin, scheduler and queue, paid for only after Preslav has
  tested the MVP. App builds via EAS to the App Store and Google Play.
- Cities: Sofia (12 drama theatres; 10 with adapters) and Varna (2 theatres). A city is a column on `theatres`,
  so another city is data, not code.
- Order of work: stable MVP (goals in STATUS.md) → Preslav tests → deploy to Laravel Cloud → store releases.
  No spending before the MVP is tested.

## Data

Source facts from any public page that has them: each theatre's own site first, then public ticketing
listings and other pages. Take bare facts only (title, date, time, stage, cast, author). Contact theatres
only for what can't be found, after the MVP. Guest performances are listed under the host theatre with
`visiting_company` set.

The exception is theatre.art.bg itself: its terms forbid copying and its catalogue is protected by the EU
database right. Every catalogue row records `source` and `source_url`. Sites that block bots or disallow
crawling in robots.txt are reported as blocked, never worked around; a robots.txt crawl delay is respected.

Posters and descriptions are the exception to "bare facts": the importer takes the theatre's own poster
(built) and short description (to-do) for testing. Before the public launch we ask each theatre for permission
and remove any that say no.

Wrong data shouldn't reach the launch: productions with missing fields, or just created by an importer, go to
a review list in the admin until someone marks them checked (to-do, MVP goal in STATUS).

## Changed since the original plan

| Original plan (15 Oct) | Now | Why |
| --- | --- | --- |
| React Native Paper (Material) for the UI | NativeWind + own components, dark theme, Playfair Display + Inter | Preslav's first test: everything worked, the Material look didn't |
| Hosting: EU VPS with Forge, or Laravel Cloud | Laravel Cloud, after the MVP is tested | Preslav's choice; no spending before testing |
| Sign in with email, Apple and Google in the accounts phase | Email and Google in the MVP; Apple after the App Store launch (to-do) | Google needs only a free OAuth client; Apple needs the paid developer account (decided 16 Oct) |
| Queue: Redis + Horizon | Database queue (`QUEUE_CONNECTION=database`) | Enough at MVP scale; one less service to run |
| Launch: 12 Sofia drama theatres; opera, musical and puppet theatres after launch | Sofia (10 with adapters) + Varna, including Варна's puppet theatre | Preslav wants a second city in the MVP to test switching. Puppet credits still use the generic roles |
| #12 "Театро / Ателие 313 (pick one)" | Théatro | Its programme page was easy to read |
| Сфумато and Нов театър НДК in the launch set | Not imported | Сфумато's robots.txt disallows crawling; НДК looks closed (Open decisions in STATUS) |
| Programme sources: each theatre's own site, contact theatres and Art.bg Net before launch | Any public source except theatre.art.bg; outreach after the MVP | Facts are free to take; outreach is slow and not needed to test |
| Posters: ask theatres for press kits, placeholder until then | Theatres' own posters imported for testing; typographic poster otherwise; permission before launch | A placeholder for every show looked empty (decided 16 Oct) |
| Descriptions not mentioned | Theatres' own short descriptions imported for testing; permission before launch | Productions looked empty without one (option c, decided 16 Oct) |
| City chosen by hand | Nearest city picked from the phone's location on first start; switcher stays | Added to the MVP goals 16 Oct |
| Admin edits catalogue data freely | Plus a review list for productions with missing or freshly imported data | "Don't launch with wrong data" (Preslav, 16 Oct) |
| Analytics with PostHog from day one | Not built yet | Moved to Launch in STATUS; needs consent and a privacy policy |
| ~20-week build, launch around 1 March 2027 | MVP built in the first days; phases replaced by MVP goals → test → deploy → stores | The build went faster than planned; dates now depend on testing and the store accounts |
| Beta phase with 50–100 founding reviews | Still planned, under Launch in STATUS | Not dropped, just not scheduled |
| Performances unique on production + stage + start time | Unique on production + start time | A production can't play twice at the same moment |

Unchanged: Laravel + Filament + Postgres, Expo Router + TanStack Query, the data model's tables, adapters
tested against saved HTML, Scramble for the API contract (app types still hand-written, task in STATUS),
deep links `teatar://…` with web links planned for the deploy.
