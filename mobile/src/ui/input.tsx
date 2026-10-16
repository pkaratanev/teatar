import { forwardRef } from 'react';
import { TextInput, type TextInputProps, View } from 'react-native';

import { colors } from './colors';
import { cn } from './cn';
import { Text } from './text';

export const Input = forwardRef<TextInput, TextInputProps & { className?: string; invalid?: boolean }>(
  ({ className, invalid, multiline, ...props }, ref) => (
    <TextInput
      ref={ref}
      multiline={multiline}
      placeholderTextColor={colors.faint}
      selectionColor={colors.primary}
      className={cn(
        'rounded-xl border border-border bg-card px-4 font-body text-base text-foreground focus:border-muted',
        multiline ? 'min-h-[120px] py-3' : 'h-12',
        invalid && 'border-destructive',
        className,
      )}
      style={multiline ? { textAlignVertical: 'top' } : undefined}
      {...props}
    />
  ),
);
Input.displayName = 'Input';

/** Label, input and a line underneath for an error or a hint. */
export function Field({
  label,
  error,
  hint,
  className,
  ...props
}: TextInputProps & { label: string; error?: string; hint?: string; className?: string }) {
  return (
    <View className={cn('gap-1.5', className)}>
      <Text variant="small" className="font-body-medium text-muted">
        {label}
      </Text>
      <Input invalid={!!error} accessibilityLabel={label} {...props} />
      {(error || hint) && (
        <Text variant="caption" className={error ? 'text-destructive' : undefined}>
          {error ?? hint}
        </Text>
      )}
    </View>
  );
}
