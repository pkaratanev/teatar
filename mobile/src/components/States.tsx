import { StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Text } from 'react-native-paper';

export function Loading() {
  return (
    <View style={styles.center}>
      <ActivityIndicator />
    </View>
  );
}

export function ErrorState({ onRetry }: { onRetry?: () => void }) {
  return (
    <View style={styles.center}>
      <Text variant="bodyLarge">Нещо се обърка при зареждането.</Text>
      {onRetry && (
        <Button onPress={onRetry} style={styles.gap}>
          Опитай пак
        </Button>
      )}
    </View>
  );
}

export function Empty({ message }: { message: string }) {
  return (
    <View style={styles.center}>
      <Text variant="bodyLarge" style={styles.muted}>
        {message}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: 24 },
  gap: { marginTop: 12 },
  muted: { opacity: 0.6, textAlign: 'center' },
});
