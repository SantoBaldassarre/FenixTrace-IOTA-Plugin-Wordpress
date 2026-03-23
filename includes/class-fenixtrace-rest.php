<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class FenixTrace_REST {

    public static function register_routes() {
        register_rest_route( 'fenixtrace/v1', '/sync/(?P<id>\d+)', array(
            array(
                'methods'             => 'POST',
                'callback'            => array( __CLASS__, 'sync_product' ),
                'permission_callback' => function () { return current_user_can( 'edit_posts' ); },
                'args'                => array( 'id' => array( 'validate_callback' => 'absint' ) ),
            ),
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'get_status' ),
                'permission_callback' => '__return_true',
                'args'                => array( 'id' => array( 'validate_callback' => 'absint' ) ),
            ),
        ) );
    }

    public static function sync_product( $request ) {
        $post_id = absint( $request['id'] );
        $result  = FenixTrace_Metabox::do_sync( $post_id );
        return rest_ensure_response( $result );
    }

    public static function get_status( $request ) {
        $post_id = absint( $request['id'] );
        $post    = get_post( $post_id );
        if ( ! $post || $post->post_type !== 'fenixtrace_product' ) {
            return new WP_Error( 'not_found', 'Product not found', array( 'status' => 404 ) );
        }
        return rest_ensure_response( array(
            'state'             => get_post_meta( $post_id, '_fenixtrace_state', true ) ?: 'draft',
            'txHash'            => get_post_meta( $post_id, '_fenixtrace_tx_hash', true ),
            'notarizationTxHash' => get_post_meta( $post_id, '_fenixtrace_notarization_tx', true ),
            'lastSync'          => get_post_meta( $post_id, '_fenixtrace_last_sync', true ),
            'lastError'         => get_post_meta( $post_id, '_fenixtrace_last_error', true ),
        ) );
    }
}
