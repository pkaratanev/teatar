import { createContext, type ReactNode, useCallback, useContext, useEffect, useMemo, useState } from 'react';

import { deviceValue } from './api/client';

export const DEFAULT_CITY = 'София';

const saved = deviceValue('teatar.city');

type CityState = { city: string; setCity: (city: string) => void };

const CityContext = createContext<CityState>({ city: DEFAULT_CITY, setCity: () => undefined });

/** The city whose programme the app shows. Remembered on the device; София until the user picks another. */
export function CityProvider({ children }: { children: ReactNode }) {
  const [city, setCityState] = useState(DEFAULT_CITY);

  useEffect(() => {
    saved
      .get()
      .then((value) => value && setCityState(value))
      .catch(() => undefined);
  }, []);

  const setCity = useCallback((next: string) => {
    setCityState(next);
    saved.set(next).catch(() => undefined);
  }, []);

  const value = useMemo(() => ({ city, setCity }), [city, setCity]);

  return <CityContext.Provider value={value}>{children}</CityContext.Provider>;
}

export const useCity = () => useContext(CityContext);
