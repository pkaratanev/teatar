import { BookOpen } from 'lucide-react-native';
import { useMemo } from 'react';
import { SectionList, View } from 'react-native';

import { useMyDiary } from '@/src/api/hooks';
import type { DiaryEntry } from '@/src/api/types';
import { useAuth } from '@/src/auth';
import { ProductionRow } from '@/src/components/ProductionRow';
import { ScreenHeader } from '@/src/components/ScreenHeader';
import { SignInPrompt } from '@/src/components/SignInPrompt';
import { Stars } from '@/src/components/Stars';
import { Empty, ErrorState, Loading } from '@/src/components/States';
import { Text } from '@/src/ui';

const MONTHS = ['януари', 'февруари', 'март', 'април', 'май', 'юни', 'юли', 'август', 'септември', 'октомври', 'ноември', 'декември'];

/** Everything you've seen, newest first, grouped by month. */
export default function DiaryScreen() {
  return (
    <View className="flex-1 bg-background">
      <ScreenHeader title="Дневник" />
      <Body />
    </View>
  );
}

function Body() {
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
  if (sections.length === 0) return <Empty icon={BookOpen} message="Още нищо. Отворете постановка и натиснете „Видях“." />;

  return (
    <SectionList
      sections={sections}
      keyExtractor={(e) => String(e.id)}
      onRefresh={diary.refetch}
      refreshing={diary.isRefetching && !diary.isFetchingNextPage}
      onEndReached={() => diary.hasNextPage && !diary.isFetchingNextPage && diary.fetchNextPage()}
      onEndReachedThreshold={0.5}
      contentContainerClassName="pb-6"
      stickySectionHeadersEnabled
      renderSectionHeader={({ section }) => (
        <View className="border-b border-border bg-background px-4 pb-2 pt-4">
          <Text variant="overline" className="text-foreground">
            {section.title}
          </Text>
        </View>
      )}
      renderItem={({ item }) => (
        <ProductionRow production={item.production} detail={item.seen_on.split('-').reverse().join('.')}>
          {item.half_stars ? (
            <View className="mt-1 flex-row items-center gap-1.5">
              <Text variant="caption">Вашата оценка</Text>
              <Stars halfStars={item.half_stars} size={12} />
            </View>
          ) : null}
        </ProductionRow>
      )}
    />
  );
}
