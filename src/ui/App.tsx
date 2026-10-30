import React, { useState } from 'react';
import { Box, Text, useApp, useInput, useStdout } from 'ink';
import { AttributeKey, BodyPart, DifficultyLevel, GameState, LocationId, SceneChoice } from '../types/game.js';
import { createNewGameState, levelUpCharacter } from '../engine/CharacterSystem.js';
import { selectChoice } from '../engine/DialogueEngine.js';
import {
  attemptFlee,
  endPlayerTurn,
  performPlayerAttack,
  useCombatItem,
} from '../engine/CombatEngine.js';
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

type ScreenMode = 'title' | 'creator' | 'game';
type OverlayTab = null | 'stats' | 'inventory' | 'quests' | 'map';

export const App: React.FC = () => {
  const { exit } = useApp();
  const { stdout } = useStdout();
  const [screen, setScreen] = useState<ScreenMode>('title');
  const [state, setState] = useState<GameState | null>(null);
  const [overlay, setOverlay] = useState<OverlayTab>(null);
  const [menuMessage, setMenuMessage] = useState<string | null>(null);
  const [difficulty, setDifficulty] = useState<DifficultyLevel>('normal');

  const cols = stdout?.columns ?? 100;
  const rows = stdout?.rows ?? 30;

  useInput((input) => {
    if (screen !== 'game' || !state) return;

    const lower = input.toLowerCase();
    if (lower === 's') {
      setOverlay((prev) => (prev === 'stats' ? null : 'stats'));
    } else if (lower === 'i') {
      setOverlay((prev) => (prev === 'inventory' ? null : 'inventory'));
    } else if (lower === 'q' && !state.activeMerchantId) {
      setOverlay((prev) => (prev === 'quests' ? null : 'quests'));
    } else if (lower === 'm' && !state.combat && !state.activeMerchantId) {
      setOverlay((prev) => (prev === 'map' ? null : 'map'));
    } else if (lower === 'z' || input === '\u001b[15~') {
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
    return (
      <Box borderStyle="double" borderColor={THEME.amber} padding={1}>
        <Text color={THEME.amber}>
          TERMINAL ROBCO: Rozmiar okna terminala ({cols}x{rows}) jest zbyt mały. Powiększ panel terminala do min. 72x24.
        </Text>
      </Box>
    );
  }

  if (screen === 'title') {
    return (
      <MainMenu
        canLoad={hasSaveGame()}
        statusMessage={menuMessage}
        onNewGame={(diff) => {
          setDifficulty(diff);
          setScreen('creator');
        }}
        onLoadGame={() => {
          const loaded = loadGame();
          if (loaded) {
            setState(loaded);
            setScreen('game');
          } else {
            setMenuMessage('Nie udało się wczytać pliku zapisu.');
          }
        }}
        onExit={() => exit()}
      />
    );
  }

  if (screen === 'creator') {
    return (
      <CharacterCreator
        difficulty={difficulty}
        onCancel={() => setScreen('title')}
        onConfirm={(name, attrs) => {
          const initial = createNewGameState(name, attrs, difficulty);
          setState(initial);
          setScreen('game');
        }}
      />
    );
  }

  if (!state) return null;

  const handleChoice = (choice: SceneChoice) => {
    if (choice.effect?.openWorldMap) {
      setOverlay('map');
      return;
    }
    const next = selectChoice(state, choice);
    setState(next);
  };

  let modeLabel = 'EKSPLORACJA';
  if (state.combat) modeLabel = 'WALKA V.A.T.S.';
  else if (state.activeMerchantId) modeLabel = 'HANDEL';
  else if (overlay) modeLabel = `PIP-BOY (${overlay.toUpperCase()})`;

  return (
    <Box flexDirection="column">
      <HeaderStatus state={state} modeLabel={modeLabel} />

      {overlay === 'map' ? (
        <WorldMapView
          state={state}
          onClose={() => setOverlay(null)}
          onSelectLocation={(locId: LocationId) => {
            const next = travelToLocation(state, locId);
            saveGame(next);
            setState(next);
            setOverlay(null);
          }}
        />
      ) : overlay ? (
        <PipBoyOverlay
          state={state}
          tab={overlay}
          onClose={() => setOverlay(null)}
          onEquipOrUse={(itemId: string) => {
            const item = ITEMS[itemId];
            if (!item) return;
            if (item.type === 'weapon' || item.type === 'armor') {
              setState(equipItem(state, itemId));
            } else if (item.type === 'consumable') {
              setState(useConsumable(state, itemId));
            }
          }}
          onLevelUp={(attr: AttributeKey, perkId: string) => {
            setState(levelUpCharacter(state, attr, perkId));
          }}
        />
      ) : state.activeMerchantId ? (
        <MerchantView
          state={state}
          onBuy={(id) => setState(buyItem(state, id))}
          onSell={(id) => setState(sellItem(state, id))}
          onClose={() => setState({ ...state, activeMerchantId: null })}
        />
      ) : state.combat ? (
        <CombatView
          state={state}
          onStandardAttack={() => setState(performPlayerAttack(state, 'standard'))}
          onVatsAttack={(part: BodyPart) => setState(performPlayerAttack(state, 'vats', part))}
          onUseItem={(itemId: string) => setState(useCombatItem(state, itemId))}
          onEndTurn={() => setState(endPlayerTurn(state))}
          onFlee={() => setState(attemptFlee(state))}
        />
      ) : (
        <ExplorationView
          state={state}
          onSelectChoice={handleChoice}
          onReturnToTitle={() => {
            setOverlay(null);
            setScreen('title');
          }}
        />
      )}

      <Box
        borderStyle="single"
        borderColor={THEME.secondary}
        paddingX={1}
        paddingY={0}
        justifyContent="space-between"
      >
        <Text color={THEME.muted}>
          [↑/↓/Enter] Wybór | [S] Karta | [I] Plecak | [Q] Misje | [M] Mapa | [Z] Zapis
        </Text>
      </Box>
    </Box>
  );
};
