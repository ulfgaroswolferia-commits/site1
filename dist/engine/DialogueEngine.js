import { SCENES } from '../content/scenes.js';
import { applyRadiation, getEffectiveMaxHp, grantXp, rollSkillCheck, } from './CharacterSystem.js';
import { startCombat } from './CombatEngine.js';
import { addItem, removeItem } from './InventorySystem.js';
import { continueAfterEncounter } from './WastelandTravel.js';
export function getCurrentScene(state) {
    return SCENES[state.currentSceneId] ?? SCENES.vault_intro;
}
export function getSceneDescription(state, scene) {
    if (scene.dynamicDescription) {
        return scene.dynamicDescription(state);
    }
    return scene.description;
}
export function getAvailableChoices(state, choices) {
    const counters = state.counters ?? {};
    return choices.filter((c) => {
        if (c.requiredFlag && !state.flags[c.requiredFlag])
            return false;
        if (c.forbiddenFlag && state.flags[c.forbiddenFlag])
            return false;
        if (c.requiredItem && (state.character.inventory[c.requiredItem] ?? 0) <= 0)
            return false;
        if (c.requiredCaps && state.character.caps < c.requiredCaps)
            return false;
        if (c.minCounter && (counters[c.minCounter.key] ?? 0) < c.minCounter.value)
            return false;
        if (c.maxCounter && (counters[c.maxCounter.key] ?? 0) > c.maxCounter.value)
            return false;
        return true;
    });
}
export function applyChoiceEffect(state, effect) {
    if (!effect)
        return state;
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
        const prevStage = next.quests[questId]?.stage ?? '';
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
        const resolvedVictory = victorySceneId === '__RESUME_TRAVEL__' && next.pendingDestinationSceneId
            ? next.pendingDestinationSceneId
            : victorySceneId;
        const resolvedFlee = fleeSceneId === '__RESUME_TRAVEL__' && next.pendingDestinationSceneId
            ? next.pendingDestinationSceneId
            : fleeSceneId;
        next = startCombat(next, enemyId, resolvedVictory, resolvedFlee);
    }
    return next;
}
function transitionToScene(state, targetSceneId) {
    if (targetSceneId === '__RESUME_TRAVEL__') {
        return continueAfterEncounter(state);
    }
    const scene = SCENES[targetSceneId];
    if (!scene)
        return state;
    let next = {
        ...state,
        currentSceneId: scene.id,
        currentLocationId: scene.locationId,
    };
    if (scene.onEnterEffect) {
        next = applyChoiceEffect(next, scene.onEnterEffect);
    }
    return next;
}
export function selectChoice(state, choice, rng = Math.random) {
    if (choice.skillCheck) {
        const result = rollSkillCheck(state, choice.skillCheck, rng);
        if (result.success) {
            let next = {
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
        }
        else {
            let next = {
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
