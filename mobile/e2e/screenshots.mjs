// Opens the web build of the app in a phone-sized Chromium and saves a screenshot of each screen.
// Run by .github/workflows/screens.yml against a local API with demo data; the images are pushed
// to the `screenshots` branch so they can be reviewed without downloading CI artifacts.
import fs from 'node:fs';
import { chromium } from 'playwright';

const API = process.env.API ?? 'http://localhost:8000/api/v1';
const APP = process.env.APP ?? 'http://localhost:8081';
const OUT = process.env.OUT ?? 'shots';
fs.mkdirSync(OUT, { recursive: true });

async function api(path, init = {}) {
  const res = await fetch(API + path, {
    ...init,
    headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...(init.headers ?? {}) },
  });
  if (!res.ok) throw new Error(`${path}: ${res.status} ${await res.text()}`);
  return res.json();
}

const login = await api('/auth/login', {
  method: 'POST',
  body: JSON.stringify({ email: 'maria.p@demo.teatar.test', password: 'password', device_name: 'screenshots' }),
});
const token = login.token;
const me = login.user.data ?? login.user;
const auth = { headers: { Authorization: `Bearer ${token}` } };

// The most-rated production on the programme makes the richest page.
const perfs = (await api('/performances?range=month')).data;
const productions = [...new Map(perfs.filter((p) => p.production).map((p) => [p.production.id, p.production])).values()];
productions.sort((a, b) => (b.ratings_count ?? 0) - (a.ratings_count ?? 0));
const production = (await api(`/productions/${productions[0].id}`, auth)).data;
const person = production.credits.find((c) => c.role_type === 'actor')?.person ?? production.credits[0]?.person;
const reviews = (await api(`/productions/${production.id}/reviews`, auth)).data;
const other = reviews.find((r) => r.user && r.user.username !== me.username)?.user;

const browser = await chromium.launch();
const log = [];

async function context(signedIn) {
  const ctx = await browser.newContext({
    viewport: { width: 390, height: 844 },
    deviceScaleFactor: 2,
    isMobile: true,
    hasTouch: true,
    locale: 'bg-BG',
    timezoneId: 'Europe/Sofia',
    colorScheme: 'dark',
  });
  if (signedIn) await ctx.addInitScript((t) => localStorage.setItem('teatar.token', t), token);
  return ctx;
}

async function shot(page, name, path, { scrolls = 0, before } = {}) {
  page.removeAllListeners('console');
  page.on('console', (m) => ['error', 'warning'].includes(m.type()) && log.push(`[${name}] ${m.type()}: ${m.text()}`));
  page.removeAllListeners('pageerror');
  page.on('pageerror', (e) => log.push(`[${name}] pageerror: ${e.message}`));
  try {
    if (path) await page.goto(APP + path, { waitUntil: 'networkidle', timeout: 30_000 });
    if (before) await before(page);
    await page.waitForTimeout(1500);
    await page.screenshot({ path: `${OUT}/${name}.png` });
    for (let i = 1; i <= scrolls; i++) {
      await page.mouse.move(195, 500);
      await page.mouse.wheel(0, 700);
      await page.waitForTimeout(800);
      await page.screenshot({ path: `${OUT}/${name}-${i + 1}.png` });
    }
  } catch (e) {
    log.push(`[${name}] FAILED: ${e.message}`);
  }
}

const signedIn = await (await context(true)).newPage();
await shot(signedIn, '01-programme', '/', { scrolls: 1 });
await shot(signedIn, '02-production', `/production/${production.id}`, { scrolls: 3 });
await shot(signedIn, '03-log-sheet', `/production/${production.id}`, {
  before: (p) => p.getByText(/^(Видях|Гледах пак)$/).first().click(),
});
if (production.theatre) await shot(signedIn, '04-theatre', `/theatre/${production.theatre.id}`, { scrolls: 1 });
if (person) await shot(signedIn, '05-person', `/person/${person.id}`);
await shot(signedIn, '06-search', '/search', {
  before: async (p) => {
    await p.getByPlaceholder('Постановка, актьор или театър').fill(production.title.split(/\s+/)[0]);
    await p.waitForLoadState('networkidle');
  },
});
await shot(signedIn, '07-feed', '/feed', { scrolls: 1 });
await shot(signedIn, '08-diary', '/diary');
await shot(signedIn, '09-profile', '/profile', { scrolls: 2 });
if (other) await shot(signedIn, '10-user', `/user/${other.username}`);
await shot(signedIn, '11-edit-profile', '/edit-profile');

const signedOut = await (await context(false)).newPage();
await shot(signedOut, '12-sign-in', '/sign-in');
await shot(signedOut, '13-feed-signed-out', '/feed');

await browser.close();
fs.writeFileSync(`${OUT}/console.txt`, log.join('\n') + '\n');
console.log(log.join('\n'));
