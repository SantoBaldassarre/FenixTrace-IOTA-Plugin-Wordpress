<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class FenixTrace_Metabox {

    public static function register() {
        add_meta_box( 'fenixtrace_product_data', 'Product Data', array( __CLASS__, 'render_product_data' ), 'fenixtrace_product', 'normal', 'high' );
        add_meta_box( 'fenixtrace_blockchain', 'FenixTrace Blockchain', array( __CLASS__, 'render_blockchain' ), 'fenixtrace_product', 'side', 'default' );
    }

    public static function render_product_data( $post ) {
        wp_nonce_field( 'fenixtrace_save', 'fenixtrace_nonce' );
        $fields = array(
            '_fenixtrace_sku'      => array( 'label' => 'SKU / Reference', 'type' => 'text' ),
            '_fenixtrace_barcode'  => array( 'label' => 'Barcode / EAN', 'type' => 'text' ),
            '_fenixtrace_price'    => array( 'label' => 'Price', 'type' => 'number' ),
            '_fenixtrace_weight'   => array( 'label' => 'Weight (kg)', 'type' => 'number' ),
            '_fenixtrace_origin'   => array( 'label' => 'Origin', 'type' => 'text' ),
        );

        $templates = array( 'generic', 'agro', 'pharma', 'fashion', 'logistics', 'electronics', 'art', 'automotive', 'cosmetics', 'chemicals', 'machinery', 'custom' );

        echo '<table class="form-table">';
        foreach ( $fields as $key => $field ) {
            $val = get_post_meta( $post->ID, $key, true );
            $step = $field['type'] === 'number' ? ' step="0.01"' : '';
            echo '<tr><th><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th>';
            echo '<td><input type="' . esc_attr( $field['type'] ) . '" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '" class="regular-text"' . $step . ' /></td></tr>';
        }
        // Template dropdown
        $current_tmpl = get_post_meta( $post->ID, '_fenixtrace_template', true ) ?: get_option( 'fenixtrace_template', 'generic' );
        echo '<tr><th><label for="_fenixtrace_template">Template</label></th><td><select name="_fenixtrace_template" id="_fenixtrace_template">';
        foreach ( $templates as $t ) {
            echo '<option value="' . esc_attr( $t ) . '"' . selected( $current_tmpl, $t, false ) . '>' . esc_html( ucfirst( $t ) ) . '</option>';
        }
        echo '</select></td></tr></table>';
    }

    public static function render_blockchain( $post ) {
        $state = get_post_meta( $post->ID, '_fenixtrace_state', true ) ?: 'draft';
        $tx    = get_post_meta( $post->ID, '_fenixtrace_tx_hash', true );
        $notar = get_post_meta( $post->ID, '_fenixtrace_notarization_tx', true );
        $last  = get_post_meta( $post->ID, '_fenixtrace_last_sync', true );
        $error = get_post_meta( $post->ID, '_fenixtrace_last_error', true );

        $badges = array( 'draft' => 'fenixtrace-badge-draft', 'queued' => 'fenixtrace-badge-queued', 'synced' => 'fenixtrace-badge-synced', 'error' => 'fenixtrace-badge-error' );
        ?>
        <div class="fenixtrace-metabox">
            <p><strong>Status:</strong> <span class="fenixtrace-badge <?php echo esc_attr( $badges[ $state ] ?? '' ); ?>"><?php echo esc_html( ucfirst( $state ) ); ?></span></p>
            <?php if ( $tx ) : ?><p><strong>TX:</strong><br><code class="fenixtrace-hash"><?php echo esc_html( $tx ); ?></code></p><?php endif; ?>
            <?php if ( $notar ) : ?><p><strong>Notarization:</strong><br><code class="fenixtrace-hash"><?php echo esc_html( $notar ); ?></code></p><?php endif; ?>
            <?php if ( $last ) : ?><p><strong>Last Sync:</strong> <?php echo esc_html( $last ); ?></p><?php endif; ?>
            <?php if ( $state === 'error' && $error ) : ?><p class="fenixtrace-error"><?php echo esc_html( $error ); ?></p><?php endif; ?>
            <button type="button" class="button button-primary fenixtrace-sync-btn" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="width:100%;margin-top:8px;">
                <?php echo $state === 'error' ? 'Retry FenixTrace' : 'Send to FenixTrace'; ?>
            </button>
        </div>
        <?php
    }

    public static function save( $post_id, $post ) {
        if ( ! isset( $_POST['fenixtrace_nonce'] ) || ! wp_verify_nonce( $_POST['fenixtrace_nonce'], 'fenixtrace_save' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $text_fields = array( '_fenixtrace_sku', '_fenixtrace_barcode', '_fenixtrace_origin', '_fenixtrace_template' );
        foreach ( $text_fields as $key ) {
            if ( isset( $_POST[ $key ] ) ) update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) );
        }
        $num_fields = array( '_fenixtrace_price', '_fenixtrace_weight' );
        foreach ( $num_fields as $key ) {
            if ( isset( $_POST[ $key ] ) ) update_post_meta( $post_id, $key, floatval( $_POST[ $key ] ) );
        }

        // Auto-sync on publish
        if ( $post->post_status === 'publish' && get_option( 'fenixtrace_auto_sync', 0 ) ) {
            self::do_sync( $post_id );
        }
    }

    public static function ajax_sync() {
        check_ajax_referer( 'fenixtrace_sync', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Permission denied' );
        $post_id = absint( $_POST['post_id'] ?? 0 );
        if ( ! $post_id ) wp_send_json_error( 'Invalid ID' );
        $result = self::do_sync( $post_id );
        $result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result['error'] ?? 'Failed' );
    }

    public static function do_sync( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== 'fenixtrace_product' ) return array( 'success' => false, 'error' => 'Not found' );

        $categories = wp_get_post_terms( $post_id, 'fenixtrace_category', array( 'fields' => 'names' ) );
        $payload = array(
            'name'    => $post->post_title,
            'company' => get_option( 'fenixtrace_company_name', get_bloginfo( 'name' ) ),
            'template' => get_post_meta( $post_id, '_fenixtrace_template', true ) ?: get_option( 'fenixtrace_template', 'generic' ),
            'product' => array(
                'name'        => $post->post_title,
                'sku'         => get_post_meta( $post_id, '_fenixtrace_sku', true ),
                'barcode'     => get_post_meta( $post_id, '_fenixtrace_barcode', true ),
                'price'       => get_post_meta( $post_id, '_fenixtrace_price', true ),
                'weight'      => get_post_meta( $post_id, '_fenixtrace_weight', true ),
                'origin'      => get_post_meta( $post_id, '_fenixtrace_origin', true ),
                'category'    => is_array( $categories ) ? implode( ', ', $categories ) : '',
                'description' => wp_strip_all_tags( $post->post_content ),
            ),
            'source'    => 'wordpress_plugin',
            'createdAt' => gmdate( 'c' ),
            'wordpress' => array(
                'post_id'   => $post_id,
                'post_url'  => get_permalink( $post_id ),
                'site_name' => get_bloginfo( 'name' ),
                'site_url'  => home_url(),
            ),
        );

        $slug = sanitize_title( get_post_meta( $post_id, '_fenixtrace_sku', true ) ?: $post->post_title );
        // Harden: sanitize_title() strips most unsafe chars but not all path
        // separators in every locale. Whitelist to [A-Za-z0-9._-] before use.
        $slug = preg_replace( '/[^A-Za-z0-9._-]/', '', (string) $slug );
        $filename = ( $slug ?: 'product-' . (int) $post_id ) . '_' . (int) $post_id . '_' . gmdate( 'YmdHis' ) . '.json';

        update_post_meta( $post_id, '_fenixtrace_state', 'queued' );
        update_post_meta( $post_id, '_fenixtrace_last_error', '' );

        $result = FenixTrace_WP_API::send_product( $payload, $filename );

        if ( $result['success'] ) {
            update_post_meta( $post_id, '_fenixtrace_state', 'synced' );
            update_post_meta( $post_id, '_fenixtrace_tx_hash', $result['txHash'] );
            update_post_meta( $post_id, '_fenixtrace_notarization_tx', $result['notarizationTxHash'] );
            update_post_meta( $post_id, '_fenixtrace_last_sync', current_time( 'mysql' ) );
        } else {
            update_post_meta( $post_id, '_fenixtrace_state', 'error' );
            update_post_meta( $post_id, '_fenixtrace_last_error', $result['error'] ?? 'Unknown error' );
        }
        return $result;
    }
}
