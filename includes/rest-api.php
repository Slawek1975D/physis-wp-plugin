<?php
/**
 * Dodaje obsługę filtrowania po _cdt_id dla CPT produkt ORAZ
 * Ręcznie rejestruje pola niestandardowe (meta data) za pomocą register_rest_field ORAZ
 * Rejestruje niestandardowy endpoint /physis/v1/produkt-by-cdt/{cdt_id}.
 * Wersja 1.0.7 - Dodano niestandardowy endpoint
 */

// Zabezpieczenie przed bezpośrednim dostępem
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// -----------------------------------------------------------------------------
// !!! UWAGA: Poniższy kod filtra i rejestracji parametru można będzie USUNĄĆ,
// jeśli nowy endpoint zadziała poprawnie, bo nie będziemy już ich potrzebować !!!
// -----------------------------------------------------------------------------

// --- KOD FILTRA (prawdopodobnie już niepotrzebny) ---
//function physis_filter_produkt_by_cdt_id( $args, $request ) { /* ... (stary kod filtra) ... */ return $args;}

// --- KOD REJESTRACJI PARAMETRU FILTROWANIA (prawdopodobnie już niepotrzebny) ---
//function physis_register_cdt_filter_query_param( $query_params ) { /* ... (stary kod rejestracji) ... */ return $query_params; }

// -----------------------------------------------------------------------------
// REJESTRACJA PÓL DLA STANDARDOWEGO ENDPOINTU /wp/v2/produkt
// (Nadal potrzebne, jeśli chcesz, aby te pola były dostępne także tam)
// -----------------------------------------------------------------------------

/**
 * Rejestruje pola meta dla CPT 'produkt' w standardowym REST API (/wp/v2/produkt).
 */
function physis_add_standard_rest_fields() {
    if ( ! post_type_exists('produkt') ) return;

     $meta_keys_to_register = [
        '_cdt_id', '_ean', '_kod_producenta', '_volume', '_sposob_uzycia_skrot',
        '_podmiot_odpowiedzialny', '_producent', '_linia_marki', '_opis_marketingowy',
        '_sklad', '_sposob_uzycia', '_ostrzezenia', '_tekst_etykieta',
        '_materialy_reklamowe_url', '_sklep_hebe_url', '_strona_domowa_url',
        '_tiktok_url', '_instagram_url', '_facebook_url',
    ];

    foreach ( $meta_keys_to_register as $field_name ) {
        register_rest_field( 'produkt', $field_name, array(
            'get_callback'    => 'physis_get_generic_meta_for_rest',
            'update_callback' => null,
            'schema'          => array( 'type' => 'string', 'context' => array( 'view', 'edit' ) )
        ));
    }

    register_rest_field( 'produkt', 'galeria_produktu', array(
        'get_callback'    => 'physis_get_multi_meta_for_rest',
        'update_callback' => null,
        'schema'          => array(
             'type' => 'array', 'context' => array( 'view', 'edit' ),
             'items'=> array( 'type' => 'integer' )
        )
    ));
}
// add_action( 'rest_api_init', 'physis_add_standard_rest_fields' ); // Możesz zostawić lub wykomentować


// --- CALLBACKI DLA PÓL META (nadal potrzebne) ---
function physis_get_generic_meta_for_rest( $object, $field_name, $request ) { /* ... (kod funkcji bez zmian) ... */
    if ( isset($object['id']) ) { return get_post_meta( $object['id'], $field_name, true ); } return null;
}
function physis_get_multi_meta_for_rest( $object, $field_name, $request ) { /* ... (kod funkcji bez zmian) ... */
    if ( isset($object['id']) ) { $value = get_post_meta( $object['id'], $field_name, false ); if ( is_array($value) && count($value) === 1 && $value[0] === '' ) { return []; } if ( !is_array($value) || empty($value) ) { return []; } return array_map('intval', $value); } return null;
}


// =============================================================================
// === NOWY NIESTANDARDOWY ENDPOINT ===
// =============================================================================

/**
 * Rejestruje niestandardowy endpoint REST API dla wtyczki Physis.
 */
function physis_register_custom_rest_routes() {
    $namespace = 'physis/v1'; // Przestrzeń nazw dla naszego API

    // Endpoint: /physis/v1/produkt-by-cdt/{cdt_id}
    register_rest_route( $namespace, '/produkt-by-cdt/(?P<cdt_id>[a-zA-Z0-9_.-]+)', array(
        'methods'             => WP_REST_Server::READABLE, // Tylko metoda GET
        'callback'            => 'physis_get_produkt_by_cdt_callback',
        'permission_callback' => 'physis_custom_endpoint_permission_callback', // Dedykowana funkcja uprawnień
        'args'                => array(
            'cdt_id' => array(
                'description'       => __('CDT ID produktu do pobrania.', 'Physis_zarzadzanie'),
                'type'              => 'string',
                'validate_callback' => function($param, $request, $key) {
                    // Prosta walidacja - niepusty string (można dodać regex)
                    return is_string($param) && !empty($param);
                },
                'required'          => true,
            ),
        ),
    ) );
}
add_action( 'rest_api_init', 'physis_register_custom_rest_routes' );


/**
 * Funkcja callback dla endpointu /produkt-by-cdt/{cdt_id}.
 * Pobiera produkt na podstawie meta pola _cdt_id.
 *
 * @param WP_REST_Request $request Obiekt zapytania REST.
 * @return WP_REST_Response|WP_Error Odpowiedź REST lub błąd.
 */
function physis_get_produkt_by_cdt_callback( WP_REST_Request $request ) {
    $cdt_id = $request['cdt_id']; // Pobierz cdt_id z parametrów ścieżki
    $sanitized_cdt_id = sanitize_text_field($cdt_id);

    $args = array(
        'post_type'      => 'produkt',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'meta_key'       => '_cdt_id',
        'meta_value'     => $sanitized_cdt_id,
        'meta_compare'   => '=',
        'suppress_filters' => true, // Opcjonalnie, aby uniknąć innych filtrów
    );

    $posts = get_posts( $args );

    if ( empty( $posts ) ) {
        // Nie znaleziono produktu
        return new WP_Error(
            'rest_produkt_not_found',
            __( 'Produkt o podanym CDT ID nie został znaleziony.', 'Physis_zarzadzanie' ),
            array( 'status' => 404 )
        );
    }

    // Znaleziono produkt - przygotuj dane
    $post = $posts[0];
    $post_id = $post->ID;
    $response_data = array();

    // --- Zbierz standardowe dane posta ---
    $response_data['id'] = $post_id;
    $response_data['title'] = array('rendered' => get_the_title($post_id));
    $response_data['slug'] = $post->post_name;
    $response_data['link'] = get_permalink($post_id);
    $response_data['date'] = $post->post_date;
    $response_data['date_gmt'] = $post->post_date_gmt;
    $response_data['modified'] = $post->post_modified;
    $response_data['modified_gmt'] = $post->post_modified_gmt;
    $response_data['status'] = $post->post_status;
    $response_data['type'] = $post->post_type;

    // --- Zbierz wszystkie pola meta (z listy i galerię) ---
    $meta_keys_to_get = [ // Lista pól do zwrócenia
        '_cdt_id', '_ean', '_kod_producenta', '_volume', '_sposob_uzycia_skrot',
        '_podmiot_odpowiedzialny', '_producent', '_linia_marki', '_opis_marketingowy',
        '_sklad', '_sposob_uzycia', '_ostrzezenia', '_tekst_etykieta',
        '_materialy_reklamowe_url', '_sklep_hebe_url', '_strona_domowa_url',
        '_tiktok_url', '_instagram_url', '_facebook_url',
        'galeria_produktu' // Dodajemy pole galerii
    ];

    foreach ( $meta_keys_to_get as $meta_key ) {
        if ( $meta_key === 'galeria_produktu' ) {
            // Użyj callbacku dla wielu wartości
            $response_data[$meta_key] = physis_get_multi_meta_for_rest( ['id' => $post_id], $meta_key, $request );
        } else {
            // Użyj callbacku dla pojedynczej wartości
             $response_data[$meta_key] = physis_get_generic_meta_for_rest( ['id' => $post_id], $meta_key, $request );
        }
         // Można by też użyć bezpośrednio get_post_meta, np.:
         // $response_data[$meta_key] = get_post_meta($post_id, $meta_key, ($meta_key !== 'galeria_produktu'));
    }

    // Zwróć poprawną odpowiedź
    return new WP_REST_Response( $response_data, 200 );
}


/**
 * Funkcja sprawdzająca uprawnienia dla niestandardowego endpointu Physis.
 * Wymaga poprawnego klucza API w nagłówku Authorization: Bearer.
 *
 * @param WP_REST_Request $request Obiekt zapytania REST.
 * @return bool|WP_Error True jeśli użytkownik ma dostęp, WP_Error w przeciwnym razie.
 */
function physis_custom_endpoint_permission_callback( WP_REST_Request $request ) {
    // Sprawdź, czy stała z kluczem API jest zdefiniowana
    if ( ! defined('PHYSIS_API_KEY') ) {
        return new WP_Error( 'rest_api_key_not_defined', __('Błąd konfiguracji klucza API.', 'Physis_zarzadzanie'), array( 'status' => 500 ) );
    }
     $correct_api_key = PHYSIS_API_KEY;
    if ( empty($correct_api_key) ) {
        return new WP_Error( 'rest_api_key_empty', __('Skonfigurowany klucz API jest pusty.', 'Physis_zarzadzanie'), array( 'status' => 500 ) );
    }

    // Pobierz klucz z nagłówka
    $auth_header = $request->get_header('authorization'); // Użyj metody obiektu request
    $provided_key = '';

    if ( ! empty( $auth_header ) && preg_match( '/^Bearer\s+(.*)$/i', $auth_header, $matches ) ) {
        $provided_key = $matches[1];
    }

    // Porównaj klucze
    if ( ! hash_equals( $correct_api_key, $provided_key ) ) {
        return new WP_Error( 'rest_forbidden', __('Nieprawidłowy klucz API.', 'Physis_zarzadzanie'), array( 'status' => 401 ) ); // 401 lub 403
    }

    // Klucz poprawny, dostęp dozwolony
    return true;
}
// Celowo brak zamykającego ?>