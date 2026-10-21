import fs from 'node:fs';
import path from 'node:path';
import { GameState } from '../types/game.js';

const DEFAULT_SAVE_PATH = path.resolve(process.cwd(), 'saves', 'savegame.json');

export function saveGame(state: GameState, filePath: string = DEFAULT_SAVE_PATH): boolean {
  try {
    const dir = path.dirname(filePath);
    if (!fs.existsSync(dir)) {
      fs.mkdirSync(dir, { recursive: true });
    }
    fs.writeFileSync(filePath, JSON.stringify(state, null, 2), 'utf-8');
    return true;
  } catch {
    return false;
  }
}

export function hasSaveGame(filePath: string = DEFAULT_SAVE_PATH): boolean {
  return fs.existsSync(filePath);
}

export function loadGame(filePath: string = DEFAULT_SAVE_PATH): GameState | null {
  try {
    if (!fs.existsSync(filePath)) return null;
    const raw = fs.readFileSync(filePath, 'utf-8');
    const parsed = JSON.parse(raw) as GameState;
    if (!parsed || !parsed.character || !parsed.currentSceneId || !parsed.character.attributes) {
      return null;
    }
    if (!parsed.counters) {
      parsed.counters = {
        vault_openness: 0,
        water_days_left: 60,
        enclave_clues: 0,
        roulette_wins: 0,
      };
    }
    const inv = parsed.character.inventory ?? {};
    if (
      inv.ammo_10mm === undefined &&
      inv.ammo_energy === undefined &&
      inv.ammo_rifle === undefined
    ) {
      inv.ammo_10mm = 20;
      if (inv.laser_rifle || inv.plasma_rifle) inv.ammo_energy = 12;
      if (inv.sniper_rifle || inv.minigun) inv.ammo_rifle = 15;
    }
    return parsed;
  } catch {
    return null;
  }
}
