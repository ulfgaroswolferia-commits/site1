import React, { useState } from 'react';
import { Box, Text, useInput } from 'ink';
import { AttributeKey, Attributes } from '../../types/game.js';
import { createInitialAttributes } from '../../engine/CharacterSystem.js';
import { THEME } from '../theme.js';

interface Props {
  onConfirm: (name: string, attributes: Attributes) => void;
  onCancel: () => void;
}

const ATTR_LIST: { key: AttributeKey; label: string; desc: string }[] = [
  { key: 'STR', label: 'SIŁA (STR)', desc: 'Obrażenia wręcz, testy siłowe i zastraszanie' },
  { key: 'PER', label: 'PERCEPCJA (PER)', desc: 'Celność broni oraz szanse w trybie V.A.T.S.' },
  { key: 'END', label: 'WYTRZYMAŁOŚĆ (END)', desc: 'Maksymalne HP oraz odporność na RAD' },
  { key: 'CHA', label: 'CHARYZMA (CHA)', desc: 'Perswazja w dialogach i ceny u kupców' },
  { key: 'INT', label: 'INTELIGENCJA (INT)', desc: 'Hakowanie RobCo, leczenie Stimpakami, bonus XP' },
  { key: 'AGI', label: 'ZRĘCZNOŚĆ (AGI)', desc: 'Punkty Akcji (AP), unik i szansa ucieczki' },
];

const PRESET_NAMES = ['Wędrowiec', 'Kora', 'Reks', 'Azar', 'Nova'];

export const CharacterCreator: React.FC<Props> = ({ onConfirm, onCancel }) => {
  const [nameIdx, setNameIdx] = useState(0);
  const [attrs, setAttrs] = useState<Attributes>(createInitialAttributes());
  const [pool, setPool] = useState(5);
  const [cursor, setCursor] = useState(0);

  useInput((input, key) => {
    if (key.escape) {
      onCancel();
      return;
    }
    if (input.toLowerCase() === 'n') {
      setNameIdx((prev) => (prev + 1) % PRESET_NAMES.length);
      return;
    }
    if (key.upArrow) {
      setCursor((prev) => (prev - 1 + 7) % 7);
    } else if (key.downArrow) {
      setCursor((prev) => (prev + 1) % 7);
    } else if (key.leftArrow && cursor < 6) {
      const k = ATTR_LIST[cursor].key;
      if (attrs[k] > 3) {
        setAttrs({ ...attrs, [k]: attrs[k] - 1 });
        setPool(pool + 1);
      }
    } else if (key.rightArrow && cursor < 6) {
      const k = ATTR_LIST[cursor].key;
      if (pool > 0 && attrs[k] < 10) {
        setAttrs({ ...attrs, [k]: attrs[k] + 1 });
        setPool(pool - 1);
      }
    } else if (key.return) {
      if (cursor === 6) {
        onConfirm(PRESET_NAMES[nameIdx], attrs);
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
      <Text color={THEME.amber} bold>
        REJESTRACJA MIESZKAŃCA KRYPTY 73 — SYSTEM S.P.E.C.I.A.L.
      </Text>
      <Box marginY={1} justifyContent="space-between">
        <Text color={THEME.primary}>
          Imię postaci: <Text color={THEME.amber} bold>{PRESET_NAMES[nameIdx]}</Text> (naciśnij [N], aby zmienić)
        </Text>
        <Text color={pool > 0 ? THEME.amber : THEME.primary} bold>
          Wolne punkty atrybutów: {pool}
        </Text>
      </Box>
      <Box flexDirection="column" marginBottom={1}>
        {ATTR_LIST.map((item, idx) => {
          const val = attrs[item.key];
          const isSelected = cursor === idx;
          return (
            <Box key={item.key} justifyContent="space-between">
              <Text color={isSelected ? THEME.amber : THEME.primary} bold={isSelected}>
                {isSelected ? '> ' : '  '}
                {item.label}: [ - {val} + ]
              </Text>
              <Text color={THEME.muted}>{item.desc}</Text>
            </Box>
          );
        })}
      </Box>
      <Box marginTop={1}>
        <Text
          color={cursor === 6 ? THEME.amber : THEME.primary}
          bold={cursor === 6}
        >
          {cursor === 6 ? '> ' : '  '}[ZATWIERDŹ KARTĘ POSTACI I ROZPOCZNIJ GRĘ (Enter)]
        </Text>
      </Box>
      <Box marginTop={1}>
        <Text color={THEME.muted}>
          [↑/↓] Wybór wiersza | [←/→] Zmiana wartości atrybutu | [N] Zmiana imienia | [ESC] Powrót
        </Text>
      </Box>
    </Box>
  );
};
