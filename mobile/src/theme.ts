import { MD3DarkTheme, MD3LightTheme } from 'react-native-paper';

// Placeholder palette: a theatre-curtain red. Swap once there is a brand.
const curtain = '#7A1F2B';

export const lightTheme = {
  ...MD3LightTheme,
  colors: { ...MD3LightTheme.colors, primary: curtain, secondaryContainer: '#F6DADD' },
};

export const darkTheme = {
  ...MD3DarkTheme,
  colors: { ...MD3DarkTheme.colors, primary: '#F2A7B0' },
};
