import { Pressable, View } from 'react-native';

import { cn } from './cn';
import { Text } from './text';

/** A pill-shaped switch between a few options. */
export function Segmented<T extends string>({
  value,
  onChange,
  options,
  className,
}: {
  value: T;
  onChange: (value: T) => void;
  options: { value: T; label: string }[];
  className?: string;
}) {
  return (
    <View role="tablist" className={cn('flex-row rounded-full bg-card p-1', className)}>
      {options.map((o) => {
        const active = o.value === value;
        return (
          <Pressable
            key={o.value}
            role="tab"
            aria-selected={active}
            onPress={() => onChange(o.value)}
            className={cn('h-9 flex-1 items-center justify-center rounded-full', active && 'bg-elevated')}
          >
            <Text variant="small" className={cn('font-body-semibold', active ? 'text-foreground' : 'text-muted')}>
              {o.label}
            </Text>
          </Pressable>
        );
      })}
    </View>
  );
}
