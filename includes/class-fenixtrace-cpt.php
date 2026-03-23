<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class FenixTrace_CPT {

    public static function register() {
        register_post_type( 'fenixtrace_product', array(
            'labels' => array(
                'name'               => 'FenixTrace Products',
                'singular_name'      => 'FenixTrace Product',
                'add_new'            => 'Add Product',
                'add_new_item'       => 'Add New Product',
                'edit_item'          => 'Edit Product',
                'view_item'          => 'View Product',
                'search_items'       => 'Search Products',
                'not_found'          => 'No products found',
                'menu_name'          => 'FenixTrace',
            ),
            'public'        => true,
            'has_archive'   => true,
            'show_in_rest'  => true,
            'menu_icon'     => 'dashicons-shield',
            'menu_position' => 25,
            'supports'      => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
            'rewrite'       => array( 'slug' => 'fenixtrace-products' ),
        ) );

        register_taxonomy( 'fenixtrace_category', 'fenixtrace_product', array(
            'labels' => array(
                'name'          => 'Product Categories',
                'singular_name' => 'Category',
                'add_new_item'  => 'Add New Category',
            ),
            'public'       => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite'      => array( 'slug' => 'fenixtrace-category' ),
        ) );

        /* Admin columns */
        add_filter( 'manage_fenixtrace_product_posts_columns', array( __CLASS__, 'columns' ) );
        add_action( 'manage_fenixtrace_product_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
    }

    public static function columns( $cols ) {
        $new = array();
        foreach ( $cols as $key => $val ) {
            $new[ $key ] = $val;
            if ( $key === 'title' ) {
                $new['ft_state']    = 'FenixTrace';
                $new['ft_tx']       = 'TX Hash';
                $new['ft_last']     = 'Last Sync';
            }
        }
        return $new;
    }

    public static function column_content( $col, $post_id ) {
        switch ( $col ) {
            case 'ft_state':
                $state = get_post_meta( $post_id, '_fenixtrace_state', true ) ?: 'draft';
                $colors = array( 'draft' => '#6b7280', 'queued' => '#d97706', 'synced' => '#059669', 'error' => '#dc2626' );
                $color = $colors[ $state ] ?? '#6b7280';
                echo '<span style="background:' . esc_attr( $color ) . ';color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;text-transform:uppercase;">' . esc_html( $state ) . '</span>';
                break;
            case 'ft_tx':
                $tx = get_post_meta( $post_id, '_fenixtrace_tx_hash', true );
                echo $tx ? '<code style="font-size:10px;">' . esc_html( substr( $tx, 0, 16 ) ) . '...</code>' : '—';
                break;
            case 'ft_last':
                $last = get_post_meta( $post_id, '_fenixtrace_last_sync', true );
                echo $last ? esc_html( $last ) : '—';
                break;
        }
    }
}
