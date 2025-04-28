<?php
// Plik: includes/security.php
if ( ! defined( 'ABSPATH' ) ) exit; // Zabezpieczenie

/**
 * Ogranicza dostęp do stron CPT 'produkt' tylko dla zalogowanych.
 */
function moj_system_ogranicz_dostep_produktow() {
    // Sprawdź, czy to archiwum 'produkt' lub pojedynczy 'produkt'
    if ( is_post_type_archive( 'produkt' ) || is_singular( 'produkt' ) ) {
        // Sprawdź, czy użytkownik NIE jest zalogowany
        if ( ! is_user_logged_in() ) {
            // Przekieruj na stronę logowania WordPressa
            // Po zalogowaniu, użytkownik wróci na stronę produktu, którą próbował otworzyć
            auth_redirect();
            // Funkcja auth_redirect() sama wywołuje exit, ale dla pewności można dodać
            // exit;
        }
    }
}
add_action( 'template_redirect', 'moj_system_ogranicz_dostep_produktow' );

// Koniec pliku - bez zamykającego ?>