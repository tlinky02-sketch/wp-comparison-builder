<?php
/**
 * Feature Table Shortcode
 * 
 * Displays a table comparing features across pricing plans.
 * Usage: [wpc_feature_table id="123"]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the shortcode
 */
add_shortcode( 'wpc_feature_table', 'wpc_feature_table_shortcode' );

function wpc_feature_table_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'id' => 0,
        'category' => '', // Specific category requested
    ), $atts, 'wpc_feature_table' );

    $post_id = intval( $atts['id'] );
    
    if ( ! $post_id || get_post_type( $post_id ) !== 'comparison_item' ) {
        return '<!-- WPC Feature Table: Invalid ID -->';
    }

    // 1. Determine Scope (Single vs Multi)
    $variants_enabled = get_post_meta( $post_id, '_wpc_variants_enabled', true ) === '1';
    $requested_cat = ! empty( $atts['category'] ) ? sanitize_text_field( $atts['category'] ) : '';
    
    // Default: Comparison Item Categories
    $target_categories = array(); 
    $render_selector = false;
    $selector_style = 'tabs';
    $active_cat = '';

    if ( $variants_enabled ) {
        if ( ! empty( $requested_cat ) ) {
            // Single Mode (Specific category requested)
            $target_categories = array( $requested_cat );
        } else {
            // Multi Mode (Show all assigned categories with tabs)
            $cat_ids = get_post_meta( $post_id, '_wpc_variant_categories', true );
            if ( ! empty( $cat_ids ) && is_array( $cat_ids ) ) {
                $terms = get_terms( array( 'taxonomy' => 'comparison_category', 'include' => $cat_ids, 'hide_empty' => false ) );
                if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                    // Sort by term order if possible, or ID
                    foreach( $terms as $term ) {
                        $target_categories[] = $term->slug;
                    }
                    
                    // Setup Selector
                    $selector_style = get_post_meta( $post_id, '_wpc_category_selector_style', true ) ?: 'tabs';
                    if ( $selector_style !== 'hidden' && count($target_categories) > 1 ) {
                        $render_selector = true;
                    }
                    
                    // Determine Active Cat (Default)
                    $default_cat = get_post_meta( $post_id, '_wpc_default_category', true );
                    $active_cat = ( ! empty( $default_cat ) && in_array( $default_cat, $target_categories ) ) ? $default_cat : $target_categories[0];
                }
            }
        }
    }
    
    // Fallback: If no variants or no categories found, run once with empty cat (Global)
    if ( empty( $target_categories ) ) {
        $target_categories = array( '' );
    } else {
        // If we found categories but active_cat isn't set (e.g. single mode), set it
        if ( empty( $active_cat ) ) $active_cat = $target_categories[0];
    }

    // 2. Prepare Global Data
    $pricing_plans_global = get_post_meta( $post_id, '_wpc_pricing_plans', true );
    if ( ! is_array( $pricing_plans_global ) ) $pricing_plans_global = array();
    
    $global_features_manual = get_post_meta( $post_id, '_wpc_plan_features', true );
    $features_by_cat_manual = get_post_meta( $post_id, '_wpc_plan_features_by_category', true );
    $features_by_cat_tax = get_post_meta( $post_id, '_wpc_features_by_category', true ); // For fallback
    $plans_by_cat_map = get_post_meta( $post_id, '_wpc_plans_by_category', true );

    // Display Options
    $options = get_post_meta( $post_id, '_wpc_feature_table_options', true ) ?: array();
    $display_mode = $options['display_mode'] ?? get_option( 'wpc_ft_display_mode', 'full_table' );
    $header_label = !empty($options['header_label']) ? esc_html($options['header_label']) : esc_html(get_option('wpc_ft_header_label', 'Key Features'));
    $header_bg    = $options['header_bg'] ?? get_option('wpc_ft_header_bg', 'hsl(var(--muted))');
    $check_color  = $options['check_color'] ?? get_option('wpc_ft_check_color', '#10b981');
    $x_color      = $options['x_color'] ?? get_option('wpc_ft_x_color', '#ef4444');
    $alt_row_bg   = $options['alt_row_bg'] ?? get_option('wpc_ft_alt_row_bg', 'hsl(var(--muted))');

    // Make sure JS is Enqueued for Tabs
    if ( $render_selector ) {
        wp_enqueue_script( 'wpc-frontend' );
    }

    // unique ID for JS
    $wrapper_id = 'wpc-ft-' . $post_id . '-' . mt_rand(1000,9999);

    ob_start();
    ?>
    <style>
        /* Hide scrollbar for tabs to prevent ugly arrows */
        .wpc-cat-tabs::-webkit-scrollbar { display: none; }
        .wpc-cat-tabs { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
    <div id="<?php echo esc_attr($wrapper_id); ?>" class="wpc-feature-table-container">
        <?php 
        // Render Selector (if applicable)
        if ( $render_selector ) {
            // Need term objects for helper
            $cat_objects = array();
            foreach ($target_categories as $slug) {
                $term = get_term_by( 'slug', $slug, 'comparison_category' );
                if ( $term ) $cat_objects[] = $term;
            }
            if ( function_exists('wpc_render_category_selector') ) {
                wpc_render_category_selector( $active_cat, $cat_objects, $selector_style, $wrapper_id );
            }
        }
        ?>

        <?php foreach ( $target_categories as $loop_cat_slug ) : 
            $is_visible = ( $loop_cat_slug === $active_cat );
            $pricing_plans = $pricing_plans_global; // Start with full list
            
            // --- FILTER PLANS ---
            if ( $variants_enabled && ! empty( $loop_cat_slug ) ) {
                if ( ! empty( $plans_by_cat_map ) && isset( $plans_by_cat_map[ $loop_cat_slug ] ) ) {
                    $allowed_indices = array_map('intval', $plans_by_cat_map[ $loop_cat_slug ]);
                    $filtered_plans = array();
                    foreach ( $pricing_plans as $idx => $plan ) {
                        if ( in_array( (int)$idx, $allowed_indices, true ) ) {
                            $filtered_plans[$idx] = $plan;
                        }
                    }
                    // Strict filtering: If cat found but no plans, empty it.
                    $pricing_plans = $filtered_plans;
                }
            }
            
            // Get Plan Names
            $plan_names = array();
            foreach ( $pricing_plans as $idx => $plan ) {
                if ( ! empty( $plan['name'] ) ) $plan_names[$idx] = $plan['name'];
            }

            // --- GET FEATURES ---
            $features = array();

            // 1. Tax Manual Features (Category Specific)
            if ( $variants_enabled && ! empty( $loop_cat_slug ) ) {
                if ( ! empty( $features_by_cat_manual ) && isset( $features_by_cat_manual[ $loop_cat_slug ] ) ) {
                    $features = $features_by_cat_manual[ $loop_cat_slug ];
                }
            }

            // 2. Global Features (Fallback) - Only if specific not found
            if ( empty( $features ) && is_array( $global_features_manual ) && ! empty( $global_features_manual ) ) {
                $features = $global_features_manual;
            }

            // 3. Taxonomy Terms Fallback
            if ( ( ! is_array( $features ) || empty( $features ) ) && $variants_enabled && ! empty( $loop_cat_slug ) ) {
                if ( ! empty( $features_by_cat_tax ) && isset( $features_by_cat_tax[ $loop_cat_slug ] ) ) {
                    $term_ids = $features_by_cat_tax[ $loop_cat_slug ];
                    $generated_features = array();
                    $plans_map = array();
                    if (!empty($pricing_plans)) {
                        foreach (array_keys($pricing_plans) as $p_idx) $plans_map[$p_idx] = '1';
                    }
                    
                    if ( is_array($term_ids) ) {
                        foreach ($term_ids as $term_id) {
                            $term = get_term( (int)$term_id, 'comparison_feature' );
                            if ( $term && ! is_wp_error( $term ) ) {
                                $generated_features[] = array(
                                    'name' => $term->name,
                                    'visible' => '1',
                                    'plans' => $plans_map
                                );
                            }
                        }
                    }
                    if ( ! empty( $generated_features ) ) $features = $generated_features;
                }
            }
            
            // Should we render empty table? 
            // If no features and no plans, probably show "No features".
            $has_content = !empty($features) || !empty($plan_names);
        ?>
            <!-- Tab Content for <?php echo esc_attr($loop_cat_slug); ?> -->
            <div 
                class="wpc-tab-content" 
                data-tab="<?php echo esc_attr( $loop_cat_slug ); ?>" 
                style="display: <?php echo $is_visible ? 'block' : 'none'; ?>;"
            >
                <?php if ( ! $has_content ) : ?>
                    <!-- Optional: Empty State -->
                     <p style="color:hsl(var(--muted-foreground)); font-style:italic;">No features defined.</p>
                <?php else: ?>
                    <div class="wpc-feature-table-wrapper" style="overflow-x: auto;">
                        <table class="wpc-feature-table" style="min-width: 100%; border-collapse: collapse; background: hsl(var(--card)); border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                            <thead>
                                <tr style="background: <?php echo esc_attr( $header_bg ); ?>;">
                                    <th style="padding: 16px 20px; text-align: left; font-weight: 600; font-size: var(--wpc-font-size-body, inherit); border-bottom: 2px solid hsl(var(--border)); min-width: 200px;">
                                        <?php echo $header_label; ?>
                                    </th>
                                    <?php if ( $display_mode === 'features_only' ) : ?>
                                        <th style="padding: 16px 20px; border-bottom: 2px solid hsl(var(--border)); width: 80px;"></th>
                                    <?php elseif ( ! empty( $plan_names ) ) : ?>
                                        <?php foreach ( $plan_names as $plan_name ) : ?>
                                            <th style="padding: 16px 20px; text-align: center; font-weight: 600; font-size: var(--wpc-font-size-body, inherit); border-bottom: 2px solid hsl(var(--border)); min-width: 140px;">
                                                <?php echo esc_html( $plan_name ); ?>
                                            </th>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $features as $f_idx => $feature ) : 
                                    if ( isset($feature['visible']) && $feature['visible'] === '0' ) continue;
                                    $row_bg = ( $f_idx % 2 === 1 ) ? $alt_row_bg : 'hsl(var(--card))';
                                ?>
                                    <tr style="background: <?php echo esc_attr( $row_bg ); ?>;">
                                        <td style="padding: 14px 20px; font-size: var(--wpc-font-size-body, inherit); color: hsl(var(--foreground)); border-bottom: 1px solid hsl(var(--border));">
                                            <?php echo esc_html( $feature['name'] ); ?>
                                        </td>
                                        <?php if ( $display_mode === 'features_only' ) : ?>
                                            <td style="padding: 14px 20px; text-align: center; border-bottom: 1px solid hsl(var(--border));">
                                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="<?php echo esc_attr( $check_color ); ?>" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block;"><circle cx="12" cy="12" r="10"></circle><path d="M9 12l2 2 4-4"></path></svg>
                                            </td>
                                        <?php elseif ( empty( $plan_names ) ) : ?>
                                             <td style="padding: 14px 20px; text-align: center; border-bottom: 1px solid hsl(var(--border)); font-style: italic; color: hsl(var(--muted-foreground));">No plans match.</td>
                                        <?php else : ?>
                                            <?php foreach ( $plan_names as $plan_idx => $plan_name ) : 
                                                $is_available = ! empty( $feature['plans'][$plan_idx] );
                                            ?>
                                                <td style="padding: 14px 20px; text-align: center; border-bottom: 1px solid hsl(var(--border));">
                                                    <?php if ( $is_available ) : ?>
                                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="<?php echo esc_attr( $check_color ); ?>" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block;"><circle cx="12" cy="12" r="10"></circle><path d="M9 12l2 2 4-4"></path></svg>
                                                    <?php else : ?>
                                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="<?php echo esc_attr( $x_color ); ?>" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block;"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
