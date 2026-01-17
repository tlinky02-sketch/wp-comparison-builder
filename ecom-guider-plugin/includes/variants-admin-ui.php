<?php
/**
 * Product Variants Admin UI
 * Renders per-item variant settings when module is enabled
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Check if variants module is enabled
 */
function wpc_is_variants_module_enabled() {
    return get_option( 'wpc_enable_variants_module', false ) === '1';
}

/**
 * Render variants toggle and settings for a comparison item
 */
function wpc_render_variants_section( $post ) {
    if ( ! wpc_is_variants_module_enabled() ) {
        return;
    }

    // Get variants enablement
    $variants_enabled = get_post_meta( $post->ID, '_wpc_variants_enabled', true ) === '1';
    $all_assigned_cats = wp_get_post_terms( $post->ID, 'comparison_category', array( 'fields' => 'all' ) );
    
    // Get selected variant categories (if set)
    $variant_cat_ids = get_post_meta( $post->ID, '_wpc_variant_categories', true );
    if ( ! is_array( $variant_cat_ids ) ) $variant_cat_ids = array();
    $variant_cat_ids = array_map( 'intval', $variant_cat_ids ); // Ensure integers
    
    // Filter to only selected categories, or use all if none selected
    if ( $variants_enabled && !empty($variant_cat_ids) ) {
        $assigned_cats = array_filter( $all_assigned_cats, function($cat) use ($variant_cat_ids) {
            return in_array( (int) $cat->term_id, $variant_cat_ids, true );
        });
        $assigned_cats = array_values($assigned_cats); // Re-index
    } else {
        $assigned_cats = $all_assigned_cats;
    }
    
    $default_category = get_post_meta( $post->ID, '_wpc_default_category', true );
    $selector_style = get_post_meta( $post->ID, '_wpc_category_selector_style', true ) ?: 'default';
    
    // Get pricing plans
    $pricing_plans = get_post_meta( $post->ID, '_wpc_pricing_plans', true );
    if ( ! is_array( $pricing_plans ) ) $pricing_plans = array();
    
    // Get saved data
    $plans_by_category = get_post_meta( $post->ID, '_wpc_plans_by_category', true ) ?: array();
    $features_by_category = get_post_meta( $post->ID, '_wpc_features_by_category', true ) ?: array();
    $use_cases_by_category = get_post_meta( $post->ID, '_wpc_use_cases_by_category', true ) ?: array();
    
    // Get all available features
    $all_features = get_terms( array( 'taxonomy' => 'comparison_feature', 'hide_empty' => false ) );
    
    // Get all defined use cases
    $all_use_cases = get_post_meta( $post->ID, '_wpc_use_cases', true ) ?: array();
    ?>
    <style>
        .wpc-variant-tab-nav { display: flex; margin-bottom: 15px; border-bottom: 2px solid #e5e7eb; }
        .wpc-variant-tab-btn { padding: 8px 16px; font-weight: 600; color: #6b7280; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; }
        .wpc-variant-tab-btn.active { color: #4f46e5; border-bottom-color: #4f46e5; }
        .wpc-variant-tab-content { display: none; }
        .wpc-variant-tab-content.active { display: block; }
    </style>
    
    <div class="wpc-variants-section" style="margin-bottom: 20px; padding: 15px; background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%); border: 1px solid #667eea40; border-radius: 8px;">
        <h3 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 700; color: #4c1d95; display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 18px;">🔄</span>
            <?php _e( 'Product Variants', 'wp-comparison-builder' ); ?>
            <span style="font-size: 11px; font-weight: 400; color: #6b7280; margin-left: 8px;">(Category-specific plans, features, use cases)</span>
        </h3>
        
        <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start;">
            <!-- Enable Toggle -->
            <div style="flex: 0 0 auto;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input 
                        type="checkbox" 
                        name="wpc_variants_enabled" 
                        id="wpc_variants_enabled"
                        value="1" 
                        <?php checked( $variants_enabled, '1' ); ?>
                        onchange="wpcToggleVariantsUI(this.checked)"
                    />
                    <strong><?php _e( 'Enable variants for this item', 'wp-comparison-builder' ); ?></strong>
                </label>
                <p style="margin: 5px 0 0 26px; font-size: 12px; color: #6b7280;">
                    <?php _e( 'Different plans per category', 'wp-comparison-builder' ); ?>
                </p>
            </div>
            
            <!-- Options -->
            <div id="wpc-variants-options" style="display: <?php echo $variants_enabled === '1' ? 'flex' : 'none'; ?>; gap: 20px; flex-wrap: wrap;">
                <div>
                    <label class="wpc-label" style="margin-bottom: 5px; display: block;">
                        <?php _e( 'Default Category:', 'wp-comparison-builder' ); ?>
                    </label>
                    <select name="wpc_default_category" style="min-width: 180px;">
                        <option value=""><?php _e( '— Auto (First) —', 'wp-comparison-builder' ); ?></option>
                        <?php foreach ( $assigned_cats as $cat ) : ?>
                            <option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( $default_category, $cat->slug ); ?>>
                                <?php echo esc_html( $cat->name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="wpc-label" style="margin-bottom: 5px; display: block;">
                        <?php _e( 'Selector Style:', 'wp-comparison-builder' ); ?>
                    </label>
                    <select name="wpc_category_selector_style" style="min-width: 150px;">
                        <option value="default" <?php selected( $selector_style, 'default' ); ?>><?php _e( 'Default (Global)', 'wp-comparison-builder' ); ?></option>
                        <option value="tabs" <?php selected( $selector_style, 'tabs' ); ?>><?php _e( 'Tabs', 'wp-comparison-builder' ); ?></option>
                        <option value="dropdown" <?php selected( $selector_style, 'dropdown' ); ?>><?php _e( 'Dropdown', 'wp-comparison-builder' ); ?></option>
                        <option value="hidden" <?php selected( $selector_style, 'hidden' ); ?>><?php _e( 'Hidden', 'wp-comparison-builder' ); ?></option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Variants Content (Tabs) -->
        <div id="wpc-variants-content" style="display: <?php echo $variants_enabled === '1' ? 'block' : 'none'; ?>; margin-top: 20px;">
            <?php if ( empty( $assigned_cats ) ) : ?>
                <div style="padding: 15px; background: #fef3c7; border: 1px solid #fcd34d; border-radius: 6px; color: #92400e;">
                    <strong>⚠️ <?php _e( 'No categories assigned', 'wp-comparison-builder' ); ?></strong>
                    <p style="margin: 5px 0 0;"><?php _e( 'Please assign categories to this item first (in the Tags & Categories tab), then come back here to set up category-specific plans.', 'wp-comparison-builder' ); ?></p>
                </div>
            <?php else : ?>
                
                <div class="wpc-variant-tab-nav">
                    <div class="wpc-variant-tab-btn active" onclick="wpcSwitchVariantTab(event, 'plans')">Plans per Category</div>
                    <div class="wpc-variant-tab-btn" onclick="wpcSwitchVariantTab(event, 'features')">Features per Category</div>
                    <div class="wpc-variant-tab-btn" onclick="wpcSwitchVariantTab(event, 'usecases')">Use Cases per Category</div>
                </div>

                <!-- 1. PLANS TAB -->
                <div id="wpc-variant-tab-plans" class="wpc-variant-tab-content active">
                    <p style="margin: 0 0 10px; font-size: 13px; color: #4b5563;">
                        <?php _e( 'Assign plans to show when a category is active:', 'wp-comparison-builder' ); ?>
                    </p>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php 
                        $all_plans = get_post_meta( $post->ID, '_wpc_pricing_plans', true ) ?: array();
                        foreach ( $assigned_cats as $cat ) : 
                            $cat_plans = isset( $plans_by_category[ $cat->slug ] ) ? $plans_by_category[ $cat->slug ] : array();
                        ?>
                        <div style="padding: 12px 15px; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <span style="display: inline-block; padding: 3px 10px; background: #6366f120; color: #4f46e5; border-radius: 9999px; font-size: 12px; font-weight: 600;">
                                    <?php echo esc_html( $cat->name ); ?>
                                </span>
                            </div>
                            <?php if ( empty( $all_plans ) ) : ?>
                                <p style="margin: 0; font-size: 12px; color: #9ca3af; font-style: italic;">No plans defined.</p>
                            <?php else : ?>
                                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                                    <?php foreach ( $all_plans as $plan_idx => $plan ) : 
                                        $plan_name = isset( $plan['name'] ) ? $plan['name'] : 'Plan ' . ($plan_idx + 1);
                                        $is_checked = in_array( $plan_idx, $cat_plans );
                                    ?>
                                    <label style="display: flex; align-items: center; gap: 5px; padding: 6px 12px; background: <?php echo $is_checked ? '#dbeafe' : '#f9fafb'; ?>; border: 1px solid <?php echo $is_checked ? '#3b82f6' : '#e5e7eb'; ?>; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                        <input type="checkbox" name="wpc_plans_by_category[<?php echo esc_attr( $cat->slug ); ?>][]" value="<?php echo esc_attr( $plan_idx ); ?>" <?php checked( $is_checked ); ?> />
                                        <?php echo esc_html( $plan_name ); ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 2. FEATURES TAB -->
                <div id="wpc-variant-tab-features" class="wpc-variant-tab-content">
                    <p style="margin: 0 0 10px; font-size: 13px; color: #4b5563;">
                        <?php _e( 'Assign specific features to highlight for each category:', 'wp-comparison-builder' ); ?>
                    </p>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ( $assigned_cats as $cat ) : 
                            $cat_feats = isset( $features_by_category[ $cat->slug ] ) ? $features_by_category[ $cat->slug ] : array();
                        ?>
                        <div style="padding: 12px 15px; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <span style="display: inline-block; padding: 3px 10px; background: #ECFDF5; color: #047857; border-radius: 9999px; font-size: 12px; font-weight: 600;">
                                    <?php echo esc_html( $cat->name ); ?>
                                </span>
                            </div>
                            <?php if ( empty( $all_features ) ) : ?>
                                <p style="margin: 0; font-size: 12px; color: #9ca3af; font-style: italic;">No features found.</p>
                            <?php else : ?>
                                <div style="max-height: 150px; overflow-y: auto; border: 1px solid #f3f4f6; padding: 8px; border-radius: 4px;">
                                    <?php foreach ( $all_features as $feature ) : 
                                        $is_checked = in_array( $feature->term_id, $cat_feats );
                                    ?>
                                    <label style="display: inline-block; margin-right: 15px; margin-bottom: 5px; font-size: 13px;">
                                        <input type="checkbox" name="wpc_features_by_category[<?php echo esc_attr( $cat->slug ); ?>][]" value="<?php echo esc_attr( $feature->term_id ); ?>" <?php checked( $is_checked ); ?> />
                                        <?php echo esc_html( $feature->name ); ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 3. USE CASES TAB -->
                <div id="wpc-variant-tab-usecases" class="wpc-variant-tab-content">
                    <p style="margin: 0 0 10px; font-size: 13px; color: #4b5563;">
                        <?php _e( 'Assign specific use cases for each category:', 'wp-comparison-builder' ); ?>
                    </p>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ( $assigned_cats as $cat ) : 
                            $cat_cases = isset( $use_cases_by_category[ $cat->slug ] ) ? $use_cases_by_category[ $cat->slug ] : array();
                        ?>
                        <div style="padding: 12px 15px; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <span style="display: inline-block; padding: 3px 10px; background: #FFFBEB; color: #B45309; border-radius: 9999px; font-size: 12px; font-weight: 600;">
                                    <?php echo esc_html( $cat->name ); ?>
                                </span>
                            </div>
                            <?php if ( empty( $all_use_cases ) ) : ?>
                                <p style="margin: 0; font-size: 12px; color: #9ca3af; font-style: italic;">No use cases defined.</p>
                            <?php else : ?>
                                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                                    <?php foreach ( $all_use_cases as $uc_idx => $uc ) : 
                                        $uc_title = isset( $uc['title'] ) ? $uc['title'] : 'Use Case ' . ($uc_idx + 1);
                                        $is_checked = in_array( $uc_idx, $cat_cases );
                                    ?>
                                    <label style="display: flex; align-items: center; gap: 5px; padding: 6px 12px; background: <?php echo $is_checked ? '#fef3c7' : '#f9fafb'; ?>; border: 1px solid <?php echo $is_checked ? '#f59e0b' : '#e5e7eb'; ?>; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                        <input type="checkbox" name="wpc_use_cases_by_category[<?php echo esc_attr( $cat->slug ); ?>][]" value="<?php echo esc_attr( $uc_idx ); ?>" <?php checked( $is_checked ); ?> />
                                        <?php echo esc_html( $uc_title ); ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
    
    <script>
    function wpcToggleVariantsUI(enabled) {
        document.getElementById('wpc-variants-options').style.display = enabled ? 'flex' : 'none';
        document.getElementById('wpc-variants-content').style.display = enabled ? 'block' : 'none';
    }
    
    function wpcSwitchVariantTab(e, tabName) {
        e.preventDefault();
        // Reset tabs
        document.querySelectorAll('.wpc-variant-tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.wpc-variant-tab-content').forEach(content => content.classList.remove('active'));
        
        // Active tab
        e.target.classList.add('active');
        document.getElementById('wpc-variant-tab-' + tabName).classList.add('active');
    }
    </script>
    <?php
}

/**
 * Save variants meta
 */
function wpc_save_variants_meta( $post_id ) {
    // Variants enabled
    $variants_enabled = isset( $_POST['wpc_variants_enabled'] ) ? '1' : '0';
    update_post_meta( $post_id, '_wpc_variants_enabled', $variants_enabled );
    
    // Default category
    if ( isset( $_POST['wpc_default_category'] ) ) {
        update_post_meta( $post_id, '_wpc_default_category', sanitize_text_field( $_POST['wpc_default_category'] ) );
    }
    
    // Selector style
    if ( isset( $_POST['wpc_category_selector_style'] ) ) {
        update_post_meta( $post_id, '_wpc_category_selector_style', sanitize_text_field( $_POST['wpc_category_selector_style'] ) );
    }
    
    // Plans by category
    if ( isset( $_POST['wpc_plans_by_category'] ) && is_array( $_POST['wpc_plans_by_category'] ) ) {
        $plans_by_cat = array();
        foreach ( $_POST['wpc_plans_by_category'] as $cat_slug => $plan_indices ) {
            $plans_by_cat[ sanitize_text_field( $cat_slug ) ] = array_map( 'intval', $plan_indices );
        }
        update_post_meta( $post_id, '_wpc_plans_by_category', $plans_by_cat );
    } else {
        update_post_meta( $post_id, '_wpc_plans_by_category', array() );
    }
    
    // Features by category
    if ( isset( $_POST['wpc_features_by_category'] ) && is_array( $_POST['wpc_features_by_category'] ) ) {
        $feats_by_cat = array();
        foreach ( $_POST['wpc_features_by_category'] as $cat_slug => $feat_ids ) {
            $feats_by_cat[ sanitize_text_field( $cat_slug ) ] = array_map( 'intval', $feat_ids );
        }
        update_post_meta( $post_id, '_wpc_features_by_category', $feats_by_cat );
    } else {
        update_post_meta( $post_id, '_wpc_features_by_category', array() );
    }
    
    // Use Cases by category
    if ( isset( $_POST['wpc_use_cases_by_category'] ) && is_array( $_POST['wpc_use_cases_by_category'] ) ) {
        $uc_by_cat = array();
        foreach ( $_POST['wpc_use_cases_by_category'] as $cat_slug => $uc_indices ) {
            $uc_by_cat[ sanitize_text_field( $cat_slug ) ] = array_map( 'intval', $uc_indices );
        }
        update_post_meta( $post_id, '_wpc_use_cases_by_category', $uc_by_cat );
    } else {
        update_post_meta( $post_id, '_wpc_use_cases_by_category', array() );
    }
    
    // Plan Features by category (New)
    if ( isset( $_POST['wpc_plan_features_by_category'] ) && is_array( $_POST['wpc_plan_features_by_category'] ) ) {
        $plan_feats_by_cat = array();
        foreach ( $_POST['wpc_plan_features_by_category'] as $cat_slug => $features ) {
            if ( is_array( $features ) ) {
                $clean_features = array();
                foreach ( $features as $feat_idx => $feature ) {
                    $clean_feature = array(
                        'name' => sanitize_text_field( $feature['name'] ?? '' ),
                        'visible' => isset( $feature['visible'] ) && $feature['visible'] === '1',
                        'plans' => array()
                    );
                    
                    // Save plan checkboxes
                    if ( isset( $feature['plans'] ) && is_array( $feature['plans'] ) ) {
                        foreach ( $feature['plans'] as $plan_idx => $val ) {
                            if ( $val === '1' ) {
                                $clean_feature['plans'][ intval($plan_idx) ] = true;
                            }
                        }
                    }
                    
                    $clean_features[ intval($feat_idx) ] = $clean_feature;
                }
                $plan_feats_by_cat[ sanitize_text_field( $cat_slug ) ] = $clean_features;
            }
        }
        update_post_meta( $post_id, '_wpc_plan_features_by_category', $plan_feats_by_cat );
    } else {
        update_post_meta( $post_id, '_wpc_plan_features_by_category', array() );
    }
}
