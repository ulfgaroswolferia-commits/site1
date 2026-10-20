import React from 'react';
import { Box, Text } from 'ink';
import { GameState } from '../../types/game.js';
import { getEffectiveMaxHp, XP_THRESHOLDS } from '../../engine/CharacterSystem.js';
import { LOCATIONS } from '../../content/scenes.js';
import { renderBar, THEME } from '../theme.js';

interface Props {
  state: GameState;
  modeLabel: string;
}

export const HeaderStatus: React.FC<Props> = ({ state, modeLabel }) => {
  const { character } = state;
  const effMaxHp = getEffectiveMaxHp(character.maxHp, character.rad);
  const nextXp = XP_THRESHOLDS[character.level] ?? 9999;
  const locName = LOCATIONS[state.currentLocationId]?.name ?? state.currentLocationId;

  return (
    <Box
      flexDirection="column"
      borderStyle="double"
      borderColor={THEME.primary}
      paddingX={1}
    >
      <Box justifyContent="space-between">
        <Text color={THEME.primary} bold>
          PIP-BOY 3000 MK IV — {character.name.toUpperCase()}
        </Text>
        <Text color={THEME.amber} bold>
          TRYB: {modeLabel}
        </Text>
      </Box>
      <Box justifyContent="space-between">
        <Text color={character.hp <= effMaxHp * 0.3 ? THEME.danger : THEME.primary}>
          HP: [{renderBar(character.hp, effMaxHp, 10)}] {character.hp}/{effMaxHp}
        </Text>
        <Text color={character.rad >= 30 ? THEME.amber : THEME.secondary}>
          RAD: [{renderBar(character.rad, 100, 8)}] {character.rad}%
        </Text>
        <Text color={THEME.amber}>KAPSLE: {character.caps}</Text>
        <Text color={THEME.cyan}>
          POZ: {character.level} ({character.xp}/{nextXp} XP)
          {character.pendingLevelUps > 0 ? ' [AWANS! Naciśnij S]' : ''}
        </Text>
      </Box>
      <Box justifyContent="space-between">
        <Text color={THEME.secondary}>LOKACJA: {locName}</Text>
      </Box>
    </Box>
  );
};
