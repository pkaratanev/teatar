import { useQuery } from '@tanstack/react-query';

import { api } from './client';
import type {
  Paginated,
  Performance,
  Person,
  Production,
  ProgrammeRange,
  SearchResults,
  Theatre,
} from './types';

export function useProgramme(range: ProgrammeRange, theatreId?: number) {
  const params = new URLSearchParams({ range });
  if (theatreId) params.set('theatre_id', String(theatreId));

  return useQuery({
    queryKey: ['programme', range, theatreId ?? null],
    queryFn: () => api<Paginated<Performance>>(`/performances?${params}`),
  });
}

export function useProduction(id: number) {
  return useQuery({
    queryKey: ['production', id],
    queryFn: () => api<{ data: Production }>(`/productions/${id}`).then((r) => r.data),
  });
}

export function usePerson(id: number) {
  return useQuery({
    queryKey: ['person', id],
    queryFn: () => api<{ data: Person }>(`/people/${id}`).then((r) => r.data),
  });
}

export function useTheatre(id: number) {
  return useQuery({
    queryKey: ['theatre', id],
    queryFn: () => api<{ data: Theatre }>(`/theatres/${id}`).then((r) => r.data),
  });
}

export function useSearch(q: string) {
  const term = q.trim();
  return useQuery({
    queryKey: ['search', term],
    queryFn: () => api<SearchResults>(`/search?q=${encodeURIComponent(term)}`),
    enabled: term.length >= 2,
    staleTime: 60_000,
  });
}
