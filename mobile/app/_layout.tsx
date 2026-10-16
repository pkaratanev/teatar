import '../global.css';

import { Inter_400Regular, Inter_500Medium, Inter_600SemiBold, Inter_700Bold } from '@expo-google-fonts/inter';
import { PlayfairDisplay_700Bold } from '@expo-google-fonts/playfair-display';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { useFonts } from 'expo-font';
import { Stack } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { StatusBar } from 'expo-status-bar';
import { useEffect } from 'react';
import { View } from 'react-native';

import { ApiError } from '@/src/api/client';
import { AuthProvider } from '@/src/auth';
import { CityProvider } from '@/src/city';
import { listenForNotificationTaps } from '@/src/push';
import { colors } from '@/src/ui';

SplashScreen.preventAutoHideAsync().catch(() => undefined);

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
  const [fontsLoaded, fontError] = useFonts({
    Inter_400Regular,
    Inter_500Medium,
    Inter_600SemiBold,
    Inter_700Bold,
    PlayfairDisplay_700Bold,
  });
  const ready = fontsLoaded || !!fontError;

  useEffect(() => listenForNotificationTaps(), []);
  useEffect(() => {
    if (ready) SplashScreen.hideAsync().catch(() => undefined);
  }, [ready]);

  if (!ready) return <View style={{ flex: 1, backgroundColor: colors.background }} />;

  return (
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
        <CityProvider>
          <StatusBar style="light" />
          <Stack
            screenOptions={{
              headerBackButtonDisplayMode: 'minimal',
              headerStyle: { backgroundColor: colors.background },
              headerTintColor: colors.foreground,
              headerTitleStyle: { fontFamily: 'Inter_600SemiBold', fontSize: 16 },
              headerShadowVisible: false,
              contentStyle: { backgroundColor: colors.background },
            }}
          >
            <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
            <Stack.Screen name="production/[id]" options={{ title: '' }} />
            <Stack.Screen name="person/[id]" options={{ title: '' }} />
            <Stack.Screen name="theatre/[id]" options={{ title: '' }} />
            <Stack.Screen name="sign-in" options={{ title: '', presentation: 'modal' }} />
            <Stack.Screen name="user/[username]" options={{ title: '' }} />
            <Stack.Screen name="edit-profile" options={{ title: 'Редактиране на профила', presentation: 'modal' }} />
            <Stack.Screen name="+not-found" options={{ title: 'Няма такава страница' }} />
          </Stack>
        </CityProvider>
      </AuthProvider>
    </QueryClientProvider>
  );
}
