import { ENCOUNTERS } from '../content/encounters.js';
import { LOCATIONS, SCENES } from '../content/scenes.js';
import { GameState, LocationId } from '../types/game.js';

export function travelToLocation(
  state: GameState,
  destinationId: LocationId,
  rng: () => number = Math.random
): GameState {
  const location = LOCATIONS[destinationId];
  if (!location) return state;
  if (location.requiredFlag && !state.flags[location.requiredFlag]) {
    return state;
  }

  if (state.currentLocationId !== destinationId && rng() <= 0.65 && ENCOUNTERS.length > 0) {
    const idx = Math.floor(rng() * ENCOUNTERS.length);
    const encounter = ENCOUNTERS[idx] ?? ENCOUNTERS[0];
    return {
      ...state,
      currentLocationId: destinationId,
      currentSceneId: encounter.id,
      pendingDestinationSceneId: location.entrySceneId,
      eventLog: [
        ...state.eventLog.slice(-19),
        `Podróż do: ${location.name} — nieoczekiwane spotkanie na Pustkowiach!`,
      ],
    };
  }

  return {
    ...state,
    currentLocationId: destinationId,
    currentSceneId: location.entrySceneId,
    pendingDestinationSceneId: null,
    eventLog: [...state.eventLog.slice(-19), `Dotarto do lokacji: ${location.name}.`],
  };
}

export function continueAfterEncounter(state: GameState): GameState {
  const destSceneId = state.pendingDestinationSceneId ?? LOCATIONS[state.currentLocationId].entrySceneId;
  const scene = SCENES[destSceneId] ?? SCENES.vault_atrium;
  return {
    ...state,
    currentSceneId: scene.id,
    currentLocationId: scene.locationId,
    pendingDestinationSceneId: null,
  };
}
