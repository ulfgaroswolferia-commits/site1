# Zmiana nazwy modułu na dashboardzie — specyfikacja projektu

## Cel

Na ekranie launchera po zalogowaniu etykieta modułu `Order` ma być zastąpiona
nazwą:

`Zamówienia z cennika excel`

Nazwa ma lepiej opisywać przeznaczenie modułu dla użytkownika.

## Zakres

- Zmienić wyłącznie tekst widoczny w `views/home/launcher.php`.
- Zachować istniejący link do `order/index`.
- Zachować istniejący opis modułu, placeholder, link do pulpitu i link
  „Wyloguj”.
- Nie zmieniać tras, kontrolerów, modeli, bazy danych ani widoków wewnątrz
  modułu Order.

## Kryteria akceptacji

- Launcher wyświetla dokładnie tekst `Zamówienia z cennika excel` jako tytuł
  pierwszego modułu.
- Kliknięcie tego modułu nadal prowadzi do `/order/index`.
- Tekst `Order` nie występuje już jako tytuł pierwszego modułu launchera.
- Pozostałe elementy launchera działają bez zmian.
- Zmieniony plik przechodzi `php -l`.

## Poza zakresem

- Nie zmieniamy technicznej nazwy trasy `order`.
- Nie zmieniamy nazw klas `OrderController` ani `OrderModel`.
- Nie zmieniamy tekstów w ekranach wewnątrz modułu Order.
