<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register CPT and Taxonomies
 */
function wpc_register_cpt() {
    
    // 1. Comparison Item CPT (formerly Ecommerce Provider)
    register_post_type( 'comparison_item', array(
        'labels' => array(
            'name'               => __( 'Comparison Items', 'wp-comparison-builder' ),
            'singular_name'      => __( 'Comparison Item', 'wp-comparison-builder' ),
            'add_new'           => __( 'Add New Item', 'wp-comparison-builder' ),
            'add_new_item'      => __( 'Add New Comparison Item', 'wp-comparison-builder' ),
            'edit_item'         => __( 'Edit Item', 'wp-comparison-builder' ),
            'new_item'          => __( 'New Item', 'wp-comparison-builder' ),
            'view_item'         => __( 'View Item', 'wp-comparison-builder' ),
            'search_items'      => __( 'Search Items', 'wp-comparison-builder' ),
            'not_found'         => __( 'No items found', 'wp-comparison-builder' ),
            'not_found_in_trash'=> __( 'No items found in Trash', 'wp-comparison-builder' ),
        ),
        'public'             => false, // Do not expose on frontend (handled via React)
        'show_ui'            => true,  // Show in admin
        'show_in_menu'       => true,
        'menu_icon'          => 'dashicons-list-view', // Changed to list view
        'query_var'          => false, // No query var
        'rewrite'            => false, // No URL rewriting
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'supports'           => array( 'title', 'thumbnail' ), 
        'show_in_rest'       => true, 
    ));

    // 2. Category Taxonomy (formerly Ecommerce Type)
    register_taxonomy( 'comparison_category', 'comparison_item', array(
        'labels' => array(
            'name'          => __( 'Categories', 'wp-comparison-builder' ),
            'singular_name' => __( 'Category', 'wp-comparison-builder' ),
        ),
        'hierarchical'      => true,
        'public'            => false, // Do not expose
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => false,
        'rewrite'           => false,
        'show_in_rest'      => true,
    ));

    // 3. Tags Taxonomy (formerly Ecommerce Feature)
    register_taxonomy( 'comparison_feature', 'comparison_item', array(
        'labels' => array(
            'name'          => __( 'Tags', 'wp-comparison-builder' ),
            'singular_name' => __( 'Tag', 'wp-comparison-builder' ),
            'menu_name'     => __( 'Tags', 'wp-comparison-builder' ),
            'search_items'  => __( 'Search Tags', 'wp-comparison-builder' ),
            'popular_items' => __( 'Popular Tags', 'wp-comparison-builder' ),
            'all_items'     => __( 'All Tags', 'wp-comparison-builder' ),
            'edit_item'     => __( 'Edit Tag', 'wp-comparison-builder' ),
            'update_item'   => __( 'Update Tag', 'wp-comparison-builder' ),
            'add_new_item'  => __( 'Add New Tag', 'wp-comparison-builder' ),
            'new_item_name' => __( 'New Tag Name', 'wp-comparison-builder' ),
        ),
        'hierarchical'      => false,
        'public'            => false, // Do not expose
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => false,
        'rewrite'           => false,
        'show_in_rest'      => true,
    ));
    
    // 4. Custom Lists CPT (for organized usage)
    register_post_type( 'comparison_list', array(
        'labels' => array(
            'name'                  => __( 'Custom Lists', 'wp-comparison-builder' ),
            'singular_name'         => __( 'Custom List', 'wp-comparison-builder' ),
            'menu_name'             => __( 'Custom Lists', 'wp-comparison-builder' ),
            'add_new'               => __( 'Create New List', 'wp-comparison-builder' ),
            'add_new_item'          => __( 'Create New Custom List', 'wp-comparison-builder' ),
            'edit_item'             => __( 'Edit List', 'wp-comparison-builder' ),
            'new_item'              => __( 'New List', 'wp-comparison-builder' ),
            'view_item'             => __( 'View List', 'wp-comparison-builder' ),
            'search_items'          => __( 'Search Lists', 'wp-comparison-builder' ),
            'not_found'             => __( 'No lists found', 'wp-comparison-builder' ),
            'not_found_in_trash'    => __( 'No lists found in Trash', 'wp-comparison-builder' ),
        ),
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => 'edit.php?post_type=comparison_item', // Submenu
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'supports'           => array( 'title' ),
        'show_in_rest'       => false,
    ));

    // 5. Review Pages CPT (for custom editorial landing pages)
    
    register_post_type( 'comparison_review', array(
        'labels' => array(
            'name'                  => __( 'Review Pages', 'wp-comparison-builder' ),
            'singular_name'         => __( 'Review Page', 'wp-comparison-builder' ),
            'menu_name'             => __( 'Review Pages', 'wp-comparison-builder' ),
            'add_new'               => __( 'Add New Review', 'wp-comparison-builder' ),
            'add_new_item'          => __( 'Add New Review Page', 'wp-comparison-builder' ),
            'edit_item'             => __( 'Edit Review Page', 'wp-comparison-builder' ),
            'new_item'              => __( 'New Review Page', 'wp-comparison-builder' ),
            'view_item'             => __( 'View Review', 'wp-comparison-builder' ),
            'search_items'          => __( 'Search Review Pages', 'wp-comparison-builder' ),
            'not_found'             => __( 'No review pages found', 'wp-comparison-builder' ),
            'not_found_in_trash'    => __( 'No review pages found in Trash', 'wp-comparison-builder' ),
        ),
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => 'edit.php?post_type=comparison_item',
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
        'show_in_rest'       => true,
        'rewrite'            => array( 'slug' => 'review' ), // Keep 'review' slug? Or generalize to 'item-review'? Let's keep 'review' for now.
    ));
}
add_action( 'init', 'wpc_register_cpt' );
