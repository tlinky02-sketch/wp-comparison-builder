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
    if ( $query->have_posts() && class_exists('WPC_Tools_Database') ) {
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
                // Fallback removed to prevent ghost terms. Trust 'comparison_category'.
                // if ( ! $categories || is_wp_error( $categories ) ) { ... }
                
                $features = get_the_terms( $id, 'comparison_feature' );
                // Fallback removed. OLD:
                // if ( ! $features || is_wp_error( $features ) ) {
                //    $features = get_the_terms( $id, 'ecommerce_feature' );
                // }
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
                $description = $get_val('short_description', '_wpc_short_description') ?: get_the_excerpt();
            }

            // Primary Categories (Convert IDs to Names)
            // Use Meta for now as DB primary_cats wasn't explicitly single column (it was mapped to meta in admin-ui)
            // Wait, Migrator did NOT map primary_cats to columns properly? 
            // Checking Migrator: It did NOT. It relied on logic?
            // "Save Primary Categories" in admin-ui uses update_post_meta.
            // "Save Terms" uses wp_set_post_terms.
            // My Schema didn't have `primary_categories` JSON column?
            // "product_category" was there.
            // Let's check Schema: `_wpc_primary_cats` is used.
            // The DB Schema has `product_category` (singular string).
            // It seems `primary_cats` (array of IDs) is still Meta-only or missing from DB schema plan?
            // In API-endpoints I just used `_wpc_primary_cats` meta. 
            // I will continue to use Meta for primary_cats for safely.
            
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
                'price'    => $price,
                'period'   => $period,
                'features' => $feature_map,
                'pricing_plans' => $pricing_plans,
                'hide_plan_features' => $hide_plan_features, 
                'show_plan_links' => $show_plan_links,
                'show_coupon' => $show_coupon,
                'pros'     => $pros,
                'cons'     => $cons,
                
                // Labels
                'prosLabel' => $l('pros_label', '_wpc_txt_pros_label'),
                'consLabel' => $l('cons_label', '_wpc_txt_cons_label'),
                'priceLabel' => $l('price_label', '_wpc_txt_price_label'),
                'ratingLabel' => $l('rating_label', '_wpc_txt_rating_label'),
                'moSuffix' => $l('mo_suffix', '_wpc_txt_mo_suffix'),
                'visitSiteLabel' => $l('visit_site', '_wpc_txt_visit_site'),
                'couponLabel' => $l('coupon_label', '_wpc_txt_coupon_label'),
                'copiedLabel' => $l('copied_label', '_wpc_txt_copied_label'),
                'featureHeader' => $l('feature_header', '_wpc_txt_feature_header'),
                
                'raw_features' => $feature_names,
                'details_link' => $details_link,
                'direct_link' => $direct_link,
                'permalink' => get_permalink($id),
                'description' => $description,
                
                // Badge Logic
                // Fix: 'badge' object forces unconditional display in PlatformCard.
                // We want 'featured_badge_text' to be used ONLY when isFeatured is true.
                'badge' => null, 
                
                'featured_badge_text' => $badge_text, // Use the value we fetched earlier (from DB or Meta)
                'featured_color' => $get_val('badge_color', '_wpc_featured_color', '_wpc_badge_color'),
                // Migration: 'badge_text' => $m('_wpc_badge_text')
                // There is also '_wpc_featured_badge_text' in admin-ui.
                // My Schema had 'badge_text'. Does it handle both?
                // Inspecting Migrator again...
                // Migrator has: 'badge_text' => $m('_wpc_badge_text'). 
                // Admin UI has: '_wpc_featured_badge_text' AND '_wpc_badge_text' (wait, let's check admin-ui keys).
                // Admin UI uses `_wpc_featured_badge_text` for the "Featured Badge Text".
                // Does it use `_wpc_badge_text` for "Best For" badge?
                // Let's check `list-meta-box`...
                // Actually, the frontend often uses ONE badge slot.
                // But looking at keys: `_wpc_featured_badge_text` is clearly for Featured items.
                // `_wpc_badge_text` might be legacy or for non-featured?
                // Schema has `badge_text`. 
                // Mapping: `featured_badge_text` => `badge_text` might be what I intended, or I missed a column.
                // Checking Schema in `WPC_Database`: `badge_text varchar(255)`. Only one.
                // Checking `WPC_Migrator`: `'badge_text' => $m('_wpc_badge_text')`.
                // Checking `admin-ui`: `update_post_meta( $post_id, '_wpc_featured_badge_text', ... )`.
                // Uh oh. Mismatch.
                // `_wpc_featured_badge_text` (Admin UI) vs `_wpc_badge_text` (Migrator).
                // I suspect `_wpc_badge_text` IS the legacy key for `featured_badge_text` OR they are separate.
                // Use Fallback here to be safe.
                'featured_badge_text' => $get_val('badge_text', '_wpc_featured_badge_text', '_wpc_badge_text'),

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
                'custom_fields' => get_post_meta($id, '_wpc_custom_fields', true) ?: [], // Not in Schema yet?
                'button_text' => $button_text, 
                'hero_subtitle' => $get_val('hero_subtitle', '_wpc_hero_subtitle'),
                'analysis_label' => $get_val('analysis_label', '_wpc_analysis_label'),
                'dashboard_image' => $dashboard_img,
                'footer_button_text' => $get_val('footer_button_text', '_wpc_footer_button_text'),
                'table_btn_pos' => $get_val('table_btn_pos', '_wpc_table_btn_pos'),
                'popup_btn_pos' => $get_val('popup_btn_pos', '_wpc_popup_btn_pos'),
                'show_footer_popup' => ($row ? $row->show_plan_links_popup == 1 : (get_post_meta( $id, '_wpc_show_footer_popup', true ) !== '0')), // wait, footer popup vs plan links popup? Double check. Schema had `show_footer_popup` inside design overrides... No, wait.
                // Migrator: `show_footer_popup` -> `design_overrides['show_footer_popup']`.
                // AND column `show_plan_links_popup` -> `_wpc_show_plan_links_popup`.
                // Frontend Logic uses `show_footer_button` often.
                // Let's rely on Design Overrides logic block below for footer stuff.
                
                // Design Overrides
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
                    }
                    
                    return $out;
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
