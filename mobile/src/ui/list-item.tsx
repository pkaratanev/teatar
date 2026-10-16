import { ChevronRight, type LucideIcon } from 'lucide-react-native';
import type { ReactNode } from 'react';
import { Pressable, type PressableProps, View } from 'react-native';

import { colors } from './colors';
import { cn } from './cn';
import { Text } from './text';

/** A tappable row: something on the left, a title and description, a chevron. */
export function ListItem({
  title,
  description,
  icon: Icon,
  left,
  right,
  className,
  ...props
}: PressableProps & {
  title: string;
  description?: string | null;
  icon?: LucideIcon;
  left?: ReactNode;
  right?: ReactNode;
  className?: string;
}) {
  return (
    <Pressable className={cn('flex-row items-center gap-3 px-4 py-3 active:bg-card', className)} {...props}>
      {left ??
        (Icon && (
          <View className="h-10 w-10 items-center justify-center rounded-full bg-card">
            <Icon size={18} color={colors.muted} />
          </View>
        ))}
      <View className="flex-1">
        <Text className="font-body-medium" numberOfLines={1}>
          {title}
        </Text>
        {description ? (
          <Text variant="muted" numberOfLines={1}>
            {description}
          </Text>
        ) : null}
      </View>
      {right ?? (props.onPress || props.role === 'link' ? <ChevronRight size={18} color={colors.faint} /> : null)}
    </Pressable>
  );
}
