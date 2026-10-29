import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState } from 'react';
import { Box, Text, useInput } from 'ink';
import { THEME } from '../theme.js';
export const MainMenu = ({ canLoad, onNewGame, onLoadGame, onExit, statusMessage, }) => {
    const options = [
        { label: 'Nowa Gra (Krypta 73)', action: onNewGame },
        ...(canLoad ? [{ label: 'Wczytaj Zapisaną Grę', action: onLoadGame }] : []),
        { label: 'Wyjdź do Terminala VS Code', action: onExit },
    ];
    const [selected, setSelected] = useState(0);
    useInput((input, key) => {
        if (key.upArrow) {
            setSelected((prev) => (prev - 1 + options.length) % options.length);
        }
        else if (key.downArrow) {
            setSelected((prev) => (prev + 1) % options.length);
        }
        else if (key.return) {
            options[selected]?.action();
        }
        else {
            const num = parseInt(input, 10);
            if (num >= 1 && num <= options.length) {
                options[num - 1]?.action();
            }
        }
    });
    return (_jsxs(Box, { flexDirection: "column", borderStyle: "double", borderColor: THEME.primary, paddingX: 2, paddingY: 1, children: [_jsx(Text, { color: THEME.primary, bold: true, children: "ROBCO INDUSTRIES UNIFIED OPERATING SYSTEM v2077" }), _jsx(Text, { color: THEME.amber, bold: true, children: "\u2550\u2550\u2550 KRYPTA 73: POPIO\u0141Y NOWEGO \u015AWITU \u2550\u2550\u2550" }), _jsx(Box, { marginY: 1, flexDirection: "column", children: _jsx(Text, { color: THEME.secondary, children: "Tekstowa gra RPG w klimacie post-apo. Sterowanie: Strza\u0142ki \u2191/\u2193 + Enter lub klawisze 1-9." }) }), statusMessage && (_jsx(Box, { marginBottom: 1, children: _jsx(Text, { color: THEME.danger, children: statusMessage }) })), _jsx(Box, { flexDirection: "column", children: options.map((opt, idx) => (_jsxs(Text, { color: idx === selected ? THEME.amber : THEME.primary, bold: idx === selected, children: [idx === selected ? '> ' : '  ', idx + 1, ". ", opt.label] }, opt.label))) })] }));
};
