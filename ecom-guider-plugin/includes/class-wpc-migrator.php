<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migration Logic: Post Meta -> Custom Table
 */
class WPC_Migrator {

    public function run_migration() {
        global $wpdb;
        $db = new WPC_Database();
        
        // Ensure table exists before migrating
        $db->create_table();
        
        // 1. Get all comparison items
        $args = array(
            'post_type'      => 'comparison_item',
            'posts_per_page' => -1,
            'post_status'    => 'any', // Migrate drafts too
        );
        $query = new WP_Query( $args );
        
        $count = 0;
        $errors = [];

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id = get_the_ID();
                
                try {
                    $data = $this->map_post_to_row( $post_id );
                    $result = $db->update_item( $post_id, $data );
                    
                    if ( $result !== false ) {
                        $count++;
                    } else {
                        $errors[] = "Failed to insert Post ID $post_id";
                    }
                } catch ( Exception $e ) {
                    $errors[] = "Error processing Post ID $post_id: " . $e->getMessage();
                }
            }
            wp_reset_postdata();
        }

        return [
            'success' => true,
            'count' => $count,
            'errors' => $errors
        ];
    }

    /**
     * Map Post Meta to Table Columns
     * Public so it can be used by admin-ui for dual-write
     */
    public function map_post_to_row( $post_id ) {
        // Helper to get meta cleanly
        $m = function($key) use ($post_id) {
            return get_post_meta( $post_id, $key, true );
        };

        // Core Data
        $row = [
            'public_name'       => $m('_wpc_public_name') ?: get_the_title($post_id),
            'short_description' => $m('_wpc_short_description'),
            'price'             => $m('_wpc_price'),
            'period'            => $m('_wpc_period'),
            'rating'            => $m('_wpc_rating'),
            'clicks'            => (int) $m('_wpc_clicks'),
            
            // Links
            'details_link'      => $m('_wpc_details_link'),
            'direct_link'       => $m('_wpc_direct_link'),
            'button_text'       => $m('_wpc_button_text'),
            'footer_button_text'=> $m('_wpc_footer_button_text'),
            
            // Visuals
            'logo_url'          => $m('_wpc_external_logo_url'),
            'dashboard_image'   => $m('_wpc_dashboard_image'),
            'hero_subtitle'     => $m('_wpc_hero_subtitle'),
            'analysis_label'    => $m('_wpc_analysis_label'),
            'badge_text'        => $m('_wpc_badge_text'),
            'badge_color'       => $m('_wpc_badge_color'),
            
            // Product Details
            'condition_status'  => $m('_wpc_condition'),
            'availability'      => $m('_wpc_availability'),
            'mfg_date'          => $m('_wpc_mfg_date') ?: null, // Date
            'exp_date'          => $m('_wpc_exp_date') ?: null, // Date
            'service_type'      => $m('_wpc_service_type'),
            'area_served'       => $m('_wpc_area_served'),
            'duration'          => $m('_wpc_duration'),
            'brand'             => $m('_wpc_brand'),
            'sku'               => $m('_wpc_sku'),
            'gtin'              => $m('_wpc_gtin'),
            'product_category'  => $m('_wpc_product_category'),
            
            // Pricing Settings
            'coupon_code'       => $m('_wpc_coupon_code'),
            'show_coupon'       => $m('_wpc_show_coupon') === '1' ? 1 : 0,
            'hide_plan_features'=> $m('_wpc_hide_plan_features') === '1' ? 1 : 0,
            'show_plan_links'   => $m('_wpc_show_plan_links') === '1' ? 1 : 0,
            'show_plan_links_popup' => $m('_wpc_show_plan_links_popup') === '1' ? 1 : 0,
            'show_plan_buttons' => $m('_wpc_show_plan_buttons') !== '0' ? 1 : 0, // Default 1
            'table_btn_pos'     => $m('_wpc_table_btn_pos'),
            'popup_btn_pos'     => $m('_wpc_popup_btn_pos'),
        ];

        // JSON: Design Overrides
        $row['design_overrides'] = [
            'enabled' => $m('_wpc_enable_design_overrides') === '1',
            'primary' => $m('_wpc_primary_color'),
            'accent'  => $m('_wpc_accent_color'),
            'border'  => $m('_wpc_border_color'),
            'coupon_bg' => $m('_wpc_color_coupon_bg'),
            'coupon_text' => $m('_wpc_color_coupon_text'),
            'show_footer' => $m('_wpc_show_footer_button') !== '0',
            'show_footer_popup' => $m('_wpc_show_footer_popup') !== '0',
            'show_footer_table' => $m('_wpc_show_footer_table') !== '0',
            'footer_text' => $m('_wpc_footer_button_text')
        ];

        // JSON: Pros/Cons Colors
        $row['pros_cons_colors'] = [
            'enabled' => $m('_wpc_enable_pros_cons_colors') === '1',
            'pros_bg' => $m('_wpc_color_pros_bg'),
            'pros_text' => $m('_wpc_color_pros_text'),
            'cons_bg' => $m('_wpc_color_cons_bg'),
            'cons_text' => $m('_wpc_color_cons_text'),
        ];

        // JSON: Feature Table Options
        $row['feature_table_options'] = [
            'display_mode' => $m('_wpc_feature_display_mode'),
            'header_label' => $m('_wpc_feature_header_label'),
            'header_bg'    => $m('_wpc_feature_header_bg'),
            'check_color'  => $m('_wpc_feature_check_color'),
            'x_color'      => $m('_wpc_feature_x_color'),
            'alt_row_bg'   => $m('_wpc_feature_alt_row_bg'),
        ];

        // JSON: Text Labels
        $row['text_labels'] = [
            'pros_label'    => $m('_wpc_txt_pros_label'),
            'cons_label'    => $m('_wpc_txt_cons_label'),
            'price_label'   => $m('_wpc_txt_price_label'),
            'rating_label'  => $m('_wpc_txt_rating_label'),
            'mo_suffix'     => $m('_wpc_txt_mo_suffix'),
            'visit_site'    => $m('_wpc_txt_visit_site'),
            'coupon_label'  => $m('_wpc_txt_coupon_label'),
            'copied_label'  => $m('_wpc_txt_copied_label'),
            'feature_header'=> $m('_wpc_txt_feature_header'),
        ];

        // Complex Data
        $row['pros'] = explode("\n", $m('_wpc_pros')); 
        $row['cons'] = explode("\n", $m('_wpc_cons'));
        $row['competitors'] = $m('_wpc_competitors'); // Array
        $row['use_cases'] = $m('_wpc_use_cases'); // Array
        $row['selected_tools'] = $m('_wpc_selected_tools'); // Array
        $row['pricing_plans'] = $m('_wpc_pricing_plans'); // Array of Arrays
        $row['plan_features'] = $m('_wpc_plan_features'); // 2D Array

        // Clean up empty arrays/lines
        if(is_array($row['pros'])) $row['pros'] = array_values(array_filter(array_map('trim', $row['pros'])));
        if(is_array($row['cons'])) $row['cons'] = array_values(array_filter(array_map('trim', $row['cons'])));

        return $row;
    }

    /**
     * Migrate Tools to wp_wpc_tools
     */
    public function migrate_tools() {
        // Only run if module is enabled
        if ( get_option( 'wpc_enable_tools_module' ) !== '1' ) {
            return ['success' => false, 'error' => 'Tools Module is disabled'];
        }

        if ( ! class_exists('WPC_Tools_Database') ) {
            return ['success' => false, 'error' => 'Tools DB Class missing'];
        }

        $db = new WPC_Tools_Database();
        $db->create_table(); // Ensure table exists

        $args = array(
            'post_type'      => 'comparison_tool',
            'posts_per_page' => -1,
            'post_status'    => 'any',
        );
        $query = new WP_Query( $args );
        
        $count = 0;
        $errors = [];

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id = get_the_ID();
                
                try {
                    // Map Meta to Table
                    $data = [
                        'badge_text'        => get_post_meta($post_id, '_tool_badge', true),
                        'link'              => get_post_meta($post_id, '_tool_link', true),
                        'button_text'       => get_post_meta($post_id, '_tool_button_text', true),
                        'short_description' => get_post_meta($post_id, '_wpc_tool_short_description', true),
                        'rating'            => get_post_meta($post_id, '_wpc_tool_rating', true),
                        
                        // JSON Fields
                        // Handle comma-separated string vs array mismatch for features
                        'features'          => [], 
                        'pricing_plans'     => get_post_meta($post_id, '_wpc_tool_pricing_plans', true) ?: []
                    ];

                    // Clean Features (textarea is newline separated)
                    $features_raw = get_post_meta($post_id, '_wpc_tool_features', true);
                    if ( $features_raw ) {
                        $data['features'] = array_filter(array_map('trim', explode("\n", $features_raw)));
                    }

                    $db->update_tool( $post_id, $data );
                    $count++;

                } catch ( Exception $e ) {
                    $errors[] = "Error migrating tool $post_id: " . $e->getMessage();
                }
            }
            wp_reset_postdata();
        }

        return [
            'success' => true,
            'count' => $count,
            'errors' => $errors
        ];
    }
}
