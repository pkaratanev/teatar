import { Image } from 'expo-image';
import { Link, Stack, useLocalSearchParams } from 'expo-router';
import * as WebBrowser from 'expo-web-browser';
import { ScrollView, StyleSheet, View } from 'react-native';
import { Button, Divider, List, Text } from 'react-native-paper';

import { useProduction } from '@/src/api/hooks';
import { ErrorState, Loading } from '@/src/components/States';
import { formatDay, formatPrice, formatStars, formatTime } from '@/src/format';

export default function ProductionScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { data: p, isLoading, isError, refetch } = useProduction(Number(id));

  if (isLoading) return <Loading />;
  if (isError || !p) return <ErrorState onRetry={refetch} />;

  const directors = p.credits.filter((c) => c.role_type === 'director');
  const cast = p.credits.filter((c) => c.role_type === 'actor');
  const crew = p.credits.filter((c) => c.role_type !== 'actor' && c.role_type !== 'director');

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <Stack.Screen options={{ title: p.title }} />

      <View style={styles.header}>
        <Image source={p.poster_url} style={styles.poster} contentFit="cover" />
        <View style={styles.headerText}>
          <Text variant="headlineSmall">{p.title}</Text>
          {p.author && <Text variant="bodyLarge">{p.author}</Text>}
          {p.theatre && (
            <Link href={{ pathname: '/theatre/[id]', params: { id: p.theatre.id } }}>
              <Text variant="bodyMedium" style={styles.link}>
                {p.theatre.name}
              </Text>
            </Link>
          )}
          <Text variant="bodySmall" style={styles.muted}>
            {[p.age_advisory, p.running_time_minutes && `${p.running_time_minutes} мин.`].filter(Boolean).join(' · ')}
          </Text>
          {p.rating_average !== null && (
            <Text variant="titleMedium">
              {formatStars(p.rating_average)} <Text style={styles.muted}>({p.ratings_count})</Text>
            </Text>
          )}
        </View>
      </View>

      {p.synopsis && <Text style={styles.block}>{p.synopsis}</Text>}

      {/* Rating, review and "seen" arrive in the Accounts phase. */}

      {p.upcoming_performances.length > 0 && (
        <List.Section title="Предстоящи представления">
          {p.upcoming_performances.map((perf) => (
            <List.Item
              key={perf.id}
              title={`${formatDay(perf.starts_at)}, ${formatTime(perf.starts_at)}`}
              description={[perf.stage, formatPrice(perf.price_from, perf.price_to)].filter(Boolean).join(' · ')}
              right={() => {
                const url = perf.ticket_url ?? p.box_office_url;
                return url ? (
                  <Button compact onPress={() => WebBrowser.openBrowserAsync(url)}>
                    Билети
                  </Button>
                ) : null;
              }}
            />
          ))}
        </List.Section>
      )}

      <Divider />
      <CreditList title="Режисьор" credits={directors} />
      <CreditList title="Актьори" credits={cast} showCharacter />
      <CreditList title="Екип" credits={crew} showRole />
    </ScrollView>
  );
}

function CreditList({
  title,
  credits,
  showCharacter,
  showRole,
}: {
  title: string;
  credits: { person: { id: number; name: string }; role_label: string; character_name: string | null }[];
  showCharacter?: boolean;
  showRole?: boolean;
}) {
  if (credits.length === 0) return null;

  return (
    <List.Section title={title}>
      {credits.map((c, i) => (
        <Link key={`${c.person.id}-${i}`} href={{ pathname: '/person/[id]', params: { id: c.person.id } }} asChild>
          <List.Item
            title={c.person.name}
            description={showCharacter ? c.character_name : showRole ? c.role_label : undefined}
          />
        </Link>
      ))}
    </List.Section>
  );
}

const styles = StyleSheet.create({
  content: { paddingBottom: 32 },
  header: { flexDirection: 'row', gap: 16, padding: 16 },
  poster: { width: 110, height: 165, borderRadius: 8, backgroundColor: '#ddd' },
  headerText: { flex: 1, gap: 4 },
  block: { paddingHorizontal: 16, paddingBottom: 8, lineHeight: 22 },
  link: { textDecorationLine: 'underline' },
  muted: { opacity: 0.6 },
});
