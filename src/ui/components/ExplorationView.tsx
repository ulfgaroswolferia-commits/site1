import React, { useState, useEffect } from 'react';
import { Box, Text, useInput } from 'ink';
import { GameState, SceneChoice } from '../../types/game.js';
import { getAvailableChoices, getCurrentScene, getSceneDescription } from '../../engine/DialogueEngine.js';
import { calculateSkillCheckChance } from '../../engine/CharacterSystem.js';
import { THEME } from '../theme.js';

interface Props {
  state: GameState;
  onSelectChoice: (choice: SceneChoice) => void;
  onReturnToTitle: () => void;
}

const CHOICES_PAGE_SIZE = 4;

export const ExplorationView: React.FC<Props> = ({
  state,
  onSelectChoice,
  onReturnToTitle,
}) => {
  const scene = getCurrentScene(state);
  const choices = getAvailableChoices(state, scene.choices);
  const [selected, setSelected] = useState(0);

  useEffect(() => {
    setSelected(0);
  }, [scene.id, choices.length]);

  useInput((input, key) => {
    if (scene.isEnding) {
      if (key.return || input === '1') {
        onReturnToTitle();
      }
      return;
    }
    if (choices.length === 0) return;
    if (key.upArrow) {
      setSelected((prev) => (prev - 1 + choices.length) % choices.length);
    } else if (key.downArrow) {
      setSelected((prev) => (prev + 1) % choices.length);
    } else if (key.return) {
      const chosen = choices[selected];
      if (chosen) onSelectChoice(chosen);
    } else {
      const num = parseInt(input, 10);
      if (num >= 1 && num <= choices.length) {
        onSelectChoice(choices[num - 1]);
      }
    }
  });

  const lastLog = state.eventLog[state.eventLog.length - 1];

  const startIndex = Math.max(
    0,
    Math.min(selected - 2, Math.max(0, choices.length - CHOICES_PAGE_SIZE))
  );
  const visibleChoices = choices.slice(startIndex, startIndex + CHOICES_PAGE_SIZE);

  return (
    <Box flexDirection="column">
      <Box
        flexDirection="column"
        borderStyle="single"
        borderColor={THEME.primary}
        paddingX={2}
        paddingY={0}
      >
        <Text color={THEME.amber} bold>
          {scene.title}
        </Text>
        <Box marginY={0}>
          <Text color={THEME.primary}>{getSceneDescription(state, scene)}</Text>
        </Box>
        {lastLog && (
          <Text color={THEME.cyan}>LOG: {lastLog}</Text>
        )}
      </Box>

      <Box
        flexDirection="column"
        borderStyle="single"
        borderColor={THEME.primary}
        paddingX={2}
        paddingY={0}
      >
        {scene.isEnding ? (
          <Text color={THEME.amber} bold>
            {'> 1. Powrót do Menu Głównego (Enter)'}
          </Text>
        ) : (
          visibleChoices.map((c, offset) => {
            const idx = startIndex + offset;
            const chanceText = c.skillCheck
              ? ` (${calculateSkillCheckChance(state, c.skillCheck)}%)`
              : '';
            const isSel = idx === selected;
            return (
              <Text
                key={c.id}
                color={isSel ? THEME.amber : THEME.primary}
                bold={isSel}
              >
                {isSel ? '> ' : '  '}
                {idx + 1}. {c.text}
                {chanceText}
              </Text>
            );
          })
        )}
      </Box>
    </Box>
  );
};
