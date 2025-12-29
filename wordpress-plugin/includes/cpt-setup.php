<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register CPT and Taxonomies
 */
function hosting_guider_register_cpt() {
    
    // 1. Hosting Provider CPT
    register_post_type( 'hosting_provider', array(
        'labels' => array(
            'name'               => __( 'Hosting Providers', 'hosting-guider' ),
            'singular_name'      => __( 'Hosting Provider', 'hosting-guider' ),
            'add_new'           => __( 'Add New Provider', 'hosting-guider' ),
            'add_new_item'      => __( 'Add New Hosting Provider', 'hosting-guider' ),
            'edit_item'         => __( 'Edit Hosting Provider', 'hosting-guider' ),
            'new_item'          => __( 'New Hosting Provider', 'hosting-guider' ),
            'view_item'         => __( 'View Hosting Provider', 'hosting-guider' ),
            'search_items'      => __( 'Search Providers', 'hosting-guider' ),
            'not_found'         => __( 'No providers found', 'hosting-guider' ),
            'not_found_in_trash'=> __( 'No providers found in Trash', 'hosting-guider' ),
        ),
        'public'             => false, // Do not expose on frontend
        'show_ui'            => true,  // Show in admin
        'show_in_menu'       => true,
        'menu_icon'          => 'dashicons-cloud',
        'query_var'          => false, // No query var
        'rewrite'            => false, // No URL rewriting
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'supports'           => array( 'title', 'thumbnail' ), 
        'show_in_rest'       => true, 
    ));

    // 2. Hosting Type Taxonomy
    register_taxonomy( 'hosting_type', 'hosting_provider', array(
        'labels' => array(
            'name'          => __( 'Hosting Types', 'hosting-guider' ),
            'singular_name' => __( 'Hosting Type', 'hosting-guider' ),
        ),
        'hierarchical'      => true,
        'public'            => false, // Do not expose
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => false,
        'rewrite'           => false,
        'show_in_rest'      => true,
    ));

    // 3. Hosting Features Taxonomy
    register_taxonomy( 'hosting_feature', 'hosting_provider', array(
        'labels' => array(
            'name'          => __( 'Hosting Features', 'hosting-guider' ),
            'singular_name' => __( 'Hosting Feature', 'hosting-guider' ),
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
    register_post_type( 'hosting_list', array(
        'labels' => array(
            'name'                  => __( 'Custom Lists', 'hosting-guider' ),
            'singular_name'         => __( 'Custom List', 'hosting-guider' ),
            'menu_name'             => __( 'Custom Lists', 'hosting-guider' ),
            'add_new'               => __( 'Create New List', 'hosting-guider' ),
            'add_new_item'          => __( 'Create New Custom List', 'hosting-guider' ),
            'edit_item'             => __( 'Edit List', 'hosting-guider' ),
            'new_item'              => __( 'New List', 'hosting-guider' ),
            'view_item'             => __( 'View List', 'hosting-guider' ),
            'search_items'          => __( 'Search Lists', 'hosting-guider' ),
            'not_found'             => __( 'No lists found', 'hosting-guider' ),
            'not_found_in_trash'    => __( 'No lists found in Trash', 'hosting-guider' ),
        ),
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => 'edit.php?post_type=hosting_provider', // Submenu
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'supports'           => array( 'title' ),
        'show_in_rest'       => false,
    ));
}
add_action( 'init', 'hosting_guider_register_cpt' );
