import { FlatList } from 'react-native';
import { Divider } from 'react-native-paper';

import { useFeed } from '@/src/api/hooks';
import { useAuth } from '@/src/auth';
import { ReviewCard } from '@/src/components/ReviewCard';
import { SignInPrompt } from '@/src/components/SignInPrompt';
import { Empty, ErrorState, Loading } from '@/src/components/States';

/** Reviews from the people you follow. */
export default function FeedScreen() {
  const { user } = useAuth();
  const feed = useFeed(!!user);

  if (user === undefined) return <Loading />;
  if (!user) return <SignInPrompt text="Следвайте приятели, за да виждате какво гледат и какво мислят." />;
  if (feed.isLoading) return <Loading />;
  if (feed.isError) return <ErrorState onRetry={feed.refetch} />;

  const reviews = feed.data?.data ?? [];
  if (reviews.length === 0) {
    return <Empty message="Още няма нищо тук. Отворете профила на някого от ревютата и натиснете „Следвай“." />;
  }

  return (
    <FlatList
      data={reviews}
      keyExtractor={(r) => String(r.id)}
      onRefresh={feed.refetch}
      refreshing={feed.isRefetching}
      ItemSeparatorComponent={Divider}
      renderItem={({ item }) => <ReviewCard review={item} showProduction showAuthor />}
    />
  );
}
