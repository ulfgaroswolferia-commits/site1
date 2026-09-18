# Uporządkowanie ekranu po zalogowaniu — specyfikacja projektu

## Cel

Po zalogowaniu użytkownik ma trafić na prosty ekran wyboru modułu, a nie na
rozbudowany dashboard zawierający wiele niezwiązanych elementów. Ekran ma
prezentować wyłącznie:

1. wejście do modułu Order,
2. miejsce na przyszły moduł,
3. wejście do dotychczasowego dashboardu.

Obecny dashboard ma pozostać dostępny bez zmiany zawartości.

## Zakres zmian

### Routing i kontroler

- `HomeController::actionIndex()`:
  - dla niezalogowanego użytkownika zachowuje obecne przejście do logowania,
  - dla zalogowanego renderuje nowy ekran wyboru modułów.
- `HomeController::actionDashboard()`:
  - wymaga zalogowania,
  - przekazuje dotychczasowe dane użytkownika i tytuł,
  - renderuje istniejący widok `views/home/dashboard.php`.
- Nie dodajemy nowej trasy ani kontrolera. Wykorzystujemy istniejącą trasę
  `home` i nową akcję `dashboard`.

### Widoki

Powstaje osobny widok ekranu wyboru, np. `views/home/launcher.php`.

Widok zawiera dokładnie trzy główne elementy, w kolejności pionowej:

1. **Order** — wyróżniona karta/przycisk prowadzący do
   `order/index`.
2. **Miejsce na następny moduł** — nieaktywny, neutralny placeholder bez
   linku i bez działania.
3. **Otwórz pulpit** — przycisk prowadzący do
   `home/dashboard`, czyli dotychczasowego dashboardu.

Ekran wyboru nie zawiera dodatkowych kafelków, tabel, danych technicznych,
linków demonstracyjnych ani skrótów do innych funkcji. Jego styl ma być
czytelny, oszczędny i zgodny z istniejącą paletą aplikacji.

### Linki powrotne

Linki opisane jako „Pulpit” w widokach modułu Order nadal prowadzą do
`home/index`, czyli do nowego ekranu wyboru modułów. Dotychczasowy dashboard
jest dostępny wyłącznie przez przycisk „Otwórz pulpit” na ekranie wyboru.

## Przepływ użytkownika

```text
GET /
  ├─ niezalogowany → formularz logowania
  └─ zalogowany → ekran wyboru modułów
                    ├─ Order → /order/index
                    ├─ placeholder → brak akcji
                    └─ Otwórz pulpit → /home/dashboard
```

Bezpośrednie wejście na `/home/dashboard` dla niezalogowanego użytkownika
ma zostać obsłużone przez standardowe `requireAuth()` i przekierowanie do
logowania.

## Kryteria akceptacji

- Po zalogowaniu widoczne są tylko karta/przycisk Order, placeholder i
  końcowy przycisk do pulpitu.
- Kliknięcie Order otwiera istniejący moduł `order/index`.
- Placeholder nie jest klikalny i nie powoduje żądania.
- Kliknięcie „Otwórz pulpit” otwiera dotychczasowy dashboard bez utraty jego
  zawartości.
- Bezpośredni adres `/home/dashboard` wymaga zalogowania.
- Niezalogowany przepływ logowania pozostaje bez zmian.
- Linki są budowane przez `App::baseUrl()`, a wartości wyświetlane w widoku
  są escapowane zgodnie ze standardem MVC.
- Zmienione pliki przechodzą `php -l`.

## Poza zakresem

- Nie zmieniamy zawartości ani stylu istniejącego dashboardu.
- Nie zmieniamy modułu Order, modeli, bazy danych ani konfiguracji tras.
- Nie implementujemy jeszcze przyszłego modułu wskazywanego przez placeholder.
