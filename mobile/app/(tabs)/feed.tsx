import { Users } from 'lucide-react-native';
import { FlatList, View } from 'react-native';

import { useFeed } from '@/src/api/hooks';
import { useAuth } from '@/src/auth';
import { ReviewCard } from '@/src/components/ReviewCard';
import { ScreenHeader } from '@/src/components/ScreenHeader';
import { SignInPrompt } from '@/src/components/SignInPrompt';
import { Empty, ErrorState, Loading } from '@/src/components/States';
import { Separator } from '@/src/ui';

/** Reviews from the people you follow. */
export default function FeedScreen() {
  return (
    <View className="flex-1 bg-background">
      <ScreenHeader title="Лента" />
      <Body />
    </View>
  );
}

function Body() {
  const { user } = useAuth();
  const feed = useFeed(!!user);

  if (user === undefined) return <Loading />;
  if (!user) return <SignInPrompt text="Следвайте приятели, за да виждате какво гледат и какво мислят." />;
  if (feed.isLoading) return <Loading />;
  if (feed.isError) return <ErrorState onRetry={feed.refetch} />;

  const reviews = feed.items;
  if (reviews.length === 0) {
    return <Empty icon={Users} message="Още няма нищо тук. Отворете профила на някого от ревютата и натиснете „Следвай“." />;
  }

  return (
    <FlatList
      data={reviews}
      keyExtractor={(r) => String(r.id)}
      onRefresh={feed.refetch}
      refreshing={feed.isRefetching && !feed.isFetchingNextPage}
      onEndReached={() => feed.hasNextPage && !feed.isFetchingNextPage && feed.fetchNextPage()}
      onEndReachedThreshold={0.5}
      ItemSeparatorComponent={() => <Separator className="ml-4" />}
      renderItem={({ item }) => <ReviewCard review={item} showProduction showAuthor />}
    />
  );
}
