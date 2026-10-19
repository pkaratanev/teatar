# Prompt: filling missing production data in the admin

For an agent with a browser (Claude in Chrome or the desktop app's browser pane), working in the Teatar admin.
Copy everything under the line. Change the batch size and the admin address if needed.

---

You're filling in missing team and cast for theatre productions in the Teatar admin at
https://teatar-production-8awteq.laravel.cloud/admin (I'm signed in). Work through a batch of **20 productions**,
then stop and report.

## Where to work

- **The list:** Каталог → **За преглед**. Keep the default filter (performances soon), soonest first. The table shows
  what's missing for each production.
- **Skip** a production that has no actors yet if its theatre is one of these 10: **Народен театър, Театър 199,
  Сатиричен театър, Театър „София“, Сълза и смях, Зад канала, Възраждане, Българска армия, Théatro, Драматичен
  театър Варна**. The nightly sync reads their cast from the theatre's own page automatically.
- **What's left for you:** productions from those 10 that still have no actors after a sync (their page had none,
  or a different layout), and every production from the other theatres (Младежки, Куклен театър Варна, guest
  shows). Check that a sync has run first: Синхронизации → the theatre's last run, column "Екип (нови)".
- **Wait about 4 seconds** after a production page opens before clicking „Внеси екип“, or the first click may not
  open the dialog.

## Where the data comes from

1. Open the production. The field **„Източник (страницата на театъра)“** at the end of the first section is the
   theatre's own page for it; the arrow button next to it opens it in a new tab.
2. Use **only the theatre's own website** (the source link, or the production's page found from the theatre's
   programme). **Never use theatre.art.bg**, not even to check something. Don't use ticket sites, news articles or
   Wikipedia for credits.
3. If the theatre's page has no credits, leave the production as it is and note it in the report. Don't guess.

## How to enter credits: use "Внеси екип", never the repeater

On the production's edit page, click **„Внеси екип“** (top right). Paste the credits as text, one label per line,
names separated by commas, then click **Добави**. It saves straight away and reloads the page.

```
Автор: Иван Вирипаев
Превод: Нева Мичева
Режисьор: Нено Койнарски
Сценография: Ясмин Манделли
Костюми: Ясмин Манделли
Музика: Петър Дундаков
Хореография: Ангелина Гаврилова-Тодорова
Светлинен дизайн: Иван Иванов
Актьори: Антъни Пенев, Боян Петров (Хамлет), Ванеса Пеянкова
```

Rules for the text:

- **Labels it knows:** Автор, Превод, Режисьор (or Постановка, Режисура), Сценография, Костюми,
  „Сценография и костюми“ / Художник (gives both), Музика / Композитор, Хореография, Светлинен дизайн, Драматург,
  Актьори / Участват / В ролите. Anything else (Фотограф, Продуцент, Видео …) is skipped and listed in the message.
  That's fine; don't try to force it in.
- **Names** as the theatre writes them, full name, no titles (проф., доц.).
- **A double surname** goes **without spaces** around the dash: `Донка Аврамова-Бочева`. With spaces, the part after
  the dash is read as a character.
- **A character**, if the page gives one, goes in brackets after the actor: `Боян Петров (Хамлет)`.
- **Alternating casts** ("Иван / Петър"): list both actors.
- It's safe to paste again. Existing people are reused by name and nothing is duplicated or deleted, so if a run
  timed out, just paste the same text again.
- If the production has no **Автор** text, the playwright you paste fills it.
- **Two people with the same name** (e.g. a director and an actor both called Стоян Радев): look them up in
  Каталог → Хора, then write `Стоян Радев #12` to attach person #12, or `Стоян Радев #нов` to create a separate
  person. Use this only when you're sure they're different people; otherwise list them in the report.

**Never** create people through the „Добави човек“ repeater or the „Хора“ form, and never run scripts in the page.
That's what created duplicates before.

## Other fields (in the form, then „Запази“)

- **Възраст** (e.g. `12+`), **Продължителност (мин.)**, **Премиера** date, only if the theatre's page states them.
- **Анотация:** leave empty. Descriptions are skipped for now.
- **Плакат:** leave empty. Posters need each theatre's permission.

## When a production is done

Click **„Прегледано“** at the top of its edit page. Do this even if some fields stay empty because the theatre doesn't
publish them. Note which ones in the report.

## Duplicates

If you see the same person twice in **Каталог → Хора** (same name, or the same name with/without a middle name),
**don't merge**. List them in the report with their # numbers. I decide and use „Слей в…“ myself.

## Report at the end of the batch

A table: production · theatre · what you added · what's still missing and why (not on the theatre's page / page
not found). Then three short lists:

- possible duplicate people (names and #);
- productions whose dates in the admin don't match the theatre's site;
- anything that didn't work as described here.

Then stop and wait for me before the next batch.
