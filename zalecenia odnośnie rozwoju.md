# Zalecenia dotyczące ulepszeń projektu Physis

## 1. Bezpieczeństwo
- Dodaj walidację i sanityzację danych wejściowych w funkcjach eksportu CSV.
- Użyj `wp_verify_nonce` do zabezpieczenia akcji eksportu CSV przed CSRF.
- Dodaj bardziej szczegółowe komunikaty błędów w panelu administracyjnym w przypadku krytycznych problemów.

## 2. Optymalizacja wydajności
- Rozważ paginację lub strumieniowe generowanie pliku CSV zamiast `posts_per_page => -1`.
- Zautomatyzuj wersjonowanie plików CSS/JS za pomocą daty modyfikacji pliku.

## 3. Poprawa czytelności i organizacji kodu
- Podziel funkcję `physis_generate_produkt_csv_export` na mniejsze funkcje, np. `prepare_csv_headers`, `prepare_csv_rows`, `output_csv`.
- Dodaj szczegółowe komentarze do funkcji i klas, używając standardu PHPDoc.

## 4. Testy jednostkowe
- Dodaj testy jednostkowe dla kluczowych funkcji, takich jak `physis_generate_produkt_csv_export` i `physis_include_file`.
- Użyj frameworka PHPUnit do testowania.

## 5. Zgodność z WordPress Coding Standards
- Uruchom narzędzie `phpcs` z regułami WordPress Coding Standards, aby znaleźć i poprawić potencjalne problemy.

## 6. Poprawa UX
- Dodaj bardziej przyjazne komunikaty błędów w panelu administracyjnym.
- Dodaj możliwość wyboru separatora (np. przecinek lub średnik) w interfejsie eksportu CSV.

## 7. Rozszerzalność
- Dodaj więcej hooków i filtrów, aby umożliwić łatwiejsze rozszerzanie funkcjonalności przez inne wtyczki.

## 8. Inne ulepszenia
- Zamień `error_log` na `WP_DEBUG_LOG` dla lepszej integracji z WordPressem.
- Dodaj logikę do obsługi błędów krytycznych w funkcji `physis_include_file`.

## Priorytety
1. Bezpieczeństwo (CSRF, sanityzacja danych).
2. Optymalizacja wydajności (paginacja, cache busting).
3. Testy jednostkowe.
4. Poprawa UX i dokumentacji.
5. Rozszerzalność i zgodność z WordPress Coding Standards.