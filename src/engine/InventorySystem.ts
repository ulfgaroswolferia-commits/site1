import { ITEMS } from '../content/items.js';
import { GameState } from '../types/game.js';
import { getEffectiveMaxHp } from './CharacterSystem.js';

export function addItem(state: GameState, itemId: string, qty = 1): GameState {
  if (qty <= 0 || !ITEMS[itemId]) return state;
  const current = state.character.inventory[itemId] ?? 0;
  return {
    ...state,
    character: {
      ...state.character,
      inventory: {
        ...state.character.inventory,
        [itemId]: current + qty,
      },
    },
  };
}

export function removeItem(state: GameState, itemId: string, qty = 1): GameState {
  const current = state.character.inventory[itemId] ?? 0;
  if (current <= 0 || qty <= 0) return state;
  const nextQty = current - qty;
  const nextInv = { ...state.character.inventory };
  if (nextQty <= 0) {
    delete nextInv[itemId];
  } else {
    nextInv[itemId] = nextQty;
  }

  let equippedWeaponId = state.character.equippedWeaponId;
  let equippedArmorId = state.character.equippedArmorId;
  if (!nextInv[itemId]) {
    if (equippedWeaponId === itemId) equippedWeaponId = null;
    if (equippedArmorId === itemId) equippedArmorId = null;
  }

  return {
    ...state,
    character: {
      ...state.character,
      inventory: nextInv,
      equippedWeaponId,
      equippedArmorId,
    },
  };
}

export function equipItem(state: GameState, itemId: string): GameState {
  const item = ITEMS[itemId];
  const qty = state.character.inventory[itemId] ?? 0;
  if (!item || qty <= 0) return state;

  if (item.type === 'weapon') {
    return {
      ...state,
      character: {
        ...state.character,
        equippedWeaponId: itemId,
      },
      eventLog: [...state.eventLog.slice(-19), `Wyposażono broń: ${item.name}`],
    };
  }
  if (item.type === 'armor') {
    return {
      ...state,
      character: {
        ...state.character,
        equippedArmorId: itemId,
      },
      eventLog: [...state.eventLog.slice(-19), `Założono pancerz: ${item.name}`],
    };
  }
  return state;
}

export function useConsumable(state: GameState, itemId: string): GameState {
  const item = ITEMS[itemId];
  const qty = state.character.inventory[itemId] ?? 0;
  if (!item || item.type !== 'consumable' || qty <= 0) return state;
  if (!item.healHp && !item.healRad) return state;

  let nextState = removeItem(state, itemId, 1);
  let newRad = nextState.character.rad;
  if (item.healRad) {
    newRad = Math.max(0, newRad - item.healRad);
  }
  const effectiveMax = getEffectiveMaxHp(nextState.character.maxHp, newRad);
  let newHp = nextState.character.hp;
  if (item.healHp) {
    const intBonus = nextState.character.attributes.INT * 3;
    newHp = Math.min(effectiveMax, newHp + item.healHp + intBonus);
  } else {
    newHp = Math.min(effectiveMax, newHp);
  }

  return {
    ...nextState,
    character: {
      ...nextState.character,
      rad: newRad,
      hp: newHp,
    },
    eventLog: [...nextState.eventLog.slice(-19), `Użyto: ${item.name}`],
  };
}

export function getBuyPrice(state: GameState, itemId: string): number {
  const item = ITEMS[itemId];
  if (!item) return 0;
  const chaFactor = 1 - (state.character.attributes.CHA - 5) * 0.03;
  const perkFactor = state.character.perks.includes('golden_tongue') ? 0.85 : 1;
  return Math.max(5, Math.round(item.value * 1.2 * chaFactor * perkFactor));
}

export function getSellPrice(state: GameState, itemId: string): number {
  const item = ITEMS[itemId];
  if (!item || item.type === 'quest') return 0;
  const chaFactor = 1 + (state.character.attributes.CHA - 5) * 0.03;
  const perkFactor = state.character.perks.includes('golden_tongue') ? 1.15 : 1;
  const buyPrice = getBuyPrice(state, itemId);
  return Math.min(
    buyPrice - 1,
    Math.max(1, Math.round(item.value * 0.55 * chaFactor * perkFactor))
  );
}

export function buyItem(state: GameState, itemId: string): GameState {
  const item = ITEMS[itemId];
  if (!item) return state;
  const price = getBuyPrice(state, itemId);
  if (state.character.caps < price) return state;

  const afterAdd = addItem(state, itemId, 1);
  return {
    ...afterAdd,
    character: {
      ...afterAdd.character,
      caps: afterAdd.character.caps - price,
    },
    eventLog: [...afterAdd.eventLog.slice(-19), `Kupiono ${item.name} za ${price} kapsli.`],
  };
}

export function sellItem(state: GameState, itemId: string): GameState {
  const item = ITEMS[itemId];
  const qty = state.character.inventory[itemId] ?? 0;
  if (!item || item.type === 'quest' || qty <= 0) return state;

  const price = getSellPrice(state, itemId);
  const afterRemove = removeItem(state, itemId, 1);
  return {
    ...afterRemove,
    character: {
      ...afterRemove.character,
      caps: afterRemove.character.caps + price,
    },
    eventLog: [...afterRemove.eventLog.slice(-19), `Sprzedano ${item.name} za ${price} kapsli.`],
  };
}
