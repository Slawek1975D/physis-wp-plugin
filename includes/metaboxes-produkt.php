<?php
// Zabezpieczenie przed bezpośrednim dostępem
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Generuje HTML dla meta boxa Produktu.
 */
function moj_system_produkt_meta_box_html( $post ) {
    wp_nonce_field( 'moj_system_produkt_save_meta_data', 'moj_system_produkt_nonce' );
    $cdt_id = get_post_meta( $post->ID, '_cdt_id', true );
    $ean = get_post_meta( $post->ID, '_ean', true );
    $kod_producenta = get_post_meta( $post->ID, '_kod_producenta', true );
    $producent = get_post_meta( $post->ID, '_producent', true );
    $linia_marki = get_post_meta( $post->ID, '_linia_marki', true ); // Dodane
    $volume = get_post_meta( $post->ID, '_volume', true );
    $opis_marketingowy = get_post_meta( $post->ID, '_opis_marketingowy', true );
    $sklad = get_post_meta( $post->ID, '_sklad', true );
    $sposob_uzycia = get_post_meta( $post->ID, '_sposob_uzycia', true );
    $sposob_uzycia_skrot = get_post_meta( $post->ID, '_sposob_uzycia_skrot', true );
    $ostrzezenia = get_post_meta( $post->ID, '_ostrzezenia', true );
    $podmiot_odpowiedzialny = get_post_meta( $post->ID, '_podmiot_odpowiedzialny', true );
    $tekst_etykieta = get_post_meta( $post->ID, '_tekst_etykieta', true );
    $materialy_url = get_post_meta( $post->ID, '_materialy_reklamowe_url', true );
    $hebe_url = get_post_meta( $post->ID, '_sklep_hebe_url', true );
    $domowa_url = get_post_meta( $post->ID, '_strona_domowa_url', true );
    $tiktok_url = get_post_meta( $post->ID, '_tiktok_url', true );
    $instagram_url = get_post_meta( $post->ID, '_instagram_url', true );
    $facebook_url = get_post_meta( $post->ID, '_facebook_url', true );

    // Style CSS są teraz w osobnym pliku admin-styles.css

    echo '<div class="moj-system-pole"><label for="cdt_id_field">' . __( 'CDT_ID:', 'Physis_zarzadzanie' ) . '</label><input type="text" id="cdt_id_field" name="cdt_id_field" value="' . esc_attr( $cdt_id ) . '" /></div>';
    echo '<div class="moj-system-pole"><label for="ean_field">' . __( 'EAN:', 'Physis_zarzadzanie' ) . '</label><input type="text" id="ean_field" name="ean_field" value="' . esc_attr( $ean ) . '" /></div>';
    echo '<div class="moj-system-pole"><label for="kod_producenta_field">' . __( 'Kod producenta:', 'Physis_zarzadzanie' ) . '</label><input type="text" id="kod_producenta_field" name="kod_producenta_field" value="' . esc_attr( $kod_producenta ) . '" /></div>';
    echo '<div class="moj-system-pole">';
    echo '<label for="producent_field">' . __( 'Producent:', 'Physis_zarzadzanie' ) . '</label>';
    echo '<input type="text" id="producent_field" name="producent_field" value="' . esc_attr( $producent ) . '" class="regular-text" />';
    echo '</div>';
    // --- Dodane pole Linia (Marka) ---
    echo '<div class="moj-system-pole">';
    echo '<label for="linia_marki_field">' . __( 'Linia (Marka):', 'Physis_zarzadzanie' ) . '</label>';
    echo '<input type="text" id="linia_marki_field" name="linia_marki_field" value="' . esc_attr( $linia_marki ) . '" class="regular-text" maxlength="25" />';
    echo '</div>';
    // --- Koniec dodanego pola ---
    echo '<div class="moj-system-pole"><label for="volume_field">' . __( 'Volume:', 'Physis_zarzadzanie' ) . '</label><input type="text" id="volume_field" name="volume_field" value="' . esc_attr( $volume ) . '" size="10" /></div>';

    echo '<div class="moj-system-pole"><label for="opis_marketingowy_field">' . __( 'Opis marketingowy:', 'Physis_zarzadzanie' ) . '</label>';
    wp_editor( $opis_marketingowy, 'opis_marketingowy_field', array( 'textarea_name' => 'opis_marketingowy_field', 'media_buttons' => false, 'textarea_rows' => 10 ) );
    echo '</div>';
    echo '<div class="moj-system-pole"><label for="sklad_field">' . __( 'Skład:', 'Physis_zarzadzanie' ) . '</label>';
    wp_editor( $sklad, 'sklad_field', array( 'textarea_name' => 'sklad_field', 'media_buttons' => false, 'textarea_rows' => 5 ) );
    echo '</div>';
    echo '<div class="moj-system-pole"><label for="sposob_uzycia_field">' . __( 'Sposób użycia:', 'Physis_zarzadzanie' ) . '</label>';
     wp_editor( $sposob_uzycia, 'sposob_uzycia_field', array( 'textarea_name' => 'sposob_uzycia_field', 'media_buttons' => false, 'textarea_rows' => 5 ) );
    echo '</div>';
    echo '<div class="moj-system-pole"><label for="sposob_uzycia_skrot_field">' . __( 'Sposób użycia skrót:', 'Physis_zarzadzanie' ) . '</label><input type="text" id="sposob_uzycia_skrot_field" name="sposob_uzycia_skrot_field" value="' . esc_attr( $sposob_uzycia_skrot ) . '" /></div>';
    echo '<div class="moj-system-pole"><label for="ostrzezenia_field">' . __( 'Ostrzeżenia:', 'Physis_zarzadzanie' ) . '</label>';
    wp_editor( $ostrzezenia, 'ostrzezenia_field', array( 'textarea_name' => 'ostrzezenia_field', 'media_buttons' => false, 'textarea_rows' => 5 ) );
    echo '</div>';
    echo '<div class="moj-system-pole"><label for="podmiot_odpowiedzialny_field">' . __( 'Podmiot odpowiedzialny:', 'Physis_zarzadzanie' ) . '</label><input type="text" id="podmiot_odpowiedzialny_field" name="podmiot_odpowiedzialny_field" value="' . esc_attr( $podmiot_odpowiedzialny ) . '" /></div>';
    echo '<hr style="margin-top:20px; margin-bottom:20px;">';
    echo '<div class="moj-system-pole">';
    echo '<label for="tekst_etykieta_field">' . __( 'Tekst etykieta:', 'Physis_zarzadzanie' ) . '</label>';
    wp_editor( $tekst_etykieta, 'tekst_etykieta_field', array( 'textarea_name' => 'tekst_etykieta_field', 'media_buttons' => false, 'textarea_rows' => 10 ) );
    echo '<p class="description">' . __('Pełny tekst, który może pojawić się na etykiecie produktu.', 'Physis_zarzadzanie') . '</p>';
    echo '</div>';
    echo '<div class="moj-system-pole">';
    echo '<label for="materialy_reklamowe_url_field">' . __( 'Materiały reklamowe (link Google Drive):', 'Physis_zarzadzanie' ) . '</label>';
    echo '<input type="url" id="materialy_reklamowe_url_field" name="materialy_reklamowe_url_field" value="' . esc_attr( $materialy_url ) . '" placeholder="https://..." class="regular-text" />';
    echo '</div>';
    echo '<div class="moj-system-pole">';
    echo '<label for="sklep_hebe_url_field">' . __( 'Sklep Hebe (link):', 'Physis_zarzadzanie' ) . '</label>';
    echo '<input type="url" id="sklep_hebe_url_field" name="sklep_hebe_url_field" value="' . esc_attr( $hebe_url ) . '" placeholder="https://..." class="regular-text" />';
    echo '</div>';
    echo '<div class="moj-system-pole">';
    echo '<label for="strona_domowa_url_field">' . __( 'Strona domowa produktu (link):', 'Physis_zarzadzanie' ) . '</label>';
    echo '<input type="url" id="strona_domowa_url_field" name="strona_domowa_url_field" value="' . esc_attr( $domowa_url ) . '" placeholder="https://..." class="regular-text" />';
    echo '</div>';
    echo '<div class="moj-system-pole">';
    echo '<label for="tiktok_url_field">' . __( 'TikTok (link):', 'Physis_zarzadzanie' ) . '</label>';
    echo '<input type="url" id="tiktok_url_field" name="tiktok_url_field" value="' . esc_attr( $tiktok_url ) . '" placeholder="https://..." class="regular-text" />';
    echo '</div>';
    echo '<div class="moj-system-pole">';
    echo '<label for="instagram_url_field">' . __( 'Instagram (link):', 'Physis_zarzadzanie' ) . '</label>';
    echo '<input type="url" id="instagram_url_field" name="instagram_url_field" value="' . esc_attr( $instagram_url ) . '" placeholder="https://..." class="regular-text" />';
    echo '</div>';
    echo '<div class="moj-system-pole">';
    echo '<label for="facebook_url_field">' . __( 'Facebook (link):', 'Physis_zarzadzanie' ) . '</label>';
    echo '<input type="url" id="facebook_url_field" name="facebook_url_field" value="' . esc_attr( $facebook_url ) . '" placeholder="https://..." class="regular-text" />';
    echo '</div>';

    echo '<p><strong>' . __( 'Zdjęcie:', 'Physis_zarzadzanie' ) . '</strong><br>' . __( 'Użyj sekcji "Obrazek wyróżniający" po prawej stronie, aby dodać lub zmienić zdjęcie produktu.', 'Physis_zarzadzanie' ) . '</p>';
}

/**
 * Zapisuje dane z meta boxa Produktu.
 */
function moj_system_produkt_save_meta_data( $post_id ) {
    // 1. Sprawdź nonce
    if ( ! isset( $_POST['moj_system_produkt_nonce'] ) || ! wp_verify_nonce( $_POST['moj_system_produkt_nonce'], 'moj_system_produkt_save_meta_data' ) ) {
        return;
    }
    // 2. Ignoruj autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    // 3. Sprawdź typ wpisu
    if ( ! isset( $_POST['post_type'] ) || 'produkt' != $_POST['post_type'] ) {
        return;
    }
    // 4. Sprawdź uprawnienia użytkownika
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // 5. Zdefiniuj pola i zapisz je

    // Pola tekstowe (krótkie)
    $pola_tekstowe = [
        'cdt_id_field' => '_cdt_id',
        'ean_field' => '_ean',
        'kod_producenta_field' => '_kod_producenta',
        'volume_field' => '_volume',
        'sposob_uzycia_skrot_field' => '_sposob_uzycia_skrot',
        'podmiot_odpowiedzialny_field' => '_podmiot_odpowiedzialny',
        'producent_field' => '_producent',
        'linia_marki_field' => '_linia_marki', // Dodane pole
    ];

    foreach ( $pola_tekstowe as $field_name => $meta_key ) {
        if ( isset( $_POST[ $field_name ] ) ) {
            // Sanityzacja i ograniczenie długości (dla linia_marki, ale stosujemy dla wszystkich tekstowych dla uproszczenia)
            $value_to_save = sanitize_text_field( $_POST[ $field_name ] );
            if ($meta_key === '_linia_marki') { // Ograniczenie tylko dla linii marki
                 $value_to_save = mb_substr( $value_to_save, 0, 25 ); // Użyj mb_substr dla UTF-8
            }
             // Zapisz tylko raz, poprawną wartość
            update_post_meta( $post_id, $meta_key, $value_to_save );
        } else {
            // Jeśli pole nie zostało przesłane (np. checkbox odznaczony, choć tu nie mamy), można usunąć meta
            // Dla pól tekstowych zazwyczaj chcemy zachować pustą wartość, jeśli użytkownik ją wyczyścił
            // Można dodać warunek: if (get_post_meta($post_id, $meta_key, true) !== '') delete_post_meta...
            // Ale bezpieczniej jest zapisać pusty string, jeśli pole istnieje w POST ale jest puste
             if ( isset( $_POST[ $field_name ] ) && $_POST[ $field_name ] === '' ) {
                 update_post_meta( $post_id, $meta_key, '' );
             } elseif (!isset($_POST[ $field_name ])){
                 // Jeśli pole w ogóle nie było w POST (dziwne, ale możliwe), można usunąć
                  delete_post_meta( $post_id, $meta_key );
             }

        }
    } // Koniec foreach dla pól tekstowych

    // Pola z edytorem WYSIWYG (długi tekst, potencjalny HTML)
    $pola_html = [
        'opis_marketingowy_field' => '_opis_marketingowy',
        'sklad_field' => '_sklad',
        'sposob_uzycia_field' => '_sposob_uzycia',
        'ostrzezenia_field' => '_ostrzezenia',
        'tekst_etykieta_field' => '_tekst_etykieta',
    ];
     foreach ( $pola_html as $field_name => $meta_key ) {
        if ( isset( $_POST[ $field_name ] ) ) {
            update_post_meta( $post_id, $meta_key, wp_kses_post( $_POST[ $field_name ] ) );
        } else {
            // Dla pól WYSIWYG bezpieczniej jest zapisać pusty string niż usuwać meta
             update_post_meta( $post_id, $meta_key, '' );
             // delete_post_meta( $post_id, $meta_key );
        }
    } // Koniec foreach dla pól HTML

    // Pola URL (linki)
    $pola_url = [
        'materialy_reklamowe_url_field' => '_materialy_reklamowe_url',
        'sklep_hebe_url_field' => '_sklep_hebe_url',
        'strona_domowa_url_field' => '_strona_domowa_url',
        'tiktok_url_field' => '_tiktok_url',
        'instagram_url_field' => '_instagram_url',
        'facebook_url_field' => '_facebook_url',
    ];
    foreach ( $pola_url as $field_name => $meta_key ) {
        if ( isset( $_POST[ $field_name ] ) ) {
            $url_value = esc_url_raw( $_POST[ $field_name ] );
             if ( ! empty( $url_value ) ) { // Zapisuj tylko jeśli URL nie jest pusty po sanitacji
                 update_post_meta( $post_id, $meta_key, $url_value );
            } else {
                 // Jeśli URL jest pusty (użytkownik wyczyścił pole), usuń meta dane
                 delete_post_meta( $post_id, $meta_key );
            }
        } else {
            // Jeśli pole nie zostało przesłane w formularzu (nie powinno się zdarzyć przy typie 'url'), usuń
             delete_post_meta( $post_id, $meta_key );
        }
    } // Koniec foreach dla pól URL

    delete_transient( 'physis_unique_producenci' );
    delete_transient( 'physis_unique_linie_marki' );

} // Koniec funkcji moj_system_produkt_save_meta_data

add_action( 'save_post_produkt', 'moj_system_produkt_save_meta_data' );

// Koniec pliku - bez zamykającego ?>