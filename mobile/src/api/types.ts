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
