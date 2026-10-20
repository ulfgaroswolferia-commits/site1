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
};
