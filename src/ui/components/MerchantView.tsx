import React, { useState } from 'react';
import { Box, Text, useInput } from 'ink';
import { GameState } from '../../types/game.js';
import { ITEMS, MERCHANTS } from '../../content/items.js';
import { getBuyPrice, getSellPrice } from '../../engine/InventorySystem.js';
import { THEME } from '../theme.js';

interface Props {
  state: GameState;
  onBuy: (itemId: string) => void;
  onSell: (itemId: string) => void;
  onClose: () => void;
}

export const MerchantView: React.FC<Props> = ({ state, onBuy, onSell, onClose }) => {
  const [tab, setTab] = useState<'buy' | 'sell'>('buy');
  const [selected, setSelected] = useState(0);

  const merchant = state.activeMerchantId ? MERCHANTS[state.activeMerchantId] : null;
  if (!merchant) return null;

  const buyList = merchant.stock.filter((id) => Boolean(ITEMS[id]));
  const sellList = Object.entries(state.character.inventory)
    .filter(([id, qty]) => qty > 0 && ITEMS[id] && ITEMS[id].type !== 'quest')
    .map(([id]) => id);

  const activeItems = tab === 'buy' ? buyList : sellList;
  const safeSelected = Math.min(selected, Math.max(0, activeItems.length - 1));

  useInput((input, key) => {
    if (key.escape || input.toLowerCase() === 'q') {
      onClose();
      return;
    }
    if (key.leftArrow || key.rightArrow || key.tab) {
      setTab((prev) => (prev === 'buy' ? 'sell' : 'buy'));
      setSelected(0);
      return;
    }
    if (activeItems.length === 0) return;
    if (key.upArrow) {
      setSelected((prev) => (prev - 1 + activeItems.length) % activeItems.length);
    } else if (key.downArrow) {
      setSelected((prev) => (prev + 1) % activeItems.length);
    } else if (key.return) {
      const id = activeItems[safeSelected];
      if (!id) return;
      if (tab === 'buy') onBuy(id);
      else onSell(id);
    }
  });

  return (
    <Box
      flexDirection="column"
      borderStyle="single"
      borderColor={THEME.amber}
      paddingX={2}
      paddingY={1}
    >
      <Text color={THEME.amber} bold>
        HANDEL: {merchant.name} | Twoje Kapsle: {state.character.caps}
      </Text>
      <Text color={THEME.secondary}>{merchant.greeting}</Text>
      <Box marginY={1}>
        <Text color={tab === 'buy' ? THEME.amber : THEME.muted} bold={tab === 'buy'}>
          [ KUPNO ]
        </Text>
        <Text>   </Text>
        <Text color={tab === 'sell' ? THEME.amber : THEME.muted} bold={tab === 'sell'}>
          [ SPRZEDAŻ ]
        </Text>
        <Text color={THEME.muted}>  (Przełącz: ←/→ lub TAB, Wyjdź: ESC)</Text>
      </Box>

      {activeItems.length === 0 ? (
        <Text color={THEME.muted}>Brak przedmiotów na tej liście.</Text>
      ) : (
        activeItems.map((id, idx) => {
          const item = ITEMS[id];
          const price = tab === 'buy' ? getBuyPrice(state, id) : getSellPrice(state, id);
          const qty = state.character.inventory[id] ?? 0;
          const isSel = idx === safeSelected;
          return (
            <Text
              key={id}
              color={isSel ? THEME.amber : THEME.primary}
              bold={isSel}
            >
              {isSel ? '> ' : '  '}
              {item.name} ({price} kapsli) {qty > 0 ? `[Posiadasz: ${qty}]` : ''} — {item.description}
            </Text>
          );
        })
      )}
    </Box>
  );
};
