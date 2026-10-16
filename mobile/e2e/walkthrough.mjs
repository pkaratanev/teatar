// Walks through the MVP goals in the web build like a tester would: signs up, logs a visit, rates,
// reviews, follows, likes, reports, moderates in the admin, deletes the account. Each check is
// recorded as passed or failed with a screenshot; the report goes to `walkthrough.md`.
// Run by .github/workflows/screens.yml against a local API with demo data (after screenshots.mjs).
// The web build is not the phone: things like SecureStore, push and Expo Go are not covered here.
import fs from 'node:fs';
import { chromium } from 'playwright';

const API = process.env.API ?? 'http://localhost:8000/api/v1';
const APP = process.env.APP ?? 'http://localhost:8081';
const ADMIN = process.env.ADMIN ?? 'http://localhost:8000/admin';
const OUT = process.env.OUT ?? 'shots/walkthrough';
fs.mkdirSync(OUT, { recursive: true });

async function api(path, init = {}) {
  const res = await fetch(API + path, {
    ...init,
    headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...(init.headers ?? {}) },
  });
  if (!res.ok) throw new Error(`${path}: ${res.status} ${await res.text()}`);
  return res.status === 204 ? null : res.json();
}

// A production that is playing and has reviews by demo users, one of them with spoilers if possible.
const perfs = (await api('/performances?range=month')).data;
const candidates = [...new Map(perfs.filter((p) => p.production).map((p) => [p.production.id, p.production])).values()]
  .sort((a, b) => (b.ratings_count ?? 0) - (a.ratings_count ?? 0))
  .slice(0, 15);
let production = null;
let reviews = [];
let spoilerProduction = null;
for (const c of candidates) {
  const r = (await api(`/productions/${c.id}/reviews`)).data;
  if (!production && r.length >= 2) [production, reviews] = [c, r];
  if (!spoilerProduction && r.some((x) => x.has_spoilers)) spoilerProduction = c;
}
if (!production) throw new Error('No production with at least two reviews in the demo data');
const other = reviews[0].user;
const detail = (await api(`/productions/${production.id}`)).data;
const person = detail.credits?.[0]?.person;

const stamp = Date.now().toString(36);
const tester = { name: 'Тест Тестов', username: `test_${stamp}`, email: `test.${stamp}@demo.teatar.test`, password: 'password123' };

const browser = await chromium.launch();
const ctx = await browser.newContext({
  viewport: { width: 390, height: 844 },
  deviceScaleFactor: 2,
  isMobile: true,
  hasTouch: true,
  locale: 'bg-BG',
  timezoneId: 'Europe/Sofia',
  colorScheme: 'dark',
});
const page = await ctx.newPage();
const errors = [];
page.on('pageerror', (e) => errors.push(e.message));

const results = [];
let n = 0;
async function check(goal, name, fn) {
  n++;
  const file = `${String(n).padStart(2, '0')}-${name.toLowerCase().replace(/[^a-z0-9а-я]+/giu, '-').slice(0, 40)}.png`;
  try {
    await fn();
    results.push({ goal, name, ok: true, file });
  } catch (e) {
    results.push({ goal, name, ok: false, error: e.message.split('\n')[0], file });
  }
  await page.screenshot({ path: `${OUT}/${file}` }).catch(() => {});
}
const go = (path) => page.goto(APP + path, { waitUntil: 'networkidle', timeout: 30_000 });
const see = (text, opts = {}) => page.getByText(text, opts).first().waitFor({ timeout: 10_000 });

// --- Your theatre life ---

await check('Sign up / sign in / delete', 'Sign up with email', async () => {
  await go('/sign-in');
  await page.getByText('Регистрация', { exact: true }).click();
  await page.getByLabel('Име', { exact: true }).fill(tester.name);
  await page.getByLabel('Потребителско име').fill(tester.username);
  await page.getByLabel('Имейл').fill(tester.email);
  await page.getByLabel('Парола').fill(tester.password);
  await page.getByText('Създай профил').click();
  await page.waitForLoadState('networkidle');
  await go('/profile');
  await see(tester.name);
});

await check('Sign up / sign in / delete', 'Still signed in after a reload', async () => {
  await page.reload({ waitUntil: 'networkidle' });
  await see(tester.name);
});

await check('Follow / feed / likes', 'Like a review', async () => {
  await go(`/production/${production.id}`);
  await page.getByLabel('Харесай').first().scrollIntoViewIfNeeded();
  await page.getByLabel('Харесай').first().click();
  await page.getByLabel('Махни харесването').first().waitFor({ timeout: 10_000 });
});

await check('Report / moderation', 'Report a review from the app', async () => {
  await page.getByLabel('Още').first().click();
  await see('Докладвай ревюто');
  await page.getByText('Спам', { exact: true }).click();
  await see('Благодарим! Ще прегледаме ревюто.');
});

await check('Rate / review / seen / watchlist', 'Log a visit with 4★, a review and spoilers', async () => {
  await go(`/production/${production.id}`);
  await page.getByText('Видях', { exact: true }).click();
  await see('Кога го гледахте?');
  await page.getByLabel('4 звезди', { exact: true }).click();
  await page.getByPlaceholder('Ревю (по желание)').fill(`Тестово ревю ${stamp}: силен финал.`);
  await page.getByRole('switch').click();
  await page.getByText('Запази', { exact: true }).click();
  await see('Гледах пак');
  await see('Вие:');
});

await check('Rate / review / seen / watchlist', 'My review shows with a spoiler cover', async () => {
  await go(`/production/${production.id}`);
  await see('Съдържа спойлери. Докоснете, за да прочетете.');
});

await check('Rate / review / seen / watchlist', 'Add to watchlist', async () => {
  await page.getByText('Искам да гледам', { exact: true }).click();
  await see('В списъка');
});

await check('Profile: diary / stats / histogram', 'Diary lists the visit', async () => {
  await go('/diary');
  await see(production.title);
});

await check('Profile: diary / stats / histogram', 'Profile shows stats, histogram and watchlist', async () => {
  await go('/profile');
  await see('тази година');
  await page.getByLabel('Разпределение на оценките').waitFor({ timeout: 10_000 });
  await see('Искам да гледам');
  await see(production.title);
});

await check('Follow / feed / likes', `Follow a user (${other.username})`, async () => {
  await go(`/user/${other.username}`);
  await page.getByText('Следвай', { exact: true }).click();
  await see('Следвате');
});

await check('Follow / feed / likes', 'Feed shows the followed user', async () => {
  await go('/feed');
  await see(other.name);
});

if (person) {
  await check('Follow / feed / likes', `Follow a person (${person.name})`, async () => {
    await go(`/person/${person.id}`);
    await page.getByText('Следвай', { exact: true }).click();
    await see('Следвате');
  });
}

await check('Sign up / sign in / delete', 'Sign out and sign in again', async () => {
  await go('/profile');
  await page.getByText('Изход', { exact: true }).click();
  await page.waitForLoadState('networkidle');
  await go('/sign-in');
  await page.getByLabel('Имейл').fill(tester.email);
  await page.getByLabel('Парола').fill(tester.password);
  await page.getByRole('button', { name: 'Вход' }).last().click();
  await page.waitForLoadState('networkidle');
  await go('/profile');
  await see(tester.name);
});

// --- Admin: moderation and sync ---

const admin = await (await browser.newContext({ viewport: { width: 1280, height: 900 } })).newPage();
async function adminCheck(goal, name, fn) {
  n++;
  const file = `${String(n).padStart(2, '0')}-admin-${name.toLowerCase().replace(/[^a-z0-9]+/g, '-').slice(0, 40)}.png`;
  try {
    await fn();
    results.push({ goal, name, ok: true, file });
  } catch (e) {
    results.push({ goal, name, ok: false, error: e.message.split('\n')[0], file });
  }
  await admin.screenshot({ path: `${OUT}/${file}`, fullPage: true }).catch(() => {});
}

await adminCheck('Report / moderation', 'Admin sign-in', async () => {
  await admin.goto(`${ADMIN}/login`, { waitUntil: 'networkidle' });
  await admin.locator('input[type=email]').fill('admin@teatar.test');
  await admin.locator('input[type=password]').fill('password');
  await admin.locator('button[type=submit]').click();
  await admin.waitForURL(/\/admin\/?$/, { timeout: 15_000 });
});

await adminCheck('Report / moderation', 'Reported review is in the moderation list and can be hidden', async () => {
  await admin.goto(`${ADMIN}/reviews`, { waitUntil: 'networkidle' });
  const row = admin.locator('tr').filter({ hasText: reviews[0].body.slice(0, 30) }).first();
  await row.waitFor({ timeout: 10_000 });
  await row.getByText('Скрий').click();
  await admin.getByRole('button', { name: /Confirm|Потвърди/ }).last().click();
  await admin.waitForLoadState('networkidle');
  const after = (await api(`/productions/${production.id}/reviews`)).data;
  if (after.some((r) => r.id === reviews[0].id)) throw new Error('The hidden review is still listed in the app');
});

await adminCheck('Report / moderation', 'Shadow ban action is there for users', async () => {
  await admin.goto(`${ADMIN}/users`, { waitUntil: 'networkidle' });
  await admin.getByText('Shadow ban').first().waitFor({ timeout: 10_000 });
});

await adminCheck('Sync management', 'Sync log shows next run, "Обнови всички" and "График"', async () => {
  await admin.goto(`${ADMIN}/adapter-runs`, { waitUntil: 'networkidle' });
  await admin.getByText('Нощна синхронизация всеки ден').first().waitFor({ timeout: 10_000 });
  await admin.getByText('Обнови всички').first().waitFor();
  await admin.getByText('График').first().waitFor();
});

await adminCheck('Sync management', 'Change the nightly time to 04:30 and back', async () => {
  for (const time of ['04:30', '03:15']) {
    await admin.getByText('График').first().click();
    const input = admin.getByLabel(/Час/);
    await input.fill(time);
    await admin.getByRole('button', { name: /Submit|Запази|Изпрати/ }).last().click();
    await admin.waitForLoadState('networkidle');
    await admin.getByText(`всеки ден в ${time}`).first().waitFor({ timeout: 10_000 });
  }
});

await adminCheck('Sync management', 'Dashboard loads with the sync table', async () => {
  await admin.goto(ADMIN, { waitUntil: 'networkidle' });
  await admin.getByText('Синхронизация по театри').first().waitFor({ timeout: 10_000 });
});

// --- Back in the app ---

if (spoilerProduction) {
  await check('Rate / review / seen / watchlist', 'Spoiler reviews are covered for other readers', async () => {
    await go(`/production/${spoilerProduction.id}`);
    await see('Съдържа спойлери. Докоснете, за да прочетете.');
  });
}

await check('Sign up / sign in / delete', 'Delete the account', async () => {
  await go('/profile');
  await page.getByText('Изтрий профила').click();
  await page.getByText('Изтрий завинаги').click();
  await page.waitForLoadState('networkidle');
  const res = await fetch(`${API}/auth/login`, {
    method: 'POST',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({ email: tester.email, password: tester.password, device_name: 'check' }),
  });
  if (res.ok) throw new Error('The deleted account can still sign in');
});

await browser.close();

const passed = results.filter((r) => r.ok).length;
const lines = [
  `# Walk-through (web build)`,
  '',
  `${passed} of ${results.length} checks passed. Production used: ${production.title} (#${production.id}).`,
  '',
  '| | Goal | Check | Screenshot |',
  '| --- | --- | --- | --- |',
  ...results.map((r) => `| ${r.ok ? '✅' : '❌'} | ${r.goal} | ${r.name}${r.ok ? '' : `<br>${r.error.replace(/\|/g, '\\|')}`} | [${r.file}](${r.file}) |`),
  '',
  errors.length ? `Page errors:\n\n${[...new Set(errors)].map((e) => `- ${e}`).join('\n')}` : 'No page errors.',
  '',
];
fs.writeFileSync(`${OUT}/walkthrough.md`, lines.join('\n'));
console.log(lines.join('\n'));
