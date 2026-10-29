import { describe, it, expect } from 'vitest';
import {
  createInitialAttributes,
  getBaseMaxHp,
  getEffectiveMaxHp,
  getMaxAp,
  calculateSkillCheckChance,
  rollSkillCheck,
  applyRadiation,
  grantXp,
  levelUpCharacter,
  createNewGameState,
} from '../src/engine/CharacterSystem.js';

describe('CharacterSystem', () => {
  it('calculates base Max HP, effective Max HP with RAD, and Max AP accurately', () => {
    const attrs = { STR: 5, PER: 6, END: 6, CHA: 5, INT: 7, AGI: 6 };
    expect(getBaseMaxHp(attrs, 1, [])).toBe(130); // 70 + 6 * 10
    expect(getBaseMaxHp(attrs, 2, ['wasteland_toughness'])).toBe(160); // 130 + 10 (lvl 2) + 20 (perk)
    expect(getEffectiveMaxHp(100, 40)).toBe(80); // 40 RAD = -20%
    expect(getMaxAp(attrs)).toBe(8); // 5 + floor(6 / 2)
  });

  it('calculates skill check chance with attribute and perk modifiers', () => {
    const state = createNewGameState('Max', { STR: 5, PER: 5, END: 5, CHA: 5, INT: 7, AGI: 5 });
    const chance = calculateSkillCheckChance(state, { attribute: 'INT', difficulty: 6 });
    // 50 + (7 - 6) * 15 = 65%
    expect(chance).toBe(65);

    state.character.perks.push('robco_hacker');
    const boosted = calculateSkillCheckChance(state, { attribute: 'INT', difficulty: 6 });
    expect(boosted).toBe(85); // +20% from robco_hacker
  });

  it('rolls skill check deterministically with custom rng', () => {
    const state = createNewGameState('Max', createInitialAttributes());
    const successRoll = rollSkillCheck(state, { attribute: 'STR', difficulty: 5 }, () => 0.4);
    expect(successRoll.success).toBe(true);
    const failRoll = rollSkillCheck(state, { attribute: 'STR', difficulty: 5 }, () => 0.8);
    expect(failRoll.success).toBe(false);
  });

  it('applies radiation mitigated by END, armor radResistance, and lead_belly perk and clamps HP', () => {
    let state = createNewGameState('Max', { STR: 5, PER: 5, END: 5, CHA: 5, INT: 5, AGI: 5 });
    state = applyRadiation(state, 40);
    // END 5 reduces 40 by 15% => 34 RAD
    expect(state.character.rad).toBe(34);
    expect(state.character.hp).toBeLessThanOrEqual(getEffectiveMaxHp(state.character.maxHp, state.character.rad));

    state.character.equippedArmorId = 'recon_armor'; // 35% radResistance
    state = applyRadiation(state, 40);
    // 40 * 0.85 * 0.65 = 22.1 -> 22 => 34 + 22 = 56
    expect(state.character.rad).toBe(56);
  });

  it('grants XP with INT bonus and handles level up with attribute co 3 lvl and perk co 2 lvl', () => {
    let state = createNewGameState('Max', { STR: 5, PER: 5, END: 5, CHA: 5, INT: 5, AGI: 5 });
    state = grantXp(state, 100); // 100 * (1 + 5*0.03) = 115 XP
    expect(state.character.xp).toBe(115);
    expect(state.character.pendingLevelUps).toBe(1);

    // Lvl 2: Perk co 2 levele (dostaje vats_sniper), ale brak punktu SPECIAL (2 % 3 !== 0)
    state = levelUpCharacter(state, 'PER', 'vats_sniper');
    expect(state.character.level).toBe(2);
    expect(state.character.attributes.PER).toBe(5); // bez zmiany na lvl 2
    expect(state.character.perks).toContain('vats_sniper');
    expect(state.character.maxHp).toBe(getBaseMaxHp(state.character.attributes, 2, state.character.perks));

    // Lvl 3: Punkt SPECIAL co 3 levele (dostaje +1 PER), ale brak perka (3 % 2 !== 0)
    state.character.pendingLevelUps = 1;
    state = levelUpCharacter(state, 'PER', 'robco_hacker');
    expect(state.character.level).toBe(3);
    expect(state.character.attributes.PER).toBe(6); // +1 na lvl 3
    expect(state.character.perks).not.toContain('robco_hacker'); // nie przysługuje na lvl 3

    // Verify XP progression supports reaching Level 12-13 in late game
    state = grantXp(state, 5000);
    expect(state.character.level + state.character.pendingLevelUps).toBe(13);
  });
});
