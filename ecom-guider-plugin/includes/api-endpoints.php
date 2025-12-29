<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register API Routes
 */
function wpc_register_api() {
    register_rest_route( 'wpc/v1', '/items', array(
        'methods'  => 'GET',
        'callback' => 'wpc_get_items',
        'permission_callback' => '__return_true', // Public endpoint
    ));

    register_rest_route( 'wpc/v1', '/track/click', array(
        'methods'  => 'POST',
        'callback' => 'wpc_track_click',
        'permission_callback' => '__return_true', // Public endpoint
    ));
}
add_action( 'rest_api_init', 'wpc_register_api' );

/**
 * Get Items and Filters
 */
function wpc_get_items() {
    $items = array();

    // Query all items (support both new and legacy types)
    $args = array(
        'post_type'      => array( 'comparison_item', 'ecommerce_provider' ),
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    );
    $query = new WP_Query( $args );

    $all_categories = array();
    $all_feature_terms = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $id = get_the_ID();

            // Meta with fallbacks
            $price = get_post_meta( $id, '_wpc_price', true ) ?: get_post_meta( $id, '_ecommerce_price', true ) ?: '0';
            $period = get_post_meta( $id, '_wpc_period', true ) ?: get_post_meta( $id, '_ecommerce_period', true ) ?: '';
            $rating = get_post_meta( $id, '_wpc_rating', true ) ?: get_post_meta( $id, '_ecommerce_rating', true ) ?: 4.0;
            $pros_raw = get_post_meta( $id, '_wpc_pros', true ) ?: get_post_meta( $id, '_ecommerce_pros', true );
            $cons_raw = get_post_meta( $id, '_wpc_cons', true ) ?: get_post_meta( $id, '_ecommerce_cons', true );
            
            // Taxonomies - check both
            $categories = get_the_terms( $id, 'comparison_category' );
            if ( ! $categories || is_wp_error( $categories ) ) {
                $categories = get_the_terms( $id, 'ecommerce_type' );
            }
            // ... (rest of category logic)
            
            // Features - check both
            $features = get_the_terms( $id, 'comparison_feature' );
            if ( ! $features || is_wp_error( $features ) ) {
                $features = get_the_terms( $id, 'ecommerce_feature' );
            }
            $category_names = array();
            if ( $categories && ! is_wp_error( $categories ) ) {
                foreach ( $categories as $c ) {
                    $category_names[] = $c->name;
                    if (!in_array($c->name, $all_categories)) $all_categories[] = $c->name;
                }
            }

            $features = get_the_terms( $id, 'comparison_feature' );
            
            // Default Feature Map for Frontend
            // We will need to update the React frontend to respect these keys
            $feature_map = array(
                'products' => 'Unlimited', 
                'fees' => '0%',
                'ssl' => false,
                'support' => '24/7',
                'channels' => 'Multi',
                // Keep some old ones for compatibility until frontend is updated?
                // Actually safer to providing them as null or defaults to prevent crash
                'storage' => 'N/A', 
                'bandwidth' => 'N/A'
            );
            
            $feature_names = array();
            if ( $features && ! is_wp_error( $features ) ) {
                foreach ( $features as $f ) {
                    $name = $f->name;
                    $feature_names[] = $name; // Collect raw names
                    $name_lower = strtolower($name);
                    
                    if (!in_array($name, $all_feature_terms)) $all_feature_terms[] = $name;

                    // Mapping Logic for Ecommerce
                    if ( strpos( $name_lower, 'ssl' ) !== false ) $feature_map['ssl'] = true;
                    if ( strpos( $name_lower, 'support' ) !== false ) $feature_map['support'] = $name;
                    
                    // Products
                    if ( strpos( $name_lower, 'product' ) !== false ) {
                        $feature_map['products'] = trim(str_ireplace(array('products', 'unlimited'), '', $name));
                        if (empty($feature_map['products'])) $feature_map['products'] = 'Unlimited';
                    }
                    
                    // Fees
                    if ( strpos( $name_lower, 'fee' ) !== false ) {
                         $feature_map['fees'] = $name;
                    }
                }
            }

            // Features processing done above within loop logic, using $features variable which now has fallback
            
            // Image
            $logo_url = get_the_post_thumbnail_url( $id, 'full' );
            if ( ! $logo_url ) {
                $logo_url = get_post_meta( $id, '_wpc_external_logo_url', true ) ?: get_post_meta( $id, '_ecommerce_external_logo_url', true ) ?: '';
            }

            // Details Link 
            $details_link = get_post_meta( $id, '_wpc_details_link', true ) ?: get_post_meta( $id, '_ecommerce_details_link', true ) ?: '#';
            $direct_link = get_post_meta( $id, '_wpc_direct_link', true ) ?: '';
            
            // Pricing Plans
            $pricing_plans = get_post_meta( $id, '_wpc_pricing_plans', true ) ?: get_post_meta( $id, '_ecommerce_pricing_plans', true ) ?: array();
            $hide_plan_features = get_post_meta( $id, '_wpc_hide_plan_features', true ) === '1' || get_post_meta( $id, '_ecommerce_hide_plan_features', true ) === '1';
            $show_plan_links = get_post_meta( $id, '_wpc_show_plan_links', true ) === '1' || get_post_meta( $id, '_ecommerce_show_plan_links', true ) === '1';
            $show_coupon = get_post_meta( $id, '_wpc_show_coupon', true ) === '1' || get_post_meta( $id, '_ecommerce_show_coupon', true ) === '1';

            $items[] = array(
                'id'       => (string) $id,
                'name'     => get_the_title(),
                'logo'     => $logo_url,
                'rating'   => (float) $rating,
                'category' => $category_names,
                'price'    => $price,
                'period'   => $period,
                'features' => $feature_map,
                'pricing_plans' => $pricing_plans,
                'hide_plan_features' => $hide_plan_features, 
                'show_plan_links' => $show_plan_links,
                'show_coupon' => $show_coupon,
                'pros'     => $pros_raw ? explode( "\n", $pros_raw ) : array(),
                'cons'     => $cons_raw ? explode( "\n", $cons_raw ) : array(),
                'raw_features' => $feature_names,
                'details_link' => $details_link,
                'direct_link' => $direct_link,
                'permalink' => get_permalink($id),
                'description' => get_the_excerpt(),
                'primary_categories' => (function() use ($id) {
                    $p_ids = get_post_meta( $id, '_wpc_primary_cats', true ) ?: [];
                    $p_names = [];
                    foreach($p_ids as $pid) {
                        $term = get_term($pid, 'comparison_category');
                        if ($term && !is_wp_error($term)) $p_names[] = $term->name;
                    }
                    return $p_names;
                })(),

                'badge' => array(
                    'text' => get_post_meta( $id, '_wpc_badge_text', true ),
                    'color' => get_post_meta( $id, '_wpc_badge_color', true )
                ),
                'featured_badge_text' => get_post_meta( $id, '_wpc_featured_badge_text', true ),
                'featured_color' => get_post_meta( $id, '_wpc_featured_color', true ),
                'coupon_code' => get_post_meta( $id, '_wpc_coupon_code', true ),
                'featured_color' => get_post_meta( $id, '_wpc_featured_color', true ),
                'coupon_code' => get_post_meta( $id, '_wpc_coupon_code', true ),
                'button_text' => get_post_meta( $id, '_wpc_button_text', true ),
                // Footer / Display Settings (Independent of Design Overrides)
                'footer_button_text' => get_post_meta( $id, '_wpc_footer_button_text', true ),
                'show_footer_popup' => get_post_meta( $id, '_wpc_show_footer_popup', true ) !== '0',
                'show_footer_table' => get_post_meta( $id, '_wpc_show_footer_table', true ) !== '0',
                
                // Design Overrides (Explicitly passed for Popup Scope)
                'design_overrides' => (function() use ($id) {
                    $enabled = get_post_meta( $id, '_wpc_enable_design_overrides', true );
                    if ($enabled === '1') {
                        return [
                            'enabled' => true,
                            'primary' => get_post_meta( $id, '_wpc_primary_color', true ),
                            'accent' => get_post_meta( $id, '_wpc_accent_color', true ),
                            'border' => get_post_meta( $id, '_wpc_border_color', true ),
                            // Granular control
                            'show_footer_popup' => get_post_meta( $id, '_wpc_show_footer_popup', true ) !== '0',
                            'show_footer_table' => get_post_meta( $id, '_wpc_show_footer_table', true ) !== '0',
                            // Legacy fallback (alias popup)
                            'show_footer' => get_post_meta( $id, '_wpc_show_footer_button', true ) !== '0',
                            'footer_text' => get_post_meta( $id, '_wpc_footer_button_text', true ),
                        ];
                    }
                    return ['enabled' => false];
                })(),
                
                'content' => apply_filters( 'the_content', get_the_content() )
            );
        }
        wp_reset_postdata();
    }

    // Get ALL available terms for the filter list
    $all_cat_terms = get_terms( array( 'taxonomy' => 'comparison_category', 'hide_empty' => false ) );
    $all_feat_terms = get_terms( array( 'taxonomy' => 'comparison_feature', 'hide_empty' => false ) );

    $all_cat_names = ! is_wp_error($all_cat_terms) ? wp_list_pluck($all_cat_terms, 'name') : [];
    $all_feat_names = ! is_wp_error($all_feat_terms) ? wp_list_pluck($all_feat_terms, 'name') : [];

    return array(
        'items' => $items,
        'categories' => $all_cat_names,
        'filterableFeatures' => $all_feat_names 
    );
}

/**
 * Track Outbound Click
 */
function wpc_track_click( $request ) {
    $item_id = $request->get_param( 'id' );
    
    if ( ! $item_id ) {
        return new WP_Error( 'no_id', 'Item ID is required', array( 'status' => 400 ) );
    }

    // Simple Counter (Meta Field)
    $current_clicks = (int) get_post_meta( $item_id, '_wpc_clicks', true );
    update_post_meta( $item_id, '_wpc_clicks', $current_clicks + 1 );

    return array( 'success' => true, 'clicks' => $current_clicks + 1 );
}
