import { Image } from 'expo-image';
import { Link, router, Stack, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import * as WebBrowser from 'expo-web-browser';
import { ScrollView, StyleSheet, View } from 'react-native';
import { Button, Divider, List, Text } from 'react-native-paper';

import { useProduction, useReviews, useWatchlist } from '@/src/api/hooks';
import { useAuth } from '@/src/auth';
import { LogSheet } from '@/src/components/LogSheet';
import { ReviewCard } from '@/src/components/ReviewCard';
import { Stars } from '@/src/components/Stars';
import { ShareButton } from '@/src/components/ShareButton';
import { ErrorState, Loading } from '@/src/components/States';
import { formatDay, formatPrice, formatStars, formatTime, guestLabel } from '@/src/format';
import { links } from '@/src/links';

export default function ProductionScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const productionId = Number(id);
  const { user } = useAuth();
  const { data, isLoading, isError, error, refetch } = useProduction(productionId);
  const reviews = useReviews(productionId);
  const watchlist = useWatchlist(productionId);
  const [logging, setLogging] = useState(false);

  if (isLoading) return <Loading />;
  if (isError || !data) return <ErrorState onRetry={refetch} error={error} />;

  const { production: p, me } = data;
  const needAccount = () => router.push('/sign-in');

  const directors = p.credits.filter((c) => c.role_type === 'director');
  const cast = p.credits.filter((c) => c.role_type === 'actor');
  const crew = p.credits.filter((c) => c.role_type !== 'actor' && c.role_type !== 'director');

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <Stack.Screen
        options={{
          title: p.title,
          headerRight: () => (
            <ShareButton text={[p.title, p.theatre?.name].filter(Boolean).join(' — ')} url={links.production(p.id)} />
          ),
        }}
      />

      <View style={styles.header}>
        <Image source={p.poster_url} style={styles.poster} contentFit="cover" />
        <View style={styles.headerText}>
          <Text variant="headlineSmall">{p.title}</Text>
          {p.author && <Text variant="bodyLarge">{p.author}</Text>}
          {guestLabel(p) && (
            <Text variant="bodyMedium" style={styles.muted}>
              {guestLabel(p)}
            </Text>
          )}
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

      <View style={styles.actions}>
        <Button mode="contained" icon="check" onPress={() => (user ? setLogging(true) : needAccount())}>
          {me?.seen_on.length ? 'Гледах пак' : 'Видях'}
        </Button>
        <Button
          mode={me?.on_watchlist ? 'contained-tonal' : 'outlined'}
          icon={me?.on_watchlist ? 'bookmark' : 'bookmark-outline'}
          loading={watchlist.isPending}
          onPress={() => (user ? watchlist.mutate(!me?.on_watchlist) : needAccount())}
        >
          {me?.on_watchlist ? 'В списъка' : 'Искам да гледам'}
        </Button>
      </View>

      {me && (me.half_stars || me.seen_on.length > 0) && (
        <View style={styles.mine}>
          <Stars halfStars={me.half_stars} size={18} />
          {me.seen_on.length > 0 && (
            <Text variant="bodySmall" style={styles.muted}>
              Гледано: {me.seen_on.map((d) => d.split('-').reverse().join('.')).join(', ')}
            </Text>
          )}
        </View>
      )}

      {p.synopsis && <Text style={styles.block}>{p.synopsis}</Text>}

      <LogSheet productionId={p.id} title={p.title} me={me} visible={logging} onDismiss={() => setLogging(false)} />

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

      <Divider />
      <List.Section title={`Ревюта${reviews.data?.meta.total ? ` (${reviews.data.meta.total})` : ''}`}>
        {reviews.data?.data.length ? (
          reviews.data.data.map((r) => <ReviewCard key={r.id} review={r} />)
        ) : (
          <Text style={[styles.block, styles.muted]}>Все още няма ревюта. Бъдете първи!</Text>
        )}
      </List.Section>
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
  actions: { flexDirection: 'row', gap: 8, paddingHorizontal: 16, paddingBottom: 8, flexWrap: 'wrap' },
  mine: { paddingHorizontal: 16, paddingBottom: 12, gap: 4 },
  muted: { opacity: 0.6 },
});
