import { MaterialCommunityIcons } from '@expo/vector-icons';
import { Pressable, StyleSheet, View } from 'react-native';
import { useTheme } from 'react-native-paper';

/** Read-only stars for a value in half stars (1–10). */
export function Stars({ halfStars, size = 16 }: { halfStars: number | null | undefined; size?: number }) {
  const theme = useTheme();
  if (!halfStars) return null;

  return (
    <View style={styles.row}>
      {[1, 2, 3, 4, 5].map((i) => (
        <MaterialCommunityIcons key={i} name={iconFor(halfStars, i)} size={size} color={theme.colors.primary} />
      ))}
    </View>
  );
}

/**
 * Tap the left half of a star for ½, the right half for a whole star.
 * Tapping the current value again clears it.
 */
export function StarInput({
  value,
  onChange,
  size = 40,
}: {
  value: number | null;
  onChange: (halfStars: number | null) => void;
  size?: number;
}) {
  const theme = useTheme();

  return (
    <View style={styles.row} accessibilityRole="adjustable" accessibilityLabel={`Оценка ${value ? value / 2 : 0} от 5`}>
      {[1, 2, 3, 4, 5].map((i) => (
        <View key={i} style={{ width: size, height: size }}>
          <MaterialCommunityIcons name={iconFor(value ?? 0, i)} size={size} color={theme.colors.primary} />
          <View style={StyleSheet.absoluteFill}>
            <View style={styles.halves}>
              {[i * 2 - 1, i * 2].map((half) => (
                <Pressable
                  key={half}
                  style={styles.half}
                  hitSlop={4}
                  accessibilityLabel={`${half / 2} звезди`}
                  onPress={() => onChange(value === half ? null : half)}
                />
              ))}
            </View>
          </View>
        </View>
      ))}
    </View>
  );
}

function iconFor(halfStars: number, star: number): 'star' | 'star-half-full' | 'star-outline' {
  if (halfStars >= star * 2) return 'star';
  if (halfStars === star * 2 - 1) return 'star-half-full';
  return 'star-outline';
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center' },
  halves: { flex: 1, flexDirection: 'row' },
  half: { flex: 1 },
});
