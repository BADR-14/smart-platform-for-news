// يشغّل موقع الشركات وموقع الإدارة معاً: npm run dev
import { spawn } from 'node:child_process';

const sites = [
  { mode: 'company', label: 'الشركات  http://localhost:5173' },
  { mode: 'admin', label: 'الإدارة   http://localhost:5174' },
];

const children = sites.map(({ mode }) => spawn('npx', ['vite', '--mode', mode], { stdio: 'inherit', shell: true }));

console.log('\n' + sites.map((site) => `  ${site.label}`).join('\n') + '\n');

const stop = () => children.forEach((child) => child.kill());
process.on('SIGINT', stop);
process.on('SIGTERM', stop);
children.forEach((child) => child.on('exit', stop));
