import { describe, it, expect } from 'vitest';
import path from 'node:path';
import os from 'node:os';
import fs from 'node:fs';
import { createNewGameState, createInitialAttributes } from '../src/engine/CharacterSystem.js';
import { getCurrentScene, getAvailableChoices, selectChoice, getSceneDescription } from '../src/engine/DialogueEngine.js';
import { travelToLocation } from '../src/engine/WastelandTravel.js';
import { saveGame, loadGame, hasSaveGame } from '../src/engine/SaveManager.js';
import { SCENES, LOCATIONS } from '../src/content/scenes.js';
import { ITEMS } from '../src/content/items.js';
import { ENEMIES } from '../src/content/enemies.js';
import { QUESTS } from '../src/content/quests.js';

describe('DialogueEngine, WastelandTravel, SaveManager & Content Integrity', () => {
  it('validates that all scene links, items, enemies, and quests in SCENES exist', () => {
    for (const scene of Object.values(SCENES)) {
      expect(LOCATIONS[scene.locationId]).toBeDefined();
      for (const choice of scene.choices) {
        expect(
          SCENES[choice.nextSceneId] || choice.nextSceneId === '__RESUME_TRAVEL__',
          `Missing nextSceneId "${choice.nextSceneId}" in scene "${scene.id}"`
        ).toBeTruthy();
        if (choice.failSceneId) {
          expect(
            SCENES[choice.failSceneId] || choice.failSceneId === '__RESUME_TRAVEL__',
            `Missing failSceneId "${choice.failSceneId}" in scene "${scene.id}"`
          ).toBeTruthy();
        }
        if (choice.requiredItem) {
          expect(ITEMS[choice.requiredItem]).toBeDefined();
        }
        if (choice.effect?.startCombat) {
          expect(ENEMIES[choice.effect.startCombat.enemyId]).toBeDefined();
          expect(
            SCENES[choice.effect.startCombat.victorySceneId] ||
              choice.effect.startCombat.victorySceneId === '__RESUME_TRAVEL__'
          ).toBeTruthy();
        }
        if (choice.effect?.setQuestState) {
          expect(QUESTS[choice.effect.setQuestState.questId]).toBeDefined();
        }
      }
    }
  });

  it('evaluates choices and transitions scenes with effects', () => {
    let state = createNewGameState('Max', createInitialAttributes());
    const intro = getCurrentScene(state);
    expect(intro.id).toBe('vault_intro');
    const choices = getAvailableChoices(state, intro.choices);
    expect(choices.length).toBeGreaterThan(0);

    state = selectChoice(state, choices[0], () => 0.1);
    expect(state.currentSceneId).not.toBe('vault_intro');

    // Non-combat lethal hpDelta transitions to game_over_death
    state.character.hp = 5;
    state = selectChoice(
      state,
      {
        id: 'lethal_choice',
        text: 'Dotknij odsłoniętego rdzenia',
        nextSceneId: 'vault_atrium',
        effect: { hpDelta: -10 },
      },
      () => 0.5
    );
    expect(state.currentSceneId).toBe('game_over_death');
  });

  it('handles wasteland travel with and without random encounters', () => {
    let state = createNewGameState('Max', createInitialAttributes());
    // high rng => direct arrival without encounter
    const direct = travelToLocation(state, 'rusty_crater', () => 0.95);
    expect(direct.currentLocationId).toBe('rusty_crater');
    expect(direct.currentSceneId).toBe(LOCATIONS.rusty_crater.entrySceneId);

    // low rng => triggers random encounter scene
    const enc = travelToLocation(state, 'rusty_crater', () => 0.1);
    expect(enc.pendingDestinationSceneId).toBe(LOCATIONS.rusty_crater.entrySceneId);
    expect(enc.currentSceneId.startsWith('encounter_')).toBe(true);
  });

  it('saves and loads game state to JSON accurately', () => {
    const tmpFile = path.join(os.tmpdir(), `krypta73-test-${Date.now()}.json`);
    const state = createNewGameState('Arya', createInitialAttributes());
    saveGame(state, tmpFile);
    expect(hasSaveGame(tmpFile)).toBe(true);

    const loaded = loadGame(tmpFile);
    expect(loaded?.character.name).toBe('Arya');
    fs.unlinkSync(tmpFile);
  });

  it('supports full Chapter 2 New Reno progression (casino, hospital, bandits alliance, sewers, boss, and Deputy Overseer ending)', () => {
    expect(LOCATIONS.new_reno).toBeDefined();
    expect(QUESTS.ch2_new_reno).toBeDefined();
    expect(QUESTS.side_reno_hospital).toBeDefined();
    expect(QUESTS.side_reno_jackals).toBeDefined();
    expect(ENEMIES.giant_rad_rat).toBeDefined();
    expect(ENEMIES.glowing_ghoul).toBeDefined();
    expect(ENEMIES.doctor_morbus_full).toBeDefined();
    expect(ENEMIES.doctor_morbus_weakened).toBeDefined();

    // Chapter 1 completion transitions to Chapter 2 instead of ending immediately
    expect(SCENES.ending_vault_saved.isEnding).toBeFalsy();
    expect(SCENES.ending_wasteland_alliance.isEnding).toBeFalsy();
    expect(SCENES.ending_deputy_overseer.choices.length).toBeGreaterThan(0);

    let state = createNewGameState('Max', { STR: 7, PER: 7, END: 7, CHA: 8, INT: 8, AGI: 7 });
    state.currentSceneId = 'ending_vault_saved';
    const ch1EndScene = getCurrentScene(state);
    const startCh2Choice = ch1EndScene.choices.find((c) => c.id === 'start_chapter_2_vault');
    expect(startCh2Choice).toBeDefined();

    state = selectChoice(state, startCh2Choice!, () => 0.1);
    expect(state.flags.new_reno_unlocked).toBe(true);
    expect(state.quests.ch2_new_reno?.status).toBe('active');

    // Travel to New Reno
    state = travelToLocation(state, 'new_reno', () => 0.99);
    expect(state.currentLocationId).toBe('new_reno');
    expect(state.currentSceneId).toBe('reno_gates');
  });

  it('supports full Chapter 3 progression (Arroyo, NCR quests, Vault 13 water chip, and Savior of Vault 73 ending)', () => {
    expect(LOCATIONS.arroyo).toBeDefined();
    expect(LOCATIONS.ncr).toBeDefined();
    expect(LOCATIONS.vault_13).toBeDefined();
    expect(QUESTS.ch3_water_chip).toBeDefined();
    expect(QUESTS.side_arroyo_pasture).toBeDefined();
    expect(QUESTS.side_ncr_crimes).toBeDefined();

    expect(ENEMIES.alpha_radscorpion).toBeDefined();
    expect(ENEMIES.desert_stalker).toBeDefined();
    expect(ENEMIES.irradiated_wanderer).toBeDefined();
    expect(ENEMIES.annihilator_sentry).toBeDefined();

    expect(ITEMS.water_chip).toBeDefined();
    expect(ITEMS.sniper_rifle).toBeDefined();
    expect(ITEMS.minigun).toBeDefined();
    expect(ITEMS.ranger_armor).toBeDefined();
    expect(ITEMS.vault_13_map).toBeDefined();

    // Scene ending_deputy_overseer now has choice to start Chapter 3 crisis
    expect(SCENES.ending_deputy_overseer.isEnding).toBeFalsy();
    expect(SCENES.ending_water_chip_saved.isEnding).toBeFalsy();
    expect(SCENES.epilogue_slideshow.isEnding).toBe(true);

    let state = createNewGameState('Max', { STR: 8, PER: 8, END: 8, CHA: 8, INT: 8, AGI: 8 });
    state.currentSceneId = 'ending_deputy_overseer';
    const ch2EndScene = getCurrentScene(state);
    const startCh3Choice = ch2EndScene.choices.find((c) => c.id === 'start_chapter_3_crisis');
    expect(startCh3Choice).toBeDefined();

    state = selectChoice(state, startCh3Choice!, () => 0.1);
    expect(state.flags.arroyo_unlocked).toBe(true);
    expect(state.quests.ch3_water_chip?.status).toBe('active');

    // Travel to Arroyo
    state = travelToLocation(state, 'arroyo', () => 0.99);
    expect(state.currentLocationId).toBe('arroyo');
    expect(state.currentSceneId).toBe('arroyo_village');
  });

  it('prevents infinite loops (Chapter 2 seal consumption, casino roulette limit, Salvatore persuasion) and supports Enclave conspiracy & Fallout slideshow epilogue', () => {
    let state = createNewGameState('Max', { STR: 8, PER: 8, END: 8, CHA: 8, INT: 8, AGI: 8 });

    // 1. Chapter 2 seal is consumed and cannot be repeated
    state.character.inventory.reno_purifier_seal = 1;
    state.currentSceneId = 'vault_atrium';
    const ch2TurnIn = getAvailableChoices(state, SCENES.vault_atrium.choices).find(
      (c) => c.id === 'return_hero_chapter_2'
    );
    expect(ch2TurnIn).toBeDefined();
    state = selectChoice(state, ch2TurnIn!);
    expect(state.character.inventory.reno_purifier_seal).toBeUndefined();
    expect(state.flags.ch2_completed).toBe(true);
    state.currentSceneId = 'vault_atrium';
    const ch2TurnInAgain = getAvailableChoices(state, SCENES.vault_atrium.choices).find(
      (c) => c.id === 'return_hero_chapter_2'
    );
    expect(ch2TurnInAgain).toBeUndefined();

    // 2. Casino roulette has table limit of 3 wins, and failed Salvatore persuasion cannot be repeated
    state.currentSceneId = 'reno_casino';
    state.character.caps = 200;
    for (let i = 0; i < 3; i++) {
      const gamble = getAvailableChoices(state, SCENES.reno_casino.choices).find(
        (c) => c.id === 'gamble_roulette'
      );
      expect(gamble).toBeDefined();
      state = selectChoice(state, gamble!, () => 0.1);
    }
    const gambleAfterLimit = getAvailableChoices(state, SCENES.reno_casino.choices).find(
      (c) => c.id === 'gamble_roulette'
    );
    expect(gambleAfterLimit).toBeUndefined();

    // Failed Salvatore persuasion
    state.character.attributes.CHA = 1;
    const persuadeSalvatore = getAvailableChoices(state, SCENES.reno_casino.choices).find(
      (c) => c.id === 'persuade_salvatore_note'
    );
    expect(persuadeSalvatore).toBeDefined();
    state = selectChoice(state, persuadeSalvatore!, () => 0.99);
    expect(state.flags.salvatore_insulted).toBe(true);
    const persuadeSalvatoreAgain = getAvailableChoices(state, SCENES.reno_casino.choices).find(
      (c) => c.id === 'persuade_salvatore_note'
    );
    expect(persuadeSalvatoreAgain).toBeUndefined();

    // 3. Water timer decrements during Chapter 3 travel
    state.flags.arroyo_unlocked = true;
    const beforeWater = state.counters?.water_days_left ?? 60;
    state = travelToLocation(state, 'arroyo', () => 0.99);
    expect(state.counters?.water_days_left).toBe(beforeWater - 6);

    // 4. Vault 13 has 4 distinct solutions + Talisman & Holomap utility
    state.character.inventory.vault_13_map = 1;
    state.character.inventory.arroyo_talisman = 1;
    state.character.inventory.water_chip_replicator = 1;
    state.currentSceneId = 'vault_13_gate';
    const holomapChoice = getAvailableChoices(state, SCENES.vault_13_gate.choices).find(
      (c) => c.id === 'use_holomap_secret_tunnel'
    );
    expect(holomapChoice).toBeDefined();
    state = selectChoice(state, holomapChoice!);
    expect(state.currentSceneId).toBe('vault_13_sanctuary');

    const copyChipChoice = getAvailableChoices(state, SCENES.vault_13_sanctuary.choices).find(
      (c) => c.id === 'v13_solution_copy_chip'
    );
    expect(copyChipChoice).toBeDefined();
    state = selectChoice(state, copyChipChoice!);
    expect(state.flags.v13_chip_copied).toBe(true);
    expect(state.character.inventory.water_chip).toBe(1);

    // 5. Enclave conspiracy confrontation & Fallout-style slideshow epilogue
    state.counters!.enclave_clues = 3;
    state.currentSceneId = 'ending_water_chip_saved';
    const confrontChoice = getAvailableChoices(state, SCENES.ending_water_chip_saved.choices).find(
      (c) => c.id === 'confront_traitor_with_evidence'
    );
    expect(confrontChoice).toBeDefined();

    state.flags.traitor_exposed = true;
    state.currentSceneId = 'epilogue_slideshow';
    const epilogueText = getSceneDescription(state, SCENES.epilogue_slideshow);
    expect(epilogueText).toContain('KRONIKI PUSTKOWI');
    expect(epilogueText).toContain('KRYPTA 73');
    expect(epilogueText).toContain('METROPOLIA NEW RENO');
    expect(epilogueText).toContain('FINAŁ SPISKU ENKLAWY');
  });

  it('provides an exit choice and dynamic description in fortress_gate when returning after defeating Korg/Zmija', () => {
    let state = createNewGameState('Max', createInitialAttributes());

    // Initially fortress_gate has Korg combat/diplomacy choices AND leave_fortress
    state.currentLocationId = 'rust_fortress';
    state.currentSceneId = 'fortress_gate';
    let available = getAvailableChoices(state, SCENES.fortress_gate.choices);
    expect(available.some((c) => c.id === 'leave_fortress')).toBe(true);
    expect(available.some((c) => c.id === 'fight_korg')).toBe(true);

    // After resolving Korg and defeating Zmija (viper_defeated_crater)
    state.flags.korg_resolved = true;
    state.flags.viper_defeated_crater = true;

    available = getAvailableChoices(state, SCENES.fortress_gate.choices);
    // Korg confrontation choices should no longer be available
    expect(available.some((c) => c.id === 'fight_korg')).toBe(false);
    expect(available.some((c) => c.id === 'diplomacy_korg')).toBe(false);
    expect(available.some((c) => c.id === 'hack_fortress_turrets')).toBe(false);

    // leave_fortress must be available and open the world map
    const exitChoice = available.find((c) => c.id === 'leave_fortress');
    expect(exitChoice).toBeDefined();
    expect(exitChoice?.effect?.openWorldMap).toBe(true);

    // Description must reflect post-viper/post-korg ruins
    const description = getSceneDescription(state, SCENES.fortress_gate);
    expect(description).toContain('Mściwej Żmii');
    expect(description).not.toContain('Korg ryczy');
  });

  it('prevents fighting Doctor Morbus repeatedly after defeat, sparing, or execution', async () => {
    let state = createNewGameState('Max', { STR: 8, PER: 8, END: 8, CHA: 8, INT: 8, AGI: 8 });
    state.flags.sewer_rats_cleared = true;
    state.flags.sewer_mid_ghoul_cleared = true;
    state.flags.sewer_mid_devourer_cleared = true;

    // 1. Before confronting Morbus: combat choices are available in reno_sewers_boss
    state.currentSceneId = 'reno_sewers_boss';
    let bossChoices = getAvailableChoices(state, SCENES.reno_sewers_boss.choices);
    expect(bossChoices.some((c) => c.id === 'boss_solo_full_fight')).toBe(true);
    expect(bossChoices.some((c) => c.id === 'boss_hack_pumps')).toBe(true);
    expect(bossChoices.some((c) => c.id === 'retreat_from_boss_to_mid')).toBe(true);
    expect(bossChoices.some((c) => c.id === 'go_to_purifier_station')).toBe(false);

    // Initial description shows Morbus shouting
    let desc = getSceneDescription(state, SCENES.reno_sewers_boss);
    expect(desc).toContain('Nikt mnie nie powstrzyma');

    // 2. In reno_sewers_mid, enter_morbus_lair is available before defeat
    state.currentSceneId = 'reno_sewers_mid';
    let midChoices = getAvailableChoices(state, SCENES.reno_sewers_mid.choices);
    expect(midChoices.some((c) => c.id === 'enter_morbus_lair')).toBe(true);
    expect(midChoices.some((c) => c.id === 'enter_cleared_morbus_lair')).toBe(false);

    // 3. Fight and defeat Morbus in combat
    const fightChoice = bossChoices.find((c) => c.id === 'boss_solo_full_fight')!;
    state = selectChoice(state, fightChoice);
    expect(state.combat).not.toBeNull();
    expect(state.combat?.enemyId).toBe('doctor_morbus_full');

    // Reduce enemy HP to 0 and perform finishing blow
    state.combat!.enemyHp = 1;
    const { performPlayerAttack } = await import('../src/engine/CombatEngine.js');
    state = performPlayerAttack(state, 'standard', 'torso', () => 0.1);

    expect(state.combat).toBeNull();
    expect(state.currentSceneId).toBe('reno_sewers_victory');
    expect(state.flags.morbus_defeated).toBe(true);
    expect(state.character.inventory.reno_purifier_seal).toBe(1);
    expect(state.quests.ch2_new_reno?.stage).toBe('return_overseer');

    // 4. Now Morbus is defeated. Re-checking reno_sewers_boss:
    state.currentSceneId = 'reno_sewers_boss';
    bossChoices = getAvailableChoices(state, SCENES.reno_sewers_boss.choices);
    expect(bossChoices.some((c) => c.id === 'boss_solo_full_fight')).toBe(false);
    expect(bossChoices.some((c) => c.id === 'boss_attack_with_jackals')).toBe(false);
    expect(bossChoices.some((c) => c.id === 'boss_use_hospital_serum')).toBe(false);
    expect(bossChoices.some((c) => c.id === 'boss_hack_pumps')).toBe(false);
    expect(bossChoices.some((c) => c.id === 'retreat_from_boss_to_mid')).toBe(false);

    // Navigation choices are now available instead
    expect(bossChoices.some((c) => c.id === 'go_to_purifier_station')).toBe(true);
    expect(bossChoices.some((c) => c.id === 'leave_boss_lair_to_mid')).toBe(true);
    expect(bossChoices.some((c) => c.id === 'leave_boss_lair_to_city')).toBe(true);

    desc = getSceneDescription(state, SCENES.reno_sewers_boss);
    expect(desc).toContain('definitywnie pokonany');
    expect(desc).not.toContain('Nikt mnie nie powstrzyma');

    // 5. In reno_sewers_mid, enter_morbus_lair is forbidden, enter_cleared_morbus_lair is available
    state.currentSceneId = 'reno_sewers_mid';
    midChoices = getAvailableChoices(state, SCENES.reno_sewers_mid.choices);
    expect(midChoices.some((c) => c.id === 'enter_morbus_lair')).toBe(false);
    expect(midChoices.some((c) => c.id === 'enter_cleared_morbus_lair')).toBe(true);

    // Using enter_cleared_morbus_lair does NOT demote quest back to defeat_morbus
    const clearedChoice = midChoices.find((c) => c.id === 'enter_cleared_morbus_lair')!;
    state = selectChoice(state, clearedChoice);
    expect(state.currentSceneId).toBe('reno_sewers_boss');
    expect(state.quests.ch2_new_reno?.stage).toBe('return_overseer');

    // 6. Leaving sewers to reno_gates does NOT demote quest stage to investigate_city
    state.currentSceneId = 'reno_gates';
    const gatesScene = getCurrentScene(state);
    if (gatesScene.onEnterEffect) {
      state = selectChoice(state, {
        id: 'dummy',
        text: 'enter gates',
        nextSceneId: 'reno_gates',
        effect: gatesScene.onEnterEffect,
      });
    }
    expect(state.quests.ch2_new_reno?.stage).toBe('return_overseer');

    // 7. Test sparing or execution: choices cannot be repeated
    state.currentSceneId = 'reno_sewers_victory';
    const victoryChoices = getAvailableChoices(state, SCENES.reno_sewers_victory.choices);
    const executeChoice = victoryChoices.find((c) => c.id === 'execute_morbus')!;
    expect(executeChoice).toBeDefined();
    state = selectChoice(state, executeChoice);
    expect(state.flags.morbus_killed).toBe(true);
    expect(state.flags.morbus_fate_decided).toBe(true);

    // Now fate decision choices must be hidden in victory scene
    const postFateChoices = getAvailableChoices(state, SCENES.reno_sewers_victory.choices);
    expect(postFateChoices.some((c) => c.id === 'execute_morbus')).toBe(false);
    expect(postFateChoices.some((c) => c.id === 'spare_morbus_testify')).toBe(false);
    expect(postFateChoices.some((c) => c.id === 'spare_morbus_hospital')).toBe(false);

    // Description reflects execution
    const victoryDesc = getSceneDescription(state, SCENES.reno_sewers_victory);
    expect(victoryDesc).toContain('Ciało pokonanego Doktora Morbusa');

    // Boss room description also reflects execution
    state.currentSceneId = 'reno_sewers_boss';
    desc = getSceneDescription(state, SCENES.reno_sewers_boss);
    expect(desc).toContain('martwego Doktora Morbusa');

    // 8. Legacy state check: even if only morbus_fate_decided/morbus_spared is present without morbus_defeated flag
    delete state.flags.morbus_defeated;
    state.flags.morbus_spared = true;
    delete state.flags.morbus_killed;
    bossChoices = getAvailableChoices(state, SCENES.reno_sewers_boss.choices);
    expect(bossChoices.some((c) => c.id === 'boss_solo_full_fight')).toBe(false);
    expect(bossChoices.some((c) => c.id === 'go_to_purifier_station')).toBe(true);
    desc = getSceneDescription(state, SCENES.reno_sewers_boss);
    expect(desc).toContain('kapitulacji i złożenia zeznań');
  });
});
