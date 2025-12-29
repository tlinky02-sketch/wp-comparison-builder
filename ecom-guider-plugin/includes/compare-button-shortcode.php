<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Compare Alternatives Button Shortcode
 * Usage: [wpc_compare_button id="6" text="Compare Alternatives" competitors="1,2,3"]
 * 
 * Displays a button that opens a dropdown with other items for comparison
 * 
 * Parameters:
 * - id: (required) Item ID
 * - text: (optional) Button text, default "Compare Alternatives"
 * - competitors: (optional) Comma-separated item IDs to show, overrides saved settings
 */
function wpc_compare_button_shortcode( $atts ) {
    // Enqueue assets
    wp_enqueue_script( 'wpc-app' );
    wp_enqueue_script( 'wpc-compare-button' );
    wp_enqueue_style( 'wpc-styles' );
    
    $attributes = shortcode_atts( array(
        'id'          => '',
        'text'        => 'Compare Alternatives',
        'competitors' => '', // comma-separated item IDs
    ), $atts );
    
    $item_id = intval( $attributes['id'] );
    
    if ( empty( $item_id ) ) {
        return '<p style="color: red;">Error: Item ID is required for compare button shortcode.</p>';
    }
    
    // Get the current item
    $current_item = get_post( $item_id );
    if ( ! $current_item || $current_item->post_type !== 'comparison_item' ) {
        // Fallback check for legacy migration issues?
        if ( ! $current_item || $current_item->post_type !== 'ecommerce_provider' ) {
             // return '<p style="color: red;">Error: Invalid item ID.</p>'; // Don't show error to user in prod?
        }
    }
    
    // Determine which competitors to show
    if ( ! empty( $attributes['competitors'] ) ) {
        // Use custom competitors from shortcode parameter
        $competitor_ids = array_map( 'intval', array_map( 'trim', explode( ',', $attributes['competitors'] ) ) );
    } else {
        // Use saved competitors from meta
        $competitor_ids = get_post_meta( $item_id, '_wpc_competitors', true );
        // Legacy fallback
        if ( empty($competitor_ids) ) {
            $competitor_ids = get_post_meta( $item_id, '_ecommerce_competitors', true );
        }
        
        if ( ! is_array( $competitor_ids ) ) {
            $competitor_ids = array();
        }
    }
    
    // Get competitor item objects
    $query_args = array(
        'post_type' => 'comparison_item',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC',
    );

    if ( empty( $competitor_ids ) ) {
        // If no specific competitors set, show all other items
        $query_args['post__not_in'] = array( $item_id );
    } else {
        // Show only selected competitors
        $query_args['post__in'] = $competitor_ids;
    }

    $all_items = get_posts( $query_args );
    
    if ( empty( $all_items ) ) {
        return ''; // No other items to compare with
    }
    
    // Generate unique ID for this button
    $unique_id = 'wpc-compare-' . $item_id . '-' . mt_rand();

    ob_start();
    ?>

    <div class="wpc-compare-wrapper" style="margin-bottom: 24px;">
        <div class="wpc-compare-button-wrapper" style="display: inline-block; position: relative;">
            <button 
                onclick="toggleCompareDropdown('<?php echo $unique_id; ?>')" 
                class="wpc-compare-btn"
                style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #6366f1; color: white; border: none; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 15px; cursor: pointer; transition: all 0.2s;"
                onmouseover="this.style.background='#4f46e5';"
                onmouseout="this.style.background='#6366f1';"
            >
                <?php echo esc_html( $attributes['text'] ); ?>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="chevron">
                    <path d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <!-- Dropdown Menu -->
            <div 
                id="<?php echo $unique_id; ?>" 
                class="wpc-compare-dropdown"
                style="display: none; position: absolute; top: calc(100% + 8px); left: 0; min-width: 320px; background: white; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); z-index: 1000; max-height: 400px; overflow-y: auto;"
            >
                <div style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; background: #f9fafb; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 10;">
                    <strong style="font-size: 13px; color: #666;">Compare with...</strong>
                    
                    <!-- Mobile specific: Compare Now button in header -->
                    <button 
                        class="mobile-compare-btn"
                        onclick="handleFinalCompare('<?php echo $unique_id; ?>', <?php echo $item_id; ?>)"
                        style="display: none; padding: 6px 12px; background: #6366f1; color: white; border: none; border-radius: 6px; font-size: 12px; font-weight: bold; cursor: pointer;"
                    >
                        Compare Now
                    </button>
                </div>

                <!-- Internal Warning Message container -->
                <div id="<?php echo $unique_id; ?>-error" style="display: none; padding: 8px 16px; background: #fee2e2; color: #b91c1c; font-size: 12px; font-weight: 600; border-bottom: 1px solid #fecaca;">
                    You can select up to 3 alternatives to compare.
                </div>

                <div style="padding: 8px;">
                    <div style="position: relative;">
                        <input 
                            type="text" 
                            id="<?php echo $unique_id; ?>-search" 
                            onkeyup="filterCompareOptions('<?php echo $unique_id; ?>')" 
                            placeholder="Search..." 
                            style="width: 100%; padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; margin-bottom: 0;"
                        />
                    </div>
                </div>
                <div id="<?php echo $unique_id; ?>-options">
                    <?php foreach ( $all_items as $p ) : 
                        $logo = get_the_post_thumbnail_url( $p->ID, 'thumbnail' );
                        if ( ! $logo ) {
                            $logo = get_post_meta( $p->ID, '_wpc_external_logo_url', true );
                        }
                    ?>
                    <div 
                        onclick="toggleItemSelection('<?php echo $unique_id; ?>', '<?php echo $p->ID; ?>', '<?php echo esc_js($p->post_title); ?>', <?php echo $item_id; ?>)"
                        class="compare-option-<?php echo $unique_id; ?> compare-option-item" 
                        data-id="<?php echo $p->ID; ?>"
                        data-name="<?php echo esc_attr( strtolower( $p->post_title ) ); ?>"
                        style="display: flex; align-items: center; gap: 12px; padding: 10px 16px; color: #333; text-decoration: none; border-bottom: 1px solid #f3f4f6; transition: background 0.2s; cursor: pointer;"
                        onmouseover="this.style.background='#f8fafc'" 
                        onmouseout="this.style.background='white'"
                    >
                        <?php if ( $logo ) : ?>
                            <img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $p->post_title ); ?>" style="width: 32px; height: 32px; object-fit: contain; border-radius: 6px; border: 1px solid #e2e8f0; padding: 3px; background: white;">
                        <?php else : ?>
                            <div style="width: 32px; height: 32px; background: #f1f5f9; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #6366f1; font-size: 12px;">
                                <?php echo esc_html( substr( $p->post_title, 0, 1 ) ); ?>
                            </div>
                        <?php endif; ?>
                        <span style="font-weight: 500; flex: 1; font-size: 14px;"><?php echo esc_html( $p->post_title ); ?></span>
                        <div class="selection-indicator">
                            <svg class="chevron" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="opacity: 0.3;">
                                <path d="M9 5l7 7-7 7"/>
                            </svg>
                            <svg class="checkmark" width="18" height="18" fill="none" stroke="#6366f1" stroke-width="3" viewBox="0 0 24 24" style="display: none;">
                                <path d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- React Comparison Container -->
    <div id="wpc-compare-<?php echo $item_id; ?>" class="wpc-root" data-config='{"ids":[],"featured":[],"hideFilters":true,"compareButtonMode":true}' style="display: block; width: 100%; margin-top: 12px;"></div>
    
    <style>
    @media (max-width: 768px) {
        .wpc-compare-dropdown { width: 90vw !important; left: 5vw !important; position: fixed !important; top: 20% !important; max-height: 60vh !important; }
        .mobile-compare-btn { display: block !important; }
    }
    </style>


    <?php
    
    return ob_get_clean();
}
// New Shortcode
add_shortcode( 'wpc_compare_button', 'wpc_compare_button_shortcode' );
// Legacy Shortcode
add_shortcode( 'ecommerce_compare_button', 'wpc_compare_button_shortcode' );
