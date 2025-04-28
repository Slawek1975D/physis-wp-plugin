<?php
// File: includes/class-produkt-search-list-table.php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure WP_List_Table class is available
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class for displaying product search results using WP_List_Table.
 */
class Produkt_Search_List_Table extends WP_List_Table {

    /**
     * Constructor. Sets basic table information.
     */
    public function __construct() {
        parent::__construct( array(
            'singular' => __( 'Produkt', 'Physis_zarzadzanie' ),
            'plural'   => __( 'Produkty', 'Physis_zarzadzanie' ),
            'ajax'     => false
        ) );
    }

    /**
     * Default column display method. Used for columns that don't have a specific column_{key}() method.
     *
     * @param object $item        WP_Post object for the row.
     * @param string $column_name The slug of the column being processed.
     * @return string Cell content.
     */
    protected function column_default( $item, $column_name ) {
        // $item is expected to be a WP_Post object
        if (!is_object($item) || !isset($item->ID)) {
             return ''; // Return empty if item is not a valid post object
        }
        switch ( $column_name ) {
            case 'ean':
                return esc_html( get_post_meta( $item->ID, '_ean', true ) );
            case 'cdt_id':
                return esc_html( get_post_meta( $item->ID, '_cdt_id', true ) );
            case 'kod_prod':
                return esc_html( get_post_meta( $item->ID, '_kod_producenta', true ) );
            case 'producent':
                return esc_html( get_post_meta( $item->ID, '_producent', true ) );
            case 'sklad':
                $sklad_val = get_post_meta( $item->ID, '_sklad', true );
                return wp_trim_words( strip_tags( $sklad_val ), 15, '...' );
            default:
                return '';
        }
    }

    /**
     * Defines the table columns that will be displayed and available in Screen Options.
     * Keys are column slugs, values are their titles.
     *
     * @return array Associative array of columns ('slug' => 'Title').
     */
    public function get_columns() {
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'title'     => __( 'Nazwa Produktu', 'Physis_zarzadzanie' ),
            'ean'       => __( 'EAN', 'Physis_zarzadzanie' ),
            'cdt_id'    => __( 'CDT ID', 'Physis_zarzadzanie' ),
            'kod_prod'  => __( 'Kod Prod.', 'Physis_zarzadzanie' ),
            'producent' => __( 'Producent', 'Physis_zarzadzanie' ),
            'sklad'     => __( 'Skład (początek)', 'Physis_zarzadzanie' ),
        );
        return $columns;
    }

    /**
     * Defines which columns are sortable.
     * The array key is the column slug, the value is the 'orderby' parameter WP will use.
     *
     * @return array Sortable columns.
     */
     public function get_sortable_columns() {
        $sortable_columns = array(
            'title'     => array( 'title', false ),
            'ean'       => array( '_ean', false ),
            'cdt_id'    => array( '_cdt_id', false ),
            'kod_prod'  => array( '_kod_producenta', false ),
            'producent' => array( '_producent', false ),
        );
        return $sortable_columns;
    }

    /**
     * Renders the 'title' column content and adds row actions (e.g., Edit).
     * column_{key}() methods override column_default() for specific columns.
     *
     * @param object $item WP_Post object for the row.
     * @return string HTML content for the cell.
     */
    protected function column_title( $item ) {
         if (!is_object($item) || !isset($item->ID)) {
             return ''; // Return empty if item is not a valid post object
        }
        $edit_link = get_edit_post_link( $item->ID );
        $title_text = get_the_title( $item->ID );
        $title = '<strong><a class="row-title" href="' . esc_url( $edit_link ) . '">' . esc_html( $title_text ) . '</a></strong>';

        if ( current_user_can('edit_post', $item->ID) ) {
            $actions = array(
                'edit' => sprintf( '<a href="%s">%s</a>', esc_url( $edit_link ), __( 'Edytuj', 'Physis_zarzadzanie' ) ),
            );
            return $title . $this->row_actions( $actions );
        } else {
            return $title;
        }
    }

    /**
      * Renders the checkbox column for bulk actions.
      *
      * @param object $item WP_Post object for the row.
      * @return string HTML for the checkbox cell.
      */
     protected function column_cb( $item ) {
         if (!is_object($item) || !isset($item->ID)) {
             return ''; // Return empty if item is not a valid post object
         }
         return sprintf(
             '<input type="checkbox" name="produkt_ids[]" value="%s" />', esc_attr($item->ID)
         );
     }

    /**
     * Prepares the items for the table display.
     * Fetches data based on search criteria, handles pagination and sorting.
     */
    public function prepare_items() {
        // Get pagination and sorting parameters
        $per_page = $this->get_items_per_page( 'produkty_search_per_page', 20 );
        $current_page = $this->get_pagenum();
        $orderby = isset( $_REQUEST['orderby'] ) ? sanitize_key( $_REQUEST['orderby'] ) : 'title';
        $order = isset( $_REQUEST['order'] ) && in_array( strtoupper( $_REQUEST['order'] ), ['ASC', 'DESC'] ) ? strtoupper( $_REQUEST['order'] ) : 'ASC';

        // Get search criteria
        $szukana_nazwa = isset( $_GET['s_nazwa'] ) ? sanitize_text_field( $_GET['s_nazwa'] ) : '';
        $szukany_ean = isset( $_GET['s_ean'] ) ? sanitize_text_field( $_GET['s_ean'] ) : '';
        $szukany_cdt_id = isset( $_GET['s_cdt_id'] ) ? sanitize_text_field( $_GET['s_cdt_id'] ) : '';
        $szukany_kod_prod = isset( $_GET['s_kod_prod'] ) ? sanitize_text_field( $_GET['s_kod_prod'] ) : '';
        $szukany_producent = isset( $_GET['s_producent'] ) ? sanitize_text_field( $_GET['s_producent'] ) : '';
        // <<< DODANE POBIERANIE LINII MARKI >>>
        $szukana_linia_marki = isset( $_GET['s_linia_marki'] ) ? sanitize_text_field( $_GET['s_linia_marki'] ) : '';
        // <<< KONIEC DODAWANIA >>> (Usunięto też zduplikowane pobieranie producenta)

        // Build WP_Query arguments
        $args = array(
            'post_type' => 'produkt',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $current_page,
            'orderby' => $orderby,
            'order' => $order,
            'meta_query' => array( 'relation' => 'AND' )
        );
        if ( ! empty( $szukana_nazwa ) ) { $args['s'] = $szukana_nazwa; }
        if ( ! empty( $szukany_ean ) ) { $args['meta_query'][] = array( 'key' => '_ean', 'value' => $szukany_ean, 'compare' => 'LIKE' ); }
        if ( ! empty( $szukany_cdt_id ) ) { $args['meta_query'][] = array( 'key' => '_cdt_id', 'value' => $szukany_cdt_id, 'compare' => 'LIKE' ); }
        if ( ! empty( $szukany_kod_prod ) ) { $args['meta_query'][] = array( 'key' => '_kod_producenta', 'value' => $szukany_kod_prod, 'compare' => 'LIKE' ); }
        if ( ! empty( $szukany_producent ) ) { $args['meta_query'][] = array( 'key' => '_producent', 'value' => $szukany_producent, 'compare' => '=' ); }
        if ( ! empty( $szukana_linia_marki ) ) { $args['meta_query'][] = array( 'key' => '_linia_marki', 'value' => $szukana_linia_marki, 'compare' => '=' ); } // <--- Zmienione na '='
        // <<< KONIEC DODAWANIA >>>

        // Handle sorting by custom meta fields
        $meta_keys_for_sorting = ['_ean', '_cdt_id', '_kod_producenta', '_producent']; // Na razie nie dodajemy tu _linia_marki, ale można
        if ( in_array($orderby, $meta_keys_for_sorting) ) {
            $args['meta_key'] = $orderby;
            if ($orderby === '_ean' || $orderby === '_cdt_id') {
                $args['orderby'] = 'meta_value_num';
            } else {
                $args['orderby'] = 'meta_value';
            }
        } else {
            unset($args['meta_key']);
        }
        // Remove 'meta_query' if only the 'relation' => 'AND' part exists
        if ( count( $args['meta_query'] ) <= 1 ) { unset( $args['meta_query'] ); }

        // Execute the WP_Query
        $data_query = new WP_Query( $args );

        // Assign the found posts to the items property
        $this->items = $data_query->posts;

        // Set pagination arguments
        $this->set_pagination_args( array(
            'total_items' => $data_query->found_posts,
            'per_page'    => $per_page,
            'total_pages' => $data_query->max_num_pages
        ) );

        // Set column headers, trying to get hidden columns dynamically
        $columns = $this->get_columns();
        $sortable = $this->get_sortable_columns();
        $hidden = array();
        $screen = get_current_screen();
        if ( $screen instanceof WP_Screen ) {
             $hidden_option = get_user_option( 'manage' . $screen->id . 'columnshidden' );
             if ( is_array($hidden_option) ) {
                 $hidden = $hidden_option;
             }
        }
        $primary = 'title';

        $this->_column_headers = array( $columns, $hidden, $sortable, $primary );

    } // <-- Koniec metody prepare_items()

} // <-- Koniec klasy Produkt_Search_List_Table

// End file - intentionally no closing PHP tag
?>