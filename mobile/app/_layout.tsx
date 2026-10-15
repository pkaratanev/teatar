import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useColorScheme } from 'react-native';
import { PaperProvider } from 'react-native-paper';

import { darkTheme, lightTheme } from '@/src/theme';

const queryClient = new QueryClient({
  defaultOptions: { queries: { staleTime: 5 * 60_000, retry: 1 } },
});

export default function RootLayout() {
  const scheme = useColorScheme();

  return (
    <QueryClientProvider client={queryClient}>
      <PaperProvider theme={scheme === 'dark' ? darkTheme : lightTheme}>
        <StatusBar style="auto" />
        <Stack screenOptions={{ headerBackButtonDisplayMode: 'minimal' }}>
          <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
          <Stack.Screen name="production/[id]" options={{ title: '' }} />
          <Stack.Screen name="person/[id]" options={{ title: '' }} />
          <Stack.Screen name="theatre/[id]" options={{ title: '' }} />
        </Stack>
      </PaperProvider>
    </QueryClientProvider>
  );
}
