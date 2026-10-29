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

const MAP_PAGE_SIZE = 5;

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

  const startIndex = Math.max(
    0,
    Math.min(selected - 2, Math.max(0, available.length - MAP_PAGE_SIZE))
  );
  const visible = available.slice(startIndex, startIndex + MAP_PAGE_SIZE);
  const selectedLoc = available[selected];

  return (
    <Box
      flexDirection="column"
      borderStyle="single"
      borderColor={THEME.primary}
      paddingX={2}
      paddingY={0}
    >
      <Text color={THEME.amber} bold>
        MAPA SEKTORA PUSTKOWI — WYBIERZ CEL PODRÓŻY ({selected + 1}/{available.length}):
      </Text>
      <Box flexDirection="column" marginY={0}>
        {visible.map((loc, offset) => {
          const idx = startIndex + offset;
          const isCurrent = loc.id === state.currentLocationId;
          const isSel = idx === selected;
          return (
            <Text key={loc.id} color={isSel ? THEME.amber : THEME.primary} bold={isSel}>
              {isSel ? '> ' : '  '}
              {idx + 1}. {loc.name} {isCurrent ? '[TUTAJ JESTEŚ]' : ''}
            </Text>
          );
        })}
      </Box>
      {selectedLoc && (
        <Text color={THEME.cyan}>
          CEL: {selectedLoc.name} — {selectedLoc.description}
        </Text>
      )}
      <Text color={THEME.muted}>[↑/↓] Wybór | [Enter / 1-{available.length}] Podróż | [ESC / M] Zamknij</Text>
    </Box>
  );
};
