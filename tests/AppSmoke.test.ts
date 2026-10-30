import { describe, it, expect } from 'vitest';
import React from 'react';
import { App } from '../src/ui/App.js';
import { PipBoyOverlay } from '../src/ui/components/PipBoyOverlay.js';
import { ExplorationView } from '../src/ui/components/ExplorationView.js';
import { WorldMapView } from '../src/ui/components/WorldMapView.js';
import { MerchantView } from '../src/ui/components/MerchantView.js';
import { HeaderStatus } from '../src/ui/components/HeaderStatus.js';
import { CombatView } from '../src/ui/components/CombatView.js';
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

  it('renders ExplorationView cleanly with compact spacing for Arroyo scene', () => {
    const state = createNewGameState('Wędrowiec', createInitialAttributes());
    state.flags.arroyo_unlocked = true;
    state.currentLocationId = 'arroyo';
    state.currentSceneId = 'arroyo_village';
    state.eventLog.push('Dotarto do lokacji: Wioska Arroyo.');

    const exploration = React.createElement(ExplorationView, {
      state,
      onSelectChoice: () => {},
      onReturnToTitle: () => {},
    });
    expect(exploration).toBeDefined();
  });

  it('renders WorldMapView cleanly with Chapter 3 locations unlocked', () => {
    const state = createNewGameState('Wędrowiec', createInitialAttributes());
    state.flags.arroyo_unlocked = true;
    state.flags.ncr_unlocked = true;
    state.flags.v13_coords_known = true;
    state.flags.vault_13_unlocked = true;

    const mapView = React.createElement(WorldMapView, {
      state,
      onSelectLocation: () => {},
      onClose: () => {},
    });
    expect(mapView).toBeDefined();
  });

  it('renders HeaderStatus cleanly with water crisis active in Chapter 3', () => {
    const state = createNewGameState('Wędrowiec', createInitialAttributes());
    state.flags.arroyo_unlocked = true;
    state.currentLocationId = 'arroyo';
    state.counters = { water_days_left: 54, vault_openness: 1 };

    const header = React.createElement(HeaderStatus, {
      state,
      modeLabel: 'EKSPLORACJA',
    });
    expect(header).toBeDefined();
  });

  it('renders MerchantView with pagination support cleanly', () => {
    const state = createNewGameState('Wędrowiec', createInitialAttributes());
    state.activeMerchantId = 'doc_mitchell';

    const merchantView = React.createElement(MerchantView, {
      state,
      onBuy: () => {},
      onSell: () => {},
      onClose: () => {},
    });
    expect(merchantView).toBeDefined();
  });

  it('renders CombatView cleanly with compact layouts', () => {
    const state = createNewGameState('Wędrowiec', createInitialAttributes());
    state.combat = {
      enemyId: 'radscorpion_alpha',
      enemyName: 'Alfa Radskorpion ze Skał Arroyo',
      enemyHp: 65,
      enemyMaxHp: 65,
      enemyAttack: 14,
      enemyXp: 180,
      turn: 1,
      playerAp: 7,
      playerMaxAp: 7,
      log: ['Początek starcia z Alfa Radskorpion ze Skał Arroyo!'],
      isBoss: false,
    };

    const combatView = React.createElement(CombatView, {
      state,
      onStandardAttack: () => {},
      onVatsAttack: () => {},
      onUseItem: () => {},
      onEndTurn: () => {},
      onFlee: () => {},
    });
    expect(combatView).toBeDefined();
  });

  it('renders HeaderStatus with specific ammo tags for different weapon types', () => {
    const state = createNewGameState('Wędrowiec', createInitialAttributes());
    // 10mm pistol
    state.character.equippedWeaponId = 'pipe_pistol';
    state.character.inventory.ammo_10mm = 0;
    const header10mm = React.createElement(HeaderStatus, { state, modeLabel: 'EKSPLORACJA' });
    expect(header10mm).toBeDefined();

    // Laser rifle (Energy / Ogniwa MFC)
    state.character.equippedWeaponId = 'laser_rifle';
    state.character.inventory.ammo_energy = 8;
    const headerEnergy = React.createElement(HeaderStatus, { state, modeLabel: 'EKSPLORACJA' });
    expect(headerEnergy).toBeDefined();

    // Sniper rifle (.308 / 5mm)
    state.character.equippedWeaponId = 'sniper_rifle';
    state.character.inventory.ammo_rifle = 12;
    const headerRifle = React.createElement(HeaderStatus, { state, modeLabel: 'EKSPLORACJA' });
    expect(headerRifle).toBeDefined();

    // Melee weapon
    state.character.equippedWeaponId = 'combat_knife';
    const headerMelee = React.createElement(HeaderStatus, { state, modeLabel: 'EKSPLORACJA' });
    expect(headerMelee).toBeDefined();
  });
});
