# Dodanie wylogowania do launchera — specyfikacja projektu

## Cel

Użytkownik musi mieć dostęp do wylogowania bezpośrednio na ekranie widocznym
po zalogowaniu (`/home/index`). Obecny rozbudowany dashboard pod
`/home/dashboard` ma już działający link „Wyloguj”; zmiana dotyczy launchera.

## Zakres

- Zmienić wyłącznie `views/home/launcher.php`.
- Dodać mały, drugorzędny link „Wyloguj” w nagłówku launchera, poza listą
  trzech głównych elementów:
  - moduł Order,
  - placeholder „Miejsce na następny moduł”,
  - przycisk „Otwórz pulpit”.
- Link ma prowadzić przez `App::baseUrl()` do `home/logout`.
- Nie zmieniać `HomeController::actionLogout()`, sesji ani routingu.

## Zachowanie

Po kliknięciu „Wyloguj” istniejąca akcja `HomeController::actionLogout()` kończy
sesję i przekierowuje użytkownika do `home/login`.

Launcher nadal ma trzy główne elementy modułowe w dotychczasowej kolejności.
Link wylogowania jest dodatkową akcją pomocniczą interfejsu, a nie czwartym
modułem.

## Kryteria akceptacji

- Na `/home/index` widoczny jest link „Wyloguj”.
- Link używa `<?= $base ?>home/logout`, bez hardkodowania domeny lub ścieżki.
- Link znajduje się poza listą modułów i nie zmienia kolejności trzech głównych
  elementów.
- Po kliknięciu użytkownik trafia do ekranu logowania i nie może wejść do
  chronionego dashboardu bez ponownego zalogowania.
- Istniejący dashboard i jego link wylogowania pozostają bez zmian.
- Zmieniony widok przechodzi `php -l`.

## Poza zakresem

- Nie dodajemy potwierdzenia wylogowania.
- Nie zmieniamy stylu ani zawartości modułu Order.
- Nie przebudowujemy wspólnego layoutu ani mechanizmu sesji.
