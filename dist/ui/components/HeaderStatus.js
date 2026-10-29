import { jsxs as _jsxs, jsx as _jsx } from "react/jsx-runtime";
import { Box, Text } from 'ink';
import { getEffectiveMaxHp, XP_THRESHOLDS } from '../../engine/CharacterSystem.js';
import { ITEMS } from '../../content/items.js';
import { LOCATIONS } from '../../content/scenes.js';
import { renderBar, THEME } from '../theme.js';
export const HeaderStatus = ({ state, modeLabel }) => {
    const { character } = state;
    const effMaxHp = getEffectiveMaxHp(character.maxHp, character.rad);
    const nextXp = XP_THRESHOLDS[character.level] ?? 9999;
    const locName = LOCATIONS[state.currentLocationId]?.name ?? state.currentLocationId;
    const waterDays = state.counters?.water_days_left ?? 60;
    const openness = state.counters?.vault_openness ?? 0;
    const factionLabel = openness >= 2 ? 'OTWARCI' : openness <= -1 ? 'IZOLACJONIŚCI' : 'RÓWNOWAGA';
    const equippedWeapon = character.equippedWeaponId ? ITEMS[character.equippedWeaponId] : null;
    const ammoId = equippedWeapon && !equippedWeapon.isMelee ? equippedWeapon.ammoType : undefined;
    const ammoQty = ammoId ? (character.inventory[ammoId] ?? 0) : null;
    return (_jsxs(Box, { flexDirection: "column", borderStyle: "double", borderColor: THEME.primary, paddingX: 1, children: [_jsxs(Box, { justifyContent: "space-between", children: [_jsxs(Text, { color: THEME.primary, bold: true, children: ["PIP-BOY 3000 MK IV \u2014 ", character.name.toUpperCase()] }), _jsxs(Text, { color: THEME.amber, bold: true, children: ["TRYB: ", modeLabel] })] }), _jsxs(Box, { justifyContent: "space-between", children: [_jsxs(Text, { color: character.hp <= effMaxHp * 0.3 ? THEME.danger : THEME.primary, children: ["HP: [", renderBar(character.hp, effMaxHp, 10), "] ", character.hp, "/", effMaxHp] }), _jsxs(Text, { color: character.rad >= 30 ? THEME.amber : THEME.secondary, children: ["RAD: [", renderBar(character.rad, 100, 8), "] ", character.rad, "%"] }), _jsxs(Text, { color: THEME.amber, children: ["KAPSLE: ", character.caps] }), _jsx(Text, { color: ammoQty !== null && ammoQty <= 4 ? THEME.danger : THEME.secondary, children: ammoQty !== null ? `AMMO: ${ammoQty}` : 'BROŃ: WRĘCZ' }), _jsxs(Text, { color: THEME.cyan, children: ["POZ: ", character.level, " (", character.xp, "/", nextXp, " XP)", character.pendingLevelUps > 0 ? ' [AWANS! Naciśnij S]' : ''] })] }), _jsxs(Box, { justifyContent: "space-between", children: [_jsxs(Text, { color: THEME.secondary, children: ["LOKACJA: ", locName] }), _jsxs(Text, { color: THEME.muted, children: ["KRYPTA 73: ", factionLabel] }), state.flags.arroyo_unlocked && (_jsxs(Text, { color: waterDays <= 18 ? THEME.danger : THEME.amber, bold: true, children: ["ZAPAS WODY: ", waterDays, " DNI"] }))] })] }));
};
