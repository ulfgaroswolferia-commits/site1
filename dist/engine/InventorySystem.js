import { ITEMS } from '../content/items.js';
import { getEffectiveMaxHp } from './CharacterSystem.js';
const DEFAULT_AMMO_FOR_WEAPON = {
    pipe_pistol: { ammoId: 'ammo_10mm', qty: 12 },
    laser_rifle: { ammoId: 'ammo_energy', qty: 10 },
    plasma_rifle: { ammoId: 'ammo_energy', qty: 15 },
    sniper_rifle: { ammoId: 'ammo_rifle', qty: 12 },
    minigun: { ammoId: 'ammo_rifle', qty: 20 },
};
const AMMO_SHORT_NAMES = {
    ammo_10mm: '10mm',
    ammo_energy: 'Ogniwa MFC',
    ammo_rifle: '.308/5mm',
};
export function formatItemStats(item) {
    const parts = [];
    if (item.type === 'weapon') {
        if (item.damage !== undefined)
            parts.push(`DMG: ${item.damage}`);
        if (item.accuracyBonus !== undefined) {
            parts.push(`CEL: ${item.accuracyBonus >= 0 ? `+${item.accuracyBonus}` : item.accuracyBonus}%`);
        }
        if (item.apCost !== undefined)
            parts.push(`AP: ${item.apCost}`);
        if (item.isMelee) {
            parts.push('WRĘCZ (STR)');
        }
        else {
            const ammoName = item.ammoType ? (AMMO_SHORT_NAMES[item.ammoType] ?? item.ammoType) : '10mm';
            const perShot = item.ammoPerShot ?? 1;
            parts.push(`DYSTANS (PER) | AMMO: ${ammoName} (${perShot}/strzał)`);
        }
    }
    else if (item.type === 'armor') {
        if (item.defense !== undefined)
            parts.push(`OBR: +${item.defense}`);
        if (item.radResistance !== undefined)
            parts.push(`ODP. RAD: ${item.radResistance}%`);
    }
    else if (item.type === 'consumable') {
        if (item.healHp !== undefined)
            parts.push(`+HP: ${item.healHp}`);
        if (item.healRad !== undefined)
            parts.push(`-RAD: ${item.healRad}`);
        if (item.combatDamage !== undefined)
            parts.push(`DMG: ${item.combatDamage}`);
        if (item.stunChance !== undefined)
            parts.push(`OGŁUSZENIE: ${item.stunChance}%`);
    }
    else if (item.type === 'ammo') {
        parts.push(`PACZKA: +${item.packSize ?? 10} SZT.`);
    }
    else if (item.type === 'quest') {
        parts.push('PRZEDMIOT FABULARNY');
    }
    return parts.length > 0 ? `[${parts.join(' | ')}]` : '';
}
export function addItem(state, itemId, qty = 1) {
    if (qty <= 0 || !ITEMS[itemId])
        return state;
    const current = state.character.inventory[itemId] ?? 0;
    const nextInv = {
        ...state.character.inventory,
        [itemId]: current + qty,
    };
    // Przy zdobyciu broni palnej/energetycznej dołącz startowy magazynek amunicji
    const starterAmmo = DEFAULT_AMMO_FOR_WEAPON[itemId];
    if (starterAmmo && current === 0) {
        nextInv[starterAmmo.ammoId] = (nextInv[starterAmmo.ammoId] ?? 0) + starterAmmo.qty;
    }
    return {
        ...state,
        character: {
            ...state.character,
            inventory: nextInv,
        },
    };
}
export function removeItem(state, itemId, qty = 1) {
    const current = state.character.inventory[itemId] ?? 0;
    if (current <= 0 || qty <= 0)
        return state;
    const nextQty = current - qty;
    const nextInv = { ...state.character.inventory };
    if (nextQty <= 0) {
        delete nextInv[itemId];
    }
    else {
        nextInv[itemId] = nextQty;
    }
    let equippedWeaponId = state.character.equippedWeaponId;
    let equippedArmorId = state.character.equippedArmorId;
    if (!nextInv[itemId]) {
        if (equippedWeaponId === itemId)
            equippedWeaponId = null;
        if (equippedArmorId === itemId)
            equippedArmorId = null;
    }
    return {
        ...state,
        character: {
            ...state.character,
            inventory: nextInv,
            equippedWeaponId,
            equippedArmorId,
        },
    };
}
export function equipItem(state, itemId) {
    const item = ITEMS[itemId];
    const qty = state.character.inventory[itemId] ?? 0;
    if (!item || qty <= 0)
        return state;
    if (item.type === 'weapon') {
        return {
            ...state,
            character: {
                ...state.character,
                equippedWeaponId: itemId,
            },
            eventLog: [...state.eventLog.slice(-19), `Wyposażono broń: ${item.name}`],
        };
    }
    if (item.type === 'armor') {
        return {
            ...state,
            character: {
                ...state.character,
                equippedArmorId: itemId,
            },
            eventLog: [...state.eventLog.slice(-19), `Założono pancerz: ${item.name}`],
        };
    }
    return state;
}
export function useConsumable(state, itemId) {
    const item = ITEMS[itemId];
    const qty = state.character.inventory[itemId] ?? 0;
    if (!item || item.type !== 'consumable' || qty <= 0)
        return state;
    if (!item.healHp && !item.healRad)
        return state;
    let nextState = removeItem(state, itemId, 1);
    let newRad = nextState.character.rad;
    if (item.healRad) {
        newRad = Math.max(0, newRad - item.healRad);
    }
    const effectiveMax = getEffectiveMaxHp(nextState.character.maxHp, newRad);
    let newHp = nextState.character.hp;
    if (item.healHp) {
        const intBonus = nextState.character.attributes.INT * 3;
        newHp = Math.min(effectiveMax, newHp + item.healHp + intBonus);
    }
    else {
        newHp = Math.min(effectiveMax, newHp);
    }
    return {
        ...nextState,
        character: {
            ...nextState.character,
            rad: newRad,
            hp: newHp,
        },
        eventLog: [...nextState.eventLog.slice(-19), `Użyto: ${item.name}`],
    };
}
export function getBuyPrice(state, itemId) {
    const item = ITEMS[itemId];
    if (!item)
        return 0;
    const chaFactor = 1 - (state.character.attributes.CHA - 5) * 0.03;
    const perkFactor = state.character.perks.includes('golden_tongue') ? 0.85 : 1;
    return Math.max(5, Math.round(item.value * 1.2 * chaFactor * perkFactor));
}
export function getSellPrice(state, itemId) {
    const item = ITEMS[itemId];
    if (!item || item.type === 'quest')
        return 0;
    const chaFactor = 1 + (state.character.attributes.CHA - 5) * 0.03;
    const perkFactor = state.character.perks.includes('golden_tongue') ? 1.15 : 1;
    const buyPrice = getBuyPrice(state, itemId);
    return Math.min(buyPrice - 1, Math.max(1, Math.round(item.value * 0.55 * chaFactor * perkFactor)));
}
export function buyItem(state, itemId) {
    const item = ITEMS[itemId];
    if (!item)
        return state;
    const price = getBuyPrice(state, itemId);
    if (state.character.caps < price)
        return state;
    const qtyToAdd = item.type === 'ammo' ? (item.packSize ?? 10) : 1;
    const afterAdd = addItem(state, itemId, qtyToAdd);
    const label = item.type === 'ammo' ? `${item.name} (+${qtyToAdd} szt.)` : item.name;
    return {
        ...afterAdd,
        character: {
            ...afterAdd.character,
            caps: afterAdd.character.caps - price,
        },
        eventLog: [...afterAdd.eventLog.slice(-19), `Kupiono ${label} za ${price} kapsli.`],
    };
}
export function sellItem(state, itemId) {
    const item = ITEMS[itemId];
    const qty = state.character.inventory[itemId] ?? 0;
    if (!item || item.type === 'quest' || qty <= 0)
        return state;
    const sellQty = item.type === 'ammo' ? Math.min(qty, item.packSize ?? 10) : 1;
    const basePrice = getSellPrice(state, itemId);
    const price = item.type === 'ammo'
        ? Math.max(1, Math.round((basePrice * sellQty) / (item.packSize ?? 10)))
        : basePrice;
    const afterRemove = removeItem(state, itemId, sellQty);
    const label = item.type === 'ammo' ? `${item.name} (${sellQty} szt.)` : item.name;
    return {
        ...afterRemove,
        character: {
            ...afterRemove.character,
            caps: afterRemove.character.caps + price,
        },
        eventLog: [...afterRemove.eventLog.slice(-19), `Sprzedano ${label} za ${price} kapsli.`],
    };
}
