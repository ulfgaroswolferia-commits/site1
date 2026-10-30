import React, { useState } from 'react';
import { Box, Text, useInput } from 'ink';
import { AttributeKey, GameState } from '../../types/game.js';
import { ITEMS } from '../../content/items.js';
import { PERKS } from '../../content/perks.js';
import { QUESTS } from '../../content/quests.js';
import { formatItemStats } from '../../engine/InventorySystem.js';
import { THEME } from '../theme.js';

interface Props {
  state: GameState;
  tab: 'stats' | 'inventory' | 'quests';
  onEquipOrUse: (itemId: string) => void;
  onLevelUp: (attr: AttributeKey, perkId: string) => void;
  onClose: () => void;
}

const ATTRS: AttributeKey[] = ['STR', 'PER', 'END', 'CHA', 'INT', 'AGI'];
const PERKS_PAGE_SIZE = 3;
const INV_PAGE_SIZE = 5;
const QUESTS_PAGE_SIZE = 3;

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
  const safeInvSelected = Math.min(selected, Math.max(0, invEntries.length - 1));

  const nextLevel = state.character.level + 1;
  const gainsAttribute = nextLevel % 3 === 0;
  const gainsPerk = nextLevel % 2 === 0;

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
        const itemId = invEntries[safeInvSelected]?.[0];
        if (itemId) onEquipOrUse(itemId);
      }
    }

    if (tab === 'quests') {
      const qEntries = Object.entries(state.quests);
      if (qEntries.length > 0) {
        if (key.upArrow) {
          setSelected((prev) => (prev - 1 + qEntries.length) % qEntries.length);
        } else if (key.downArrow) {
          setSelected((prev) => (prev + 1) % qEntries.length);
        }
      }
    }

    if (tab === 'stats' && state.character.pendingLevelUps > 0) {
      if (gainsAttribute) {
        if (key.leftArrow) {
          const idx = ATTRS.indexOf(selectedAttr);
          setSelectedAttr(ATTRS[(idx - 1 + ATTRS.length) % ATTRS.length]);
        } else if (key.rightArrow) {
          const idx = ATTRS.indexOf(selectedAttr);
          setSelectedAttr(ATTRS[(idx + 1) % ATTRS.length]);
        }
      }
      if (gainsPerk && availablePerks.length > 0) {
        if (key.upArrow) {
          setSelected((prev) => (prev - 1 + availablePerks.length) % availablePerks.length);
        } else if (key.downArrow) {
          setSelected((prev) => (prev + 1) % availablePerks.length);
        }
      }
      if (key.return) {
        const perk = availablePerks[selected] ?? availablePerks[0];
        onLevelUp(selectedAttr, perk ? perk.id : '');
      }
    }
  });

  return (
    <Box
      flexDirection="column"
      borderStyle="single"
      borderColor={THEME.cyan}
      paddingX={2}
      paddingY={0}
    >
      {tab === 'stats' && (
        <Box flexDirection="column">
          <Box justifyContent="space-between">
            <Text color={THEME.amber} bold>
              KARTA POSTACI S.P.E.C.I.A.L. & PROFITY (PERKI)
            </Text>
            <Text color={state.difficulty === 'hard' ? THEME.danger : THEME.muted} bold>
              [POZIOM: {state.difficulty === 'hard' ? 'HARD' : 'NORMAL'}]
            </Text>
          </Box>
          <Box marginY={0} justifyContent="space-between">
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
          <Box flexDirection="column" marginTop={0}>
            <Text color={THEME.cyan}>
              Aktywna Broń:{' '}
              {state.character.equippedWeaponId && ITEMS[state.character.equippedWeaponId]
                ? `${ITEMS[state.character.equippedWeaponId].name} ${formatItemStats(ITEMS[state.character.equippedWeaponId])}`
                : 'Brak (Pięści)'}
            </Text>
            <Text color={THEME.cyan}>
              Aktywny Pancerz:{' '}
              {state.character.equippedArmorId && ITEMS[state.character.equippedArmorId]
                ? `${ITEMS[state.character.equippedArmorId].name} ${formatItemStats(ITEMS[state.character.equippedArmorId])}`
                : 'Brak'}
            </Text>
          </Box>

          {state.character.pendingLevelUps > 0 && (
            <Box
              flexDirection="column"
              borderStyle="round"
              borderColor={THEME.amber}
              paddingX={1}
              marginTop={0}
            >
              <Text color={THEME.amber} bold>
                AWANS NA POZIOM {nextLevel}! (+10 Max HP & Pełne Uleczenie)
              </Text>
              {gainsAttribute ? (
                <Text color={THEME.primary}>
                  • Wybierz atrybut S.P.E.C.I.A.L. (+1 co 3 lvl) klawiszami [←/→]:{' '}
                  <Text color={THEME.amber} bold>{selectedAttr}</Text>
                </Text>
              ) : (
                <Text color={THEME.muted}>
                  • Punkty S.P.E.C.I.A.L. przysługują co 3 poziomy (najbliższy na poziomie {Math.ceil(nextLevel / 3) * 3}).
                </Text>
              )}

              {gainsPerk ? (
                <Box flexDirection="column" marginTop={0}>
                  <Text color={THEME.primary}>
                    • Wybierz nowy Perk (co 2 lvl) klawiszami [↑/↓] ({selected + 1}/{availablePerks.length}):
                  </Text>
                  {(() => {
                    const perkStartIndex = Math.max(
                      0,
                      Math.min(selected - 1, Math.max(0, availablePerks.length - PERKS_PAGE_SIZE))
                    );
                    const visiblePerks = availablePerks.slice(
                      perkStartIndex,
                      perkStartIndex + PERKS_PAGE_SIZE
                    );
                    return (
                      <>
                        {visiblePerks.map((perk, offset) => {
                          const actualIdx = perkStartIndex + offset;
                          const isSel = actualIdx === selected;
                          return (
                            <Text
                              key={perk.id}
                              color={isSel ? THEME.amber : THEME.primary}
                              bold={isSel}
                            >
                              {isSel ? '> ' : '  '}
                              {actualIdx + 1}. {perk.name}
                            </Text>
                          );
                        })}
                        {availablePerks[selected] && (
                          <Text color={THEME.cyan}>
                            {'   '}Opis: {availablePerks[selected].description}
                          </Text>
                        )}
                      </>
                    );
                  })()}
                </Box>
              ) : (
                <Text color={THEME.muted}>
                  • Nowy Perk przysługuje co 2 poziomy (najbliższy na poziomie {Math.ceil(nextLevel / 2) * 2}).
                </Text>
              )}

              <Box marginTop={0}>
                <Text color={THEME.amber} bold>
                  Wciśnij [Enter], aby zatwierdzić awans!
                </Text>
              </Box>
            </Box>
          )}
        </Box>
      )}

      {tab === 'inventory' && (
        <Box flexDirection="column">
          <Text color={THEME.amber} bold>
            EKWIPUNEK POSTACI (Enter = Załóż / Użyj) {invEntries.length > 0 ? `(${safeInvSelected + 1}/${invEntries.length})` : ''}
          </Text>
          <Box marginY={0} flexDirection="column">
            {invEntries.length === 0 ? (
              <Text color={THEME.muted}>Brak przedmiotów w ekwipunku.</Text>
            ) : (() => {
              const invStartIndex = Math.max(
                0,
                Math.min(safeInvSelected - 2, Math.max(0, invEntries.length - INV_PAGE_SIZE))
              );
              const visibleInv = invEntries.slice(invStartIndex, invStartIndex + INV_PAGE_SIZE);
              return visibleInv.map(([id, qty], offset) => {
                const actualIdx = invStartIndex + offset;
                const item = ITEMS[id];
                if (!item) return null;
                const isEquipped =
                  state.character.equippedWeaponId === id ||
                  state.character.equippedArmorId === id;
                const isSel = actualIdx === safeInvSelected;
                return (
                  <Text
                    key={id}
                    color={isSel ? THEME.amber : THEME.primary}
                    bold={isSel}
                  >
                    {isSel ? '> ' : '  '}
                    {item.name} x{qty} {isEquipped ? '[ZAŁOŻONO] ' : ''}
                    <Text color={THEME.cyan}>{formatItemStats(item)}</Text> — {item.description}
                  </Text>
                );
              });
            })()}
          </Box>
        </Box>
      )}

      {tab === 'quests' && (() => {
        const questEntries = Object.entries(state.quests);
        const qStartIndex = Math.max(
          0,
          Math.min(selected - 1, Math.max(0, questEntries.length - QUESTS_PAGE_SIZE))
        );
        const visibleQuests = questEntries.slice(qStartIndex, qStartIndex + QUESTS_PAGE_SIZE);
        return (
          <Box flexDirection="column">
            <Text color={THEME.amber} bold>
              DZIENNIK ZADAŃ PIP-BOY ({selected + 1}/{questEntries.length || 1})
            </Text>
            <Box marginY={0} flexDirection="column">
              {visibleQuests.map(([questId, qState], offset) => {
                const idx = qStartIndex + offset;
                const def = QUESTS[questId];
                if (!def) return null;
                const stageDesc = def.stages[qState.stage] ?? '';
                const isSel = idx === selected;
                return (
                  <Box key={questId} flexDirection="column" marginBottom={0}>
                    <Text
                      color={qState.status === 'completed' ? THEME.secondary : THEME.amber}
                      bold={isSel}
                    >
                      {isSel ? '> ' : '  '}• {def.title} [{qState.status === 'completed' ? 'UKOŃCZONE' : 'AKTYWNE'}]
                    </Text>
                    <Text color={THEME.primary}>    Cel: {stageDesc}</Text>
                  </Box>
                );
              })}
            </Box>
          </Box>
        );
      })()}

      <Text color={THEME.muted}>[ESC] Zamknij zakładkę Pip-Boya</Text>
    </Box>
  );
};
