import { router } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, View } from 'react-native';
import { Button, Dialog, List, Portal, Text } from 'react-native-paper';

import { useMyWatchlist, useProfile } from '@/src/api/hooks';
import { useAuth } from '@/src/auth';
import { ProductionRow } from '@/src/components/ProductionRow';
import { ProfileView } from '@/src/components/ProfileView';
import { SignInPrompt } from '@/src/components/SignInPrompt';
import { ErrorState, Loading } from '@/src/components/States';
import { formatDay } from '@/src/format';

export default function ProfileScreen() {
  const { user, signOut, deleteAccount } = useAuth();
  const profile = useProfile(user?.username);
  const watchlist = useMyWatchlist(!!user);
  const [confirmDelete, setConfirmDelete] = useState(false);
  const [deleting, setDeleting] = useState(false);

  if (user === undefined) return <Loading />;
  if (!user) return <SignInPrompt text="Оценки, ревюта, дневник и списък „Искам да гледам“ — всичко на едно място." />;
  if (profile.isLoading) return <Loading />;
  if (profile.isError || !profile.data) return <ErrorState onRetry={profile.refetch} />;

  const watching = watchlist.data?.data ?? [];

  return (
    <>
      <ProfileView
        profile={profile.data}
        header={
          <>
            <View style={styles.edit}>
              <Button mode="contained-tonal" icon="pencil" compact onPress={() => router.push('/edit-profile')}>
                Редактирай профила
              </Button>
            </View>
            {watching.length > 0 && (
              <List.Section title="Искам да гледам">
                {watching.map((p) => (
                  <ProductionRow
                    key={p.id}
                    production={p}
                    detail={p.next_performance_at ? `Следващо: ${formatDay(p.next_performance_at)}` : 'Няма обявени дати'}
                  />
                ))}
              </List.Section>
            )}
          </>
        }
        footer={
          <View style={styles.account}>
            <Button mode="outlined" onPress={signOut}>
              Изход
            </Button>
            <Button textColor="#B3261E" onPress={() => setConfirmDelete(true)}>
              Изтрий профила
            </Button>
          </View>
        }
      />

      <Portal>
        <Dialog visible={confirmDelete} onDismiss={() => setConfirmDelete(false)}>
          <Dialog.Title>Изтриване на профила</Dialog.Title>
          <Dialog.Content>
            <Text>Профилът и всичко в него — оценки, ревюта, дневник и списък — ще бъдат изтрити завинаги.</Text>
          </Dialog.Content>
          <Dialog.Actions>
            <Button onPress={() => setConfirmDelete(false)}>Отказ</Button>
            <Button
              textColor="#B3261E"
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
              Изтрий
            </Button>
          </Dialog.Actions>
        </Dialog>
      </Portal>
    </>
  );
}

const styles = StyleSheet.create({
  account: { padding: 16, gap: 8 },
  edit: { paddingHorizontal: 16, paddingBottom: 8, alignItems: 'flex-start' },
});
