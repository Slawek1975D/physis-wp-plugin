<?php
/**
 * Plugin Name:       Physis
 * Plugin URI:        #
 * Description:       Niestandardowy system zarządzania produktami dla małej firmy.
 * Version:           1.1.1
 * Author:            Physis sp z o.o.
 * Author URI:        #
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       Physis_zarzadzanie
 * Domain Path:       /languages
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin directory path constant
define( 'PHYSIS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PHYSIS_PLUGIN_FILE', __FILE__ );
// error_log('DEBUG Physis: Sprawdzana ścieżka dla includes: ' . PHYSIS_PLUGIN_DIR . 'includes/'); // Odkomentuj TYLKO do debugowania ścieżki

// --- Funkcja pomocnicza do ładowania plików PHP ---
// UWAGA: Ta funkcja jest przeznaczona TYLKO do ładowania plików .php!
if ( ! function_exists('physis_include_file') ) {
    function physis_include_file( $relative_path, $is_critical = false ) {
        $file_path = PHYSIS_PLUGIN_DIR . $relative_path;
        if ( file_exists( $file_path ) ) {
            // Upewnij się, że to plik PHP przed próbą dołączenia
            if ( pathinfo($file_path, PATHINFO_EXTENSION) === 'php') {
                 require_once $file_path;
                 return true;
            } else {
                 $error_message = "BŁĄD Physis (Helper): Próba dołączenia pliku niebędącego PHP przez require_once: " . esc_html($relative_path);
                 error_log($error_message);
                 // Nie dodawaj admin_notices dla tego typu błędu, bo może być wywołany w złym momencie
                 return false; // Zwróć false, bo to nie jest plik PHP
            }
        } else {
            $error_message = "BŁĄD Physis (Helper): Plik PHP nie znaleziony: " . esc_html($relative_path) . " (sprawdzana ścieżka: " . esc_html($file_path) . ")";
            error_log($error_message); // Zapisz do logu błędów PHP/WP
            if ($is_critical) {
                 add_action('admin_notices', function() use ($error_message) {
                     echo '<div class="notice notice-error is-dismissible"><p>' . $error_message . '</p></div>';
                 });
            }
            return false;
        }
    }
}

// --- Include Functionality Files (Wersja tylko dla Produktów) ---

physis_include_file( 'includes/helpers.php', true );
physis_include_file( 'includes/cpt-setup.php', true ); // Rejestruje tylko 'produkt'
physis_include_file( 'includes/security.php' ); // Zakładam, że ten plik istnieje
physis_include_file( 'includes/rest-api.php' );
physis_include_file( 'includes/secur-api.php' );

// Admin-only functions
if ( is_admin() ) {

    // Załaduj pliki z logiką admina
    $metaboxes_produkt_loaded = physis_include_file( 'includes/metaboxes-produkt.php', true );
    $admin_columns_produkt_loaded = physis_include_file( 'includes/admin-columns-produkt.php', true );
    $search_class_loaded = physis_include_file( 'includes/class-produkt-search-list-table.php', true );
    $search_page_loaded = physis_include_file( 'includes/admin-search-page-produkt.php', true );

    // Sprawdź, czy krytyczne pliki admina zostały załadowane
    if ( !$metaboxes_produkt_loaded || !$admin_columns_produkt_loaded || !$search_class_loaded || !$search_page_loaded ) {
         // Jeśli któryś z krytycznych plików admina się nie załadował, można dodać ogólny notice
         add_action('admin_notices', function() {
             echo '<div class="notice notice-error"><p>' . __('Błąd krytyczny: Nie udało się załadować wszystkich niezbędnych komponentów panelu admina dla wtyczki Physis. Sprawdź logi błędów PHP.', 'Physis_zarzadzanie') . '</p></div>';
         });
         // Można rozważyć `return;` tutaj, aby nie kontynuować, jeśli kluczowe pliki brakuje
    }


    /**
     * Dodaje metaboxy tylko dla CPT Produkt.
     * Uruchamiane tylko jeśli plik metaboxów został poprawnie załadowany.
     */
    if ( $metaboxes_produkt_loaded && ! function_exists('moj_system_add_meta_boxes_callback') ) {
        function moj_system_add_meta_boxes_callback() {
            // Sprawdź, czy funkcja renderująca HTML metaboxa istnieje (powinna, bo plik się załadował)
            if ( function_exists('moj_system_produkt_meta_box_html') ) {
                 add_meta_box(
                    'moj_system_produkt_meta_box_id',          // ID metaboxa
                    __( 'Dane Produktu', 'Physis_zarzadzanie' ), // Tytuł metaboxa
                    'moj_system_produkt_meta_box_html',        // Funkcja renderująca zawartość
                    'produkt',                                 // Typ postu
                    'normal',                                  // Kontekst (normal, side, advanced)
                    'high'                                     // Priorytet (high, core, default, low)
                 );
            } else {
                // Ten log nie powinien się pojawić, jeśli $metaboxes_produkt_loaded jest true, ale dla pewności
                error_log('BŁĄD Physis: Próba dodania metaboxa produktu, ale funkcja moj_system_produkt_meta_box_html nie istnieje, mimo że plik został załadowany!');
            }
             // Usunięto dodawanie metaboxów dla 'etykieta'
        }
        // Podpinamy funkcję callback do hooka dla CPT 'produkt'
        add_action( 'add_meta_boxes_produkt', 'moj_system_add_meta_boxes_callback' );
    }
    // Usunięto hook dla 'add_meta_boxes_etykieta'


    // --- Funkcje eksportu CSV ---
    // Definicja funkcji generowania CSV (upewnij się, że helper istnieje)
    if ( function_exists('physis_get_exportable_fields') && ! function_exists('physis_generate_produkt_csv_export') ) {
        function physis_generate_produkt_csv_export() {
            // --- 1. Pobierz parametry wyszukiwania ---
            $szukana_nazwa = isset( $_GET['s_nazwa'] ) ? sanitize_text_field( $_GET['s_nazwa'] ) : '';
            $szukany_ean = isset( $_GET['s_ean'] ) ? sanitize_text_field( $_GET['s_ean'] ) : '';
            $szukany_cdt_id = isset( $_GET['s_cdt_id'] ) ? sanitize_text_field( $_GET['s_cdt_id'] ) : '';
            $szukany_kod_prod = isset( $_GET['s_kod_prod'] ) ? sanitize_text_field( $_GET['s_kod_prod'] ) : '';
            $szukany_producent = isset( $_GET['s_producent'] ) ? sanitize_text_field( $_GET['s_producent'] ) : '';
            $szukana_linia_marki = isset( $_GET['s_linia_marki'] ) ? sanitize_text_field( $_GET['s_linia_marki'] ) : '';

            // --- 2. Pobierz wybrane kolumny do eksportu ---
            $selected_keys = array();
            if ( isset($_GET['export_cols']) && is_array($_GET['export_cols']) ) {
                 // Sanityzuj klucze kolumn
                 $selected_keys = array_map('sanitize_key', $_GET['export_cols']);
            }
            $all_exportable_fields = physis_get_exportable_fields();
            // Domyślne klucze, jeśli nic nie wybrano lub wystąpił błąd
            $default_keys = ['ID', 'post_title', '_ean', '_producent'];

            // Jeśli nie ma wybranych kluczy LUB wybrane klucze są nieprawidłowe, użyj domyślnych
            if ( empty($selected_keys) ) {
                $selected_keys = $default_keys;
            }

            // Filtruj dostępne pola eksportu na podstawie wybranych kluczy
            $export_fields = array_filter(
                $all_exportable_fields,
                function($key) use ($selected_keys) {
                    return in_array($key, $selected_keys, true);
                },
                ARRAY_FILTER_USE_KEY
            );

            // Jeśli po filtracji nadal nie ma pól (np. wszystkie podane klucze były błędne), wróć do domyślnych
            if ( empty($export_fields) ) {
                 $selected_keys = $default_keys;
                 $export_fields = array_filter( $all_exportable_fields, function($key) use ($selected_keys) { return in_array($key, $selected_keys, true); }, ARRAY_FILTER_USE_KEY );
            }

            // --- 3. Zbuduj argumenty WP_Query na podstawie kryteriów wyszukiwania ---
            $args = array(
                'post_type'      => 'produkt',
                'post_status'    => 'publish',
                'posts_per_page' => -1, // Pobierz wszystkie pasujące produkty
                'orderby'        => 'title', // Domyślne sortowanie
                'order'          => 'ASC',
                'meta_query'     => array( 'relation' => 'AND' ) // Zacznij od relacji AND
            );
            // Dodaj warunki wyszukiwania
            if ( ! empty( $szukana_nazwa ) ) { $args['s'] = $szukana_nazwa; } // Wyszukiwanie po tytule/treści
            if ( ! empty( $szukany_ean ) ) { $args['meta_query'][] = array( 'key' => '_ean', 'value' => $szukany_ean, 'compare' => 'LIKE' ); }
            if ( ! empty( $szukany_cdt_id ) ) { $args['meta_query'][] = array( 'key' => '_cdt_id', 'value' => $szukany_cdt_id, 'compare' => 'LIKE' ); }
            if ( ! empty( $szukany_kod_prod ) ) { $args['meta_query'][] = array( 'key' => '_kod_producenta', 'value' => $szukany_kod_prod, 'compare' => 'LIKE' ); }
            if ( ! empty( $szukany_producent ) ) { $args['meta_query'][] = array( 'key' => '_producent', 'value' => $szukany_producent, 'compare' => '=' ); } // Dokładne dopasowanie producenta
            if ( ! empty( $szukana_linia_marki ) ) { $args['meta_query'][] = array( 'key' => '_linia_marki', 'value' => $szukana_linia_marki, 'compare' => '=' ); } // Dokładne dopasowanie linii
            // Usuń 'meta_query', jeśli nie dodano żadnych warunków meta
            if ( count( $args['meta_query'] ) <= 1 ) {
                unset( $args['meta_query'] );
            }

            // --- 4. Wykonaj zapytanie WP_Query ---
            $produkt_query = new WP_Query( $args );

            // --- 5. Przygotuj nagłówki i klucze CSV ---
            $csv_headers = array_values($export_fields); // Etykiety kolumn
            $csv_keys = array_keys($export_fields);     // Klucze meta (lub 'ID', 'post_title')

            // --- 6. Ustaw nagłówki HTTP do pobrania pliku CSV ---
            $filename = 'eksport_produktow_' . date('Y-m-d_H-i') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            // --- 7. Otwórz strumień wyjściowy PHP ---
            $output = fopen('php://output', 'w');
            // Dodaj BOM UTF-8, aby Excel poprawnie rozpoznał polskie znaki
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // --- 8. Zapisz wiersz nagłówków do CSV ---
            fputcsv($output, $csv_headers, ';'); // Użyj średnika jako separatora

            // --- 9. Iteruj po wynikach zapytania i zapisz dane ---
            if ( $produkt_query->have_posts() ) {
                while ( $produkt_query->have_posts() ) {
                    $produkt_query->the_post();
                    $post_id = get_the_ID();
                    $row = array();
                    // Zbierz dane dla każdej wybranej kolumny
                    foreach ($csv_keys as $key) {
                        $value = '';
                        if ($key === 'ID') {
                            $value = $post_id;
                        } elseif ($key === 'post_title') {
                            $value = get_the_title($post_id);
                        } else {
                            // Pobierz wartość pola meta
                            $value = get_post_meta($post_id, $key, true);
                        }
                        // Prosta sanityzacja danych dla CSV
                        $cleaned_value = is_scalar($value) ? (string) $value : ''; // Upewnij się, że to string
                        // Obsłuż cudzysłowy i średniki wewnątrz wartości
                        $cleaned_value = str_replace(array('"', ';'), array('""', ','), $cleaned_value);
                        // Zastąp wielokrotne białe znaki pojedynczą spacją
                        $cleaned_value = preg_replace('/\s+/', ' ', $cleaned_value);
                        $row[] = $cleaned_value;
                    }
                    // Zapisz wiersz danych do CSV
                    fputcsv($output, $row, ';');
                }
                wp_reset_postdata(); // Przywróć globalny obiekt $post
            }

            // --- 10. Zamknij strumień i zakończ skrypt ---
            fclose($output);
            exit; // Zakończ działanie skryptu po wygenerowaniu pliku
        }
    } elseif ( ! function_exists('physis_get_exportable_fields') ) {
         // Krytyczny błąd, jeśli funkcja pomocnicza nie istnieje
         add_action('admin_notices', function() {
             echo '<div class="notice notice-error"><p>' . __('Błąd krytyczny: Brak funkcji physis_get_exportable_fields() potrzebnej do eksportu CSV.', 'Physis_zarzadzanie') . '</p></div>';
         });
    }

    // Definicja funkcji sprawdzającej żądanie eksportu
    if ( ! function_exists('physis_check_for_export_request') ) {
        function physis_check_for_export_request() {
            // Sprawdź, czy jesteśmy na stronie wyszukiwania i czy parametr akcji eksportu jest ustawiony
            if ( isset($_GET['page']) && $_GET['page'] === 'wyszukaj-produkt-physis' &&
                 isset($_GET['physis_action']) && $_GET['physis_action'] === 'export_csv' )
            {
                // Sprawdź uprawnienia użytkownika
                if ( ! current_user_can('edit_posts') ) { // Można zmienić na bardziej restrykcyjne uprawnienie
                    wp_die( __('Nie masz wystarczających uprawnień do wykonania tej akcji.', 'Physis_zarzadzanie'), 403 );
                }
                // Wywołaj generator CSV, jeśli funkcja istnieje
                if ( function_exists('physis_generate_produkt_csv_export') ) {
                     physis_generate_produkt_csv_export();
                } else {
                     // Ten błąd nie powinien wystąpić, jeśli powyższa definicja się powiodła
                     wp_die('Błąd krytyczny: Funkcja eksportu CSV (physis_generate_produkt_csv_export) nie jest dostępna.', 500);
                }
            }
        }
    }
    // Dodaj hook do sprawdzania żądania eksportu na etapie inicjalizacji admina
    if ( function_exists('physis_check_for_export_request') ) {
        add_action( 'admin_init', 'physis_check_for_export_request' );
    }

    // =========================================================================
    // === POCZĄTEK POPRAWIONEJ SEKCJI ŁADOWANIA SKRYPTÓW I STYLI ===
    // =========================================================================
    /**
     * Ładuje skrypty i style admina - wersja bez Etykiet.
     * POPRAWIONA: Usunięto błędne użycie physis_include_file dla zasobów CSS/JS.
     */
     if ( ! function_exists('physis_admin_enqueue_scripts') ) {
         function physis_admin_enqueue_scripts( $hook_suffix ) {
             $screen = get_current_screen();
             if ( ! $screen ) { return; }
             $search_page_hook = 'produkt_page_wyszukaj-produkt-physis';
             // Ustal wersję pliku (można użyć stałej lub daty modyfikacji pliku dla cache busting)
             $plugin_data = get_plugin_data(PHYSIS_PLUGIN_FILE);
             $plugin_version = isset($plugin_data['Version']) ? $plugin_data['Version'] : '1.0.0';

             // Zdefiniuj ścieżki i URL do katalogu assets dla czytelności
             $assets_dir_path = PHYSIS_PLUGIN_DIR . 'assets/';
             $assets_dir_url = plugin_dir_url( PHYSIS_PLUGIN_FILE ) . 'assets/';

             // --- Ładowanie styli CSS ---
             // Ładuj style CSS na stronie edycji produktu LUB na stronie wyszukiwania produktu
             if ( ($screen->base === 'post' && $screen->post_type === 'produkt') || $hook_suffix === $search_page_hook )
             {
                 $css_file_path = $assets_dir_path . 'css/admin-styles.css';
                 // *** POPRAWKA: Bezpośrednie sprawdzenie file_exists ***
                 if ( file_exists($css_file_path) ) {
                     // Dodajmy wersję pliku na podstawie czasu modyfikacji dla lepszego cache busting
                     $css_version = $plugin_version . '.' . filemtime($css_file_path);
                     wp_enqueue_style(
                         'physis-admin-styles',                             // Uchwyt stylu
                         $assets_dir_url . 'css/admin-styles.css',           // URL do pliku CSS
                         array(),                                           // Zależności (brak)
                         $css_version                                      // Wersja pliku
                     );
                 } else {
                     // Opcjonalnie: zaloguj błąd, jeśli plik nie istnieje
                     error_log('BŁĄD Physis: Nie znaleziono pliku CSS: ' . $css_file_path);
                 }
             }

             // --- Ładowanie skryptów JS ---
             // Ładuj skrypt JS tylko na stronie wyszukiwania produktów
             if ( $hook_suffix === $search_page_hook ) {
                  $js_file_path = $assets_dir_path . 'js/admin-scripts.js';
                  // *** POPRAWKA: Bezpośrednie sprawdzenie file_exists ***
                  if ( file_exists($js_file_path) ) {
                      // Dodajmy wersję pliku na podstawie czasu modyfikacji
                      $js_version = $plugin_version . '.' . filemtime($js_file_path);
                      wp_enqueue_script(
                          'physis-admin-scripts',                            // Uchwyt skryptu
                          $assets_dir_url . 'js/admin-scripts.js',          // URL do pliku JS
                          array('jquery'),                                  // Zależności (jQuery)
                          $js_version,                                      // Wersja pliku
                          true                                              // Ładuj w stopce
                      );
                  } else {
                     // Opcjonalnie: zaloguj błąd, jeśli plik nie istnieje
                     error_log('BŁĄD Physis: Nie znaleziono pliku JS: ' . $js_file_path);
                 }
                  // Usunięto wp_localize_script (jeśli nie jest potrzebne do przekazywania danych PHP->JS)
             }
         }
     }
     // Upewnij się, że hook jest nadal podpięty
     add_action( 'admin_enqueue_scripts', 'physis_admin_enqueue_scripts' );
    // =========================================================================
    // === KONIEC POPRAWIONEJ SEKCJI ŁADOWANIA SKRYPTÓW I STYLI ===
    // =========================================================================

} // Koniec if ( is_admin() )


// --- Activation Hook ---
// Funkcja uruchamiana podczas aktywacji wtyczki (np. do flushowania rewrite rules)
function moj_system_rewrite_flush() {
    // Upewnij się, że funkcja rejestracji CPT jest dostępna
    if ( function_exists( 'moj_system_rejestracja_cpt' ) ) {
        moj_system_rejestracja_cpt(); // Zarejestruj typ postu 'produkt'
    } else {
        error_log('BŁĄD Physis (Aktywacja): Funkcja moj_system_rejestracja_cpt nie istnieje podczas aktywacji.');
    }
    // Odśwież reguły przepisywania, aby nowy CPT był dostępny od razu
    flush_rewrite_rules();
}
// Zarejestruj funkcję do wykonania podczas aktywacji wtyczki
register_activation_hook( PHYSIS_PLUGIN_FILE, 'moj_system_rewrite_flush' );