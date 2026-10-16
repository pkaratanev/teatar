import Constants from 'expo-constants';
import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';
import { router } from 'expo-router';
import { Platform } from 'react-native';

import { api } from './api/client';

Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowBanner: true,
    shouldShowList: true,
    shouldPlaySound: false,
    shouldSetBadge: false,
  }),
});

/**
 * Ask for permission (once) and send the Expo push token to the API.
 * Called after the first "Искам да гледам", so the prompt has a reason.
 * Push needs a real device and a development build; in Expo Go or a
 * simulator this quietly does nothing.
 */
export async function registerForPush(): Promise<void> {
  try {
    if (!Device.isDevice) return;

    if (Platform.OS === 'android') {
      await Notifications.setNotificationChannelAsync('reminders', {
        name: 'Напомняния',
        importance: Notifications.AndroidImportance.DEFAULT,
      });
    }

    let { status } = await Notifications.getPermissionsAsync();
    if (status !== 'granted') {
      ({ status } = await Notifications.requestPermissionsAsync());
    }
    if (status !== 'granted') return;

    const projectId = Constants.expoConfig?.extra?.eas?.projectId as string | undefined;
    if (!projectId) return; // set by `eas init`; until then there is no push token

    const { data: token } = await Notifications.getExpoPushTokenAsync({ projectId });
    await api('/me/push-token', { method: 'PUT', body: JSON.stringify({ token }) });
  } catch {
    // Push is a nice-to-have; never break the action that triggered it.
  }
}

/**
 * Tapping a reminder opens the production, whether the app was running or
 * was started by the tap. Returns an unsubscribe function.
 */
export function listenForNotificationTaps(): () => void {
  let handled: string | null = null;

  const open = (response: Notifications.NotificationResponse | null) => {
    if (!response || response.notification.request.identifier === handled) return;
    handled = response.notification.request.identifier;
    const id = response.notification.request.content.data?.productionId;
    if (typeof id === 'number' || typeof id === 'string') {
      router.push({ pathname: '/production/[id]', params: { id: String(id) } });
    }
  };

  // App started from the notification: the listener below was not yet registered when it was tapped.
  Notifications.getLastNotificationResponseAsync()
    .then(open)
    .catch(() => undefined);

  const sub = Notifications.addNotificationResponseReceivedListener(open);

  return () => sub.remove();
}
