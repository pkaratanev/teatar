import { Image } from 'expo-image';
import { View } from 'react-native';

import { colors, Text } from '@/src/ui';

// Deep "stage light" colours for productions without a poster, picked from the title.
const TINTS = ['#4A1E27', '#2A2E4A', '#1E3A35', '#4A391E', '#3A2247', '#21323F', '#40291F', '#2E3B22'];

/**
 * A production poster in 2:3. Most theatres don't publish posters we can use yet, so without one
 * this draws a typographic poster: the title (or its first letter, when small) on a colour picked
 * from the title, so rows don't all look the same.
 */
export function Poster({
  uri,
  title,
  width,
  radius = 8,
}: {
  uri: string | null | undefined;
  title: string;
  width: number;
  radius?: number;
}) {
  const height = Math.round(width * 1.5);
  const style = { width, height, borderRadius: radius };

  if (uri) {
    return (
      <View style={[style, { backgroundColor: colors.elevated }]} className="overflow-hidden">
        <Image source={uri} style={style} contentFit="cover" transition={150} />
      </View>
    );
  }

  const tint = TINTS[hash(title) % TINTS.length];
  const small = width < 80;
  const letter = title.replace(/^[^\p{L}\p{N}]+/u, '').charAt(0).toUpperCase();

  return (
    <View
      style={[style, { backgroundColor: tint, borderColor: 'rgba(255,255,255,0.08)', borderWidth: 1 }]}
      className="overflow-hidden"
      accessibilityLabel={title}
    >
      {/* A soft spotlight from the top. */}
      <View
        style={{ position: 'absolute', top: -height * 0.35, left: -width * 0.25, width: width * 1.5, height: height * 0.8, borderRadius: width }}
        className="bg-white/[0.06]"
      />
      {small ? (
        <View className="flex-1 items-center justify-center">
          <Text className="font-display text-foreground/90" style={{ fontSize: width * 0.5, lineHeight: width * 0.62 }}>
            {letter}
          </Text>
        </View>
      ) : (
        <View className="flex-1 justify-end" style={{ padding: width * 0.09 }}>
          <View className="mb-2 h-[2px] w-6 bg-gold/70" />
          <Text
            className="font-display text-foreground"
            style={{ fontSize: width * 0.12, lineHeight: width * 0.145 }}
            numberOfLines={5}
          >
            {title}
          </Text>
        </View>
      )}
    </View>
  );
}

function hash(s: string): number {
  let h = 0;
  for (const ch of s) h = (h * 31 + ch.charCodeAt(0)) >>> 0;
  return h;
}
