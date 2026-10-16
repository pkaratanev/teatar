// Hand-written to match backend/app/Http/Resources. Once the API is running,
// `npm run api:types` generates src/api/schema.d.ts from Scramble's OpenAPI
// output and these can be replaced by the generated types.

export type ProductionStatus = 'running' | 'closed';

export type CreditRole =
  | 'actor'
  | 'director'
  | 'playwright'
  | 'translator'
  | 'set_designer'
  | 'costume_designer'
  | 'composer'
  | 'choreographer'
  | 'lighting_designer'
  | 'other';

export interface ProductionSummary {
  id: number;
  slug: string;
  title: string;
  author: string | null;
  /** Guest performance: a visiting company plays at this theatre (company name may be unknown). */
  is_guest: boolean;
  visiting_company: string | null;
  status: ProductionStatus;
  poster_url: string | null;
  rating_average: number | null;
  ratings_count: number;
  theatre?: { id: number; name: string };
  /** Only on the watchlist: next upcoming performance. */
  next_performance_at?: string | null;
  /** Only on "my ratings". */
  my_half_stars?: number | null;
}

export interface Performance {
  id: number;
  starts_at: string; // ISO 8601, UTC
  stage: string | null;
  ticket_url: string | null;
  price_from: number | null;
  price_to: number | null;
  production?: ProductionSummary;
}

export interface Credit {
  person: { id: number; slug: string; name: string };
  role_type: CreditRole;
  role_label: string;
  character_name: string | null;
}

export interface Production extends ProductionSummary {
  synopsis: string | null;
  age_advisory: string | null;
  running_time_minutes: number | null;
  premiered_on: string | null;
  box_office_url: string | null;
  credits: Credit[];
  upcoming_performances: Performance[];
}

export interface PersonCredit {
  production: ProductionSummary;
  role_type: CreditRole;
  role_label: string;
  character_name: string | null;
}

export interface Person {
  id: number;
  slug: string;
  name: string;
  bio: string | null;
  born_year: number | null;
  headshot_url: string | null;
  current: PersonCredit[];
  past: PersonCredit[];
}

export interface Theatre {
  id: number;
  slug: string;
  name: string;
  city: string;
  address: string | null;
  lat: number | null;
  lng: number | null;
  website: string | null;
  box_office_url: string | null;
  logo_url: string | null;
  productions?: ProductionSummary[];
}

export interface SearchResults {
  productions: ProductionSummary[];
  people: { id: number; slug: string; name: string }[];
  theatres: { id: number; slug: string; name: string }[];
}

export interface Paginated<T> {
  data: T[];
  meta: { current_page: number; last_page: number; total: number };
}

export type ProgrammeRange = 'tonight' | 'week' | 'month';

// --- Your theatre life -------------------------------------------------------

export interface User {
  id: number;
  name: string;
  username: string;
  bio: string | null;
  email?: string;
}

export interface Review {
  id: number;
  body: string;
  has_spoilers: boolean;
  likes_count: number;
  created_at: string;
  updated_at: string;
  /** The author's rating of the same production (1–10 half stars), when known. */
  half_stars?: number | null;
  user?: { username: string; name: string };
  production?: ProductionSummary;
  is_mine: boolean;
}

/** The signed-in user's own state for one production. */
export interface MyProductionState {
  half_stars: number | null;
  rating: number | null;
  review: Review | null;
  seen_on: string[];
  on_watchlist: boolean;
}

export interface DiaryEntry {
  id: number;
  seen_on: string; // YYYY-MM-DD
  performance_id: number | null;
  half_stars: number | null;
  production: ProductionSummary;
}

export interface ProfileStats {
  seen_total: number;
  seen_this_year: number;
  ratings_count: number;
  /** Ten counts: [0] = ½★ … [9] = ★★★★★ */
  ratings_histogram: number[];
  favourite_theatre: { id: number; name: string; visits: number } | null;
  most_seen_actor: { id: number; name: string; times: number } | null;
}

export interface Profile {
  username: string;
  name: string;
  bio: string | null;
  joined_at: string | null;
  stats: ProfileStats;
  recent_diary: DiaryEntry[];
  recent_reviews: Review[];
}

export interface LogVisitInput {
  seen_on?: string;
  performance_id?: number | null;
  half_stars?: number | null;
  review?: string | null;
  has_spoilers?: boolean;
}

export type ReportReason = 'spam' | 'abuse' | 'spoiler' | 'other';
