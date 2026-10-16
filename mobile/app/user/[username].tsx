import { Stack, useLocalSearchParams } from 'expo-router';

import { useProfile } from '@/src/api/hooks';
import { ProfileView } from '@/src/components/ProfileView';
import { ShareButton } from '@/src/components/ShareButton';
import { ErrorState, Loading } from '@/src/components/States';
import { links } from '@/src/links';

export default function UserScreen() {
  const { username } = useLocalSearchParams<{ username: string }>();
  const profile = useProfile(username);

  if (profile.isLoading) return <Loading />;
  if (profile.isError || !profile.data) return <ErrorState onRetry={profile.refetch} error={profile.error} />;

  const user = profile.data;

  return (
    <>
      <Stack.Screen
        options={{
          title: `@${user.username}`,
          headerRight: () => <ShareButton text={`${user.name} в Teatar`} url={links.user(user.username)} />,
        }}
      />
      <ProfileView profile={user} />
    </>
  );
}
