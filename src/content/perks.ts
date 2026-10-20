import { PerkDefinition } from '../types/game.js';

export const PERKS: Record<string, PerkDefinition> = {
  vats_sniper: {
    id: 'vats_sniper',
    name: 'Snajper V.A.T.S.',
    description: '+15% do szansy trafienia w głowę w systemie V.A.T.S.',
  },
  robco_hacker: {
    id: 'robco_hacker',
    name: 'Haker RobCo',
    description: '+20% do testów Inteligencji oraz hakowania terminali.',
  },
  wasteland_toughness: {
    id: 'wasteland_toughness',
    name: 'Twardziel Pustkowi',
    description: '+20 do maksymalnego HP oraz +3 do obrony pancerza.',
  },
  golden_tongue: {
    id: 'golden_tongue',
    name: 'Złoty Język',
    description: '+20% do testów Charyzmy oraz 15% korzystniejsze ceny u kupców.',
  },
  lead_belly: {
    id: 'lead_belly',
    name: 'Ołowiany Brzuch',
    description: 'Zmniejsza otrzymywane skażenie promieniowaniem (RAD) o 50%.',
  },
  reno_gunslinger: {
    id: 'reno_gunslinger',
    name: 'Rewolwerowiec z New Reno',
    description: '+10% do celności w V.A.T.S. dla wszystkich części ciała.',
  },
  cyborg_reflex: {
    id: 'cyborg_reflex',
    name: 'Implant Bojowy Vault-Tec',
    description: '+15 do maksymalnego HP oraz dodatkowe wyostrzenie zmysłów na Pustkowiach.',
  },
};
