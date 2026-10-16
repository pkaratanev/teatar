import { cva, type VariantProps } from 'class-variance-authority';
import type { LucideIcon } from 'lucide-react-native';
import type { ReactNode } from 'react';
import { ActivityIndicator, Pressable, type PressableProps } from 'react-native';

import { colors } from './colors';
import { cn } from './cn';
import { Text, TextClassContext } from './text';

const buttonVariants = cva('flex-row items-center justify-center gap-2 rounded-full active:opacity-75', {
  variants: {
    variant: {
      primary: 'bg-primary',
      secondary: 'bg-elevated',
      outline: 'border border-border',
      ghost: '',
      destructive: 'border border-destructive/40',
    },
    size: {
      default: 'h-11 px-5',
      sm: 'h-9 px-4',
      icon: 'h-10 w-10',
    },
  },
  defaultVariants: { variant: 'primary', size: 'default' },
});

const textClass = {
  primary: 'text-primary-foreground',
  secondary: 'text-foreground',
  outline: 'text-foreground',
  ghost: 'text-foreground',
  destructive: 'text-destructive',
} as const;

const iconColor = {
  primary: colors['primary-foreground'],
  secondary: colors.foreground,
  outline: colors.foreground,
  ghost: colors.foreground,
  destructive: colors.destructive,
} as const;

type Variant = keyof typeof textClass;

export type ButtonProps = Omit<PressableProps, 'children'> &
  VariantProps<typeof buttonVariants> & {
    className?: string;
    children?: ReactNode;
    icon?: LucideIcon;
    /** Fill the icon (e.g. a filled bookmark when the item is saved). */
    iconFilled?: boolean;
    iconColor?: string;
    loading?: boolean;
  };

export function Button({
  className,
  variant,
  size,
  children,
  icon: Icon,
  iconFilled,
  iconColor: iconColorOverride,
  loading,
  disabled,
  ...props
}: ButtonProps) {
  const v: Variant = variant ?? 'primary';
  const color = iconColorOverride ?? iconColor[v];
  const iconSize = size === 'sm' ? 16 : 18;

  return (
    <TextClassContext.Provider value={cn('font-body-semibold', size === 'sm' ? 'text-[13px]' : 'text-[15px]', textClass[v])}>
      <Pressable
        role="button"
        disabled={disabled || loading}
        className={cn(buttonVariants({ variant, size }), (disabled || loading) && 'opacity-50', className)}
        {...props}
      >
        {loading ? (
          <ActivityIndicator size="small" color={color} />
        ) : (
          Icon && <Icon size={iconSize} color={color} fill={iconFilled ? color : 'transparent'} />
        )}
        {typeof children === 'string' ? <Text>{children}</Text> : children}
      </Pressable>
    </TextClassContext.Provider>
  );
}
