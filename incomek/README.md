# Incom Group — Prezentacja Szkoleniowa & Animacja „Pat i Mat” (Openspace)

Projekt zawiera gotową animację poklatkową **Pat i Mat („Sąsiedzi — A je to!”)** w barwach **Incom Group** na tle firmowego openspace'u oraz konspekt zadania na poniedziałek: **kompleksowe odświeżenie prezentacji szkoleniowej z pliku PowerPoint (`.pptx`)**.

---

## 1. Zawartość projektu (Stan obecny)

### Katalog `/dist` (Gotowa paczka na serwer WWW)
- `index.html` — interaktywny odtwarzacz prezentacyjny HTML5 (tryb animacji poklatkowej Live, podgląd GIF, siatka 4 klatek, pełny ekran oraz eksport wideo `.webm` jednym kliknięciem).
- `incom-pat-mat-animacja.gif` — gotowa, ostra animacja poklatkowa GIF z plakietką `• Szkolenie zaraz się zacznie...`.
- `incom-pat-mat-frame1.jpg` — **Klatka 1**: Pat i Mat majsterkują przy laptopie (Pat pisze na klawiaturze, Mat zagląda i doradza palcem).
- `incom-pat-mat-frame2.jpg` — **Klatka 2**: Pierwszy test udany — Pat z ręką w kieszeni, Mat pokazuje kciuk w górę (klatka referencyjna Master).
- `incom-pat-mat-frame3.jpg` — **Klatka 3**: Kultowy gest *„A je to!”* — obaj patrzą prosto w kamerę i zginają ręce w łokciach pod kątem 90° z zaciśniętymi pięściami przy piersi.
- `incom-pat-mat-frame4.jpg` — **Klatka 4**: Finał w stylu odcinków *Pat i Mat* — Pat z dumą obraca laptopa ekranem do widza (plansza startowa ze znakiem `✓`) i pokazuje kciuk w górę, a Mat triumfalnie unosi oba kciuki w górę.
- `incom-pat-mat-characters.jpg` — postacie Pata i Mata w barwach Incom Group na czystym białym tle (do wykorzystania na slajdach).
- `incom-openspace.webp` — czyste zdjęcie referencyjne przestrzeni biurowej Incom Group.
- `incom-pat-mat-paczka.zip` — kompletne archiwum ZIP ze wszystkimi powyższymi plikami.

### Katalog `/docs` (Materiały źródłowe i dokumentacja)
- Kopie klatek, animacji oraz pliku `incom-pat-mat-prezentacja.html`.
- `KONSPEKT-PREZENTACJI.md` — szczegółowa instrukcja odświeżenia prezentacji szkoleniowej `.pptx`.

---

## 2. Zadanie na poniedziałek: Odświeżenie prezentacji szkoleniowej (`.pptx`)

### Krok 1: Przekazanie pliku źródłowego
1. Wrzuć źródłowy plik PowerPoint (`.pptx`) do katalogu `C:\laragon\www\incomek\docs\` (lub bezpośrednio do `C:\laragon\www\incomek\`).
2. Wskaż plik w rozmowie z asystentem AI i wybierz preferowany wariant wyjściowy (**Wariant A**, **Wariant B** lub **oba naraz**).

### Krok 2: Automatyczna ekstrakcja treści z `.pptx`
Asystent automatycznie:
- Rozpakuje strukturę OpenXML pliku `.pptx` (lub użyje `python-pptx` / PowerShell),
- Wyciągnie kompletną treść wszystkich slajdów, strukturę nagłówków, notatki prelegenta, tabele, dane wykresów oraz osadzone obrazy/diagramy,
- Zaproponuje uporządkowany podział slajdów i nowoczesny układ wizualny (zastąpienie „ścian tekstu” czytelnymi kartami, schematami, tabelami i punktami z ikonami).

### Krok 3: Warianty wykonania prezentacji

#### Wariant A: Interaktywna prezentacja Web / HTML5 (z eksportem do PDF)
- Nowoczesna aplikacja prezentacyjna 16:9 działająca w każdej przeglądarce (lokalnie lub na serwerze WWW).
- **Funkcje**: nawigacja strzałkami / spacją, spis slajdów (drawer), pasek postępu, tryb pełnoekranowy (`Fullscreen`), widok notatek prelegenta.
- **Warstwa wizualna**: płynne animacje wejścia elementów, interaktywne diagramy/wykresy, opcjonalne mikro-quizy podsumowujące działy oraz wbudowana animacja *Pat & Mat* na slajdzie powitalnym (`Szkolenie zaraz się zacznie...`) i slajdach sekcyjnych.
- **Eksport do PDF**: dedykowany arkusz stylów `@media print` pozwalający zapisać całą prezentację do czystego pliku PDF 16:9 jednym kliknięciem.

#### Wariant B: Natywny, edytowalny plik PowerPoint (`.pptx`)
- Wygenerowany programowo plik `.pptx` w formacie panoramicznym **16:9**.
- **System identyfikacji wizualnej Incom Group**:
  - Granat główny (`--incom-navy`): `#071930`
  - Niebieski korporacyjny (`--incom-blue`): `#0056A4`
  - Cyjan akcentowy (`--incom-cyan`): `#00A0DF`
  - Powierzchnie kart (`--surface`): `#0D223F` / jasne karty kontrastowe `#F8FAFC`
  - Font: `Inter` / `Segoe UI`
- **Zawartość**: przebudowane, czytelne układy slajdów, osadzona animacja `incom-pat-mat-animacja.gif` na slajdzie tytułowym oraz dedykowane ilustracje z Patem i Matem ilustrujące wybrane zagadnienia szkoleniowe.
- Pełna edytowalność tekstów, kształtów i wykresów w programie Microsoft PowerPoint.

---

## 3. Wytyczne techniczne dla AI (Zachowanie spójności postaci Pat & Mat)
Przy generowaniu nowych scenek lub ilustracji z udziałem Pata i Mata na potrzeby kolejnych slajdów szkolenia:
1. **Zawsze przekazuj jako obraz referencyjny** plik `dist/incom-pat-mat-frame2.jpg` (oraz `dist/incom-openspace.webp` lub `dist/incom-pat-mat-characters.jpg`).
2. **Wygląd postaci**:
   - **Pat (po lewej)**: ciemnogranatowy beret z antenką, kobaltowo-niebieski dziergany sweter z białym geometrycznym logo na piersi, ciemnogranatowe spodnie, brązowe buty.
   - **Mat (po prawej)**: niebiesko-biała czapka z pomponem, biało-cyjanowy golf, smycz z identyfikatorem IT, ciemnoszare spodnie, ciemne buty.
3. **Zakaz AI Slop i deformacji**:
   - Nigdy nie generuj otwartych ust z ludzkimi zębami — postacie mają klasyczne, plastelinowe uśmiechy (zamknięta linia ust jak w oryginalnym serialu *Pat i Mat*).
   - Każda postać musi mieć dokładnie 2 ręce i naturalnie połączony kołnierz golfu z szyją.
   - **Zakaz cięcia głów/szyi i rozciągania pikseli skryptami** — postacie w każdej klatce muszą pochodzić z jednego spójnego renderu.
