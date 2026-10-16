import type { ReactNode } from 'react';
import { View } from 'react-native';

import { cn } from './cn';
import { Text } from './text';

/** A titled block on a screen: "АКТЬОРИ", "РЕВЮТА (3)". */
export function Section({
  title,
  action,
  children,
  className,
}: {
  title?: string;
  action?: ReactNode;
  children: ReactNode;
  className?: string;
}) {
  return (
    <View className={cn('pt-6', className)}>
      {title && (
        <View className="mb-2 flex-row items-center justify-between px-4">
          <Text variant="overline">{title}</Text>
          {action}
        </View>
      )}
      {children}
    </View>
  );
}

export function Separator({ className }: { className?: string }) {
  return <View className={cn('h-px bg-border', className)} />;
}
