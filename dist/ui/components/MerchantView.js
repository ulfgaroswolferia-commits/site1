import { jsxs as _jsxs, jsx as _jsx } from "react/jsx-runtime";
import { useState } from 'react';
import { Box, Text, useInput } from 'ink';
import { ITEMS, MERCHANTS } from '../../content/items.js';
import { formatItemStats, getBuyPrice, getSellPrice } from '../../engine/InventorySystem.js';
import { THEME } from '../theme.js';
export const MerchantView = ({ state, onBuy, onSell, onClose }) => {
    const [tab, setTab] = useState('buy');
    const [selected, setSelected] = useState(0);
    const merchant = state.activeMerchantId ? MERCHANTS[state.activeMerchantId] : null;
    if (!merchant)
        return null;
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
        if (activeItems.length === 0)
            return;
        if (key.upArrow) {
            setSelected((prev) => (prev - 1 + activeItems.length) % activeItems.length);
        }
        else if (key.downArrow) {
            setSelected((prev) => (prev + 1) % activeItems.length);
        }
        else if (key.return) {
            const id = activeItems[safeSelected];
            if (!id)
                return;
            if (tab === 'buy')
                onBuy(id);
            else
                onSell(id);
        }
    });
    return (_jsxs(Box, { flexDirection: "column", borderStyle: "single", borderColor: THEME.amber, paddingX: 2, paddingY: 1, children: [_jsxs(Text, { color: THEME.amber, bold: true, children: ["HANDEL: ", merchant.name, " | Twoje Kapsle: ", state.character.caps] }), _jsx(Text, { color: THEME.secondary, children: merchant.greeting }), _jsxs(Box, { marginY: 1, children: [_jsx(Text, { color: tab === 'buy' ? THEME.amber : THEME.muted, bold: tab === 'buy', children: "[ KUPNO ]" }), _jsx(Text, { children: "   " }), _jsx(Text, { color: tab === 'sell' ? THEME.amber : THEME.muted, bold: tab === 'sell', children: "[ SPRZEDA\u017B ]" }), _jsx(Text, { color: THEME.muted, children: "  (Prze\u0142\u0105cz: \u2190/\u2192 lub TAB, Wyjd\u017A: ESC)" })] }), activeItems.length === 0 ? (_jsx(Text, { color: THEME.muted, children: "Brak przedmiot\u00F3w na tej li\u015Bcie." })) : (activeItems.map((id, idx) => {
                const item = ITEMS[id];
                const price = tab === 'buy' ? getBuyPrice(state, id) : getSellPrice(state, id);
                const qty = state.character.inventory[id] ?? 0;
                const isSel = idx === safeSelected;
                return (_jsxs(Text, { color: isSel ? THEME.amber : THEME.primary, bold: isSel, children: [isSel ? '> ' : '  ', item.name, " (", price, " kapsli) ", qty > 0 ? `[Posiadasz: ${qty}] ` : '', _jsx(Text, { color: THEME.cyan, children: formatItemStats(item) }), " \u2014 ", item.description] }, id));
            }))] }));
};
