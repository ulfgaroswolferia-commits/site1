import React, { useState } from 'react';
import { Box, Text, useInput } from 'ink';
import { THEME } from '../theme.js';

interface Props {
  canLoad: boolean;
  onNewGame: () => void;
  onLoadGame: () => void;
  onExit: () => void;
  statusMessage?: string | null;
}

export const MainMenu: React.FC<Props> = ({
  canLoad,
  onNewGame,
  onLoadGame,
  onExit,
  statusMessage,
}) => {
  const options = [
    { label: 'Nowa Gra (Krypta 73)', action: onNewGame },
    ...(canLoad ? [{ label: 'Wczytaj Zapisaną Grę', action: onLoadGame }] : []),
    { label: 'Wyjdź do Terminala VS Code', action: onExit },
  ];
  const [selected, setSelected] = useState(0);

  useInput((input, key) => {
    if (key.upArrow) {
      setSelected((prev) => (prev - 1 + options.length) % options.length);
    } else if (key.downArrow) {
      setSelected((prev) => (prev + 1) % options.length);
    } else if (key.return) {
      options[selected]?.action();
    } else {
      const num = parseInt(input, 10);
      if (num >= 1 && num <= options.length) {
        options[num - 1]?.action();
      }
    }
  });

  return (
    <Box
      flexDirection="column"
      borderStyle="double"
      borderColor={THEME.primary}
      paddingX={2}
      paddingY={1}
    >
      <Text color={THEME.primary} bold>
        ROBCO INDUSTRIES UNIFIED OPERATING SYSTEM v2077
      </Text>
      <Text color={THEME.amber} bold>
        ═══ KRYPTA 73: POPIOŁY NOWEGO ŚWITU ═══
      </Text>
      <Box marginY={1} flexDirection="column">
        <Text color={THEME.secondary}>
          Tekstowa gra RPG w klimacie post-apo. Sterowanie: Strzałki ↑/↓ + Enter lub klawisze 1-9.
        </Text>
      </Box>
      {statusMessage && (
        <Box marginBottom={1}>
          <Text color={THEME.danger}>{statusMessage}</Text>
        </Box>
      )}
      <Box flexDirection="column">
        {options.map((opt, idx) => (
          <Text
            key={opt.label}
            color={idx === selected ? THEME.amber : THEME.primary}
            bold={idx === selected}
          >
            {idx === selected ? '> ' : '  '}
            {idx + 1}. {opt.label}
          </Text>
        ))}
      </Box>
    </Box>
  );
};
