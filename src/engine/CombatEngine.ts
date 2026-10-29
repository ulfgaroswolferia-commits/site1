import { ENEMIES } from '../content/enemies.js';
import { ITEMS } from '../content/items.js';
import { BodyPart, GameState } from '../types/game.js';
import { applyRadiation, getMaxAp, grantXp } from './CharacterSystem.js';
import { addItem, removeItem, useConsumable } from './InventorySystem.js';

export function startCombat(
  state: GameState,
  enemyId: string,
  victorySceneId: string,
  fleeSceneId?: string
): GameState {
  const enemy = ENEMIES[enemyId];
  if (!enemy) return state;
  const maxAp = getMaxAp(state.character.attributes);

  return {
    ...state,
    combat: {
      enemyId: enemy.id,
      enemyName: enemy.name,
      enemyHp: enemy.maxHp,
      enemyMaxHp: enemy.maxHp,
      enemyStunned: false,
      enemyWeakenedTurns: 0,
      playerAp: maxAp,
      playerMaxAp: maxAp,
      playerDodgeBonus: 0,
      turn: 1,
      log: [`Rozpoczęto starcie: ${enemy.name}!`],
      victorySceneId,
      fleeSceneId,
      isBoss: Boolean(enemy.isBoss),
    },
  };
}

export function getVatsHitChance(state: GameState, part: BodyPart): number {
  const weapon = state.character.equippedWeaponId
    ? ITEMS[state.character.equippedWeaponId]
    : null;
  const weaponAcc = weapon?.accuracyBonus ?? 0;
  const base = 65 + state.character.attributes.PER * 3 + weaponAcc;

  let modifier =
    (state.character.perks.includes('reno_gunslinger') ? 10 : 0) +
    (state.character.perks.includes('plasma_master') ? 10 : 0);
  if (part === 'head') {
    modifier += -25 + (state.character.perks.includes('vats_sniper') ? 15 : 0);
  } else if (part === 'limbs') {
    modifier += -15;
  }

  return Math.max(15, Math.min(95, base + modifier));
}

function resolveCombatVictory(state: GameState): GameState {
  const combat = state.combat;
  if (!combat) return state;
  const enemy = ENEMIES[combat.enemyId];
  let nextState: GameState = {
    ...state,
    combat: null,
    currentSceneId: combat.victorySceneId,
    character: {
      ...state.character,
      caps: state.character.caps + (enemy?.capsReward ?? 0),
    },
    eventLog: [
      ...state.eventLog.slice(-19),
      `Pokonano: ${combat.enemyName}! (+${enemy?.capsReward ?? 0} kapsli)`,
    ],
  };

  if (enemy?.lootItemId) {
    nextState = addItem(nextState, enemy.lootItemId, 1);
  }
  if (enemy?.ammoDrop) {
    nextState = addItem(nextState, enemy.ammoDrop.ammoId, enemy.ammoDrop.qty);
  }
  if (enemy?.xpReward) {
    nextState = grantXp(nextState, enemy.xpReward);
  }
  return nextState;
}

export function performPlayerAttack(
  state: GameState,
  mode: 'standard' | 'vats',
  part: BodyPart = 'torso',
  rng: () => number = Math.random
): GameState {
  const combat = state.combat;
  if (!combat) return state;

  const weapon = state.character.equippedWeaponId
    ? ITEMS[state.character.equippedWeaponId]
    : null;
  const ammoId = weapon && !weapon.isMelee ? weapon.ammoType : undefined;
  const ammoNeeded = weapon && !weapon.isMelee ? (weapon.ammoPerShot ?? 1) : 0;
  const currentAmmo = ammoId ? (state.character.inventory[ammoId] ?? 0) : 0;
  const outOfAmmo = Boolean(weapon && !weapon.isMelee && ammoId && currentAmmo < ammoNeeded);

  const baseApCost = outOfAmmo ? 3 : (weapon?.apCost ?? 3);
  const apCost = mode === 'vats' ? baseApCost + 1 : baseApCost;
  if (combat.playerAp < apCost) {
    return {
      ...state,
      combat: {
        ...combat,
        log: [...combat.log.slice(-7), 'Brak wystarczających Punktów Akcji (AP)!'],
      },
    };
  }

  let workingState = state;
  if (weapon && !weapon.isMelee && ammoId && !outOfAmmo) {
    workingState = removeItem(state, ammoId, ammoNeeded);
  }

  const perkBonusDmg = workingState.character.perks.includes('plasma_master') ? 10 : 0;
  const baseDamage = outOfAmmo
    ? 10 + workingState.character.attributes.STR + workingState.character.level
    : (weapon?.damage ?? 8) +
      perkBonusDmg +
      (weapon?.isMelee
        ? workingState.character.attributes.STR
        : Math.floor(workingState.character.attributes.PER / 2));

  const targetPart: BodyPart = mode === 'standard' ? 'torso' : part;
  const hitChance = outOfAmmo
    ? Math.max(25, Math.min(90, 65 + workingState.character.attributes.STR * 3))
    : mode === 'standard'
    ? Math.max(
        25,
        Math.min(
          95,
          70 +
            workingState.character.attributes.PER * 3 +
            (weapon?.accuracyBonus ?? 0) +
            (workingState.character.perks.includes('plasma_master') ? 10 : 0)
        )
      )
    : getVatsHitChance(workingState, targetPart);

  const roll = Math.floor(rng() * 100) + 1;
  const remainingAp = combat.playerAp - apCost;
  const noAmmoNotice = outOfAmmo ? ' [BRAK AMUNICJI — uderzenie kolbą!]' : '';

  if (roll > hitChance) {
    return {
      ...workingState,
      combat: {
        ...combat,
        playerAp: remainingAp,
        log: [
          ...combat.log.slice(-7),
          `Chybienie! (${mode === 'vats' ? `V.A.T.S. ${targetPart}` : 'Atak'} - szansa ${hitChance}%)${noAmmoNotice}`,
        ],
      },
    };
  }

  const enemy = ENEMIES[combat.enemyId];
  let mult = 1.0;
  if (mode === 'vats') {
    if (targetPart === 'head') mult = 1.75;
    else if (targetPart === 'torso') mult = 1.15;
    else mult = 0.85;
  }

  const rawDamage = Math.round(baseDamage * mult);
  const finalDamage = Math.max(2, rawDamage - (enemy?.defense ?? 0));
  const nextEnemyHp = Math.max(0, combat.enemyHp - finalDamage);

  let enemyStunned = combat.enemyStunned;
  let enemyWeakenedTurns = combat.enemyWeakenedTurns;
  const extraEffects: string[] = [];

  if (mode === 'vats' && targetPart === 'head' && rng() <= 0.25) {
    enemyStunned = true;
    extraEffects.push('OGŁUSZENIE');
  }
  if (mode === 'vats' && targetPart === 'limbs') {
    enemyWeakenedTurns = 2;
    extraEffects.push('OSŁABIENIE');
  }

  const updatedState: GameState = {
    ...workingState,
    combat: {
      ...combat,
      enemyHp: nextEnemyHp,
      enemyStunned,
      enemyWeakenedTurns,
      playerAp: remainingAp,
      log: [
        ...combat.log.slice(-7),
        `Trafienie za ${finalDamage} pkt obrażeń!${extraEffects.length ? ` [${extraEffects.join(', ')}]` : ''}${noAmmoNotice}`,
      ],
    },
  };

  if (nextEnemyHp <= 0) {
    return resolveCombatVictory(updatedState);
  }
  return updatedState;
}

export function useCombatItem(
  state: GameState,
  itemId: string,
  rng: () => number = Math.random
): GameState {
  const combat = state.combat;
  const item = ITEMS[itemId];
  if (!combat || !item || (state.character.inventory[itemId] ?? 0) <= 0) return state;
  if (combat.playerAp < 2) {
    return {
      ...state,
      combat: {
        ...combat,
        log: [...combat.log.slice(-7), 'Za mało AP (wymagane 2 AP)!'],
      },
    };
  }

  if (item.combatDamage) {
    let nextState = removeItem(state, itemId, 1);
    const nextEnemyHp = Math.max(0, combat.enemyHp - item.combatDamage);
    const stunned = item.stunChance ? rng() * 100 <= item.stunChance : false;
    nextState = {
      ...nextState,
      combat: {
        ...combat,
        playerAp: combat.playerAp - 2,
        enemyHp: nextEnemyHp,
        enemyStunned: combat.enemyStunned || stunned,
        log: [
          ...combat.log.slice(-7),
          `Rzucono ${item.name}: ${item.combatDamage} obrażeń!${stunned ? ' [OGŁUSZENIE]' : ''}`,
        ],
      },
    };
    if (nextEnemyHp <= 0) {
      return resolveCombatVictory(nextState);
    }
    return nextState;
  }

  const afterHeal = useConsumable(state, itemId);
  return {
    ...afterHeal,
    combat: {
      ...combat,
      playerAp: combat.playerAp - 2,
      log: [...combat.log.slice(-7), `Użyto ${item.name} w walce.`],
    },
  };
}

export function endPlayerTurn(state: GameState, rng: () => number = Math.random): GameState {
  const combat = state.combat;
  if (!combat) return state;

  const dodgeBonus = combat.playerAp * 5;
  const enemy = ENEMIES[combat.enemyId];
  const logs = [...combat.log];

  if (combat.enemyStunned) {
    logs.push(`${combat.enemyName} jest ogłuszony i traci turę!`);
    return {
      ...state,
      combat: {
        ...combat,
        enemyStunned: false,
        enemyWeakenedTurns: Math.max(0, combat.enemyWeakenedTurns - 1),
        playerAp: combat.playerMaxAp,
        playerDodgeBonus: 0,
        turn: combat.turn + 1,
        log: logs.slice(-7),
      },
    };
  }

  const legendDodgeBonus = state.character.perks.includes('wasteland_legend') ? 10 : 0;
  const playerDodge = state.character.attributes.AGI * 2 + dodgeBonus + legendDodgeBonus;
  const enemyHitChance = Math.max(15, Math.min(90, (enemy?.accuracy ?? 65) - playerDodge));
  const roll = Math.floor(rng() * 100) + 1;

  let nextState = state;
  if (roll > enemyHitChance) {
    logs.push(`${combat.enemyName} pudłuje!`);
  } else {
    const armor = state.character.equippedArmorId
      ? ITEMS[state.character.equippedArmorId]
      : null;
    const playerDef =
      (armor?.defense ?? 0) + (state.character.perks.includes('wasteland_toughness') ? 3 : 0);
    const weakenMult = combat.enemyWeakenedTurns > 0 ? 0.75 : 1;
    const rawDmg = Math.round((enemy?.damage ?? 10) * weakenMult);
    const dmg = Math.max(1, rawDmg - playerDef);

    const newHp = Math.max(0, nextState.character.hp - dmg);
    nextState = {
      ...nextState,
      character: {
        ...nextState.character,
        hp: newHp,
      },
    };
    logs.push(`${combat.enemyName} zadaje Ci ${dmg} pkt obrażeń!`);

    if (enemy?.radDamage && enemy.radDamage > 0 && newHp > 0) {
      nextState = applyRadiation(nextState, enemy.radDamage);
      logs.push(`Otrzymujesz skażenie promieniowaniem (+RAD)!`);
    }
  }

  if (nextState.character.hp <= 0) {
    return {
      ...nextState,
      combat: null,
      currentSceneId: 'game_over_death',
    };
  }

  return {
    ...nextState,
    combat: {
      ...combat,
      enemyStunned: false,
      enemyWeakenedTurns: Math.max(0, combat.enemyWeakenedTurns - 1),
      playerAp: combat.playerMaxAp,
      playerDodgeBonus: 0,
      turn: combat.turn + 1,
      log: logs.slice(-7),
    },
  };
}

export function attemptFlee(state: GameState, rng: () => number = Math.random): GameState {
  const combat = state.combat;
  if (!combat || combat.isBoss || combat.playerAp < 3) return state;

  const legendFleeBonus = state.character.perks.includes('wasteland_legend') ? 10 : 0;
  const chance = Math.min(90, 40 + state.character.attributes.AGI * 5 + legendFleeBonus);
  const roll = Math.floor(rng() * 100) + 1;

  if (roll <= chance) {
    return {
      ...state,
      combat: null,
      currentSceneId: combat.fleeSceneId ?? combat.victorySceneId,
      eventLog: [...state.eventLog.slice(-19), `Udało Ci się uciec przed: ${combat.enemyName}.`],
    };
  }

  const failedState: GameState = {
    ...state,
    combat: {
      ...combat,
      playerAp: 0,
      log: [...combat.log.slice(-7), 'Nieudana ucieczka! Wróg wyprowadza kontratak.'],
    },
  };
  return endPlayerTurn(failedState, rng);
}
