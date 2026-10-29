import { ENCOUNTERS } from '../content/encounters.js';
import { LOCATIONS, SCENES } from '../content/scenes.js';
export function travelToLocation(state, destinationId, rng = Math.random) {
    const location = LOCATIONS[destinationId];
    if (!location)
        return state;
    if (location.requiredFlag && !state.flags[location.requiredFlag]) {
        return state;
    }
    let nextState = state;
    if (state.currentLocationId !== destinationId && state.flags.arroyo_unlocked) {
        const currentDays = state.counters?.water_days_left ?? 60;
        const nextDays = Math.max(0, currentDays - 6);
        const nextFlags = { ...state.flags };
        if (nextDays <= 24)
            nextFlags.water_crisis_severe = true;
        if (nextDays <= 0)
            nextFlags.water_crisis_casualties = true;
        nextState = {
            ...state,
            flags: nextFlags,
            counters: {
                ...(state.counters ?? {}),
                water_days_left: nextDays,
            },
        };
    }
    if (nextState.currentLocationId !== destinationId && rng() <= 0.65 && ENCOUNTERS.length > 0) {
        const idx = Math.floor(rng() * ENCOUNTERS.length);
        const encounter = ENCOUNTERS[idx] ?? ENCOUNTERS[0];
        return {
            ...nextState,
            currentLocationId: destinationId,
            currentSceneId: encounter.id,
            pendingDestinationSceneId: location.entrySceneId,
            eventLog: [
                ...nextState.eventLog.slice(-19),
                `Podróż do: ${location.name} — nieoczekiwane spotkanie na Pustkowiach!`,
            ],
        };
    }
    return {
        ...nextState,
        currentLocationId: destinationId,
        currentSceneId: location.entrySceneId,
        pendingDestinationSceneId: null,
        eventLog: [...nextState.eventLog.slice(-19), `Dotarto do lokacji: ${location.name}.`],
    };
}
export function continueAfterEncounter(state) {
    const destSceneId = state.pendingDestinationSceneId ?? LOCATIONS[state.currentLocationId].entrySceneId;
    const scene = SCENES[destSceneId] ?? SCENES.vault_atrium;
    return {
        ...state,
        currentSceneId: scene.id,
        currentLocationId: scene.locationId,
        pendingDestinationSceneId: null,
    };
}
