import { Star, StarHalf } from 'lucide-react-native';
import type { ReactNode } from 'react';
import { Pressable, View } from 'react-native';

import { colors, Text } from '@/src/ui';

/** Read-only stars for a value in half stars (1–10). Empty stars are left out, like Letterboxd. */
export function Stars({ halfStars, size = 14 }: { halfStars: number | null | undefined; size?: number }) {
  if (!halfStars) return null;
  const full = Math.floor(halfStars / 2);

  return (
    <View className="flex-row items-center" accessibilityLabel={`${halfStars / 2} от 5 звезди`}>
      {Array.from({ length: full }, (_, i) => (
        <Star key={i} size={size} color={colors.gold} fill={colors.gold} />
      ))}
      {halfStars % 2 === 1 && <StarHalf size={size} color={colors.gold} fill={colors.gold} />}
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
  const v = value ?? 0;

  return (
    <View className="flex-row items-center gap-1" accessibilityRole="adjustable" accessibilityLabel={`Оценка ${v / 2} от 5`}>
      {[1, 2, 3, 4, 5].map((i) => (
        <View key={i} style={{ width: size, height: size }}>
          <Star size={size} color={colors.border} fill={colors.elevated} strokeWidth={1.5} />
          {v >= i * 2 - 1 && (
            <View className="absolute inset-0">
              {v >= i * 2 ? (
                <Star size={size} color={colors.gold} fill={colors.gold} strokeWidth={1.5} />
              ) : (
                <StarHalf size={size} color={colors.gold} fill={colors.gold} strokeWidth={1.5} />
              )}
            </View>
          )}
          <View className="absolute inset-0 flex-row">
            {[i * 2 - 1, i * 2].map((half) => (
              <Pressable
                key={half}
                className="flex-1"
                hitSlop={4}
                accessibilityLabel={`${half / 2} звезди`}
                onPress={() => onChange(value === half ? null : half)}
              />
            ))}
          </View>
        </View>
      ))}
    </View>
  );
}

/** "★ 4.2 · 18" in the app's gold. */
export function RatingBadge({ average, count, size = 13 }: { average: number | null; count: number; size?: number }) {
  if (average === null) return null;
  return (
    <View className="flex-row items-center gap-1">
      <Star size={size} color={colors.gold} fill={colors.gold} />
      <RatingText>{average.toFixed(1).replace('.', ',')}</RatingText>
      <RatingText muted>· {count}</RatingText>
    </View>
  );
}

function RatingText({ children, muted }: { children: ReactNode; muted?: boolean }) {
  return (
    <Text variant="small" className={muted ? 'text-muted' : 'font-body-semibold text-gold'}>
      {children}
    </Text>
  );
}
