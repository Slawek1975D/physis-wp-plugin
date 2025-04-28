<?php
// Plik: includes/admin-columns-produkt.php
// Zabezpieczenie przed bezpośrednim dostępem
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Modyfikuje kolumny wyświetlane na liście produktów w panelu admina.
 * Dodaje: Miniatura, Linia (Marka), Kod Prod., Opis Marketingowy (skrót), Skład (skrót).
 * Usuwa: CDT ID.
 * Zachowuje: Checkbox, Nazwa (Tytuł), Data.
 *
 * @param array $columns Domyślna tablica kolumn.
 * @return array Zmodyfikowana tablica kolumn.
 */
function physis_manage_produkt_columns( $columns ) {
    // Usuń niechciane kolumny
    unset($columns['produkt_cdt_id']);

    // Stwórz nową tablicę w pożądanej kolejności
    $new_columns = array();
    $new_columns['cb'] = $columns['cb']; // Checkbox
    $new_columns['produkt_thumbnail'] = __( 'Miniatura', 'Physis_zarzadzanie' ); // <<< DODANO MINIATURĘ
    $new_columns['title'] = __( 'Nazwa Produktu', 'Physis_zarzadzanie' ); // Standardowa kolumna Tytuł = Nazwa
    $new_columns['produkt_linia_marki'] = __( 'Linia (Marka)', 'Physis_zarzadzanie' );
    $new_columns['produkt_kod_prod'] = __( 'Kod Prod.', 'Physis_zarzadzanie' );
    $new_columns['produkt_opis_marketingowy'] = __( 'Opis Marketingowy (skrót)', 'Physis_zarzadzanie' );
    $new_columns['produkt_sklad'] = __( 'Skład (skrót)', 'Physis_zarzadzanie' );

    // Dodaj z powrotem kolumnę daty na końcu (jeśli istniała)
    if (isset($columns['date'])) {
        $new_columns['date'] = $columns['date'];
    }

    return $new_columns;
}
add_filter( 'manage_edit-produkt_columns', 'physis_manage_produkt_columns' );

/**
 * Wyświetla zawartość dla niestandardowych kolumn na liście produktów.
 *
 * @param string $column_name Nazwa aktualnie przetwarzanej kolumny.
 * @param int $post_id ID aktualnego posta (produktu).
 */
function physis_manage_produkt_custom_column( $column_name, $post_id ) {
    switch ( $column_name ) {
        case 'produkt_thumbnail': // <<< DODANO OBSŁUGĘ MINIATURY
            if ( has_post_thumbnail( $post_id ) ) {
                // Wyświetl miniaturkę o rozmiarze 50x50 pikseli
                echo get_the_post_thumbnail( $post_id, array( 50, 50 ) );
            } else {
                // Wyświetl ikonę zastępczą (Dashicon) jeśli brak obrazka
                // Możesz dostosować ikonę (np. dashicons-format-image, dashicons-no) i style
                echo '<span class="dashicons dashicons-format-image" style="font-size: 36px; width: 50px; height: 50px; color: #ccc; display: inline-block; text-align: center; line-height: 50px; vertical-align: middle;"></span>';
            }
            break;

        case 'produkt_linia_marki':
            $linia_marki = get_post_meta( $post_id, '_linia_marki', true );
            echo esc_html( $linia_marki );
            break;

        case 'produkt_kod_prod':
            $kod_producenta = get_post_meta( $post_id, '_kod_producenta', true );
            echo esc_html( $kod_producenta );
            break;

        case 'produkt_opis_marketingowy':
            $opis = get_post_meta( $post_id, '_opis_marketingowy', true );
            echo esc_html( wp_trim_words( strip_tags($opis), 15, '...' ) );
            break;

        case 'produkt_sklad':
            $sklad = get_post_meta( $post_id, '_sklad', true );
            echo esc_html( wp_trim_words( strip_tags($sklad), 15, '...' ) );
            break;
    }
}
add_action( 'manage_produkt_posts_custom_column', 'physis_manage_produkt_custom_column', 10, 2 );


/**
 * Rejestruje niestandardowe kolumny jako sortowalne
 * na liście produktów w panelu admina.
 * (Miniaturka nie jest sortowalna)
 *
 * @param array $columns Tablica sortowalnych kolumn.
 * @return array Zmodyfikowana tablica kolumn.
 */
function physis_manage_produkt_sortable_columns( $columns ) {
    $columns['produkt_linia_marki'] = '_linia_marki';
    $columns['produkt_kod_prod'] = '_kod_producenta';
    // Pomijamy sortowanie dla Miniaturki, Opisu Marketingowego i Składu

    return $columns;
}
add_filter( 'manage_edit-produkt_sortable_columns', 'physis_manage_produkt_sortable_columns' );

/**
 * Modyfikuje zapytanie WP_Query w panelu admina, aby poprawnie obsłużyć sortowanie
 * po niestandardowych kolumnach (wg wartości pól meta).
 *
 * @param WP_Query $query Obiekt zapytania WordPress.
 */
function physis_admin_sort_query( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() || $query->get('post_type') !== 'produkt' ) {
        return;
    }

    $orderby = $query->get( 'orderby' );

    if ( '_linia_marki' === $orderby ) {
        $query->set( 'meta_key', '_linia_marki' );
        $query->set( 'orderby', 'meta_value' );
    } elseif ( '_kod_producenta' === $orderby ) {
        $query->set( 'meta_key', '_kod_producenta' );
        $query->set( 'orderby', 'meta_value' );
    }
}
add_action( 'pre_get_posts', 'physis_admin_sort_query' );

// Koniec pliku - bez zamykającego ?>