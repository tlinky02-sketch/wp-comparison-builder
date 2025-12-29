<?php
/**
 * Plugin Name: HostingGuider Comparison
 * Description: A powerful hosting comparison plugin with a user-friendly admin interface and instant React-based frontend.
 * Version: 1.0.8
 * Author: HostingGuider
 * Text Domain: hosting-guider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HOSTING_GUIDER_PATH', plugin_dir_path( __FILE__ ) );
define( 'HOSTING_GUIDER_URL', plugin_dir_url( __FILE__ ) );

// Include required files
require_once HOSTING_GUIDER_PATH . 'includes/cpt-setup.php';
require_once HOSTING_GUIDER_PATH . 'includes/admin-ui.php';
require_once HOSTING_GUIDER_PATH . 'includes/list-meta-box.php';
require_once HOSTING_GUIDER_PATH . 'includes/api-endpoints.php';
require_once HOSTING_GUIDER_PATH . 'includes/settings-page.php';
require_once HOSTING_GUIDER_PATH . 'includes/sample-data.php';

/**
 * Register Frontend Scripts (Enqueue only when Shortcode is used)
 */
function hosting_guider_register_scripts() {
    // Register the React bundle
    $asset_file = HOSTING_GUIDER_PATH . 'dist/assets/wp-plugin.js';
    $asset_url = HOSTING_GUIDER_URL . 'dist/assets/wp-plugin.js';
    
    // Check if built file exists
    if ( file_exists( $asset_file ) ) {
        $version = filemtime( $asset_file );
        wp_register_script( 'hosting-guider-app', $asset_url, array(), $version, true );
        
        // Register the styles
        $style_file = HOSTING_GUIDER_PATH . 'dist/assets/wp-plugin.css';
        $style_url = HOSTING_GUIDER_URL . 'dist/assets/wp-plugin.css';
        if ( file_exists( $style_file ) ) {
            $style_version = filemtime( $style_file );
            wp_register_style( 'hosting-guider-styles', $style_url, array(), $style_version );

            // Inject Custom Colors
            $options = get_option( 'hosting_guider_settings' );
            if ( ! empty( $options ) ) {
                $primary = isset($options['primary_color']) ? $options['primary_color'] : '#0d9488';
                $accent  = isset($options['accent_color']) ? $options['accent_color'] : '#06b6d4';
                $secondary = isset($options['secondary_color']) ? $options['secondary_color'] : '#334155';

                $primary_hsl = hosting_guider_hex_to_hsl($primary);
                $accent_hsl  = hosting_guider_hex_to_hsl($accent);
                $secondary_hsl = hosting_guider_hex_to_hsl($secondary);

                $custom_css = "
                    :root {
                        --primary: {$primary_hsl};
                        --accent: {$accent_hsl};
                        --ring: {$primary_hsl};
                        --secondary: {$secondary_hsl};
                    }
                ";
                wp_add_inline_style( 'hosting-guider-styles', $custom_css );
            }
        }
        
        // Preload Data for Instant Render
        $preload_data = hosting_guider_get_providers();

        // Pass the API URL to the frontend
        wp_localize_script( 'hosting-guider-app', 'hostingGuiderSettings', array(
            'apiUrl' => get_rest_url( null, 'hosting-guider/v1/providers' ),
            'nonce'  => wp_create_nonce( 'wp_rest' ),
            'showPlanButtons' => ( ! empty( $options['show_plan_buttons'] ) && $options['show_plan_buttons'] === '1' ),
            'initialData' => $preload_data
        ));
    }
}
add_action( 'wp_enqueue_scripts', 'hosting_guider_register_scripts' );

/**
 * Shortcode to render the comparison tool
 * Usage: [hosting_guider_compare ids="1,2" featured="1" category="vps" limit="4"]
 */
function hosting_guider_shortcode( $atts ) {
    // Enqueue assets conditionally
    wp_enqueue_script( 'hosting-guider-app' );
    wp_enqueue_style( 'hosting-guider-styles' );

    $attributes = shortcode_atts( array(
        'ids'      => '',
        'featured' => '',
        'category' => '',
        'limit'    => '',
        'badge_texts' => '',
        'badge_colors' => '',
    ), $atts );

    // Sanitize attributes
    $config = array(
        'ids'      => !empty($attributes['ids']) ? array_map('trim', explode(',', $attributes['ids'])) : [],
        'featured' => !empty($attributes['featured']) ? array_map('trim', explode(',', $attributes['featured'])) : [],
        'category' => sanitize_text_field($attributes['category']),
        'limit'    => intval($attributes['limit']),
        'badgeTexts' => !empty($attributes['badge_texts']) ? json_decode($attributes['badge_texts'], true) : [],
        'badgeColors' => !empty($attributes['badge_colors']) ? json_decode($attributes['badge_colors'], true) : [],
    );

    // Encode config for JS
    $config_json = htmlspecialchars(json_encode($config), ENT_QUOTES, 'UTF-8');

    // Generate a unique ID for React to mount to
    return '<div id="hosting-guider-root" data-config="' . $config_json . '"></div>';
}
add_shortcode( 'hosting_guider_compare', 'hosting_guider_shortcode' );

/**
 * Shortcode for Saved Lists
 * Usage: [hosting_guider_list id="123"]
 */
function hosting_guider_list_shortcode( $atts ) {
    $attributes = shortcode_atts( array(
        'id' => '',
    ), $atts );

    if ( empty( $attributes['id'] ) ) return '';

    $post_id = intval( $attributes['id'] );
    
    // Fetch Saved Meta
    $ids = get_post_meta( $post_id, '_hg_list_ids', true );
    $featured = get_post_meta( $post_id, '_hg_list_featured', true );
    $limit = get_post_meta( $post_id, '_hg_list_limit', true );
    $badge_texts = get_post_meta( $post_id, '_hg_list_badge_texts', true ) ?: [];
    $badge_colors = get_post_meta( $post_id, '_hg_list_badge_colors', true ) ?: [];

    // Convert arrays to comma-separated strings
    $ids_str = !empty($ids) ? implode(',', (array)$ids) : '';
    $featured_str = !empty($featured) ? implode(',', (array)$featured) : '';

    // Delegate to main shortcode
    return hosting_guider_shortcode( array(
        'ids' => $ids_str,
        'featured' => $featured_str,
        'limit' => $limit,
        'badge_texts' => json_encode($badge_texts),
        'badge_colors' => json_encode($badge_colors),
    ));
}
add_shortcode( 'hosting_guider_list', 'hosting_guider_list_shortcode' );
