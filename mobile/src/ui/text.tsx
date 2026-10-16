import { cva, type VariantProps } from 'class-variance-authority';
import { createContext, useContext } from 'react';
import { Text as RNText, type TextProps } from 'react-native';

import { cn } from './cn';

/** Lets a parent (e.g. Button) set the text style of the Text inside it. */
export const TextClassContext = createContext<string | undefined>(undefined);

const textVariants = cva('font-body text-base text-foreground', {
  variants: {
    variant: {
      default: '',
      display: 'font-display text-[30px] leading-[36px]',
      title: 'font-display text-2xl leading-[30px]',
      heading: 'font-body-semibold text-lg leading-6',
      strong: 'font-body-semibold',
      small: 'text-sm leading-5',
      muted: 'text-sm leading-5 text-muted',
      caption: 'text-xs text-muted',
      overline: 'font-body-semibold text-[11px] uppercase tracking-[1.5px] text-muted',
    },
  },
  defaultVariants: { variant: 'default' },
});

export type TextVariant = NonNullable<VariantProps<typeof textVariants>['variant']>;

export function Text({ className, variant, ...props }: TextProps & VariantProps<typeof textVariants> & { className?: string }) {
  const inherited = useContext(TextClassContext);
  return <RNText className={cn(textVariants({ variant }), inherited, className)} {...props} />;
}
