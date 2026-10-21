import { describe, it, expect } from 'vitest';
import { createNewGameState, createInitialAttributes } from '../src/engine/CharacterSystem.js';
import {
  addItem,
  equipItem,
  useConsumable,
  getBuyPrice,
  getSellPrice,
  buyItem,
  sellItem,
  formatItemStats,
} from '../src/engine/InventorySystem.js';
import { ITEMS } from '../src/content/items.js';

describe('InventorySystem', () => {
  it('equips weapons and armor from inventory', () => {
    let state = createNewGameState('Max', createInitialAttributes());
    state = addItem(state, 'laser_rifle', 1);
    state = equipItem(state, 'laser_rifle');
    expect(state.character.equippedWeaponId).toBe('laser_rifle');
  });

  it('uses stimpak with INT bonus and radaway to reduce RAD', () => {
    let state = createNewGameState('Max', { STR: 5, PER: 5, END: 5, CHA: 5, INT: 6, AGI: 5 });
    state.character.rad = 30;
    state.character.hp = 40;
    state = useConsumable(state, 'stimpak');
    // base 35 + INT(6)*3 = 53 => 40 + 53 = 93
    expect(state.character.hp).toBe(93);
    expect(state.character.inventory.stimpak).toBe(1);

    state = useConsumable(state, 'radaway');
    expect(state.character.rad).toBe(0);
    expect(state.character.inventory.radaway).toBeUndefined();
  });

  it('calculates buy/sell prices with CHA and golden_tongue perk and processes trade', () => {
    let state = createNewGameState('Max', { STR: 5, PER: 5, END: 5, CHA: 7, INT: 5, AGI: 5 });
    state.character.caps = 200;
    const buyPrice = getBuyPrice(state, 'stimpak');
    const sellPrice = getSellPrice(state, 'stimpak');
    expect(buyPrice).toBeGreaterThan(sellPrice);

    state = buyItem(state, 'stimpak');
    expect(state.character.caps).toBe(200 - buyPrice);
    expect(state.character.inventory.stimpak).toBe(3);

    state = sellItem(state, 'stimpak');
    expect(state.character.inventory.stimpak).toBe(2);
  });

  it('formats weapon, armor, and consumable statistics for UI display', () => {
    expect(formatItemStats(ITEMS.laser_rifle)).toBe(
      '[DMG: 26 | CEL: +15% | AP: 3 | DYSTANS (PER) | AMMO: Ogniwa MFC (1/strzał)]'
    );
    expect(formatItemStats(ITEMS.super_sledge)).toBe('[DMG: 40 | CEL: +12% | AP: 3 | WRĘCZ (STR)]');
    expect(formatItemStats(ITEMS.power_armor_t51)).toBe('[OBR: +22 | ODP. RAD: 65%]');
    expect(formatItemStats(ITEMS.pulse_grenade)).toBe('[DMG: 35 | OGŁUSZENIE: 80%]');
    expect(formatItemStats(ITEMS.ammo_10mm)).toBe('[PACZKA: +12 SZT.]');
  });

  it('buys ammo in packs and includes starter magazine when acquiring a ranged weapon', () => {
    let state = createNewGameState('Max', createInitialAttributes());
    state.character.caps = 200;
    const before10mm = state.character.inventory.ammo_10mm ?? 0;
    state = buyItem(state, 'ammo_10mm');
    expect(state.character.inventory.ammo_10mm).toBe(before10mm + 12);

    // Acquiring laser_rifle for the first time grants 10 starter energy cells
    state = addItem(state, 'laser_rifle', 1);
    expect(state.character.inventory.ammo_energy).toBe(10);
  });
});
