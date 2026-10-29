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
  arroyo: {
    id: 'arroyo',
    name: 'Wioska Arroyo',
    description: 'Tradycyjna osada plemienna na północnych pustkowiach, nękana przez zmutowane drapieżniki.',
    entrySceneId: 'arroyo_village',
    requiredFlag: 'arroyo_unlocked',
  },
  ncr: {
    id: 'ncr',
    name: 'Nowa Republika Kalifornii (NCR)',
    description: 'Zorganizowane państwo z własnym rządem, armią i przedwojenną wiedzą technologiczną.',
    entrySceneId: 'ncr_downtown',
    requiredFlag: 'ncr_unlocked',
  },
  vault_13: {
    id: 'vault_13',
    name: 'Krypta 13',
    description: 'Mityczny, zapieczętowany schron Vault-Tec kryjący zapasowy Hydroprocesor.',
    entrySceneId: 'vault_13_gate',
    requiredFlag: 'vault_13_unlocked',
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
      {
        id: 'support_open_faction_intro',
        text: '[OTWARCI] Poprzyj postulat handlu i otwarcia Krypty na współpracę z powierzchnią',
        forbiddenFlag: 'vault_faction_intro_chosen',
        nextSceneId: 'vault_atrium',
        effect: {
          setFlags: { vault_faction_intro_chosen: true },
          incrementCounters: { vault_openness: 1 },
          xpDelta: 25,
          setQuestState: { questId: 'main_stabilizer', status: 'active', stage: 'leave_vault' },
        },
      },
      {
        id: 'support_iso_faction_intro',
        text: '[IZOLACJONIŚCI] Poprzyj doktrynę szczelnego zamknięcia wrót po zdobyciu części',
        forbiddenFlag: 'vault_faction_intro_chosen',
        nextSceneId: 'vault_atrium',
        effect: {
          setFlags: { vault_faction_intro_chosen: true },
          incrementCounters: { vault_openness: -1 },
          xpDelta: 25,
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
    dynamicDescription: (state) => {
      const openness = state.counters?.vault_openness ?? 0;
      const factionNote =
        openness >= 2
          ? '\n\nW Atrium dominuje obóz Otwartych — mieszkańcy przygotowują punkty wymiany handlowej ze światem zewnętrznym.'
          : openness <= -1
          ? '\n\nW Atrium dominują Izolacjoniści — strażnicy spawają dodatkowe rygle i żądają odcięcia schronu od Pustkowi.'
          : '\n\nW Atrium trwa gorący spór między frakcją Izolacjonistów (chcących zaryglować wrota na zawsze) a Otwartymi (pragnącymi handlu z powierzchnią).';
      if (!state.flags.arroyo_unlocked) {
        return (
          'Stoisz przed monumentalnymi, zębatymi wrotami Krypty 73. Przy pulpicie sterującym stoi uszkodzony Strażnik RobCo — w jego zacisku widnieje Karta Dostępu Strażnika otwierająca zbrojownię oraz śluzę.' +
          factionNote
        );
      }
      const days = state.counters?.water_days_left ?? 60;
      if (days <= 0) {
        return (
          'KRYZYS WODNY (0 DNI ZAPASU): W Krypcie 73 wybuchła panika! Kran z wodą wysechł zupełnie, a w skrzydle medycznym są już pierwsze ofiary śmiertelne z odwodnienia. Liczy się każda sekunda!' +
          factionNote
        );
      }
      if (days <= 24) {
        return (
          `ALARM WODNY (Zostało tylko ${days} dni wody!): Powietrze w Krypcie 73 jest duszne i gorące. Mieszkańcy mdleją w kolejkach po ostatnie racje brudnej wody z rezerwuaru.` +
          factionNote
        );
      }
      return (
        `STAN REZERW WODY: Zostało ${days} dni zapasu wody. Mieszkańcy z niepokojem spoglądają na wskaźniki ciśnienia hydroprocesora.` +
        factionNote
      );
    },
    choices: [
      {
        id: 'return_hero_chapter_3',
        text: '[FINAŁ ROZDZIAŁU 3] Zamontuj Hydroprocesor z Krypty 13 w reaktorze Krypty 73 i ocal mieszkańców przed suszą',
        requiredItem: 'water_chip',
        nextSceneId: 'ending_water_chip_saved',
        effect: {
          removeItems: { water_chip: 1 },
          addItems: { stimpak: 2, ammo_energy: 8, ammo_rifle: 8 },
          hpDelta: 200,
          radDelta: -100,
          setQuestState: { questId: 'ch3_water_chip', status: 'completed', stage: 'completed' },
          xpDelta: 600,
        },
      },
      {
        id: 'return_hero_chapter_3_ncr_writ',
        text: '[FINAŁ ROZDZIAŁU 3 — SOJUSZ Z NCR] Przedstaw Traktat Protektoratu Wodnego NCR i uruchom budowę rurociągu',
        requiredItem: 'ncr_annexation_writ',
        nextSceneId: 'ending_water_chip_saved',
        effect: {
          removeItems: { ncr_annexation_writ: 1 },
          addItems: { stimpak: 2, ammo_energy: 8, ammo_rifle: 8 },
          hpDelta: 200,
          radDelta: -100,
          setFlags: { v13_ncr_annexed: true },
          incrementCounters: { vault_openness: 2 },
          setQuestState: { questId: 'ch3_water_chip', status: 'completed', stage: 'completed' },
          xpDelta: 600,
        },
      },
      {
        id: 'return_hero_chapter_2',
        text: '[FINAŁ ROZDZIAŁU 2] Przedstaw Nadzorcy Pieczęć Oczyszczalni New Reno i odbierz nominację',
        requiredItem: 'reno_purifier_seal',
        forbiddenFlag: 'ch2_completed',
        nextSceneId: 'ending_deputy_overseer',
        effect: {
          removeItems: { reno_purifier_seal: 1 },
          setFlags: { ch2_completed: true },
          setQuestState: { questId: 'ch2_new_reno', status: 'completed', stage: 'completed' },
          xpDelta: 400,
        },
      },
      {
        id: 'deliver_stabilizer',
        text: '[FINAŁ ROZDZIAŁU 1 — IZOLACJA] Zamontuj Moduł Stabilizatora wyłącznie w reaktorze Krypty 73 i zarygluj wrota',
        requiredItem: 'geck_stabilizer',
        forbiddenFlag: 'new_reno_unlocked',
        nextSceneId: 'ending_vault_saved',
        effect: {
          removeItems: { geck_stabilizer: 1 },
          setFlags: { vault_isolated_ch1: true },
          incrementCounters: { vault_openness: -1 },
          setQuestState: { questId: 'main_stabilizer', status: 'completed', stage: 'completed' },
          xpDelta: 250,
        },
      },
      {
        id: 'sneak_protectron_keycard',
        text: '[AGI] Przemknij w cieniu za plecami uszkodzonego Strażnika RobCo i wyjmij mu Kartę Dostępu',
        forbiddenFlag: 'vault_robot_resolved',
        skillCheck: { attribute: 'AGI', difficulty: 5 },
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
    dynamicDescription: (state) => {
      if (state.flags.crater_alliance_formed) {
        return 'NOWE RDZAWE MIASTO: Dzięki zasilaniu ze Stabilizatora G.E.C.K.-Lite dawne złomowisko rozrosło się w tętniące życiem miasto z pompami czystej wody i warsztatami! Mieszkańcy Krypty 73 i osadnicy handlują ramię w ramię, choć na obrzeżach pojawiły się echa dawnego konfliktu z Dziećmi Rdzy.';
      }
      return 'Gwar osady miesza się z zapachem ozonu i pieczonego mięsa kretoszczura. Po lewej Kupiec Szrot rozłożył stragan z bronią, po prawej świeci neon kliniki Doktor Igi, a w głębi widać wejście do baru „Pod Opadem”.';
    },
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
        id: 'visit_crater_expansion_korg',
        text: '[NOWE MIASTO] Odwiedź Nową Kuźnię Żelaza założoną przez oszczędzonego Korga',
        requiredFlag: 'crater_alliance_formed',
        forbiddenFlag: 'crater_expansion_resolved',
        nextSceneId: 'crater_town_council',
        effect: {
          setQuestState: { questId: 'side_crater_expansion', status: 'active', stage: 'defend_town' },
        },
      },
      {
        id: 'leave_crater',
        text: 'Wyrusz w drogę przez Pustkowia [Mapa Świata]',
        nextSceneId: 'crater_hub',
        effect: { openWorldMap: true },
      },
    ],
  },
  crater_town_council: {
    id: 'crater_town_council',
    title: 'Nowe Rdzawe Miasto — Obrzeża i Kuźnia',
    locationId: 'rusty_crater',
    description:
      'Na obrzeżach rozbudowanego Rdzawego Miasta rozstrzygają się losy dawnych Dzieci Rdzy.',
    dynamicDescription: (state) => {
      if (state.flags.korg_spared) {
        return 'Oszczędzony przez Ciebie Watażka Korg porzucił fanatyzm i wraz ze swoimi ludźmi założył Nową Osadę Kuźniczą obok Rdzawego Miasta. „Dotrzymałem słowa, Wędrowcze! Przetapiamy stary złom na płyty pancerne dla miasta i Twojej Krypty. Jeśli pomożesz nam uruchomić wielki piec, oddam Ci naszą najlepszą broń!”';
      }
      return 'Ponieważ Watażka Korg zginął w rafinerii, władzę nad niedobitkami Dzieci Rdzy przejęła jego fanatyczna zastępczyni — Mściwa Żmija! Jej komando właśnie podkłada ładunki pod mury Nowego Rdzawego Miasta: „Zapłacicie krwią za śmierć Korga!”';
    },
    choices: [
      {
        id: 'ally_korg_settlement',
        text: 'Przyjmij traktat z osadą Korga i odbierz dar kuźni (+150 kapsli, +Ostrze Plazmowe, +150 XP)',
        requiredFlag: 'korg_spared',
        forbiddenFlag: 'crater_expansion_resolved',
        nextSceneId: 'crater_hub',
        effect: {
          capsDelta: 150,
          addItems: { plasma_blade: 1, stimpak: 2 },
          xpDelta: 150,
          setFlags: { crater_expansion_resolved: true, korg_settlement_active: true },
          incrementCounters: { vault_openness: 1 },
          setQuestState: { questId: 'side_crater_expansion', status: 'completed', stage: 'completed' },
        },
      },
      {
        id: 'fight_viper_revenge',
        text: 'Staw czoła Mściwej Żmii i obronić Nowe Rdzawe Miasto przed odwetem Dzieci Rdzy!',
        forbiddenFlag: 'korg_spared',
        nextSceneId: 'crater_hub',
        effect: {
          setFlags: { crater_expansion_resolved: true, viper_defeated_crater: true },
          setQuestState: { questId: 'side_crater_expansion', status: 'completed', stage: 'completed' },
          startCombat: {
            enemyId: 'viper_rust_leader',
            victorySceneId: 'crater_hub',
          },
        },
      },
      {
        id: 'back_crater_from_council',
        text: 'Wróć na Plac Centralny',
        nextSceneId: 'crater_hub',
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
        id: 'sneak_past_relay_ghoul',
        text: '[AGI] Przemknij cicho po dachu ambulansu za plecami Ghula i zabezpiecz dziedziniec',
        forbiddenFlag: 'relay_ghoul_cleared',
        skillCheck: { attribute: 'AGI', difficulty: 5 },
        nextSceneId: 'relay_entrance',
        failSceneId: 'relay_entrance',
        effect: {
          setFlags: { relay_ghoul_cleared: true },
          xpDelta: 65,
        },
        failEffect: {
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
      'Na zielonym ekranie odczytujesz raport czujników: Watażka Korg zabrał Moduł Stabilizatora G.E.C.K.-Lite do Twierdzy Dzieci Rdzy w starej rafinerii. W napędzie terminala tkwi też zaszyfrowana Holotaśma Nadzorcza RobCo.\n\nPonadto w rejestrze dostępu zauważasz coś niepokojącego: zamek komory stabilizatora nie został wyłamany przez Korga — ktoś otworzył go wcześniej zdalnym kodem autoryzacyjnym nadanym bezpośrednio z Gabinetu Nadzorcy Krypty 73!',
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
        id: 'inspect_relay_sabotage_log',
        text: '[PER/INT] Zabezpiecz logi zdalnego sabotażu komory stabilizatora (Ślad spisku Enklawy 1/3)',
        forbiddenFlag: 'clue_relay_sabotage',
        nextSceneId: 'relay_terminal',
        effect: {
          setFlags: { clue_relay_sabotage: true },
          incrementCounters: { enclave_clues: 1 },
          setQuestState: {
            questId: 'side_enclave_conspiracy',
            status: 'active',
            stage: 'gather_clues',
          },
          xpDelta: 70,
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
    dynamicDescription: (state) => {
      if (state.flags.korg_resolved) {
        if (state.flags.viper_defeated_crater || state.flags.viper_v13_defeated) {
          return 'Sala tronowa w dawnej rafinerii pogrążona jest w martwej ciszy. Po upadku Korga i rozbiciu niedobitków Mściwej Żmii dawna Twierdza Dzieci Rdzy opustoszała — z wygaszonych rurociągów ulatniają się resztki pary, a na posadzce zalega popiół i bezużyteczny złom.';
        }
        if (state.flags.korg_spared) {
          return 'Sala tronowa dawnej rafinerii stoi pusta. Po zawarciu porozumienia Watażka Korg i jego ludzie przenieśli się do nowej osady kuźniczej przy Rdzawym Kraterze, pozostawiając za sobą jedynie wygaszone palniki i puste kadzie.';
        }
        return 'Płonąca niegdyś sala tronowa rafinerii opustoszała po pokonaniu Watażki Korga. Wypalone cewki generatora plazmowego ostygły, a po klanie Dzieci Rdzy pozostały jedynie zgliszcza i wypalony żużel.';
      }
      return 'W sercu płonącej rafinerii Watażka Korg podłączył Moduł Stabilizatora G.E.C.K.-Lite do niestabilnego generatora plazmowego. Iskry sypią się z przegrzanych cewek!\n\nKorg ryczy przez głośnik pancerza: „Ten święty rdzeń da Dzieciom Rdzy moc panowania nad całym sektorem! Zbliż się o krok, a spopielę Cię na miejscu!”';
    },
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
      {
        id: 'leave_fortress',
        text: 'Opuść Twierdzę Dzieci Rdzy [Mapa Świata]',
        nextSceneId: 'fortress_gate',
        effect: { openWorldMap: true },
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
        text: '[FINAŁ ROZDZIAŁU 1 — SOJUSZ Z KRATEREM] Przekaż Stabilizator wspólnocie Rdzawego Krateru i otwórz Kryptę na świat',
        forbiddenFlag: 'crater_alliance_formed',
        nextSceneId: 'ending_wasteland_alliance',
        effect: {
          removeItems: { geck_stabilizer: 1 },
          setFlags: { crater_alliance_formed: true },
          incrementCounters: { vault_openness: 2 },
          setQuestState: { questId: 'main_stabilizer', status: 'completed', stage: 'completed' },
          xpDelta: 250,
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
        id: 'go_reno_rooftops',
        text: '[AGI — PRZEMYT] Wdrap się po drabinie pożarowej na dachy Alei Neonów (Szlak Przemytników Leków)',
        forbiddenFlag: 'reno_rooftop_meds_delivered',
        nextSceneId: 'reno_rooftops',
        effect: {
          setQuestState: { questId: 'side_reno_rooftops', status: 'active', stage: 'run_rooftops' },
        },
      },
      {
        id: 'leave_reno_map',
        text: 'Otwórz Mapę Świata (Podróż poza New Reno)',
        nextSceneId: 'reno_gates',
        effect: { openWorldMap: true },
      },
    ],
  },
  reno_rooftops: {
    id: 'reno_rooftops',
    title: 'New Reno — Dachy Alei Neonów (Szlak Kurierów)',
    locationId: 'new_reno',
    description:
      'Wysoko nad zatłoczonymi ulicami New Reno rozciąga się plątanina stalowych kładek, kabli wysokiego napięcia i migoczących neonów. Kurierzy przemycają tędy leki do Szpitala Św. Atomu, omijając haracze gangów. Na sąsiednim dachu leży porzucony zasobnik z kontrabandą medyczną.',
    choices: [
      {
        id: 'parkour_rooftop_meds',
        text: '[AGI] Przeskocz nad przepaścią po gzymsach neonu i przechwyć Kontrabandę Leków (+110 XP)',
        forbiddenFlag: 'reno_rooftop_meds_taken',
        skillCheck: { attribute: 'AGI', difficulty: 6 },
        nextSceneId: 'reno_rooftops',
        failSceneId: 'reno_gates',
        effect: {
          addItems: { smuggled_reno_meds: 1, stimpak: 1 },
          setFlags: { reno_rooftop_meds_taken: true },
          setQuestState: { questId: 'side_reno_rooftops', status: 'active', stage: 'deliver_hospital' },
          xpDelta: 110,
        },
        failEffect: {
          hpDelta: -20,
        },
      },
      {
        id: 'descend_rooftops_hospital',
        text: 'Zjedź po linie wentylacyjnej prosto na dziedziniec Szpitala Św. Atomu',
        nextSceneId: 'reno_hospital',
      },
      {
        id: 'descend_rooftops_gates',
        text: 'Zejdź po drabinie z powrotem na Aleję Neonów',
        nextSceneId: 'reno_gates',
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
        id: 'deliver_rooftop_smuggled_meds',
        text: '[PRZEMYT Z DACHÓW] Przekaż Ordynator Lenie Kontrabandę Leków (+140 kapsli, +100 XP)',
        requiredItem: 'smuggled_reno_meds',
        forbiddenFlag: 'reno_rooftop_meds_delivered',
        nextSceneId: 'reno_hospital',
        effect: {
          removeItems: { smuggled_reno_meds: 1 },
          capsDelta: 140,
          addItems: { radaway: 2 },
          xpDelta: 100,
          setFlags: { reno_rooftop_meds_delivered: true },
          setQuestState: { questId: 'side_reno_rooftops', status: 'completed', stage: 'completed' },
        },
      },
      {
        id: 'talk_cured_morbus_hospital',
        text: 'Przesłuchaj leczonego pod strażą Doktora Morbusa na temat dostawców mutagenu',
        requiredFlag: 'morbus_in_hospital',
        forbiddenFlag: 'morbus_hospital_interrogated',
        nextSceneId: 'reno_hospital',
        effect: {
          addItems: { stimpak: 2 },
          xpDelta: 80,
          setFlags: { morbus_hospital_interrogated: true, clue_morbus_enclave: true },
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
        text: '[PER] Zagraj o wysoką stawkę przy stole Ruletki Atomowej (Stawka: 30 kapsli, limit stołu: 3 wygrane)',
        requiredCaps: 30,
        forbiddenFlag: 'casino_banned',
        maxCounter: { key: 'roulette_wins', value: 2 },
        skillCheck: { attribute: 'PER', difficulty: 6 },
        nextSceneId: 'reno_casino',
        failSceneId: 'reno_casino',
        effect: {
          capsDelta: 60,
          xpDelta: 30,
          incrementCounters: { roulette_wins: 1 },
        },
        failEffect: {
          capsDelta: -30,
        },
      },
      {
        id: 'persuade_salvatore_note',
        text: '[CHA] Przekonaj Dona Salvatore, że oczyszczenie kanałów uratuje zyski kasyna (Odbierz Weksel za darmo)',
        forbiddenFlag: 'salvatore_persuasion_attempted',
        skillCheck: { attribute: 'CHA', difficulty: 7 },
        nextSceneId: 'reno_casino',
        failSceneId: 'reno_casino',
        effect: {
          addItems: { jackals_debt_note: 1 },
          xpDelta: 90,
          setFlags: { debt_note_acquired: true, salvatore_persuasion_attempted: true },
        },
        failEffect: {
          hpDelta: -15,
          setFlags: { salvatore_persuasion_attempted: true, salvatore_insulted: true },
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
        id: 'enter_casino_boxing_ring',
        text: '[STR/END — RING BOKSERSKI] Zejdź na podziemny ring Kasyna i walcz na pięści o pas mistrzowski',
        forbiddenFlag: 'reno_boxing_completed',
        nextSceneId: 'reno_boxing_ring',
        effect: {
          setQuestState: { questId: 'side_reno_boxing', status: 'active', stage: 'fight_champion' },
        },
      },
      {
        id: 'back_reno_from_casino',
        text: 'Wyjdź z Kasyna na Aleję Neonów',
        nextSceneId: 'reno_gates',
      },
    ],
  },
  reno_boxing_ring: {
    id: 'reno_boxing_ring',
    title: 'Kasyno „Złoty Atom” — Podziemny Ring Bokserski',
    locationId: 'new_reno',
    description:
      'W zadymionej piwnicy kasyna tłum hazardzistów dopinguje zawodników walczących w stalowej klatce. Na środku ringu pręży muskuły niepokonany czempion Don Salvatore — „Miażdżyciel” Malone!\n\n„Wchodzisz do klatki, szczurze z Krypty? Albo znokautujesz mnie gołymi pięśćmi, albo wyniosą Cię stąd w worku!”',
    choices: [
      {
        id: 'boxing_knockout_str',
        text: '[STR 7] Wyprowadź druzgocący sierpowy i znokautuj Malone’a w pierwszej rundzie (+200 kapsli, +140 XP)',
        forbiddenFlag: 'reno_boxing_completed',
        skillCheck: { attribute: 'STR', difficulty: 7 },
        nextSceneId: 'reno_casino',
        failSceneId: 'reno_boxing_ring',
        effect: {
          capsDelta: 200,
          xpDelta: 140,
          setFlags: { reno_boxing_completed: true, reno_boxing_champion: true },
          setQuestState: { questId: 'side_reno_boxing', status: 'completed', stage: 'completed' },
        },
        failEffect: {
          hpDelta: -25,
        },
      },
      {
        id: 'boxing_outlast_end',
        text: '[END 7] Przetrzymaj grad ciosów Malone’a i wykończ go, gdy opadnie z sił (+200 kapsli, +140 XP)',
        forbiddenFlag: 'reno_boxing_completed',
        skillCheck: { attribute: 'END', difficulty: 7 },
        nextSceneId: 'reno_casino',
        failSceneId: 'reno_boxing_ring',
        effect: {
          capsDelta: 200,
          xpDelta: 140,
          setFlags: { reno_boxing_completed: true, reno_boxing_champion: true },
          setQuestState: { questId: 'side_reno_boxing', status: 'completed', stage: 'completed' },
        },
        failEffect: {
          hpDelta: -25,
        },
      },
      {
        id: 'boxing_brawl_combat',
        text: 'Stocz pełne starcie na arenie z „Miażdżycielem” Malone’em!',
        forbiddenFlag: 'reno_boxing_completed',
        nextSceneId: 'reno_casino',
        effect: {
          setFlags: { reno_boxing_completed: true, reno_boxing_champion: true },
          setQuestState: { questId: 'side_reno_boxing', status: 'completed', stage: 'completed' },
          startCombat: {
            enemyId: 'reno_pit_champion',
            victorySceneId: 'reno_casino',
          },
        },
      },
      {
        id: 'leave_boxing_ring',
        text: 'Wróć na salę główną Kasyna',
        nextSceneId: 'reno_casino',
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
        forbiddenFlag: 'morbus_defeated',
        nextSceneId: 'reno_sewers_boss',
        effect: {
          setQuestState: { questId: 'ch2_new_reno', status: 'active', stage: 'defeat_morbus' },
        },
      },
      {
        id: 'enter_cleared_morbus_lair',
        text: 'Wejdź do zabezpieczonego Laboratorium Doktora Morbusa (Sektor 3)',
        requiredFlag: 'morbus_defeated',
        nextSceneId: 'reno_sewers_boss',
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
    dynamicDescription: (state) => {
      const morbusDefeated = Boolean(
        state.flags.morbus_defeated ||
        state.flags.morbus_fate_decided ||
        state.flags.morbus_spared ||
        state.flags.morbus_killed ||
        state.flags.morbus_in_hospital
      );
      if (morbusDefeated) {
        if (state.flags.morbus_killed) {
          return 'Wkraczasz do zdemolowanego Laboratorium w Sektorze 3. Zniszczony egzoszkielet Króla Kanałów dymi w kałuży mutagenu obok martwego Doktora Morbusa. Pompy tłoczące truciznę zostały wyłączone, a woda płynąca przez komorę jest krystalicznie czysta.';
        }
        if (state.flags.morbus_in_hospital) {
          return 'Laboratorium w Sektorze 3 opustoszało. Aparatura pompująca została odcięta i zabezpieczona, a ranny Doktor Morbus trafił pod klucz do Szpitala Św. Atomu. W rurociągach New Reno znów płynie nieskażona woda.';
        }
        if (state.flags.morbus_spared) {
          return 'Wchodzisz do cichej komory pomp w Sektorze 3. Po tym, jak Doktor Morbus został zmuszony do kapitulacji i złożenia zeznań obciążających Enklawę, jego instalacja została zabezpieczona. Zagrożenie dla New Reno minęło.';
        }
        return 'Komora laboratoryjna w Sektorze 3 została oczyszczona. Aparatura tłocząca mutagen została trwale wyłączona, a egzoszkielet Króla Kanałów leży roztrzaskany na pomoście. Doktor Morbus został już definitywnie pokonany.';
      }
      return (
        'W centrum zalanej komory wodociągowej wznosi się aparatura pompująca zielony mutagen prosto do rur New Reno. Na pomoście stoi Doktor Morbus w egzoszkielecie połączonym kablami z gigantycznym Królem Kanałów!\n\n' +
        '„New Reno zgniło od środka!” — skrzeczy Morbus przez maskę tlenową. — „Mój mutagen oczyści to miasto i stworzy nową rasę dzieci podziemi! Nikt mnie nie powstrzyma!”'
      );
    },
    choices: [
      {
        id: 'boss_attack_with_jackals',
        text: '[SOJUSZ Z BANDYTAMI] Daj sygnał Wściekłemu Vinnie’mu — Żelazne Szakale uderzają z flanki i osłabiają Morbusa!',
        requiredFlag: 'jackals_allied',
        forbiddenFlag: 'morbus_defeated',
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
        forbiddenFlag: 'morbus_defeated',
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
        forbiddenFlag: 'morbus_defeated',
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
        forbiddenFlag: 'morbus_defeated',
        nextSceneId: 'reno_sewers_victory',
        effect: {
          startCombat: {
            enemyId: 'doctor_morbus_full',
            victorySceneId: 'reno_sewers_victory',
          },
        },
      },
      {
        id: 'retreat_from_boss_to_mid',
        text: 'Wycofaj się po cichu do Zbiornika Filtracyjnego (Sektor 2)',
        forbiddenFlag: 'morbus_defeated',
        nextSceneId: 'reno_sewers_mid',
      },
      {
        id: 'go_to_purifier_station',
        text: 'Przejdź do Stacji Kontroli Wodociągów New Reno',
        requiredFlag: 'morbus_defeated',
        nextSceneId: 'reno_sewers_victory',
      },
      {
        id: 'leave_boss_lair_to_mid',
        text: 'Wróć korytarzem do Zbiornika Filtracyjnego (Sektor 2)',
        requiredFlag: 'morbus_defeated',
        nextSceneId: 'reno_sewers_mid',
      },
      {
        id: 'leave_boss_lair_to_city',
        text: 'Wyjdź szybem wentylacyjnym bezpośrednio na Aleję Neonów',
        requiredFlag: 'morbus_defeated',
        nextSceneId: 'reno_gates',
      },
    ],
  },
  reno_sewers_victory: {
    id: 'reno_sewers_victory',
    title: 'New Reno Ocalone — Czyste Źródła i Zeznania Morbusa',
    locationId: 'new_reno',
    onEnterEffect: {
      setQuestState: { questId: 'ch2_new_reno', status: 'active', stage: 'return_overseer' },
      setFlags: { morbus_defeated: true },
    },
    description:
      'Doktor Morbus pada na kolana obok roztrzaskanej kadzi z mutagenem! Przełączasz główną dźwignię stacji filtracyjnej — krystalicznie czysta woda pod ciśnieniem wypłukuje toksyny z wodociągów New Reno.\n\nZanim zadasz ostateczny cios, krwawiący Morbus zdziera maskę tlenową i charczy:\n„Głupcze... myślisz, że sam wymyśliłem tę formułę?! Ampułki z mutagenem FEV-II i schematy wodociągów przysłał mi kurier z szyfrem Enklawy — prosto z Waszej Krypty 73! Oszczędź mnie, a powiem Ci wszystko!”',
    dynamicDescription: (state) => {
      const rulerText = state.flags.reno_ruled_by_vinnie
        ? '\n\nWładzę nad ulicami New Reno przejął Twój sojusznik Wściekły Vinnie i Żelazne Szakale, zaprowadzając żelazny porządek w dzielnicach.'
        : state.flags.reno_ruled_by_salvatore
        ? '\n\nWładzę nad New Reno przejął syndykat Dona Salvatore, zamieniając miasto w imperium hazardu i handlu wodą.'
        : state.flags.reno_ruled_by_sheriff
        ? '\n\nWładzę w New Reno umocnił Szeryf Vance wraz z radą obywatelską i lekarzami ze Szpitala.'
        : '';

      if (state.flags.morbus_fate_decided) {
        let morbusFateText = '';
        if (state.flags.morbus_killed) {
          morbusFateText = 'Stacja filtracyjna New Reno pracuje bez zakłóceń, tłocząc czystą wodę do miejskiej sieci wodociągowej. Ciało pokonanego Doktora Morbusa leży obok rozbitej kadzi z mutagenem — jego szaleńczy eksperyment dobiegł końca.';
        } else if (state.flags.morbus_in_hospital) {
          morbusFateText = 'Stacja filtracyjna New Reno tłoczy czystą wodę do kranów całego miasta. Ranny Doktor Morbus został przekazany pod strażą na leczenie do Szpitala Św. Atomu, gdzie oczekuje na dalsze śledztwo.';
        } else {
          morbusFateText = 'Stacja filtracyjna New Reno pracuje pełną mocą, oczyszczając podziemne rezerwuary. Oszczędzony Doktor Morbus złożył zeznania obciążające agentów Enklawy i został odstawiony pod nadzorem straży.';
        }
        return morbusFateText + rulerText;
      }

      return (
        'Doktor Morbus pada na kolana obok roztrzaskanej kadzi z mutagenem! Przełączasz główną dźwignię stacji filtracyjnej — krystalicznie czysta woda pod ciśnieniem wypłukuje toksyny z wodociągów New Reno.\n\nKrwawiący Morbus zdziera maskę tlenową i charczy: „Głupcze... myślisz, że sam wymyśliłem tę formułę?! Ampułki z mutagenem FEV-II i schematy wodociągów przysłał mi kurier z szyfrem Enklawy — prosto z Waszej Krypty 73! Oszczędź mnie, a powiem Ci wszystko!”' +
        rulerText
      );
    },
    choices: [
      {
        id: 'spare_morbus_testify',
        text: '[OSZCZĘDŹ MORBUSA — ZEZNANIA] Oszczędź Morbusa i zmuś go do złożenia zeznań o Enklawie oraz oddania szyfrów (Ślad spisku 2/3)',
        forbiddenFlag: 'morbus_fate_decided',
        nextSceneId: 'reno_sewers_victory',
        effect: {
          setFlags: {
            morbus_defeated: true,
            morbus_fate_decided: true,
            morbus_spared: true,
            clue_morbus_enclave: true,
          },
          incrementCounters: { enclave_clues: 1, vault_openness: 1 },
          setQuestState: {
            questId: 'side_enclave_conspiracy',
            status: 'active',
            stage: 'gather_clues',
          },
          xpDelta: 120,
        },
      },
      {
        id: 'spare_morbus_hospital',
        text: '[OSZCZĘDŹ MORBUSA — SZPITAL] Odeślij rannego Morbusa pod strażą na leczenie i obserwację do Szpitala Św. Atomu',
        forbiddenFlag: 'morbus_fate_decided',
        nextSceneId: 'reno_sewers_victory',
        effect: {
          setFlags: {
            morbus_defeated: true,
            morbus_fate_decided: true,
            morbus_spared: true,
            morbus_in_hospital: true,
            clue_morbus_enclave: true,
          },
          incrementCounters: { enclave_clues: 1 },
          setQuestState: {
            questId: 'side_enclave_conspiracy',
            status: 'active',
            stage: 'gather_clues',
          },
          xpDelta: 120,
        },
      },
      {
        id: 'execute_morbus',
        text: '[EGZEKUCJA] Wymierz Morbusowi sprawiedliwość na miejscu i przeszukaj jego dziennik laboratoryjny',
        forbiddenFlag: 'morbus_fate_decided',
        nextSceneId: 'reno_sewers_victory',
        effect: {
          setFlags: {
            morbus_defeated: true,
            morbus_fate_decided: true,
            morbus_killed: true,
          },
          capsDelta: 80,
          xpDelta: 80,
        },
      },
      {
        id: 'reno_power_sheriff',
        text: '[WŁADZA W NEW RENO] Przekaż kontrolę nad oczyszczalnią Szeryfowi Vance’owi i Straży Obywatelskiej',
        requiredFlag: 'morbus_fate_decided',
        forbiddenFlag: 'reno_power_decided',
        nextSceneId: 'reno_sewers_victory',
        effect: {
          setFlags: { reno_power_decided: true, reno_ruled_by_sheriff: true },
          incrementCounters: { vault_openness: 1 },
          xpDelta: 75,
        },
      },
      {
        id: 'reno_power_vinnie',
        text: '[WŁADZA W NEW RENO] Powierz rządy nad miastem swoim sojusznikom — Wściekłemu Vinnie’mu i Żelaznym Szakalom',
        requiredFlag: 'jackals_allied',
        forbiddenFlag: 'reno_power_decided',
        nextSceneId: 'reno_sewers_victory',
        effect: {
          setFlags: { reno_power_decided: true, reno_ruled_by_vinnie: true },
          capsDelta: 120,
          xpDelta: 75,
        },
      },
      {
        id: 'reno_power_salvatore',
        text: '[WŁADZA W NEW RENO] Sprzedaj koncesję wodną syndykatowi Dona Salvatore z Kasyna „Złoty Atom” (+200 kapsli)',
        requiredFlag: 'morbus_fate_decided',
        forbiddenFlag: 'reno_power_decided',
        nextSceneId: 'reno_sewers_victory',
        effect: {
          setFlags: { reno_power_decided: true, reno_ruled_by_salvatore: true },
          capsDelta: 200,
          incrementCounters: { vault_openness: -1 },
          xpDelta: 75,
        },
      },
      {
        id: 'return_to_mid_from_victory',
        text: 'Wróć w głąb kanałów (Sektor 2: Zbiornik Filtracyjny)',
        nextSceneId: 'reno_sewers_mid',
      },
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
    title: 'KONIEC ROZDZIAŁU 2: Zastępca Nadzorcy — Rok Później: Kryzys Wodny',
    locationId: 'vault_73',
    description:
      'Minął dokładnie rok, odkąd jako Zastępca Nadzorcy zaprowadziłeś pokój i dobrobyt w Krypcie 73. Niestety, w samym sercu schronu dochodzi do katastrofy — główny Water Chip (Hydroprocesor) stopił się pod wpływem przeciążenia obwodów. Zapasów zdatnej do picia wody wystarczy na zaledwie 2 miesiące!\n\nNadzorca zwołuje naradę kryzysową:\n„Wędrowcze, jesteś naszą jedyną nadzieją. Przedwojenne rejestry mówią o mitycznej Krypcie 13 — bliźniaczym schronie, który posiadał zapasowe moduły oczyszczania wody. Nikt nie zna jej dokładnej lokalizacji, ale starszyzna z plemiennej wioski Arroyo na północy może pamiętać pradawne legendy o Krypcie 13. Wyrusz do Arroyo, odnajdź drogę do Krypty 13 i ocal nas!”',
    choices: [
      {
        id: 'start_chapter_3_crisis',
        text: 'Odbierz ekwipunek wyprawy ratunkowej (+150 kapsli, +3 Stimpaki) i ruszaj do Wioski Arroyo [Rozdział 3]',
        nextSceneId: 'vault_atrium',
        effect: {
          capsDelta: 150,
          addItems: { stimpak: 3, radaway: 2 },
          setFlags: { arroyo_unlocked: true },
          setQuestState: { questId: 'ch3_water_chip', status: 'active', stage: 'travel_arroyo' },
        },
      },
    ],
  },
  arroyo_village: {
    id: 'arroyo_village',
    title: 'Wioska Arroyo — Namiot Starszej Hakunin',
    locationId: 'arroyo',
    onEnterEffect: {
      setQuestState: { questId: 'ch3_water_chip', status: 'active', stage: 'help_arroyo' },
    },
    description:
      'Docierasz do skalistego kanionu, w którym mieści się plemienna wioska Arroyo. Przy świętym ognisku modli się Starsza Hakunin:\n\n„Witaj, wędrowcze w błękitnym stroju Pradawnych. Słyszę szept wiatru — szukasz Krypty 13, kolebki naszych przodków. Nasz lud przekaże Ci wiedzę i skieruje Cię do Nowej Republiki Kalifornii (NCR), gdzie mają przedwojenne mapy. Ale najpierw musisz nam pomóc — horda krwiożerczych bestii pustkowi napadła na nasze święte pastwiska Brahminów. Jeśli bydło padnie, plemię wymrze z głodu!”',
    choices: [
      {
        id: 'go_arroyo_pasture',
        text: 'Chwyć za broń i ruszaj na pastwisko bronić Brahminów',
        nextSceneId: 'arroyo_pasture',
        effect: {
          setQuestState: { questId: 'side_arroyo_pasture', status: 'active', stage: 'hunt_beasts' },
        },
      },
      {
        id: 'claim_arroyo_reward',
        text: 'Odbierz od Starszej Hakunin Talizman Wybrańca i namiary na NCR (+150 XP)',
        requiredFlag: 'arroyo_pasture_cleared',
        forbiddenFlag: 'arroyo_reward_taken',
        nextSceneId: 'arroyo_village',
        effect: {
          addItems: { arroyo_talisman: 1, stimpak: 2 },
          setFlags: { arroyo_reward_taken: true, ncr_unlocked: true },
          setQuestState: { questId: 'ch3_water_chip', status: 'active', stage: 'travel_ncr' },
          xpDelta: 150,
        },
      },
      {
        id: 'arroyo_heal',
        text: '[END] Poproś szamana o tradycyjny wywar z ziół leczniczych (+70 HP, -30 RAD)',
        nextSceneId: 'arroyo_village',
        effect: {
          hpDelta: 70,
          radDelta: -30,
        },
      },
      {
        id: 'leave_arroyo_map',
        text: 'Otwórz Mapę Świata (Wyrusz w dalszą podróż)',
        nextSceneId: 'arroyo_village',
        effect: { openWorldMap: true },
      },
    ],
  },
  arroyo_pasture: {
    id: 'arroyo_pasture',
    title: 'Wioska Arroyo — Skaliste Pastwiska',
    locationId: 'arroyo',
    description:
      'W kanionie słychać ryk przerażonych dwugłowych krów. Wokół stada krążą wataha Zdziczałych Wilków Pustynnych oraz olbrzymi Alfa Radskorpion z kleszczami twardymi jak stal!',
    choices: [
      {
        id: 'fight_arroyo_wolves',
        text: 'Zlikwiduj Zdziczałego Wilka Pustynnego atakującego młodego brahmina',
        forbiddenFlag: 'arroyo_wolf_cleared',
        nextSceneId: 'arroyo_pasture',
        effect: {
          setFlags: { arroyo_wolf_cleared: true },
          startCombat: {
            enemyId: 'desert_stalker',
            victorySceneId: 'arroyo_pasture',
            fleeSceneId: 'arroyo_village',
          },
        },
      },
      {
        id: 'fight_arroyo_scorpion',
        text: 'Stań do walki z potężnym Alfa Radskorpionem!',
        requiredFlag: 'arroyo_wolf_cleared',
        forbiddenFlag: 'arroyo_scorpion_cleared',
        nextSceneId: 'arroyo_pasture',
        effect: {
          setFlags: { arroyo_scorpion_cleared: true },
          startCombat: {
            enemyId: 'alpha_radscorpion',
            victorySceneId: 'arroyo_pasture',
            fleeSceneId: 'arroyo_village',
          },
        },
      },
      {
        id: 'tame_brahmins',
        text: '[CHA] Uspokój stado Brahminów i zabezpiecz przejście do kanionu',
        requiredFlag: 'arroyo_scorpion_cleared',
        forbiddenFlag: 'arroyo_pasture_cleared',
        skillCheck: { attribute: 'CHA', difficulty: 6 },
        nextSceneId: 'arroyo_village',
        failSceneId: 'arroyo_village',
        effect: {
          setFlags: { arroyo_pasture_cleared: true },
          setQuestState: { questId: 'side_arroyo_pasture', status: 'completed', stage: 'completed' },
          xpDelta: 100,
        },
        failEffect: {
          setFlags: { arroyo_pasture_cleared: true },
          setQuestState: { questId: 'side_arroyo_pasture', status: 'completed', stage: 'completed' },
        },
      },
      {
        id: 'back_arroyo_village',
        text: 'Wróć do centrum wioski Arroyo',
        nextSceneId: 'arroyo_village',
      },
    ],
  },
  ncr_downtown: {
    id: 'ncr_downtown',
    title: 'Nowa Republika Kalifornii (NCR) — Plac Wolności',
    locationId: 'ncr',
    onEnterEffect: {
      setQuestState: { questId: 'ch3_water_chip', status: 'active', stage: 'gain_ncr_map' },
    },
    description:
      'NCR to prawdziwe państwo z asfaltowymi alejami, betonowymi budynkami i patrolami Strażników NCR w prochowcach. W centrum miasta wznosi się Ratusz, po lewej Baza Zaopatrzeniowa Kwatermistrza Bustera, po prawej Szpital Wojskowy Dr Henry’ego, a w bocznej alei neon Klubu „Kocia Łapka”. Na rogu wisi obwieszczenie o dezerterach nękających konwoje na przedmieściach.',
    choices: [
      {
        id: 'go_ncr_hall',
        text: 'Wejdź do Ratusza i spotkaj się z Burmistrzem Tibbettem',
        nextSceneId: 'ncr_hall',
      },
      {
        id: 'trade_buster',
        text: 'Odwiedź Kwatermistrza Bustera [Karabiny Snajperskie, Miniguny, Pancerze NCR]',
        nextSceneId: 'ncr_downtown',
        effect: { openMerchant: 'buster_ncr' },
      },
      {
        id: 'go_ncr_hospital',
        text: 'Odwiedź Szpital Polowy NCR (Dr Henry)',
        nextSceneId: 'ncr_hospital',
      },
      {
        id: 'go_ncr_catspaw',
        text: 'Wejdź do Klubu „Kocia Łapka” (Madame Dupree)',
        nextSceneId: 'ncr_catspaw',
      },
      {
        id: 'go_ncr_outskirts',
        text: 'Wyrusz na przedmieścia NCR (Kryjówka Buntowników)',
        nextSceneId: 'ncr_outskirts',
      },
      {
        id: 'leave_ncr_map',
        text: 'Otwórz Mapę Świata',
        nextSceneId: 'ncr_downtown',
        effect: { openWorldMap: true },
      },
    ],
  },
  ncr_hall: {
    id: 'ncr_hall',
    title: 'NCR — Ratusz Republiki (Gabinet Burmistrza Tibbetta)',
    locationId: 'ncr',
    description:
      'Burmistrz Tibbett spogląda na Ciebie zza dębowego biurka: „Słyszałem o Tobie od Starszej z Arroyo. Szukasz Krypty 13? Nasze archiwa posiadają oryginalną, przedwojenną Holomapę z dokładnymi współrzędnymi jej śluzy. Republika nie rozdaje jednak skarbów za darmo. Na przedmieściach operuje zorganizowana banda dezerterów nękająca nasze szlaki, a Dr Henry ze szpitala stracił przez nich prototypowy skaner. Zlikwiduj buntowników, a Holomapa Krypty 13 będzie Twoja!”',
    choices: [
      {
        id: 'claim_vault13_map',
        text: 'Zamelduj o rozbiciu buntowników i odbierz Przedwojenną Holomapę Krypty 13 (+200 XP)',
        requiredFlag: 'ncr_convoy_cleared',
        forbiddenFlag: 'vault13_map_taken',
        nextSceneId: 'ncr_hall',
        effect: {
          addItems: { vault_13_map: 1 },
          setFlags: { vault13_map_taken: true, vault_13_unlocked: true },
          setQuestState: { questId: 'ch3_water_chip', status: 'active', stage: 'infiltrate_vault_13' },
          xpDelta: 200,
        },
      },
      {
        id: 'persuade_mayor_bonus',
        text: '[CHA] Wynegocjuj dodatkową dotację rządową 150 kapsli na amunicję',
        forbiddenFlag: 'ncr_stipend_taken',
        skillCheck: { attribute: 'CHA', difficulty: 7 },
        nextSceneId: 'ncr_hall',
        failSceneId: 'ncr_hall',
        effect: {
          capsDelta: 150,
          xpDelta: 75,
          setFlags: { ncr_stipend_taken: true },
        },
        failEffect: {
          setFlags: { ncr_stipend_taken: true },
        },
      },
      {
        id: 'back_ncr_from_hall',
        text: 'Wróć na Plac Wolności',
        nextSceneId: 'ncr_downtown',
      },
    ],
  },
  ncr_hospital: {
    id: 'ncr_hospital',
    title: 'NCR — Szpital Wojskowy Dr Henry’ego',
    locationId: 'ncr',
    description:
      'Dr Henry, dawny naukowiec Enklawy pracujący dla Republiki, krząta się wśród aparatury: „Buntownicy z przedmieść napadli na mój transport medyczny i ukradli Zautomatyzowany Skaner Polowy! Bez niego nie mogę przeprowadzać precyzyjnych operacji usuwania skażenia. Odzyskaj go, a sowicie Cię wynagrodzę. Ponadto mam w laboratorium prototypową Matrycę Replikacyjną zdolną skopiować układ sterujący Water Chipa bez jego wyjmowania!”',
    choices: [
      {
        id: 'accept_scanner_quest',
        text: 'Zadeklaruj odzyskanie skanera medycznego od buntowników',
        forbiddenFlag: 'scanner_quest_started',
        nextSceneId: 'ncr_hospital',
        effect: {
          setFlags: { scanner_quest_started: true },
          setQuestState: { questId: 'side_ncr_crimes', status: 'active', stage: 'attack_convoy' },
        },
      },
      {
        id: 'turn_in_scanner',
        text: 'Zwróć odzyskany Skaner Medyczny (+150 kapsli, +Matryca Replikacyjna Water Chipa, +120 XP)',
        requiredItem: 'stolen_medical_scanner',
        forbiddenFlag: 'scanner_returned',
        nextSceneId: 'ncr_hospital',
        effect: {
          removeItems: { stolen_medical_scanner: 1 },
          capsDelta: 150,
          addItems: { water_chip_replicator: 1, stimpak: 3, radaway: 2 },
          xpDelta: 120,
          setFlags: { scanner_returned: true, henry_replicator_ready: true },
          setQuestState: { questId: 'side_ncr_crimes', status: 'completed', stage: 'completed' },
        },
      },
      {
        id: 'analyze_enclave_signature_henry',
        text: '[ŚLEDZTWO ENKLAWY] Poproś Dr Henry’ego o analizę sygnatur sabotażu reaktora i mutagenu Morbusa (Ślad spisku 3/3)',
        forbiddenFlag: 'clue_henry_enclave',
        nextSceneId: 'ncr_hospital',
        effect: {
          addItems: { enclave_dispatch_log: 1, water_chip_replicator: 1 },
          setFlags: { clue_henry_enclave: true, henry_replicator_ready: true },
          incrementCounters: { enclave_clues: 1 },
          setQuestState: {
            questId: 'side_enclave_conspiracy',
            status: 'active',
            stage: 'confront_traitor',
          },
          xpDelta: 140,
        },
      },
      {
        id: 'ncr_hospital_heal',
        text: 'Zapłać 30 kapsli za wojskową procedurę odtruwania (-100 RAD, +200 HP)',
        requiredCaps: 30,
        nextSceneId: 'ncr_hospital',
        effect: {
          capsDelta: -30,
          radDelta: -100,
          hpDelta: 200,
        },
      },
      {
        id: 'back_ncr_from_hospital',
        text: 'Wróć na Plac Wolności',
        nextSceneId: 'ncr_downtown',
      },
    ],
  },
  ncr_catspaw: {
    id: 'ncr_catspaw',
    title: 'NCR — Klub i Lupanar „Kocia Łapka”',
    locationId: 'ncr',
    description:
      'Aksamitne zasłony, dźwięki jazzu i zapach drogich cygar. Właścicielka, Madame Dupree, wita Cię tajemniczym uśmiechem:\n„Szukasz wytchnienia od kurzu pustkowi, czy może szukasz informacji? Moi bywalcy wiedzą wszystko o NCR — na przykład to, że dowódca buntowników na przedmieściach ma słabość do materiałów wybuchowych, a ich generator można przeciążyć jednym strzałem.”',
    choices: [
      {
        id: 'bribe_madame_intel',
        text: '[CHA lub 50 kapsli] Zdobądź dokładne kody sabotażu obozu buntowników (+80 XP)',
        forbiddenFlag: 'catspaw_intel_known',
        skillCheck: { attribute: 'CHA', difficulty: 6 },
        nextSceneId: 'ncr_catspaw',
        failSceneId: 'ncr_catspaw',
        effect: {
          setFlags: { catspaw_intel_known: true },
          xpDelta: 80,
        },
        failEffect: {
          setFlags: { catspaw_intel_known: true },
          capsDelta: -50,
        },
      },
      {
        id: 'catspaw_relax',
        text: 'Spędź wieczór w salonie VIP (Kapsle: 40 | Pełna regeneracja HP i usunięcie zmęczenia)',
        requiredCaps: 40,
        nextSceneId: 'ncr_catspaw',
        effect: {
          capsDelta: -40,
          hpDelta: 100,
        },
      },
      {
        id: 'back_ncr_from_catspaw',
        text: 'Wyjdź na Plac Wolności',
        nextSceneId: 'ncr_downtown',
      },
    ],
  },
  ncr_outskirts: {
    id: 'ncr_outskirts',
    title: 'Przedmieścia NCR — Obóz Buntowników',
    locationId: 'ncr',
    description:
      'Wokół opuszczonego magazynu kolejowego stoi barykada z wraków wagonów. W środku stacjonuje uzbrojony oddział dezerterów pod wodzą Najemnika Bandy Buntowników, rabujący karawany zmierzające do stolicy.',
    choices: [
      {
        id: 'sabotage_camp_generator',
        text: '[Informacje z Kociej Łapki] Wysadź generator polowy buntowników i zdezorganizuj ich obronę (+100 XP)',
        requiredFlag: 'catspaw_intel_known',
        forbiddenFlag: 'ncr_convoy_cleared',
        nextSceneId: 'ncr_outskirts',
        effect: {
          setFlags: { ncr_convoy_cleared: true },
          addItems: { stolen_medical_scanner: 1 },
          xpDelta: 100,
        },
      },
      {
        id: 'fight_ncr_raiders',
        text: 'Zaatakuj Najemnika Bandy Buntowników i odbierz skradziony sprzęt w walce!',
        forbiddenFlag: 'ncr_convoy_cleared',
        nextSceneId: 'ncr_outskirts',
        effect: {
          setFlags: { ncr_convoy_cleared: true },
          startCombat: {
            enemyId: 'ncr_convoy_raider',
            victorySceneId: 'ncr_outskirts',
            fleeSceneId: 'ncr_downtown',
          },
        },
      },
      {
        id: 'back_ncr_from_outskirts',
        text: 'Wróć do centrum NCR',
        nextSceneId: 'ncr_downtown',
      },
    ],
  },
  vault_13_gate: {
    id: 'vault_13_gate',
    title: 'Krypta 13 — Monumentalna Śluza Zewnętrzna',
    locationId: 'vault_13',
    description:
      'Wypalone w skale potężne wrota ze złotą liczbą „13” stoją uchylone w ponurym milczeniu. Na ziemi leżą przedwojenne szkielety, a z sufitu zwisają aktywne Wieżyczki Laserowe RobCo podłączone do głównego terminala kontrolnego.',
    dynamicDescription: (state) => {
      const korgOrViper = state.flags.korg_spared
        ? '\n\nPrzed jaskinią czekają zwiadowcy Watażki Korga z Nowej Osady Kuźniczej — przybyli spłacić dług honorowy i osłaniać Twoje wejście!'
        : '\n\nW cieniu skał czai się oddział pościgowy Mściwej Żmii z Dzieci Rdzy, pragnący pomścić klęskę swojego klanu!';
      const jackalsNote = state.flags.jackals_allied
        ? '\nObok wrót obozują też Żelazne Szakale Wściekłego Vinniego — zwęszyli fortunę i również polują na legendarny Water Chip!'
        : '';
      return (
        'Wypalone w skale potężne wrota ze złotą liczbą „13” stoją uchylone. Z sufitu zwisają aktywne Wieżyczki Laserowe RobCo podłączone do terminala kontrolnego.' +
        korgOrViper +
        jackalsNote
      );
    },
    choices: [
      {
        id: 'use_holomap_secret_tunnel',
        text: '[PRZEDWOJENNA HOLOMAPA] Użyj szyfru z Holomapy NCR, by otworzyć ukryty tunel serwisowy z pominięciem wieżyczek i strażników (+150 XP)',
        requiredItem: 'vault_13_map',
        nextSceneId: 'vault_13_sanctuary',
        effect: {
          setFlags: { vault13_turrets_hacked: true, vault13_holomap_used: true },
          xpDelta: 150,
        },
      },
      {
        id: 'korg_allies_clear_gate',
        text: '[SOJUSZNIK: KORG] Przyjmij wsparcie ogniowe gwardii Watażki Korga i zniszcz wieżyczki bez strat (+100 XP)',
        requiredFlag: 'korg_spared',
        forbiddenFlag: 'vault13_turrets_hacked',
        nextSceneId: 'vault_13_gate',
        effect: {
          setFlags: { vault13_turrets_hacked: true, korg_helped_v13: true },
          addItems: { pulse_grenade: 1 },
          xpDelta: 100,
        },
      },
      {
        id: 'fight_viper_ambush_v13',
        text: '[ZEMSTA ŻMII]Odeprzyj zasadzkę Mściwej Żmii przed wrotami Krypty 13!',
        forbiddenFlag: 'korg_spared',
        nextSceneId: 'vault_13_gate',
        effect: {
          setFlags: { korg_spared: false, viper_v13_defeated: true },
          startCombat: {
            enemyId: 'viper_rust_leader',
            victorySceneId: 'vault_13_gate',
          },
        },
      },
      {
        id: 'hack_vault13_turrets',
        text: '[INT] Zhakuj terminal kontrolny śluzy i dezaktywuj wieżyczki obronne (+120 XP)',
        forbiddenFlag: 'vault13_turrets_hacked',
        skillCheck: { attribute: 'INT', difficulty: 7 },
        nextSceneId: 'vault_13_gate',
        failSceneId: 'vault_13_gate',
        effect: {
          setFlags: { vault13_turrets_hacked: true },
          xpDelta: 120,
        },
        failEffect: {
          setFlags: { vault13_turrets_hacked: true },
          hpDelta: -25,
        },
      },
      {
        id: 'enter_vault13_corridors',
        text: 'Przejdź przez śluzę w głąb Korytarzy Technicznych Krypty 13',
        nextSceneId: 'vault_13_corridors',
      },
      {
        id: 'exit_vault13_map',
        text: 'Wyjdź przed wejście do jaskini [Mapa Świata]',
        nextSceneId: 'vault_13_gate',
        effect: { openWorldMap: true },
      },
    ],
  },
  vault_13_corridors: {
    id: 'vault_13_corridors',
    title: 'Krypta 13 — Korytarze Techniczne i Sekcja Reaktorowa',
    locationId: 'vault_13',
    description:
      'Cienie rzucane przez awaryjne lampy migoczą na stalowych ścianach. Korytarza do zamieszkałego sektora oraz Głównego Laboratorium Hydroprocesorów pilnuje patrolujący Dron Obronny Krypty 13. W bocznym korytarzu widać też sylwetki Żelaznych Szakali Vinniego próbujących wyważyć zamek!',
    choices: [
      {
        id: 'fight_vault13_drone',
        text: 'Zniszcz Drona Obronnego Krypty 13',
        forbiddenFlag: 'vault13_drone_cleared',
        nextSceneId: 'vault_13_corridors',
        effect: {
          setFlags: { vault13_drone_cleared: true },
          startCombat: {
            enemyId: 'robco_repair_drone',
            victorySceneId: 'vault_13_corridors',
            fleeSceneId: 'vault_13_gate',
          },
        },
      },
      {
        id: 'sneak_vault13_drone',
        text: '[AGI] Przemknij tunelem kablowym pod czujnikami Drona Obronnego (+120 XP)',
        forbiddenFlag: 'vault13_drone_cleared',
        skillCheck: { attribute: 'AGI', difficulty: 7 },
        nextSceneId: 'vault_13_corridors',
        failSceneId: 'vault_13_corridors',
        effect: {
          setFlags: { vault13_drone_cleared: true },
          xpDelta: 120,
        },
        failEffect: {
          setFlags: { vault13_drone_cleared: true },
          startCombat: {
            enemyId: 'robco_repair_drone',
            victorySceneId: 'vault_13_corridors',
            fleeSceneId: 'vault_13_gate',
          },
        },
      },
      {
        id: 'confront_vinnie_rival_v13',
        text: '[RYWALIZACJA SZAKALI] Przekonaj lub zastrasz Wściekłego Vinniego, by wycofał Szakali z polowania na Water Chip (+120 XP)',
        requiredFlag: 'jackals_allied',
        forbiddenFlag: 'vinnie_v13_resolved',
        skillCheck: { attribute: 'CHA', difficulty: 6 },
        nextSceneId: 'vault_13_corridors',
        failSceneId: 'vault_13_corridors',
        effect: {
          setFlags: { vinnie_v13_resolved: true },
          xpDelta: 120,
        },
        failEffect: {
          setFlags: { vinnie_v13_resolved: true, jackals_defeated: true },
          startCombat: {
            enemyId: 'vinnie_jackal',
            victorySceneId: 'vault_13_corridors',
          },
        },
      },
      {
        id: 'enter_vault13_sanctuary',
        text: 'Wejdź do Sanktuarium Ocalałych Mieszkańców Krypty 13',
        requiredFlag: 'vault13_drone_cleared',
        nextSceneId: 'vault_13_sanctuary',
      },
      {
        id: 'enter_vault13_lab',
        text: 'Idź bezpośrednio do Głównego Laboratorium Hydroprocesorów (BOSS)',
        requiredFlag: 'vault13_drone_cleared',
        nextSceneId: 'vault_13_lab',
      },
      {
        id: 'back_vault13_gate',
        text: 'Cofnij się w stronę Śluzy',
        nextSceneId: 'vault_13_gate',
      },
    ],
  },
  vault_13_sanctuary: {
    id: 'vault_13_sanctuary',
    title: 'Krypta 13 — Sanktuarium Ocalałych i Nadzorca Lazarus',
    locationId: 'vault_13',
    description:
      'Krypta 13 nie jest martwa! W oświetlonym świetlówkami atrium żyje społeczność ocalałych mieszkańców oraz świadomych, łagodnych ghuli-inżynierów pod przywództwem sędziwego Nadzorcy Lazarusa.\n\nLazarus staje przed grodzią prowadzącą do Hydroprocesora:\n„Zatrzymaj się, Wędrowcze z Krypty 73! Ten Water Chip w naszym laboratorium to ostatni działający układ w całym kompleksie. Jeśli wyrwiesz go siłą, nasza aparatura podtrzymująca życie stanie, a wszyscy mieszkańcy Krypty 13 zginą w ciągu tygodnia! Musi istnieć inne wyjście!”',
    choices: [
      {
        id: 'show_arroyo_talisman_lazarus',
        text: '[TALIZMAN Z ARROYO] Pokaż Nadzorcy Lazarusowi Święty Talizman Wybrańca — „Znak Pradawnych” (+200 XP)',
        requiredItem: 'arroyo_talisman',
        forbiddenFlag: 'v13_talisman_shown',
        nextSceneId: 'vault_13_sanctuary',
        effect: {
          setFlags: { v13_talisman_shown: true },
          incrementCounters: { vault_openness: 1 },
          xpDelta: 200,
        },
      },
      {
        id: 'v13_solution_copy_chip',
        text: '[WYJŚCIE 2: KOPIA Z DR HENRYM] Użyj Matrycy Replikacyjnej Dr Henry’ego, by skopiować układ Water Chipa i ocalić obie Krypty!',
        requiredItem: 'water_chip_replicator',
        forbiddenFlag: 'v13_resolution_done',
        nextSceneId: 'vault_13_victory',
        effect: {
          removeItems: { water_chip_replicator: 1 },
          addItems: { water_chip: 1 },
          setFlags: { v13_resolution_done: true, v13_chip_copied: true },
          incrementCounters: { vault_openness: 1 },
          xpDelta: 350,
        },
      },
      {
        id: 'v13_solution_evacuate_to_73',
        text: '[WYJŚCIE 3: EWAKUACJA DO KRYPTY 73] Zabierz Water Chip, ale przeprowadź wszystkich mieszkańców Krypty 13 do swojego schronu!',
        forbiddenFlag: 'v13_resolution_done',
        nextSceneId: 'vault_13_victory',
        effect: {
          addItems: { water_chip: 1 },
          setFlags: { v13_resolution_done: true, v13_residents_migrated: true },
          incrementCounters: { vault_openness: 2 },
          xpDelta: 300,
        },
      },
      {
        id: 'v13_solution_hand_over_ncr',
        text: '[WYJŚCIE 4: PROTEKTORAT NCR] Przekaż Kryptę 13 pod opiekę inżynierów NCR w zamian za rurociąg dla obu schronów',
        requiredFlag: 'vault13_map_taken',
        forbiddenFlag: 'v13_resolution_done',
        nextSceneId: 'vault_13_victory',
        effect: {
          addItems: { ncr_annexation_writ: 1, water_chip: 1 },
          setFlags: { v13_resolution_done: true, v13_ncr_annexed: true },
          incrementCounters: { vault_openness: 1 },
          xpDelta: 300,
        },
      },
      {
        id: 'v13_solution_take_by_force',
        text: '[WYJŚCIE 1: SIŁA] Odepchnij mieszkańców i wejdź do Laboratorium, by zabrać Water Chip siłą (Skazujesz Kryptę 13 na zagładę)',
        forbiddenFlag: 'v13_resolution_done',
        nextSceneId: 'vault_13_lab',
        effect: {
          setFlags: { v13_resolution_done: true, v13_doomed_by_force: true },
          incrementCounters: { vault_openness: -2 },
        },
      },
      {
        id: 'back_v13_corridors_from_sanctuary',
        text: 'Cofnij się do Korytarzy Technicznych',
        nextSceneId: 'vault_13_corridors',
      },
    ],
  },
  vault_13_lab: {
    id: 'vault_13_lab',
    title: 'Krypta 13 — Laboratorium Hydroprocesorów (Sektor Centralny)',
    locationId: 'vault_13',
    description:
      'W sterylnej komorze w szklanej gablocie lśni nienaruszony Hydroprocesor (Water Chip z Krypty 13)! Drogę do modułu blokuje jednak potężny, trójkołowy Prototypowy Sentry Bot Zagłady — robot obronny najwyższej klasy militarnej z podwójnym minigunem i wyrzutnią rakiet!',
    choices: [
      {
        id: 'peaceful_sentry_talisman',
        text: '[TALIZMAN Z ARROYO] Unieś Talizman Wybrańca — Nadzorca Lazarus zdalnie wyłącza Sentry Bota dla „Potomka Pradawnych”!',
        requiredItem: 'arroyo_talisman',
        nextSceneId: 'vault_13_victory',
        effect: {
          addItems: { water_chip: 1 },
          setFlags: { v13_talisman_bypass: true },
          xpDelta: 300,
        },
      },
      {
        id: 'hack_sentry_overload',
        text: '[INT 8] Przeciąż zasilanie reaktora Sentry Bota przez konsolę serwisową przed walką',
        skillCheck: { attribute: 'INT', difficulty: 8 },
        nextSceneId: 'vault_13_victory',
        failSceneId: 'vault_13_victory',
        effect: {
          xpDelta: 150,
          startCombat: {
            enemyId: 'annihilator_sentry_weakened',
            victorySceneId: 'vault_13_victory',
          },
        },
        failEffect: {
          startCombat: {
            enemyId: 'annihilator_sentry',
            victorySceneId: 'vault_13_victory',
          },
        },
      },
      {
        id: 'fight_sentry_direct',
        text: 'Zaatakuj Prototypowego Sentry Bota Zagłady bez przygotowania!',
        nextSceneId: 'vault_13_victory',
        effect: {
          startCombat: {
            enemyId: 'annihilator_sentry',
            victorySceneId: 'vault_13_victory',
          },
        },
      },
    ],
  },
  vault_13_victory: {
    id: 'vault_13_victory',
    title: 'Krypta 13 — Zwycięstwo i Zdobycie Hydroprocesora',
    locationId: 'vault_13',
    onEnterEffect: {
      setQuestState: { questId: 'ch3_water_chip', status: 'active', stage: 'return_vault_73' },
    },
    description:
      'Los Krypty 13 został rozstrzygnięty, a w Twoich rękach znajduje się ratunek dla Krypty 73! Zegar tyka — pora wracać do domu i stawić czoła prawdzie o tym, kto stał za trzema katastrofami!',
    dynamicDescription: (state) => {
      if (state.flags.v13_chip_copied) {
        return 'Dzięki Matrycy Replikacyjnej Dr Henry’ego w Twoim plecaku spoczywa pełnoprawny duplikat Hydroprocesora, a oryginalny układ nadal tłoczy wodę dla wdzięcznych mieszkańców Krypty 13! Pora wracać do Krypty 73.';
      }
      if (state.flags.v13_residents_migrated) {
        return 'Hydroprocesor został wymontowany, a kolumna mieszkańców i ghuli Nadzorcy Lazarusa wyrusza wraz z Tobą do nowego domu w Krypcie 73!';
      }
      if (state.flags.v13_ncr_annexed) {
        return 'Inżynierowie NCR przejmują opiekę nad Kryptą 13, wręczając Ci układ hydroprocesora i gwarancje budowy wodociągu dla Krypty 73!';
      }
      return 'Wyjmujesz z gabloty jedyny Hydroprocesor (Water Chip z Krypty 13). W tle słychać wycie gasnących systemów podtrzymywania życia Krypty 13... Pora wracać do Krypty 73!';
    },
    choices: [
      {
        id: 'leave_vault13_with_chip',
        text: 'Wyrusz na Pustkowia i wracaj do Krypty 73 [Mapa Świata]',
        nextSceneId: 'vault_13_gate',
        effect: { openWorldMap: true },
      },
    ],
  },
  ending_water_chip_saved: {
    id: 'ending_water_chip_saved',
    title: 'POWRÓT DO KRYPTY 73: Cień Zdrady przy Reaktorze',
    locationId: 'vault_73',
    description:
      'Wkładasz moduł Water Chip w gniazdo głównego systemu uzdatniania wody Krypty 73. Ciche buczenie pomp zmienia się w potężny szum — czysta woda znów wypełnia zbiorniki!\n\nGdy jednak odwracasz się od konsoli, w drzwiach sekcji reaktora staje Nadzorca Krypty 73 z odbezpieczonym karabinem plazmowym. Na jego naramienniku połyskuje ukryty emblemat Enklawy!\n\n„Miałeś zginąć na Pustkowiach, Wędrowcze” — mówi chłodno Nadzorca. — „To ja otworzyłem komorę stabilizatora dla Korga, ja przekazałem Doktorowi Morbusowi mutagen Enklawy i ja przepaliłem stary Water Chip, by zmusić Kryptę 73 do ewakuacji w ręce naszych oddziałów badawczych!”',
    dynamicDescription: (state) => {
      const clues = state.counters?.enclave_clues ?? 0;
      const waterNote = state.flags.water_crisis_casualties
        ? '\n\n[OPÓŹNIENIE WYPRAWY]: Zbyt długie podróże sprawiły, że zapasy wody wyczerpały się przed Twoim powrotem — kilkunastu mieszkańców przypłaciło suszę życiem, zanim uruchomiłeś nowy Hydroprocesor.'
        : '\n\n[CZAS URATOWANY]: Zdążyłeś przed wyczerpaniem rezerw wody (pozostało ' +
          (state.counters?.water_days_left ?? 0) +
          ' dni zapasu) — nikt w Krypcie nie zginął z pragnienia!';
      if (clues >= 2 || state.flags.clue_henry_enclave) {
        return (
          'Wkładasz moduł Water Chip w gniazdo głównego systemu uzdatniania wody Krypty 73. Czysta woda znów wypełnia zbiorniki!' +
          waterNote +
          '\n\nZanim Nadzorca zdąży sięgnąć po broń, rzucasz na konsolę zebrane dowody: logi sabotażu ze Stacji RobCo, zeznania Morbusa o mutagenie FEV-II oraz Dziennik Transmisji Enklawy od Dr Henry’ego! Strażnicy Krypty mierzą do zdemaskowanego Nadzorcy:\n„Więc odkryłeś prawdę o Projekcie Czystość Enklawy... Skoro tak, sam pogrzebię Cię w tym reaktorze!”'
        );
      }
      return (
        'Wkładasz moduł Water Chip w gniazdo systemu uzdatniania wody Krypty 73. Czysta woda znów wypełnia zbiorniki!' +
        waterNote +
        '\n\nNagle na ekranie głównego terminala mruga szyfrowany komunikat nadany z wewnątrz Krypty do bazy Enklawy: „Eksperyment 73 zakończony niepowodzeniem — obiekt przeżył wszystkie trzy sabotaże”. Kiedy wbiegasz do gabinetu Nadzorcy, pomieszczenie jest puste, a tajny właz ewakuacyjny właśnie się zatrzaskuje! Zdrajca uciekł w cień Pustkowi...'
      );
    },
    choices: [
      {
        id: 'confront_traitor_with_evidence',
        text: '[KONFRONTACJA ZE ZDRAJCĄ — DOWODY ENKLAWY] Przedstaw strażnikom zebrane dowody spisku i stocz finałowy pojedynek z Nadzorcą-Agentem!',
        minCounter: { key: 'enclave_clues', value: 2 },
        nextSceneId: 'epilogue_slideshow',
        effect: {
          setFlags: { traitor_exposed: true, traitor_defeated: true },
          setQuestState: { questId: 'side_enclave_conspiracy', status: 'completed', stage: 'completed' },
          startCombat: {
            enemyId: 'overseer_traitor',
            victorySceneId: 'epilogue_slideshow',
          },
        },
      },
      {
        id: 'persuade_traitor_surrender',
        text: '[CHA 8 + DOWODY ENKLAWY] Złam morale zdemaskowanego Nadzorcy i zmuś go do poddania się bez rozlewu krwi (+300 XP)',
        minCounter: { key: 'enclave_clues', value: 2 },
        skillCheck: { attribute: 'CHA', difficulty: 8 },
        nextSceneId: 'epilogue_slideshow',
        failSceneId: 'ending_water_chip_saved',
        effect: {
          setFlags: { traitor_exposed: true, traitor_arrested: true },
          setQuestState: { questId: 'side_enclave_conspiracy', status: 'completed', stage: 'completed' },
          xpDelta: 300,
        },
        failEffect: {
          setFlags: { traitor_exposed: true, traitor_defeated: true },
          startCombat: {
            enemyId: 'overseer_traitor',
            victorySceneId: 'epilogue_slideshow',
          },
        },
      },
      {
        id: 'proceed_epilogue_cliffhanger',
        text: '[PRZEJDŹ DO KRONIKI EPILOGU] Spojrzyj w przyszłość odmienionych Pustkowi...',
        nextSceneId: 'epilogue_slideshow',
      },
    ],
  },
  epilogue_slideshow: {
    id: 'epilogue_slideshow',
    title: 'KRONIKI PUSTKOWI — WIELOWARIANTOWY EPILOG SAGI KRYPTY 73',
    locationId: 'vault_73',
    isEnding: true,
    description: 'Wojna. Wojna nigdy się nie zmienia... ale Twoje czyny na zawsze odmieniły oblicze Pustkowi.',
    dynamicDescription: (state) => {
      const slides: string[] = [
        '=== KRONIKI PUSTKOWI: EPILOG TWOJEJ SAGI ===',
      ];

      // 1. Los Krypty 73 (Otwarci vs Izolacjoniści + Woda)
      const openness = state.counters?.vault_openness ?? 0;
      const waterSuffix = state.flags.water_crisis_casualties
        ? ' Choć spóźniona dostawa wody pochłonęła bolesne ofiary podczas wielkiej suszy, pozostali przy życiu mieszkańcy odbudowali społeczność.'
        : ' Dzięki Twojemu pośpiechowi zapasy wody nie wyczerpały się na czas i żaden mieszkaniec nie zginął z pragnienia.';
      if (openness >= 2) {
        slides.push(
          '• KRYPTA 73 (ZWYCIĘSTWO OBOZU OTWARTYCH): Wrota Krypty 73 stanęły otworem. Schron przekształcił się w tętniącą życiem stolicę technologiczną dzielącą się czystą wodą i medycyną z całym regionem.' +
            waterSuffix
        );
      } else if (openness <= -1) {
        slides.push(
          '• KRYPTA 73 (TRIUMF IZOLACJONISTÓW): Zgodnie z wolą Izolacjonistów pancerne wrota Krypty 73 zostały zaspawane. Mieszkańcy przetrwali w bezpiecznej samowystarczalności, odcinając się na zawsze od chaosu powierzchni.' +
            waterSuffix
        );
      } else {
        slides.push(
          '• KRYPTA 73 (ZŁOTY ŚRODEK): Krypta 73 zachowała ostrożną autonomię, wysyłając uzbrojone karawany handlowe na Pustkowia, lecz strzegąc tajemnic swojego reaktora.' +
            waterSuffix
        );
      }

      // 2. Rdzawy Krater i Dzieci Rdzy (Korg vs Żmija)
      const craterPart = state.flags.crater_alliance_formed
        ? 'Dzięki sojuszowi energetycznemu Rdzawy Krater rozrósł się w ufortyfikowane Nowe Rdzawe Miasto. '
        : 'Osada Rdzawy Krater pozostała niewielkim, niezależnym posterunkiem złomiarzy. ';
      const korgPart = state.flags.korg_spared
        ? 'Oszczędzony Watażka Korg dotrzymał przysięgi — jego Nowa Osada Kuźnicza stała się kuźnią pancerzy i wiernym sojusznikiem wędrowców.'
        : 'Po śmierci Korga władzę nad niedobitkami Dzieci Rdzy przejęła Mściwa Żmija, prowadząc fanatyków do krwawych rajdów odwetowych, które musiałeś tłumić ogniem.';
      slides.push(`• RDZAWY KRATER I DZIECI RDZY: ${craterPart}${korgPart}`);

      // 3. New Reno i Doktor Morbus
      let renoRuler = 'Szeryf Vance oraz lekarze ze Szpitala Św. Atomu zaprowadzili w New Reno rządy prawa i bezpieczny handel.';
      if (state.flags.reno_ruled_by_vinnie || (state.flags.jackals_allied && !state.flags.reno_ruled_by_sheriff && !state.flags.reno_ruled_by_salvatore)) {
        renoRuler = 'Wściekły Vinnie i Żelazne Szakale przejęli kontrolę nad Aleją Neonów, rządząc miastem twardą ręką najemników.';
      } else if (state.flags.reno_ruled_by_salvatore) {
        renoRuler = 'Syntykat Dona Salvatore z Kasyna „Złoty Atom” zmonopolizował handel oczyszczoną wodą, zamieniając New Reno w oligarchię kasyn.';
      }
      const morbusPart = state.flags.morbus_spared
        ? 'Oszczędzony Doktor Morbus pod nadzorem medyków pomógł stworzyć odtrutki na mutacje i ujawnił siatkę dostawców Enklawy.'
        : 'Po egzekucji Doktora Morbusa jego podziemne laboratorium zostało zalane betonem.';
      slides.push(`• METROPOLIA NEW RENO: ${renoRuler} ${morbusPart}`);

      // 4. Arroyo, NCR i Krypta 13
      let v13Text = 'W Krypcie 13 zabranie jedynego Hydroprocesora siłą skazało społeczność Nadzorcy Lazarusa na powolną śmierć w ciemnościach.';
      if (state.flags.v13_chip_copied) {
        v13Text = 'Dzięki Matrycy Replikacyjnej Dr Henry’ego skopiowałeś Water Chip — mieszkańcy i ghule z Krypty 13 ocaleli, zawierając braterski pakt z Kryptą 73!';
      } else if (state.flags.v13_residents_migrated) {
        v13Text = 'Ewakuowani mieszkańcy i ghule z Krypty 13 znaleźli nowy, bezpieczny dom w murach Krypty 73, łącząc wiedzę obu schronów.';
      } else if (state.flags.v13_ncr_annexed) {
        v13Text = 'Pod protektoratem Nowej Republiki Kalifornii (NCR) zbudowano wielki rurociąg łączący Kryptę 13, NCR i Kryptę 73.';
      }
      slides.push(`• KRYPTA 13, ARROYO I NCR: Plemię Arroyo rozkwitło na oczyszczonych pastwiskach. ${v13Text}`);

      // 5. Spisek Enklawy i Zdrajca w Krypcie 73
      if (state.flags.traitor_exposed) {
        slides.push(
          '• FINAŁ SPISKU ENKLAWY (PEŁNE ZWYCIĘSTWO): Dzięki zebranym dowodom zdemaskowałeś i powstrzymałeś Nadzorcę-Zdrajcę działającego na rzecz Enklawy. Krypta 73 wybrała Cię na nowego, prawowitego Nadzorcę!'
        );
      } else {
        slides.push(
          '• CIEŃ NA HORYZONCIE (CLIFFHANGER): Choć ocaliłeś Kryptę 73, tajemniczy mocodawca z Enklawy, który sabotował reaktor, dostarczył mutagen Morbusowi i uszkodził Water Chip, zdołał zatrzeć ślady i zniknąć na Pustkowiach. Pewnego dnia wróci...'
        );
      }

      slides.push('\nGRATULACJE — UKOŃCZYŁEŚ CAŁĄ SAGĘ KRYPTY 73!');
      return slides.join('\n\n');
    },
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
