import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState } from 'react';
import { Box, Text, useInput } from 'ink';
import { LOCATIONS } from '../../content/scenes.js';
import { THEME } from '../theme.js';
export const WorldMapView = ({ state, onSelectLocation, onClose }) => {
    const available = Object.values(LOCATIONS).filter((loc) => !loc.requiredFlag || state.flags[loc.requiredFlag]);
    const [selected, setSelected] = useState(0);
    useInput((input, key) => {
        if (key.escape || input.toLowerCase() === 'm') {
            onClose();
            return;
        }
        if (key.upArrow) {
            setSelected((prev) => (prev - 1 + available.length) % available.length);
        }
        else if (key.downArrow) {
            setSelected((prev) => (prev + 1) % available.length);
        }
        else if (key.return) {
            const loc = available[selected];
            if (loc)
                onSelectLocation(loc.id);
        }
        else {
            const num = parseInt(input, 10);
            if (num >= 1 && num <= available.length) {
                onSelectLocation(available[num - 1].id);
            }
        }
    });
    return (_jsxs(Box, { flexDirection: "column", borderStyle: "single", borderColor: THEME.primary, paddingX: 2, paddingY: 1, children: [_jsx(Text, { color: THEME.amber, bold: true, children: "MAPA SEKTORA PUSTKOWI \u2014 WYBIERZ CEL PODR\u00D3\u017BY:" }), _jsx(Box, { flexDirection: "column", marginY: 1, children: available.map((loc, idx) => {
                    const isCurrent = loc.id === state.currentLocationId;
                    const isSel = idx === selected;
                    return (_jsxs(Box, { flexDirection: "column", marginBottom: 1, children: [_jsxs(Text, { color: isSel ? THEME.amber : THEME.primary, bold: isSel, children: [isSel ? '> ' : '  ', idx + 1, ". ", loc.name, " ", isCurrent ? '[TUTAJ JESTEŚ]' : ''] }), _jsxs(Text, { color: THEME.muted, children: ["    ", loc.description] })] }, loc.id));
                }) }), _jsx(Text, { color: THEME.muted, children: "[Enter / 1-8] Podr\u00F3\u017Cuj | [ESC] Zamknij map\u0119" })] }));
};
