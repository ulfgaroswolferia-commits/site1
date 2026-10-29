import { jsxs as _jsxs, jsx as _jsx } from "react/jsx-runtime";
import { useState } from 'react';
import { Box, Text, useApp, useInput, useStdout } from 'ink';
import { createNewGameState, levelUpCharacter } from '../engine/CharacterSystem.js';
import { selectChoice } from '../engine/DialogueEngine.js';
import { attemptFlee, endPlayerTurn, performPlayerAttack, useCombatItem, } from '../engine/CombatEngine.js';
import { buyItem, equipItem, sellItem, useConsumable } from '../engine/InventorySystem.js';
import { travelToLocation } from '../engine/WastelandTravel.js';
import { hasSaveGame, loadGame, saveGame } from '../engine/SaveManager.js';
import { ITEMS } from '../content/items.js';
import { HeaderStatus } from './components/HeaderStatus.js';
import { MainMenu } from './components/MainMenu.js';
import { CharacterCreator } from './components/CharacterCreator.js';
import { ExplorationView } from './components/ExplorationView.js';
import { CombatView } from './components/CombatView.js';
import { MerchantView } from './components/MerchantView.js';
import { WorldMapView } from './components/WorldMapView.js';
import { PipBoyOverlay } from './components/PipBoyOverlay.js';
import { THEME } from './theme.js';
export const App = () => {
    const { exit } = useApp();
    const { stdout } = useStdout();
    const [screen, setScreen] = useState('title');
    const [state, setState] = useState(null);
    const [overlay, setOverlay] = useState(null);
    const [menuMessage, setMenuMessage] = useState(null);
    const cols = stdout?.columns ?? 100;
    const rows = stdout?.rows ?? 30;
    useInput((input) => {
        if (screen !== 'game' || !state)
            return;
        const lower = input.toLowerCase();
        if (lower === 's') {
            setOverlay((prev) => (prev === 'stats' ? null : 'stats'));
        }
        else if (lower === 'i') {
            setOverlay((prev) => (prev === 'inventory' ? null : 'inventory'));
        }
        else if (lower === 'q' && !state.activeMerchantId) {
            setOverlay((prev) => (prev === 'quests' ? null : 'quests'));
        }
        else if (lower === 'm' && !state.combat && !state.activeMerchantId) {
            setOverlay((prev) => (prev === 'map' ? null : 'map'));
        }
        else if (lower === 'z' || input === '\u001b[15~') {
            const ok = saveGame(state);
            setState({
                ...state,
                eventLog: [
                    ...state.eventLog.slice(-19),
                    ok ? 'Stan gry zapisany pomyślnie (saves/savegame.json).' : 'Błąd zapisu gry!',
                ],
            });
        }
    });
    if (cols < 72 || rows < 24) {
        return (_jsx(Box, { borderStyle: "double", borderColor: THEME.amber, padding: 1, children: _jsxs(Text, { color: THEME.amber, children: ["TERMINAL ROBCO: Rozmiar okna terminala (", cols, "x", rows, ") jest zbyt ma\u0142y. Powi\u0119ksz panel terminala do min. 72x24."] }) }));
    }
    if (screen === 'title') {
        return (_jsx(MainMenu, { canLoad: hasSaveGame(), statusMessage: menuMessage, onNewGame: () => setScreen('creator'), onLoadGame: () => {
                const loaded = loadGame();
                if (loaded) {
                    setState(loaded);
                    setScreen('game');
                }
                else {
                    setMenuMessage('Nie udało się wczytać pliku zapisu.');
                }
            }, onExit: () => exit() }));
    }
    if (screen === 'creator') {
        return (_jsx(CharacterCreator, { onCancel: () => setScreen('title'), onConfirm: (name, attrs) => {
                const initial = createNewGameState(name, attrs);
                setState(initial);
                setScreen('game');
            } }));
    }
    if (!state)
        return null;
    const handleChoice = (choice) => {
        if (choice.effect?.openWorldMap) {
            setOverlay('map');
            return;
        }
        const next = selectChoice(state, choice);
        setState(next);
    };
    let modeLabel = 'EKSPLORACJA';
    if (state.combat)
        modeLabel = 'WALKA V.A.T.S.';
    else if (state.activeMerchantId)
        modeLabel = 'HANDEL';
    else if (overlay)
        modeLabel = `PIP-BOY (${overlay.toUpperCase()})`;
    return (_jsxs(Box, { flexDirection: "column", children: [_jsx(HeaderStatus, { state: state, modeLabel: modeLabel }), overlay === 'map' ? (_jsx(WorldMapView, { state: state, onClose: () => setOverlay(null), onSelectLocation: (locId) => {
                    const next = travelToLocation(state, locId);
                    saveGame(next);
                    setState(next);
                    setOverlay(null);
                } })) : overlay ? (_jsx(PipBoyOverlay, { state: state, tab: overlay, onClose: () => setOverlay(null), onEquipOrUse: (itemId) => {
                    const item = ITEMS[itemId];
                    if (!item)
                        return;
                    if (item.type === 'weapon' || item.type === 'armor') {
                        setState(equipItem(state, itemId));
                    }
                    else if (item.type === 'consumable') {
                        setState(useConsumable(state, itemId));
                    }
                }, onLevelUp: (attr, perkId) => {
                    setState(levelUpCharacter(state, attr, perkId));
                } })) : state.activeMerchantId ? (_jsx(MerchantView, { state: state, onBuy: (id) => setState(buyItem(state, id)), onSell: (id) => setState(sellItem(state, id)), onClose: () => setState({ ...state, activeMerchantId: null }) })) : state.combat ? (_jsx(CombatView, { state: state, onStandardAttack: () => setState(performPlayerAttack(state, 'standard')), onVatsAttack: (part) => setState(performPlayerAttack(state, 'vats', part)), onUseItem: (itemId) => setState(useCombatItem(state, itemId)), onEndTurn: () => setState(endPlayerTurn(state)), onFlee: () => setState(attemptFlee(state)) })) : (_jsx(ExplorationView, { state: state, onSelectChoice: handleChoice, onReturnToTitle: () => {
                    setOverlay(null);
                    setScreen('title');
                } })), _jsx(Box, { borderStyle: "single", borderColor: THEME.secondary, paddingX: 1, paddingY: 0, justifyContent: "space-between", children: _jsx(Text, { color: THEME.muted, children: "[\u2191/\u2193 + Enter lub 1-9] Wyb\u00F3r | [S] Statystyki | [I] Ekwipunek | [Q] Zadania | [M] Mapa | [Z] Zapisz" }) })] }));
};
