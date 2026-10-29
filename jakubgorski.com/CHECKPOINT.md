# CHECKPOINT – jakubgorski.com (sesja 2026-10-03/04)

## Stan
- `index.html` = jednostronicowe CV (glassmorphism, granat + złoto), jeden plik z inline CSS.
- Plik jest **tylko lokalnie** w `C:\laragon\www\jakubgorski.com\`. Na serwerze `~/www/index.html` jest wersja z ok. 00:05 (sprzed usunięcia Git i zmiany podtytułu z "&").
- **Nic nie zostało zacommitowane** (repo git jest w `C:\laragon\www`, remote `ulf1`; `.gitignore` tam ma lokalne modyfikacje).

## Zawartość strony (aktualna)
- Podtytuł: `E-commerce & IT Specialist` ("&" w Playfair Display italic, jak w projekcie Bielice; font z Google Fonts).
- Chipy: Mensa Member, E-commerce Specialist, AI-assisted Development, IT Specialist, PHP Developer, Sales Manager, Psychologia w biznesie, Neuromarketing, BVB fan, 20+ lat doświadczenia.
- Dev Tools: Claude Code (CLI / AI-assisted coding), Google AI Pro, PHP, JS, NODE, REACT, VS Code, Many others..., Mózg (radioaktywny glow, klasa `.radioactive`). Git i GitHub Copilot usunięte.
- AI & Automatyzacja: Claude Code, Google AI Pro Certificate, Prompt engineering, ...na miarę, Narzędzia AI w cyklu wytwarzania oprogramowania.
- Certyfikaty: Cambridge FCE, Ratownik wodny i górski (WOPR & GOPR), PHP Developer, Google AI Pro Certificate (Coursera), Indie Game Developer (Personal Projects), "Wędkarz, grzybiarz, akrobata...". (Sekcja "Doświadczenie w sprzedaży" usunięta na życzenie użytkownika).
- `<title>`: `Jakub Górski – IT Specialist`.
- Efekty wizualne nieba i pełna responsywność (całkowity redesign):
  - Całkowicie usunięte stare, zamazane kolorowe plamy (żółto-brązowa, niebieska, turkusowa), które brudziły stronę.
  - Tło zastąpione czystym, aksamitnym gradientem głębokiego kosmosu (`#0d2146` -> `#061126` -> `#01040a`).
  - Promienny, fotorealistyczny księżyc (`moon.png`) z wielowarstwową, żywą aureolą świetlną, umieszczony w prawym górnym rogu.
  - Wyraziste światło księżyca (`.moonlight-wash`) oświetlające treść po przekątnej, z luksusowym chłodno-srebrnym odbiciem na krawędziach szkła kart `.glass`.
  - Spokojne, subtelnie pracujące gwiazdy w tle (powolne, kojące cykle 6s–14s, bez agresywnych rozbłysków) oraz okazjonalne, płynne spadające gwiazdy.
  - **Pełna responsywność Mobile (Android & iPhone)**:
    - Przetestowano i zweryfikowano wizualnie na modelach iPhone 14/15 (390×844) oraz Android (360×800).
    - Rozwiązano problem poziomego rozjeżdżania się siatki: karty w kolumnach przechodzą w czytelny układ jednokolumnowy (`1fr`), tagi i chipy elegancko się zawijają, zero uciętych treści.
    - Księżyc na urządzeniach mobilnych automatycznie skaluje się (64px / 56px) i osadza w prawym górnym rogu z dedykowaną poświatą, nie zasłaniając nagłówka ("Jakub Górski" ma zagwarantowany margines bezpieczeństwa).
  - Do wgrania na FTP: zarówno `index.html`, jak i `moon.png` do katalogu `~/www/`.

## Hosting (OVH) – ważne
- Domena `jakubgorski.com` serwuje **`~/www/`**. `.vscode/sftp.json` wysyła do `~/ulf1` (inny projekt!) – nie używać do tej strony.
- FTP działa przez `ftp://` + TLS (`curl --ssl --insecure`); `sftp` w lokalnym curlu jest wyłączony. Dane logowania w `C:\laragon\www\.vscode\sftp.json`.
- `~/bielice2` został przeniesiony do `~/www/bielice2` (użytkownik zrobił to klientem FTP) – `https://jakubgorski.com/bielice2/` zwraca 200.
- Sprawdzono: brak śladów włamania (pierwszy poziom katalogów); logi niedostępne przez FTP.
- Zmieniono uprawnienia `ulf1/db/*.sqlite` z 666 na 664 – warto sprawdzić, czy zamówienia/B2B zapisują się poprawnie.

## Do zrobienia po powrocie
1. Wgrać aktualny `index.html` do `~/www/index.html` (FTP).
2. Zrobić commit zmian.
3. Opcjonalnie: dopisać podpisy wystawców przy certyfikatach Cambridge FCE i PHP Developer; ocenić wygląd w przeglądarce (nie był sprawdzany wizualnie).
