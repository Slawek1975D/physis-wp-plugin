<?php
// Plik: includes/helpers.php (z logowaniem)


// Zabezpieczenie przed bezpośrednim dostępem
if ( ! defined( 'ABSPATH' ) ) {
    error_log('DEBUG Physis: helpers.php - ABSPATH not defined, exiting.'); // Log 2: Powinno się pojawić tylko przy bezpośrednim wywołaniu pliku (błąd)
    exit;
}

/**
 * Zwraca tablicę pól dostępnych do eksportu [klucz => etykieta]
 *
 * @return array Tablica pól eksportowalnych.
 */
function physis_get_exportable_fields() {
    $fields = array(
        'ID' => __('ID', 'Physis_zarzadzanie'),
        'post_title' => __('Nazwa Produktu', 'Physis_zarzadzanie'),
        '_cdt_id' => __('CDT_ID', 'Physis_zarzadzanie'),
        '_ean' => __('EAN', 'Physis_zarzadzanie'),
        '_kod_producenta' => __('Kod Producenta', 'Physis_zarzadzanie'),
        '_producent' => __('Producent', 'Physis_zarzadzanie'),
        '_linia_marki' => __('Linia (Marka)', 'Physis_zarzadzanie'),
        '_volume' => __('Volume', 'Physis_zarzadzanie'),
        '_opis_marketingowy' => __('Opis Marketingowy', 'Physis_zarzadzanie'),
        '_sklad' => __('Skład', 'Physis_zarzadzanie'),
        '_sposob_uzycia' => __('Sposób Użycia', 'Physis_zarzadzanie'),
        '_sposob_uzycia_skrot' => __('Sposób Użycia Skrót', 'Physis_zarzadzanie'),
        '_ostrzezenia' => __('Ostrzeżenia', 'Physis_zarzadzanie'),
        '_podmiot_odpowiedzialny' => __('Podmiot Odpowiedzialny', 'Physis_zarzadzanie'),
        '_tekst_etykieta' => __('Tekst Etykieta', 'Physis_zarzadzanie'),
        '_materialy_reklamowe_url' => __('URL Materiały Rekl.', 'Physis_zarzadzanie'),
        '_sklep_hebe_url' => __('URL Hebe', 'Physis_zarzadzanie'),
        '_strona_domowa_url' => __('URL Strona Dom.', 'Physis_zarzadzanie'),
        '_tiktok_url' => __('URL TikTok', 'Physis_zarzadzanie'),
        '_instagram_url' => __('URL Instagram', 'Physis_zarzadzanie'),
        '_facebook_url' => __('URL Facebook', 'Physis_zarzadzanie')
    );
    return $fields;
}