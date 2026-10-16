import { useMemo, useState } from 'react';
import { ScrollView, SectionList, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Chip, SegmentedButtons, Text } from 'react-native-paper';

import { useProgramme, useTheatres } from '@/src/api/hooks';
import type { Performance, ProgrammeRange } from '@/src/api/types';
import { ProductionRow } from '@/src/components/ProductionRow';
import { Empty, ErrorState, Loading } from '@/src/components/States';
import { dayKey, formatDay, formatTime } from '@/src/format';

export default function ProgrammeScreen() {
  const [range, setRange] = useState<ProgrammeRange>('week');
  const [theatreId, setTheatreId] = useState<number | undefined>(undefined);
  const theatres = useTheatres();
  const { items, isLoading, isError, refetch, isRefetching, fetchNextPage, hasNextPage, isFetchingNextPage } = useProgramme(
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
    return [...byDay.values()].map((items) => ({ title: formatDay(items[0].starts_at), data: items }));
  }, [items]);

  return (
    <View style={styles.screen}>
      <SegmentedButtons
        style={styles.tabs}
        value={range}
        onValueChange={(v) => setRange(v as ProgrammeRange)}
        buttons={[
          { value: 'tonight', label: 'Тази вечер' },
          { value: 'week', label: 'Седмица' },
          { value: 'month', label: 'Месец' },
        ]}
      />
      {playing.length > 1 && (
        <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.chipBar} contentContainerStyle={styles.chips}>
          <Chip selected={theatreId === undefined} onPress={() => setTheatreId(undefined)} showSelectedOverlay>
            Всички театри
          </Chip>
          {playing.map((t) => (
            <Chip key={t.id} selected={theatreId === t.id} onPress={() => setTheatreId(theatreId === t.id ? undefined : t.id)} showSelectedOverlay>
              {shortName(t.name)}
            </Chip>
          ))}
        </ScrollView>
      )}
      {isLoading ? (
        <Loading />
      ) : isError ? (
        <ErrorState onRetry={refetch} />
      ) : sections.length === 0 ? (
        <Empty message="Няма представления в този период." />
      ) : (
        <SectionList
          sections={sections}
          keyExtractor={(p) => String(p.id)}
          onRefresh={refetch}
          refreshing={isRefetching && !isFetchingNextPage}
          onEndReached={() => hasNextPage && !isFetchingNextPage && fetchNextPage()}
          onEndReachedThreshold={0.5}
          ListFooterComponent={isFetchingNextPage ? <ActivityIndicator style={styles.more} /> : null}
          stickySectionHeadersEnabled
          renderSectionHeader={({ section }) => (
            <Text variant="titleSmall" style={styles.day}>
              {section.title}
            </Text>
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

/** "Театър „Българска армия“" → "Българска армия" for compact chips. */
function shortName(name: string): string {
  const quoted = name.match(/[„"“]([^“”"]+)[“”"]/);
  return quoted ? quoted[1] : name;
}

const styles = StyleSheet.create({
  screen: { flex: 1 },
  tabs: { marginHorizontal: 16, marginTop: 16, marginBottom: 8 },
  chipBar: { flexGrow: 0 },
  more: { padding: 16 },
  chips: { gap: 8, paddingHorizontal: 16, paddingBottom: 8 },
  day: { paddingHorizontal: 16, paddingVertical: 6, opacity: 0.7, textTransform: 'capitalize' },
});
