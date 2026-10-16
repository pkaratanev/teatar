import { Link } from 'expo-router';
import type { ReactNode } from 'react';
import { ScrollView, StyleSheet, View } from 'react-native';
import { List, Text, useTheme } from 'react-native-paper';

import type { Profile } from '@/src/api/types';
import { ProductionRow } from './ProductionRow';
import { ReviewCard } from './ReviewCard';

/** Shared by the Profile tab (your own) and /user/[username] (someone else's). */
export function ProfileView({ profile, header, footer }: { profile: Profile; header?: ReactNode; footer?: ReactNode }) {
  const { stats } = profile;

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <View style={styles.head}>
        <Text variant="headlineSmall">{profile.name}</Text>
        <Text variant="bodyMedium" style={styles.muted}>
          @{profile.username}
        </Text>
        {profile.bio && <Text style={styles.bio}>{profile.bio}</Text>}
      </View>
      {header}

      <View style={styles.tiles}>
        <Tile value={stats.seen_this_year} label="тази година" />
        <Tile value={stats.seen_total} label="общо гледани" />
        <Tile value={stats.ratings_count} label="оценки" />
      </View>

      {stats.ratings_count > 0 && <Histogram counts={stats.ratings_histogram} />}

      {(stats.favourite_theatre || stats.most_seen_actor) && (
        <List.Section>
          {stats.favourite_theatre && (
            <Link href={{ pathname: '/theatre/[id]', params: { id: stats.favourite_theatre.id } }} asChild>
              <List.Item
                title={stats.favourite_theatre.name}
                description={`Любим театър · ${stats.favourite_theatre.visits} посещения`}
                left={(p) => <List.Icon {...p} icon="domain" />}
              />
            </Link>
          )}
          {stats.most_seen_actor && (
            <Link href={{ pathname: '/person/[id]', params: { id: stats.most_seen_actor.id } }} asChild>
              <List.Item
                title={stats.most_seen_actor.name}
                description={`Най-гледан актьор · ${stats.most_seen_actor.times} пъти`}
                left={(p) => <List.Icon {...p} icon="account-star" />}
              />
            </Link>
          )}
        </List.Section>
      )}

      {profile.recent_diary.length > 0 && (
        <List.Section title="Последно гледани">
          {profile.recent_diary.map((e) => (
            <ProductionRow key={e.id} production={e.production} detail={e.seen_on.split('-').reverse().join('.')} />
          ))}
        </List.Section>
      )}

      {profile.recent_reviews.length > 0 && (
        <List.Section title="Ревюта">
          {profile.recent_reviews.map((r) => (
            <ReviewCard key={r.id} review={r} showProduction />
          ))}
        </List.Section>
      )}

      {footer}
    </ScrollView>
  );
}

function Tile({ value, label }: { value: number; label: string }) {
  return (
    <View style={styles.tile}>
      <Text variant="headlineMedium">{value}</Text>
      <Text variant="bodySmall" style={styles.muted}>
        {label}
      </Text>
    </View>
  );
}

/** Ten bars, ½★ … 5★, like Letterboxd's ratings spread. */
function Histogram({ counts }: { counts: number[] }) {
  const theme = useTheme();
  const max = Math.max(1, ...counts);

  return (
    <View style={styles.histogram} accessibilityLabel="Разпределение на оценките">
      <Text variant="labelMedium" style={styles.muted}>
        ½★
      </Text>
      <View style={styles.bars}>
        {counts.map((n, i) => (
          <View
            key={i}
            style={[styles.bar, { height: 4 + (n / max) * 56, backgroundColor: n ? theme.colors.primary : theme.colors.surfaceVariant }]}
          />
        ))}
      </View>
      <Text variant="labelMedium" style={styles.muted}>
        5★
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  content: { paddingBottom: 32 },
  head: { padding: 16, gap: 2 },
  bio: { marginTop: 8 },
  muted: { opacity: 0.6 },
  tiles: { flexDirection: 'row', paddingHorizontal: 8 },
  tile: { flex: 1, alignItems: 'center', paddingVertical: 8 },
  histogram: { flexDirection: 'row', alignItems: 'flex-end', gap: 8, paddingHorizontal: 16, paddingVertical: 12 },
  bars: { flex: 1, flexDirection: 'row', alignItems: 'flex-end', gap: 4, height: 60 },
  bar: { flex: 1, borderRadius: 3 },
});
