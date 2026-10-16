import { router } from 'expo-router';
import { Button } from 'react-native-paper';

import { useFollow } from '@/src/api/hooks';
import type { FollowState } from '@/src/api/types';
import { useAuth } from '@/src/auth';

export function FollowButton({ target, state }: { target: { username: string } | { personId: number }; state: FollowState }) {
  const { user } = useAuth();
  const follow = useFollow(target);
  const following = follow.data?.data.is_following ?? state.is_following;
  const count = follow.data?.data.followers_count ?? state.followers_count;

  return (
    <Button
      mode={following ? 'contained-tonal' : 'outlined'}
      icon={following ? 'account-check' : 'account-plus'}
      loading={follow.isPending}
      onPress={() => (user ? follow.mutate(!following) : router.push('/sign-in'))}
    >
      {following ? 'Следвате' : 'Следвай'}
      {count > 0 ? ` · ${count}` : ''}
    </Button>
  );
}
