import { Image } from 'expo-image';
import { Stack, useLocalSearchParams } from 'expo-router';
import { ScrollView, StyleSheet, View } from 'react-native';
import { List, Text } from 'react-native-paper';

import { usePerson } from '@/src/api/hooks';
import type { PersonCredit } from '@/src/api/types';
import { FollowButton } from '@/src/components/FollowButton';
import { ProductionRow } from '@/src/components/ProductionRow';
import { ErrorState, Loading } from '@/src/components/States';

export default function PersonScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { data, isLoading, isError, refetch } = usePerson(Number(id));

  if (isLoading) return <Loading />;
  if (isError || !data) return <ErrorState onRetry={refetch} />;

  const { person, social } = data;

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <Stack.Screen options={{ title: person.name }} />
      <View style={styles.header}>
        <Image source={person.headshot_url} style={styles.photo} contentFit="cover" />
        <Text variant="headlineSmall" style={styles.name}>
          {person.name}
        </Text>
        <FollowButton target={{ personId: person.id }} state={social} />
      </View>
      {person.bio && <Text style={styles.bio}>{person.bio}</Text>}
      <Credits title="Сега на сцена" credits={person.current} />
      <Credits title="Минали постановки" credits={person.past} />
    </ScrollView>
  );
}

function Credits({ title, credits }: { title: string; credits: PersonCredit[] }) {
  if (credits.length === 0) return null;

  return (
    <List.Section title={title}>
      {credits.map((c, i) => (
        <ProductionRow
          key={`${c.production.id}-${i}`}
          production={c.production}
          detail={c.character_name ? `${c.role_label} · ${c.character_name}` : c.role_label}
        />
      ))}
    </List.Section>
  );
}

const styles = StyleSheet.create({
  content: { paddingBottom: 32 },
  header: { alignItems: 'center', padding: 16, gap: 12 },
  photo: { width: 120, height: 120, borderRadius: 60, backgroundColor: '#ddd' },
  name: { textAlign: 'center' },
  bio: { paddingHorizontal: 16, lineHeight: 22 },
});
