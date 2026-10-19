import * as Linking from 'expo-linking';
import { Platform } from 'react-native';

/** A place to show on a map: a theatre. Coordinates when known, else the address is searched. */
export type Place = { name: string; address: string | null; city: string; lat: number | null; lng: number | null };

const label = (p: Place) => [p.name, p.address, p.city].filter(Boolean).join(', ');
const point = (p: Place) => (p.lat !== null && p.lng !== null ? `${p.lat},${p.lng}` : null);

/** Google Maps in the browser: the fallback everywhere, and what the web build uses. */
const google = {
  place: (p: Place) => `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(point(p) ?? label(p))}`,
  directions: (p: Place) => `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(point(p) ?? label(p))}`,
};

async function open(url: string, fallback: string) {
  try {
    await Linking.openURL(url);
  } catch {
    await Linking.openURL(fallback);
  }
}

/**
 * The place in the phone's map app. Android's geo: link lets the person pick the app (Google Maps, Waze …);
 * iOS opens Apple Maps.
 */
export function openInMaps(p: Place) {
  const at = point(p);
  if (Platform.OS === 'android') {
    const q = at ? `${at}(${p.name})` : label(p);
    return open(`geo:${at ?? '0,0'}?q=${encodeURIComponent(q)}`, google.place(p));
  }
  if (Platform.OS === 'ios') {
    const q = `?q=${encodeURIComponent(at ? p.name : label(p))}${at ? `&ll=${at}` : ''}`;
    return open(`maps://${q}`, google.place(p));
  }
  return Linking.openURL(google.place(p));
}

/** Directions from where the person is. Google Maps opens in its app on Android; Apple Maps on iOS. */
export function openDirections(p: Place) {
  if (Platform.OS === 'ios') {
    return open(`maps://?daddr=${encodeURIComponent(point(p) ?? label(p))}`, google.directions(p));
  }
  return Linking.openURL(google.directions(p));
}

/** Can a map be shown at all? */
export const hasLocation = (p: Place) => point(p) !== null || !!p.address;
