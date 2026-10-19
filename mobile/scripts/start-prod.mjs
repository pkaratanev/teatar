// `npm run start:prod`: Expo Go against the production API on Laravel Cloud instead of the local one.
// The address set here wins over mobile/.env (which scripts/windows.ps1 points at this PC); --clear makes Metro
// rebuild, since EXPO_PUBLIC_ values are baked into the bundle.
import { spawn } from 'node:child_process';

const API = 'https://teatar-production-8awteq.laravel.cloud/api/v1'; // same as eas.json

console.log(`API: ${API}\n`);
const child = spawn('npx', ['expo', 'start', '--clear', ...process.argv.slice(2)], {
  stdio: 'inherit',
  shell: process.platform === 'win32',
  env: { ...process.env, EXPO_PUBLIC_API_URL: API },
});
child.on('exit', (code) => process.exit(code ?? 0));
