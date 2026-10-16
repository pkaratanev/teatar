const TZ = 'Europe/Sofia';

/** "чт, 15 окт." */
export function formatDay(iso: string): string {
  return new Intl.DateTimeFormat('bg-BG', { weekday: 'short', day: 'numeric', month: 'short', timeZone: TZ }).format(
    new Date(iso),
  );
}

/** Programme day heading: "Днес · петък, 16 октомври", "Утре · …", or just "събота, 24 октомври". */
export function formatDayHeading(iso: string): string {
  const long = new Intl.DateTimeFormat('bg-BG', { weekday: 'long', day: 'numeric', month: 'long', timeZone: TZ })
    .format(new Date(iso))
    .replace(/\s*г\.$/, '');
  const key = dayKey(iso);
  if (key === dayKey(new Date().toISOString())) return `Днес · ${long}`;
  if (key === dayKey(new Date(Date.now() + 86_400_000).toISOString())) return `Утре · ${long}`;
  return long;
}

/** "19:00" */
export function formatTime(iso: string): string {
  return new Intl.DateTimeFormat('bg-BG', { hour: '2-digit', minute: '2-digit', timeZone: TZ }).format(new Date(iso));
}

/** Stable key for grouping performances by local calendar day. */
export function dayKey(iso: string): string {
  return new Intl.DateTimeFormat('en-CA', { timeZone: TZ }).format(new Date(iso)); // YYYY-MM-DD
}

/** 3.5 → "★★★½" */
export function formatStars(stars: number | null): string {
  if (stars === null) return '';
  const rounded = Math.round(stars * 2) / 2;
  return '★'.repeat(Math.floor(rounded)) + (rounded % 1 ? '½' : '');
}

/** "гостува ДТ-Пловдив", or "гостуващ спектакъл" when the company isn't known. */
export function guestLabel(p: { is_guest: boolean; visiting_company: string | null }): string | null {
  if (!p.is_guest) return null;
  return p.visiting_company ? `гостува ${p.visiting_company}` : 'гостуващ спектакъл';
}

export function formatPrice(from: number | null, to: number | null): string | null {
  if (from === null) return null;
  return to !== null && to !== from ? `${from}–${to} €` : `${from} €`;
}
