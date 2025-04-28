<?php
// Plik: includes/cpt-setup.php
// Zabezpieczenie przed bezpośrednim dostępem
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Rejestruje niestandardowy typ wpisu: Produkt.
 * USUNIĘTO: Rejestrację CPT Etykieta.
 */
function moj_system_rejestracja_cpt() {
    // --- Produkt ---
    $produkt_labels = array(
        'name'                  => _x( 'Produkty', 'Post type general name', 'Physis_zarzadzanie' ),
        'singular_name'         => _x( 'Produkt', 'Post type singular name', 'Physis_zarzadzanie' ),
        'menu_name'             => _x( 'Produkty', 'Admin Menu text', 'Physis_zarzadzanie' ),
        'name_admin_bar'        => _x( 'Produkt', 'Add New on Toolbar', 'Physis_zarzadzanie' ),
        'add_new'               => __( 'Dodaj nowy', 'Physis_zarzadzanie' ),
        'add_new_item'          => __( 'Dodaj nowy Produkt', 'Physis_zarzadzanie' ),
        'new_item'              => __( 'Nowy Produkt', 'Physis_zarzadzanie' ),
        'edit_item'             => __( 'Edytuj Produkt', 'Physis_zarzadzanie' ),
        'view_item'             => __( 'Zobacz Produkt', 'Physis_zarzadzanie' ),
        'all_items'             => __( 'Wszystkie Produkty', 'Physis_zarzadzanie' ),
        'search_items'          => __( 'Szukaj Produktów', 'Physis_zarzadzanie' ),
        'parent_item_colon'     => __( 'Produkt nadrzędny:', 'Physis_zarzadzanie' ),
        'not_found'             => __( 'Nie znaleziono Produktów.', 'Physis_zarzadzanie' ),
        'not_found_in_trash'    => __( 'Nie znaleziono Produktów w koszu.', 'Physis_zarzadzanie' )
    );
    $produkt_args = array(
        'labels'             => $produkt_labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'produkty' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'supports'           => array( 'title', 'thumbnail' ), // Zachowano title i thumbnail
        'show_in_rest'       => true,
        'menu_icon'          => 'dashicons-package',
    );
    register_post_type( 'produkt', $produkt_args );

    // --- Sekcja Etykieta USUNIĘTA ---

    // Upewnij się, że motyw wspiera obrazki wyróżniające
    add_theme_support( 'post-thumbnails' );
}
add_action( 'init', 'moj_system_rejestracja_cpt' );

// Koniec pliku - bez zamykającego ?>