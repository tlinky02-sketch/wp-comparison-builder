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
 * Get Items and Filters (Internal Helper)
 * @param array $specific_ids Optional array of post IDs to fetch
 * @param int $limit Optional limit
 */
function wpc_fetch_items_data( $specific_ids = array(), $limit = -1 ) {
    $items = array();

    // Query all items (support both new and legacy types)
    $args = array(
        'post_type'      => array( 'comparison_item', 'ecommerce_provider', 'comparison_tool' ),
        'posts_per_page' => $limit,
        'post_status'    => 'publish',
    );
    
    // Add specific ID filtering if provided
    if ( ! empty( $specific_ids ) ) {
        $args['post__in'] = $specific_ids;
        $args['orderby'] = 'post__in';
    }

    $query = new WP_Query( $args );

    // HYBRID FETCH: pre-load data from Custom Table
    $db_data = array();
    if ( $query->have_posts() && class_exists('WPC_Database') ) {
        $found_ids = wp_list_pluck( $query->posts, 'ID' );
        $db = new WPC_Database();
        $rows = $db->get_items( $found_ids );
        foreach ( $rows as $r ) {
            $db_data[ $r->post_id ] = $r;
        }
    }

    // HYBRID FETCH: Tools
    $tools_db_data = array();
    if ( $query->have_posts() && class_exists('WPC_Tools_Database') && get_option('wpc_enable_tools_module') === '1' ) {
        $found_ids = wp_list_pluck( $query->posts, 'ID' );
        $tools_db = new WPC_Tools_Database();
        $tool_rows = $tools_db->get_tools( $found_ids );
        foreach ( $tool_rows as $tr ) {
            $tools_db_data[ $tr->post_id ] = $tr;
        }
    }

    $all_categories = array();
    $all_feature_terms = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $id = get_the_ID();
            $row = isset( $db_data[$id] ) ? $db_data[$id] : null; // Custom Table Row

            // Helper to get value: DB > WPC Meta > Legacy Meta > Default
            $get_val = function($db_col, $meta_key, $legacy_key = '', $default = '') use ($row, $id) {
                if ( $row && isset( $row->$db_col ) ) {
                    // Check strict null? Or just truthy?
                    // For strings, empty string in DB means empty.
                    // But for JSON arrays, empty array is valid.
                    return $row->$db_col;
                }
                $val = get_post_meta( $id, $meta_key, true );
                if ( ! empty( $val ) ) return $val;
                if ( $legacy_key ) {
                    $val = get_post_meta( $id, $legacy_key, true );
                    if ( ! empty( $val ) ) return $val;
                }
                return $default;
            };
            
            // Taxonomies - check both Item and Tool taxonomies
            $post_type = get_post_type($id);
            
            $categories = array();
            $features = array();
            
            // DEBUG: Get all taxonomies for this post type
            $taxonomies = get_object_taxonomies($post_type);
            // (Removed debug_all_terms block strictly for cleanliness, but specific logic below is crucial)

            if ( $post_type === 'comparison_tool' ) {
                $categories = get_the_terms( $id, 'tool_category' );
                if (!$categories || is_wp_error($categories)) $categories = wp_get_post_terms( $id, 'tool_category' ); 
                
                $features = get_the_terms( $id, 'tool_tag' );
                 if (!$features || is_wp_error($features)) $features = wp_get_post_terms( $id, 'tool_tag' );
            } else {
                $categories = get_the_terms( $id, 'comparison_category' );
                $features = get_the_terms( $id, 'comparison_feature' );
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
                'products' => '', 
                'fees' => '',
                'ssl' => false,
                'support' => '',
                'channels' => '',
                'storage' => '', 
                'bandwidth' => ''
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
            // Logic: Check overridden URL first (DB or Meta). NO FALLBACK to Featured Image for Logo anymore (per user request).
            $custom_logo = $get_val('logo_url', '_wpc_external_logo_url', '_ecommerce_external_logo_url');
            $logo_url = $custom_logo ?: '';

            // Dashboard / Hero Image
            // Logic: Featured Image (Priority) > Dashboard Image Field (Fallback)
            $featured_img = get_the_post_thumbnail_url( $id, 'full' );
            $dashboard_field_val = $get_val('dashboard_image', '_wpc_dashboard_image');
            
            $dashboard_img = $featured_img ?: $dashboard_field_val;

            // Data Mapping
            if ( $post_type === 'comparison_tool' ) {
                // Try Custom Table First (Hybrid)
                if ( isset($tools_db_data[$id]) ) {
                    $t = $tools_db_data[$id];
                    $price = $t->pricing_plans[0]['price'] ?? ''; 
                    $period = $t->pricing_plans[0]['period'] ?? ''; 
                    $rating = $t->rating;
                    $details_link = $t->link;
                    $direct_link = ''; 
                    $pricing_plans = $t->pricing_plans;
                    $badge_text = $t->badge_text;
                    $button_text = $t->button_text;
                    $description = $t->short_description;
                } else {
                    // Fallback to Meta
                    $price = get_post_meta( $id, '_tool_price', true ) ?: ''; 
                    $period = ''; 
                    $rating = get_post_meta( $id, '_wpc_tool_rating', true ) ?: 4.5;
                    $details_link = get_post_meta( $id, '_tool_link', true ) ?: '#';
                    $direct_link = ''; 
                    $pricing_plans = get_post_meta( $id, '_wpc_tool_pricing_plans', true ) ?: array();
                    $badge_text = get_post_meta( $id, '_tool_badge', true );
                    $button_text = get_post_meta( $id, '_tool_button_text', true ) ?: 'View Details';
                    $description = get_post_meta( $id, '_wpc_tool_short_description', true ) ?: get_the_excerpt();
                    
                    if ( empty( $price ) && ! empty( $pricing_plans[0]['price'] ) ) {
                       $price = $pricing_plans[0]['price'];
                       $period = $pricing_plans[0]['period'] ?? '';
                    }
                }
                
                $hide_plan_features = false;
                $show_plan_links = true;
                $show_coupon = false;

            } else {
                // Comparison Item (Uses Helper)
                $price = $get_val('price', '_wpc_price', '_ecommerce_price', '0');
                $period = $get_val('period', '_wpc_period', '_ecommerce_period');
                $rating = $get_val('rating', '_wpc_rating', '_ecommerce_rating', 4.0);
                $details_link = $get_val('details_link', '_wpc_details_link', '_ecommerce_details_link', '#');
                $direct_link = $get_val('direct_link', '_wpc_direct_link');
                
                // Arrays/JSON
                $pricing_plans = ($row && !empty($row->pricing_plans)) ? $row->pricing_plans : (get_post_meta( $id, '_wpc_pricing_plans', true ) ?: get_post_meta( $id, '_ecommerce_pricing_plans', true ) ?: array());

                // Robust Price Fallback Logic:
                // 1. Try Monthly from plans
                // 2. Fallback to any other cycle (Yearly, etc.) from plans
                // 3. Keep the manual price field value ($price already set)
                // 4. If still empty, use configurable "Free" text
                $plan_price_found = false;
                if ( ! empty( $pricing_plans ) && is_array( $pricing_plans ) ) {
                    foreach ( $pricing_plans as $p ) {
                        // Check new dynamic 'prices' structure for monthly FIRST
                        if ( ! empty($p['prices']) && isset($p['prices']['monthly']) && ! empty($p['prices']['monthly']['amount']) ) {
                            $price = $p['prices']['monthly']['amount'];
                            $period = isset($p['prices']['monthly']['period']) ? $p['prices']['monthly']['period'] : '/mo';
                            $plan_price_found = true;
                            break;
                        }
                    }
                    // If no monthly found, try any other cycle
                    if ( ! $plan_price_found ) {
                        foreach ( $pricing_plans as $p ) {
                            if ( ! empty($p['prices']) && is_array($p['prices']) ) {
                                foreach ( $p['prices'] as $cycle_slug => $cycle_data ) {
                                    if ( ! empty($cycle_data['amount']) ) {
                                        $price = $cycle_data['amount'];
                                        $period = isset($cycle_data['period']) ? $cycle_data['period'] : '';
                                        $plan_price_found = true;
                                        break 2; // Exit both loops
                                    }
                                }
                            }
                            // Legacy fallback
                            elseif ( ! empty($p['price']) ) {
                                $price = $p['price'];
                                $period = isset($p['period']) ? $p['period'] : '/mo';
                                $plan_price_found = true;
                                break;
                            }
                        }
                    }
                }
                
                // If price is still empty or '0', use global "Free" text
                if ( empty($price) || $price === '0' || $price === 0 ) {
                    $price = get_option( 'wpc_text_empty_price', 'Free' );
                    $period = ''; // No period for "Free"
                }

                // Booleans / Checkboxes
                // In DB, these are 1/0 ints. In Meta "1" string.
                if ($row) {
                    $hide_plan_features = $row->hide_plan_features == 1;
                    $show_plan_links = $row->show_plan_links == 1;
                    $show_coupon = $row->show_coupon == 1;
                } else {
                    $hide_plan_features = get_post_meta( $id, '_wpc_hide_plan_features', true ) === '1';
                    $show_plan_links = get_post_meta( $id, '_wpc_show_plan_links', true ) === '1';
                    $show_coupon = get_post_meta( $id, '_wpc_show_coupon', true ) === '1';
                }

                $badge_text = $get_val('badge_text', '_wpc_badge_text');
                $button_text = $get_val('button_text', '_wpc_button_text');
                $hero_button_text = get_post_meta($id, '_wpc_hero_button_text', true);
                $description = $get_val('short_description', '_wpc_short_description') ?: get_the_excerpt();
                // Duplicate assignment in original, preserving structure if user wants it, but likely can just keep one. 
                // Wait, original had duplication. I will just keep strict logic flow.
            }

            // Design Overrides Logic (Kept simple variable here, closure in array)
            // But wait, user specifically asked for the closure IN the array.
            
            $primary_cat_ids = get_post_meta( $id, '_wpc_primary_cats', true );
            $primary_category_names = array();
            if ( ! empty( $primary_cat_ids ) && is_array( $primary_cat_ids ) ) {
                foreach ( $primary_cat_ids as $p_id ) {
                    $term = get_term( $p_id );
                    if ( $term && ! is_wp_error( $term ) ) {
                        $primary_category_names[] = $term->name;
                    }
                }
            }
            $primary_feat_ids = get_post_meta( $id, '_wpc_primary_features', true );
            
            $primary_feature_names = array();
            if ( ! empty( $primary_feat_ids ) && is_array( $primary_feat_ids ) ) {
                foreach ( $primary_feat_ids as $f_id ) {
                    $term = get_term( (int) $f_id, 'comparison_feature' );
                    if ( $term && ! is_wp_error( $term ) ) {
                        $primary_feature_names[] = $term->name;
                    }
                }
            }

            // Pros/Cons
            $pros = ($row && !empty($row->pros)) ? $row->pros : (get_post_meta( $id, '_wpc_pros', true ) ? explode( "\n", get_post_meta( $id, '_wpc_pros', true ) ) : array());
            $cons = ($row && !empty($row->cons)) ? $row->cons : (get_post_meta( $id, '_wpc_cons', true ) ? explode( "\n", get_post_meta( $id, '_wpc_cons', true ) ) : array());
            
            // Labels (JSON in DB 'text_labels' column vs individual keys in Meta)
            $labels = [];
            if ($row && !empty($row->text_labels)) {
                $labels = $row->text_labels;
            }
            // Helper to get label with fallback
            $l = function($key, $meta) use ($labels, $id) {
                return $labels[$key] ?? get_post_meta($id, $meta, true) ?: '';
            };

            $items[] = array(
                'id'       => (string) $id,
                'post_type'   => $post_type, 

                'name'     => $get_val('public_name', '_wpc_public_name') ?: get_the_title(),
                'logo'     => $logo_url,
                'rating'   => (float) $rating,
                'category' => $category_names,
                'primary_categories' => $primary_category_names,
                'primary_features' => $primary_feature_names,
                'price'    => (string) $price,
                'period'   => (string) $period,
                'features' => array_merge($feature_map, (array)$features),
                'pricing_plans' => $pricing_plans,
                'plan_features' => get_post_meta($id, '_wpc_plan_features', true) ?: [],
                'use_cases' => get_post_meta($id, '_wpc_use_cases', true) ?: [],
                'hide_plan_features' => $hide_plan_features, 
                'show_plan_links' => $show_plan_links,
                'show_coupon' => $show_coupon,
                
                // Billing Mode Settings
                'billing_mode' => get_post_meta($id, '_wpc_billing_mode', true) ?: 'monthly_only',
                'monthly_label' => get_post_meta($id, '_wpc_monthly_label', true) ?: 'Pay monthly',
                'yearly_label' => get_post_meta($id, '_wpc_yearly_label', true) ?: 'Pay yearly (save 25%)*',
                'default_billing' => get_post_meta($id, '_wpc_default_billing', true) ?: 'monthly',
                'billing_cycles' => array_values( get_post_meta($id, '_wpc_billing_cycles', true) ?: [] ), // Force sequential array
                'default_cycle' => get_post_meta($id, '_wpc_default_cycle', true) ?: 'monthly',
                'billing_display_style' => get_post_meta($id, '_wpc_billing_display_style', true) ?: 'toggle',

                'pros'     => $pros,
                'cons'     => $cons,
                
                // Labels - Restored Explicit List
                'prosLabel' => $l('pros_label', '_wpc_txt_pros_label'),
                'consLabel' => $l('cons_label', '_wpc_txt_cons_label'),
                'priceLabel' => $l('price_label', '_wpc_txt_price_label'),
                'ratingLabel' => $l('rating_label', '_wpc_txt_rating_label'),
                'moSuffix' => $l('mo_suffix', '_wpc_txt_mo_suffix'),
                'visitSiteLabel' => $l('visit_site', '_wpc_txt_visit_site'),
                'couponLabel' => $l('coupon_label', '_wpc_txt_coupon_label'),
                'copiedLabel' => $l('copied_label', '_wpc_txt_copied_label'),
                'show_hero_logo' => get_post_meta($id, '_wpc_show_hero_logo', true) !== '0',
                'featureHeader' => $l('feature_header', '_wpc_txt_feature_header'),
                
                'raw_features' => $feature_names,
                'details_link' => $details_link,
                'direct_link' => $direct_link,
                'button_text' => $button_text,
                'hero_button_text' => $hero_button_text,
                'permalink' => get_permalink($id),
                'description' => $description,
                
                'badge' => null, // Restored explicit null
                
                'featured_badge_text' => $get_val('badge_text', '_wpc_featured_badge_text', '_wpc_badge_text'), // Restored detailed fetch
                'featured_color' => $get_val('badge_color', '_wpc_featured_color', '_wpc_badge_color'),
                
                'coupon_code' => $get_val('coupon_code', '_wpc_coupon_code'),
                'product_details' => array(
                    'category' => $get_val('product_category', '_wpc_product_category'),
                    'brand' => $get_val('brand', '_wpc_brand'),
                    'sku' => $get_val('sku', '_wpc_sku'),
                    'gtin' => $get_val('gtin', '_wpc_gtin'),
                    'condition' => $get_val('condition_status', '_wpc_condition'),
                    'availability' => $get_val('availability', '_wpc_availability'),
                    'mfg_date' => $get_val('mfg_date', '_wpc_mfg_date'),
                    'exp_date' => $get_val('exp_date', '_wpc_exp_date'),
                    'service_type' => $get_val('service_type', '_wpc_service_type'),
                    'area_served' => $get_val('area_served', '_wpc_area_served'),
                    'duration' => $get_val('duration', '_wpc_duration'),
                ),
                'custom_fields' => get_post_meta($id, '_wpc_custom_fields', true) ?: [], 
                'button_text' => $button_text, 
                'hero_subtitle' => $get_val('hero_subtitle', '_wpc_hero_subtitle'),
                'analysis_label' => $get_val('analysis_label', '_wpc_analysis_label'),
                'dashboard_image' => $dashboard_img,
                'footer_button_text' => $get_val('footer_button_text', '_wpc_footer_button_text'),
                'table_btn_pos' => $get_val('table_btn_pos', '_wpc_table_btn_pos'),
                'popup_btn_pos' => $get_val('popup_btn_pos', '_wpc_popup_btn_pos'),
                'show_footer_popup' => ($row ? $row->show_plan_links_popup == 1 : (get_post_meta( $id, '_wpc_show_footer_popup', true ) !== '0')), // Restored logic line
                
                // Design Overrides - Restored Immediate Closure Logic
                'design_overrides' => (function() use ($id, $row) {
                    if ($row && !empty($row->design_overrides)) {
                         return $row->design_overrides;
                    }
                    $enabled = get_post_meta( $id, '_wpc_enable_design_overrides', true );
                    
                    $out = [
                        'enabled' => ($enabled === '1'),
                        // Footer settings (Independent of Design Overrides toggle)
                        'show_footer_popup' => get_post_meta( $id, '_wpc_show_footer_popup', true ) !== '0',
                        'show_footer_table' => get_post_meta( $id, '_wpc_show_footer_table', true ) !== '0',
                        'show_footer' => get_post_meta( $id, '_wpc_show_footer_button', true ) !== '0',
                        'footer_text' => get_post_meta( $id, '_wpc_footer_button_text', true ),
                    ];

                    if ($enabled === '1') {
                        $out['primary'] = get_post_meta( $id, '_wpc_primary_color', true );
                        $out['accent'] = get_post_meta( $id, '_wpc_accent_color', true );
                        $out['border'] = get_post_meta( $id, '_wpc_border_color', true );
                        $out['coupon_bg'] = get_post_meta( $id, '_wpc_color_coupon_bg', true );
                        $out['coupon_text'] = get_post_meta( $id, '_wpc_color_coupon_text', true );
                        $out['coupon_hover'] = get_post_meta( $id, '_wpc_color_coupon_hover', true );
                        $out['copied_text'] = get_post_meta( $id, '_wpc_color_copied', true );
                    }
                    
                    return $out;
                })(),
                
                'content' => apply_filters( 'the_content', get_the_content() ),
                
                // Product Variants Module Data
                'variants' => (function() use ($id, $post_type) {
                    if ($post_type !== 'comparison_item') return null;
                    if (get_option('wpc_enable_variants_module') !== '1') return null;
                    if (get_post_meta($id, '_wpc_variants_enabled', true) !== '1') return null;

                    return [
                        'enabled' => true,
                        'default_category' => get_post_meta($id, '_wpc_default_category', true),
                        'selector_style' => get_post_meta($id, '_wpc_category_selector_style', true) ?: 'default',
                        'plans_by_category' => get_post_meta($id, '_wpc_plans_by_category', true) ?: [],
                        'features_by_category' => get_post_meta($id, '_wpc_features_by_category', true) ?: [],
                        'use_cases_by_category' => get_post_meta($id, '_wpc_use_cases_by_category', true) ?: [],
                        'plan_features_by_category' => get_post_meta($id, '_wpc_plan_features_by_category', true) ?: [],
                    ];
                })()
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
 * Get Items (API Callback)
 */
function wpc_get_items() {
    return wpc_fetch_items_data();
}

/**
 * Track Outbound Click
 */
function wpc_track_click( $request ) {
    $item_id = $request->get_param( 'id' );
    
    if ( ! $item_id ) {
        return new WP_Error( 'no_id', 'Item ID is required', array( 'status' => 400 ) );
    }

    // Dual Write Tracking
    // 1. Meta
    $current_clicks = (int) get_post_meta( $item_id, '_wpc_clicks', true );
    $new_clicks = $current_clicks + 1;
    update_post_meta( $item_id, '_wpc_clicks', $new_clicks );

    // 2. Custom Table
    $post_type = get_post_type( $item_id );
    
    if ( $post_type === 'comparison_tool' ) {
        if ( class_exists('WPC_Tools_Database') && get_option('wpc_enable_tools_module') === '1' ) {
            $tools_db = new WPC_Tools_Database();
            $tools_db->update_tool( $item_id, array( 'clicks' => $new_clicks ) );
        }
    } else {
        if ( class_exists('WPC_Database') ) {
             $db = new WPC_Database();
             $db->update_item( $item_id, array( 'clicks' => $new_clicks ) );
        }
    }

    return array( 'success' => true, 'clicks' => $new_clicks );
}

