# Krypta 73: Popioły Nowego Świtu

Postapokaliptyczna tekstowa gra RPG działająca bezpośrednio w terminalu (VS Code, PhpStorm, PowerShell, CMD) z retro-futurystycznym interfejsem wzorowanym na **Pip-Boy 3000**.

## O projekcie

**Krypta 73: Popioły Nowego Świtu** to turowa gra fabularna osadzona na radioaktywnych pustkowiach. Oferuje:
- **System rozwoju postaci S.P.E.C.I.A.L.** oraz unikalne profity (Perki) odblokowywane wraz z kolejnymi poziomami doświadczenia.
- **Rozbudowaną nieliniową fabułę i dialogi** z testami statystyk, reputacją i konsekwencjami wyborów.
- **Turowy system walki taktycznej** z celowaniem w poszczególne części ciała (system V.A.T.S.), punktami akcji (AP), obrażeniami krytycznymi i różnorodnym arsenałem.
- **Eksplorację Pustkowi**, losowe spotkania, handel wymienny (Kapsle), zarządzanie ekwipunkiem, napromieniowaniem (RAD) oraz dziennik zadań.
- **Interfejs Pip-Boy 3000** dostosowany do wygodnej rozgrywki w dolnym panelu terminala IDE (np. VS Code lub PhpStorm).

---

## 1. Wymagania wstępne

Do uruchomienia gry wymagane jest środowisko **Node.js** (wersja 18 lub nowsza, zalecana wersja 20/22/24 LTS).

Aby sprawdzić, czy masz już zainstalowanego Node.js, wpisz w terminalu:

```bash
node -v
```

Jeśli polecenie nie jest rozpoznawane, pobierz i zainstaluj Node.js ze strony [https://nodejs.org/](https://nodejs.org/).

---

## 2. Instalacja

### Opcja A: Instalacja bezpośrednio z npm (zalecana)

Zainstaluj grę globalnie z rejestru npm:

```bash
npm install -g krypta-73-rpg
```

Po zakończeniu instalacji w systemie zostanie zarejestrowana globalna komenda `krypta73`.

Możesz również uruchomić grę bez globalnej instalacji za pomocą `npx`:

```bash
npx krypta-73-rpg
```

### Opcja B: Instalacja z pliku paczki (`.tgz`)

1. Pobierz plik `krypta-73-rpg-1.0.6.tgz` do dowolnego folderu na dysku.
2. Otwórz terminal (w VS Code, PhpStorm, PowerShell lub CMD) w folderze z plikiem `.tgz`.
3. Zainstaluj grę globalnie poleceniem:

```bash
npm install -g ./krypta-73-rpg-1.0.6.tgz
```

---

## 3. Uruchamianie gry

Otwórz terminal w VS Code (skrót `Ctrl + ~`) lub terminal w PhpStormie (`Alt + F12`) i wpisz:

```bash
krypta73
```

> **Wskazówka:** Zalecany minimalny rozmiar panelu terminala to **72 kolumny szerokości** i **18 wierszy wysokości** (w razie potrzeby przeciągnij górną krawędź panelu terminala nieco wyżej).

---

## 4. Sterowanie w grze (Interfejs Pip-Boy 3000)

| Klawisz | Akcja |
| :--- | :--- |
| **Strzałki `[↑ / ↓]` + `[Enter]`** lub **`[1 - 9]`** | Wybór opcji dialogowej / akcji |
| **Strzałki `[← / →]`** | Rozdawanie punktów S.P.E.C.I.A.L. / Kupno–Sprzedaż |
| **`[S]`** | Karta Postaci S.P.E.C.I.A.L., Perki oraz Awans na nowy poziom |
| **`[I]`** | Ekwipunek (zakładanie broni/pancerza, używanie Stimpaków i AntyRadu) |
| **`[Q]`** | Dziennik Zadań (Quest Log) / Wyjście z okna handlu |
| **`[M]`** | Mapa Pustkowi (podróż między odkrytymi lokacjami) |
| **`[Z]`** lub **`[F5]`** | Szybki zapis gry |
| **`[ESC]`** | Zamknięcie otwartej zakładki Pip-Boya / Anulowanie |

> **Uwaga dla użytkowników PhpStorma:**
> Domyślnie klawisz `ESC` w PhpStormie przenosi fokus z terminala do edytora kodu. Zakładki Pip-Boya możesz zamykać ponownym naciśnięciem klawisza danej zakładki (`[S]`, `[I]`, `[Q]`, `[M]`), albo wyłączyć tę opcję w PhpStormie: *Settings → Tools → Terminal → odznacz "Move focus to editor on Escape"*.

---

## 5. Aktualizacja i odinstalowanie

Aby zaktualizować grę do najnowszej wersji z npm:

```bash
npm install -g krypta-73-rpg@latest
```

Lub przy instalacji z nowego pliku `.tgz`:

```bash
npm install -g --force ./krypta-73-rpg-1.0.6.tgz
```

Zapisy gry (plik `saves/savegame.json`) pozostają w 100% bezpieczne i zachowane.

Aby całkowicie odinstalować grę z systemu, wpisz:

```bash
npm uninstall -g krypta-73-rpg
```
