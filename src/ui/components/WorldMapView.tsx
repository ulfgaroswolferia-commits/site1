import React, { useState } from 'react';
import { Box, Text, useInput } from 'ink';
import { GameState, LocationId } from '../../types/game.js';
import { LOCATIONS } from '../../content/scenes.js';
import { THEME } from '../theme.js';

interface Props {
  state: GameState;
  onSelectLocation: (locId: LocationId) => void;
  onClose: () => void;
}

export const WorldMapView: React.FC<Props> = ({ state, onSelectLocation, onClose }) => {
  const available = Object.values(LOCATIONS).filter(
    (loc) => !loc.requiredFlag || state.flags[loc.requiredFlag]
  );
  const [selected, setSelected] = useState(0);

  useInput((input, key) => {
    if (key.escape || input.toLowerCase() === 'm') {
      onClose();
      return;
    }
    if (key.upArrow) {
      setSelected((prev) => (prev - 1 + available.length) % available.length);
    } else if (key.downArrow) {
      setSelected((prev) => (prev + 1) % available.length);
    } else if (key.return) {
      const loc = available[selected];
      if (loc) onSelectLocation(loc.id);
    } else {
      const num = parseInt(input, 10);
      if (num >= 1 && num <= available.length) {
        onSelectLocation(available[num - 1].id);
      }
    }
  });

  return (
    <Box
      flexDirection="column"
      borderStyle="single"
      borderColor={THEME.primary}
      paddingX={2}
      paddingY={1}
    >
      <Text color={THEME.amber} bold>
        MAPA SEKTORA PUSTKOWI — WYBIERZ CEL PODRÓŻY:
      </Text>
      <Box flexDirection="column" marginY={1}>
        {available.map((loc, idx) => {
          const isCurrent = loc.id === state.currentLocationId;
          const isSel = idx === selected;
          return (
            <Box key={loc.id} flexDirection="column" marginBottom={1}>
              <Text color={isSel ? THEME.amber : THEME.primary} bold={isSel}>
                {isSel ? '> ' : '  '}
                {idx + 1}. {loc.name} {isCurrent ? '[TUTAJ JESTEŚ]' : ''}
              </Text>
              <Text color={THEME.muted}>    {loc.description}</Text>
            </Box>
          );
        })}
      </Box>
      <Text color={THEME.muted}>[Enter / 1-5] Podróżuj | [ESC] Zamknij mapę</Text>
    </Box>
  );
};
