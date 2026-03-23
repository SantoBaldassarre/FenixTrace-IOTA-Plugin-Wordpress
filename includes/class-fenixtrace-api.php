<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class FenixTrace_WP_API {

    public static function send_product( array $payload, string $filename ): array {
        $kit_url    = rtrim( get_option( 'fenixtrace_kit_url', 'http://localhost:3005' ), '/' );
        $upload_dir = get_option( 'fenixtrace_upload_dir', '' );

        if ( $upload_dir && is_dir( $upload_dir ) && is_writable( $upload_dir ) ) {
            file_put_contents( trailingslashit( $upload_dir ) . $filename, wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
        }

        $response = wp_remote_post( $kit_url . '/process/' . rawurlencode( $filename ), array(
            'timeout' => 60,
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => '',
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'error' => $response->get_error_message() );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $code = wp_remote_retrieve_response_code( $response );

        if ( $code >= 400 || empty( $body ) ) {
            return array( 'success' => false, 'error' => $body['error'] ?? "HTTP $code" );
        }

        $result = isset( $body['result'] ) && is_array( $body['result'] ) ? $body['result'] : $body;
        return array(
            'success'            => true,
            'txHash'             => sanitize_text_field( $result['txHash'] ?? '' ),
            'notarizationTxHash' => sanitize_text_field( $result['notarizationTxHash'] ?? '' ),
            'ipfsHash'           => sanitize_text_field( $result['ipfsHash'] ?? '' ),
        );
    }

    public static function check_status(): array {
        $kit_url  = rtrim( get_option( 'fenixtrace_kit_url', 'http://localhost:3005' ), '/' );
        $response = wp_remote_get( $kit_url . '/status', array( 'timeout' => 10 ) );
        if ( is_wp_error( $response ) ) return array( 'connected' => false, 'error' => $response->get_error_message() );
        return array( 'connected' => true, 'data' => json_decode( wp_remote_retrieve_body( $response ), true ) );
    }
}
