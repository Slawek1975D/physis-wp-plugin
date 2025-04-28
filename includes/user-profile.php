<?php
// Zabezpieczenie przed bezpośrednim dostępem
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Dodaje pole "Funkcja" do profilu użytkownika.
 */
function moj_system_add_user_funkcja_field( $user ) {
    if ( ! current_user_can( 'edit_user', $user->ID ) ) { return; }
    $funkcja = get_user_meta( $user->ID, '_funkcja', true );
    $opcje = ['Edytor', 'Administrator'];
    ?>
    <h3><?php _e( 'Dodatkowe informacje', 'Physis_zarzadzanie' ); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="funkcja_field"><?php _e( 'Funkcja', 'Physis_zarzadzanie' ); ?></label></th>
            <td>
                <select name="funkcja_field" id="funkcja_field">
                    <option value="" <?php selected( $funkcja, '' ); ?>>-- <?php _e('Wybierz funkcję', 'Physis_zarzadzanie'); ?> --</option>
                    <?php foreach ( $opcje as $opcja ) : ?>
                        <option value="<?php echo esc_attr( $opcja ); ?>" <?php selected( $funkcja, $opcja ); ?>><?php echo esc_html( $opcja ); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e( 'Określa funkcję użytkownika w systemie bazodanowym (informacyjnie).', 'Physis_zarzadzanie' ); ?></p>
                <?php wp_nonce_field( 'moj_system_save_user_funkcja', 'moj_system_user_funkcja_nonce' ); ?>
            </td>
        </tr>
    </table>
    <?php
} // Koniec funkcji moj_system_add_user_funkcja_field
add_action( 'show_user_profile', 'moj_system_add_user_funkcja_field' );
add_action( 'edit_user_profile', 'moj_system_add_user_funkcja_field' );

/**
 * Zapisuje wartość pola "Funkcja" z profilu użytkownika.
 */
function moj_system_save_user_funkcja_field( $user_id ) {
    if ( ! isset( $_POST['moj_system_user_funkcja_nonce'] ) || ! wp_verify_nonce( $_POST['moj_system_user_funkcja_nonce'], 'moj_system_save_user_funkcja' ) ) { return; }
    if ( ! current_user_can( 'edit_user', $user_id ) ) { return; }

    if ( isset( $_POST['funkcja_field'] ) ) {
        $allowed_funkcje = ['Edytor', 'Administrator'];
        $selected_funkcja = sanitize_text_field( $_POST['funkcja_field'] );
        if ( in_array( $selected_funkcja, $allowed_funkcje ) ) {
            update_user_meta( $user_id, '_funkcja', $selected_funkcja );
        } else {
            delete_user_meta( $user_id, '_funkcja' );
        }
    }
}
add_action( 'personal_options_update', 'moj_system_save_user_funkcja_field' );
add_action( 'edit_user_profile_update', 'moj_system_save_user_funkcja_field' );

// Koniec pliku - bez zamykającego ?>