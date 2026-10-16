import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

const BASE_URL = process.env.EXPO_PUBLIC_API_URL ?? 'http://localhost:8000/api/v1';
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

export const tokenStore = {
  get: async () => (web ? local((s) => s.getItem(TOKEN_KEY)) : SecureStore.getItemAsync(TOKEN_KEY)),
  set: async (token: string) => {
    if (web) local((s) => s.setItem(TOKEN_KEY, token));
    else await SecureStore.setItemAsync(TOKEN_KEY, token);
  },
  clear: async () => {
    if (web) local((s) => s.removeItem(TOKEN_KEY));
    else await SecureStore.deleteItemAsync(TOKEN_KEY);
  },
};

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
