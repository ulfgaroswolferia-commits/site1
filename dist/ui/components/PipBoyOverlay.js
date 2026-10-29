import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import { useState } from 'react';
import { Box, Text, useInput } from 'ink';
import { ITEMS } from '../../content/items.js';
import { PERKS } from '../../content/perks.js';
import { QUESTS } from '../../content/quests.js';
import { formatItemStats } from '../../engine/InventorySystem.js';
import { THEME } from '../theme.js';
const ATTRS = ['STR', 'PER', 'END', 'CHA', 'INT', 'AGI'];
const PERKS_PAGE_SIZE = 3;
const INV_PAGE_SIZE = 5;
export const PipBoyOverlay = ({ state, tab, onEquipOrUse, onLevelUp, onClose, }) => {
    const [selected, setSelected] = useState(0);
    const [selectedAttr, setSelectedAttr] = useState('STR');
    const invEntries = Object.entries(state.character.inventory).filter(([, qty]) => qty > 0);
    const availablePerks = Object.values(PERKS).filter((p) => !state.character.perks.includes(p.id));
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
            }
            else if (key.downArrow) {
                setSelected((prev) => (prev + 1) % invEntries.length);
            }
            else if (key.return) {
                const itemId = invEntries[safeInvSelected]?.[0];
                if (itemId)
                    onEquipOrUse(itemId);
            }
        }
        if (tab === 'stats' && state.character.pendingLevelUps > 0) {
            if (gainsAttribute) {
                if (key.leftArrow) {
                    const idx = ATTRS.indexOf(selectedAttr);
                    setSelectedAttr(ATTRS[(idx - 1 + ATTRS.length) % ATTRS.length]);
                }
                else if (key.rightArrow) {
                    const idx = ATTRS.indexOf(selectedAttr);
                    setSelectedAttr(ATTRS[(idx + 1) % ATTRS.length]);
                }
            }
            if (gainsPerk && availablePerks.length > 0) {
                if (key.upArrow) {
                    setSelected((prev) => (prev - 1 + availablePerks.length) % availablePerks.length);
                }
                else if (key.downArrow) {
                    setSelected((prev) => (prev + 1) % availablePerks.length);
                }
            }
            if (key.return) {
                const perk = availablePerks[selected] ?? availablePerks[0];
                onLevelUp(selectedAttr, perk ? perk.id : '');
            }
        }
    });
    return (_jsxs(Box, { flexDirection: "column", borderStyle: "single", borderColor: THEME.cyan, paddingX: 2, paddingY: 0, children: [tab === 'stats' && (_jsxs(Box, { flexDirection: "column", children: [_jsx(Text, { color: THEME.amber, bold: true, children: "KARTA POSTACI S.P.E.C.I.A.L. & PROFITY (PERKI)" }), _jsx(Box, { marginY: 0, justifyContent: "space-between", children: ATTRS.map((k) => (_jsxs(Text, { color: THEME.primary, bold: true, children: [k, ": ", state.character.attributes[k]] }, k))) }), _jsxs(Text, { color: THEME.secondary, children: ["Odblokowane Perki:", ' ', state.character.perks.length === 0
                                ? 'Brak'
                                : state.character.perks.map((id) => PERKS[id]?.name ?? id).join(', ')] }), _jsxs(Box, { flexDirection: "column", marginTop: 0, children: [_jsxs(Text, { color: THEME.cyan, children: ["Aktywna Bro\u0144:", ' ', state.character.equippedWeaponId && ITEMS[state.character.equippedWeaponId]
                                        ? `${ITEMS[state.character.equippedWeaponId].name} ${formatItemStats(ITEMS[state.character.equippedWeaponId])}`
                                        : 'Brak (Pięści)'] }), _jsxs(Text, { color: THEME.cyan, children: ["Aktywny Pancerz:", ' ', state.character.equippedArmorId && ITEMS[state.character.equippedArmorId]
                                        ? `${ITEMS[state.character.equippedArmorId].name} ${formatItemStats(ITEMS[state.character.equippedArmorId])}`
                                        : 'Brak'] })] }), state.character.pendingLevelUps > 0 && (_jsxs(Box, { flexDirection: "column", borderStyle: "round", borderColor: THEME.amber, paddingX: 1, marginTop: 0, children: [_jsxs(Text, { color: THEME.amber, bold: true, children: ["AWANS NA POZIOM ", nextLevel, "! (+10 Max HP & Pe\u0142ne Uleczenie)"] }), gainsAttribute ? (_jsxs(Text, { color: THEME.primary, children: ["\u2022 Wybierz atrybut S.P.E.C.I.A.L. (+1 co 3 lvl) klawiszami [\u2190/\u2192]:", ' ', _jsx(Text, { color: THEME.amber, bold: true, children: selectedAttr })] })) : (_jsxs(Text, { color: THEME.muted, children: ["\u2022 Punkty S.P.E.C.I.A.L. przys\u0142uguj\u0105 co 3 poziomy (najbli\u017Cszy na poziomie ", Math.ceil(nextLevel / 3) * 3, ")."] })), gainsPerk ? (_jsxs(Box, { flexDirection: "column", marginTop: 0, children: [_jsxs(Text, { color: THEME.primary, children: ["\u2022 Wybierz nowy Perk (co 2 lvl) klawiszami [\u2191/\u2193] (", selected + 1, "/", availablePerks.length, "):"] }), (() => {
                                        const perkStartIndex = Math.max(0, Math.min(selected - 1, Math.max(0, availablePerks.length - PERKS_PAGE_SIZE)));
                                        const visiblePerks = availablePerks.slice(perkStartIndex, perkStartIndex + PERKS_PAGE_SIZE);
                                        return (_jsxs(_Fragment, { children: [visiblePerks.map((perk, offset) => {
                                                    const actualIdx = perkStartIndex + offset;
                                                    const isSel = actualIdx === selected;
                                                    return (_jsxs(Text, { color: isSel ? THEME.amber : THEME.primary, bold: isSel, children: [isSel ? '> ' : '  ', actualIdx + 1, ". ", perk.name] }, perk.id));
                                                }), availablePerks[selected] && (_jsxs(Text, { color: THEME.cyan, children: ['   ', "Opis: ", availablePerks[selected].description] }))] }));
                                    })()] })) : (_jsxs(Text, { color: THEME.muted, children: ["\u2022 Nowy Perk przys\u0142uguje co 2 poziomy (najbli\u017Cszy na poziomie ", Math.ceil(nextLevel / 2) * 2, ")."] })), _jsx(Box, { marginTop: 0, children: _jsx(Text, { color: THEME.amber, bold: true, children: "Wci\u015Bnij [Enter], aby zatwierdzi\u0107 awans!" }) })] }))] })), tab === 'inventory' && (_jsxs(Box, { flexDirection: "column", children: [_jsxs(Text, { color: THEME.amber, bold: true, children: ["EKWIPUNEK POSTACI (Enter = Za\u0142\u00F3\u017C / U\u017Cyj) ", invEntries.length > 0 ? `(${safeInvSelected + 1}/${invEntries.length})` : ''] }), _jsx(Box, { marginY: 0, flexDirection: "column", children: invEntries.length === 0 ? (_jsx(Text, { color: THEME.muted, children: "Brak przedmiot\u00F3w w ekwipunku." })) : (() => {
                            const invStartIndex = Math.max(0, Math.min(safeInvSelected - 2, Math.max(0, invEntries.length - INV_PAGE_SIZE)));
                            const visibleInv = invEntries.slice(invStartIndex, invStartIndex + INV_PAGE_SIZE);
                            return visibleInv.map(([id, qty], offset) => {
                                const actualIdx = invStartIndex + offset;
                                const item = ITEMS[id];
                                if (!item)
                                    return null;
                                const isEquipped = state.character.equippedWeaponId === id ||
                                    state.character.equippedArmorId === id;
                                const isSel = actualIdx === safeInvSelected;
                                return (_jsxs(Text, { color: isSel ? THEME.amber : THEME.primary, bold: isSel, children: [isSel ? '> ' : '  ', item.name, " x", qty, " ", isEquipped ? '[ZAŁOŻONO] ' : '', _jsx(Text, { color: THEME.cyan, children: formatItemStats(item) }), " \u2014 ", item.description] }, id));
                            });
                        })() })] })), tab === 'quests' && (_jsxs(Box, { flexDirection: "column", children: [_jsx(Text, { color: THEME.amber, bold: true, children: "DZIENNIK ZADA\u0143 PIP-BOY" }), _jsx(Box, { marginY: 0, flexDirection: "column", children: Object.entries(state.quests).map(([questId, qState]) => {
                            const def = QUESTS[questId];
                            if (!def)
                                return null;
                            const stageDesc = def.stages[qState.stage] ?? '';
                            return (_jsxs(Box, { flexDirection: "column", marginBottom: 0, children: [_jsxs(Text, { color: qState.status === 'completed' ? THEME.secondary : THEME.amber, bold: true, children: ["\u2022 ", def.title, " [", qState.status === 'completed' ? 'UKOŃCZONE' : 'AKTYWNE', "]"] }), _jsxs(Text, { color: THEME.primary, children: ["  Cel: ", stageDesc] })] }, questId));
                        }) })] })), _jsx(Text, { color: THEME.muted, children: "[ESC] Zamknij zak\u0142adk\u0119 Pip-Boya" })] }));
};
