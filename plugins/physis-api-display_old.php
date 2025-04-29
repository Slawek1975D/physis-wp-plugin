<?php
/**
 * Plugin Name:       Physis API Display
 * Plugin URI:        #
 * Description:       Pobiera dane z API systemu Physis i wyświetla je za pomocą shortcode'ów.
 * Version:           1.2.2 - Dodano ładowanie lokalnych stylów CSS galerii.
 * Author:            Sławomir Dukała / Physis sp z o.o.
 * Author URI:        #
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       physis-api-display
 * Domain Path:       /languages
 */

// Zabezpieczenie przed bezpośrednim dostępem
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Definicja stałych dla spójności
define( 'PAPI_PLUGIN_VERSION', '1.2.2' );
define( 'PAPI_PLUGIN_FILE', __FILE__ ); // Definicja ścieżki do głównego pliku
define( 'PAPI_PLUGIN_DIR', plugin_dir_path( PAPI_PLUGIN_FILE ) ); // Definicja ścieżki do katalogu wtyczki
define( 'PAPI_OPTION_NAME', 'papi_api_options' ); // Nazwa opcji w bazie danych
define( 'PAPI_SETTINGS_SLUG', 'physis-api-settings' ); // Slug strony ustawień
define( 'PAPI_SETTINGS_GROUP', 'papi_api_settings_group' ); // Nazwa grupy ustawień

// =============================================================================
// === FUNKCJE POMOCNICZE ===
// =============================================================================

/**
 * Pobiera i zwraca zapisane opcje wtyczki.
 *
 * @return array Tablica z opcjami ('api_url', 'api_key') lub pusta tablica.
 */
function papi_get_options() {
    static $options = null;
    if ( $options === null ) {
        $options = get_option( PAPI_OPTION_NAME, array() );
    }
    return $options;
}

/**
 * Wykonuje zapytanie GET do API, obsługuje cache (transienty) i błędy.
 * !! WERSJA POPRAWIONA - z mapowaniem danych mediów !!
 *
 * @param string $endpoint_path Ścieżka endpointu API (np. '/produkt-by-cdt/XYZ' lub '/media?include=1,2,3').
 * @param int $cache_duration Czas przechowywania cache w sekundach. Domyślnie 1 godzina.
 * @param bool $is_media_query Czy to zapytanie do endpointu /media WordPressa?
 * @return array|WP_Error Dane z API jako tablica (asocjacyjna dla mediów) lub obiekt WP_Error w przypadku błędu.
 */
function papi_fetch_api_data( $endpoint_path, $cache_duration = HOUR_IN_SECONDS, $is_media_query = false ) {
    $options = papi_get_options();
    $api_base_url = $options['api_url'] ?? '';
    $api_key      = $options['api_key'] ?? '';

    if ( empty( $api_base_url ) || empty( $api_key ) ) {
        error_log( "[Physis API Display] API URL or Key is not configured in settings for endpoint: " . $endpoint_path );
        return new WP_Error( 'papi_config_error', __( 'API URL or Key not configured in plugin settings.', 'physis-api-display' ) );
    }

    // Zbuduj pełny URL zapytania
    if ($is_media_query) {
        $parsed_url = wp_parse_url($api_base_url);
        $media_api_host = ($parsed_url['scheme'] ?? 'https') . '://' . ($parsed_url['host'] ?? '');
        if (!empty($parsed_url['port'])) { $media_api_host .= ':' . $parsed_url['port']; }
        $full_api_url = $media_api_host . '/wp-json/wp/v2' . $endpoint_path;
    } else {
        $full_api_url = $api_base_url . $endpoint_path;
    }

    $transient_key = 'papi_cache_' . md5( $full_api_url );
    $cached_data = get_transient( $transient_key );

    if ( false !== $cached_data ) {
        if (is_string($cached_data) && strpos($cached_data, 'error_') === 0) {
            return new WP_Error($cached_data, 'Error previously cached.');
        } elseif ($cached_data === 'not_found') {
             return new WP_Error('papi_not_found', 'Resource not found (cached).');
        }
        return $cached_data;
    }

    $args = array(
        'headers' => array( 'Authorization' => 'Bearer ' . $api_key ),
        'timeout' => $is_media_query ? 20 : 15
    );
    $response = wp_remote_get( $full_api_url, $args );

    if ( is_wp_error( $response ) ) {
        error_log( "[Physis API Display] API Fetch Error for " . esc_url($full_api_url) . ": " . $response->get_error_message() );
        set_transient( $transient_key, 'error_fetch', MINUTE_IN_SECONDS * 5 );
        return $response;
    } else {
        $http_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( $http_code === 200 ) {
            $decoded_data = json_decode( $body, true );
            if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_data ) ) {
                if ($is_media_query) {
                    $media_data_map = array();
                    foreach ($decoded_data as $media_item) {
                        if (isset($media_item['id'])) {
                            $media_data_map[$media_item['id']] = $media_item;
                        }
                    }
                    set_transient( $transient_key, $media_data_map, $cache_duration );
                    return $media_data_map;
                } else {
                     set_transient( $transient_key, $decoded_data, $cache_duration );
                     return $decoded_data;
                }
            } else {
                error_log( "[Physis API Display] API Decode Error for " . esc_url($full_api_url) . ". JSON Error: " . json_last_error_msg() . ". Body: " . substr($body, 0, 500) );
                set_transient( $transient_key, 'error_decode', MINUTE_IN_SECONDS * 5 );
                return new WP_Error( 'papi_decode_error', __( 'Error decoding API response.', 'physis-api-display' ) );
            }
        } elseif ( $http_code === 404 ) {
            error_log( "[Physis API Display] API Not Found (404) for " . esc_url($full_api_url) );
            set_transient( $transient_key, 'not_found', $cache_duration );
            return new WP_Error( 'papi_not_found', __( 'Resource not found.', 'physis-api-display' ) );
        } else {
            error_log( "[Physis API Display] API HTTP Error for " . esc_url($full_api_url) . ". Code: " . $http_code . ". Body: " . substr($body, 0, 500));
            set_transient( $transient_key, 'error_http_' . $http_code, MINUTE_IN_SECONDS * 5 );
            return new WP_Error( 'papi_http_error', sprintf( __( 'API HTTP Error: %d', 'physis-api-display' ), $http_code ) );
        }
    }
}


// =============================================================================
// === SHORTCODE [papi_data] - Wyświetlanie pojedynczych pól ===
// =============================================================================

function papi_display_data_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'cdt_id' => '',
        'field'  => ''
    ), $atts, 'papi_data' );

    $cdt_id_value = sanitize_text_field( $atts['cdt_id'] );
    $field_key    = sanitize_key( $atts['field'] );

    if ( empty($cdt_id_value) || empty($field_key) ) {
        return '';
    }

    $endpoint_path = '/produkt-by-cdt/' . rawurlencode($cdt_id_value);
    $product_data = papi_fetch_api_data( $endpoint_path );

    if ( is_wp_error( $product_data ) ) {
        if ( $product_data->get_error_code() === 'papi_config_error' && current_user_can('manage_options') ) {
            return '<p style="color:red;">' . esc_html( $product_data->get_error_message() ) . '</p>';
        }
        return '';
    }
    if (!is_array($product_data)){
         return '';
    }

    $output_value = null;
    if ( $field_key === 'title' && isset( $product_data['title']['rendered'] ) ) {
        $output_value = $product_data['title']['rendered'];
    } elseif ( $field_key === 'galeria_produktu' ) {
         return '';
    } elseif ( isset( $product_data[ $field_key ] ) ) {
        if ( is_array( $product_data[ $field_key ] ) ) {
             $output_value = null;
        } else {
            $output_value = $product_data[ $field_key ];
        }
    }

    if ( $output_value === null ) {
        return '';
    }

    $escaped_output = '';
    $html_fields = ['_opis_marketingowy', '_sklad', '_sposob_uzycia', '_ostrzezenia', '_tekst_etykieta'];
    $url_fields = ['_materialy_reklamowe_url', '_sklep_hebe_url', '_strona_domowa_url', '_tiktok_url', '_instagram_url', '_facebook_url', 'link'];

    if ( in_array( $field_key, $html_fields, true ) ) {
        $escaped_output = wp_kses_post( $output_value );
    } elseif ( in_array( $field_key, $url_fields, true ) ) {
        $escaped_output = esc_url( $output_value );
    } else {
        $escaped_output = esc_html( $output_value );
    }

    return $escaped_output;
}
add_shortcode( 'papi_data', 'papi_display_data_shortcode' );


// =============================================================================
// === SHORTCODE [papi_gallery] - Wyświetlanie Galerii ===
// =============================================================================

function papi_display_gallery_shortcode( $atts ) {
    $atts = shortcode_atts( array( 'cdt_id' => '' ), $atts, 'papi_gallery' );
    $cdt_id_value = sanitize_text_field( $atts['cdt_id'] );
    if ( empty($cdt_id_value) ) return '';

    // Pobierz dane produktu
    $product_endpoint = '/produkt-by-cdt/' . rawurlencode($cdt_id_value);
    $product_data = papi_fetch_api_data( $product_endpoint );

    // Sprawdź błąd, brak danych produktu lub brak pola galerii
    if ( is_wp_error( $product_data ) || !is_array( $product_data ) || empty( $product_data['galeria_produktu'] ) || !is_array( $product_data['galeria_produktu'] ) ) {
        if ( is_wp_error( $product_data ) && $product_data->get_error_code() === 'papi_config_error' && current_user_can('manage_options') ) {
             return '<p style="color:red;">' . esc_html( $product_data->get_error_message() ) . '</p>';
        }
        return '';
    }

    $image_ids = $product_data['galeria_produktu'];
    if ( empty( $image_ids ) ) {
        return '';
    }

    // Pobierz dane mediów
    $media_endpoint = '/media?include=' . implode(',', $image_ids) . '&per_page=' . count($image_ids) . '&_fields=id,source_url,alt_text,media_details';
    $media_data_map = papi_fetch_api_data( $media_endpoint, HOUR_IN_SECONDS, true );

    // Sprawdź błąd lub brak danych mediów
    if ( is_wp_error( $media_data_map ) || !is_array($media_data_map) || empty($media_data_map) ) {
          if ( is_wp_error( $media_data_map ) && $media_data_map->get_error_code() === 'papi_config_error' && current_user_can('manage_options') ) {
             return '<p style="color:red;">' . esc_html( $media_data_map->get_error_message() ) . '</p>';
         }
        return '';
    }

    // --- Generowanie HTML Galerii ---
    $unique_gallery_id = 'papi-gal-' . esc_attr($cdt_id_value) . '-' . wp_rand(100,999);
    $output = '<div class="physis-api-gallery-wrapper ' . $unique_gallery_id . '">'; // Klasa główna
    $output .= '<div style="--swiper-navigation-color: #333; --swiper-pagination-color: #333" class="swiper papi-main-swiper">'; // Klasa Swipera
    $output .= '<div class="swiper-wrapper">';

    $valid_images_count = 0;
    foreach ( $image_ids as $image_id ) {
        if ( isset( $media_data_map[$image_id] ) ) {
            $media_item = $media_data_map[$image_id];
            $full_url = $media_item['source_url'] ?? '';
            $large_url = $media_item['media_details']['sizes']['large']['source_url'] ?? $full_url;
            $alt_text = !empty($media_item['alt_text']) ? esc_attr($media_item['alt_text']) : ($product_data['title']['rendered'] ?? '');

            if ( !empty($full_url) && !empty($large_url) ) {
                $valid_images_count++;
                $output .= '<div class="swiper-slide">';
                $output .= '<a data-fancybox="' . $unique_gallery_id . '" href="' . esc_url($full_url) . '" data-caption="' . esc_attr($alt_text) . '">';
                $output .= '<img src="' . esc_url($large_url) . '" alt="' . esc_attr($alt_text) . '" loading="lazy" />';
                $output .= '</a></div>';
            }
        }
    }
    $output .= '</div>'; // Zamknij .swiper-wrapper dla głównego slidera

    // Dodaj przyciski i zamknij główny slider, jeśli były jakieś obrazy
    if ($valid_images_count > 0) {
        if ($valid_images_count > 1) {
            $output .= '<div class="swiper-button-next"></div>';
            $output .= '<div class="swiper-button-prev"></div>';
        }
        $output .= '</div>'; // Zamknij .papi-main-swiper

        // Generuj miniaturki tylko jeśli > 1 obrazek
        if ($valid_images_count > 1) {
            $output .= '<div thumbsSlider="" class="swiper papi-thumbs-swiper">'; // Klasa Swipera miniaturek
            $output .= '<div class="swiper-wrapper">';
            foreach ( $image_ids as $image_id ) {
                 if ( isset( $media_data_map[$image_id] ) ) {
                    $media_item = $media_data_map[$image_id];
                    $thumb_url = $media_item['media_details']['sizes']['thumbnail']['source_url']
                              ?? ($media_item['media_details']['sizes']['medium']['source_url']
                              ?? $media_item['source_url']);
                    $alt_text = !empty($media_item['alt_text']) ? esc_attr($media_item['alt_text']) : ($product_data['title']['rendered'] ?? '');
                    if (!empty($thumb_url)) {
                         $output .= '<div class="swiper-slide"><img src="' . esc_url($thumb_url) . '" alt="' . esc_attr($alt_text) . '" loading="lazy" /></div>';
                    }
                 }
            }
            $output .= '</div>'; // Zamknij .swiper-wrapper dla miniaturek
            $output .= '</div>'; // Zamknij .papi-thumbs-swiper
        }
    } else {
        $output .= '</div>'; // Zamknij .papi-main-swiper
        $output = ''; // Resetuj, bo nie było obrazków
    }

    // Zamknij główny wrapper galerii, tylko jeśli $output nie jest pusty
    if ( ! empty( $output ) ) {
        $output .= '</div>'; // Zamknij .physis-api-gallery-wrapper
    }

    return $output;
}
if (!shortcode_exists('papi_gallery')) {
    add_shortcode( 'papi_gallery', 'papi_display_gallery_shortcode' );
}


// =============================================================================
// === ŁADOWANIE STYLÓW I SKRYPTÓW DLA GALERII (SWIPER + FANCYBOX) ===
// =============================================================================
function papi_enqueue_gallery_assets() {
    global $post;
    if ( ! is_admin() && is_a( $post, 'WP_Post' ) && !empty($post->post_content) && has_shortcode( $post->post_content, 'papi_gallery' ) ) {

        $swiper_version = '11.0.5';
        $fancybox_version = '5.0.36';
        $plugin_version = PAPI_PLUGIN_VERSION; // Użyj stałej wersji wtyczki

        // Ładowanie stylów Swiper i Fancybox z CDN
        wp_enqueue_style( 'swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@' . $swiper_version . '/swiper-bundle.min.css', array(), $swiper_version );
        wp_enqueue_style( 'fancybox-css', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@' . $fancybox_version . '/dist/fancybox/fancybox.css', array(), $fancybox_version );

        // --- DODANO: Ładowanie lokalnego pliku CSS dla galerii ---
        $gallery_css_path = 'assets/css/gallery-styles.css';
        if ( file_exists( PAPI_PLUGIN_DIR . $gallery_css_path ) ) {
            wp_enqueue_style(
                'physis-api-gallery-styles', // Unikalny uchwyt dla stylów
                plugin_dir_url( PAPI_PLUGIN_FILE ) . $gallery_css_path,
                array('swiper-css', 'fancybox-css'), // Zależności
                filemtime( PAPI_PLUGIN_DIR . $gallery_css_path ) // Wersja pliku dla cache busting
            );
        }
        // --- KONIEC DODAWANIA LOKALNEGO CSS ---

        // Ładowanie skryptów Swiper i Fancybox z CDN
        wp_enqueue_script( 'swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@' . $swiper_version . '/swiper-bundle.min.js', array(), $swiper_version, true );
        wp_enqueue_script( 'fancybox-js', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@' . $fancybox_version . '/dist/fancybox/fancybox.umd.js', array(), $fancybox_version, true );

        // Skrypt inicjalizujący
        $inline_script = <<<JS
        document.addEventListener('DOMContentLoaded', function() {
            const galleryWrappers = document.querySelectorAll('.physis-api-gallery-wrapper[class*="papi-gal-"]'); // Używa nowej klasy
            galleryWrappers.forEach(wrapper => {
                const mainSwiperEl = wrapper.querySelector('.papi-main-swiper'); // Używa nowej klasy
                const thumbsSwiperEl = wrapper.querySelector('.papi-thumbs-swiper'); // Używa nowej klasy
                const nextEl = wrapper.querySelector('.swiper-button-next');
                const prevEl = wrapper.querySelector('.swiper-button-prev');
                const uniqueId = wrapper.getAttribute('class').match(/papi-gal-[^ ]+/)[0];

                let thumbsSwiper = null;
                if (thumbsSwiperEl) {
                    thumbsSwiper = new Swiper(thumbsSwiperEl, {
                        spaceBetween: 10, slidesPerView: 'auto', freeMode: true,
                        watchSlidesProgress: true, slideToClickedSlide: true,
                    });
                }
                if (mainSwiperEl) {
                    const mainSwiperConfig = { spaceBetween: 10, loop: true, navigation: { nextEl: nextEl, prevEl: prevEl } };
                    if (thumbsSwiper) { mainSwiperConfig.thumbs = { swiper: thumbsSwiper }; }
                    const mainSwiper = new Swiper(mainSwiperEl, mainSwiperConfig);
                }
            }); // Koniec forEach

            Fancybox.bind('[data-fancybox^="papi-gal-"]', { // Używa nowego prefixu
                 Toolbar: { display: { left: ["infobar"], middle: [], right: ["slideshow", "thumbs", "close"] } },
                 Thumbs: { type: "classic" }
             });
        }); // Koniec DOMContentLoaded
JS;
        // Dołącz inline script po jednym z głównych skryptów (np. fancybox-js)
        // Upewnij się, że uchwyt 'fancybox-js' jest poprawny
        wp_add_inline_script( 'fancybox-js', $inline_script );
    }
}
add_action( 'wp_enqueue_scripts', 'papi_enqueue_gallery_assets' );


// =============================================================================
// === SEKCJA USTAWIEŃ WTYCZKI (Rejestracja, Renderowanie) ===
// =============================================================================

/**
 * Dodaje stronę ustawień Physis API Display do menu Ustawienia.
 */
function papi_add_settings_page() {
    add_options_page(
        __( 'Physis API Display Settings', 'physis-api-display' ),
        __( 'Physis API Display', 'physis-api-display' ),
        'manage_options',
        PAPI_SETTINGS_SLUG,
        'papi_render_settings_page'
    );
}
add_action( 'admin_menu', 'papi_add_settings_page' );

/**
 * Renderuje zawartość (HTML) strony ustawień.
 */
function papi_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( PAPI_SETTINGS_GROUP );
            do_settings_sections( PAPI_SETTINGS_SLUG );
            submit_button( __( 'Save Settings', 'physis-api-display' ) );
            ?>
        </form>
    </div>
    <?php
}

/**
 * Rejestruje ustawienia wtyczki Physis API Display.
 */
function papi_register_settings() {
    register_setting( PAPI_SETTINGS_GROUP, PAPI_OPTION_NAME, 'papi_sanitize_options' );
    add_settings_section( 'papi_connection_section', __( 'Connection Settings', 'physis-api-display' ), 'papi_connection_section_callback', PAPI_SETTINGS_SLUG );
    add_settings_field( 'papi_api_url', __( 'Physis API Base URL', 'physis-api-display' ), 'papi_api_url_callback', PAPI_SETTINGS_SLUG, 'papi_connection_section' );
    add_settings_field( 'papi_api_key', __( 'API Key', 'physis-api-display' ), 'papi_api_key_callback', PAPI_SETTINGS_SLUG, 'papi_connection_section' );
}
add_action( 'admin_init', 'papi_register_settings' );

/**
 * Wyświetla opis dla sekcji ustawień połączenia.
 */
function papi_connection_section_callback() {
    echo '<p>' . __( 'Enter the connection details for the Physis API endpoint.', 'physis-api-display' ) . '</p>';
}

/**
 * Renderuje pole input dla adresu URL API.
 */
function papi_api_url_callback() {
    $options = papi_get_options();
    $url = $options['api_url'] ?? '';
    echo '<input type="url" id="papi_api_url" name="' . PAPI_OPTION_NAME . '[api_url]" value="' . esc_url($url) . '" class="regular-text" placeholder="https://your-physis-site.com/wp-json/physis/v1" />';
    echo '<p class="description">' . __( 'Enter the full base URL for the Physis API endpoint (e.g., https://your-physis-site.com/wp-json/physis/v1). No trailing slash.', 'physis-api-display') . '</p>';
}

/**
 * Renderuje pole input dla klucza API.
 */
function papi_api_key_callback() {
    $options = papi_get_options();
    $key = $options['api_key'] ?? '';
    echo '<input type="password" id="papi_api_key" name="' . PAPI_OPTION_NAME . '[api_key]" value="' . esc_attr($key) . '" class="regular-text" />';
}

/**
 * Funkcja do sanityzacji i walidacji opcji przed zapisem.
 * Dodano test połączenia z API.
 */
function papi_sanitize_options( $input ) {
    $sanitized_input = array();
    $options = papi_get_options(); // Pobierz stare opcje na wypadek błędu

    // Sanityzuj URL
    if ( isset( $input['api_url'] ) ) {
        $url = esc_url_raw( trim( $input['api_url'] ), array('http', 'https') );
        $sanitized_input['api_url'] = rtrim($url, '/');
    } else {
        $sanitized_input['api_url'] = '';
    }

    // Sanityzuj klucz API
    if ( isset( $input['api_key'] ) ) {
        $sanitized_input['api_key'] = sanitize_text_field( trim( $input['api_key'] ) );
    } else {
        $sanitized_input['api_key'] = '';
    }

    // --- TEST POŁĄCZENIA ---
    if ( ! empty( $sanitized_input['api_url'] ) && ! empty( $sanitized_input['api_key'] ) ) {
        $test_result = papi_test_api_connection( $sanitized_input['api_url'], $sanitized_input['api_key'] );
        if ( is_wp_error( $test_result ) ) {
            $error_code = $test_result->get_error_code();
            $error_message = $test_result->get_error_message();
            add_settings_error(
                PAPI_OPTION_NAME, // Slug ustawienia, powiązany z nazwą opcji
                esc_attr( 'papi_connection_failed_' . $error_code ),
                sprintf( __( 'API Connection Test Failed: %s', 'physis-api-display' ), $error_message ),
                'error'
            );
            // Zwróć stare opcje, aby nie zapisać błędnych danych
            return $options;
        } else {
             add_settings_error(
                 PAPI_OPTION_NAME, // Slug komunikatu
                 esc_attr( 'papi_connection_success' ),
                 __( 'API Connection Test Successful!', 'physis-api-display' ),
                 'updated' // 'updated' to zielony komunikat
             );
        }
    } elseif ( empty( $sanitized_input['api_url'] ) || empty( $sanitized_input['api_key'] ) ) {
        add_settings_error(
            PAPI_OPTION_NAME, // Slug ostrzeżenia
            esc_attr( 'papi_fields_required' ),
            __( 'Both API Base URL and API Key are required for the plugin to function.', 'physis-api-display' ),
            'warning'
        );
    }

    // Zwróć oczyszczone dane do zapisania
    return $sanitized_input;
}


/**
 * Funkcja pomocnicza do testowania połączenia z API Physis.
 */
function papi_test_api_connection( $api_base_url, $api_key ) {
    $test_endpoint_path = '/produkt-by-cdt/connection-test'; // Testowy/nieistniejący produkt
    $test_url = $api_base_url . $test_endpoint_path;

    $args = array(
        'headers' => array( 'Authorization' => 'Bearer ' . $api_key ),
        'timeout' => 10
    );
    $response = wp_remote_get( $test_url, $args );

    if ( is_wp_error( $response ) ) {
        return $response; // Zwróć błąd połączenia
    } else {
        $http_code = wp_remote_retrieve_response_code( $response );
        if ( $http_code >= 200 && $http_code < 500 ) { // Oczekujemy 200, 404, 401, 403
            if ($http_code === 401 || $http_code === 403) {
                 return new WP_Error('papi_invalid_key', __( 'Invalid API Key.', 'physis-api-display' ));
            }
            return true; // Połączenie udane (nawet jeśli produkt testowy nie istnieje - 404)
        } else {
             error_log("[Physis API Display] API Connection Test HTTP Error: " . $http_code);
             return new WP_Error('papi_test_http_error', sprintf( __( 'Server responded with code: %d', 'physis-api-display' ), $http_code ) );
        }
    }
}


/**
 * Dodaje link do ustawień na liście wtyczek.
 */
function papi_add_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page=' . PAPI_SETTINGS_SLUG . '">' . __( 'Settings', 'physis-api-display' ) . '</a>';
    array_unshift( $links, $settings_link ); // Dodaj na początku listy
    return $links;
}
$plugin_basename = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin_basename", 'papi_add_settings_link' );


// Celowo brak zamykającego ?>