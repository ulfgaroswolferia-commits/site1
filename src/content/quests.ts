import { QuestDefinition } from '../types/game.js';

export const QUESTS: Record<string, QuestDefinition> = {
  main_stabilizer: {
    id: 'main_stabilizer',
    title: 'Popioły Nowego Świtu (Zadanie Główne)',
    description: 'Reaktor i hydroprocesor Krypty 73 gasną. Odnajdź Moduł Stabilizatora G.E.C.K.-Lite na Pustkowiach.',
    stages: {
      talk_overseer: 'Porozmawiaj z Nadzorcą Krypty 73 i odbierz wytyczne.',
      leave_vault: 'Wydostań się przez wrota Krypty 73 i udaj się do osady „Rdzawy Krater”.',
      search_relay: 'Przeszukaj Stację Przekaźnikową RobCo w poszukiwaniu Modułu Stabilizatora.',
      infiltrate_fortress: 'Odzyskaj Stabilizator z rąk Watażki Korga w Twierdzy Dzieci Rdzy.',
      return_vault: 'Wróć z Modułem Stabilizatora do Krypty 73 i zdecyduj o losie schronu.',
      completed: 'Los Krypty 73 został przypieczętowany.',
    },
  },
  side_meds: {
    id: 'side_meds',
    title: 'Skradzione Panaceum (Zadanie Poboczne)',
    description: 'Doktor Iga z Rdzawego Krateru potrzebuje skradzionej skrzynki z antybiotykami.',
    stages: {
      find_crate: 'Odnajdź Skrzynkę Medyczną w ruinach wokół Stacji Przekaźnikowej RobCo.',
      return_iga: 'Zanieś odzyskaną Skrzynkę Medyczną Doktor Idze w Rdzawym Kraterze.',
      completed: 'Dostarczono leki Doktor Idze i odebrano nagrodę.',
    },
  },
  ch2_new_reno: {
    id: 'ch2_new_reno',
    title: 'Rozdział 2: Zatrute Źródła New Reno (Zadanie Główne)',
    description: 'Nadzorca wysłał Cię do New Reno. Miasto kona przez zatrutą wodę i terror gangu Żelaznych Szakali.',
    stages: {
      travel_reno: 'Wyrusz na Mapę Świata i dotrzyj do metropolii New Reno.',
      investigate_city: 'Porozmawiaj z Szeryfem Vance’em, zbadaj Szpital, Kasyno, Melinę Bandytów lub zejdź do Kanałów.',
      defeat_morbus: 'Pokonaj Doktora Morbusa w głębi Podziemnych Kanałów (samotnie lub z pomocą bandytów/surowicy).',
      return_overseer: 'Wróć z Pieczęcią Oczyszczalni New Reno do Nadzorcy w Krypcie 73.',
      completed: 'New Reno zostało ocalone, a Ty zostałeś mianowany Zastępcą Nadzorcy Krypty 73.',
    },
  },
  side_reno_hospital: {
    id: 'side_reno_hospital',
    title: 'Epidemia w Szpitalu Św. Atomu (Zadanie Poboczne)',
    description: 'Ordynator Lena potrzebuje próbki toksycznego szlamu z kanałów, by stworzyć Surowicę Neutralizującą.',
    stages: {
      collect_sample: 'Pobierz Próbkę Szlamu Mutagenu w pierwszym sektorze Podziemnych Kanałów New Reno.',
      deliver_sample: 'Zanieś Próbkę Szlamu Ordynator Lenie w Szpitalu New Reno.',
      completed: 'Opracowano Surowicę Neutralizującą osłabiającą bossa w kanałach.',
    },
  },
  side_reno_jackals: {
    id: 'side_reno_jackals',
    title: 'Żelazne Szakale z Dzielnicy Magazynów (Zadanie Poboczne)',
    description: 'Gang Wściekłego Vinniego terroryzuje New Reno, ale może też posłużyć jako siła uderzeniowa w kanałach.',
    stages: {
      confront_jackals: 'Odwiedź Melinę Żelaznych Szakali lub zdobądź na nich haki w Kasynie „Złoty Atom”.',
      allied: 'Zawarto sojusz z Żelaznymi Szakalami — pomogą Ci w szturmie na kryjówkę Doktora Morbusa.',
      eliminated: 'Rozbito gang Żelaznych Szakali w walce.',
    },
  },
  ch3_water_chip: {
    id: 'ch3_water_chip',
    title: 'Rozdział 3: Wyścig o Hydroprocesor (Zadanie Główne)',
    description: 'W Krypcie 73 spłonął Water Chip. Zostały 2 miesiące wody. Musisz odnaleźć Kryptę 13 i ocalić schron.',
    stages: {
      travel_arroyo: 'Wyrusz do plemiennej wioski Arroyo, by zdobyć pierwsze wskazówki o Krypcie 13.',
      help_arroyo: 'Ocal stada Brahminów w Arroyo przed bestiami i porozmawiaj ze Starszą Hakunin.',
      travel_ncr: 'Udaj się do stolicy NCR (Nowej Republiki Kalifornii) i spotkaj się z Burmistrzem.',
      gain_ncr_map: 'Zdobądź zaufanie władz NCR i odbierz Przedwojenną Holomapę Krypty 13.',
      infiltrate_vault_13: 'Przedrzyj się przez systemy obronne Krypty 13 i zdobądź Water Chip.',
      return_vault_73: 'Dostarcz Water Chip do reaktora Krypty 73, nim skończą się zapasy wody.',
      completed: 'Krypta 73 ma nieograniczony zapas wody. Zostałeś legendarnym Zbawcą Pustkowi.',
    },
  },
  side_arroyo_pasture: {
    id: 'side_arroyo_pasture',
    title: 'Czystka na Pastwiskach Arroyo (Zadanie Poboczne)',
    description: 'Oczyść skalisty kanion z drapieżników zagrażających przetrwaniu wioski Arroyo.',
    stages: {
      hunt_beasts: 'Pokonaj Zdziczałego Wilka oraz Alfę Radskorpiona na pastwiskach Arroyo.',
      completed: 'Pastwiska są bezpieczne, a plemię Arroyo otacza Cię czcią.',
    },
  },
  side_ncr_crimes: {
    id: 'side_ncr_crimes',
    title: 'Buntownicy na Szlaku NCR (Zadanie Poboczne)',
    description: 'Rozbij konwój dezerterów nękających szlaki handlowe NCR i odzyskaj skaner Dr Henry’ego.',
    stages: {
      attack_convoy: 'Zlikwiduj obóz buntowników na przedmieściach NCR.',
      return_scanner: 'Oddaj odzyskany Skaner Medyczny Dr Henry’emu w Szpitalu NCR.',
      completed: 'Zlikwidowano zagrożenie dla karawan i wsparto medyków NCR.',
    },
  },
  side_crater_expansion: {
    id: 'side_crater_expansion',
    title: 'Rozkwit Rdzawego Miasta (Zadanie Poboczne)',
    description: 'Po sojuszu z Kryptą 73 Rdzawy Krater rozrasta się w miasto. Pomóż utrzymać pokój na szlakach.',
    stages: {
      defend_town: 'Zbadaj sytuację wokół nowej osady Korga lub odeprzyj odwet Mściwej Żmii.',
      completed: 'Bezpieczeństwo Nowego Rdzawego Miasta zostało zapewnione.',
    },
  },
  side_reno_rooftops: {
    id: 'side_reno_rooftops',
    title: 'Kurier z Dachów New Reno (Zadanie Poboczne)',
    description: 'Przemyć cenne leki po gzymsach i dachach ponad blokadami gangów do Szpitala Św. Atomu.',
    stages: {
      run_rooftops: 'Pokonaj trasę po dachach Alei Neonów i zdobądź kontrabandę medyczną.',
      deliver_hospital: 'Dostarcz przemycone leki Ordynator Lenie w Szpitalu.',
      completed: 'Leki dotarły do szpitala ponad głowami bandytów.',
    },
  },
  side_reno_boxing: {
    id: 'side_reno_boxing',
    title: 'Żelazna Pięść New Reno (Zadanie Poboczne)',
    description: 'Stań do walki na podziemnym ringu bokserskim w Kasynie „Złoty Atom”.',
    stages: {
      fight_champion: 'Zmierz się z mistrzem ringu „Miażdżycielem” Malone’em.',
      completed: 'Zdobyto tytuł mistrza ringu New Reno.',
    },
  },
  side_enclave_conspiracy: {
    id: 'side_enclave_conspiracy',
    title: 'Cień Enklawy w Krypcie 73 (Śledztwo)',
    description: 'Trzy katastrofy nie były przypadkiem. Zbierz dowody sabotażu ze Stacji RobCo, od Morbusa i od Dr Henry’ego.',
    stages: {
      gather_clues: 'Szukaj śladów celowego sabotażu reaktora, formuły mutagenu i sygnałów Enklawy.',
      confront_traitor: 'Ujawnij zdrajcę w Krypcie 73 podczas wielkiego finału.',
      completed: 'Spisek Enklawy w Krypcie 73 został całkowicie zdemaskowany.',
    },
  },
};
