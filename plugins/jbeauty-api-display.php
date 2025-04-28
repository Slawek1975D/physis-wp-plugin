<?php
/**
 * Plugin Name:       J-Beauty API Display
 * Plugin URI:        #
 * Description:       Pobiera dane z j-beauty.eu przez REST API i wyświetla je za pomocą shortcode'ów.
 * Version:           1.1.4 - Dostosowanie JS dla dużych miniaturek (500px)
 * Author:            Sławomir Dukała / Physis sp z o.o.
 * Author URI:        #
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       jbeauty-api-display
 * Domain Path:       /languages
 */

// Zabezpieczenie przed bezpośrednim dostępem
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =============================================================================
// === SHORTCODE [jbeauty_data] - Wyświetlanie pojedynczych pól ===
// =============================================================================
function physis_display_jbeauty_data_shortcode( $atts ) {
    $atts = shortcode_atts( array('cdt_id' => '','field'  => ''), $atts, 'jbeauty_data' );
    $cdt_id_value = sanitize_text_field( $atts['cdt_id'] );
    $field_key    = sanitize_key( $atts['field'] );
    if ( empty($cdt_id_value) || empty($field_key) ) return '';
    $api_url = 'https://j-beauty.eu/wp-json/physis/v1/produkt-by-cdt/' . rawurlencode($cdt_id_value);
    if (!defined('JBEAUTY_API_KEY')) { error_log("[jbeauty_data] JBEAUTY_API_KEY missing!"); return ''; }
    $api_key = JBEAUTY_API_KEY;
    $transient_key = 'jbad_prod_' . md5($cdt_id_value);
    $product_data = get_transient( $transient_key );
    if ( false === $product_data ) {
        $args = array( 'headers' => array( 'Authorization' => 'Bearer ' . $api_key ), 'timeout' => 15 );
        $response = wp_remote_get( $api_url, $args ); $product_data = null;
        if ( is_wp_error( $response ) ) { set_transient( $transient_key, 'error_fetch', MINUTE_IN_SECONDS * 5 ); return ''; }
        else { $http_code = wp_remote_retrieve_response_code( $response ); $body = wp_remote_retrieve_body( $response );
            if ( $http_code === 200 ) { $decoded_data = json_decode( $body, true );
                if ( is_array( $decoded_data ) && ! empty( $decoded_data ) ) { $product_data = $decoded_data; set_transient( $transient_key, $product_data, HOUR_IN_SECONDS ); }
                else { set_transient( $transient_key, 'error_decode', MINUTE_IN_SECONDS * 5 ); return ''; }
            } elseif ( $http_code === 404 ) { set_transient( $transient_key, 'not_found', HOUR_IN_SECONDS ); return ''; }
            else { set_transient( $transient_key, 'error_http_' . $http_code, MINUTE_IN_SECONDS * 5 ); return ''; }
        }
    }
    if ( !is_array($product_data) ) return '';
    $output_value = '';
    if ( $field_key === 'title' && isset( $product_data['title']['rendered'] ) ) { $output_value = $product_data['title']['rendered']; }
    elseif ( $field_key === 'galeria_produktu' && isset( $product_data['galeria_produktu'] ) && is_array($product_data['galeria_produktu']) ) { $output_value = implode(', ', $product_data['galeria_produktu']); }
    elseif ( isset( $product_data[ $field_key ] ) ) { if (is_array($product_data[ $field_key ])) { $output_value = ''; } else { $output_value = $product_data[ $field_key ]; } }
    else { return ''; }
    $escaped_output = ''; $html_fields = ['_opis_marketingowy', '_sklad', '_sposob_uzycia', '_ostrzezenia', '_tekst_etykieta']; $url_fields = ['_materialy_reklamowe_url', '_sklep_hebe_url', '_strona_domowa_url', '_tiktok_url', '_instagram_url', '_facebook_url', 'link'];
    if ( in_array( $field_key, $html_fields ) ) $escaped_output = wp_kses_post( $output_value );
    elseif ( in_array( $field_key, $url_fields ) ) $escaped_output = esc_url( $output_value );
    elseif ($field_key === 'galeria_produktu') $escaped_output = esc_html( $output_value );
    else $escaped_output = esc_html( $output_value );
    return $escaped_output;
}
add_shortcode( 'jbeauty_data', 'physis_display_jbeauty_data_shortcode' );

// =============================================================================
// === SHORTCODE [jbeauty_gallery] - Wyświetlanie Galerii ===
// =============================================================================
function physis_display_jbeauty_gallery_shortcode( $atts ) {
    $atts = shortcode_atts( array( 'cdt_id' => '' ), $atts, 'jbeauty_gallery' );
    $cdt_id_value = sanitize_text_field( $atts['cdt_id'] );
    if ( empty($cdt_id_value) ) return '';
    $api_url = 'https://j-beauty.eu/wp-json/physis/v1/produkt-by-cdt/' . rawurlencode($cdt_id_value);
    if (!defined('JBEAUTY_API_KEY')) { error_log("[jbeauty_gallery] JBEAUTY_API_KEY missing!"); return ''; }
    $api_key = JBEAUTY_API_KEY;
    $transient_key = 'jbad_prod_' . md5($cdt_id_value);
    $product_data = get_transient( $transient_key );
    if ( false === $product_data ) { /* ... (logika pobierania danych produktu z API i zapis do cache) ... */
        $args = array( 'headers' => array( 'Authorization' => 'Bearer ' . $api_key ), 'timeout' => 15 );
        $response = wp_remote_get( $api_url, $args ); $product_data = null;
        if ( !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200 ) {
            $product_data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( is_array($product_data) && !empty($product_data) ) { set_transient( $transient_key, $product_data, HOUR_IN_SECONDS ); }
            else { $product_data = null; }
        } else { $product_data = null; }
    }
    if ( !is_array($product_data) || empty($product_data['galeria_produktu']) || !is_array($product_data['galeria_produktu']) ) return '';
    $image_ids = $product_data['galeria_produktu'];
    $unique_gallery_id = 'jbg-' . esc_attr($cdt_id_value);
    $media_cache_key = 'jbad_media_' . md5(implode(',', $image_ids));
    $media_data = get_transient($media_cache_key);
    if ( false === $media_data ) { /* ... (logika pobierania danych mediów z API /wp/v2/media?include=... i zapis do cache) ... */
         $media_api_url = add_query_arg( array( 'include' => implode(',', $image_ids), 'per_page' => count($image_ids), '_fields' => 'id,source_url,alt_text,media_details'), 'https://j-beauty.eu/wp-json/wp/v2/media' );
         $media_args = array( 'headers' => array( 'Authorization' => 'Bearer ' . $api_key ), 'timeout' => 20 );
         $media_response = wp_remote_get( $media_api_url, $media_args ); $media_data = null;
         if ( !is_wp_error($media_response) && wp_remote_retrieve_response_code($media_response) === 200 ) {
             $decoded_media = json_decode( wp_remote_retrieve_body( $media_response ), true );
             if ( is_array( $decoded_media ) ) {
                 $media_data_map = array(); foreach ($decoded_media as $media_item) { if (isset($media_item['id'])) $media_data_map[$media_item['id']] = $media_item; }
                 $media_data = $media_data_map; set_transient( $media_cache_key, $media_data, HOUR_IN_SECONDS );
             }
         }
    }
    if ( empty($media_data) || !is_array($media_data) ) return '';
    $output = '<div class="jbeauty-gallery-wrapper ' . $unique_gallery_id . '">';
    $output .= '<div style="--swiper-navigation-color: #333; --swiper-pagination-color: #333" class="swiper jbeauty-main-swiper">';
    $output .= '<div class="swiper-wrapper">';
    foreach ( $image_ids as $image_id ) { if ( isset( $media_data[$image_id] ) ) { $media_item = $media_data[$image_id]; $full_url = $media_item['source_url'] ?? ''; $large_url = $media_item['media_details']['sizes']['large']['source_url'] ?? $full_url; $alt_text = !empty($media_item['alt_text']) ? esc_attr($media_item['alt_text']) : (isset($product_data['title']['rendered']) ? esc_attr($product_data['title']['rendered']) : ''); if (!empty($full_url) && !empty($large_url)) { $output .= '<div class="swiper-slide"><a data-fancybox="' . $unique_gallery_id . '" href="' . esc_url($full_url) . '" data-caption="' . $alt_text . '"><img src="' . esc_url($large_url) . '" alt="' . $alt_text . '" loading="lazy" /></a></div>'; } } }
    $output .= '</div>'; $output .= '<div class="swiper-button-next"></div>'; $output .= '<div class="swiper-button-prev"></div>'; $output .= '</div>';
    if (count($image_ids) > 1) { $output .= '<div thumbsSlider="" class="swiper jbeauty-thumbs-swiper">'; $output .= '<div class="swiper-wrapper">';
        foreach ( $image_ids as $image_id ) { if ( isset( $media_data[$image_id] ) ) { $media_item = $media_data[$image_id]; $thumb_url = $media_item['media_details']['sizes']['thumbnail']['source_url'] ?? ($media_item['media_details']['sizes']['medium']['source_url'] ?? $media_item['source_url']); $alt_text = !empty($media_item['alt_text']) ? esc_attr($media_item['alt_text']) : (isset($product_data['title']['rendered']) ? esc_attr($product_data['title']['rendered']) : ''); if (!empty($thumb_url)) { $output .= '<div class="swiper-slide"><img src="' . esc_url($thumb_url) . '" alt="' . $alt_text . '" loading="lazy" /></div>'; } } }
        $output .= '</div>'; $output .= '</div>'; }
    $output .= '</div>';
    return $output;
}
if (!shortcode_exists('jbeauty_gallery')) { add_shortcode( 'jbeauty_gallery', 'physis_display_jbeauty_gallery_shortcode' ); }

// =============================================================================
// === ŁADOWANIE STYLÓW I SKRYPTÓW DLA GALERII (SWIPER + FANCYBOX) ===
// =============================================================================
function physis_enqueue_gallery_assets() {
    global $post;
    if ( ! is_admin() && is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'jbeauty_gallery' ) ) {
        wp_enqueue_style( 'swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), '11.0' );
        wp_enqueue_script( 'swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', array(), '11.0', true );
        wp_enqueue_style( 'fancybox-css', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css', array(), '5.0' );
        wp_enqueue_script( 'fancybox-js', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js', array(), '5.0', true );

        // --- POCZĄTEK DODANIA INLINE SCRIPT (z modyfikacją dla dużych miniaturek) ---
        $inline_script = <<<JS
        document.addEventListener('DOMContentLoaded', function() {
            const galleryWrappers = document.querySelectorAll('.jbeauty-gallery-wrapper[class*="jbg-"]');
            galleryWrappers.forEach(wrapper => {
                const mainSwiperEl = wrapper.querySelector('.jbeauty-main-swiper');
                const thumbsSwiperEl = wrapper.querySelector('.jbeauty-thumbs-swiper');
                const nextEl = wrapper.querySelector('.swiper-button-next');
                const prevEl = wrapper.querySelector('.swiper-button-prev');
                const galleryId = wrapper.getAttribute('class').match(/jbg-[^ ]+/)[0];
                let thumbsSwiper = null;

                if (thumbsSwiperEl) {
                    thumbsSwiper = new Swiper(thumbsSwiperEl, {
                        spaceBetween: 15,          // Odstęp między miniaturkami 500px
                        slidesPerView: 'auto',     // <<< ZMIANA: Automatyczna liczba slajdów (CSS ustali szerokość)
                        freeMode: true,            // Płynne przewijanie
                        watchSlidesProgress: true,
                        centeredSlides: false,     // <<< ZMIANA: Wyłączone centrowanie dla 'auto'
                        slideToClickedSlide: true, // Nadal przesuwaj do klikniętego
                    });
                }

                if (mainSwiperEl) {
                    const mainSwiper = new Swiper(mainSwiperEl, {
                        spaceBetween: 10,
                        loop: true, // Włącz zapętlanie dla głównego slidera
                        navigation: { nextEl: nextEl, prevEl: prevEl },
                        thumbs: { swiper: thumbsSwiper }, // Połącz z miniaturkami
                    });
                }
            }); // Koniec forEach

            Fancybox.bind('[data-fancybox^="jbg-"]', {
                // Opcje Fancybox
                 Toolbar: {
                    display: {
                        left: ["infobar"],
                        middle: [],
                        right: ["slideshow", "thumbs", "close"], // Poprawiona konfiguracja przycisków
                    }
                 },
                 Thumbs: {
                    type: "classic" // Klasyczny wygląd miniaturek w Fancybox
                 }
            });
        }); // Koniec DOMContentLoaded
JS;
        wp_add_inline_script( 'fancybox-js', $inline_script );
        // --- KONIEC DODANIA INLINE SCRIPT ---
    }
}
add_action( 'wp_enqueue_scripts', 'physis_enqueue_gallery_assets' );

// Celowo brak zamykającego ?>