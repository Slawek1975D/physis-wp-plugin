<?php
// File: includes/admin-search-page-produkt.php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Zmienna globalna do przechowywania instancji tabeli
global $produkt_list_table_instance;

/**
 * Adds the submenu page for the product search and captures the page hook.
 */
function moj_system_dodaj_strone_wyszukiwania() {
    $page_hook = add_submenu_page(
        'edit.php?post_type=produkt',
        __( 'Wyszukaj Produkt', 'Physis_zarzadzanie' ),
        __( 'Wyszukaj Produkt', 'Physis_zarzadzanie' ),
        'edit_posts', // Zmień uprawnienie jeśli potrzeba
        'wyszukaj-produkt-physis',
        'moj_system_renderuj_strone_wyszukiwania'
    );

    // Dodaj akcję 'load' specyficzną dla tej strony
    add_action( "load-{$page_hook}", 'moj_system_zaladuj_ekran_wyszukiwania' );
}
add_action( 'admin_menu', 'moj_system_dodaj_strone_wyszukiwania' );

/**
 * Funkcja wywoływana na haku load-{page_hook}.
 * Inicjalizuje tabelę WP_List_Table i dodaje opcje ekranu.
 */
function moj_system_zaladuj_ekran_wyszukiwania() {
    global $produkt_list_table_instance;

    // Utwórz instancję tabeli
    // Upewnij się, że plik z klasą został załadowany (powinien być w physis.php)
    if ( class_exists('Produkt_Search_List_Table') ) {
        $produkt_list_table_instance = new Produkt_Search_List_Table();

        // Dodaj opcję ekranu 'per_page' (liczba elementów na stronę)
        add_screen_option( 'per_page', array(
            'label'   => __( 'Produkty na stronę', 'Physis_zarzadzanie' ),
            'default' => 20,
            'option'  => 'produkty_search_per_page' // Klucz opcji zapisywanej w user meta
        ) );
    } else {
        // Zaloguj błąd lub wyświetl powiadomienie, jeśli klasa nie istnieje
        error_log('BŁĄD Physis: Klasa Produkt_Search_List_Table nie została znaleziona podczas ładowania ekranu wyszukiwania.');
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>' . __('Błąd krytyczny: Nie można załadować tabeli wyszukiwania produktów.', 'Physis_zarzadzanie') . '</p></div>';
        });
    }
}


/**
 * Renderuje zawartość strony wyszukiwania produktów w panelu admina.
 * Używa wcześniej utworzonej instancji Produkt_Search_List_Table.
 */
function moj_system_renderuj_strone_wyszukiwania() {
    global $wpdb, $produkt_list_table_instance;

    // Sprawdź, czy instancja tabeli została poprawnie utworzona w moj_system_zaladuj_ekran_wyszukiwania
    if ( ! isset($produkt_list_table_instance) || ! is_object($produkt_list_table_instance) || ! ($produkt_list_table_instance instanceof Produkt_Search_List_Table) ) {
         // Jeśli instancja nie istnieje, wyświetl komunikat błędu i zakończ
         echo '<div class="wrap"><h1>' . esc_html__( 'Wyszukiwanie Produktów', 'Physis_zarzadzanie' ) . '</h1>';
         echo '<div class="notice notice-error"><p>' . __('Wystąpił błąd podczas inicjalizacji tabeli wyszukiwania. Sprawdź logi błędów.', 'Physis_zarzadzanie') . '</p></div>';
         echo '</div>';
         return; // Zakończ renderowanie strony
    }

    // --- Pobieranie wartości dla dropdownów ---
    // Producent
    $szukany_producent = isset( $_GET['s_producent'] ) ? sanitize_text_field( $_GET['s_producent'] ) : '';
    $unikalni_producenci = get_transient( 'physis_unique_producenci' );
    if ( false === $unikalni_producenci ) {
        $unikalni_producenci = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND p.post_type = %s AND p.post_status = 'publish' AND pm.meta_value IS NOT NULL AND pm.meta_value != '' ORDER BY pm.meta_value ASC",
            '_producent', 'produkt'
        ) );
        set_transient( 'physis_unique_producenci', $unikalni_producenci, HOUR_IN_SECONDS );
    }

    // Linia Marki
    $szukana_linia_marki = isset( $_GET['s_linia_marki'] ) ? sanitize_text_field( $_GET['s_linia_marki'] ) : '';
    $unikalne_linie_marki = get_transient( 'physis_unique_linie_marki' );
    if ( false === $unikalne_linie_marki ) {
        $unikalne_linie_marki = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND p.post_type = %s AND p.post_status = 'publish' AND pm.meta_value IS NOT NULL AND pm.meta_value != '' ORDER BY pm.meta_value ASC",
            '_linia_marki', 'produkt'
        ) );
        set_transient( 'physis_unique_linie_marki', $unikalne_linie_marki, HOUR_IN_SECONDS );
    }

    // Przygotuj dane dla tabeli (metoda prepare_items() jest kluczowa)
    $produkt_list_table_instance->prepare_items();

    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'Wyszukiwanie Produktów', 'Physis_zarzadzanie' ); ?></h1>

        <?php // --- Formularz Wyszukiwania --- ?>
        <form method="get" action="">
            <?php // Ukryte pola potrzebne, aby WordPress wiedział, gdzie jesteśmy ?>
            <input type="hidden" name="post_type" value="produkt">
            <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ?? 'wyszukaj-produkt-physis' ); ?>">

            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><label for="s_nazwa"><?php esc_html_e( 'Nazwa Produktu (lub fragment)', 'Physis_zarzadzanie' ); ?></label></th>
                        <td><input type="text" name="s_nazwa" id="s_nazwa" value="<?php echo esc_attr( isset( $_GET['s_nazwa'] ) ? sanitize_text_field( $_GET['s_nazwa'] ) : '' ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="s_ean"><?php esc_html_e( 'EAN (lub fragment)', 'Physis_zarzadzanie' ); ?></label></th>
                        <td><input type="text" name="s_ean" id="s_ean" value="<?php echo esc_attr( isset( $_GET['s_ean'] ) ? sanitize_text_field( $_GET['s_ean'] ) : '' ); ?>" class="regular-text"></td>
                    </tr>
                     <tr>
                        <th scope="row"><label for="s_cdt_id"><?php esc_html_e( 'CDT ID (lub fragment)', 'Physis_zarzadzanie' ); ?></label></th>
                        <td><input type="text" name="s_cdt_id" id="s_cdt_id" value="<?php echo esc_attr( isset( $_GET['s_cdt_id'] ) ? sanitize_text_field( $_GET['s_cdt_id'] ) : '' ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="s_kod_prod"><?php esc_html_e( 'Kod Producenta (lub fragment)', 'Physis_zarzadzanie' ); ?></label></th>
                        <td><input type="text" name="s_kod_prod" id="s_kod_prod" value="<?php echo esc_attr( isset( $_GET['s_kod_prod'] ) ? sanitize_text_field( $_GET['s_kod_prod'] ) : '' ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="s_producent"><?php esc_html_e( 'Producent', 'Physis_zarzadzanie' ); ?></label></th>
                        <td>
                            <select name="s_producent" id="s_producent">
                                <option value="">-- <?php esc_html_e( 'Wszyscy producenci', 'Physis_zarzadzanie' ); ?> --</option>
                                <?php
                                if ( ! empty( $unikalni_producenci ) && is_array($unikalni_producenci) ) {
                                    foreach ( $unikalni_producenci as $producent ) {
                                        if (!empty($producent)) { // Dodatkowe sprawdzenie, by pominąć puste wartości
                                            echo '<option value="' . esc_attr( $producent ) . '"' . selected( $szukany_producent, $producent, false ) . '>' . esc_html( $producent ) . '</option>';
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="s_linia_marki"><?php esc_html_e( 'Linia (Marka)', 'Physis_zarzadzanie' ); ?></label></th>
                        <td>
                            <select name="s_linia_marki" id="s_linia_marki">
                                <option value="">-- <?php esc_html_e( 'Wszystkie linie', 'Physis_zarzadzanie' ); ?> --</option>
                                <?php
                                if ( ! empty( $unikalne_linie_marki ) && is_array($unikalne_linie_marki) ) {
                                    foreach ( $unikalne_linie_marki as $linia ) {
                                         if (!empty($linia)) { // Dodatkowe sprawdzenie
                                            echo '<option value="' . esc_attr( $linia ) . '"' . selected( $szukana_linia_marki, $linia, false ) . '>' . esc_html( $linia ) . '</option>';
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php // Przycisk wyszukiwania ?>
            <?php submit_button( __( 'Szukaj Produktów', 'Physis_zarzadzanie' ), 'primary', 'szukaj_submit', false ); ?>
            <?php // Link do czyszczenia filtrów ?>
            <a href="<?php echo esc_url( admin_url('edit.php?post_type=produkt&page=wyszukaj-produkt-physis') ); ?>" class="button"><?php esc_html_e('Wyczyść', 'Physis_zarzadzanie'); ?></a>
            <?php
            // Przygotowanie bazowego URL dla przycisku eksportu
            $export_url_params = $_GET; // Pobierz bieżące parametry wyszukiwania
            $export_url_params['physis_action'] = 'export_csv'; // Dodaj akcję eksportu
            // Usuń parametry niepotrzebne dla samego eksportu (paginacja, sortowanie, submit, stare kolumny)
            unset($export_url_params['paged'], $export_url_params['orderby'], $export_url_params['order'], $export_url_params['szukaj_submit'], $export_url_params['export_cols']);
            // Zbuduj bazowy URL (bez dynamicznych parametrów export_cols[], które doda JS)
            $base_export_url = add_query_arg($export_url_params, admin_url('edit.php')); // Użyj edit.php jako bazy, bo export jest obsługiwany na admin_init
            ?>
             <?php // Dodano atrybut data-baseurl przechowujący URL bez parametrów kolumn ?>
            <a href="<?php echo esc_url( $base_export_url ); ?>" id="physis-export-button" data-baseurl="<?php echo esc_url( $base_export_url ); ?>" class="button" style="margin-left: 10px;"><?php esc_html_e('Eksportuj do CSV', 'Physis_zarzadzanie'); ?></a>
        </form>
        <?php // --- Koniec Formularza Wyszukiwania --- ?>

        <?php // --- Sekcja wyboru kolumn do eksportu --- ?>
        <div id="physis-export-options" style="margin-top: 15px;">
             <p><a href="#" id="physis-toggle-export-cols"><?php esc_html_e('Wybierz kolumny do eksportu CSV', 'Physis_zarzadzanie'); ?></a></p>
             <?php // Kontener na checkboxy, domyślnie ukryty (obsługa w JS) ?>
            <div id="physis-export-cols-checkboxes" style="display: none; border: 1px solid #ccd0d4; padding: 15px; margin-top: 5px; max-height: 250px; overflow-y: auto; column-count: 3;">
                <p><em><?php esc_html_e('Zaznacz kolumny, które chcesz uwzględnić w pliku CSV:', 'Physis_zarzadzanie'); ?></em></p>
                <?php
                // Sprawdź, czy funkcja pomocnicza istnieje
                if ( function_exists('physis_get_exportable_fields') ) {
                    $exportable_fields = physis_get_exportable_fields();
                    // Domyślnie zaznaczone kolumny
                    $default_checked = ['ID', 'post_title', '_ean', '_producent'];

                    foreach ($exportable_fields as $key => $label) {
                        // Sprawdź, czy klucz jest w domyślnie zaznaczonych
                        $checked_attr = in_array($key, $default_checked, true) ? ' checked="checked"' : '';
                        // Zablokuj i zaznacz ID oraz Tytuł
                        $disabled_attr = ($key === 'ID' || $key === 'post_title') ? ' disabled="disabled" checked="checked"' : '';

                        // Etykieta dla lepszej klikalności
                        echo '<label style="display: block; margin-bottom: 5px; break-inside: avoid-column;">';
                        // Użyj name="export_cols[]", aby PHP odebrało to jako tablicę
                        echo '<input type="checkbox" name="export_cols[]" value="' . esc_attr($key) . '"' . $checked_attr . $disabled_attr . '> ';
                        echo esc_html($label);
                        echo '</label>';
                    }
                } else {
                     // Komunikat błędu, jeśli funkcja pomocnicza nie jest dostępna
                     echo '<p class="notice notice-error">' . esc_html__('Błąd: Funkcja physis_get_exportable_fields() nie jest dostępna.', 'Physis_zarzadzanie') . '</p>';
                }
                ?>
                 <?php // Przycisk do zaznaczania/odznaczania wszystkich ?>
                 <p style="margin-top: 10px; column-span: all;"><button type="button" id="physis-select-all-cols" class="button button-small"><?php esc_html_e('Zaznacz/Odznacz wszystkie (oprócz zablokowanych)', 'Physis_zarzadzanie'); ?></button></p>
            </div>
        </div>
        <?php // --- Koniec sekcji wyboru kolumn --- ?>

        <?php // Usunięto osadzony blok <script> - logika przeniesiona do admin-scripts.js ?>

        <hr> <?php // Linia oddzielająca formularz od wyników ?>

        <?php // --- Wyświetlanie Tabeli Wyników --- ?>
        <?php // Formularz jest potrzebny dla WP_List_Table (np. bulk actions) ?>
        <form method="post" id="physis-produkt-search-results-form">
             <?php // Ukryte pole 'page' może być potrzebne dla niektórych akcji ?>
             <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page'] ?? '' ) ?>" />
             <?php // Bezpieczeństwo: Nonce dla potencjalnych przyszłych akcji masowych ?>
             <?php // $produkt_list_table_instance->search_box( __( 'Szukaj Produktów', 'Physis_zarzadzanie' ), 'produkt' ); // Można dodać pole wyszukiwania WP ?>

             <?php
             // Wyrenderuj i wyświetl tabelę z wynikami
             $produkt_list_table_instance->display();
             ?>
        </form>
         <?php // --- Koniec Tabeli Wyników --- ?>

    </div><?php // Koniec div.wrap ?>
<?php
} // Koniec funkcji moj_system_renderuj_strone_wyszukiwania

// Celowy brak zamykającego ?>