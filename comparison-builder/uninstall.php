<?php
/**
 * Uninstall WP Comparison Builder
 * 
 * This file runs when the plugin is deleted via WordPress admin.
 * It will only delete data if the "Uninstall on Delete" option is enabled.
 */

// Exit if accessed directly or not during uninstall
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Check if user wants to remove all data
$should_uninstall = get_option( 'wpc_uninstall_on_delete' );

if ( $should_uninstall !== '1' ) {
    // User doesn't want to delete data, exit
    return;
}

global $wpdb;

// ========== DELETE ALL POSTS ==========

// All plugin post types
$post_types = array(
    'comparison_item',
    'comparison_tool',
    'comparison_list',
    'comparison_review',
    'wpc_custom_list'
);

// Delete posts directly from database (more reliable during uninstall)
$post_types_sql = "'" . implode( "','", $post_types ) . "'";
$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type IN ({$post_types_sql})" );

// ========== DELETE ALL TAXONOMIES (DIRECTLY FROM DB) ==========

// All plugin taxonomies
$taxonomies = array(
    'comparison_category',
    'comparison_feature',
    'tool_category',
    'tool_tag'
);

$taxonomies_sql = "'" . implode( "','", $taxonomies ) . "'";

// Get term_taxonomy_ids for our taxonomies
$term_taxonomy_ids = $wpdb->get_col( 
    "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ({$taxonomies_sql})" 
);

if ( ! empty( $term_taxonomy_ids ) ) {
    $tt_ids_sql = implode( ',', array_map( 'intval', $term_taxonomy_ids ) );
    
    // Delete term relationships
    $wpdb->query( "DELETE FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ({$tt_ids_sql})" );
}

// Get term_ids for our taxonomies
$term_ids = $wpdb->get_col( 
    "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ({$taxonomies_sql})" 
);

// Delete from term_taxonomy
$wpdb->query( "DELETE FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ({$taxonomies_sql})" );

// Delete from terms (only orphaned terms that are no longer in term_taxonomy)
if ( ! empty( $term_ids ) ) {
    $term_ids_sql = implode( ',', array_map( 'intval', $term_ids ) );
    $wpdb->query( 
        "DELETE FROM {$wpdb->terms} WHERE term_id IN ({$term_ids_sql}) 
         AND term_id NOT IN (SELECT term_id FROM {$wpdb->term_taxonomy})" 
    );
}

// Delete termmeta for our terms
if ( ! empty( $term_ids ) ) {
    $term_ids_sql = implode( ',', array_map( 'intval', $term_ids ) );
    $wpdb->query( "DELETE FROM {$wpdb->termmeta} WHERE term_id IN ({$term_ids_sql})" );
}

// ========== DELETE ALL OPTIONS ==========

// Get all WPC options
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wpc_%'" );

// Delete transients
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wpc_%' OR option_name LIKE '_transient_timeout_wpc_%'"
);

// ========== DELETE CUSTOM DATABASE TABLES ==========

$tables = array(
    $wpdb->prefix . 'wpc_items',
    $wpdb->prefix . 'wpc_tools',
    $wpdb->prefix . 'wpc_lists'
);

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

// ========== DELETE ORPHANED POST META ==========

// Delete all post meta with our prefix
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_wpc_%'" );
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'wpc_%'" );

// ========== CLEAR OBJECT CACHE ==========

if ( function_exists( 'wp_cache_flush' ) ) {
    wp_cache_flush();
}
