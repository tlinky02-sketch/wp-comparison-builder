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

// Delete all Comparison Items
$comparison_items = get_posts( array(
    'post_type' => 'comparison_item',
    'numberposts' => -1,
    'post_status' => 'any'
) );

foreach ( $comparison_items as $item ) {
    wp_delete_post( $item->ID, true ); // true = force delete, skip trash
}

// Delete all Comparison Tools
$comparison_tools = get_posts( array(
    'post_type' => 'comparison_tool',
    'numberposts' => -1,
    'post_status' => 'any'
) );

foreach ( $comparison_tools as $tool ) {
    wp_delete_post( $tool->ID, true );
}

// Delete all Custom Lists
$custom_lists = get_posts( array(
    'post_type' => 'wpc_custom_list',
    'numberposts' => -1,
    'post_status' => 'any'
) );

foreach ( $custom_lists as $list ) {
    wp_delete_post( $list->ID, true );
}

// ========== DELETE ALL TAXONOMIES ==========

// Delete all terms from comparison_category
$cat_terms = get_terms( array(
    'taxonomy' => 'comparison_category',
    'hide_empty' => false,
    'fields' => 'ids'
) );

if ( ! is_wp_error( $cat_terms ) && ! empty( $cat_terms ) ) {
    foreach ( $cat_terms as $term_id ) {
        wp_delete_term( $term_id, 'comparison_category' );
    }
}

// Delete all terms from comparison_feature
$feat_terms = get_terms( array(
    'taxonomy' => 'comparison_feature',
    'hide_empty' => false,
    'fields' => 'ids'
) );

if ( ! is_wp_error( $feat_terms ) && ! empty( $feat_terms ) ) {
    foreach ( $feat_terms as $term_id ) {
        wp_delete_term( $term_id, 'comparison_feature' );
    }
}

// Delete all terms from tool_category
$tool_cat_terms = get_terms( array(
    'taxonomy' => 'tool_category',
    'hide_empty' => false,
    'fields' => 'ids'
) );

if ( ! is_wp_error( $tool_cat_terms ) && ! empty( $tool_cat_terms ) ) {
    foreach ( $tool_cat_terms as $term_id ) {
        wp_delete_term( $term_id, 'tool_category' );
    }
}

// Delete all terms from tool_tag
$tool_tag_terms = get_terms( array(
    'taxonomy' => 'tool_tag',
    'hide_empty' => false,
    'fields' => 'ids'
) );

if ( ! is_wp_error( $tool_tag_terms ) && ! empty( $tool_tag_terms ) ) {
    foreach ( $tool_tag_terms as $term_id ) {
        wp_delete_term( $term_id, 'tool_tag' );
    }
}

// ========== DELETE ALL OPTIONS ==========

// Get all WPC options
$options_to_delete = $wpdb->get_col( 
    "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'wpc_%'" 
);

foreach ( $options_to_delete as $option ) {
    delete_option( $option );
}

// Delete transients
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wpc_%' OR option_name LIKE '_transient_timeout_wpc_%'"
);

// ========== DELETE CUSTOM DATABASE TABLES ==========

$items_table = $wpdb->prefix . 'wpc_items';
$tools_table = $wpdb->prefix . 'wpc_tools';
$lists_table = $wpdb->prefix . 'wpc_lists';

// Drop tables
$wpdb->query( "DROP TABLE IF EXISTS {$items_table}" );
$wpdb->query( "DROP TABLE IF EXISTS {$tools_table}" );
$wpdb->query( "DROP TABLE IF EXISTS {$lists_table}" );

// ========== DELETE ORPHANED POST META ==========

// Clean up any leftover post meta (in case some posts were already deleted)
$wpdb->query(
    "DELETE pm FROM {$wpdb->postmeta} pm 
     LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
     WHERE p.ID IS NULL AND pm.meta_key LIKE '_wpc_%'"
);

// ========== CLEAR OBJECT CACHE ==========

if ( function_exists( 'wp_cache_flush' ) ) {
    wp_cache_flush();
}
