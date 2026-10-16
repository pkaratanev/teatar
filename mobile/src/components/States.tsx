import { router } from 'expo-router';
import { CloudOff, SearchX } from 'lucide-react-native';
import type { LucideIcon } from 'lucide-react-native';
import type { ReactNode } from 'react';
import { ActivityIndicator, View } from 'react-native';

import { ApiError } from '@/src/api/client';
import { Button, colors, Text } from '@/src/ui';

export function Loading() {
  return (
    <View className="flex-1 items-center justify-center bg-background">
      <ActivityIndicator color={colors.primary} />
    </View>
  );
}

function Message({ icon: Icon, text, children }: { icon?: LucideIcon; text: string; children?: ReactNode }) {
  return (
    <View className="flex-1 items-center justify-center gap-4 bg-background p-8">
      {Icon && <Icon size={36} color={colors.faint} strokeWidth={1.5} />}
      <Text className="text-center text-muted">{text}</Text>
      {children}
    </View>
  );
}

export function ErrorState({ onRetry, error }: { onRetry?: () => void; error?: unknown }) {
  // A deep link or old notification can point at something that no longer exists.
  if (error instanceof ApiError && error.status === 404) return <NotFound />;

  return (
    <Message icon={CloudOff} text="Нещо се обърка при зареждането.">
      {onRetry && (
        <Button variant="secondary" size="sm" onPress={onRetry}>
          Опитай пак
        </Button>
      )}
    </Message>
  );
}

export function NotFound() {
  return (
    <Message icon={SearchX} text="Не намерихме тази страница. Може да е премахната.">
      <Button variant="secondary" size="sm" onPress={() => router.replace('/')}>
        Към програмата
      </Button>
    </Message>
  );
}

export function Empty({ message, icon }: { message: string; icon?: LucideIcon }) {
  return <Message icon={icon} text={message} />;
}
