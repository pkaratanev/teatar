import { type ClassValue, clsx } from 'clsx';
import { extendTailwindMerge } from 'tailwind-merge';

// Teach tailwind-merge our font families so `font-body font-display` keeps only the last one.
const twMerge = extendTailwindMerge({
  extend: {
    classGroups: {
      'font-family': [{ font: ['body', 'body-medium', 'body-semibold', 'body-bold', 'display'] }],
    },
  },
});

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}
