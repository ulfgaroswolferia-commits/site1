import React, { useState, useEffect } from 'react';
import { Box, Text, useInput } from 'ink';
import { GameState, SceneChoice } from '../../types/game.js';
import { getAvailableChoices, getCurrentScene } from '../../engine/DialogueEngine.js';
import { calculateSkillCheckChance } from '../../engine/CharacterSystem.js';
import { THEME } from '../theme.js';

interface Props {
  state: GameState;
  onSelectChoice: (choice: SceneChoice) => void;
  onReturnToTitle: () => void;
}

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

  return (
    <Box flexDirection="column">
      <Box
        flexDirection="column"
        borderStyle="single"
        borderColor={THEME.primary}
        paddingX={2}
        paddingY={1}
      >
        <Text color={THEME.amber} bold>
          {scene.title}
        </Text>
        <Box marginY={1}>
          <Text color={THEME.primary}>{scene.description}</Text>
        </Box>
        {lastLog && (
          <Box borderStyle="round" borderColor={THEME.secondary} paddingX={1}>
            <Text color={THEME.cyan}>LOG: {lastLog}</Text>
          </Box>
        )}
      </Box>

      <Box
        flexDirection="column"
        borderStyle="single"
        borderColor={THEME.primary}
        paddingX={2}
        paddingY={1}
      >
        {scene.isEnding ? (
          <Text color={THEME.amber} bold>
            {'> 1. Powrót do Menu Głównego (Enter)'}
          </Text>
        ) : (
          choices.map((c, idx) => {
            const chanceText = c.skillCheck
              ? ` (Szansa: ${calculateSkillCheckChance(state, c.skillCheck)}%)`
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
