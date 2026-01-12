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
        'post_type'      => array( 'comparison_item', 'ecommerce_provider', 'comparison_tool' ),
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

            // Taxonomies - check both Item and Tool taxonomies
            $post_type = get_post_type($id);
            
            $categories = array();
            $features = array();
            
            $post_type = get_post_type($id);
            $categories = false;
            $features = false;
            $debug_all_terms = array(); // DEBUG: Store all found terms

            // DEBUG: Get all taxonomies for this post type
            $taxonomies = get_object_taxonomies($post_type);
            foreach ($taxonomies as $tax) {
                $terms = get_the_terms($id, $tax);
                if ($terms && !is_wp_error($terms)) {
                    $debug_all_terms[$tax] = wp_list_pluck($terms, 'name');
                }
            }

            if ( $post_type === 'comparison_tool' ) {
                $categories = get_the_terms( $id, 'tool_category' );
                if (!$categories || is_wp_error($categories)) $categories = wp_get_post_terms( $id, 'tool_category' ); 
                
                $features = get_the_terms( $id, 'tool_tag' );
                 if (!$features || is_wp_error($features)) $features = wp_get_post_terms( $id, 'tool_tag' );
            } else {
                $categories = get_the_terms( $id, 'comparison_category' );
                if ( ! $categories || is_wp_error( $categories ) ) {
                    $categories = get_the_terms( $id, 'ecommerce_type' );
                }
                
                $features = get_the_terms( $id, 'comparison_feature' );
                if ( ! $features || is_wp_error( $features ) ) {
                    $features = get_the_terms( $id, 'ecommerce_feature' );
                }
            }

            $category_names = array();
            if ( $categories && ! is_wp_error( $categories ) ) {
                foreach ( $categories as $c ) {
                    $category_names[] = $c->name;
                    if (!in_array($c->name, $all_categories)) $all_categories[] = $c->name;
                }
            }
            
            // Feature mapping logic... (reused)
            $feature_map = array(
                'products' => 'Unlimited', 
                'fees' => '0%',
                'ssl' => false,
                'support' => '24/7',
                'channels' => 'Multi',
                'storage' => 'N/A', 
                'bandwidth' => 'N/A'
            );
            
            $feature_names = array();
            if ( $features && ! is_wp_error( $features ) ) {
                foreach ( $features as $f ) {
                    $name = $f->name;
                    $feature_names[] = $name; 
                    if (!in_array($name, $all_feature_terms)) $all_feature_terms[] = $name;
                    
                    // Simple mapping for tools as well
                    $name_lower = strtolower($name);
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

            // Image
            $logo_url = get_the_post_thumbnail_url( $id, 'full' );
            if ( ! $logo_url ) {
                $logo_url = get_post_meta( $id, '_wpc_external_logo_url', true ) ?: get_post_meta( $id, '_ecommerce_external_logo_url', true ) ?: '';
            }

            // Meta Mapping based on Post Type
            if ( $post_type === 'comparison_tool' ) {
                $price = get_post_meta( $id, '_tool_price', true ) ?: ''; // Tools might not have single price, handled in pricing plans
                $period = ''; 
                $rating = get_post_meta( $id, '_wpc_tool_rating', true ) ?: 4.5;
                $details_link = get_post_meta( $id, '_tool_link', true ) ?: '#';
                $direct_link = ''; 
                $pricing_plans = get_post_meta( $id, '_wpc_tool_pricing_plans', true ) ?: array();
                $hide_plan_features = false;
                $show_plan_links = true;
                $show_coupon = false;
                $badge_text = get_post_meta( $id, '_tool_badge', true );
                $button_text = get_post_meta( $id, '_tool_button_text', true ) ?: 'View Details';
                $description = get_post_meta( $id, '_wpc_tool_short_description', true ) ?: get_the_excerpt();
                
                // Construct basic price from first plan if needed
                if ( empty( $price ) && ! empty( $pricing_plans[0]['price'] ) ) {
                   $price = $pricing_plans[0]['price'];
                   $period = $pricing_plans[0]['period'] ?? '';
                }

            } else {
                // Comparison Item Meta
                $price = get_post_meta( $id, '_wpc_price', true ) ?: get_post_meta( $id, '_ecommerce_price', true ) ?: '0';
                $period = get_post_meta( $id, '_wpc_period', true ) ?: get_post_meta( $id, '_ecommerce_period', true ) ?: '';
                $rating = get_post_meta( $id, '_wpc_rating', true ) ?: get_post_meta( $id, '_ecommerce_rating', true ) ?: 4.0;
                $details_link = get_post_meta( $id, '_wpc_details_link', true ) ?: get_post_meta( $id, '_ecommerce_details_link', true ) ?: '#';
                $direct_link = get_post_meta( $id, '_wpc_direct_link', true ) ?: '';
                $pricing_plans = get_post_meta( $id, '_wpc_pricing_plans', true ) ?: get_post_meta( $id, '_ecommerce_pricing_plans', true ) ?: array();
                $hide_plan_features = get_post_meta( $id, '_wpc_hide_plan_features', true ) === '1';
                $show_plan_links = get_post_meta( $id, '_wpc_show_plan_links', true ) === '1';
                $show_coupon = get_post_meta( $id, '_wpc_show_coupon', true ) === '1';
                $badge_text = get_post_meta( $id, '_wpc_badge_text', true );
                $button_text = get_post_meta( $id, '_wpc_button_text', true );
                $description = get_post_meta( $id, '_wpc_short_description', true ) ?: get_the_excerpt();
            }

            // Pros/Cons (Tools don't use these typically, but we can support if added later)
            $pros_raw = get_post_meta( $id, '_wpc_pros', true );
            $cons_raw = get_post_meta( $id, '_wpc_cons', true );

            $items[] = array(
                'id'       => (string) $id,
                'post_type'   => $post_type, // Debugging
                'debug_terms' => $debug_all_terms, // DEBUG
                'name'     => get_post_meta($id, '_wpc_public_name', true) ?: get_the_title(),
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
                // ... prose/cons
                'pros'     => $pros_raw ? explode( "\n", $pros_raw ) : array(),
                'cons'     => $cons_raw ? explode( "\n", $cons_raw ) : array(),
                
                // ... labels (use global or item specific)
                'prosLabel' => get_post_meta( $id, '_wpc_txt_pros_label', true ) ?: '',
                'consLabel' => get_post_meta( $id, '_wpc_txt_cons_label', true ) ?: '',
                'priceLabel' => get_post_meta( $id, '_wpc_txt_price_label', true ) ?: '',
                'ratingLabel' => get_post_meta( $id, '_wpc_txt_rating_label', true ) ?: '',
                'moSuffix' => get_post_meta( $id, '_wpc_txt_mo_suffix', true ) ?: '',
                'visitSiteLabel' => get_post_meta( $id, '_wpc_txt_visit_site', true ) ?: '',
                'couponLabel' => get_post_meta( $id, '_wpc_txt_coupon_label', true ) ?: '',
                'copiedLabel' => get_post_meta( $id, '_wpc_txt_copied_label', true ) ?: '',
                'featureHeader' => get_post_meta( $id, '_wpc_txt_feature_header', true ) ?: '',
                'raw_features' => $feature_names,
                'details_link' => $details_link,
                'direct_link' => $direct_link,
                'permalink' => get_permalink($id),
                'description' => $description,
                
                // Badge Logic
                'badge' => array(
                    'text' => $badge_text,
                    'color' => get_post_meta( $id, '_wpc_badge_color', true )
                ),
                'featured_badge_text' => get_post_meta( $id, '_wpc_featured_badge_text', true ),
                'featured_color' => get_post_meta( $id, '_wpc_featured_color', true ),
                'coupon_code' => get_post_meta( $id, '_wpc_coupon_code', true ),
                'product_details' => array(
                    'category' => get_post_meta($id, '_wpc_product_category', true),
                    'brand' => get_post_meta($id, '_wpc_brand', true),
                    'sku' => get_post_meta($id, '_wpc_sku', true),
                    'gtin' => get_post_meta($id, '_wpc_gtin', true),
                    'condition' => get_post_meta($id, '_wpc_condition', true),
                    'availability' => get_post_meta($id, '_wpc_availability', true),
                    'mfg_date' => get_post_meta($id, '_wpc_mfg_date', true),
                    'exp_date' => get_post_meta($id, '_wpc_exp_date', true),
                    'service_type' => get_post_meta($id, '_wpc_service_type', true),
                    'area_served' => get_post_meta($id, '_wpc_area_served', true),
                    'duration' => get_post_meta($id, '_wpc_duration', true),
                ),
                'custom_fields' => get_post_meta($id, '_wpc_custom_fields', true) ?: [],
                'button_text' => $button_text, // Use the variable
                'hero_subtitle' => get_post_meta( $id, '_wpc_hero_subtitle', true ),
                'analysis_label' => get_post_meta( $id, '_wpc_analysis_label', true ),
                'dashboard_image' => get_post_meta( $id, '_wpc_dashboard_image', true ),
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
                            'coupon_bg' => get_post_meta( $id, '_wpc_color_coupon_bg', true ),
                            'coupon_text' => get_post_meta( $id, '_wpc_color_coupon_text', true ),
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

    // Get ALL available terms for the filter list (Items + Tools)
    $all_cat_terms = get_terms( array( 'taxonomy' => array('comparison_category', 'tool_category'), 'hide_empty' => false ) );
    $all_feat_terms = get_terms( array( 'taxonomy' => array('comparison_feature', 'tool_tag'), 'hide_empty' => false ) );

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
