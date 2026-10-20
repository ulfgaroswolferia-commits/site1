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
  new_reno: {
    id: 'new_reno',
    name: 'Metropolia New Reno',
    description: 'Rozświetlone neonami miasto kasyn, gangów i zatrutych wodociągów.',
    entrySceneId: 'reno_gates',
    requiredFlag: 'new_reno_unlocked',
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
        id: 'return_hero_chapter_2',
        text: '[FINAŁ ROZDZIAŁU 2] Przedstaw Nadzorcy Pieczęć Oczyszczalni New Reno i odbierz nominację',
        requiredItem: 'reno_purifier_seal',
        nextSceneId: 'ending_deputy_overseer',
        effect: {
          setQuestState: { questId: 'ch2_new_reno', status: 'completed', stage: 'completed' },
          xpDelta: 400,
        },
      },
      {
        id: 'deliver_stabilizer',
        text: '[FINAŁ ROZDZIAŁU 1] Zamontuj Moduł Stabilizatora G.E.C.K.-Lite w reaktorze Krypty 73',
        requiredItem: 'geck_stabilizer',
        forbiddenFlag: 'new_reno_unlocked',
        nextSceneId: 'ending_vault_saved',
        effect: {
          removeItems: { geck_stabilizer: 1 },
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
    title: 'KONIEC ROZDZIAŁU 1: Krypta 73 Ocalona — Nowe Wezwanie',
    locationId: 'vault_73',
    description:
      'Gdy Moduł Stabilizatora G.E.C.K.-Lite wsuwa się w gniazdo reaktora, alarmowe czerwone światła gasną, a hydroprocesor znów tłoczy czystą wodę. Nadzorca ściska Twoją dłoń na oczach wiwatujących mieszkańców, lecz po chwili jego twarz poważnieje:\n\n„Uratowałeś nasz dom, Wędrowcze, ale to dopiero początek. Przed chwilą odebraliśmy dramatyczny sygnał z metropolii New Reno — jedynego miasta zdolnego dostarczać nam rzadkie leki i części techniczne. Ktoś zatruwa ich wodociągi z podziemnych kanałów, a ulice terroryzuje gang Żelaznych Szakali. Jeśli New Reno upadnie, zostaniemy na Pustkowiach sami. Wyrusz do New Reno i ocal miasto!”',
    choices: [
      {
        id: 'start_chapter_2_vault',
        text: 'Przyjmij misję w New Reno (+120 kapsli, +2 Stimpaki) i otwórz Mapę Świata [Rozdział 2]',
        nextSceneId: 'vault_atrium',
        effect: {
          capsDelta: 120,
          addItems: { stimpak: 2, radaway: 1 },
          setFlags: { new_reno_unlocked: true },
          setQuestState: { questId: 'ch2_new_reno', status: 'active', stage: 'travel_reno' },
        },
      },
    ],
  },
  ending_wasteland_alliance: {
    id: 'ending_wasteland_alliance',
    title: 'KONIEC ROZDZIAŁU 1: Sojusz Nowego Świtu — Kierunek New Reno',
    locationId: 'rusty_crater',
    description:
      'Połączyłeś technologię Krypty 73 z odwagą osadników z Rdzawego Krateru. Stabilizator zasilił zarówno schron, jak i oczyszczalnię na powierzchni. Podczas narady Nadzorca Krypty 73 przekazuje Ci pilny komunikat radiowy:\n\n„Nasz nowy sojusz potrzebuje handlu z metropolią New Reno na zachodzie. Niestety miasto pogrąża się w agonii — ktoś zatruwa ich podziemne wodociągi śmiertelnym mutagenem, a gang Żelaznych Szakali przejmuje dzielnice. Udaj się do New Reno jako nasz wysłannik, powstrzymaj truciciela i wróć do Krypty 73!”',
    choices: [
      {
        id: 'start_chapter_2_crater',
        text: 'Wyrusz na ratunek New Reno (+120 kapsli, +2 Stimpaki) [Rozdział 2]',
        nextSceneId: 'crater_hub',
        effect: {
          capsDelta: 120,
          addItems: { stimpak: 2, radaway: 1 },
          setFlags: { new_reno_unlocked: true },
          setQuestState: { questId: 'ch2_new_reno', status: 'active', stage: 'travel_reno' },
        },
      },
    ],
  },
  reno_gates: {
    id: 'reno_gates',
    title: 'New Reno — Aleja Neonów (Centrum Miasta)',
    locationId: 'new_reno',
    onEnterEffect: {
      setQuestState: { questId: 'ch2_new_reno', status: 'active', stage: 'investigate_city' },
    },
    description:
      'Stoisz w sercu New Reno. Nad popękanym asfaltem buczą przedwojenne neony, ale na ulicach widać oznaki agonii miasta — strażnicy rozdają racjonowaną wodę, a przed szpitalem ustawiają się kolejki chorych.\n\nPrzed sobą widzisz Biuro Szeryfa Vance’a, Zbrojownię Corso, Szpital Św. Atomu, rozświetlone Kasyno „Złoty Atom”, barykady w Dzielnicy Magazynów (melina gangu Żelaznych Szakali) oraz żeliwny właz do Podziemnych Kanałów.',
    choices: [
      {
        id: 'go_reno_sheriff',
        text: 'Wejdź do Biura Szeryfa Vance’a',
        nextSceneId: 'reno_sheriff',
      },
      {
        id: 'trade_corso',
        text: 'Odwiedź Zbrojmistrza Corso [Ciężki sprzęt i Pancerze Wspomagane]',
        nextSceneId: 'reno_gates',
        effect: { openMerchant: 'corso_armorer' },
      },
      {
        id: 'go_reno_hospital',
        text: 'Udaj się do Szpitala Św. Atomu',
        nextSceneId: 'reno_hospital',
      },
      {
        id: 'go_reno_casino',
        text: 'Wejdź do Kasyna „Złoty Atom”',
        nextSceneId: 'reno_casino',
      },
      {
        id: 'go_reno_bandits',
        text: 'Idź do Dzielnicy Magazynów (Melina gangu Żelaznych Szakali)',
        nextSceneId: 'reno_bandits',
      },
      {
        id: 'go_reno_sewers',
        text: 'Zejdź przez właz do Podziemnych Kanałów New Reno',
        nextSceneId: 'reno_sewers_entry',
      },
      {
        id: 'leave_reno_map',
        text: 'Otwórz Mapę Świata (Podróż poza New Reno)',
        nextSceneId: 'reno_gates',
        effect: { openWorldMap: true },
      },
    ],
  },
  reno_sheriff: {
    id: 'reno_sheriff',
    title: 'New Reno — Biuro Szeryfa Vance’a',
    locationId: 'new_reno',
    description:
      'Szeryf Vance opiera się o stół z planem kanalizacji New Reno: „Wędrowiec z Krypty 73? W samą porę. Mamy dwa śmiertelne problemy. Po pierwsze: w najgłębszym sektorze kanałów pod stacją uzdatniania zalęgł się szaleniec zwany Doktorem Morbusem — wpuszcza mutagen do głównego ujęcia wody, a kanały roją się od zmutowanych szczurów i świecących ghuli. Po drugie: gang Żelaznych Szakali pod wodzą Wściekłego Vinniego terroryzuje dzielnicę magazynów. Możesz wybić Szakali co do jednego... albo, jeśli masz dość tupetu, zmusić ich, żeby zeszli do kanałów i przelali krew w szturmie na Morbusa.”',
    choices: [
      {
        id: 'ask_sheriff_tactics',
        text: '[PER] Przeanalizuj z Szeryfem mapę kolektorów ściekowych (+50 XP)',
        forbiddenFlag: 'sheriff_map_studied',
        skillCheck: { attribute: 'PER', difficulty: 6 },
        nextSceneId: 'reno_sheriff',
        failSceneId: 'reno_sheriff',
        effect: {
          xpDelta: 50,
          setFlags: { sheriff_map_studied: true },
          setQuestState: { questId: 'side_reno_jackals', status: 'active', stage: 'confront_jackals' },
        },
        failEffect: {
          setFlags: { sheriff_map_studied: true },
          setQuestState: { questId: 'side_reno_jackals', status: 'active', stage: 'confront_jackals' },
        },
      },
      {
        id: 'claim_jackals_bounty',
        text: 'Odbierz nagrodę 120 kapsli za rozbicie gangu Żelaznych Szakali',
        requiredFlag: 'jackals_defeated',
        forbiddenFlag: 'jackals_bounty_claimed',
        nextSceneId: 'reno_sheriff',
        effect: {
          capsDelta: 120,
          xpDelta: 80,
          setFlags: { jackals_bounty_claimed: true },
        },
      },
      {
        id: 'back_reno_from_sheriff',
        text: 'Wróć na Aleję Neonów',
        nextSceneId: 'reno_gates',
      },
    ],
  },
  reno_hospital: {
    id: 'reno_hospital',
    title: 'New Reno — Szpital Św. Atomu',
    locationId: 'new_reno',
    description:
      'Korytarze szpitala są przepełnione mieszkańcami cierpiącymi na zatrucie popromienne. Ordynator Lena spogląda znad mikroskopu:\n\n„Ta toksyna w wodzie to nie zwykły opad — to syntetyczny mutagen! Jeśli przyniesiesz mi nierozcieńczoną Próbkę Szlamu z pierwszego poziomu kanałów, zsyntetyzuję Surowicę Neutralizującą. Wlana do głównego zbiornika w kryjówce truciciela sparaliżuje jego zmutowane bestie i osłabi go o połowę!”',
    choices: [
      {
        id: 'accept_hospital_quest',
        text: 'Zaoferuj pomoc w zdobyciu Próbki Szlamu z kanałów',
        forbiddenFlag: 'hospital_quest_started',
        nextSceneId: 'reno_hospital',
        effect: {
          setFlags: { hospital_quest_started: true },
          setQuestState: { questId: 'side_reno_hospital', status: 'active', stage: 'collect_sample' },
        },
      },
      {
        id: 'craft_neutralizing_serum',
        text: 'Przekaż Próbkę Szlamu i odbierz Surowicę Neutralizującą (+100 XP, +2 Stimpaki)',
        requiredItem: 'sewer_toxin_sample',
        forbiddenFlag: 'serum_crafted',
        nextSceneId: 'reno_hospital',
        effect: {
          removeItems: { sewer_toxin_sample: 1 },
          addItems: { neutralizing_serum: 1, stimpak: 2 },
          xpDelta: 100,
          setFlags: { serum_crafted: true },
          setQuestState: { questId: 'side_reno_hospital', status: 'completed', stage: 'completed' },
        },
      },
      {
        id: 'hospital_med_help',
        text: '[INT] Pomóż lekarzom skalibrować aparaturę dializującą (+80 kapsli, +60 XP)',
        forbiddenFlag: 'hospital_calibrated',
        skillCheck: { attribute: 'INT', difficulty: 6 },
        nextSceneId: 'reno_hospital',
        failSceneId: 'reno_hospital',
        effect: {
          capsDelta: 80,
          xpDelta: 60,
          setFlags: { hospital_calibrated: true },
        },
        failEffect: {
          setFlags: { hospital_calibrated: true },
        },
      },
      {
        id: 'hospital_full_heal',
        text: 'Skorzystaj z komory regeneracyjnej Szpitala (25 kapsli: -100 RAD, +150 HP)',
        requiredCaps: 25,
        nextSceneId: 'reno_hospital',
        effect: {
          capsDelta: -25,
          radDelta: -100,
          hpDelta: 150,
        },
      },
      {
        id: 'back_reno_from_hospital',
        text: 'Wróć na Aleję Neonów',
        nextSceneId: 'reno_gates',
      },
    ],
  },
  reno_casino: {
    id: 'reno_casino',
    title: 'New Reno — Kasyno „Złoty Atom”',
    locationId: 'new_reno',
    description:
      'Złote żyrandole oświetlają stoły do ruletki i automatów. Przy loży VIP siedzi Don Salvatore, właściciel kasyna:\n\n„Interesują Cię Żelazne Szakale? Ich herszt, Wściekły Vinnie, ma u mnie gigantyczny dług hazardowy. Jeśli zapłacisz 80 kapsli albo wyświadczysz mi przysługę przy stole, oddam Ci jego Weksel Dłużny — z tym papierem Vinnie i jego ludzie pójdą za Tobą nawet w ogień kanałów.”',
    choices: [
      {
        id: 'gamble_roulette',
        text: '[PER] Zagraj o wysoką stawkę przy stole Ruletki Atomowej (Stawka: 30 kapsli)',
        requiredCaps: 30,
        skillCheck: { attribute: 'PER', difficulty: 6 },
        nextSceneId: 'reno_casino',
        failSceneId: 'reno_casino',
        effect: {
          capsDelta: 60,
          xpDelta: 30,
        },
        failEffect: {
          capsDelta: -30,
        },
      },
      {
        id: 'persuade_salvatore_note',
        text: '[CHA] Przekonaj Dona Salvatore, że oczyszczenie kanałów uratuje zyski kasyna (Odbierz Weksel za darmo)',
        forbiddenFlag: 'debt_note_acquired',
        skillCheck: { attribute: 'CHA', difficulty: 7 },
        nextSceneId: 'reno_casino',
        failSceneId: 'reno_casino',
        effect: {
          addItems: { jackals_debt_note: 1 },
          xpDelta: 90,
          setFlags: { debt_note_acquired: true },
        },
      },
      {
        id: 'buy_salvatore_note',
        text: 'Wykup Weksel Dłużny Żelaznych Szakali za 80 kapsli',
        requiredCaps: 80,
        forbiddenFlag: 'debt_note_acquired',
        nextSceneId: 'reno_casino',
        effect: {
          capsDelta: -80,
          addItems: { jackals_debt_note: 1 },
          xpDelta: 50,
          setFlags: { debt_note_acquired: true },
        },
      },
      {
        id: 'back_reno_from_casino',
        text: 'Wyjdź z Kasyna na Aleję Neonów',
        nextSceneId: 'reno_gates',
      },
    ],
  },
  reno_bandits: {
    id: 'reno_bandits',
    title: 'New Reno — Dzielnica Magazynów (Melina „Żelaznych Szakali”)',
    locationId: 'new_reno',
    description:
      'Wokół płonących beczek stoją uzbrojeni po zęby najemnicy. Na masce opancerzonego wozu siedzi Wściekły Vinnie, bawiąc się nożem sprężynowym:\n\n„Czego tu szukasz, przybłędo z Krypty? To terytorium Żelaznych Szakali! Chociaż... połowa moich chłopaków leży zatruta tą przeklętą wodą z kranów.”',
    choices: [
      {
        id: 'use_debt_note_jackals',
        text: '[Weksel Dłużny] Pokaż Vinnie’mu weksel od Dona Salvatore: „Dług zostanie anulowany, jeśli pomożecie mi rozbić laboratorium Morbusa w kanałach!”',
        requiredItem: 'jackals_debt_note',
        forbiddenFlag: 'jackals_resolved',
        nextSceneId: 'reno_bandits',
        effect: {
          removeItems: { jackals_debt_note: 1 },
          setFlags: { jackals_resolved: true, jackals_allied: true },
          setQuestState: { questId: 'side_reno_jackals', status: 'completed', stage: 'allied' },
          xpDelta: 120,
        },
      },
      {
        id: 'persuade_vinnie_alliance',
        text: '[CHA] Przekonaj Vinniego, że bez czystej wody jego gang wyginie, a skarbiec Morbusa w kanałach jest pełen łupów',
        forbiddenFlag: 'jackals_resolved',
        skillCheck: { attribute: 'CHA', difficulty: 7 },
        nextSceneId: 'reno_bandits',
        failSceneId: 'reno_bandits',
        effect: {
          setFlags: { jackals_resolved: true, jackals_allied: true },
          setQuestState: { questId: 'side_reno_jackals', status: 'completed', stage: 'allied' },
          xpDelta: 120,
        },
        failEffect: {
          setFlags: { jackals_resolved: true, jackals_defeated: true },
          setQuestState: { questId: 'side_reno_jackals', status: 'completed', stage: 'eliminated' },
          startCombat: {
            enemyId: 'vinnie_jackal',
            victorySceneId: 'reno_bandits',
          },
        },
      },
      {
        id: 'fight_vinnie_jackals',
        text: 'Zaatakuj Wściekłego Vinniego i rozbij gang Żelaznych Szakali siłą!',
        forbiddenFlag: 'jackals_resolved',
        nextSceneId: 'reno_bandits',
        effect: {
          setFlags: { jackals_resolved: true, jackals_defeated: true },
          setQuestState: { questId: 'side_reno_jackals', status: 'completed', stage: 'eliminated' },
          startCombat: {
            enemyId: 'vinnie_jackal',
            victorySceneId: 'reno_bandits',
          },
        },
      },
      {
        id: 'back_reno_from_bandits',
        text: 'Wróć na Aleję Neonów',
        nextSceneId: 'reno_gates',
      },
    ],
  },
  reno_sewers_entry: {
    id: 'reno_sewers_entry',
    title: 'Podziemne Kanały New Reno — Sektor 1: Kolektor Ściekowy',
    locationId: 'new_reno',
    description:
      'Schodzisz po śliskiej drabinie w zielonkawy półmrok. Woda w kanale bulgocze od toksycznych wyziewów, a z bocznych rur wypełza stado wygłodniałych Radioaktywnych Szczurów-Gigantów! Przy kracie odpływowej widać też osad z czystego mutagenu.',
    choices: [
      {
        id: 'fight_sewer_rats',
        text: 'Oczyść przejście ze stada Radioaktywnych Szczurów-Gigantów',
        forbiddenFlag: 'sewer_rats_cleared',
        nextSceneId: 'reno_sewers_entry',
        effect: {
          setFlags: { sewer_rats_cleared: true },
          startCombat: {
            enemyId: 'giant_rad_rat',
            victorySceneId: 'reno_sewers_entry',
            fleeSceneId: 'reno_gates',
          },
        },
      },
      {
        id: 'collect_toxin_sample',
        text: 'Pobierz Próbkę Szlamu Mutagenu dla Szpitala Św. Atomu',
        requiredFlag: 'sewer_rats_cleared',
        forbiddenFlag: 'toxin_sample_collected',
        nextSceneId: 'reno_sewers_entry',
        effect: {
          addItems: { sewer_toxin_sample: 1 },
          setFlags: { toxin_sample_collected: true },
          setQuestState: { questId: 'side_reno_hospital', status: 'active', stage: 'deliver_sample' },
          xpDelta: 45,
        },
      },
      {
        id: 'go_sewers_mid',
        text: 'Idź w głąb kanałów do Zbiornika Filtracyjnego (Sektor 2)',
        requiredFlag: 'sewer_rats_cleared',
        nextSceneId: 'reno_sewers_mid',
      },
      {
        id: 'exit_sewers_to_city',
        text: 'Wspinaj się po drabinie z powrotem na Aleję Neonów',
        nextSceneId: 'reno_gates',
      },
    ],
  },
  reno_sewers_mid: {
    id: 'reno_sewers_mid',
    title: 'Podziemne Kanały New Reno — Sektor 2: Zbiornik Filtracyjny',
    locationId: 'new_reno',
    description:
      'Docierasz do ogromnej hali podziemnych filtrów. W radioaktywnej mgle żerują Świecący Ghul oraz Zmutowany Pożeracz Kanałowy pilnujący grodzi do Laboratorium Wodociągów.',
    choices: [
      {
        id: 'fight_glowing_ghoul',
        text: 'Pokonaj Świecącego Ghula z Kolektora',
        forbiddenFlag: 'sewer_mid_ghoul_cleared',
        nextSceneId: 'reno_sewers_mid',
        effect: {
          setFlags: { sewer_mid_ghoul_cleared: true },
          startCombat: {
            enemyId: 'glowing_ghoul',
            victorySceneId: 'reno_sewers_mid',
            fleeSceneId: 'reno_sewers_entry',
          },
        },
      },
      {
        id: 'fight_sewer_devourer',
        text: 'Zgładź Zmutowanego Pożeracza Kanałowego przed grodzią',
        requiredFlag: 'sewer_mid_ghoul_cleared',
        forbiddenFlag: 'sewer_mid_devourer_cleared',
        nextSceneId: 'reno_sewers_mid',
        effect: {
          setFlags: { sewer_mid_devourer_cleared: true },
          startCombat: {
            enemyId: 'sewer_devourer',
            victorySceneId: 'reno_sewers_mid',
            fleeSceneId: 'reno_sewers_entry',
          },
        },
      },
      {
        id: 'bypass_devourer_valve',
        text: '[STR] Odkręć zardzewiały zawór parowy i zdmuchnij Pożeracza gorącą parą (+Granat Pulsacyjny)',
        requiredFlag: 'sewer_mid_ghoul_cleared',
        forbiddenFlag: 'sewer_mid_devourer_cleared',
        skillCheck: { attribute: 'STR', difficulty: 7 },
        nextSceneId: 'reno_sewers_mid',
        failSceneId: 'reno_sewers_mid',
        effect: {
          setFlags: { sewer_mid_devourer_cleared: true },
          addItems: { pulse_grenade: 1 },
          xpDelta: 110,
        },
        failEffect: {
          setFlags: { sewer_mid_devourer_cleared: true },
          startCombat: {
            enemyId: 'sewer_devourer',
            victorySceneId: 'reno_sewers_mid',
            fleeSceneId: 'reno_sewers_entry',
          },
        },
      },
      {
        id: 'enter_morbus_lair',
        text: 'Wyważ gródź i wejdź do Podziemnego Laboratorium Doktora Morbusa (Sektor 3 — BOSS)',
        requiredFlag: 'sewer_mid_devourer_cleared',
        nextSceneId: 'reno_sewers_boss',
        effect: {
          setQuestState: { questId: 'ch2_new_reno', status: 'active', stage: 'defeat_morbus' },
        },
      },
      {
        id: 'back_sewers_entry',
        text: 'Cofnij się do Kolektora Ściekowego (Sektor 1)',
        nextSceneId: 'reno_sewers_entry',
      },
    ],
  },
  reno_sewers_boss: {
    id: 'reno_sewers_boss',
    title: 'Podziemne Kanały New Reno — Sektor 3: Laboratorium Doktora Morbusa',
    locationId: 'new_reno',
    description:
      'W centrum zalanej komory wodociągowej wznosi się aparatura pompująca zielony mutagen prosto do rur New Reno. Na pomoście stoi Doktor Morbus w egzoszkielecie połączonym kablami z gigantycznym Królem Kanałów!\n\n„New Reno zgniło od środka!” — skrzeczy Morbus przez maskę tlenową. — „Mój mutagen oczyści to miasto i stworzy nową rasę dzieci podziemi! Nikt mnie nie powstrzyma!”',
    choices: [
      {
        id: 'boss_attack_with_jackals',
        text: '[SOJUSZ Z BANDYTAMI] Daj sygnał Wściekłemu Vinnie’mu — Żelazne Szakale uderzają z flanki i osłabiają Morbusa!',
        requiredFlag: 'jackals_allied',
        nextSceneId: 'reno_sewers_victory',
        effect: {
          startCombat: {
            enemyId: 'doctor_morbus_weakened',
            victorySceneId: 'reno_sewers_victory',
          },
        },
      },
      {
        id: 'boss_use_hospital_serum',
        text: '[SUROWICA SZPITALNA] Wlej Surowicę Neutralizującą Dr Leny do głównej pompy, paraliżując mutacje Morbusa!',
        requiredItem: 'neutralizing_serum',
        nextSceneId: 'reno_sewers_victory',
        effect: {
          removeItems: { neutralizing_serum: 1 },
          xpDelta: 100,
          startCombat: {
            enemyId: 'doctor_morbus_weakened',
            victorySceneId: 'reno_sewers_victory',
          },
        },
      },
      {
        id: 'boss_hack_pumps',
        text: '[INT] Przeciąż zawory bezpieczeństwa aparatury Morbusa przed walką',
        skillCheck: { attribute: 'INT', difficulty: 8 },
        nextSceneId: 'reno_sewers_victory',
        failSceneId: 'reno_sewers_victory',
        effect: {
          xpDelta: 120,
          startCombat: {
            enemyId: 'doctor_morbus_weakened',
            victorySceneId: 'reno_sewers_victory',
          },
        },
        failEffect: {
          startCombat: {
            enemyId: 'doctor_morbus_full',
            victorySceneId: 'reno_sewers_victory',
          },
        },
      },
      {
        id: 'boss_solo_full_fight',
        text: 'Staw czoła Doktorowi Morbusowi i Królowi Kanałów w pojedynkę (Pełna Siła Bossa)!',
        nextSceneId: 'reno_sewers_victory',
        effect: {
          startCombat: {
            enemyId: 'doctor_morbus_full',
            victorySceneId: 'reno_sewers_victory',
          },
        },
      },
    ],
  },
  reno_sewers_victory: {
    id: 'reno_sewers_victory',
    title: 'New Reno Ocalone — Czyste Źródła',
    locationId: 'new_reno',
    onEnterEffect: {
      setQuestState: { questId: 'ch2_new_reno', status: 'active', stage: 'return_overseer' },
    },
    description:
      'Doktor Morbus pada obok roztrzaskanej kadzi z mutagenem! Przełączasz główną dźwignię stacji filtracyjnej — krystalicznie czysta woda pod ciśnieniem wypłukuje toksyny z wodociągów New Reno.\n\nGdy wychodzisz na powierzchnię, mieszkańcy Alei Neonów, lekarze ze Szpitala i Szeryf Vance wiwatują na Twoją cześć. W dłoni trzymasz Pieczęć Oczyszczalni New Reno gwarantującą dozgonny sojusz i dostawy dla Krypty 73. Czas wrócić do domu jako bohater!',
    choices: [
      {
        id: 'open_map_after_reno_victory',
        text: 'Otwórz Mapę Świata i wróć w chwale do Krypty 73',
        nextSceneId: 'reno_gates',
        effect: { openWorldMap: true },
      },
      {
        id: 'stay_reno_celebrate',
        text: 'Przejdź się jeszcze po ocalonej Alei Neonów',
        nextSceneId: 'reno_gates',
      },
    ],
  },
  ending_deputy_overseer: {
    id: 'ending_deputy_overseer',
    title: 'WIELKI FINAŁ: Zastępca Nadzorcy Krypty 73',
    locationId: 'vault_73',
    isEnding: true,
    description:
      'Wracasz do Krypty 73 witany jak żywa legenda Pustkowi. Karawany z ocalonego New Reno już rozładowują w śluzie medykamenty, czyste ogniwa energetyczne i zapasy żywności.\n\nPodczas uroczystego zgromadzenia w Atrium Nadzorca przypina do Twojego kombinezonu złote insygnia Vault-Tec:\n\n„Ocaliłeś nasz reaktor przed zagładą, a potem uratowałeś całe New Reno przed śmiercią. Od dziś mianuję Cię moim pierwszym Zastępcą Nadzorcy Krypty 73!”\n\nGRATULACJE — UKOŃCZYŁEŚ PEŁNĄ KAMPANIĘ (ROZDZIAŁ 1 i 2)!',
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
