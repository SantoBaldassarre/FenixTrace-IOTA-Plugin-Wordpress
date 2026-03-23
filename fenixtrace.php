<?php
/**
 * Plugin Name: FenixTrace for WordPress
 * Plugin URI:  https://trace.fenixsoftwarelabs.com
 * Description: Register products on the IOTA L1 blockchain via FenixTrace. Adds a custom post type for product traceability without requiring WooCommerce.
 * Version:     1.0.0
 * Author:      Fenix Software Labs
 * Author URI:  https://www.fenixsoftwarelabs.com
 * License:     GPL-2.0-or-later
 * Text Domain: fenixtrace
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'FENIXTRACE_VERSION', '1.0.0' );
define( 'FENIXTRACE_FILE', __FILE__ );
define( 'FENIXTRACE_DIR', plugin_dir_path( __FILE__ ) );
define( 'FENIXTRACE_URL', plugin_dir_url( __FILE__ ) );

/* Load classes */
require_once FENIXTRACE_DIR . 'includes/class-fenixtrace-api.php';
require_once FENIXTRACE_DIR . 'includes/class-fenixtrace-cpt.php';
require_once FENIXTRACE_DIR . 'includes/class-fenixtrace-metabox.php';
require_once FENIXTRACE_DIR . 'includes/class-fenixtrace-settings.php';
require_once FENIXTRACE_DIR . 'includes/class-fenixtrace-rest.php';

/* Init */
add_action( 'init', array( 'FenixTrace_CPT', 'register' ) );
add_action( 'admin_init', array( 'FenixTrace_Settings', 'register_settings' ) );
add_action( 'admin_menu', array( 'FenixTrace_Settings', 'add_menu' ) );
add_action( 'add_meta_boxes', array( 'FenixTrace_Metabox', 'register' ) );
add_action( 'save_post_fenixtrace_product', array( 'FenixTrace_Metabox', 'save' ), 10, 2 );
add_action( 'wp_ajax_fenixtrace_sync', array( 'FenixTrace_Metabox', 'ajax_sync' ) );
add_action( 'rest_api_init', array( 'FenixTrace_REST', 'register_routes' ) );

/* Admin assets */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    $screen = get_current_screen();
    if ( $screen && $screen->post_type === 'fenixtrace_product' ) {
        wp_enqueue_style( 'fenixtrace-admin', FENIXTRACE_URL . 'assets/css/fenixtrace-admin.css', array(), FENIXTRACE_VERSION );
        wp_enqueue_script( 'fenixtrace-admin', FENIXTRACE_URL . 'assets/js/fenixtrace-admin.js', array( 'jquery' ), FENIXTRACE_VERSION, true );
        wp_localize_script( 'fenixtrace-admin', 'fenixtrace', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'fenixtrace_sync' ),
        ) );
    }
} );

/* Settings link */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
    array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=fenixtrace' ) ) . '">Settings</a>' );
    return $links;
} );

/* Activation defaults */
register_activation_hook( __FILE__, function () {
    if ( ! get_option( 'fenixtrace_kit_url' ) ) update_option( 'fenixtrace_kit_url', 'http://localhost:3005' );
    if ( ! get_option( 'fenixtrace_template' ) ) update_option( 'fenixtrace_template', 'generic' );
    FenixTrace_CPT::register();
    flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
