import { Image } from 'expo-image';
import { Link } from 'expo-router';
import { Pressable, StyleSheet, View } from 'react-native';
import { Text } from 'react-native-paper';

import type { ProductionSummary } from '@/src/api/types';
import { formatStars, guestLabel } from '@/src/format';

type Props = {
  production: ProductionSummary;
  /** Extra line under the title, e.g. "19:00 · Голяма сцена". */
  detail?: string;
};

export function ProductionRow({ production, detail }: Props) {
  return (
    <Link href={{ pathname: '/production/[id]', params: { id: production.id } }} asChild>
      <Pressable style={styles.row}>
        <Image source={production.poster_url} style={styles.poster} contentFit="cover" />
        <View style={styles.text}>
          {detail && <Text variant="labelLarge">{detail}</Text>}
          <Text variant="titleMedium" numberOfLines={2}>
            {production.title}
          </Text>
          {production.theatre && (
            <Text variant="bodyMedium" style={styles.muted} numberOfLines={1}>
              {production.theatre.name}
            </Text>
          )}
          {guestLabel(production) && (
            <Text variant="bodySmall" style={styles.muted} numberOfLines={1}>
              {guestLabel(production)}
            </Text>
          )}
          {production.rating_average !== null && (
            <Text variant="bodySmall">
              {formatStars(production.rating_average)} · {production.ratings_count}
            </Text>
          )}
        </View>
      </Pressable>
    </Link>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', paddingHorizontal: 16, paddingVertical: 10, gap: 12 },
  poster: { width: 56, height: 84, borderRadius: 6, backgroundColor: '#ddd' },
  text: { flex: 1, justifyContent: 'center', gap: 2 },
  muted: { opacity: 0.7 },
});
