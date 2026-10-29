import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState } from 'react';
import { Box, Text, useInput } from 'ink';
import { createInitialAttributes } from '../../engine/CharacterSystem.js';
import { THEME } from '../theme.js';
const ATTR_LIST = [
    { key: 'STR', label: 'SIŁA (STR)', desc: 'Obrażenia wręcz, testy siłowe i zastraszanie' },
    { key: 'PER', label: 'PERCEPCJA (PER)', desc: 'Celność broni oraz szanse w trybie V.A.T.S.' },
    { key: 'END', label: 'WYTRZYMAŁOŚĆ (END)', desc: 'Maksymalne HP oraz odporność na RAD' },
    { key: 'CHA', label: 'CHARYZMA (CHA)', desc: 'Perswazja w dialogach i ceny u kupców' },
    { key: 'INT', label: 'INTELIGENCJA (INT)', desc: 'Hakowanie RobCo, leczenie Stimpakami, bonus XP' },
    { key: 'AGI', label: 'ZRĘCZNOŚĆ (AGI)', desc: 'Punkty Akcji (AP), unik i szansa ucieczki' },
];
const PRESET_NAMES = ['Wędrowiec', 'Kora', 'Reks', 'Azar', 'Nova'];
export const CharacterCreator = ({ onConfirm, onCancel }) => {
    const [nameIdx, setNameIdx] = useState(0);
    const [attrs, setAttrs] = useState(createInitialAttributes());
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
        }
        else if (key.downArrow) {
            setCursor((prev) => (prev + 1) % 7);
        }
        else if (key.leftArrow && cursor < 6) {
            const k = ATTR_LIST[cursor].key;
            if (attrs[k] > 3) {
                setAttrs({ ...attrs, [k]: attrs[k] - 1 });
                setPool(pool + 1);
            }
        }
        else if (key.rightArrow && cursor < 6) {
            const k = ATTR_LIST[cursor].key;
            if (pool > 0 && attrs[k] < 10) {
                setAttrs({ ...attrs, [k]: attrs[k] + 1 });
                setPool(pool - 1);
            }
        }
        else if (key.return) {
            if (cursor === 6) {
                onConfirm(PRESET_NAMES[nameIdx], attrs);
            }
        }
    });
    return (_jsxs(Box, { flexDirection: "column", borderStyle: "double", borderColor: THEME.primary, paddingX: 2, paddingY: 1, children: [_jsx(Text, { color: THEME.amber, bold: true, children: "REJESTRACJA MIESZKA\u0143CA KRYPTY 73 \u2014 SYSTEM S.P.E.C.I.A.L." }), _jsxs(Box, { marginY: 1, justifyContent: "space-between", children: [_jsxs(Text, { color: THEME.primary, children: ["Imi\u0119 postaci: ", _jsx(Text, { color: THEME.amber, bold: true, children: PRESET_NAMES[nameIdx] }), " (naci\u015Bnij [N], aby zmieni\u0107)"] }), _jsxs(Text, { color: pool > 0 ? THEME.amber : THEME.primary, bold: true, children: ["Wolne punkty atrybut\u00F3w: ", pool] })] }), _jsx(Box, { flexDirection: "column", marginBottom: 1, children: ATTR_LIST.map((item, idx) => {
                    const val = attrs[item.key];
                    const isSelected = cursor === idx;
                    return (_jsxs(Box, { justifyContent: "space-between", children: [_jsxs(Text, { color: isSelected ? THEME.amber : THEME.primary, bold: isSelected, children: [isSelected ? '> ' : '  ', item.label, ": [ - ", val, " + ]"] }), _jsx(Text, { color: THEME.muted, children: item.desc })] }, item.key));
                }) }), _jsx(Box, { marginTop: 1, children: _jsxs(Text, { color: cursor === 6 ? THEME.amber : THEME.primary, bold: cursor === 6, children: [cursor === 6 ? '> ' : '  ', "[ZATWIERD\u0179 KART\u0118 POSTACI I ROZPOCZNIJ GR\u0118 (Enter)]"] }) }), _jsx(Box, { marginTop: 1, children: _jsx(Text, { color: THEME.muted, children: "[\u2191/\u2193] Wyb\u00F3r wiersza | [\u2190/\u2192] Zmiana warto\u015Bci atrybutu | [N] Zmiana imienia | [ESC] Powr\u00F3t" }) })] }));
};
