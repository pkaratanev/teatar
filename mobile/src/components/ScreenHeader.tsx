import type { ReactNode } from 'react';
import { View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Text } from '@/src/ui';

/** Large serif title at the top of a tab, below the status bar. */
export function ScreenHeader({ title, right, children }: { title: string; right?: ReactNode; children?: ReactNode }) {
  const insets = useSafeAreaInsets();

  return (
    <View className="bg-background px-4 pb-3" style={{ paddingTop: insets.top + 12 }}>
      <View className="flex-row items-center justify-between">
        <Text variant="display">{title}</Text>
        {right}
      </View>
      {children}
    </View>
  );
}
