<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode: [wpc_use_cases]
 * Displays "Best Use Cases" grid for an item.
 */
function wpc_use_cases_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'id' => '',
        'columns' => '4',
        'title' => '',
    ), $atts, 'wpc_use_cases' );

    $post_id = ! empty( $atts['id'] ) ? intval( $atts['id'] ) : get_the_ID();
    if ( ! $post_id ) return '';

    $use_cases = get_post_meta( $post_id, '_wpc_use_cases', true );
    if ( empty( $use_cases ) || ! is_array( $use_cases ) ) {
        return '';
    }

    // Prepare config
    $config = array(
        'columns' => intval( $atts['columns'] ),
    );

    // Enqueue styles if needed (usually main plugin style covers cards)
    // Enqueue React app if not already
    wp_enqueue_script( 'wpc-app' );
    wp_enqueue_style( 'wpc-app' );
    wp_enqueue_style( 'fontawesome' ); // FontAwesome for icons

    $data_props = htmlspecialchars( json_encode( array(
        'items' => $use_cases,
        'config' => $config,
        'title' => $atts['title']
    ) ), ENT_QUOTES, 'UTF-8' );

    return sprintf(
        '<div class="wpc-use-cases-root" data-props="%s"></div>',
        $data_props
    );
}
add_shortcode( 'wpc_use_cases', 'wpc_use_cases_shortcode' );
