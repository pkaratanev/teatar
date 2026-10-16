import { router } from 'expo-router';
import { Drama } from 'lucide-react-native';
import { View } from 'react-native';

import { Button, colors, Text } from '@/src/ui';

export function SignInPrompt({ text }: { text: string }) {
  return (
    <View className="flex-1 items-center justify-center gap-5 bg-background p-8">
      <View className="h-16 w-16 items-center justify-center rounded-full bg-card">
        <Drama size={30} color={colors.primary} />
      </View>
      <Text className="text-center text-muted">{text}</Text>
      <Button onPress={() => router.push('/sign-in')}>Вход или регистрация</Button>
    </View>
  );
}
