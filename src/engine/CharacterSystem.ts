import {
  AttributeKey,
  Attributes,
  CharacterState,
  GameState,
  SkillCheckSpec,
} from '../types/game.js';
import { ITEMS } from '../content/items.js';

export const XP_THRESHOLDS = [0, 100, 250, 450, 700, 1000, 1350, 1750, 2200];

export function createInitialAttributes(): Attributes {
  return { STR: 5, PER: 5, END: 5, CHA: 5, INT: 5, AGI: 5 };
}

export function getBaseMaxHp(attributes: Attributes, level: number, perks: string[]): number {
  const base = 70 + attributes.END * 10 + (level - 1) * 10;
  const perkBonus =
    (perks.includes('wasteland_toughness') ? 20 : 0) +
    (perks.includes('cyborg_reflex') ? 15 : 0);
  return base + perkBonus;
}

export function getEffectiveMaxHp(maxHp: number, rad: number): number {
  const clampedRad = Math.max(0, Math.min(90, rad));
  const multiplier = 1 - clampedRad * 0.005;
  return Math.max(10, Math.round(maxHp * multiplier));
}

export function getMaxAp(attributes: Attributes): number {
  return 5 + Math.floor(attributes.AGI / 2);
}

export function calculateSkillCheckChance(state: GameState, check: SkillCheckSpec): number {
  const attrVal = state.character.attributes[check.attribute];
  if (attrVal >= check.difficulty + 2) {
    return 100;
  }
  let bonus = 0;
  if (check.attribute === 'INT' && state.character.perks.includes('robco_hacker')) {
    bonus += 20;
  }
  if (check.attribute === 'CHA' && state.character.perks.includes('golden_tongue')) {
    bonus += 20;
  }
  const raw = 50 + (attrVal - check.difficulty) * 15 + bonus;
  return Math.max(10, Math.min(95, raw));
}

export function rollSkillCheck(
  state: GameState,
  check: SkillCheckSpec,
  rng: () => number = Math.random
): { success: boolean; chance: number; roll: number } {
  const chance = calculateSkillCheckChance(state, check);
  const roll = Math.floor(rng() * 100) + 1;
  return {
    success: roll <= chance,
    chance,
    roll,
  };
}

export function applyRadiation(state: GameState, rawRad: number): GameState {
  if (rawRad === 0) return state;
  let delta = rawRad;
  if (rawRad > 0) {
    const endMitigation = 1 - state.character.attributes.END * 0.03;
    const perkMitigation = state.character.perks.includes('lead_belly') ? 0.5 : 1;
    const armor = state.character.equippedArmorId
      ? ITEMS[state.character.equippedArmorId]
      : null;
    const armorMitigation = 1 - (armor?.radResistance ?? 0) / 100;
    delta = Math.max(1, Math.round(rawRad * endMitigation * perkMitigation * armorMitigation));
  }
  const newRad = Math.max(0, Math.min(90, state.character.rad + delta));
  const effectiveMax = getEffectiveMaxHp(state.character.maxHp, newRad);
  const newHp = Math.min(state.character.hp, effectiveMax);

  return {
    ...state,
    character: {
      ...state.character,
      rad: newRad,
      hp: newHp,
    },
  };
}

export function grantXp(state: GameState, baseXp: number): GameState {
  if (baseXp <= 0) return state;
  const intBonus = 1 + state.character.attributes.INT * 0.03;
  const gained = Math.round(baseXp * intBonus);
  const newXp = state.character.xp + gained;

  let targetLevel = state.character.level + state.character.pendingLevelUps;
  let newPending = state.character.pendingLevelUps;
  while (targetLevel < XP_THRESHOLDS.length && newXp >= XP_THRESHOLDS[targetLevel]) {
    newPending += 1;
    targetLevel += 1;
  }

  return {
    ...state,
    character: {
      ...state.character,
      xp: newXp,
      pendingLevelUps: newPending,
    },
    eventLog: [...state.eventLog.slice(-19), `Zdobyto ${gained} XP.`],
  };
}

export function levelUpCharacter(
  state: GameState,
  attributeToIncrease: AttributeKey,
  perkId: string
): GameState {
  if (state.character.pendingLevelUps <= 0) return state;

  const newLevel = state.character.level + 1;
  const newAttributes: Attributes = {
    ...state.character.attributes,
    [attributeToIncrease]: Math.min(10, state.character.attributes[attributeToIncrease] + 1),
  };
  const newPerks = state.character.perks.includes(perkId)
    ? state.character.perks
    : [...state.character.perks, perkId];
  const newMaxHp = getBaseMaxHp(newAttributes, newLevel, newPerks);
  const effectiveMax = getEffectiveMaxHp(newMaxHp, state.character.rad);

  return {
    ...state,
    character: {
      ...state.character,
      level: newLevel,
      pendingLevelUps: state.character.pendingLevelUps - 1,
      attributes: newAttributes,
      perks: newPerks,
      maxHp: newMaxHp,
      hp: effectiveMax,
    },
    eventLog: [
      ...state.eventLog.slice(-19),
      `Awans na poziom ${newLevel}! (+1 ${attributeToIncrease}, nowy Perk: ${perkId})`,
    ],
  };
}

export function createNewGameState(name: string, attributes: Attributes): GameState {
  const maxHp = getBaseMaxHp(attributes, 1, []);
  const character: CharacterState = {
    name: name.trim() || 'Wędrowiec z Krypty',
    level: 1,
    xp: 0,
    pendingLevelUps: 0,
    hp: maxHp,
    maxHp,
    rad: 0,
    caps: 50,
    attributes,
    perks: [],
    equippedWeaponId: 'pipe_pistol',
    equippedArmorId: 'vault_suit',
    inventory: {
      pipe_pistol: 1,
      vault_suit: 1,
      stimpak: 2,
      radaway: 1,
    },
  };

  return {
    character,
    currentLocationId: 'vault_73',
    currentSceneId: 'vault_intro',
    flags: {},
    quests: {
      main_stabilizer: {
        status: 'active',
        stage: 'talk_overseer',
      },
    },
    combat: null,
    activeMerchantId: null,
    pendingDestinationSceneId: null,
    eventLog: ['System Pip-Boy 3000 MK IV zainicjalizowany.'],
  };
}
