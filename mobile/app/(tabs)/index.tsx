import { CalendarX } from 'lucide-react-native';
import { useMemo, useState } from 'react';
import { ActivityIndicator, ScrollView, SectionList, View } from 'react-native';

import { useProgramme, useTheatres } from '@/src/api/hooks';
import { useCity } from '@/src/city';
import type { Performance, ProgrammeRange } from '@/src/api/types';
import { CityPicker } from '@/src/components/CityPicker';
import { ProductionRow } from '@/src/components/ProductionRow';
import { ScreenHeader } from '@/src/components/ScreenHeader';
import { Empty, ErrorState, Loading } from '@/src/components/States';
import { dayKey, formatDayHeading, formatTime } from '@/src/format';
import { Chip, colors, Segmented, Text } from '@/src/ui';

export default function ProgrammeScreen() {
  const [range, setRange] = useState<ProgrammeRange>('week');
  const [theatreId, setTheatreId] = useState<number | undefined>(undefined);
  const { city } = useCity();
  const theatres = useTheatres(city);
  const { items, isLoading, isError, refetch, isRefetching, fetchNextPage, hasNextPage, isFetchingNextPage } = useProgramme(
    city,
    range,
    theatreId,
  );
  const playing = (theatres.data ?? []).filter((t) => (t.upcoming_count ?? 1) > 0);

  const sections = useMemo(() => {
    const byDay = new Map<string, Performance[]>();
    for (const p of items) {
      const key = dayKey(p.starts_at);
      byDay.set(key, [...(byDay.get(key) ?? []), p]);
    }
    return [...byDay.values()].map((items) => ({ title: formatDayHeading(items[0].starts_at), data: items }));
  }, [items]);

  return (
    <View className="flex-1 bg-background">
      <ScreenHeader title="Програма" right={<CityPicker onChange={() => setTheatreId(undefined)} />}>
        <Segmented
          className="mt-4"
          value={range}
          onChange={setRange}
          options={[
            { value: 'tonight', label: 'Тази вечер' },
            { value: 'week', label: 'Седмица' },
            { value: 'month', label: 'Месец' },
          ]}
        />
      </ScreenHeader>
      {playing.length > 1 && (
        <View>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerClassName="gap-2 px-4 pb-3">
            <Chip label="Всички театри" selected={theatreId === undefined} onPress={() => setTheatreId(undefined)} />
            {playing.map((t) => (
              <Chip
                key={t.id}
                label={t.short_name ?? t.name}
                selected={theatreId === t.id}
                onPress={() => setTheatreId(theatreId === t.id ? undefined : t.id)}
              />
            ))}
          </ScrollView>
        </View>
      )}
      {isLoading ? (
        <Loading />
      ) : isError ? (
        <ErrorState onRetry={refetch} />
      ) : sections.length === 0 ? (
        <Empty icon={CalendarX} message="Няма представления в този период." />
      ) : (
        <SectionList
          sections={sections}
          keyExtractor={(p) => String(p.id)}
          onRefresh={refetch}
          refreshing={isRefetching && !isFetchingNextPage}
          onEndReached={() => hasNextPage && !isFetchingNextPage && fetchNextPage()}
          onEndReachedThreshold={0.5}
          ListFooterComponent={isFetchingNextPage ? <ActivityIndicator className="p-4" color={colors.primary} /> : null}
          contentContainerClassName="pb-6"
          stickySectionHeadersEnabled
          renderSectionHeader={({ section }) => (
            <View className="border-b border-border bg-background px-4 pb-2 pt-4">
              <Text variant="overline" className="text-foreground">
                {section.title}
              </Text>
            </View>
          )}
          renderItem={({ item }) =>
            item.production ? (
              <ProductionRow
                production={item.production}
                detail={[formatTime(item.starts_at), item.stage].filter(Boolean).join(' · ')}
              />
            ) : null
          }
        />
      )}
    </View>
  );
}
