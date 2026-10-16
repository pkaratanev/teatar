import { Link } from 'expo-router';
import type { ReactNode } from 'react';
import { Pressable, View } from 'react-native';

import type { ProductionSummary } from '@/src/api/types';
import { guestLabel } from '@/src/format';
import { Text } from '@/src/ui';
import { Poster } from './Poster';
import { RatingBadge } from './Stars';

type Props = {
  production: ProductionSummary;
  /** Line above the title, e.g. "19:00 · Голяма сцена". */
  detail?: string;
  /** Extra content under the text, e.g. the user's own stars. */
  children?: ReactNode;
};

export function ProductionRow({ production, detail, children }: Props) {
  const guest = guestLabel(production);

  return (
    <Link href={{ pathname: '/production/[id]', params: { id: production.id } }} asChild>
      <Pressable className="flex-row gap-3.5 px-4 py-2.5 active:bg-card">
        <Poster uri={production.poster_url} title={production.title} width={60} radius={6} />
        <View className="flex-1 justify-center gap-0.5">
          {detail && (
            <Text variant="small" className="font-body-semibold text-primary">
              {detail}
            </Text>
          )}
          <Text className="font-body-semibold text-[16px] leading-[21px]" numberOfLines={2}>
            {production.title}
          </Text>
          {production.theatre && (
            <Text variant="muted" numberOfLines={1}>
              {production.theatre.name}
            </Text>
          )}
          {guest && (
            <Text variant="caption" numberOfLines={1}>
              {guest}
            </Text>
          )}
          {production.rating_average !== null && (
            <View className="mt-0.5">
              <RatingBadge average={production.rating_average} count={production.ratings_count} />
            </View>
          )}
          {children}
        </View>
      </Pressable>
    </Link>
  );
}
