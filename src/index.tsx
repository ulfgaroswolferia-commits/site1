import React from 'react';
import { render } from 'ink';
import { App } from './ui/App.js';

if (process.stdout.isTTY) {
  process.stdout.write('\x1b[?1049h\x1b[H');
  const restore = () => {
    process.stdout.write('\x1b[?1049l\x1b[?25h');
  };
  process.on('exit', restore);
  process.on('SIGINT', () => {
    restore();
    process.exit(0);
  });
}

console.clear();
const app = render(<App />);
await app.waitUntilExit();
