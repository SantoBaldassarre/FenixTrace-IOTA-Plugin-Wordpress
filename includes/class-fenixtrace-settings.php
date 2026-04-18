<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class FenixTrace_Settings {

    public static function add_menu() {
        add_options_page( 'FenixTrace', 'FenixTrace', 'manage_options', 'fenixtrace', array( __CLASS__, 'render' ) );
    }

    /**
     * Accept the Integration Kit URL only if it uses http / https and has a
     * host. esc_url_raw alone would still allow exotic schemes.
     */
    public static function sanitize_kit_url( $value ) {
        $raw = esc_url_raw( (string) $value, array( 'http', 'https' ) );
        if ( empty( $raw ) ) return 'http://localhost:3005';
        $parts = wp_parse_url( $raw );
        if ( empty( $parts['host'] ) ) return 'http://localhost:3005';
        return $raw;
    }

    public static function register_settings() {
        register_setting( 'fenixtrace_opts', 'fenixtrace_kit_url', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_kit_url' ), 'default' => 'http://localhost:3005' ) );
        register_setting( 'fenixtrace_opts', 'fenixtrace_upload_dir', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'fenixtrace_opts', 'fenixtrace_company_name', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'fenixtrace_opts', 'fenixtrace_template', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => 'generic' ) );
        register_setting( 'fenixtrace_opts', 'fenixtrace_auto_sync', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );

        add_settings_section( 'fenixtrace_main', 'Integration Kit', null, 'fenixtrace' );
        add_settings_field( 'kit_url', 'Kit URL', array( __CLASS__, 'field_text' ), 'fenixtrace', 'fenixtrace_main', array( 'name' => 'fenixtrace_kit_url', 'desc' => 'Integration Kit URL', 'placeholder' => 'http://localhost:3005' ) );
        add_settings_field( 'upload_dir', 'Upload Directory', array( __CLASS__, 'field_text' ), 'fenixtrace', 'fenixtrace_main', array( 'name' => 'fenixtrace_upload_dir', 'desc' => 'Optional local path to Kit uploads/', 'placeholder' => '/opt/fenixtrace-kit/uploads' ) );
        add_settings_field( 'company', 'Company Name', array( __CLASS__, 'field_text' ), 'fenixtrace', 'fenixtrace_main', array( 'name' => 'fenixtrace_company_name', 'desc' => 'Your company name for blockchain records', 'placeholder' => get_bloginfo( 'name' ) ) );
        add_settings_field( 'template', 'Default Template', array( __CLASS__, 'field_template' ), 'fenixtrace', 'fenixtrace_main' );
        add_settings_field( 'auto_sync', 'Auto-sync', array( __CLASS__, 'field_checkbox' ), 'fenixtrace', 'fenixtrace_main' );
    }

    public static function field_text( $args ) {
        $val = esc_attr( get_option( $args['name'], '' ) );
        echo "<input type='text' name='" . esc_attr( $args['name'] ) . "' value='{$val}' class='regular-text' placeholder='" . esc_attr( $args['placeholder'] ?? '' ) . "' />";
        if ( ! empty( $args['desc'] ) ) echo "<p class='description'>" . esc_html( $args['desc'] ) . "</p>";
    }

    public static function field_template() {
        $val = get_option( 'fenixtrace_template', 'generic' );
        $templates = array( 'generic', 'agro', 'pharma', 'fashion', 'logistics', 'electronics', 'art', 'automotive', 'cosmetics', 'chemicals', 'machinery', 'custom' );
        echo "<select name='fenixtrace_template'>";
        foreach ( $templates as $t ) echo "<option value='" . esc_attr( $t ) . "'" . selected( $val, $t, false ) . ">" . esc_html( ucfirst( $t ) ) . "</option>";
        echo "</select>";
    }

    public static function field_checkbox() {
        $checked = checked( get_option( 'fenixtrace_auto_sync', 0 ), 1, false );
        echo "<label><input type='checkbox' name='fenixtrace_auto_sync' value='1' {$checked} /> Auto-sync products when published</label>";
    }

    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $status = FenixTrace_WP_API::check_status();
        ?>
        <div class="wrap">
            <h1>FenixTrace Settings</h1>
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin:12px 0;">
                <strong>Kit Status:</strong>
                <?php echo ! empty( $status['connected'] ) ? '<span style="color:#059669;">Connected</span>' : '<span style="color:#dc2626;">Disconnected</span>'; ?>
            </div>
            <form method="post" action="options.php">
                <?php settings_fields( 'fenixtrace_opts' ); do_settings_sections( 'fenixtrace' ); submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
