import { ENCOUNTERS } from './encounters.js';
import { LocationDefinition, LocationId, SceneDefinition } from '../types/game.js';

export const LOCATIONS: Record<LocationId, LocationDefinition> = {
  vault_73: {
    id: 'vault_73',
    name: 'Krypta 73',
    description: 'Podziemny schron Vault-Tec. Twój dom, któremu grozi wygaśnięcie reaktora.',
    entrySceneId: 'vault_atrium',
  },
  rusty_crater: {
    id: 'rusty_crater',
    name: 'Złomowisko „Rdzawy Krater”',
    description: 'Ufortyfikowana osada handlowa zbudowana wokół krateru i wraku frachtowca.',
    entrySceneId: 'crater_hub',
  },
  robco_relay: {
    id: 'robco_relay',
    name: 'Stacja Przekaźnikowa RobCo',
    description: 'Przedwojenny kompleks badawczy pełen terminali i zdziczałych ghuli.',
    entrySceneId: 'relay_entrance',
  },
  rust_fortress: {
    id: 'rust_fortress',
    name: 'Twierdza Dzieci Rdzy',
    description: 'Stara rafineria przekształcona w cytadelę Watażki Korga.',
    entrySceneId: 'fortress_gate',
    requiredFlag: 'found_relay_clue',
  },
};

const baseScenes: Record<string, SceneDefinition> = {
  vault_intro: {
    id: 'vault_intro',
    title: 'Krypta 73 — Gabinet Nadzorcy',
    locationId: 'vault_73',
    description:
      'Czerwone lampy alarmowe pulsują w rytmie syreny. Nadzorca pochyla się nad konsolą RobCo:\n\n„Wędrowcze, nasz stabilizator reaktora uległ nieodwracalnej korozji. Mamy zapas energii na góra kilka dni. Przedwojenne rejestry wskazują, że w Stacji Przekaźnikowej RobCo znajduje się zapasowy Moduł Stabilizatora G.E.C.K.-Lite. Najpierw odwiedź osadę Rdzawy Krater — tamtejsi zwiadowcy znają drogę.”',
    choices: [
      {
        id: 'ask_supplies',
        text: '[CHA] Przekonaj Nadzorcę, by wydał dodatkowe kapsle i Stimpaka z rezerwy',
        forbiddenFlag: 'overseer_bonus_taken',
        skillCheck: { attribute: 'CHA', difficulty: 5 },
        nextSceneId: 'vault_atrium',
        failSceneId: 'vault_atrium',
        effect: {
          capsDelta: 45,
          addItems: { stimpak: 1 },
          xpDelta: 35,
          setFlags: { overseer_bonus_taken: true },
          setQuestState: { questId: 'main_stabilizer', status: 'active', stage: 'leave_vault' },
        },
        failEffect: {
          setFlags: { overseer_bonus_taken: true },
          setQuestState: { questId: 'main_stabilizer', status: 'active', stage: 'leave_vault' },
        },
      },
      {
        id: 'go_atrium',
        text: 'Przyjmij rozkaz i zejdź do Atrium Krypty 73',
        nextSceneId: 'vault_atrium',
        effect: {
          setQuestState: { questId: 'main_stabilizer', status: 'active', stage: 'leave_vault' },
        },
      },
    ],
  },
  vault_atrium: {
    id: 'vault_atrium',
    title: 'Krypta 73 — Atrium i Śluza Wyjściowa',
    locationId: 'vault_73',
    description:
      'Stoisz przed monumentalnymi, zębatymi wrotami Krypty 73. Przy pulpicie sterującym stoi uszkodzony Strażnik RobCo — w jego zacisku widnieje Karta Dostępu Strażnika otwierająca zbrojownię oraz śluzę.',
    choices: [
      {
        id: 'deliver_stabilizer',
        text: '[FINAŁ] Zamontuj Moduł Stabilizatora G.E.C.K.-Lite w reaktorze Krypty 73',
        requiredItem: 'geck_stabilizer',
        nextSceneId: 'ending_vault_saved',
        effect: {
          setQuestState: { questId: 'main_stabilizer', status: 'completed', stage: 'completed' },
          xpDelta: 250,
        },
      },
      {
        id: 'hack_protectron',
        text: '[INT] Przeprogramuj rdzeń Strażnika RobCo i odbierz Kartę Dostępu',
        forbiddenFlag: 'vault_robot_resolved',
        skillCheck: { attribute: 'INT', difficulty: 5 },
        nextSceneId: 'vault_atrium',
        failSceneId: 'vault_atrium',
        effect: {
          addItems: { security_keycard: 1 },
          xpDelta: 45,
          setFlags: { vault_robot_resolved: true },
        },
        failEffect: {
          setFlags: { vault_robot_resolved: true },
          startCombat: {
            enemyId: 'damaged_protectron',
            victorySceneId: 'vault_atrium',
          },
        },
      },
      {
        id: 'fight_protectron',
        text: 'Zniszcz uszkodzonego robota i wyrwij Kartę Dostępu siłą',
        forbiddenFlag: 'vault_robot_resolved',
        nextSceneId: 'vault_atrium',
        effect: {
          setFlags: { vault_robot_resolved: true },
          startCombat: {
            enemyId: 'damaged_protectron',
            victorySceneId: 'vault_atrium',
          },
        },
      },
      {
        id: 'loot_armory',
        text: 'Otwórz szafkę zbrojowni Kartą Dostępu (Zębata Maczeta + Granat Pulsacyjny)',
        requiredItem: 'security_keycard',
        forbiddenFlag: 'vault_armory_looted',
        nextSceneId: 'vault_atrium',
        effect: {
          addItems: { rusty_machete: 1, pulse_grenade: 1 },
          setFlags: { vault_armory_looted: true },
          xpDelta: 25,
        },
      },
      {
        id: 'open_map_from_vault',
        text: 'Otwórz wrota śluzy i wyjdź na Pustkowia [Mapa Świata]',
        nextSceneId: 'vault_atrium',
        effect: { openWorldMap: true },
      },
    ],
  },
  crater_hub: {
    id: 'crater_hub',
    title: 'Złomowisko „Rdzawy Krater” — Plac Centralny',
    locationId: 'rusty_crater',
    description:
      'Gwar osady miesza się z zapachem ozonu i pieczonego mięsa kretoszczura. Po lewej Kupiec Szrot rozłożył stragan z bronią, po prawej świeci neon kliniki Doktor Igi, a w głębi widać wejście do baru „Pod Opadem”.',
    choices: [
      {
        id: 'trade_szrot',
        text: 'Porozmawiaj z Kupcem Szrotem [Handel]',
        nextSceneId: 'crater_hub',
        effect: { openMerchant: 'szrot_trader' },
      },
      {
        id: 'visit_clinic',
        text: 'Wejdź do kliniki polowej Doktor Igi',
        nextSceneId: 'crater_clinic',
      },
      {
        id: 'visit_bar',
        text: 'Wejdź do baru „Pod Opadem” (wywiad o Stacji RobCo)',
        nextSceneId: 'crater_bar',
      },
      {
        id: 'leave_crater',
        text: 'Wyrusz w drogę przez Pustkowia [Mapa Świata]',
        nextSceneId: 'crater_hub',
        effect: { openWorldMap: true },
      },
    ],
  },
  crater_clinic: {
    id: 'crater_clinic',
    title: 'Rdzawy Krater — Klinika Doktor Igi',
    locationId: 'rusty_crater',
    description:
      'Doktor Iga przeciera rękawice spirytusem: „Kolejny wędrowiec z popromienną gorączką? Mogę Cię połatać za 20 kapsli. A jeśli wybierasz się w okolice Stacji Przekaźnikowej RobCo — ghule rozszarpały mojego kuriera. Odzyskaj moją Skrzynkę Medyczną, a dostaniesz Pancerz Skórzany i sporo kapsli.”',
    choices: [
      {
        id: 'accept_med_quest',
        text: 'Obiecaj odnaleźć Skrzynkę Medyczną przy Stacji RobCo',
        forbiddenFlag: 'med_quest_started',
        nextSceneId: 'crater_clinic',
        effect: {
          setFlags: { med_quest_started: true },
          setQuestState: { questId: 'side_meds', status: 'active', stage: 'find_crate' },
        },
      },
      {
        id: 'turn_in_med_quest',
        text: 'Oddaj Doktor Idze odzyskaną Skrzynkę Medyczną (+100 kapsli, +Pancerz Skórzany)',
        requiredItem: 'medical_crate',
        forbiddenFlag: 'med_quest_completed',
        nextSceneId: 'crater_clinic',
        effect: {
          removeItems: { medical_crate: 1 },
          capsDelta: 100,
          addItems: { leather_armor: 1, stimpak: 2 },
          xpDelta: 90,
          setFlags: { med_quest_completed: true },
          setQuestState: { questId: 'side_meds', status: 'completed', stage: 'completed' },
        },
      },
      {
        id: 'pay_heal',
        text: 'Zapłać 20 kapsli za pełną dekontaminację (-100 RAD, +80 HP)',
        requiredCaps: 20,
        nextSceneId: 'crater_clinic',
        effect: { capsDelta: -20, radDelta: -100, hpDelta: 80 },
      },
      {
        id: 'back_crater_from_clinic',
        text: 'Wróć na Plac Centralny',
        nextSceneId: 'crater_hub',
      },
    ],
  },
  crater_bar: {
    id: 'crater_bar',
    title: 'Rdzawy Krater — Bar „Pod Opadem”',
    locationId: 'rusty_crater',
    description:
      'Stary najemnik o ksywie „Zgorzel” sączy radioaktywny bimber: „Szukasz części ze Stacji Przekaźnikowej RobCo? Uważaj na wieżyczki i ghule. Słyszałem też, że fanatycy Watażki Korga z Dzieci Rdzy splądrowali tamtejszy skarbiec.”',
    choices: [
      {
        id: 'ask_robco_codes',
        text: '[CHA] Postaw najemnikowi kolejkę (10 kapsli) i wyciągnij skrót do laboratorium RobCo',
        requiredCaps: 10,
        forbiddenFlag: 'Shortcut_robco_known',
        skillCheck: { attribute: 'CHA', difficulty: 5 },
        nextSceneId: 'crater_bar',
        failSceneId: 'crater_bar',
        effect: {
          capsDelta: -10,
          xpDelta: 40,
          setFlags: { Shortcut_robco_known: true },
          setQuestState: { questId: 'main_stabilizer', status: 'active', stage: 'search_relay' },
        },
        failEffect: {
          capsDelta: -10,
          setQuestState: { questId: 'main_stabilizer', status: 'active', stage: 'search_relay' },
        },
      },
      {
        id: 'back_crater_from_bar',
        text: 'Wróć na Plac Centralny',
        nextSceneId: 'crater_hub',
        effect: {
          setQuestState: { questId: 'main_stabilizer', status: 'active', stage: 'search_relay' },
        },
      },
    ],
  },
  relay_entrance: {
    id: 'relay_entrance',
    title: 'Stacja Przekaźnikowa RobCo — Dziedziniec',
    locationId: 'robco_relay',
    description:
      'Przed bunkrem wznosi się zardzewiała wieża antenowa. Wśród wraków wojskowych ciężarówek krąży wygłodniały Zdziczały Ghul, a obok rozbitego ambulansu leży zapieczętowana skrzynka z czerwonym krzyżem.',
    choices: [
      {
        id: 'fight_relay_ghoul',
        text: 'Zlikwiduj Zdziczałego Ghula pilnującego wejścia',
        forbiddenFlag: 'relay_ghoul_cleared',
        nextSceneId: 'relay_entrance',
        effect: {
          setFlags: { relay_ghoul_cleared: true },
          startCombat: {
            enemyId: 'feral_ghoul',
            victorySceneId: 'relay_entrance',
            fleeSceneId: 'relay_entrance',
          },
        },
      },
      {
        id: 'take_med_crate',
        text: 'Zabierz Skrzynkę Medyczną z rozbitego ambulansu',
        requiredFlag: 'relay_ghoul_cleared',
        forbiddenFlag: 'med_crate_taken',
        nextSceneId: 'relay_entrance',
        effect: {
          addItems: { medical_crate: 1 },
          setFlags: { med_crate_taken: true },
          setQuestState: { questId: 'side_meds', status: 'active', stage: 'return_iga' },
          xpDelta: 35,
        },
      },
      {
        id: 'enter_relay_lab',
        text: 'Wejdź do podziemnego Laboratorium RobCo',
        requiredFlag: 'relay_ghoul_cleared',
        nextSceneId: 'relay_lab',
      },
      {
        id: 'use_robco_shortcut',
        text: '[Skrót Najemnika] Wejdź szybem wentylacyjnym prosto do Laboratorium',
        requiredFlag: 'Shortcut_robco_known',
        nextSceneId: 'relay_lab',
      },
      {
        id: 'leave_relay',
        text: 'Opuść Stację Przekaźnikową [Mapa Świata]',
        nextSceneId: 'relay_entrance',
        effect: { openWorldMap: true },
      },
    ],
  },
  relay_lab: {
    id: 'relay_lab',
    title: 'Stacja Przekaźnikowa RobCo — Główna Serwerownia',
    locationId: 'robco_relay',
    description:
      'Komora stabilizatora jest pusta — ktoś wyciął go palnikiem plazmowym! Na ścianie widnieje wypalony symbol „Dzieci Rdzy”. Drogę do głównego terminala grodzi aktywna Wieżyczka Bojowa RobCo MK II.',
    choices: [
      {
        id: 'hack_relay_sentry',
        text: '[INT] Zhakuj terminal serwisowy i wyłącz Wieżyczkę RobCo MK II (+Karabin Laserowy)',
        forbiddenFlag: 'relay_sentry_resolved',
        skillCheck: { attribute: 'INT', difficulty: 6 },
        nextSceneId: 'relay_terminal',
        failSceneId: 'relay_lab',
        effect: {
          setFlags: { relay_sentry_resolved: true },
          addItems: { laser_rifle: 1 },
          xpDelta: 80,
        },
        failEffect: {
          setFlags: { relay_sentry_resolved: true },
          startCombat: {
            enemyId: 'robco_sentry',
            victorySceneId: 'relay_terminal',
            fleeSceneId: 'relay_entrance',
          },
        },
      },
      {
        id: 'fight_relay_sentry',
        text: 'Zniszcz Wieżyczkę Bojową RobCo MK II w walce',
        forbiddenFlag: 'relay_sentry_resolved',
        nextSceneId: 'relay_terminal',
        effect: {
          setFlags: { relay_sentry_resolved: true },
          startCombat: {
            enemyId: 'robco_sentry',
            victorySceneId: 'relay_terminal',
            fleeSceneId: 'relay_entrance',
          },
        },
      },
      {
        id: 'go_relay_terminal',
        text: 'Podejdź do głównego terminala archiwalnego',
        requiredFlag: 'relay_sentry_resolved',
        nextSceneId: 'relay_terminal',
      },
      {
        id: 'back_relay_entrance',
        text: 'Wróć na dziedziniec Stacji',
        nextSceneId: 'relay_entrance',
      },
    ],
  },
  relay_terminal: {
    id: 'relay_terminal',
    title: 'Stacja Przekaźnikowa RobCo — Terminal Archiwalny',
    locationId: 'robco_relay',
    description:
      'Na zielonym ekranie odczytujesz raport czujników: Watażka Korg zabrał Moduł Stabilizatora G.E.C.K.-Lite do Twierdzy Dzieci Rdzy w starej rafinerii. W napędzie terminala tkwi też zaszyfrowana Holotaśma Nadzorcza RobCo.',
    choices: [
      {
        id: 'download_holotape',
        text: 'Pobierz współrzędne Twierdzy Dzieci Rdzy oraz Holotaśmę Nadzorczą RobCo',
        forbiddenFlag: 'found_relay_clue',
        nextSceneId: 'relay_terminal',
        effect: {
          addItems: { robco_holotape: 1, stimpak: 1 },
          setFlags: { found_relay_clue: true },
          setQuestState: {
            questId: 'main_stabilizer',
            status: 'active',
            stage: 'infiltrate_fortress',
          },
          xpDelta: 75,
        },
      },
      {
        id: 'leave_from_terminal',
        text: 'Wróć na dziedziniec i ruszaj do Twierdzy Dzieci Rdzy',
        requiredFlag: 'found_relay_clue',
        nextSceneId: 'relay_entrance',
      },
    ],
  },
  fortress_gate: {
    id: 'fortress_gate',
    title: 'Twierdza Dzieci Rdzy — Sala Tronowa Rafinerii',
    locationId: 'rust_fortress',
    description:
      'W sercu płonącej rafinerii Watażka Korg podłączył Moduł Stabilizatora G.E.C.K.-Lite do niestabilnego generatora plazmowego. Iskry sypią się z przegrzanych cewek!\n\nKorg ryczy przez głośnik pancerza: „Ten święty rdzeń da Dzieciom Rdzy moc panowania nad całym sektorem! Zbliż się o krok, a spopielę Cię na miejscu!”',
    choices: [
      {
        id: 'diplomacy_korg',
        text: '[CHA] Przekonaj Korga, że przeciążony stabilizator za chwilę wysadzi całą rafinerię',
        forbiddenFlag: 'korg_resolved',
        skillCheck: { attribute: 'CHA', difficulty: 7 },
        nextSceneId: 'fortress_victory',
        failSceneId: 'fortress_gate',
        effect: {
          setFlags: { korg_resolved: true, korg_spared: true },
          addItems: { geck_stabilizer: 1 },
          xpDelta: 180,
          setQuestState: { questId: 'main_stabilizer', status: 'active', stage: 'return_vault' },
        },
        failEffect: {
          setFlags: { korg_resolved: true },
          startCombat: {
            enemyId: 'warlord_korg',
            victorySceneId: 'fortress_victory',
          },
        },
      },
      {
        id: 'hack_fortress_turrets',
        text: '[INT + Holotaśma RobCo] Użyj Holotaśmy, by obrócić wieżyczki rafinerii przeciw Korgowi',
        requiredItem: 'robco_holotape',
        forbiddenFlag: 'korg_resolved',
        skillCheck: { attribute: 'INT', difficulty: 6 },
        nextSceneId: 'fortress_victory',
        failSceneId: 'fortress_gate',
        effect: {
          setFlags: { korg_resolved: true, korg_turrets: true },
          addItems: { geck_stabilizer: 1 },
          xpDelta: 180,
          setQuestState: { questId: 'main_stabilizer', status: 'active', stage: 'return_vault' },
        },
        failEffect: {
          setFlags: { korg_resolved: true },
          startCombat: {
            enemyId: 'warlord_korg',
            victorySceneId: 'fortress_victory',
          },
        },
      },
      {
        id: 'fight_korg',
        text: 'Zaatakuj Watażkę Korga w bezpośrednim starciu!',
        forbiddenFlag: 'korg_resolved',
        nextSceneId: 'fortress_victory',
        effect: {
          setFlags: { korg_resolved: true },
          startCombat: {
            enemyId: 'warlord_korg',
            victorySceneId: 'fortress_victory',
          },
        },
      },
    ],
  },
  fortress_victory: {
    id: 'fortress_victory',
    title: 'Twierdza Dzieci Rdzy — Zdobycz',
    locationId: 'rust_fortress',
    description:
      'Moduł Stabilizatora G.E.C.K.-Lite spoczywa bezpiecznie w Twoim plecaku. Możesz teraz wrócić do Krypty 73 i ocalić jej mieszkańców — albo przekazać technologię mieszkańcom Rdzawego Krateru, by zbudowali nową przyszłość na powierzchni.',
    onEnterEffect: {
      setQuestState: { questId: 'main_stabilizer', status: 'active', stage: 'return_vault' },
    },
    choices: [
      {
        id: 'ending_share_wasteland',
        text: '[FINAŁ ALTERNATYWNY] Przekaż Stabilizator wspólnocie Rdzawego Krateru i otwórz Kryptę na świat',
        nextSceneId: 'ending_wasteland_alliance',
        effect: {
          setQuestState: { questId: 'main_stabilizer', status: 'completed', stage: 'completed' },
        },
      },
      {
        id: 'open_map_from_fortress',
        text: 'Wróć na Mapę Świata (udaj się do Krypty 73)',
        nextSceneId: 'fortress_victory',
        effect: { openWorldMap: true },
      },
    ],
  },
  ending_vault_saved: {
    id: 'ending_vault_saved',
    title: 'EPILOG: Bohater Krypty 73',
    locationId: 'vault_73',
    isEnding: true,
    description:
      'Gdy Moduł Stabilizatora G.E.C.K.-Lite wsuwa się w gniazdo reaktora, alarmowe czerwone światła gasną, ustępując kojącemu blaskowi lamp jarzeniowych. Hydroprocesor znów tłoczy czystą wodę.\n\nTwoje imię zostaje zapisane w kronikach Krypty 73 jako wybawcy podziemnego świata. Gratulacje — ukończyłeś grę!',
    choices: [],
  },
  ending_wasteland_alliance: {
    id: 'ending_wasteland_alliance',
    title: 'EPILOG: Architekt Nowego Świtu',
    locationId: 'rusty_crater',
    isEnding: true,
    description:
      'Zamiast zamykać się ponownie pod ziemią, połączyłeś technologię Krypty 73 z odwagą osadników z Rdzawego Krateru. Stabilizator zasilił zarówno schron, jak i oczyszczalnię na powierzchni.\n\nNarodziło się pierwsze wolne miasto Nowego Świtu. Gratulacje — ukończyłeś grę!',
    choices: [],
  },
  game_over_death: {
    id: 'game_over_death',
    title: 'KONIEC GRY — Poległeś na Pustkowiach',
    locationId: 'vault_73',
    isEnding: true,
    description:
      'Twoje kości bieleją w radioaktywnym pyle Pustkowi, a Pip-Boy 3000 wyświetla ostatni komunikat diagnostyczny: BRAK FUNKCJI ŻYCIOWYCH.',
    choices: [],
  },
};

const encounterScenes: Record<string, SceneDefinition> = Object.fromEntries(
  ENCOUNTERS.map((enc) => [
    enc.id,
    {
      id: enc.id,
      title: enc.title,
      locationId: 'rusty_crater',
      description: enc.description,
      choices: enc.choices,
    },
  ])
);

export const SCENES: Record<string, SceneDefinition> = {
  ...baseScenes,
  ...encounterScenes,
};
