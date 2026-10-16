import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { api } from './client';
import type {
  DiaryEntry,
  LogVisitInput,
  MyProductionState,
  Paginated,
  Performance,
  Person,
  Production,
  ProductionSummary,
  Profile,
  ProgrammeRange,
  ReportReason,
  Review,
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

/** The production, plus the signed-in user's own state ("me") when there is a session. */
export function useProduction(id: number) {
  return useQuery({
    queryKey: ['production', id],
    queryFn: () =>
      api<{ data: Production; me?: MyProductionState }>(`/productions/${id}`).then((r) => ({
        production: r.data,
        me: r.me ?? null,
      })),
  });
}

export function useReviews(productionId: number) {
  return useQuery({
    queryKey: ['reviews', productionId],
    queryFn: () => api<Paginated<Review>>(`/productions/${productionId}/reviews`),
  });
}

export function useMyDiary(enabled: boolean) {
  return useQuery({
    queryKey: ['me', 'diary'],
    queryFn: () => api<Paginated<DiaryEntry>>('/me/diary'),
    enabled,
  });
}

export function useMyWatchlist(enabled: boolean) {
  return useQuery({
    queryKey: ['me', 'watchlist'],
    queryFn: () => api<Paginated<ProductionSummary>>('/me/watchlist'),
    enabled,
  });
}

export function useProfile(username: string | undefined) {
  return useQuery({
    queryKey: ['profile', username],
    queryFn: () => api<{ data: Profile }>(`/users/${username}`).then((r) => r.data),
    enabled: !!username,
  });
}

// --- Writes: each returns the fresh "me" state for the production -----------

function useProductionMutation<TInput = void>(productionId: number, request: (input: TInput) => Promise<{ data: MyProductionState }>) {
  const client = useQueryClient();

  return useMutation({
    mutationFn: request,
    onSuccess: ({ data }) => {
      client.setQueryData(['production', productionId], (old: { production: Production; me: MyProductionState | null } | undefined) =>
        old ? { ...old, me: data } : old,
      );
      // Averages, feeds and lists that include this production are now stale.
      client.invalidateQueries({ queryKey: ['production', productionId] });
      client.invalidateQueries({ queryKey: ['reviews', productionId] });
      client.invalidateQueries({ queryKey: ['me'] });
      client.invalidateQueries({ queryKey: ['profile'] });
    },
  });
}

const json = (method: string, body?: unknown): RequestInit => ({ method, body: body === undefined ? undefined : JSON.stringify(body) });

export function useLogVisit(productionId: number) {
  return useProductionMutation(productionId, (input: LogVisitInput) =>
    api<{ data: MyProductionState }>(`/productions/${productionId}/log`, json('POST', input)),
  );
}

export function useRate(productionId: number) {
  return useProductionMutation(productionId, (halfStars: number | null) =>
    halfStars === null
      ? api<{ data: MyProductionState }>(`/productions/${productionId}/rating`, json('DELETE'))
      : api<{ data: MyProductionState }>(`/productions/${productionId}/rating`, json('PUT', { half_stars: halfStars })),
  );
}

export function useWatchlist(productionId: number) {
  return useProductionMutation(productionId, (on: boolean) =>
    api<{ data: MyProductionState }>(`/productions/${productionId}/watchlist`, json(on ? 'PUT' : 'DELETE')),
  );
}

export function useDeleteReview(productionId: number) {
  return useProductionMutation(productionId, () => api<{ data: MyProductionState }>(`/productions/${productionId}/review`, json('DELETE')));
}

export function useReport() {
  return useMutation({
    mutationFn: ({ reviewId, reason }: { reviewId: number; reason: ReportReason }) =>
      api<void>(`/reviews/${reviewId}/report`, json('POST', { reason })),
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
