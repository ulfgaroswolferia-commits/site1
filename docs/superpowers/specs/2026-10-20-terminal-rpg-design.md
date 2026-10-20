# Specyfikacja Projektowa: „Krypta 73: Popioły Nowego Świtu” (Terminal Text RPG)

**Data:** 2026-10-20  
**Środowisko docelowe:** Terminal VS Code (Node.js v24+, TypeScript, Ink / React TUI)  
**Klimat:** Post-apokaliptyczny retro-futuryzm inspirowany serią *Fallout* (interfejs Pip-Boy 3000 / Terminal RobCo)

---

## 1. Cel projektu i założenia

Celem projektu jest stworzenie kompletnej, narracyjnej gry RPG uruchamianej bezpośrednio w zintegrowanym terminalu VS Code. Gra łączy klasyczną rozgrywkę tekstową (drzewa dialogowe, testy umiejętności, wybory moralne) z pełnoprawnymi systemami RPG: kartą postaci opartą na 6 atrybutach (S.P.E.C.I.A.L.), turowym systemem walki z celowaniem w części ciała (V.A.T.S.), mechaniką promieniowania (RAD), ekwipunkiem, handlem za Kapsle, dziennikiem zadań (Quest Log), podróżami po Pustkowiach z losowymi spotkaniami oraz systemem zapisu/odczytu stanu gry.

---

## 2. Architektura Techniczna

Projekt wykorzystuje **TypeScript** na platformie **Node.js** oraz bibliotekę **Ink** (`ink` + `react`), która renderuje komponenty w terminalu przy użyciu układu Flexbox bez migotania ekranu.

### Podział na warstwy (`src/`)

```text
src/
├── index.tsx                 # Punkt wejścia aplikacji (uruchomienie Ink)
├── types/
│   └── game.ts               # Wszystkie interfejsy domenowe (GameState, Character, Item, Enemy, Scene, Quest)
├── engine/                   # Czysta logika domenowa (zero zależności od React/Ink, 100% testowalna w Vitest)
│   ├── GameStateManager.ts   # Zarządzanie stanem gry, modyfikatory HP/RAD/XP/Kapsli, flagi fabularne
│   ├── CharacterSystem.ts    # Obliczanie statystyk pochodnych, testy umiejętności (rzuty k20), awans na poziom i Perki
│   ├── DialogueEngine.ts     # Ewaluacja warunków dialogowych, wykonywanie efektów wyboru i testów atrybutów
│   ├── CombatEngine.ts       # Turowa walka, punkty akcji (AP), system celowania V.A.T.S., AI przeciwnika
│   ├── InventorySystem.ts    # Zakładanie broni/pancerza, używanie medykamentów, kalkulacja cen u kupców
│   ├── WastelandTravel.ts    # Podróże między lokacjami i losowanie spotkań na Pustkowiach
│   └── SaveManager.ts        # Zapis i odczyt pliku JSON (saves/savegame.json) z walidacją integralności
├── content/                  # Deklaratywna baza danych świata gry
│   ├── items.ts              # Bronie, pancerze, medykamenty (Stimpak, AntyRad), przedmioty fabularne
│   ├── perks.ts              # Lista profitów (Perków) odblokowywanych przy awansie
│   ├── enemies.ts            # Szablony wrogów (Ghule, Bandyci, Skorpiony, Roboty RobCo, Boss)
│   ├── quests.ts             # Definicje zadań głównych i pobocznych oraz ich etapów
│   ├── encounters.ts         # Tabela losowych spotkań na Pustkowiach
│   └── scenes.ts             # Wszystkie lokacje, opisy, drzewa dialogowe i interakcje w świecie
└── ui/                       # Komponenty interfejsu Pip-Boy 3000 (Ink)
    ├── App.tsx               # Główny router widoków i obsługa globalnych skrótów klawiszowych
    ├── theme.ts              # Paleta kolorów Pip-Boy (fosforowa zieleń, bursztyn, czerwień alarmowa)
    └── components/
        ├── HeaderStatus.tsx  # Górny pasek: HP, RAD, Kapsle, Poziom/XP, aktualna lokacja
        ├── MainMenu.tsx      # Ekran tytułowy (Nowa Gra, Wczytaj Grę, Wyjście)
        ├── CharacterCreator.tsx # Kreator postaci (Imię + rozdanie punktów S.P.E.C.I.A.L.)
        ├── ExplorationView.tsx  # Widok fabularny: opis sceny, historia zdarzeń, lista opcji wyboru
        ├── CombatView.tsx    # Widok walki turowej i menu celowania V.A.T.S.
        ├── MerchantView.tsx  # Ekran handlu (Kup / Sprzedaj)
        ├── WorldMapView.tsx  # Wybór lokacji docelowej na mapie Pustkowi
        └── PipBoyOverlay.tsx # Modal zakładek: [S] Statystyki/Perki, [I] Ekwipunek, [Q] Dziennik Zadań
```

---

## 3. Interfejs Użytkownika i Sterowanie w Terminalu

1. **Układ ekranu (Stały Terminal Pip-Boy 3000):**
   - **Górny HUD (`HeaderStatus`):** Pasek zdrowia (`HP / Efektywne Max HP`), poziom skażenia (`RAD %`), liczba `Kapsli`, `Poziom (XP / próg)` oraz nazwa bieżącej lokacji.
   - **Główny ekran (`Main Panel`):** Wyświetla aktualny widok (narracja i dialogi, starcie bojowe, mapa świata lub otwarta zakładka Pip-Boya).
   - **Dolny panel akcji i stopka skrótów:** Interaktywna lista opcji sterowana strzałkami `↑/↓` + `Enter` lub klawiszami numerycznymi `1–9`, oraz pasek skrótów globalnych:
     - `[S]` — Karta Postaci i Perki
     - `[I]` — Ekwipunek i użycie przedmiotów
     - `[Q]` — Dziennik Zadań (Quest Log)
     - `[M]` — Mapa Pustkowi (gdy postać jest w strefie wyjścia z lokacji)
     - `[F5]` / `[Z]` — Szybki zapis gry
     - `[ESC]` — Powrót / Menu główne

2. **Odporność UI:**
   - Jeśli terminal VS Code ma mniej niż `80 kolumn x 22 wiersze`, wyświetlany jest czytelny komunikat w stylu RobCo proszący o powiększenie panelu terminala.

---

## 4. Mechaniki RPG (Szczegółowe Reguły)

### 4.1 System Atrybutów (S.P.E.C.I.A.L.)
Każdy z 6 atrybutów przyjmuje wartość od `1` do `10`. Na starcie wszystkie wynoszą `5`, a gracz ma `5` dodatkowych punktów do rozdysponowania (może też obniżyć wybrany atrybut do min. `3`, aby zyskać punkty na inne):

| Atrybut | Skrót | Wpływ na mechanikę |
| :--- | :--- | :--- |
| **Siła** | `STR` | Bonus do obrażeń wręcz (`+STR`), testy siłowe (wyważanie, zastraszanie). |
| **Percepcja** | `PER` | Bonus do bazowej szansy trafienia w walce (`+3% za punkt`), testy spostrzegawczości. |
| **Wytrzymałość** | `END` | Bazowe Max HP = `70 + END * 10`. Zmniejsza otrzymywane skażenie RAD o `END * 3%`. |
| **Charyzma** | `CHA` | Testy perswazji w dialogach, modyfikator cen kupna/sprzedaży (`±3% za punkt`). |
| **Inteligencja** | `INT` | Hakowanie terminali RobCo, nauka/medycyna (Stimpaki leczą `+INT * 3` HP więcej), bonus XP (`+INT * 3%`). |
| **Zręczność** | `AGI` | Maksymalne Punkty Akcji w walce (`AP = 5 + Math.floor(AGI / 2)`), szansa na ucieczkę i unik (`+AGI * 2%`). |

### 4.2 Testy umiejętności (Skill Checks)
- Każda opcja dialogowa lub interakcja z testem atrybutu ma zdefiniowany `attribute` (np. `INT`) oraz `difficulty` (wartość `1–10`).
- **Wzór na szansę sukcesu (%):**
  `clamp(50 + (wartość_atrybutu - trudność) * 15 + bonus_z_perków, 10, 95)`
- Jeśli atrybut gracza jest równy lub wyższy niż `difficulty + 2`, test może zakończyć się automatycznym sukcesem (100%).
- Wynik rzutu (`1–100 <= szansa`) decyduje o przejściu do węzła sukcesu (`nextSceneId` + nagroda XP) lub porażki (`failSceneId` + ewentualna kara HP/alarm).

### 4.3 Walka Turowa i System V.A.T.S.
Walka toczy się w turach. W swojej turze gracz dysponuje pulą **Punktów Akcji (AP)**:
1. **Standardowy Atak (Koszt: 3 AP):** Atak aktualnie założoną bronią w korpus (100% modyfikatora celności broni, obrażenia `1.0x`).
2. **Celowanie V.A.T.S. (Koszt: 4 AP):**
   - **Głowa:** Celność `-25%`, Obrażenia `1.75x`, `25%` szansy na ogłuszenie wroga (wróg traci następną turę).
   - **Tors:** Celność `+0%`, Obrażenia `1.15x`.
   - **Kończyny / Uzbrojenie:** Celność `-15%`, Obrażenia `0.85x`, nakłada efekt *Osłabienie* (wróg zadaje `-25%` obrażeń przez 2 tury).
3. **Użycie przedmiotu (Koszt: 2 AP):** Użycie Stimpaka, AntyRadu, Bojowego Stymulanta (+AP/Obrażenia) lub rzut Granatem Pulsacyjnym/Odłamkowym.
4. **Zakończenie tury / Pozycja obronna:** Niewykorzystane AP dają bonus do obrony (`+5% uniku za każdy zachowany AP`) do następnej tury gracza.
5. **Ucieczka (Koszt: 3 AP):** Test Zręczności (bazowo `40% + AGI * 5%`, niedostępne w walce z bossem finałowym).

### 4.4 Promieniowanie (RAD)
- Skażenie `RAD` rośnie w strefach napromieniowanych lub od ataków zmutowanych stworzeń (`0` do `100`).
- Każdy punkt `RAD` obniża **Efektywne Maksymalne HP** o `0.5%` (np. przy `40 RAD` i `120 Max HP`, efektywne maksimum zdrowia wynosi `96 HP`).
- Redukcja RAD następuje poprzez użycie *AntyRadu* (`-35 RAD`) lub wizytę u medyka w osadzie.

### 4.5 Rozwój Postaci (XP, Poziomy, Perki)
- Progi XP na kolejne poziomy: Poziom 2 = `100 XP`, Poziom 3 = `250 XP`, Poziom 4 = `450 XP`, Poziom 5 = `700 XP`.
- Przy awansie na nowy poziom gracz otrzymuje:
  - `+10` do bazowego Max HP i pełne wyleczenie z ran,
  - `+1` punkt do wybranego atrybutu S.P.E.C.I.A.L.,
  - Wybór **1 Perka** z listy (np. *Snajper V.A.T.S.* (+15% celności w głowę), *Haker RobCo* (+20% do testów INT i hakowania), *Twardziel Pustkowi* (+3 pancerza i +20 Max HP), *Złoty Język* (+20% do testów CHA i 15% lepsze ceny), *Ołowiany Brzuch* (50% mniej otrzymywanego RAD)).

---

## 5. Świat Gry i Kampania: „Krypta 73: Popioły Nowego Świtu”

Fabuła obejmuje 4 rozbudowane lokacje połączone mapą Pustkowi oraz zestaw zadań głównych i pobocznych:

1. **Krypta 73 (Start):**
   - Rdzeń uzdatniania wody i stabilizator reaktora uległy awarii. Nadzorca wysyła gracza na powierzchnię w celu odzyskania **Modułu Stabilizatora G.E.C.K.-Lite**.
   - Interakcje: rozmowa z Nadzorcą, kwatermistrzem (pierwszy ekwipunek), przeprogramowanie uszkodzonego robota przy wrotach, wyjście na powierzchnię.
2. **Złomowisko „Rdzawy Krater” (Osada i Hub):**
   - Miasteczko zbudowane wokół wraku przedwojennego frachtowca.
   - Interakcje: Kupiec Szrot (handel bronią, pancerzami i Stimpakami), Medyk Doktor Iga (leczenie i usuwanie RAD, zadanie poboczne na odzyskanie skradzionych medykamentów), Bar „Pod Opadem” (informacje o Stacji RobCo i kultystach Dzieci Rdzy).
3. **Stacja Przekaźnikowa RobCo (Kompleks technologiczny):**
   - Przedwojenny bunkier badawczy opanowany przez zdziczałe ghule i zablokowane systemy obronne.
   - Interakcje: hakowanie terminali bezpieczeństwa, eksploracja laboratorium, walka ze Strażnikiem RobCo MK II, odkrycie, że Moduł Stabilizatora został zabrany przez fanatyczny gang „Dzieci Rdzy”.
4. **Twierdza Dzieci Rdzy (Finał):**
   - Ufortyfikowana rafineria rządzona przez Watażkę Korga.
   - Trzy ścieżki rozwiązania finału:
     - **Bojowa:** Szturm i walka z Korgiem oraz jego gwardią.
     - **Technologiczna (`INT`):** Przejęcie kontroli nad wieżyczkami obronnymi rafinerii przez terminal główny.
     - **Dyplomatyczna (`CHA`):** Przekonanie Korga, że przeciążony stabilizator wysadzi jego rafinerię, jeśli nie odda go dobrowolnie.
   - Powrót do Krypty 73 i podsumowanie zakończenia zależne od podjętych decyzji.

### Losowe Spotkania w Podróży (`WastelandTravel`)
Podczas zmiany lokacji na Mapie Świata istnieje 65% szansy na zdarzenie losowe:
- *Zasadzka Bandytów z Pustkowi* (walka lub okup/zastraszenie `STR`),
- *Stado Zdziczałych Ghuli* (walka i ryzyko skażenia `RAD`),
- *Wędrowny Karawaniarz* (okazja do handlu i plotek),
- *Opuszczona Kapsuła Medyczna RobCo* (test `INT`/`PER` na zdobycie łupów),
- *Burza Radioaktywna* (test `END` lub utrata HP/wzrost RAD).

---

## 6. Zapis Gry, Obsługa Błędów i Testowanie

- **Zapis / Odczyt (`SaveManager`):**
  - Zapis do pliku `saves/savegame.json` (tworzonego automatycznie przy szybkim zapisie `[F5]` oraz przy zmianie lokacji jako autozapis).
  - Walidacja struktury JSON przy wczytywaniu — w razie braku lub uszkodzenia pliku gra wyświetla komunikat w interfejsie bez przerywania działania procesu.
- **Testy Jednostkowe (`Vitest`):**
  - Testy kalkulacji statystyk pochodnych i testów umiejętności (`CharacterSystem.test.ts`),
  - Testy przebiegu walki turowej, kosztów AP, celowania V.A.T.S. i promieniowania (`CombatEngine.test.ts`),
  - Testy ekwipunku, zakładania przedmiotów i cen handlowych (`InventorySystem.test.ts`),
  - Testy integralności danych fabularnych — automatyczny test sprawdzający, czy każde `nextSceneId`, `itemId`, `enemyId` i `questId` w `src/content/` wskazuje na istniejący obiekt (`ContentIntegrity.test.ts`),
  - Testy zapisu i odczytu stanu gry (`SaveManager.test.ts`).
