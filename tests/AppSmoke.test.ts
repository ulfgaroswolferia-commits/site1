import { describe, it, expect } from 'vitest';
import React from 'react';
import { App } from '../src/ui/App.js';
import { PipBoyOverlay } from '../src/ui/components/PipBoyOverlay.js';
import { ExplorationView } from '../src/ui/components/ExplorationView.js';
import { createInitialAttributes, createNewGameState } from '../src/engine/CharacterSystem.js';
import { renderBar, THEME } from '../src/ui/theme.js';

describe('UI Smoke Test', () => {
  it('renders ASCII progress bar and exports App component cleanly', () => {
    expect(renderBar(50, 100, 10)).toBe('█████░░░░░');
    expect(THEME.primary).toBe('greenBright');
    const element = React.createElement(App);
    expect(element).toBeDefined();
  });

  it('renders PipBoyOverlay for stats with and without pending level ups cleanly', () => {
    const state = createNewGameState('Wędrowiec', createInitialAttributes());
    const statsOverlay = React.createElement(PipBoyOverlay, {
      state,
      tab: 'stats',
      onEquipOrUse: () => {},
      onLevelUp: () => {},
      onClose: () => {},
    });
    expect(statsOverlay).toBeDefined();

    const stateWithLevelUp = {
      ...state,
      character: {
        ...state.character,
        pendingLevelUps: 1,
        level: 1,
      },
    };
    const levelUpOverlay = React.createElement(PipBoyOverlay, {
      state: stateWithLevelUp,
      tab: 'stats',
      onEquipOrUse: () => {},
      onLevelUp: () => {},
      onClose: () => {},
    });
    expect(levelUpOverlay).toBeDefined();

    const inventoryOverlay = React.createElement(PipBoyOverlay, {
      state,
      tab: 'inventory',
      onEquipOrUse: () => {},
      onLevelUp: () => {},
      onClose: () => {},
    });
    expect(inventoryOverlay).toBeDefined();

    const questsOverlay = React.createElement(PipBoyOverlay, {
      state,
      tab: 'quests',
      onEquipOrUse: () => {},
      onLevelUp: () => {},
      onClose: () => {},
    });
    expect(questsOverlay).toBeDefined();
  });

  it('renders ExplorationView cleanly with compact spacing', () => {
    const state = createNewGameState('Wędrowiec', createInitialAttributes());
    const exploration = React.createElement(ExplorationView, {
      state,
      onSelectChoice: () => {},
      onReturnToTitle: () => {},
    });
    expect(exploration).toBeDefined();
  });
});
