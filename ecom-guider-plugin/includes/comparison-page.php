<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create Comparison Page on Plugin Activation
 */
function wpc_create_comparison_page() {
    // Check if comparison page already exists
    $existing = get_posts( array(
        'post_type' => 'page',
        'posts_per_page' => 1,
        'meta_query' => array(
            array(
                'key' => '_wpc_comparison_page',
                'value' => '1'
            )
        )
    ));
    
    // Fallback: check legacy
    if ( empty( $existing ) ) {
        $existing = get_posts( array(
            'post_type' => 'page',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_ecommerce_comparison_page',
                    'value' => '1'
                )
            )
        ));
    }
    
    if ( ! empty( $existing ) ) {
        return; // Page already exists
    }

    // Create the comparison page
    $page_id = wp_insert_post( array(
        'post_title' => 'Comparison',
        'post_content' => '[wpc_compare]',
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_name' => 'comparison'
    ));
    
    if ( $page_id ) {
        update_post_meta( $page_id, '_wpc_comparison_page', '1' );
    }
}

// Run on plugin activation
register_activation_hook( WPC_PLUGIN_DIR . 'wp-comparison-builder.php', 'wpc_create_comparison_page' );

// Also check on admin_init in case it was missed
add_action( 'admin_init', function() {
    if ( ! get_option( 'wpc_comparison_page_created' ) ) {
        wpc_create_comparison_page();
        update_option( 'wpc_comparison_page_created', '1' );
    }
});
