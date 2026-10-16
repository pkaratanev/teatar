import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useEffect } from 'react';
import { useColorScheme } from 'react-native';
import { PaperProvider } from 'react-native-paper';

import { ApiError } from '@/src/api/client';
import { AuthProvider } from '@/src/auth';
import { listenForNotificationTaps } from '@/src/push';
import { darkTheme, lightTheme } from '@/src/theme';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 5 * 60_000,
      // A 404 won't fix itself; anything else gets one more try.
      retry: (failures, error) => !(error instanceof ApiError && error.status === 404) && failures < 1,
    },
  },
});

// A deep link opens a single screen; this puts the tabs underneath so Back has somewhere to go.
export const unstable_settings = { initialRouteName: '(tabs)' };

export default function RootLayout() {
  const scheme = useColorScheme();
  useEffect(() => listenForNotificationTaps(), []);

  return (
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
      <PaperProvider theme={scheme === 'dark' ? darkTheme : lightTheme}>
        <StatusBar style="auto" />
        <Stack screenOptions={{ headerBackButtonDisplayMode: 'minimal' }}>
          <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
          <Stack.Screen name="production/[id]" options={{ title: '' }} />
          <Stack.Screen name="person/[id]" options={{ title: '' }} />
          <Stack.Screen name="theatre/[id]" options={{ title: '' }} />
          <Stack.Screen name="sign-in" options={{ title: 'Вход', presentation: 'modal' }} />
          <Stack.Screen name="user/[username]" options={{ title: '' }} />
          <Stack.Screen name="edit-profile" options={{ title: 'Редактиране на профила', presentation: 'modal' }} />
          <Stack.Screen name="+not-found" options={{ title: 'Няма такава страница' }} />
        </Stack>
      </PaperProvider>
      </AuthProvider>
    </QueryClientProvider>
  );
}
