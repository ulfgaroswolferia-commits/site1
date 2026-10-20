import { describe, it, expect } from 'vitest';
import React from 'react';
import { App } from '../src/ui/App.js';
import { renderBar, THEME } from '../src/ui/theme.js';

describe('UI Smoke Test', () => {
  it('renders ASCII progress bar and exports App component cleanly', () => {
    expect(renderBar(50, 100, 10)).toBe('█████░░░░░');
    expect(THEME.primary).toBe('greenBright');
    const element = React.createElement(App);
    expect(element).toBeDefined();
  });
});
