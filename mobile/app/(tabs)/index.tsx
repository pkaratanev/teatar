import { useMemo, useState } from 'react';
import { SectionList, StyleSheet, View } from 'react-native';
import { SegmentedButtons, Text } from 'react-native-paper';

import { useProgramme } from '@/src/api/hooks';
import type { Performance, ProgrammeRange } from '@/src/api/types';
import { ProductionRow } from '@/src/components/ProductionRow';
import { Empty, ErrorState, Loading } from '@/src/components/States';
import { dayKey, formatDay, formatTime } from '@/src/format';

export default function ProgrammeScreen() {
  const [range, setRange] = useState<ProgrammeRange>('week');
  const { data, isLoading, isError, refetch, isRefetching } = useProgramme(range);

  const sections = useMemo(() => {
    const byDay = new Map<string, Performance[]>();
    for (const p of data?.data ?? []) {
      const key = dayKey(p.starts_at);
      byDay.set(key, [...(byDay.get(key) ?? []), p]);
    }
    return [...byDay.values()].map((items) => ({ title: formatDay(items[0].starts_at), data: items }));
  }, [data]);

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
          refreshing={isRefetching}
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

const styles = StyleSheet.create({
  screen: { flex: 1 },
  tabs: { margin: 16 },
  day: { paddingHorizontal: 16, paddingVertical: 6, opacity: 0.7, textTransform: 'capitalize' },
});
