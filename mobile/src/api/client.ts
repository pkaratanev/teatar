import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

const ENV_URL = process.env.EXPO_PUBLIC_API_URL ?? 'http://localhost:8000/api/v1';
// The web build opened on the PC itself (http://localhost:8081) talks to the API on the same PC, not the LAN address
// the phone needs: browsers treat LAN addresses as private and may block or prompt for every request.
const BASE_URL =
  Platform.OS === 'web' && globalThis.location?.hostname === 'localhost'
    ? ENV_URL.replace(/^(https?:\/\/)[^/:]+/, '$1localhost')
    : ENV_URL;
const TOKEN_KEY = 'teatar.token';

export class ApiError extends Error {
  constructor(
    public status: number,
    public body: unknown,
  ) {
    super(`API ${status}`);
  }
}

// SecureStore has no web version; the web build (used for screenshots in CI) keeps the token in localStorage.
const web = Platform.OS === 'web';

function local<T>(fn: (s: Storage) => T): T | null {
  try {
    return fn(globalThis.localStorage);
  } catch {
    return null;
  }
}

/** A small string saved on the device: SecureStore on phones, localStorage on the web build. */
export function deviceValue(key: string) {
  return {
    get: async () => (web ? local((s) => s.getItem(key)) : SecureStore.getItemAsync(key)),
    set: async (value: string) => {
      if (web) local((s) => s.setItem(key, value));
      else await SecureStore.setItemAsync(key, value);
    },
    clear: async () => {
      if (web) local((s) => s.removeItem(key));
      else await SecureStore.deleteItemAsync(key);
    },
  };
}

export const tokenStore = deviceValue(TOKEN_KEY);

export async function api<T>(path: string, init: RequestInit = {}): Promise<T> {
  const token = await tokenStore.get();

  const res = await fetch(`${BASE_URL}${path}`, {
    ...init,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...init.headers,
    },
  });

  if (res.status === 204) {
    return undefined as T;
  }

  const body = await res.json().catch(() => null);
  if (!res.ok) {
    throw new ApiError(res.status, body);
  }

  return body as T;
}
