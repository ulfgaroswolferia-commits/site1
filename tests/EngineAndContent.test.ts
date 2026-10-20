import { describe, it, expect } from 'vitest';
import path from 'node:path';
import os from 'node:os';
import fs from 'node:fs';
import { createNewGameState, createInitialAttributes } from '../src/engine/CharacterSystem.js';
import { getCurrentScene, getAvailableChoices, selectChoice } from '../src/engine/DialogueEngine.js';
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
});
