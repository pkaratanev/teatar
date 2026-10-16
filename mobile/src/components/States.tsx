import { router } from 'expo-router';
import { StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Text } from 'react-native-paper';

import { ApiError } from '@/src/api/client';

export function Loading() {
  return (
    <View style={styles.center}>
      <ActivityIndicator />
    </View>
  );
}

export function ErrorState({ onRetry, error }: { onRetry?: () => void; error?: unknown }) {
  // A deep link or old notification can point at something that no longer exists.
  if (error instanceof ApiError && error.status === 404) return <NotFound />;

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

export function NotFound() {
  return (
    <View style={styles.center}>
      <Text variant="bodyLarge" style={styles.muted}>
        Не намерихме тази страница. Може да е премахната.
      </Text>
      <Button onPress={() => router.replace('/')} style={styles.gap}>
        Към програмата
      </Button>
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
