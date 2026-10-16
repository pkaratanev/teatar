import { useQueryClient } from '@tanstack/react-query';
import { createContext, type ReactNode, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { Platform } from 'react-native';

import { api, tokenStore } from './api/client';
import type { User } from './api/types';

type Credentials = { email: string; password: string };
type Registration = Credentials & { name: string; username: string };

type AuthState = {
  /** undefined while the stored token is being checked on start-up */
  user: User | null | undefined;
  signIn: (c: Credentials) => Promise<void>;
  signUp: (r: Registration) => Promise<void>;
  signOut: () => Promise<void>;
  deleteAccount: () => Promise<void>;
  updateProfile: (changes: ProfileChanges) => Promise<void>;
};

type ProfileChanges = { name?: string; bio?: string | null };

const AuthContext = createContext<AuthState | null>(null);

const deviceName = `${Platform.OS} app`;

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null | undefined>(undefined);
  const queryClient = useQueryClient();

  useEffect(() => {
    (async () => {
      if (!(await tokenStore.get())) {
        setUser(null);
        return;
      }
      try {
        setUser((await api<{ data: User }>('/me')).data);
      } catch {
        await tokenStore.clear(); // expired or revoked
        setUser(null);
      }
    })();
  }, []);

  const startSession = useCallback(
    async (token: string, next: User) => {
      await tokenStore.set(token);
      setUser(next);
      // Production pages now include "me"; refetch them.
      queryClient.invalidateQueries();
    },
    [queryClient],
  );

  const signIn = useCallback(
    async (c: Credentials) => {
      const r = await api<{ token: string; user: { data?: User } & User }>('/auth/login', {
        method: 'POST',
        body: JSON.stringify({ ...c, device_name: deviceName }),
      });
      await startSession(r.token, r.user.data ?? r.user);
    },
    [startSession],
  );

  const signUp = useCallback(
    async (reg: Registration) => {
      const r = await api<{ token: string; user: { data?: User } & User }>('/auth/register', {
        method: 'POST',
        body: JSON.stringify({ ...reg, device_name: deviceName }),
      });
      await startSession(r.token, r.user.data ?? r.user);
    },
    [startSession],
  );

  const endSession = useCallback(async () => {
    await tokenStore.clear();
    setUser(null);
    queryClient.clear();
  }, [queryClient]);

  const signOut = useCallback(async () => {
    await api('/auth/logout', { method: 'POST' }).catch(() => undefined);
    await endSession();
  }, [endSession]);

  const deleteAccount = useCallback(async () => {
    await api('/me', { method: 'DELETE' });
    await endSession();
  }, [endSession]);

  const updateProfile = useCallback(
    async (changes: ProfileChanges) => {
      const r = await api<{ data: User }>('/me', { method: 'PATCH', body: JSON.stringify(changes) });
      setUser(r.data);
      // Name and bio show on the profile, reviews and feed.
      queryClient.invalidateQueries({ queryKey: ['profile'] });
      queryClient.invalidateQueries({ queryKey: ['reviews'] });
      queryClient.invalidateQueries({ queryKey: ['me', 'feed'] });
    },
    [queryClient],
  );

  const value = useMemo(
    () => ({ user, signIn, signUp, signOut, deleteAccount, updateProfile }),
    [user, signIn, signUp, signOut, deleteAccount, updateProfile],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthState {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used inside <AuthProvider>');
  return ctx;
}

/** Laravel validation errors → { field: "first message" } */
export function fieldErrors(error: unknown): Record<string, string> {
  const body = (error as { body?: { errors?: Record<string, string[]> } })?.body;
  return Object.fromEntries(Object.entries(body?.errors ?? {}).map(([k, v]) => [k, v[0] ?? '']));
}
