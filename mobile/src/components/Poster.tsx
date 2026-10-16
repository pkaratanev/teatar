import { Image } from 'expo-image';
import { Drama } from 'lucide-react-native';
import { View } from 'react-native';

import { colors } from '@/src/ui';

/** A production poster in 2:3, with a quiet placeholder when there is none. */
export function Poster({ uri, width, radius = 8 }: { uri: string | null | undefined; width: number; radius?: number }) {
  const style = { width, height: Math.round(width * 1.5), borderRadius: radius };

  return (
    <View style={[style, { backgroundColor: colors.elevated, borderColor: colors.border, borderWidth: 1 }]} className="items-center justify-center overflow-hidden">
      {uri ? (
        <Image source={uri} style={[style, { position: 'absolute' }]} contentFit="cover" transition={150} />
      ) : (
        <Drama size={width * 0.35} color={colors.faint} />
      )}
    </View>
  );
}
