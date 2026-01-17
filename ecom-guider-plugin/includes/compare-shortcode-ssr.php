<?php
/**
 * WPC Compare Shortcode - Pure PHP SSR Version
 * Usage: [wpc_compare ids="1,2" featured="1" category="cat_slug" limit="4"]
 * No React dependency - Fast initial load with vanilla JS interactivity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SSR Compare Shortcode
 */
function wpc_compare_shortcode_ssr( $atts ) {
    $atts = shortcode_atts( array(
        'ids' => '',
        'featured' => '',
        'category' => '',
        'limit' => '',
    ), $atts );

    // Enqueue SSR assets (no React!)
    wp_enqueue_style( 'wpc-styles' );
    wp_enqueue_script( 'wpc-frontend' );
    
    // Pass settings to JS
    wp_localize_script( 'wpc-frontend', 'wpcSettings', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'wpc_nonce' ),
    ));

    // --- Fetch Items Data ---
    if ( ! function_exists( 'wpc_get_items' ) ) {
        require_once WPC_PLUGIN_DIR . 'includes/api-endpoints.php';
    }
    
    $data = wpc_get_items();
    $items = $data['items'];
    $all_categories = $data['categories'] ?? array();
    $all_features = $data['filterableFeatures'] ?? array();

    // Filter by specific IDs
    $specific_ids = ! empty( $atts['ids'] ) ? array_map( 'trim', explode( ',', $atts['ids'] ) ) : array();
    if ( ! empty( $specific_ids ) ) {
        $items = array_filter( $items, function( $item ) use ( $specific_ids ) {
            return in_array( $item['id'], $specific_ids );
        });
        $items = array_values( $items ); // Re-index
    }

    // Filter by Category
    $category_slug = sanitize_text_field( $atts['category'] );
    if ( ! empty( $category_slug ) ) {
        $items = array_filter( $items, function( $item ) use ( $category_slug ) {
            foreach ( (array) $item['category'] as $cat ) {
                if ( strtolower( $cat ) === strtolower( $category_slug ) ) return true;
            }
            return false;
        });
        $items = array_values( $items );
    }

    // Apply featured ordering
    $featured_ids = ! empty( $atts['featured'] ) ? array_map( 'trim', explode( ',', $atts['featured'] ) ) : array();
    if ( ! empty( $featured_ids ) ) {
        usort( $items, function( $a, $b ) use ( $featured_ids ) {
            $a_pos = array_search( $a['id'], $featured_ids );
            $b_pos = array_search( $b['id'], $featured_ids );
            if ( $a_pos === false && $b_pos === false ) return 0;
            if ( $a_pos === false ) return 1;
            if ( $b_pos === false ) return -1;
            return $a_pos - $b_pos;
        });
        foreach ( $items as &$item ) {
            $item['is_featured'] = in_array( $item['id'], $featured_ids );
        }
    }

    // Apply limit
    $limit = intval( $atts['limit'] );
    if ( $limit > 0 ) {
        $items = array_slice( $items, 0, $limit );
    }

    if ( empty( $items ) ) {
        return '<!-- WPC Compare: No items found -->';
    }

    // Settings
    $filter_style = get_option( 'wpc_filter_style', 'top' );
    $show_filters = get_option( 'wpc_show_filters', '1' ) === '1';
    $show_search = get_option( 'wpc_show_search', '1' ) === '1';

    // Colors
    $primary_color = get_option( 'wpc_primary_color', '#6366f1' );
    $border_color = get_option( 'wpc_border_color', '#e2e8f0' );

    ob_start();
    ?>
    <div class="wpc-compare-ssr" style="width: 100%;">
        
        <?php if ( $show_search ) : ?>
        <!-- Search Bar -->
        <div style="margin-bottom: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
            <div style="position: relative; flex: 1; min-width: 200px;">
                <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input 
                    type="text" 
                    data-wpc-search
                    placeholder="Search by name..." 
                    style="width: 100%; padding: 0.625rem 1rem 0.625rem 2.5rem; border: 1px solid <?php echo esc_attr( $border_color ); ?>; border-radius: 0.75rem; font-size: 0.875rem;"
                />
            </div>
            <span data-wpc-count style="font-size: 0.875rem; color: #6b7280;"><?php echo count( $items ); ?> items</span>
        </div>
        <?php endif; ?>

        <?php if ( $show_filters && ! empty( $all_categories ) ) : ?>
        <!-- Filters -->
        <div style="margin-bottom: 1.5rem; padding: 1rem; background: #f9fafb; border-radius: 0.75rem; border: 1px solid <?php echo esc_attr( $border_color ); ?>;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                <strong style="font-size: 0.875rem;">Filters</strong>
            </div>
            
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                <button 
                    data-wpc-filter-cat="all" 
                    class="wpc-filter-active"
                    style="padding: 0.375rem 0.75rem; background: <?php echo esc_attr( $primary_color ); ?>; color: #fff; border: none; border-radius: 9999px; font-size: 0.75rem; cursor: pointer;"
                >All</button>
                <?php foreach ( $all_categories as $cat ) : ?>
                    <button 
                        data-wpc-filter-cat="<?php echo esc_attr( $cat['name'] ?? $cat ); ?>"
                        style="padding: 0.375rem 0.75rem; background: #fff; color: #374151; border: 1px solid <?php echo esc_attr( $border_color ); ?>; border-radius: 9999px; font-size: 0.75rem; cursor: pointer;"
                    ><?php echo esc_html( $cat['name'] ?? $cat ); ?></button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Cards Grid -->
        <div class="wpc-cards-grid" style="
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1.5rem;
        ">
            <style>
                @media (min-width: 640px) { .wpc-compare-ssr .wpc-cards-grid { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; } }
                @media (min-width: 1024px) { .wpc-compare-ssr .wpc-cards-grid { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; } }
                .wpc-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important; transform: translateY(-2px); }
                .wpc-compare-selected { background: <?php echo esc_attr( $primary_color ); ?>10 !important; border-color: <?php echo esc_attr( $primary_color ); ?> !important; color: <?php echo esc_attr( $primary_color ); ?> !important; }
                .wpc-filter-active { background: <?php echo esc_attr( $primary_color ); ?> !important; color: #fff !important; border-color: <?php echo esc_attr( $primary_color ); ?> !important; }
            </style>
            
            <?php 
            foreach ( $items as $item ) : 
                $card_config = array(
                    'show_rating' => true,
                    'show_price' => true,
                    'badge_style' => 'floating',
                    'enable_comparison' => true,
                    'txt_compare' => 'Compare',
                    'txt_visit' => get_option( 'wpc_text_visit', 'Visit Site' ),
                );
                echo wpc_render_card_ssr( $item, $card_config );
            endforeach; 
            ?>
        </div>

        <!-- Compare Bar (Hidden by default) -->
        <div 
            data-wpc-compare-bar
            style="display: none; position: fixed; bottom: 0; left: 0; right: 0; background: #1f2937; color: #fff; padding: 1rem 2rem; z-index: 9999; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;"
        >
            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <span><strong data-wpc-selected-count>0</strong> selected</span>
                <div data-wpc-selected-names style="display: flex; gap: 0.5rem; flex-wrap: wrap;"></div>
            </div>
            <button 
                onclick="wpcCompareNow()"
                style="padding: 0.625rem 1.5rem; background: <?php echo esc_attr( $primary_color ); ?>; color: #fff; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer;"
            >Compare Now</button>
        </div>

        <!-- Comparison Table Container -->
        <div data-wpc-compare-table style="display: none; margin-top: 2rem;"></div>
    </div>
    <?php
    return ob_get_clean();
}

// Override the original shortcode with SSR version
// Use init hook with priority 99 to ensure this runs AFTER original registrations
function wpc_register_compare_shortcode_ssr() {
    remove_shortcode( 'wpc_compare' );
    remove_shortcode( 'ecommerce_guider_compare' );
    add_shortcode( 'wpc_compare', 'wpc_compare_shortcode_ssr' );
    add_shortcode( 'ecommerce_guider_compare', 'wpc_compare_shortcode_ssr' );
}
add_action( 'init', 'wpc_register_compare_shortcode_ssr', 99 );
