<?php
/**
 * WPC List Shortcode - Pure PHP SSR Version
 * Usage: [wpc_list id="123"]
 * No React dependency - Fast initial load with vanilla JS interactivity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SSR List Shortcode
 */
function wpc_list_shortcode_ssr( $atts ) {
    $atts = shortcode_atts( array(
        'id' => '',
        'style' => '',
    ), $atts );

    if ( empty( $atts['id'] ) ) return '';

    $post_id = intval( $atts['id'] );
    
    // Enqueue SSR assets (no React!)
    wp_enqueue_style( 'wpc-styles' );
    wp_enqueue_script( 'wpc-frontend' );
    
    // Pass settings to JS
    wp_localize_script( 'wpc-frontend', 'wpcSettings', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'wpc_nonce' ),
    ));

    // --- Fetch List Meta ---
    $ids = get_post_meta( $post_id, '_wpc_list_ids', true ) ?: get_post_meta( $post_id, '_hg_list_ids', true ) ?: array();
    $featured = get_post_meta( $post_id, '_wpc_list_featured', true ) ?: get_post_meta( $post_id, '_hg_list_featured', true ) ?: array();
    $limit = get_post_meta( $post_id, '_wpc_list_limit', true ) ?: 0;
    $badge_texts_raw = get_post_meta( $post_id, '_wpc_list_badge_texts', true ) ?: array();
    $badge_colors_raw = get_post_meta( $post_id, '_wpc_list_badge_colors', true ) ?: array();

    // Settings
    $enable_comparison = get_post_meta( $post_id, '_wpc_list_enable_comparison', true );
    if ( $enable_comparison === '' ) $enable_comparison = '1';
    $list_button_text = get_post_meta( $post_id, '_wpc_list_button_text', true );
    $filter_layout = get_post_meta( $post_id, '_wpc_list_filter_layout', true ) ?: 'top';
    $filter_cats = get_post_meta( $post_id, '_wpc_list_filter_cats', true ) ?: array();
    $filter_feats = get_post_meta( $post_id, '_wpc_list_filter_feats', true ) ?: array();
    $show_all_enabled = get_post_meta( $post_id, '_wpc_list_show_all_enabled', true );
    if ( $show_all_enabled === '' ) $show_all_enabled = '1';
    $initial_visible = intval( get_post_meta( $post_id, '_wpc_list_initial_visible', true ) ) ?: 8;

    // Display options
    $badge_style = get_post_meta( $post_id, '_wpc_list_badge_style', true ) ?: 'floating';
    $show_rating = get_post_meta( $post_id, '_wpc_list_show_rating', true );
    if ( $show_rating === '' ) $show_rating = '1';
    $show_price = get_post_meta( $post_id, '_wpc_list_show_price', true );
    if ( $show_price === '' ) $show_price = '1';

    // Text customization
    $txt_compare = get_post_meta( $post_id, '_wpc_list_txt_compare', true ) ?: 'Compare';
    $txt_view = get_post_meta( $post_id, '_wpc_list_txt_view', true ) ?: 'View Details';
    $txt_visit = get_post_meta( $post_id, '_wpc_list_txt_visit', true ) ?: 'Visit Site';

    // Filter visibility
    $show_filters_opt = get_post_meta( $post_id, '_wpc_list_show_filters_opt', true ) ?: 'default';
    $show_filters = ( $show_filters_opt === 'show' ) || ( $show_filters_opt === 'default' && get_option( 'wpc_show_filters', '1' ) === '1' );
    $show_search_opt = get_post_meta( $post_id, '_wpc_list_show_search_opt', true ) ?: 'default';
    $show_search = ( $show_search_opt === 'show' ) || ( $show_search_opt === 'default' && get_option( 'wpc_show_search', '1' ) === '1' );

    // --- Fetch Items Data ---
    if ( ! function_exists( 'wpc_fetch_items_data' ) ) {
        require_once WPC_PLUGIN_DIR . 'includes/api-endpoints.php';
    }
    
    $data = wpc_fetch_items_data( $ids );
    $items = $data['items'] ?? array();

    if ( empty( $items ) ) {
        return '<!-- WPC List: No items found -->';
    }

    // Apply featured ordering
    if ( ! empty( $featured ) ) {
        usort( $items, function( $a, $b ) use ( $featured ) {
            $a_pos = array_search( $a['id'], $featured );
            $b_pos = array_search( $b['id'], $featured );
            if ( $a_pos === false && $b_pos === false ) return 0;
            if ( $a_pos === false ) return 1;
            if ( $b_pos === false ) return -1;
            return $a_pos - $b_pos;
        });
        // Mark featured items
        foreach ( $items as &$item ) {
            $item['is_featured'] = in_array( $item['id'], $featured );
        }
    }

    // Apply limit
    $total_items = count( $items );
    $limit = intval( $limit );
    if ( $limit > 0 ) {
        $items = array_slice( $items, 0, $limit );
    }

    // --- Prepare Categories for Filters ---
    $all_categories = array();
    $all_features = array();
    foreach ( $items as $item ) {
        if ( ! empty( $item['category'] ) ) {
            foreach ( (array) $item['category'] as $cat ) {
                $all_categories[ $cat ] = true;
            }
        }
        if ( ! empty( $item['features'] ) ) {
            foreach ( (array) $item['features'] as $feat ) {
                if ( is_array( $feat ) ) {
                    $feat_name = isset( $feat['name'] ) && is_string( $feat['name'] ) ? $feat['name'] : '';
                } else {
                    $feat_name = is_string( $feat ) ? $feat : '';
                }
                if ( $feat_name && is_string( $feat_name ) ) {
                    $all_features[ $feat_name ] = true;
                }
            }
        }
    }
    $all_categories = array_keys( $all_categories );
    $all_features = array_keys( $all_features );

    // Colors
    $primary_color = get_option( 'wpc_primary_color', '#6366f1' );
    $border_color = get_option( 'wpc_border_color', '#e2e8f0' );

    ob_start();
    ?>
    <div class="wpc-list-ssr" style="width: 100%; padding-bottom: 80px;">
        
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

        <?php if ( $show_filters && ( ! empty( $all_categories ) || ! empty( $all_features ) ) ) : ?>
        <!-- Filters -->
        <div style="margin-bottom: 1.5rem; padding: 1rem; background: #f9fafb; border-radius: 0.75rem; border: 1px solid <?php echo esc_attr( $border_color ); ?>;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                <strong style="font-size: 0.875rem;">Filters</strong>
            </div>
            
            <?php if ( ! empty( $all_categories ) ) : ?>
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.5rem;">
                <button 
                    data-wpc-filter-cat="all" 
                    class="wpc-filter-active"
                    style="padding: 0.375rem 0.75rem; background: <?php echo esc_attr( $primary_color ); ?>; color: #fff; border: none; border-radius: 9999px; font-size: 0.75rem; cursor: pointer;"
                >All</button>
                <?php foreach ( $all_categories as $cat ) : ?>
                    <button 
                        data-wpc-filter-cat="<?php echo esc_attr( $cat ); ?>"
                        style="padding: 0.375rem 0.75rem; background: #fff; color: #374151; border: 1px solid <?php echo esc_attr( $border_color ); ?>; border-radius: 9999px; font-size: 0.75rem; cursor: pointer;"
                    ><?php echo esc_html( $cat ); ?></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Cards Grid -->
        <div class="wpc-cards-grid" style="
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1.5rem;
        ">
            <style>
                @media (min-width: 640px) { .wpc-cards-grid { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; } }
                @media (min-width: 1024px) { .wpc-cards-grid { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; } }
                .wpc-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important; transform: translateY(-2px); }
                .wpc-compare-selected { background: <?php echo esc_attr( $primary_color ); ?>10 !important; border-color: <?php echo esc_attr( $primary_color ); ?> !important; color: <?php echo esc_attr( $primary_color ); ?> !important; }
                .wpc-filter-active { background: <?php echo esc_attr( $primary_color ); ?> !important; color: #fff !important; border-color: <?php echo esc_attr( $primary_color ); ?> !important; }
                .wpc-selected-pill { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; background: #fff; border-radius: 9999px; font-size: 0.75rem; }
                .wpc-selected-pill button { background: none; border: none; cursor: pointer; color: #ef4444; font-size: 1rem; line-height: 1; }
            </style>
            
            <?php 
            $visible_count = 0;
            foreach ( $items as $idx => $item ) : 
                $is_hidden = ( $show_all_enabled === '1' && $idx >= $initial_visible );
                $badge_text = isset( $badge_texts_raw[ $item['id'] ] ) ? $badge_texts_raw[ $item['id'] ] : '';
                $badge_color = isset( $badge_colors_raw[ $item['id'] ] ) ? $badge_colors_raw[ $item['id'] ] : '';
                
                $card_config = array(
                    'show_rating' => $show_rating === '1',
                    'show_price' => $show_price === '1',
                    'badge_text' => $badge_text,
                    'badge_color' => $badge_color,
                    'badge_style' => $badge_style,
                    'enable_comparison' => $enable_comparison === '1',
                    'button_text' => $list_button_text,
                    'txt_compare' => $txt_compare,
                    'txt_view' => $txt_view,
                    'txt_visit' => $txt_visit,
                );
                
                if ( $is_hidden ) {
                    echo '<div data-wpc-hidden="true" style="display:none;">';
                }
                echo wpc_render_card_ssr( $item, $card_config );
                if ( $is_hidden ) {
                    echo '</div>';
                } else {
                    $visible_count++;
                }
            endforeach; 
            ?>
        </div>

        <?php if ( $show_all_enabled === '1' && count( $items ) > $initial_visible ) : ?>
        <!-- Load More -->
        <div style="text-align: center; margin-top: 1.5rem;">
            <button 
                data-wpc-load-more="4"
                style="padding: 0.75rem 2rem; background: <?php echo esc_attr( $primary_color ); ?>; color: #fff; border: none; border-radius: 0.5rem; font-weight: 500; cursor: pointer;"
            >
                Show More (<?php echo count( $items ) - $initial_visible; ?> remaining)
            </button>
        </div>
        <?php endif; ?>

        <?php if ( $enable_comparison === '1' ) : ?>
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
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// SSR version disabled - using React for better styling
// To re-enable SSR, uncomment below:
/*
function wpc_register_list_shortcode_ssr() {
    remove_shortcode( 'wpc_list' );
    remove_shortcode( 'ecommerce_guider_list' );
    add_shortcode( 'wpc_list', 'wpc_list_shortcode_ssr' );
    add_shortcode( 'ecommerce_guider_list', 'wpc_list_shortcode_ssr' );
}
add_action( 'init', 'wpc_register_list_shortcode_ssr', 99 );
*/
