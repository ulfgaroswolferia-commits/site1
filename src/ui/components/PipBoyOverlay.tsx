import React, { useState } from 'react';
import { Box, Text, useInput } from 'ink';
import { AttributeKey, GameState } from '../../types/game.js';
import { ITEMS } from '../../content/items.js';
import { PERKS } from '../../content/perks.js';
import { QUESTS } from '../../content/quests.js';
import { THEME } from '../theme.js';

interface Props {
  state: GameState;
  tab: 'stats' | 'inventory' | 'quests';
  onEquipOrUse: (itemId: string) => void;
  onLevelUp: (attr: AttributeKey, perkId: string) => void;
  onClose: () => void;
}

const ATTRS: AttributeKey[] = ['STR', 'PER', 'END', 'CHA', 'INT', 'AGI'];

export const PipBoyOverlay: React.FC<Props> = ({
  state,
  tab,
  onEquipOrUse,
  onLevelUp,
  onClose,
}) => {
  const [selected, setSelected] = useState(0);
  const [selectedAttr, setSelectedAttr] = useState<AttributeKey>('STR');

  const invEntries = Object.entries(state.character.inventory).filter(([, qty]) => qty > 0);
  const availablePerks = Object.values(PERKS).filter(
    (p) => !state.character.perks.includes(p.id)
  );

  useInput((input, key) => {
    if (key.escape) {
      onClose();
      return;
    }

    if (tab === 'inventory' && invEntries.length > 0) {
      if (key.upArrow) {
        setSelected((prev) => (prev - 1 + invEntries.length) % invEntries.length);
      } else if (key.downArrow) {
        setSelected((prev) => (prev + 1) % invEntries.length);
      } else if (key.return) {
        const itemId = invEntries[selected]?.[0];
        if (itemId) onEquipOrUse(itemId);
      }
    }

    if (tab === 'stats' && state.character.pendingLevelUps > 0 && availablePerks.length > 0) {
      if (key.leftArrow) {
        const idx = ATTRS.indexOf(selectedAttr);
        setSelectedAttr(ATTRS[(idx - 1 + ATTRS.length) % ATTRS.length]);
      } else if (key.rightArrow) {
        const idx = ATTRS.indexOf(selectedAttr);
        setSelectedAttr(ATTRS[(idx + 1) % ATTRS.length]);
      } else if (key.upArrow) {
        setSelected((prev) => (prev - 1 + availablePerks.length) % availablePerks.length);
      } else if (key.downArrow) {
        setSelected((prev) => (prev + 1) % availablePerks.length);
      } else if (key.return) {
        const perk = availablePerks[selected] ?? availablePerks[0];
        if (perk) onLevelUp(selectedAttr, perk.id);
      }
    }
  });

  return (
    <Box
      flexDirection="column"
      borderStyle="single"
      borderColor={THEME.cyan}
      paddingX={2}
      paddingY={1}
    >
      {tab === 'stats' && (
        <Box flexDirection="column">
          <Text color={THEME.amber} bold>
            KARTA POSTACI S.P.E.C.I.A.L. & PROFITY (PERKI)
          </Text>
          <Box marginY={1} justifyContent="space-between">
            {ATTRS.map((k) => (
              <Text key={k} color={THEME.primary} bold>
                {k}: {state.character.attributes[k]}
              </Text>
            ))}
          </Box>
          <Text color={THEME.secondary}>
            Odblokowane Perki:{' '}
            {state.character.perks.length === 0
              ? 'Brak'
              : state.character.perks.map((id) => PERKS[id]?.name ?? id).join(', ')}
          </Text>

          {state.character.pendingLevelUps > 0 && (
            <Box
              flexDirection="column"
              borderStyle="round"
              borderColor={THEME.amber}
              paddingX={1}
              marginTop={1}
            >
              <Text color={THEME.amber} bold>
                DOSTĘPNY AWANS NA NOWY POZIOM!
              </Text>
              <Text color={THEME.primary}>
                1. Wybierz atrybut do zwiększenia (+1) klawiszami [←/→]:{' '}
                <Text color={THEME.amber} bold>{selectedAttr}</Text>
              </Text>
              <Text color={THEME.primary}>
                2. Wybierz nowy Perk klawiszami [↑/↓] i zatwierdź [Enter]:
              </Text>
              {availablePerks.map((perk, idx) => (
                <Text
                  key={perk.id}
                  color={idx === selected ? THEME.amber : THEME.primary}
                  bold={idx === selected}
                >
                  {idx === selected ? '> ' : '  '}
                  {perk.name} — {perk.description}
                </Text>
              ))}
            </Box>
          )}
        </Box>
      )}

      {tab === 'inventory' && (
        <Box flexDirection="column">
          <Text color={THEME.amber} bold>
            EKWIPUNEK POSTACI (Enter = Załóż / Użyj)
          </Text>
          <Box marginY={1} flexDirection="column">
            {invEntries.map(([id, qty], idx) => {
              const item = ITEMS[id];
              if (!item) return null;
              const isEquipped =
                state.character.equippedWeaponId === id ||
                state.character.equippedArmorId === id;
              const isSel = idx === selected;
              return (
                <Text
                  key={id}
                  color={isSel ? THEME.amber : THEME.primary}
                  bold={isSel}
                >
                  {isSel ? '> ' : '  '}
                  {item.name} x{qty} {isEquipped ? '[ZAŁOŻONO]' : ''} — {item.description}
                </Text>
              );
            })}
          </Box>
        </Box>
      )}

      {tab === 'quests' && (
        <Box flexDirection="column">
          <Text color={THEME.amber} bold>
            DZIENNIK ZADAŃ PIP-BOY
          </Text>
          <Box marginY={1} flexDirection="column">
            {Object.entries(state.quests).map(([questId, qState]) => {
              const def = QUESTS[questId];
              if (!def) return null;
              const stageDesc = def.stages[qState.stage] ?? '';
              return (
                <Box key={questId} flexDirection="column" marginBottom={1}>
                  <Text
                    color={qState.status === 'completed' ? THEME.secondary : THEME.amber}
                    bold
                  >
                    • {def.title} [{qState.status === 'completed' ? 'UKOŃCZONE' : 'AKTYWNE'}]
                  </Text>
                  <Text color={THEME.primary}>  Cel: {stageDesc}</Text>
                </Box>
              );
            })}
          </Box>
        </Box>
      )}

      <Text color={THEME.muted}>[ESC] Zamknij zakładkę Pip-Boya</Text>
    </Box>
  );
};
