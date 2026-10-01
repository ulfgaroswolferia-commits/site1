# Budowanie `assets/css/b2b.css` (Tailwind CSS v3)

Widoki B2B (`views/b2b/*.php`) używają klas Tailwinda. CSS jest budowany statycznie —
strona nie ładuje Tailwinda z CDN (blokuje to też nagłówek CSP).

Po dodaniu lub zmianie klas w `views/b2b/*` przebuduj plik i wgraj go razem z widokami.

## Narzędzie

Samodzielna binarka Tailwind CLI **v3.4.17** (bez npm, nie trzymamy jej w repozytorium):

- Windows: <https://github.com/tailwindlabs/tailwindcss/releases/download/v3.4.17/tailwindcss-windows-x64.exe>
- SHA-256 (windows-x64): `67f1c5e3f5a03406a7bf5badf5ada09b79f3ae78ec43450c15f7e983068da346`
  (lista sum: `sha256sums.txt` w tym samym wydaniu)

Wersja 3 odpowiada temu, co wcześniej serwował `cdn.tailwindcss.com` — nie przechodź na v4
bez przejrzenia klas (v4 zmienia nazwy części utility).

## Budowanie

Z katalogu głównego projektu:

```
tailwindcss-windows-x64.exe -c bin/tailwind/tailwind.config.js -i bin/tailwind/input.css -o assets/css/b2b.css --minify
```

Widoki dołączają plik z parametrem `?v=<filemtime>`, więc przeglądarki pobiorą nową wersję
automatycznie po wgraniu.

Klasy muszą występować w plikach jako pełne napisy (np. `'bg-emerald-600'`) — sklejane
dynamicznie (`'bg-' + kolor`) nie zostaną wykryte.
