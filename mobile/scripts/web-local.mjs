// Builds the web version of the app into backend/public/app-build; the local API serves it at
// http://localhost:8000/app (routes/web.php). Same address as the API, so a browser makes no cross-port requests.
// For testing in a browser on this PC only; the phone uses Expo Go as before.
//
// The folder is deliberately not called "app": PHP's built-in server (php artisan serve) treats a public folder
// with an index.html as a script folder, and Laravel then sees /app/sign-in as /sign-in.
import { execSync } from 'node:child_process';
import { rmSync } from 'node:fs';

rmSync('../backend/public/app', { recursive: true, force: true }); // an older build's location
execSync('npx expo export --platform web --output-dir ../backend/public/app-build --clear', {
  stdio: 'inherit',
  env: { ...process.env, EXPO_BASE_URL: '/app' },
});
console.log('\nDone. With the API running, open http://localhost:8000/app');
