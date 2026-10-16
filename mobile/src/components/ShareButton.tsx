import { Share } from 'react-native';
import { IconButton } from 'react-native-paper';

/** Header button that opens the system share sheet with a line of text and an app link. */
export function ShareButton({ text, url }: { text: string; url: string }) {
  return (
    <IconButton
      icon="share-variant"
      accessibilityLabel="Сподели"
      onPress={() => Share.share({ message: `${text}\n${url}` }).catch(() => undefined)}
    />
  );
}
