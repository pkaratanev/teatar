# Deploy

The backend on Laravel Cloud and the app on a closed Google Play test. Decided by Preslav on 2026-10-17:
deploy the backend early, keep the app in closed testing, and go public only once the MVP goals are ticked and
the theatres have agreed to their posters being used.

## Keeping the production database

The database must never be reset. These rules keep it that way:

- The deploy command is **only** `php artisan migrate --force`. Never `migrate:fresh`, `migrate:refresh`,
  `migrate:reset`, `db:wipe`, or `teatar:demo` (that one refuses to run in production anyway).
- Migrations are only ever **added**. A migration that has already run in production is never edited. A fix
  goes in a new migration.
- A migration that drops or renames a column moves the data first, in the same migration.
- CI runs every migration on Postgres, the database production uses, so a migration that only works on SQLite
  fails in CI first.
- Turn on backups for the database before the first real user signs up.

## Branches: `main` and `prod`

Work lands on `main` as before. Laravel Cloud builds only from `prod`, so nothing reaches production by accident.
To deploy, once CI is green on `main`:

```
git push origin main:prod
```

This moves `prod` forward to the same commit as `main`. Claude does this only when Preslav asks for a deploy.

## Laravel Cloud

1. **Application:** new application from `pkaratanev/teatar`, EU region, branch **`prod`**, root folder `backend`,
   auto-deploy on push.
2. **Database:** Postgres in the same region, attached to the environment. It fills the `DB_*` variables.
3. **Bucket:** Laravel Object Storage, visibility **public**, attached with disk name `public` and "default disk"
   on. Posters go to the `public` disk, so they land in the bucket with no extra setting. The server's own disk is
   wiped on every deploy. If you name the disk anything else, set `MEDIA_DISK` to that name.
4. **Environment variables** (the rest of `backend/.env.example`):

   | Variable | Value |
   |---|---|
   | `APP_ENV` | `production` |
   | `APP_DEBUG` | `false` |
   | `APP_URL` | the address Laravel Cloud gives you (or your domain later) |
   | `APP_LOCALE` | `bg` |
   | `QUEUE_CONNECTION` | `database` |
   | `TEATAR_CONTACT_EMAIL` | the address shown on /privacy and /delete-account |
   | `MAIL_*` | see "Email" below |

5. **Deploy command:** `php artisan migrate --force`.
6. **Background work:** on the App cluster, turn on the scheduler (nightly sync, hourly stale-run cleanup, watchlist
   reminders) and add a queue worker (`php artisan queue:work`). Without the worker, syncs started from the admin
   stay "queued".
   - **Scale to zero is fine.** Scheduled tasks wake the sleeping app, and the nightly sync takes a few minutes, well
     inside the sleep timeout. Queued jobs alone don't wake it, but they run as soon as the next task or request
     does. Don't schedule anything more often than hourly, or the app and database never get to sleep.
7. **First deploy, once only.** Run these in Laravel Cloud's command runner:
   ```
   php artisan db:seed --force
   php artisan teatar:make-admin you@example.com
   ```
   The first command adds the launch theatres. It is safe to run again, because it skips theatres that already
   exist. The second creates your admin account and prints a generated password once; to set your own, add
   `--password=...`. Then sign in at `APP_URL/admin` and press "Обнови всички" on the dashboard.
8. **Check:**
   - `APP_URL/api/v1/theatres` lists the theatres.
   - `APP_URL/privacy` and `APP_URL/delete-account` open.
   - After the sync, posters load (their address points at the bucket).

## Email

Reset codes for forgotten passwords are sent by email. Any SMTP service works. Resend has a free tier:

| Variable | Value |
|---|---|
| `MAIL_MAILER` | `smtp` |
| `MAIL_HOST` | `smtp.resend.com` |
| `MAIL_PORT` | `465` |
| `MAIL_SCHEME` | `smtps` |
| `MAIL_USERNAME` | `resend` |
| `MAIL_PASSWORD` | the Resend API key |
| `MAIL_FROM_ADDRESS` | an address on a domain verified in Resend |
| `MAIL_FROM_NAME` | `Teatar` |

Until a domain is verified, Resend only delivers to your own address, which is enough for testing.

## Google Play (closed test)

1. **Play Console** account (25 USD once). New personal accounts must run a closed test with at least 12 testers
   for 14 days before a public release.
2. **EAS** (free), in `mobile/`:
   ```
   npm.cmd install -g eas-cli
   eas login
   eas init
   ```
   `eas init` asks you to add `extra.eas.projectId` to `app.json`; commit that.
3. **API address:** on expo.dev → the project → Environment variables, add `EXPO_PUBLIC_API_URL` =
   `APP_URL/api/v1` for the `preview` and `production` environments.
4. **Builds:**
   - `eas build -p android --profile preview` makes an APK you can install on your phone directly. Try it there first.
   - `eas build -p android --profile production` makes the app bundle for Play.
5. **Push notifications** in a real build need Firebase (FCM). Create a free Firebase project for `bg.teatar.app`
   and upload its key with `eas credentials`. Without it, everything else works but no pushes arrive.
6. **Store listing:**
   - Privacy policy: `APP_URL/privacy`.
   - Account deletion URL: `APP_URL/delete-account`.
   - Fill in the Data safety form from what /privacy lists: account data, user content, a device id for pushes;
     nothing sold or shared for ads.
7. **Closed test:** upload the bundle (`eas submit -p android`, or by hand), add the testers' emails, and send them
   the opt-in link.

## Before going public (not now)

- Every MVP goal ticked (STATUS).
- Each theatre whose posters or descriptions we import has given permission; remove any that said no.
- /privacy updated for anything added since (for example location, analytics).
