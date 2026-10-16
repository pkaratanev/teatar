import { useMemo } from 'react';
import { SectionList, StyleSheet, View } from 'react-native';
import { Text } from 'react-native-paper';

import { useMyDiary } from '@/src/api/hooks';
import type { DiaryEntry } from '@/src/api/types';
import { useAuth } from '@/src/auth';
import { ProductionRow } from '@/src/components/ProductionRow';
import { SignInPrompt } from '@/src/components/SignInPrompt';
import { Stars } from '@/src/components/Stars';
import { Empty, ErrorState, Loading } from '@/src/components/States';

const MONTHS = ['януари', 'февруари', 'март', 'април', 'май', 'юни', 'юли', 'август', 'септември', 'октомври', 'ноември', 'декември'];

/** Everything you've seen, newest first, grouped by month. */
export default function DiaryScreen() {
  const { user } = useAuth();
  const diary = useMyDiary(!!user);

  const sections = useMemo(() => {
    const byMonth = new Map<string, DiaryEntry[]>();
    for (const e of diary.items) {
      const key = e.seen_on.slice(0, 7);
      byMonth.set(key, [...(byMonth.get(key) ?? []), e]);
    }
    return [...byMonth.entries()].map(([key, data]) => ({
      title: `${MONTHS[Number(key.slice(5, 7)) - 1]} ${key.slice(0, 4)}`,
      data,
    }));
  }, [diary.items]);

  if (user === undefined) return <Loading />;
  if (!user) return <SignInPrompt text="Тук ще е дневникът на всичко, което сте гледали." />;
  if (diary.isLoading) return <Loading />;
  if (diary.isError) return <ErrorState onRetry={diary.refetch} />;
  if (sections.length === 0) return <Empty message="Още нищо. Отворете постановка и натиснете „Видях“." />;

  return (
    <SectionList
      sections={sections}
      keyExtractor={(e) => String(e.id)}
      onRefresh={diary.refetch}
      refreshing={diary.isRefetching && !diary.isFetchingNextPage}
      onEndReached={() => diary.hasNextPage && !diary.isFetchingNextPage && diary.fetchNextPage()}
      onEndReachedThreshold={0.5}
      renderSectionHeader={({ section }) => (
        <Text variant="titleSmall" style={styles.month}>
          {section.title}
        </Text>
      )}
      renderItem={({ item }) => (
        <View>
          <ProductionRow production={item.production} detail={item.seen_on.split('-').reverse().join('.')} />
          {item.half_stars ? (
            <View style={styles.stars}>
              <Stars halfStars={item.half_stars} size={14} />
            </View>
          ) : null}
        </View>
      )}
    />
  );
}

const styles = StyleSheet.create({
  month: { paddingHorizontal: 16, paddingVertical: 6, opacity: 0.7, textTransform: 'capitalize' },
  stars: { paddingLeft: 84, marginTop: -6, paddingBottom: 6 },
});
