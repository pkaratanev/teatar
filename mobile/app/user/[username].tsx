import { Stack, useLocalSearchParams } from 'expo-router';

import { useProfile } from '@/src/api/hooks';
import { ProfileView } from '@/src/components/ProfileView';
import { ErrorState, Loading } from '@/src/components/States';

export default function UserScreen() {
  const { username } = useLocalSearchParams<{ username: string }>();
  const profile = useProfile(username);

  if (profile.isLoading) return <Loading />;
  if (profile.isError || !profile.data) return <ErrorState onRetry={profile.refetch} />;

  return (
    <>
      <Stack.Screen options={{ title: `@${profile.data.username}` }} />
      <ProfileView profile={profile.data} />
    </>
  );
}
