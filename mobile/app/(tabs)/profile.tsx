import { router } from 'expo-router';
import { LogOut, Pencil, Trash2 } from 'lucide-react-native';
import { useState } from 'react';
import { View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { useMyWatchlist, useProfile } from '@/src/api/hooks';
import { useAuth } from '@/src/auth';
import { ProductionRow } from '@/src/components/ProductionRow';
import { ProfileView } from '@/src/components/ProfileView';
import { ScreenHeader } from '@/src/components/ScreenHeader';
import { SignInPrompt } from '@/src/components/SignInPrompt';
import { ErrorState, Loading } from '@/src/components/States';
import { formatDay } from '@/src/format';
import { Button, Dialog, Section, Text } from '@/src/ui';

export default function ProfileScreen() {
  const { user, signOut, deleteAccount } = useAuth();
  const profile = useProfile(user?.username);
  const watchlist = useMyWatchlist(!!user);
  const insets = useSafeAreaInsets();
  const [confirmDelete, setConfirmDelete] = useState(false);
  const [deleting, setDeleting] = useState(false);

  if (user === undefined) return <Loading />;
  if (!user) {
    return (
      <View className="flex-1 bg-background">
        <ScreenHeader title="Профил" />
        <SignInPrompt text="Оценки, ревюта, дневник и списък „Искам да гледам“ — всичко на едно място." />
      </View>
    );
  }
  if (profile.isLoading) return <Loading />;
  if (profile.isError || !profile.data) return <ErrorState onRetry={profile.refetch} />;

  const watching = watchlist.data?.data ?? [];

  return (
    <>
      <ProfileView
        profile={profile.data}
        topInset={insets.top}
        actions={
          <Button variant="secondary" size="sm" icon={Pencil} onPress={() => router.push('/edit-profile')}>
            Редактирай
          </Button>
        }
        header={
          watching.length > 0 && (
            <Section title="Искам да гледам">
              {watching.map((p) => (
                <ProductionRow
                  key={p.id}
                  production={p}
                  detail={p.next_performance_at ? `Следващо: ${formatDay(p.next_performance_at)}` : 'Няма обявени дати'}
                />
              ))}
            </Section>
          )
        }
        footer={
          <View className="mt-8 gap-3 px-4">
            <Button variant="outline" icon={LogOut} onPress={signOut}>
              Изход
            </Button>
            <Button variant="ghost" size="sm" icon={Trash2} iconColor="#F0525A" onPress={() => setConfirmDelete(true)}>
              <Text className="font-body-semibold text-[13px] text-destructive">Изтрий профила</Text>
            </Button>
          </View>
        }
      />

      <Dialog
        visible={confirmDelete}
        onClose={() => setConfirmDelete(false)}
        title="Изтриване на профила"
        actions={
          <>
            <Button variant="ghost" size="sm" onPress={() => setConfirmDelete(false)}>
              Отказ
            </Button>
            <Button
              variant="destructive"
              size="sm"
              loading={deleting}
              onPress={async () => {
                setDeleting(true);
                try {
                  await deleteAccount();
                } finally {
                  setDeleting(false);
                  setConfirmDelete(false);
                }
              }}
            >
              Изтрий завинаги
            </Button>
          </>
        }
      >
        <Text variant="muted">Профилът и всичко в него — оценки, ревюта, дневник и списък — ще бъдат изтрити завинаги.</Text>
      </Dialog>
    </>
  );
}
