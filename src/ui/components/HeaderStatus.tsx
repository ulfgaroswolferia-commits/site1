import React from 'react';
import { Box, Text } from 'ink';
import { GameState } from '../../types/game.js';
import { getEffectiveMaxHp, XP_THRESHOLDS } from '../../engine/CharacterSystem.js';
import { ITEMS } from '../../content/items.js';
import { LOCATIONS } from '../../content/scenes.js';
import { renderBar, THEME } from '../theme.js';
import { AMMO_SHORT_NAMES } from '../../engine/InventorySystem.js';

interface Props {
  state: GameState;
  modeLabel: string;
}

export const HeaderStatus: React.FC<Props> = ({ state, modeLabel }) => {
  const { character } = state;
  const effMaxHp = getEffectiveMaxHp(character.maxHp, character.rad);
  const nextXp = XP_THRESHOLDS[character.level] ?? 9999;
  const locName = LOCATIONS[state.currentLocationId]?.name ?? state.currentLocationId;
  const waterDays = state.counters?.water_days_left ?? 60;
  const openness = state.counters?.vault_openness ?? 0;
  const factionLabel =
    openness >= 2 ? 'OTWARCI' : openness <= -1 ? 'IZOLACJONIŚCI' : 'RÓWNOWAGA';
  const equippedWeapon = character.equippedWeaponId ? ITEMS[character.equippedWeaponId] : null;
  const ammoId = equippedWeapon && !equippedWeapon.isMelee ? equippedWeapon.ammoType : undefined;
  const ammoQty = ammoId ? (character.inventory[ammoId] ?? 0) : null;
  const ammoTag = ammoId ? (AMMO_SHORT_NAMES[ammoId] ?? '10mm') : '';

  return (
    <Box
      flexDirection="column"
      borderStyle="double"
      borderColor={THEME.primary}
      paddingX={1}
    >
      <Box justifyContent="space-between">
        <Text color={THEME.primary} bold>
          PIP-BOY 3000 MK IV — {character.name.toUpperCase()}{state.difficulty === 'hard' ? ' [HARD]' : ''}
        </Text>
        <Text color={THEME.amber} bold>
          TRYB: {modeLabel}
        </Text>
      </Box>
      <Box justifyContent="space-between">
        <Text color={character.hp <= effMaxHp * 0.3 ? THEME.danger : THEME.primary}>
          HP: [{renderBar(character.hp, effMaxHp, 5)}] {character.hp}/{effMaxHp}
        </Text>
        <Text color={character.rad >= 30 ? THEME.amber : THEME.secondary}>
          RAD: {character.rad}%
        </Text>
        <Text color={THEME.amber}>KAPSLE: {character.caps}</Text>
        <Text color={ammoQty !== null && ammoQty <= 4 ? THEME.danger : THEME.secondary}>
          {ammoQty !== null ? `AMMO (${ammoTag}): ${ammoQty}` : 'BROŃ: WRĘCZ'}
        </Text>
        <Text color={THEME.cyan}>
          POZ: {character.level} ({character.xp}/{nextXp})
          {character.pendingLevelUps > 0 ? ' [AWANS: S]' : ''}
        </Text>
      </Box>
      <Box justifyContent="space-between">
        <Text color={THEME.secondary}>LOKACJA: {locName}</Text>
        <Text color={state.difficulty === 'hard' ? THEME.danger : THEME.muted} bold={state.difficulty === 'hard'}>
          TRUDNOŚĆ: {state.difficulty === 'hard' ? 'HARD' : 'NORMAL'}
        </Text>
        <Text color={THEME.muted}>K73: {factionLabel}</Text>
        {state.flags.arroyo_unlocked && (
          <Text color={waterDays <= 18 ? THEME.danger : THEME.amber} bold>
            WODA: {waterDays} DNI
          </Text>
        )}
      </Box>
    </Box>
  );
};
