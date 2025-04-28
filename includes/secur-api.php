<?php
/**
 * Plik odpowiedzialny za zabezpieczenie WordPress REST API za pomocą klucza API.
 * Klucz API jest odczytywany ze stałej PHYSIS_API_KEY zdefiniowanej w wp-config.php.
 */

// Zabezpieczenie przed bezpośrednim dostępem
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Dodaje uwierzytelnianie kluczem API do WordPress REST API.
 */
add_filter( 'rest_authentication_errors', 'physis_rest_api_key_auth_from_config' );

/**
 * Sprawdza klucz API w żądaniach do REST API, odczytując go z wp-config.php.
 * Wersja dla Read-Only - nie ustawia kontekstu użytkownika.
 * POPRAWIONA: Dodano sprawdzenie uprawnień, aby ignorować dla zalogowanych adminów.
 *
 * @param WP_Error|null|true $result Wynik poprzednich sprawdzeń uwierzytelniania.
 * @return WP_Error|null|true
 */
function physis_rest_api_key_auth_from_config( $result ) {
    // Jeśli inny sposób uwierzytelniania już zadziałał lub zwrócił błąd, przekaż go dalej.
    if ( true === $result || is_wp_error( $result ) ) {
        return $result;
    }

    // <<< NOWY WARUNEK >>>
    // Sprawdź, czy użytkownik jest zalogowany i ma odpowiednie uprawnienia
    // np. 'manage_options' (dla administratorów) lub 'edit_posts' (jeśli edytorzy też mają działać)
    // Jeśli tak, to prawdopodobnie jest to akcja w panelu admina lub uwierzytelnione żądanie REST,
    // które nie powinno wymagać klucza API.
    // Dostosuj uprawnienie ('manage_options') do swoich potrzeb.
    if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
        // Zalogowany użytkownik z uprawnieniami - nie wymagaj klucza API dla niego.
        // Zwracamy oryginalny $result, który w tym momencie prawdopodobnie jest null,
        // co pozwala WordPressowi kontynuować standardowe sprawdzanie uprawnień dla endpointu.
        // LUB można zwrócić true, jeśli chcemy od razu nadać dostęp na tym etapie.
        // Bezpieczniej jest zwrócić $result.
        return $result;
    }
    // <<< KONIEC NOWEGO WARUNKU >>>


    // --- Odczyt poprawnego klucza API ze stałej w wp-config.php ---
    if ( ! defined('PHYSIS_API_KEY') ) {
        // Krytyczny błąd konfiguracyjny - stała nie jest zdefiniowana
        // Można zalogować ten błąd dla administratora
        // error_log('Stała PHYSIS_API_KEY nie jest zdefiniowana w wp-config.php!');
        return new WP_Error(
            'rest_api_key_not_defined',
            __( 'Klucz API nie został poprawnie skonfigurowany po stronie serwera.', 'Physis_zarzadzanie' ),
            array( 'status' => 500 ) // Internal Server Error
        );
    }

    $correct_api_key = PHYSIS_API_KEY;

    // Sprawdź, czy klucz nie jest pusty (na wszelki wypadek)
    if ( empty($correct_api_key) ) {
         return new WP_Error(
            'rest_api_key_empty',
            __( 'Skonfigurowany klucz API jest pusty.', 'Physis_zarzadzanie' ),
            array( 'status' => 500 ) // Internal Server Error
        );
    }

    // Pobierz klucz API z nagłówka żądania (np. Authorization: Bearer KLUCZ)
    $auth_header = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? sanitize_text_field( $_SERVER['HTTP_AUTHORIZATION'] ) : '';
    $provided_key = '';

    if ( ! empty( $auth_header ) ) {
        // Sprawdź format "Bearer <klucz>"
        if ( preg_match( '/^Bearer\s+(.*)$/i', $auth_header, $matches ) ) {
            $provided_key = $matches[1];
        }
        // Można dodać alternatywne sprawdzanie innego nagłówka, np. X-API-Key
    }

    // --- Weryfikacja klucza ---
    // Używamy hash_equals dla bezpiecznego porównania stringów (odporność na timing attacks)
    if ( ! hash_equals( $correct_api_key, $provided_key ) ) {
        // Zwróć błąd uwierzytelniania - klucz nie pasuje lub nie został podany
        // UWAGA: Ten błąd będzie teraz zwracany tylko dla niezalogowanych użytkowników
        // LUB zalogowanych bez uprawnienia 'manage_options', którzy nie podali poprawnego klucza API.
        return new WP_Error(
            'rest_invalid_api_key',
            __( 'Nieprawidłowy klucz API.', 'Physis_zarzadzanie' ),
            array( 'status' => 401 ) // 401 Unauthorized
        );
    }

    // --- Klucz jest poprawny ---
    // Zwróć true, aby zasygnalizować sukces uwierzytelniania API kluczem
    // i zezwolić na dalsze przetwarzanie.
    return true;
}

// Celowo brak zamykającego ?>