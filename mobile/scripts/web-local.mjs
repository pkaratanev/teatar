// Builds the web version of the app into backend/public/app, served by the local API at
// http://localhost:8000/app. Same address as the API, so a browser makes no cross-port requests.
// For testing in a browser on this PC only; the phone uses Expo Go as before.
import { execSync } from 'node:child_process';

execSync('npx expo export --platform web --output-dir ../backend/public/app --clear', {
  stdio: 'inherit',
  env: { ...process.env, EXPO_BASE_URL: '/app' },
});
console.log('\nDone. With the API running, open http://localhost:8000/app');
