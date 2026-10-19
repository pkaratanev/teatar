# How we test a feature

A new feature is done when Preslav (or Claude while he watches) has walked through it, and its MVP goal in
[STATUS.md](STATUS.md) is ticked with a note saying how it was checked. Automated tests in CI come first;
this is the hands-on pass on top.

## 1. Start the local stack (Preslav's Windows PC)

```
irm https://raw.githubusercontent.com/pkaratanev/teatar/main/scripts/windows.ps1 | iex
```
Pulls, migrates, starts the API (own window, `php artisan serve --host=0.0.0.0 --port=8000`) and Expo.
Fresh programmes and posters: `php artisan teatar:demo` in `C:\Users\Atlas\Projects\teatar\backend`.
Jobs queued from the admin (sync buttons) run only with `php artisan queue:work` in another window.
In PowerShell use `npm.cmd` / `npx.cmd` (Windows blocks `npm.ps1`).

## 2. Phone checks (Preslav)

Things only the phone can show: Expo Go start-up, SecureStore (staying signed in), touch feel, native
pickers, posters from the LAN address. Claude gives **one check at a time** (what to tap, what to expect),
Preslav answers, Claude ticks the goal or logs a finding under "Test findings" and commits right away.

## 3. Browser checks (Claude, Preslav watching)

For everything else Claude drives the web build in the Claude desktop app's browser pane, linked to the PC:

1. Preslav runs, after pulling app changes: `cd C:\Users\Atlas\Projects\teatar\mobile` then `npm.cmd run web:local`.
   This builds the web app into `backend/public/app-build`, served by the API at **http://localhost:8000/app**,
   the same address as the API (the pane blocks requests to another port or to the LAN address).
2. Claude opens http://localhost:8000/app in the pane (approve `localhost:8000` once), sets the mobile viewport,
   and clicks through the feature, reading the page text after each step. Navigate inside the app: opening a
   deep URL directly gives a 404 on the PC (Test findings).
3. Admin side in the same pane at http://localhost:8000/admin (`admin@teatar.test` / `password`).
4. Use throwaway test accounts (`test_<something>@demo.teatar.test`) and delete them at the end with the
   app's own "Изтрий профила", which also tests deletion. Put changed settings back (e.g. the sync time).
5. API checks from the page with `fetch('/api/v1/…')` (same origin) to confirm what the screen shows.

Not covered by the browser: push (needs a development build), SecureStore, Expo Go crashes. Those stay on
the phone list.

## 4. Record it

Tick the goal in STATUS.md with "checked <date> by Preslav on the phone" or "by Claude in the web build",
add findings under "Test findings", and a Log line. Commit after each tick so nothing is lost.

## What we tried and dropped

- Testing the Expo dev server (http://localhost:8081) in the pane: its API calls to port 8000 are blocked.
- A Playwright walk-through on GitHub Actions: works and is free (public repo), but Preslav wants to watch
  the testing, so it was removed.
