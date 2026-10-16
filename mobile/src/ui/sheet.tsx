import type { ReactNode } from 'react';
import { KeyboardAvoidingView, Modal, Platform, Pressable, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { cn } from './cn';
import { Text } from './text';

/** A panel that slides up from the bottom over a dimmed screen. Tap outside to close. */
export function Sheet({
  visible,
  onClose,
  title,
  children,
}: {
  visible: boolean;
  onClose: () => void;
  title?: string;
  children: ReactNode;
}) {
  const insets = useSafeAreaInsets();

  return (
    <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose} statusBarTranslucent>
      <KeyboardAvoidingView className="flex-1" behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <Pressable className="flex-1 bg-black/60" onPress={onClose} accessibilityLabel="Затвори" />
        <View
          className="max-h-[90%] rounded-t-3xl border-t border-border bg-card px-5 pt-3"
          style={{ paddingBottom: Math.max(insets.bottom, 16) }}
        >
          <View className="mb-3 h-1 w-10 self-center rounded-full bg-border" />
          {title && (
            <Text variant="title" className="mb-2" numberOfLines={2}>
              {title}
            </Text>
          )}
          {children}
        </View>
      </KeyboardAvoidingView>
    </Modal>
  );
}

/** A centred confirmation box. */
export function Dialog({
  visible,
  onClose,
  title,
  children,
  actions,
}: {
  visible: boolean;
  onClose: () => void;
  title: string;
  children: ReactNode;
  actions: ReactNode;
}) {
  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose} statusBarTranslucent>
      <Pressable className="flex-1 items-center justify-center bg-black/60 p-6" onPress={onClose}>
        <Pressable className={cn('w-full max-w-sm gap-3 rounded-3xl border border-border bg-card p-6')} onPress={() => undefined}>
          <Text variant="heading">{title}</Text>
          {children}
          <View className="mt-2 flex-row justify-end gap-2">{actions}</View>
        </Pressable>
      </Pressable>
    </Modal>
  );
}
