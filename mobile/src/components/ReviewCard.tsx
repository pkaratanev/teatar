import { Link } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import { IconButton, Menu, Snackbar, Text } from 'react-native-paper';

import { useReport } from '@/src/api/hooks';
import type { ReportReason, Review } from '@/src/api/types';
import { useAuth } from '@/src/auth';
import { Stars } from './Stars';

const REASONS: { value: ReportReason; label: string }[] = [
  { value: 'spoiler', label: 'Спойлер без отбелязване' },
  { value: 'abuse', label: 'Обидно съдържание' },
  { value: 'spam', label: 'Спам' },
  { value: 'other', label: 'Друго' },
];

/** One review: author, stars, text. Spoilers stay hidden until tapped; others' reviews can be reported. */
export function ReviewCard({ review, showProduction = false }: { review: Review; showProduction?: boolean }) {
  const { user } = useAuth();
  const report = useReport();
  const [revealed, setRevealed] = useState(!review.has_spoilers || review.is_mine);
  const [menu, setMenu] = useState(false);
  const [thanks, setThanks] = useState(false);

  return (
    <View style={styles.card}>
      <View style={styles.header}>
        <View style={styles.headerText}>
          {showProduction && review.production ? (
            <Link href={{ pathname: '/production/[id]', params: { id: review.production.id } }}>
              <Text variant="labelLarge">{review.production.title}</Text>
            </Link>
          ) : review.user ? (
            <Link href={{ pathname: '/user/[username]', params: { username: review.user.username } }}>
              <Text variant="labelLarge">{review.user.name}</Text>
            </Link>
          ) : null}
          <Stars halfStars={review.half_stars ?? null} size={14} />
        </View>
        {user && !review.is_mine && (
          <Menu visible={menu} onDismiss={() => setMenu(false)} anchor={<IconButton icon="dots-vertical" size={18} onPress={() => setMenu(true)} />}>
            {REASONS.map((r) => (
              <Menu.Item
                key={r.value}
                title={`Докладвай: ${r.label}`}
                onPress={() => {
                  setMenu(false);
                  report.mutate({ reviewId: review.id, reason: r.value }, { onSuccess: () => setThanks(true) });
                }}
              />
            ))}
          </Menu>
        )}
      </View>

      {revealed ? (
        <Text variant="bodyMedium">{review.body}</Text>
      ) : (
        <Pressable onPress={() => setRevealed(true)} accessibilityRole="button">
          <Text variant="bodyMedium" style={styles.spoiler}>
            Съдържа спойлери. Докоснете, за да прочетете.
          </Text>
        </Pressable>
      )}

      <Snackbar visible={thanks} onDismiss={() => setThanks(false)} duration={3000}>
        Благодарим! Ще прегледаме ревюто.
      </Snackbar>
    </View>
  );
}

const styles = StyleSheet.create({
  card: { paddingHorizontal: 16, paddingVertical: 12, gap: 6 },
  header: { flexDirection: 'row', alignItems: 'center' },
  headerText: { flex: 1, gap: 2 },
  spoiler: { fontStyle: 'italic', opacity: 0.6 },
});
