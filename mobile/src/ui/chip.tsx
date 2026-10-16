import { Pressable } from 'react-native';

import { cn } from './cn';
import { Text } from './text';

export function Chip({ label, selected, onPress }: { label: string; selected?: boolean; onPress?: () => void }) {
  return (
    <Pressable
      role="button"
      aria-selected={selected}
      onPress={onPress}
      className={cn(
        'h-8 justify-center rounded-full border px-3.5 active:opacity-75',
        selected ? 'border-foreground bg-foreground' : 'border-border',
      )}
    >
      <Text variant="small" className={cn('font-body-medium', selected ? 'text-background' : 'text-foreground')}>
        {label}
      </Text>
    </Pressable>
  );
}
