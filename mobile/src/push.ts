import Constants, { ExecutionEnvironment } from 'expo-constants';
import * as Device from 'expo-device';
import { router } from 'expo-router';
import { Platform } from 'react-native';

import { api } from './api/client';

type NotificationsModule = typeof import('expo-notifications');
type NotificationResponse = import('expo-notifications').NotificationResponse;

/**
 * Expo Go can't do push, and on Android just importing expo-notifications
 * there throws ("Cannot find native module ExpoTopicSubscriptionModule"),
 * which took every screen down. So the module is only loaded, lazily, in a
 * development or store build (never on web).
 */
const pushAvailable = Platform.OS !== 'web' && Constants.executionEnvironment !== ExecutionEnvironment.StoreClient;

let loaded: Promise<NotificationsModule | null> | null = null;

function notifications(): Promise<NotificationsModule | null> {
  if (!pushAvailable) return Promise.resolve(null);
  loaded ??= import('expo-notifications')
    .then((Notifications) => {
      Notifications.setNotificationHandler({
        handleNotification: async () => ({
          shouldShowBanner: true,
          shouldShowList: true,
          shouldPlaySound: false,
          shouldSetBadge: false,
        }),
      });
      return Notifications;
    })
    .catch(() => null);
  return loaded;
}

/**
 * Ask for permission (once) and send the Expo push token to the API.
 * Called after the first "Искам да гледам", so the prompt has a reason.
 * Push needs a real device and a development build; in Expo Go or a
 * simulator this quietly does nothing.
 */
export async function registerForPush(): Promise<void> {
  try {
    const Notifications = await notifications();
    if (!Notifications || !Device.isDevice) return;

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
  let unsubscribe: (() => void) | null = null;
  let stopped = false;

  const open = (response: NotificationResponse | null) => {
    if (!response || response.notification.request.identifier === handled) return;
    handled = response.notification.request.identifier;
    const id = response.notification.request.content.data?.productionId;
    if (typeof id === 'number' || typeof id === 'string') {
      router.push({ pathname: '/production/[id]', params: { id: String(id) } });
    }
  };

  notifications()
    .then((Notifications) => {
      if (!Notifications || stopped) return;
      const sub = Notifications.addNotificationResponseReceivedListener(open);
      unsubscribe = () => sub.remove();
      // App started from the notification: the listener wasn't registered yet when it was tapped.
      return Notifications.getLastNotificationResponseAsync().then(open);
    })
    .catch(() => undefined);

  return () => {
    stopped = true;
    unsubscribe?.();
  };
}
