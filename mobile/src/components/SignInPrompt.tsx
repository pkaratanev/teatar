import { router } from 'expo-router';
import { StyleSheet, View } from 'react-native';
import { Button, Text } from 'react-native-paper';

export function SignInPrompt({ text }: { text: string }) {
  return (
    <View style={styles.prompt}>
      <Text variant="bodyLarge" style={styles.text}>
        {text}
      </Text>
      <Button mode="contained" onPress={() => router.push('/sign-in')}>
        Вход или регистрация
      </Button>
    </View>
  );
}

const styles = StyleSheet.create({
  prompt: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: 24, gap: 16 },
  text: { textAlign: 'center' },
});
