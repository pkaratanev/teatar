import { Share2 } from 'lucide-react-native';
import { Pressable, Share } from 'react-native';

import { colors } from '@/src/ui';

/** Header button that opens the system share sheet with a line of text and an app link. */
export function ShareButton({ text, url }: { text: string; url: string }) {
  return (
    <Pressable
      role="button"
      accessibilityLabel="Сподели"
      hitSlop={10}
      className="h-9 w-9 items-center justify-center rounded-full active:bg-card"
      onPress={() => Share.share({ message: `${text}\n${url}` }).catch(() => undefined)}
    >
      <Share2 size={20} color={colors.foreground} />
    </Pressable>
  );
}
