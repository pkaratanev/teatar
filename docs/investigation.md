# Bulgarian Theatre App — MVP Investigation

Oct 15, 2026 · @Preslav Minchev Karatanev

## Summary

A Letterboxd-style app for Bulgarian theatre is feasible and there is no direct competitor: theatre.art.bg is the only nationwide catalogue, it is a 1990s-era PHP site with no app, and its ratings and comments are its weakest part. The market is small but real: 81 theatres, 16,586 performances and roughly 2.3 million visits in 2024 (NSI).

The hard problem is not the app, it is the data. theatre.art.bg's terms forbid copying its content, and under EU database law a wholesale scrape of its catalogue would be an infringement. The MVP should therefore build its own catalogue: seed it with a small set of Sofia theatres whose programmes come from the theatres' own sites, ticketing partners or direct agreements, and let the community fill in cast, crew and reviews.

Recommended MVP: a cross-platform app (React Native or Flutter) with a browsable programme for Sofia, production pages linked to people pages, a 5-star rating, written reviews, a "seen" diary and a watchlist. No ticketing; each production links out to the theatre's own box office. Target: 10 to 15 Sofia theatres and 300 to 500 active productions at launch, then expand city by city.

## The opportunity

Bulgarian theatre is a niche with a loyal, repeat audience and no modern consumer product serving it. Attendance has recovered past pre-pandemic levels and is still growing.

| Year | Theatres | Performances | Visits |
| --- | --- | --- | --- |
| 2021 | 71 | 9,970 | 885,000 |
| 2022 | 72 | 13,193 | 1,712,000 |
| 2023 | 83 | 16,125 | 2,249,000 |
| 2024 | 81 | 16,586 | \~2,300,000 (+2.4%) |

Source: [NSI, Culture 2024](https://www.nsi.bg/en/press-release/kultura-biblioteki-kina-filmovo-proizvodstvo-radio-i-televizionna-programa-scenichni-izkustva-i-muzei-7897), [NSI, Culture 2023](https://www.nsi.bg/en/press-release/main-data-on-culture-in-the-republic-of-bulgaria-for-2023-7300), [NSI, Culture 2022](https://www.nsi.bg/en/press-release/main-data-on-culture-in-the-republic-of-bulgaria-for-2022-6963), [NSI, Culture 2021](https://www.nsi.bg/en/file/23754/Culture2021_en_SNXTI8Y.pdf). In 2024 the 34 dramatic theatres gave 6,376 performances and the 22 puppet theatres 5,954; the rest are opera, operetta, dramatic-puppet and circus.

**What theatre.art.bg does today.** It is the de facto national catalogue: 7,851 productions, 6,984 people, 219 theatres and stages across 32 cities, daily programmes three months ahead, and ticket sales for a subset of Sofia theatres via ePay. It runs on server-rendered PHP in windows-1251 encoding, has no mobile app, no API and no personalisation.

**Where it is weak, and where the app wins.**

- Ratings are a 0-6 mask scale with no account needed, so they are easily gamed and carry little trust.
- Comments are a flat, anonymous list; there are no profiles, no follow, no diary, no lists, no "seen it".
- Person pages exist but are thin: a photo and a list of productions, nothing about roles or history.
- Search and discovery are by city and date only; no genre, no "similar to", no recommendations.
- The site is built around selling tickets; the audience's own record of what they saw is an afterthought.

The Letterboxd model inverts that: the user's diary and taste are the product, and the catalogue exists to serve them. That is the gap.

## Competitive landscape

Nobody in Bulgaria does audience reviews or a theatre diary; every local player is a listing or a ticket shop. The model you want has been proven in New York, London and Russia.

**Bulgaria**

| Player | What it is | Overlap with the MVP |
| --- | --- | --- |
| [theatre.art.bg](https://www.theatre.art.bg/) (Art.bg Net OOD, since 1997) | National catalogue + ticket sales for some Sofia theatres; basic ratings and comments | High on catalogue, low on community |
| [Eventim.bg](https://www.eventim.bg/) | Largest event ticket seller in Bulgaria, \~773K monthly visits (Semrush, May 2026); theatre is one category | None on reviews; a possible outbound link target |
| [Entase](https://www.entase.com/) (Sofia) | Ticketing and event-management platform sold to venues; many Bulgarian theatres use it | None on reviews; a possible data partner |
| [ePayGO](https://epaygo.bg/) (EasyPay) | Event ticketing with 3,200 cash desks | None |
| [Bilet.bg](https://bilet.bg/) | Event ticketing | None |
| Individual theatre sites (e.g. tba.art.bg, nationaltheatre.bg) | Each theatre's own programme and box office | Primary sources for the catalogue |

**International analogues**

| Product | Market | Model | Lesson |
| --- | --- | --- | --- |
| [Letterboxd](https://letterboxd.com/) | Film, global | Diary, ratings, lists, follow; freemium | The UX pattern to copy: log, rate, review, list |
| [Show-Score](https://www.show-score.com/) | NYC theatre | Audience score next to critic score; 300K+ members; alerts | Audience vs critic split is the hook; lists every show in one city |
| [Stagedoor](https://www.seedrs.com/stagedoor) | London theatre | Discovery + journal + reviews; verified venue profiles; 10-15% ticket commission | Venues onboarded as partners; review cards handed out at the door |
| [Mezzanine Theatre Diary](https://lmda.org/?p=2560) | US, iOS | Personal theatre diary with social layer; free + paid cosmetics | Works even with small user bases per city |
| [Teatrum](https://apps.apple.com/ru/app/id1613484127) | Russia, iOS | Afisha + reviews + ratings + artist posts; filter by city, genre, theatre, actor | Closest full analogue for a Cyrillic-language market |
| StagePort, Playbill | US | Journaling / news apps | Social stats and bragging rights drive retention |

Source: [Stage and Cinema, best theatre apps 2025](https://stageandcinema.com/2025/08/23/best-apps-sites-theatre/), [Semrush events sites BG](https://www.semrush.com/trending-websites/bg/events-services), [Show-Score about](https://showscore.substack.com/about), [Stagedoor on Seedrs](https://www.seedrs.com/stagedoor).

## Data: where the catalogue comes from

Do not build the product on a scrape of theatre.art.bg. Technically it is easy; legally it is the single biggest risk to the project.

**What theatre.art.bg exposes.** Every page is plain server-rendered HTML with stable URL patterns and sequential numeric ids, so the whole catalogue (productions, people, theatres, schedules, ratings, comments, photos) could be pulled with a few hundred lines of code. robots.txt blocks only `/inc/` and `/admin/`.

**Why that is not an option.**

- Its [terms of use](https://www.theatre.art.bg/pages.php?pageId=11) (section III) state that all IP belongs to Art.bg Net OOD and that users may not copy, reproduce, adapt or translate any part of the site's content.
- Bulgaria applies the EU Database Directive (96/9/EC, transposed in the Copyright and Related Rights Act). A curated catalogue of this size qualifies for the *sui generis* database right, which forbids extracting or re-using a substantial part of it, regardless of whether individual facts are copyrightable.
- Descriptions, photos and user comments are separately copyrighted works (of the theatres, photographers and commenters).
- A commercial app visibly built from their data invites a cease-and-desist from a 29-year-old incumbent with every theatre's contact details.

**What is safe.** Bare facts are not protected: a play's title, author, venue, date, time and cast list. Gathering those from each theatre's own website, poster or press release is fine. Using theatre.art.bg to *check* coverage or to learn the id of a theatre is fine; bulk extraction is not.

**Recommended sourcing strategy, in order of preference**

1. **Direct from theatres.** Most Sofia theatres publish a monthly programme on their own site (e.g. [tba.art.bg](http://tba.art.bg), [nationaltheatre.bg](https://nationaltheatre.bg)). Write a small adapter per theatre; 10 to 15 adapters cover the launch set. Ask each theatre for a simple feed or a monthly spreadsheet; many will say yes because an app that drives attendance costs them nothing.
2. **Ticketing-partner feeds.** Entase and Eventim both expose event data to partners. An affiliate or data-partner arrangement gives you clean schedules and a revenue-neutral "buy tickets" link.
3. **Community contribution.** Let verified users add productions, fix cast lists and tag roles, with moderation. This is how Letterboxd's and Wikipedia's data got good, and it turns the data gap into an engagement feature.
4. **Licensing talks with Art.bg Net.** Worth one conversation: they may license the catalogue or want to be the ticketing partner. Treat it as an option, not a dependency.

**Minimum data per entity for launch**

| Entity | Must have | Nice to have |
| --- | --- | --- |
| Theatre | Name, city, address, website, box-office link | Logo, stages, phone, hours |
| Production | Title, theatre, author, director, cast (name + role), poster, short synopsis (own words or theatre-supplied), age advisory, premiere date, status (running / closed) | Genre tags, running time, trailer |
| Performance | Production, date, time, stage | Price range, ticket URL |
| Person | Name, photo, roles (actor / director / designer) | Bio, birth year, awards |

## MVP scope

The MVP is Sofia only, four screens deep, and shippable in roughly three to four months by a two-person team. Everything that needs a theatre's cooperation to work (ticketing, seat maps, official accounts) is out.

**In**

- **Browse.** Tonight / this week / this month in Sofia; filter by theatre and genre; search by title, person or theatre.
- **Production page.** Poster, synopsis, theatre, author, director, cast with roles, upcoming dates, average rating, review feed, "Buy tickets" link to the theatre's own box office.
- **Person page.** Photo, roles grouped by production, current vs past; follow.
- **Theatre page.** Address, map link, current repertoire, upcoming dates.
- **Log a visit.** Mark a production as seen (with date), rate it (1-5 stars with halves), write a review, mark spoilers.
- **Watchlist.** "Want to see"; notification when a watchlisted production has a date within 7 days.
- **Profile.** Diary (by date), stats (seen this year, favourite theatre, most-seen actor), ratings histogram.
- **Social, minimal.** Follow people; a feed of followed users' reviews; like a review.
- **Accounts.** Email + Apple + Google sign-in. Reviews require an account; browsing does not.
- **Moderation.** Report a review; admin queue; shadow-ban.

**Out of the MVP**

- Ticket purchase, seat selection, payments of any kind.
- Cities other than Sofia.
- Critic reviews, press aggregation, news.
- Theatre or actor official accounts and verification.
- Lists (curated collections), comments on reviews, direct messages.
- Recommendations beyond "popular this week" and "similar genre".

**Later (post-MVP)**

- Varna, Plovdiv, Burgas; then all 32 cities.
- Letterboxd-style lists and a yearly "wrapped".
- Verified theatre accounts that manage their own programme (which also solves data freshness).
- Affiliate ticket links via Entase / Eventim.
- Critic score alongside audience score (the Show-Score hook).
- A Pro tier: stats, no ads, custom themes.

## Data model

&#91;embedded content: data model · 4 catalogue entities, 1 join, 4 user records\]

Production is the hub: a theatre hosts it, people are credited on it with a role, and each night it runs is a Performance. Everything the user does hangs off Production (rate, want) or off a specific Performance (the diary), so stats like "most-seen actor" are one join away. The same schema serves every city; City is a field on Theatre, not a separate tier.

## Technical architecture

&#91;embedded content: MVP architecture · 2 clients, 1 API, 1 database, 2 ingestion paths\]

A deliberately boring stack: one cross-platform app, one API, one Postgres. The only unusual piece is the ingestion layer, which is a folder of small per-theatre adapters rather than one generic crawler, because every theatre site is different and will break independently.

**Stack recommendation**

| Layer | Choice | Why |
| --- | --- | --- |
| Mobile | React Native with Expo (or Flutter if the team already knows Dart) | One codebase, over-the-air updates, push and auth libraries ready |
| Backend | Supabase (Postgres, auth, storage, edge functions) for the MVP; migrate to NestJS on the same Postgres if logic outgrows it | Fastest path to a working API with social login and row-level security |
| Database | Postgres with `pg_trgm` for Cyrillic fuzzy search | Handles joins for stats and feeds; no second search engine needed at MVP scale |
| Images | Supabase Storage or S3 + an image CDN | Posters and headshots resized once, served fast |
| Ingestion | Node or Python scripts on a nightly cron; each adapter emits a normalised JSON of performances | Easy to add a theatre, easy to see which one broke |
| Push | Expo Push (wraps APNs/FCM) | Watchlist reminders without native code |
| Admin | Small Next.js app on the same API | Moderation and data fixes without touching the database by hand |
| Analytics | PostHog or Mixpanel | Measure log-rate, review-rate, D7 retention from day one |

**Non-functional notes**

- All text fields are UTF-8 and Bulgarian-first; store an optional `title_en` for later.
- Every catalogue record carries `source` and `source_url` so provenance is auditable (see Data section).
- One rating per user per production is a database constraint, not application logic.
- GDPR: EU hosting (Supabase eu-central), account deletion endpoint, no third-party ad SDKs in the MVP.

## Risks and open questions

| Risk | Likelihood | Impact | Mitigation |
| --- | --- | --- | --- |
| Cold start: empty reviews make the app look dead | High | High | Seed with 50-100 reviews from a founding group of theatre-goers before public launch; show "seen by N people" even with no reviews |
| Data goes stale when a theatre changes its programme | High | Medium | Per-theatre adapters re-run nightly; users can flag a wrong date; verified theatre accounts post-MVP |
| Legal challenge from Art.bg Net over data | Low if sourcing is clean | High | No scraping of theatre.art.bg; keep a provenance log per record; talk to them early |
| Small market caps growth | Medium | Medium | \~2.3M visits/year is enough for a sustainable niche app; Sofia alone has 60+ stages on theatre.art.bg |
| Review abuse (theatres or actors rating themselves) | Medium | Medium | Account required, one rating per user per production, rate-limit new accounts, report queue |
| Rights to posters and photos | Medium | Medium | Ask theatres for press kits; fall back to a generated placeholder; never hotlink theatre.art.bg images |
| Two platforms doubles QA | Medium | Low | Cross-platform framework; ship iOS first if the team is one person |

**Open questions to settle before building**

- [ ] Which 10-15 Sofia theatres are in the launch set, and has each been contacted for a programme feed?
- [ ] Bulgarian only, or Bulgarian + English from day one?
- [ ] Is the rating scale 5 stars with halves (Letterboxd) or 0-100 (Show-Score)?
- [ ] Does the app need a web version for SEO and sharing, or is a mobile-only MVP acceptable?
- [ ] Is there a budget for a designer, or does the MVP use a component library as-is?

## Suggested roadmap

&#91;embedded content: roadmap · 5 phases, 4 gates, Sofia launch around week 18\]

The first gate is the one that matters: if fewer than eight Sofia theatres will share their programme, the data strategy falls back to community entry and the timeline stretches. Build and Catalogue overlap on purpose, since the app's screens can be developed against a handful of seeded productions while the adapters catch up.

## Sources

- [theatre.art.bg: Колко е важно да бъдеш сериозен](https://www.theatre.art.bg/колко-е-важно-да-бъдеш-сериозен_13_6_20) — production page used to map the data model
- [theatre.art.bg: Театър "Българска армия"](https://www.theatre.art.bg/театър-българска-армия___6___20) — theatre page and monthly programme
- [theatre.art.bg: Sofia programme, 15.10.2026](https://www.theatre.art.bg/театри-софия-програма______2026-10-15______20) — day listing
- [theatre.art.bg: Условия за ползване](https://www.theatre.art.bg/pages.php?pageId=11) — terms of use, IP clause
- [theatre.art.bg: robots.txt](https://www.theatre.art.bg/robots.txt)
- [NSI: Culture 2024](https://www.nsi.bg/en/press-release/kultura-biblioteki-kina-filmovo-proizvodstvo-radio-i-televizionna-programa-scenichni-izkustva-i-muzei-7897), [Culture 2023](https://www.nsi.bg/en/press-release/main-data-on-culture-in-the-republic-of-bulgaria-for-2023-7300), [Culture 2022](https://www.nsi.bg/en/press-release/main-data-on-culture-in-the-republic-of-bulgaria-for-2022-6963), [Culture 2021 (PDF)](https://www.nsi.bg/en/file/23754/Culture2021_en_SNXTI8Y.pdf), [Culture 2024 detail (PDF)](https://www.nsi.bg/en/file/28706/Culture2023_en_J1AV5E5.pdf)
- [Semrush: top events sites in Bulgaria, May 2026](https://www.semrush.com/trending-websites/bg/events-services)
- [Capital: Revolut and Eventim partnership, March 2026](https://www.capital.bg/biznes/kompanii/2026/03/18/4893825_revolut_i_eventim_obiaviha_partnyorstvo_za_burzo/)
- [Entase for theatre and opera](https://www.entase.com/event-organizers/theater-and-opera?lc=en)
- [ePayGO](https://epaygo.bg/epaygo?lang=en)
- [Stage and Cinema: best theatre apps 2025](https://stageandcinema.com/2025/08/23/best-apps-sites-theatre/)
- [Show-Score: about](https://showscore.substack.com/about)
- [Stagedoor on Seedrs](https://www.seedrs.com/stagedoor)
- [LMDA: Mezzanine Theatre Diary review](https://lmda.org/?p=2560)
- [Teatrum on the App Store](https://apps.apple.com/ru/app/id1613484127)
- [BroadwayWorld forum: Goodreads for theatre](https://forum.broadwayworld.com/thread/Goodreads-equivalent-for-theater--1080558)
