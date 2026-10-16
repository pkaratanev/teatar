import * as Linking from 'expo-linking';

/**
 * Links into the app. In a store or dev build they use the app's own scheme
 * (teatar://production/12); in Expo Go they come out as exp://…/--/production/12.
 * Expo Router opens the matching screen from these paths, so the paths here
 * are the same as the file routes under app/.
 *
 * They only open on phones that have the app. Links that also work in a
 * browser (https://…) need a website domain; that's planned with the deploy.
 */
export const links = {
  production: (id: number) => Linking.createURL(`production/${id}`),
  theatre: (id: number) => Linking.createURL(`theatre/${id}`),
  person: (id: number) => Linking.createURL(`person/${id}`),
  user: (username: string) => Linking.createURL(`user/${username}`),
};
