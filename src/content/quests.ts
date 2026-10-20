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
};
