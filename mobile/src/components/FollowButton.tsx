import { router } from 'expo-router';
import { UserCheck, UserPlus } from 'lucide-react-native';

import { useFollow } from '@/src/api/hooks';
import type { FollowState } from '@/src/api/types';
import { useAuth } from '@/src/auth';
import { Button } from '@/src/ui';

export function FollowButton({ target, state }: { target: { username: string } | { personId: number }; state: FollowState }) {
  const { user } = useAuth();
  const follow = useFollow(target);
  const following = follow.data?.data.is_following ?? state.is_following;

  return (
    <Button
      size="sm"
      variant={following ? 'secondary' : 'primary'}
      icon={following ? UserCheck : UserPlus}
      loading={follow.isPending}
      onPress={() => (user ? follow.mutate(!following) : router.push('/sign-in'))}
    >
      {following ? 'Следвате' : 'Следвай'}
    </Button>
  );
}
