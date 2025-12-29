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
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY item_id (item_id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

// Run on plugin activation - NOTE: WPC_PLUGIN_DIR must be defined in main file
register_activation_hook( WPC_PLUGIN_DIR . 'wp-comparison-builder.php', 'wpc_create_comparison_sets_table' );

// Also run on admin_init in case missed
add_action( 'admin_init', function() {
    if ( ! get_option( 'wpc_comparison_sets_table_created' ) ) {
        wpc_create_comparison_sets_table();
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
    
    if ( empty( $set_name ) ) {
        wp_send_json_error( 'Set name is required' );
    }
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'item_id' => $item_id,
            'set_name' => $set_name,
            'competitor_ids' => $competitor_ids,
            'button_text' => $button_text
        ),
        array( '%d', '%s', '%s', '%s' )
    );
    
    if ( $result ) {
        wp_send_json_success( array( 'id' => $wpdb->insert_id ) );
    } else {
        wp_send_json_error( 'Failed to save' );
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
    
    if ( empty( $set_name ) ) {
        wp_send_json_error( 'Set name is required' );
    }
    
    $result = $wpdb->update(
        $table_name,
        array(
            'set_name' => $set_name,
            'competitor_ids' => $competitor_ids,
            'button_text' => $button_text
        ),
        array( 'id' => $set_id ),
        array( '%s', '%s', '%s' ),
        array( '%d' )
    );
    
    if ( $result !== false ) {
        wp_send_json_success();
    } else {
        wp_send_json_error( 'Failed to update' );
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
