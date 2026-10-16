const { colors } = require('./src/ui/colors');

/** Design tokens. Colours live in src/ui/colors.js so icons and navigation can use them too. */
/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./app/**/*.{ts,tsx}', './src/**/*.{ts,tsx}'],
  presets: [require('nativewind/preset')],
  theme: {
    extend: {
      colors,
      fontFamily: {
        // React Native needs one family per weight; never combine these with font-bold etc.
        body: ['Inter_400Regular'],
        'body-medium': ['Inter_500Medium'],
        'body-semibold': ['Inter_600SemiBold'],
        'body-bold': ['Inter_700Bold'],
        display: ['PlayfairDisplay_700Bold'],
      },
    },
  },
  plugins: [],
};
