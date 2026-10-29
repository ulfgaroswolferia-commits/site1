import { describe, it, expect } from 'vitest';
import { createNewGameState, createInitialAttributes } from '../src/engine/CharacterSystem.js';
import {
  startCombat,
  getVatsHitChance,
  performPlayerAttack,
  endPlayerTurn,
  attemptFlee,
} from '../src/engine/CombatEngine.js';

describe('CombatEngine', () => {
  it('initializes combat state with enemy and player AP', () => {
    let state = createNewGameState('Max', createInitialAttributes());
    state = startCombat(state, 'feral_ghoul', 'vault_gate');
    expect(state.combat).not.toBeNull();
    expect(state.combat?.enemyName).toBe('Zdziczały Ghul');
    expect(state.combat?.playerAp).toBe(7);
  });

  it('calculates V.A.T.S. hit chances and applies headshot damage + stun on low rng', () => {
    let state = createNewGameState('Max', { STR: 5, PER: 8, END: 5, CHA: 5, INT: 5, AGI: 6 });
    state = startCombat(state, 'feral_ghoul', 'vault_gate');
    const headChance = getVatsHitChance(state, 'head');
    const torsoChance = getVatsHitChance(state, 'torso');
    expect(torsoChance).toBeGreaterThan(headChance);

    const beforeHp = state.combat!.enemyHp;
    state = performPlayerAttack(state, 'vats', 'head', () => 0.1);
    expect(state.combat!.enemyHp).toBeLessThan(beforeHp);
    expect(state.combat!.enemyStunned).toBe(true);
    expect(state.combat!.playerAp).toBe(4); // 8 - 4
  });

  it('transitions to victorySceneId and awards XP + caps when enemy dies', () => {
    let state = createNewGameState('Max', createInitialAttributes());
    state = startCombat(state, 'feral_ghoul', 'vault_gate');
    state.combat!.enemyHp = 5;
    state = performPlayerAttack(state, 'standard', 'torso', () => 0.1);
    expect(state.combat).toBeNull();
    expect(state.currentSceneId).toBe('vault_gate');
    expect(state.character.xp).toBeGreaterThan(0);
  });

  it('executes enemy turn when player ends turn and refreshes AP', () => {
    let state = createNewGameState('Max', createInitialAttributes());
    state = startCombat(state, 'feral_ghoul', 'vault_gate');
    const beforeHp = state.character.hp;
    state = endPlayerTurn(state, () => 0.1);
    expect(state.character.hp).toBeLessThan(beforeHp);
    expect(state.combat?.turn).toBe(2);
    expect(state.combat?.playerAp).toBe(state.combat?.playerMaxAp);
  });

  it('allows fleeing from non-boss enemies on successful AGI roll', () => {
    let state = createNewGameState('Max', createInitialAttributes());
    state = startCombat(state, 'wasteland_raider', 'crater_hub', 'crater_hub');
    state = attemptFlee(state, () => 0.1);
    expect(state.combat).toBeNull();
    expect(state.currentSceneId).toBe('crater_hub');
  });

  it('consumes ammunition on ranged attacks and falls back to rifle butt strike when out of ammo', () => {
    let state = createNewGameState('Max', createInitialAttributes());
    state = startCombat(state, 'feral_ghoul', 'vault_gate');
    const initialAmmo = state.character.inventory.ammo_10mm ?? 0;
    expect(initialAmmo).toBeGreaterThan(0);

    state = performPlayerAttack(state, 'standard', 'torso', () => 0.1);
    expect(state.character.inventory.ammo_10mm).toBe(initialAmmo - 1);

    // Exhaust ammo and verify fallback melee attack still works
    delete state.character.inventory.ammo_10mm;
    state = performPlayerAttack(state, 'standard', 'torso', () => 0.1);
    expect(state.combat?.log.some((l) => l.includes('BRAK AMUNICJI'))).toBe(true);
  });

  it('applies plasma_master accuracy bonus and wasteland_legend dodge/flee bonuses', () => {
    let state = createNewGameState('Max', createInitialAttributes());
    state = startCombat(state, 'wasteland_raider', 'crater_hub', 'crater_hub');
    const baseVatsTorso = getVatsHitChance(state, 'torso');

    state.character.perks.push('plasma_master');
    expect(getVatsHitChance(state, 'torso')).toBe(baseVatsTorso + 10);

    // wasteland_legend gives +10% flee chance (40 + 5*5 = 65% -> 75% with perk)
    state.character.perks.push('wasteland_legend');
    // Roll 0.70 (71) fails at 65% but succeeds at 75%
    const fled = attemptFlee(state, () => 0.7);
    expect(fled.combat).toBeNull();
  });
});
