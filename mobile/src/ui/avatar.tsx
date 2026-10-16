import { Image } from 'expo-image';
import { View } from 'react-native';

import { cn } from './cn';
import { Text } from './text';

/** A photo, or the person's initials on a colour picked from their name. */
export function Avatar({ name, uri, size = 40, className }: { name: string; uri?: string | null; size?: number; className?: string }) {
  const style = { width: size, height: size, borderRadius: size / 2 };

  if (uri) return <Image source={uri} style={style} contentFit="cover" />;

  return (
    <View style={[style, { backgroundColor: tint(name) }]} className={cn('items-center justify-center', className)}>
      <Text className="font-body-semibold text-foreground" style={{ fontSize: size * 0.38 }}>
        {initials(name)}
      </Text>
    </View>
  );
}

function initials(name: string): string {
  const parts = name.trim().split(/\s+/);
  return ((parts[0]?.[0] ?? '') + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
}

const TINTS = ['#5B2A33', '#3D3352', '#2E4A44', '#4F3B26', '#2F3F55', '#503046'];

function tint(name: string): string {
  let h = 0;
  for (const ch of name) h = (h * 31 + ch.charCodeAt(0)) >>> 0;
  return TINTS[h % TINTS.length];
}
