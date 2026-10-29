import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState, useEffect } from 'react';
import { Box, Text, useInput } from 'ink';
import { getAvailableChoices, getCurrentScene, getSceneDescription } from '../../engine/DialogueEngine.js';
import { calculateSkillCheckChance } from '../../engine/CharacterSystem.js';
import { THEME } from '../theme.js';
export const ExplorationView = ({ state, onSelectChoice, onReturnToTitle, }) => {
    const scene = getCurrentScene(state);
    const choices = getAvailableChoices(state, scene.choices);
    const [selected, setSelected] = useState(0);
    useEffect(() => {
        setSelected(0);
    }, [scene.id, choices.length]);
    useInput((input, key) => {
        if (scene.isEnding) {
            if (key.return || input === '1') {
                onReturnToTitle();
            }
            return;
        }
        if (choices.length === 0)
            return;
        if (key.upArrow) {
            setSelected((prev) => (prev - 1 + choices.length) % choices.length);
        }
        else if (key.downArrow) {
            setSelected((prev) => (prev + 1) % choices.length);
        }
        else if (key.return) {
            const chosen = choices[selected];
            if (chosen)
                onSelectChoice(chosen);
        }
        else {
            const num = parseInt(input, 10);
            if (num >= 1 && num <= choices.length) {
                onSelectChoice(choices[num - 1]);
            }
        }
    });
    const lastLog = state.eventLog[state.eventLog.length - 1];
    return (_jsxs(Box, { flexDirection: "column", children: [_jsxs(Box, { flexDirection: "column", borderStyle: "single", borderColor: THEME.primary, paddingX: 2, paddingY: 0, children: [_jsx(Text, { color: THEME.amber, bold: true, children: scene.title }), _jsx(Box, { marginY: 0, children: _jsx(Text, { color: THEME.primary, children: getSceneDescription(state, scene) }) }), lastLog && (_jsx(Box, { borderStyle: "round", borderColor: THEME.secondary, paddingX: 1, marginY: 0, children: _jsxs(Text, { color: THEME.cyan, children: ["LOG: ", lastLog] }) }))] }), _jsx(Box, { flexDirection: "column", borderStyle: "single", borderColor: THEME.primary, paddingX: 2, paddingY: 0, children: scene.isEnding ? (_jsx(Text, { color: THEME.amber, bold: true, children: '> 1. Powrót do Menu Głównego (Enter)' })) : (choices.map((c, idx) => {
                    const chanceText = c.skillCheck
                        ? ` (Szansa: ${calculateSkillCheckChance(state, c.skillCheck)}%)`
                        : '';
                    const isSel = idx === selected;
                    return (_jsxs(Text, { color: isSel ? THEME.amber : THEME.primary, bold: isSel, children: [isSel ? '> ' : '  ', idx + 1, ". ", c.text, chanceText] }, c.id));
                })) })] }));
};
