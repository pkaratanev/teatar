# MVP build plan

The living plan is a shared doc: https://claude.ai/code/artifact/90314828-97b1-45d6-b53d-5a091cde0ff9

Decisions that shape this codebase:

- Bulgarian only (nullable `title_en` kept for later); ratings are 5 stars with halves, stored as `half_stars` 1–10.
- Mobile app only (Expo), plus a Filament admin panel. No public web pages.
- Laravel API (`/api/v1`, Sanctum tokens) + Filament + Postgres with `pg_trgm`.
- Hosting: Laravel Cloud (EU region) for the API, admin, scheduler and queue; app builds via EAS to the App Store and Google Play.
- Launch: 12 Sofia drama theatres, programmes from each theatre's own source. Gate: 8 of 12 with a usable source.

## Data

Never extract from theatre.art.bg: its terms forbid copying and its catalogue is protected by the EU
database right. Bare facts (title, date, time, venue, cast) gathered from each theatre's own site, feed or
spreadsheet are fine. Every catalogue row records `source` and `source_url`.
