<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create Database Table for Saved Comparison Sets
 */
function wpc_create_comparison_sets_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wpc_comparison_sets';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        item_id bigint(20) NOT NULL,
        set_name varchar(255) NOT NULL,
        competitor_ids text NOT NULL,
        button_text varchar(255) DEFAULT 'Compare Alternatives',
        display_mode varchar(20) DEFAULT 'button',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY item_id (item_id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    
    // FORCE CHECK: Ensure display_mode column exists (dbDelta can be finicky with ALTER)
    $column = $wpdb->get_results( "SHOW COLUMNS FROM $table_name LIKE 'display_mode'" );
    if ( empty( $column ) ) {
        $wpdb->query( "ALTER TABLE $table_name ADD display_mode varchar(20) DEFAULT 'button'" );
    }
}

// Run on plugin activation
register_activation_hook( WPC_PLUGIN_DIR . 'wp-comparison-builder.php', 'wpc_create_comparison_sets_table' );

// Run on admin_init for updates
add_action( 'admin_init', function() {
    $db_ver = '1.3'; // Bumped to 1.3 to force ALTER TABLE
    $installed_ver = get_option( 'wpc_comparison_sets_db_version' );
    
    if ( $installed_ver !== $db_ver ) {
        wpc_create_comparison_sets_table();
        update_option( 'wpc_comparison_sets_db_version', $db_ver );
        update_option( 'wpc_comparison_sets_table_created', '1' );
    }
});

/**
 * Save Comparison Set via AJAX
 */
add_action( 'wp_ajax_wpc_save_comparison_set', 'wpc_save_comparison_set' );
function wpc_save_comparison_set() {
    check_ajax_referer( 'wpc_comparison_nonce', 'nonce' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied' );
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'wpc_comparison_sets';
    
    $item_id = intval( $_POST['item_id'] );
    $set_name = sanitize_text_field( $_POST['set_name'] );
    $competitor_ids = sanitize_text_field( $_POST['competitor_ids'] );
    $button_text = sanitize_text_field( $_POST['button_text'] );
    $display_mode = !empty($_POST['display_mode']) ? sanitize_text_field( $_POST['display_mode'] ) : 'button';
    
    if ( empty( $set_name ) ) {
        wp_send_json_error( 'Set name is required' );
    }
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'item_id' => $item_id,
            'set_name' => $set_name,
            'competitor_ids' => $competitor_ids,
            'button_text' => $button_text,
            'display_mode' => $display_mode
        ),
        array( '%d', '%s', '%s', '%s', '%s' )
    );
    
    if ( $result ) {
        wp_send_json_success( array( 'id' => $wpdb->insert_id ) );
    } else {
        wp_send_json_error( 'DB Error: ' . $wpdb->last_error );
    }
}

/**
 * Update Comparison Set via AJAX
 */
add_action( 'wp_ajax_wpc_update_comparison_set', 'wpc_update_comparison_set' );
function wpc_update_comparison_set() {
    check_ajax_referer( 'wpc_comparison_nonce', 'nonce' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied' );
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'wpc_comparison_sets';
    
    $set_id = intval( $_POST['set_id'] );
    $set_name = sanitize_text_field( $_POST['set_name'] );
    $competitor_ids = sanitize_text_field( $_POST['competitor_ids'] );
    $button_text = sanitize_text_field( $_POST['button_text'] );
    $display_mode = !empty($_POST['display_mode']) ? sanitize_text_field( $_POST['display_mode'] ) : 'button';
    
    if ( empty( $set_name ) ) {
        wp_send_json_error( 'Set name is required' );
    }
    
    $result = $wpdb->update(
        $table_name,
        array(
            'set_name' => $set_name,
            'competitor_ids' => $competitor_ids,
            'button_text' => $button_text,
            'display_mode' => $display_mode
        ),
        array( 'id' => $set_id ),
        array( '%s', '%s', '%s', '%s' ),
        array( '%d' )
    );
    
    if ( $result !== false ) {
        wp_send_json_success();
    } else {
        wp_send_json_error( 'DB Update Failed: ' . $wpdb->last_error );
    }
}

/**
 * Delete Comparison Set via AJAX
 */
add_action( 'wp_ajax_wpc_delete_comparison_set', 'wpc_delete_comparison_set' );
function wpc_delete_comparison_set() {
    check_ajax_referer( 'wpc_comparison_nonce', 'nonce' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied' );
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'wpc_comparison_sets';
    $set_id = intval( $_POST['set_id'] );
    
    $result = $wpdb->delete( $table_name, array( 'id' => $set_id ), array( '%d' ) );
    
    if ( $result ) {
        wp_send_json_success();
    } else {
        wp_send_json_error( 'Failed to delete' );
    }
}

/**
 * Get Saved Sets for Item
 */
function wpc_get_saved_sets( $item_id ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wpc_comparison_sets';
    
    // Check if table exists first to avoid error on fresh install before init
    if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {
        return array();
    }
    
    return $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM $table_name WHERE item_id = %d ORDER BY created_at DESC",
        $item_id
    ));
}
