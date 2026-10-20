import { EncounterDefinition } from '../types/game.js';

export const ENCOUNTERS: EncounterDefinition[] = [
  {
    id: 'encounter_raiders',
    title: 'Zasadzka na Pustkowiach: Łupieżcy!',
    description:
      'Zza wraku przedwojennego autobusu wyskakuje uzbrojony Bandyta z Pustkowi! „Kapsle albo życie, szczurze z Krypty!”',
    choices: [
      {
        id: 'fight_raider',
        text: 'Dobyj broni i walcz!',
        nextSceneId: '__RESUME_TRAVEL__',
        effect: {
          startCombat: {
            enemyId: 'wasteland_raider',
            victorySceneId: '__RESUME_TRAVEL__',
            fleeSceneId: '__RESUME_TRAVEL__',
          },
        },
      },
      {
        id: 'intimidate_raider',
        text: '[STR] Zastrasz bandytę pokazem brutalnej siły',
        skillCheck: { attribute: 'STR', difficulty: 6 },
        nextSceneId: '__RESUME_TRAVEL__',
        failSceneId: 'encounter_raiders',
        effect: { xpDelta: 45 },
        failEffect: {
          startCombat: {
            enemyId: 'wasteland_raider',
            victorySceneId: '__RESUME_TRAVEL__',
            fleeSceneId: '__RESUME_TRAVEL__',
          },
        },
      },
      {
        id: 'bribe_raider',
        text: 'Zapłać 25 kapsli myta za spokojne przejście',
        requiredCaps: 25,
        nextSceneId: '__RESUME_TRAVEL__',
        effect: { capsDelta: -25 },
      },
    ],
  },
  {
    id: 'encounter_radstorm',
    title: 'Zjawisko Atmosferyczne: Burza Radioaktywna',
    description:
      'Niebo przybiera zielonkawy odcień, a licznik Geigera w Twoim Pip-Boyu zaczyna szaleńczo trzeszczeć.',
    choices: [
      {
        id: 'endure_storm',
        text: '[END] Przedrzyj się przez burzę, osłaniając drogi oddechowe',
        skillCheck: { attribute: 'END', difficulty: 6 },
        nextSceneId: '__RESUME_TRAVEL__',
        failSceneId: '__RESUME_TRAVEL__',
        effect: { xpDelta: 35 },
        failEffect: { radDelta: 18, hpDelta: -10 },
      },
      {
        id: 'shelter_storm',
        text: '[PER] Wypatrz szczelinę skalną i przeczekaj opad',
        skillCheck: { attribute: 'PER', difficulty: 5 },
        nextSceneId: '__RESUME_TRAVEL__',
        failSceneId: '__RESUME_TRAVEL__',
        effect: { xpDelta: 35 },
        failEffect: { radDelta: 12 },
      },
    ],
  },
  {
    id: 'encounter_caravan',
    title: 'Spotkanie na Szlaku: Wędrowny Karawaniarz',
    description:
      'Spotykasz kupca prowadzącego dwugłowego bramina obładowanego skrzyniami ze sprzętem.',
    choices: [
      {
        id: 'trade_caravan',
        text: 'Pohandluj z Karawaniarzem',
        nextSceneId: 'encounter_caravan',
        effect: { openMerchant: 'caravan_trader' },
      },
      {
        id: 'continue_journey',
        text: 'Ruszy dalej w stronę celu',
        nextSceneId: '__RESUME_TRAVEL__',
      },
    ],
  },
  {
    id: 'encounter_scorpion',
    title: 'Zagrożenie Biologiczne: Radskorpion!',
    description:
      'Spod spękanego asfaltu wynurza się olbrzymi Radskorpion, unosząc jadowity kolec ogonowy!',
    choices: [
      {
        id: 'fight_scorpion',
        text: 'Staw czoła bestii!',
        nextSceneId: '__RESUME_TRAVEL__',
        effect: {
          startCombat: {
            enemyId: 'rad_scorpion',
            victorySceneId: '__RESUME_TRAVEL__',
            fleeSceneId: '__RESUME_TRAVEL__',
          },
        },
      },
      {
        id: 'evade_scorpion',
        text: '[AGI] Odskocz w bok i ucieknij po skałach',
        skillCheck: { attribute: 'AGI', difficulty: 6 },
        nextSceneId: '__RESUME_TRAVEL__',
        failSceneId: '__RESUME_TRAVEL__',
        effect: { xpDelta: 40 },
        failEffect: {
          startCombat: {
            enemyId: 'rad_scorpion',
            victorySceneId: '__RESUME_TRAVEL__',
            fleeSceneId: '__RESUME_TRAVEL__',
          },
        },
      },
    ],
  },
];
