import { SCENES } from '../content/scenes.js';
import { ChoiceEffect, GameState, SceneChoice, SceneDefinition } from '../types/game.js';
import {
  applyRadiation,
  getEffectiveMaxHp,
  grantXp,
  rollSkillCheck,
} from './CharacterSystem.js';
import { startCombat } from './CombatEngine.js';
import { addItem, removeItem } from './InventorySystem.js';
import { continueAfterEncounter } from './WastelandTravel.js';

export function getCurrentScene(state: GameState): SceneDefinition {
  return SCENES[state.currentSceneId] ?? SCENES.vault_intro;
}

export function getSceneDescription(state: GameState, scene: SceneDefinition): string {
  if (scene.dynamicDescription) {
    return scene.dynamicDescription(state);
  }
  return scene.description;
}

export function isMorbusDefeated(state: GameState): boolean {
  return Boolean(
    state.flags.morbus_defeated ||
    state.flags.morbus_fate_decided ||
    state.flags.morbus_spared ||
    state.flags.morbus_killed ||
    state.flags.morbus_in_hospital
  );
}

export function getAvailableChoices(state: GameState, choices: SceneChoice[]): SceneChoice[] {
  const counters = state.counters ?? {};
  const morbusDefeated = isMorbusDefeated(state);

  return choices.filter((c) => {
    if (c.requiredFlag) {
      if (c.requiredFlag === 'morbus_defeated') {
        if (!morbusDefeated) return false;
      } else if (!state.flags[c.requiredFlag]) {
        return false;
      }
    }
    if (c.forbiddenFlag) {
      if (c.forbiddenFlag === 'morbus_defeated') {
        if (morbusDefeated) return false;
      } else if (state.flags[c.forbiddenFlag]) {
        return false;
      }
    }
    if (c.requiredItem && (state.character.inventory[c.requiredItem] ?? 0) <= 0) return false;
    if (c.requiredCaps && state.character.caps < c.requiredCaps) return false;
    if (c.minCounter && (counters[c.minCounter.key] ?? 0) < c.minCounter.value) return false;
    if (c.maxCounter && (counters[c.maxCounter.key] ?? 0) > c.maxCounter.value) return false;
    return true;
  });
}

export function applyChoiceEffect(state: GameState, effect?: ChoiceEffect): GameState {
  if (!effect) return state;
  let next = { ...state };

  if (effect.radDelta) {
    next = applyRadiation(next, effect.radDelta);
  }
  if (effect.hpDelta) {
    const effMax = getEffectiveMaxHp(next.character.maxHp, next.character.rad);
    const newHp = Math.max(0, Math.min(effMax, next.character.hp + effect.hpDelta));
    next = {
      ...next,
      character: {
        ...next.character,
        hp: newHp,
      },
    };
  }
  if (effect.capsDelta) {
    next = {
      ...next,
      character: {
        ...next.character,
        caps: Math.max(0, next.character.caps + effect.capsDelta),
      },
    };
  }
  if (effect.addItems) {
    for (const [itemId, qty] of Object.entries(effect.addItems)) {
      next = addItem(next, itemId, qty);
    }
  }
  if (effect.removeItems) {
    for (const [itemId, qty] of Object.entries(effect.removeItems)) {
      next = removeItem(next, itemId, qty);
    }
  }
  if (effect.setFlags) {
    next = {
      ...next,
      flags: {
        ...next.flags,
        ...effect.setFlags,
      },
    };
  }
  if (effect.incrementCounters) {
    const nextCounters = { ...(next.counters ?? {}) };
    for (const [key, delta] of Object.entries(effect.incrementCounters)) {
      nextCounters[key] = (nextCounters[key] ?? 0) + delta;
    }
    next = {
      ...next,
      counters: nextCounters,
    };
  }
  if (effect.setQuestState) {
    const { questId, status, stage } = effect.setQuestState;
    const currentQuest = next.quests[questId];
    const prevStatus = currentQuest?.status;
    const prevStage = currentQuest?.stage ?? '';

    if (prevStatus === 'completed' && status !== 'completed') {
      // Keep completed status
    } else if (
      questId === 'ch2_new_reno' &&
      stage === 'investigate_city' &&
      (prevStage === 'defeat_morbus' || prevStage === 'return_overseer' || prevStatus === 'completed')
    ) {
      // Do not demote ch2_new_reno if player has progressed past investigate_city
    } else if (
      questId === 'ch2_new_reno' &&
      stage === 'defeat_morbus' &&
      (prevStage === 'return_overseer' || prevStatus === 'completed' || isMorbusDefeated(next))
    ) {
      // Do not demote ch2_new_reno if Morbus has already been defeated
    } else {
      next = {
        ...next,
        quests: {
          ...next.quests,
          [questId]: {
            status,
            stage: stage ?? prevStage,
          },
        },
      };
    }
  }
  if (effect.xpDelta) {
    next = grantXp(next, effect.xpDelta);
  }
  if (effect.changeLocation) {
    next = {
      ...next,
      currentLocationId: effect.changeLocation,
    };
  }
  if (effect.openMerchant) {
    next = {
      ...next,
      activeMerchantId: effect.openMerchant,
    };
  }
  if (effect.startCombat) {
    const { enemyId, victorySceneId, fleeSceneId } = effect.startCombat;
    const resolvedVictory =
      victorySceneId === '__RESUME_TRAVEL__' && next.pendingDestinationSceneId
        ? next.pendingDestinationSceneId
        : victorySceneId;
    const resolvedFlee =
      fleeSceneId === '__RESUME_TRAVEL__' && next.pendingDestinationSceneId
        ? next.pendingDestinationSceneId
        : fleeSceneId;
    next = startCombat(next, enemyId, resolvedVictory, resolvedFlee);
  }

  return next;
}

function transitionToScene(state: GameState, targetSceneId: string): GameState {
  if (targetSceneId === '__RESUME_TRAVEL__') {
    return continueAfterEncounter(state);
  }
  const scene = SCENES[targetSceneId];
  if (!scene) return state;
  let next: GameState = {
    ...state,
    currentSceneId: scene.id,
    currentLocationId: scene.locationId,
  };
  if (scene.onEnterEffect) {
    next = applyChoiceEffect(next, scene.onEnterEffect);
  }
  return next;
}

export function selectChoice(
  state: GameState,
  choice: SceneChoice,
  rng: () => number = Math.random
): GameState {
  if (choice.skillCheck) {
    const result = rollSkillCheck(state, choice.skillCheck, rng);
    if (result.success) {
      let next: GameState = {
        ...state,
        eventLog: [
          ...state.eventLog.slice(-19),
          `[SUKCES ${choice.skillCheck.attribute}] Rzut ${result.roll} <= ${result.chance}%`,
        ],
      };
      next = applyChoiceEffect(next, choice.effect);
      if (next.character.hp <= 0) {
        return { ...next, combat: null, currentSceneId: 'game_over_death' };
      }
      if (!next.combat) {
        next = transitionToScene(next, choice.nextSceneId);
      }
      return next;
    } else {
      let next: GameState = {
        ...state,
        eventLog: [
          ...state.eventLog.slice(-19),
          `[PORAŻKA ${choice.skillCheck.attribute}] Rzut ${result.roll} > ${result.chance}%`,
        ],
      };
      next = applyChoiceEffect(next, choice.failEffect);
      if (next.character.hp <= 0) {
        return { ...next, combat: null, currentSceneId: 'game_over_death' };
      }
      if (!next.combat) {
        next = transitionToScene(next, choice.failSceneId ?? choice.nextSceneId);
      }
      return next;
    }
  }

  let next = applyChoiceEffect(state, choice.effect);
  if (next.character.hp <= 0) {
    return { ...next, combat: null, currentSceneId: 'game_over_death' };
  }
  if (!next.combat && !choice.effect?.openMerchant && !choice.effect?.openWorldMap) {
    next = transitionToScene(next, choice.nextSceneId);
  }
  return next;
}
