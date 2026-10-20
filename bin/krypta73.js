#!/usr/bin/env node
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import fs from 'node:fs';
import path from 'node:path';

const rootDir = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const builtEntry = path.join(rootDir, 'dist', 'index.js');
const tsxCli = path.join(rootDir, 'node_modules', 'tsx', 'dist', 'cli.mjs');
const srcEntry = path.join(rootDir, 'src', 'index.tsx');

const args = fs.existsSync(builtEntry) ? [builtEntry] : [tsxCli, srcEntry];

const child = spawn(process.execPath, args, {
  cwd: rootDir,
  stdio: 'inherit',
});

child.on('exit', (code) => {
  process.exit(code ?? 0);
});
