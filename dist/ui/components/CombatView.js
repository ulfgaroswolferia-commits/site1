import { jsxs as _jsxs, jsx as _jsx } from "react/jsx-runtime";
import { useState } from 'react';
import { Box, Text, useInput } from 'ink';
import { ITEMS } from '../../content/items.js';
import { getVatsHitChance } from '../../engine/CombatEngine.js';
import { formatItemStats } from '../../engine/InventorySystem.js';
import { renderBar, THEME } from '../theme.js';
export const CombatView = ({ state, onStandardAttack, onVatsAttack, onUseItem, onEndTurn, onFlee, }) => {
    const [vatsMenu, setVatsMenu] = useState(false);
    const [selected, setSelected] = useState(0);
    const combat = state.combat;
    if (!combat)
        return null;
    const stimpakCount = state.character.inventory.stimpak ?? 0;
    const radawayCount = state.character.inventory.radaway ?? 0;
    const grenadeCount = state.character.inventory.pulse_grenade ?? 0;
    const equippedWeapon = state.character.equippedWeaponId
        ? ITEMS[state.character.equippedWeaponId]
        : null;
    const ammoId = equippedWeapon && !equippedWeapon.isMelee ? equippedWeapon.ammoType : undefined;
    const ammoCount = ammoId ? (state.character.inventory[ammoId] ?? 0) : 0;
    const ammoPerShot = equippedWeapon && !equippedWeapon.isMelee ? (equippedWeapon.ammoPerShot ?? 1) : 0;
    const outOfAmmo = Boolean(equippedWeapon && !equippedWeapon.isMelee && ammoId && ammoCount < ammoPerShot);
    const baseAp = outOfAmmo ? 3 : (equippedWeapon?.apCost ?? 3);
    const vatsAp = baseAp + 1;
    const mainOptions = [
        {
            label: outOfAmmo
                ? `Uderzenie kolbą [BRAK AMUNICJI!] (${baseAp} AP)`
                : `Szybki Atak w korpus (${baseAp} AP${ammoPerShot > 0 ? `, -${ammoPerShot} ammo` : ''})`,
            action: onStandardAttack,
        },
        {
            label: `System Celowania V.A.T.S. (${vatsAp} AP${ammoPerShot > 0 && !outOfAmmo ? `, -${ammoPerShot} ammo` : ''})`,
            action: () => {
                setVatsMenu(true);
                setSelected(0);
            },
        },
        ...(stimpakCount > 0
            ? [{ label: `Użyj Stimpaka (${stimpakCount} szt., 2 AP)`, action: () => onUseItem('stimpak') }]
            : []),
        ...(radawayCount > 0
            ? [{ label: `Użyj AntyRadu (${radawayCount} szt., 2 AP)`, action: () => onUseItem('radaway') }]
            : []),
        ...(grenadeCount > 0
            ? [{ label: `Rzuć Granat Pulsacyjny (${grenadeCount} szt., 2 AP)`, action: () => onUseItem('pulse_grenade') }]
            : []),
        {
            label: `Zakończ Turę / Obrona (+${combat.playerAp * 5}% uniku)`,
            action: onEndTurn,
        },
        ...(!combat.isBoss
            ? [{ label: 'Ucieczka ze starcia [AGI] (3 AP)', action: onFlee }]
            : []),
    ];
    const vatsOptions = [
        {
            label: `[V.A.T.S.] Głowa — Szansa: ${getVatsHitChance(state, 'head')}% | Obrażenia x1.75 | 25% Ogłuszenie`,
            action: () => {
                onVatsAttack('head');
                setVatsMenu(false);
                setSelected(0);
            },
        },
        {
            label: `[V.A.T.S.] Tors — Szansa: ${getVatsHitChance(state, 'torso')}% | Obrażenia x1.15`,
            action: () => {
                onVatsAttack('torso');
                setVatsMenu(false);
                setSelected(0);
            },
        },
        {
            label: `[V.A.T.S.] Kończyny/Broń — Szansa: ${getVatsHitChance(state, 'limbs')}% | Obrażenia x0.85 | Osłabia atak wroga`,
            action: () => {
                onVatsAttack('limbs');
                setVatsMenu(false);
                setSelected(0);
            },
        },
        {
            label: 'Anuluj V.A.T.S.',
            action: () => {
                setVatsMenu(false);
                setSelected(0);
            },
        },
    ];
    const currentList = vatsMenu ? vatsOptions : mainOptions;
    useInput((input, key) => {
        if (key.escape && vatsMenu) {
            setVatsMenu(false);
            setSelected(0);
            return;
        }
        if (key.upArrow) {
            setSelected((prev) => (prev - 1 + currentList.length) % currentList.length);
        }
        else if (key.downArrow) {
            setSelected((prev) => (prev + 1) % currentList.length);
        }
        else if (key.return) {
            currentList[selected]?.action();
        }
        else {
            const num = parseInt(input, 10);
            if (num >= 1 && num <= currentList.length) {
                currentList[num - 1]?.action();
            }
        }
    });
    return (_jsxs(Box, { flexDirection: "column", children: [_jsxs(Box, { flexDirection: "column", borderStyle: "single", borderColor: THEME.danger, paddingX: 2, paddingY: 1, children: [_jsxs(Box, { justifyContent: "space-between", children: [_jsxs(Text, { color: THEME.danger, bold: true, children: ["WR\u00D3G: ", combat.enemyName, combat.enemyStunned ? ' [OGŁUSZONY]' : '', combat.enemyWeakenedTurns > 0 ? ' [OSŁABIONY]' : ''] }), _jsxs(Text, { color: THEME.amber, bold: true, children: ["TURA: ", combat.turn, " | PUNKTY AKCJI (AP): ", combat.playerAp, "/", combat.playerMaxAp] })] }), _jsxs(Text, { color: THEME.danger, children: ["HP WROGA: [", renderBar(combat.enemyHp, combat.enemyMaxHp, 16), "] ", combat.enemyHp, "/", combat.enemyMaxHp] }), _jsxs(Text, { color: THEME.cyan, children: ["TWOJA BRO\u0143: ", equippedWeapon ? `${equippedWeapon.name} ${formatItemStats(equippedWeapon)}` : 'Pięści [DMG: 8 | AP: 3]', ammoId
                                ? ` | Zapas amunicji: ${ammoCount} szt.${outOfAmmo ? ' [BRAK AMUNICJI!]' : ''}`
                                : ''] }), _jsx(Box, { flexDirection: "column", marginTop: 1, children: combat.log.slice(-4).map((entry, idx) => (_jsxs(Text, { color: THEME.primary, children: ["\u2022 ", entry] }, idx))) })] }), _jsxs(Box, { flexDirection: "column", borderStyle: "single", borderColor: THEME.primary, paddingX: 2, paddingY: 1, children: [_jsx(Text, { color: THEME.amber, bold: true, children: vatsMenu ? `SYSTEM V.A.T.S. — WYBIERZ CEL (${vatsAp} AP):` : 'DOSTĘPNE AKCJE BOJOWE:' }), currentList.map((opt, idx) => (_jsxs(Text, { color: idx === selected ? THEME.amber : THEME.primary, bold: idx === selected, children: [idx === selected ? '> ' : '  ', idx + 1, ". ", opt.label] }, opt.label)))] })] }));
};
