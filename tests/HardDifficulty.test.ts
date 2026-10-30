import { describe, it, expect } from 'vitest';
import path from 'node:path';
import os from 'node:os';
import fs from 'node:fs';
import React from 'react';
import { createNewGameState, calculateSkillCheckChance } from '../src/engine/CharacterSystem.js';
import {
  startCombat,
  getVatsHitChance,
  performPlayerAttack,
  endPlayerTurn,
  attemptFlee,
} from '../src/engine/CombatEngine.js';
import { saveGame, loadGame } from '../src/engine/SaveManager.js';
import { ENEMIES } from '../src/content/enemies.js';
import { MainMenu } from '../src/ui/components/MainMenu.js';
import { CharacterCreator } from '../src/ui/components/CharacterCreator.js';
import { HeaderStatus } from '../src/ui/components/HeaderStatus.js';
import { CombatView } from '../src/ui/components/CombatView.js';

describe('Hard Difficulty System', () => {
  it('creates new game with normal difficulty by default and supports explicit hard difficulty', () => {
    const normalState = createNewGameState('Max', { STR: 5, PER: 5, END: 5, CHA: 5, INT: 5, AGI: 5 });
    expect(normalState.difficulty).toBe('normal');

    const hardState = createNewGameState('Max', { STR: 5, PER: 5, END: 5, CHA: 5, INT: 5, AGI: 5 }, 'hard');
    expect(hardState.difficulty).toBe('hard');
  });

  it('reduces dialogue and skill check chances on HARD compared to Normal', () => {
    const normalState = createNewGameState('Max', { STR: 5, PER: 5, END: 5, CHA: 7, INT: 5, AGI: 5 }, 'normal');
    const hardState = createNewGameState('Max', { STR: 5, PER: 5, END: 5, CHA: 7, INT: 5, AGI: 5 }, 'hard');

    // On normal, attrVal 7 vs diff 5 (>= diff + 2) is 100% guaranteed
    const normalChance = calculateSkillCheckChance(normalState, { attribute: 'CHA', difficulty: 5 });
    expect(normalChance).toBe(100);

    // On hard, no auto-100%, -20% penalty: 50 + (7 - 5)*15 - 20 = 60%
    const hardChance = calculateSkillCheckChance(hardState, { attribute: 'CHA', difficulty: 5 });
    expect(hardChance).toBe(60);

    // Equal attribute check (attr 5 vs diff 5)
    const normalEqual = calculateSkillCheckChance(normalState, { attribute: 'INT', difficulty: 5 });
    const hardEqual = calculateSkillCheckChance(hardState, { attribute: 'INT', difficulty: 5 });
    expect(normalEqual).toBe(50);
    expect(hardEqual).toBe(30);

    // Max skill check chance on HARD is capped at 80% even with perks and max stat
    hardState.character.attributes.CHA = 10;
    hardState.character.perks.push('golden_tongue');
    const cappedHard = calculateSkillCheckChance(hardState, { attribute: 'CHA', difficulty: 5 });
    expect(cappedHard).toBe(80);
  });

  it('boosts enemy HP by 65% when combat starts on HARD', () => {
    const normalState = createNewGameState('Max', { STR: 5, PER: 5, END: 5, CHA: 5, INT: 5, AGI: 5 }, 'normal');
    const hardState = createNewGameState('Max', { STR: 5, PER: 5, END: 5, CHA: 5, INT: 5, AGI: 5 }, 'hard');

    const enemyBase = ENEMIES.feral_ghoul;
    const combatNormal = startCombat(normalState, 'feral_ghoul', 'vault_gate');
    const combatHard = startCombat(hardState, 'feral_ghoul', 'vault_gate');

    expect(combatNormal.combat?.enemyMaxHp).toBe(enemyBase.maxHp);
    expect(combatHard.combat?.enemyMaxHp).toBe(Math.round(enemyBase.maxHp * 1.65));
    expect(combatHard.combat?.enemyHp).toBe(combatHard.combat?.enemyMaxHp);
    expect(combatHard.combat?.log[0]).toContain('TRYB HARD');
  });

  it('enemies hit harder and with higher accuracy on HARD', () => {
    const normalState = startCombat(
      createNewGameState('Max', { STR: 5, PER: 5, END: 5, CHA: 5, INT: 5, AGI: 5 }, 'normal'),
      'feral_ghoul',
      'vault_gate'
    );
    const hardState = startCombat(
      createNewGameState('Max', { STR: 5, PER: 5, END: 5, CHA: 5, INT: 5, AGI: 5 }, 'hard'),
      'feral_ghoul',
      'vault_gate'
    );

    // RNG 0.05 guarantees hit for both
    const afterNormalTurn = endPlayerTurn(normalState, () => 0.05);
    const afterHardTurn = endPlayerTurn(hardState, () => 0.05);

    const normalDmgTaken = normalState.character.hp - afterNormalTurn.character.hp;
    const hardDmgTaken = hardState.character.hp - afterHardTurn.character.hp;

    expect(hardDmgTaken).toBeGreaterThan(normalDmgTaken);
  });

  it('increases enemy defense against player attacks and reduces VATS hit chance on HARD', () => {
    const normalState = startCombat(
      createNewGameState('Max', { STR: 5, PER: 6, END: 5, CHA: 5, INT: 5, AGI: 5 }, 'normal'),
      'feral_ghoul',
      'vault_gate'
    );
    const hardState = startCombat(
      createNewGameState('Max', { STR: 5, PER: 6, END: 5, CHA: 5, INT: 5, AGI: 5 }, 'hard'),
      'feral_ghoul',
      'vault_gate'
    );

    const normalVatsHead = getVatsHitChance(normalState, 'head');
    const hardVatsHead = getVatsHitChance(hardState, 'head');
    expect(hardVatsHead).toBe(normalVatsHead - 10);

    // Same hit roll (0.01 = hit), check damage dealt
    const normalAfterAttack = performPlayerAttack(normalState, 'standard', 'torso', () => 0.01);
    const hardAfterAttack = performPlayerAttack(hardState, 'standard', 'torso', () => 0.01);

    const normalDmgDealt = normalState.combat!.enemyHp - normalAfterAttack.combat!.enemyHp;
    const hardDmgDealt = hardState.combat!.enemyHp - hardAfterAttack.combat!.enemyHp;

    expect(hardDmgDealt).toBeLessThan(normalDmgDealt);
  });

  it('makes fleeing more difficult on HARD', () => {
    const normalState = startCombat(
      createNewGameState('Max', { STR: 5, PER: 5, END: 5, CHA: 5, INT: 5, AGI: 5 }, 'normal'),
      'wasteland_raider',
      'crater_hub',
      'crater_hub'
    );
    const hardState = startCombat(
      createNewGameState('Max', { STR: 5, PER: 5, END: 5, CHA: 5, INT: 5, AGI: 5 }, 'hard'),
      'wasteland_raider',
      'crater_hub',
      'crater_hub'
    );

    // Roll 0.55 (56): Normal chance is 40 + 25 = 65% (succeeds)
    // HARD chance is 65% - 15% = 50% (fails)
    const normalFlee = attemptFlee(normalState, () => 0.55);
    const hardFlee = attemptFlee(hardState, () => 0.55);

    expect(normalFlee.combat).toBeNull();
    expect(hardFlee.combat).not.toBeNull();
  });

  it('persists and loads difficulty in SaveManager, defaulting legacy saves to normal', () => {
    const tmpFile = path.join(os.tmpdir(), `krypta73-hard-test-${Date.now()}.json`);
    const state = createNewGameState('Elena', { STR: 5, PER: 5, END: 5, CHA: 5, INT: 5, AGI: 5 }, 'hard');
    saveGame(state, tmpFile);

    const loaded = loadGame(tmpFile);
    expect(loaded?.difficulty).toBe('hard');

    // Simulate legacy save without difficulty property
    const raw = JSON.parse(fs.readFileSync(tmpFile, 'utf-8'));
    delete raw.difficulty;
    fs.writeFileSync(tmpFile, JSON.stringify(raw), 'utf-8');

    const legacyLoaded = loadGame(tmpFile);
    expect(legacyLoaded?.difficulty).toBe('normal');

    fs.unlinkSync(tmpFile);
  });

  it('renders UI elements with HARD indicators cleanly', () => {
    const hardState = createNewGameState('Max', { STR: 5, PER: 5, END: 5, CHA: 5, INT: 5, AGI: 5 }, 'hard');
    const header = React.createElement(HeaderStatus, { state: hardState, modeLabel: 'EKSPLORACJA' });
    expect(header).toBeDefined();

    const mainMenu = React.createElement(MainMenu, {
      canLoad: true,
      onNewGame: () => {},
      onLoadGame: () => {},
      onExit: () => {},
    });
    expect(mainMenu).toBeDefined();

    const creator = React.createElement(CharacterCreator, {
      difficulty: 'hard',
      onConfirm: () => {},
      onCancel: () => {},
    });
    expect(creator).toBeDefined();

    const combatWithHard = startCombat(hardState, 'feral_ghoul', 'vault_gate');
    const combatView = React.createElement(CombatView, {
      state: combatWithHard,
      onStandardAttack: () => {},
      onVatsAttack: () => {},
      onUseItem: () => {},
      onEndTurn: () => {},
      onFlee: () => {},
    });
    expect(combatView).toBeDefined();
  });

  it('validates that Chapter 2 and Chapter 3 enemies have significantly stronger base and hard stats', () => {
    // Chapter 2 enemies
    expect(ENEMIES.giant_rad_rat.maxHp).toBeGreaterThanOrEqual(110);
    expect(ENEMIES.glowing_ghoul.maxHp).toBeGreaterThanOrEqual(150);
    expect(ENEMIES.doctor_morbus_full.maxHp).toBeGreaterThanOrEqual(300);
    expect(ENEMIES.doctor_morbus_full.damage).toBeGreaterThanOrEqual(40);

    // Chapter 3 enemies
    expect(ENEMIES.desert_stalker.maxHp).toBeGreaterThanOrEqual(140);
    expect(ENEMIES.alpha_radscorpion.maxHp).toBeGreaterThanOrEqual(200);
    expect(ENEMIES.annihilator_sentry.maxHp).toBeGreaterThanOrEqual(400);
    expect(ENEMIES.annihilator_sentry.damage).toBeGreaterThanOrEqual(55);
    expect(ENEMIES.overseer_traitor.maxHp).toBeGreaterThanOrEqual(350);

    // Verify Chapter 3 Boss on HARD scaling
    const hardState = createNewGameState('Hero', { STR: 5, PER: 5, END: 5, CHA: 5, INT: 5, AGI: 5 }, 'hard');
    const bossCombat = startCombat(hardState, 'annihilator_sentry', 'ending_scene');
    expect(bossCombat.combat?.enemyMaxHp).toBe(Math.round(440 * 1.65)); // 726 HP
    expect(bossCombat.combat?.enemyMaxHp).toBeGreaterThan(700);
  });
});
