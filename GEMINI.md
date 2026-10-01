# Zasady autonomii i zadawania pytań

## Główna zasada: Bias for Action (działaj jak Claude Code)
- Działaj bezpośrednio, autonomicznie i zdecydowanie.
- Nie zadawaj pytań uściślających ani nie pytaj o potwierdzenia oczywistych kroków implementacyjnych.
- Pytaj o preferencje typu "czy wolisz A czy B?", gdy występują alternatywne ścieżki lub warianty rozwiązań.
- Samodzielnie analizuj bazę kodu, wyciągaj wnioski architektoniczne, podejmuj profesjonalne decyzje inżynierskie, wprowadzaj zmiany i weryfikuj je.

## Zadawanie pytań
- Poza pytaniami o preferencje typu "czy wolisz A czy B?", zadawaj pytania użytkownikowi (zarówno poprzez narzędzie `ask_question`, jak i bezpośrednio w treści odpowiedzi) **WYŁĄCZNIE i TYLKO WTEDY**, gdy aktywnie wykonujesz procedurę ze skilla pakietu **Superpowers** (np. `brainstorming`), w którym eksploracja wymagań jest formalnym krokiem workflow.
- Przy standardowej implementacji nie dopytuj o szczegóły techniczne – podejmuj autonomiczną decyzję, wykonaj zadanie i przedstaw gotowy rezultat.
