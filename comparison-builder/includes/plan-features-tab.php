<?php
/**
 * Plan Features Tab - Category-Aware Version
 * This file contains the enhanced Plan Features UI with category support
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render the Plan Features tab content
 * 
 * @param WP_Post $post The current post object
 */
function wpc_render_plan_features_tab( $post ) {
    // Get pricing plans
    $pricing_plans = get_post_meta( $post->ID, '_wpc_pricing_plans', true );
    if ( ! is_array( $pricing_plans ) ) $pricing_plans = array();
    
    // Get all plan names (for non-variant mode)
    $all_plan_names = array();
    foreach ( $pricing_plans as $idx => $plan ) {
        if ( ! empty( $plan['name'] ) ) {
            $all_plan_names[$idx] = $plan['name'];
        }
    }
    
    // Get saved features (legacy format)
    $plan_features = get_post_meta( $post->ID, '_wpc_plan_features', true );
    if ( ! is_array( $plan_features ) ) $plan_features = array();
    
    // Get category-specific features (new format)
    $plan_features_by_category = get_post_meta( $post->ID, '_wpc_plan_features_by_category', true );
    if ( ! is_array( $plan_features_by_category ) ) $plan_features_by_category = array();
    
    // Get features format
    $features_format = get_post_meta( $post->ID, '_wpc_features_format', true ) ?: 'boolean';
    
    // Get display options
    $feature_table_options = get_post_meta( $post->ID, '_wpc_feature_table_options', true );
    if ( ! is_array( $feature_table_options ) ) $feature_table_options = array();
    
    // Check if variants are enabled
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
    
    $plans_by_category = get_post_meta( $post->ID, '_wpc_plans_by_category', true ) ?: array();
    
    $has_variants = $variants_enabled && !empty($assigned_cats);
    ?>
    
    <!-- Shortcode Display -->
    <div style="background:#f0f9ff; border:1px solid #bae6fd; padding:15px; border-radius:5px; margin-bottom:20px;">
        <h3 style="margin-top:0; color: #0284c7; font-size:14px;">Feature Table Shortcode</h3>
        
        <?php if ( $has_variants ) : ?>
            <p style="margin-bottom: 10px; font-size:13px;">Use these shortcodes to display category-specific feature tables:</p>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <!-- All Features (No Category) -->
                <div style="display:flex; align-items:center; gap:10px;">
                    <code style="flex:1; background:#fff; padding:8px 12px; border:1px solid #dde1e5; border-radius:4px; font-size:13px; color:#c02b5c;">
                        [wpc_feature_table id="<?php echo $post->ID; ?>"]
                    </code>
                    <button type="button" class="button" onclick="wpcCopyFeatureShortcode('<?php echo $post->ID; ?>', '', this)">Copy</button>
                    <span style="font-size: 11px; color: #64748b;">(All features)</span>
                </div>
                
                <!-- Category-Specific -->
                <?php foreach ( $assigned_cats as $cat ) : ?>
                <div style="display:flex; align-items:center; gap:10px;">
                    <code style="flex:1; background:#fff; padding:8px 12px; border:1px solid #dde1e5; border-radius:4px; font-size:13px; color:#c02b5c;">
                        [wpc_feature_table id="<?php echo $post->ID; ?>" category="<?php echo esc_attr($cat->slug); ?>"]
                    </code>
                    <button type="button" class="button" onclick="wpcCopyFeatureShortcode('<?php echo $post->ID; ?>', '<?php echo esc_js($cat->slug); ?>', this)">Copy</button>
                    <span style="padding: 2px 8px; background: #e0e7ff; color: #4f46e5; border-radius: 9999px; font-size: 11px; font-weight: 600;"><?php echo esc_html($cat->name); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p style="margin-bottom: 10px; font-size:13px;">Use this shortcode to display a feature comparison table:</p>
            <div style="display:flex; align-items:center; gap:10px;">
                <code style="background:#fff; padding:8px 12px; border:1px solid #dde1e5; border-radius:4px; font-size:13px; color:#c02b5c;">
                    [wpc_feature_table id="<?php echo $post->ID; ?>"]
                </code>
                <button type="button" class="button" onclick="wpcCopyFeatureShortcode('<?php echo $post->ID; ?>', '', this)">Copy</button>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ( empty( $all_plan_names ) ) : ?>
        <div style="background: #fef3c7; border: 1px solid #fcd34d; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <strong>⚠️ No Pricing Plans Found</strong>
            <p style="margin: 5px 0 0;">Please add pricing plans in the "Pricing Plans" tab first. The plan names will become columns in the feature table.</p>
        </div>
    <?php else : ?>
    <div class="wpc-features-editor-wrapper wpc-features-format-<?php echo esc_attr( $features_format ); ?>">
    
    <!-- Display Options -->
    <div class="wpc-row" style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e5e7eb;">
        <div class="wpc-col">
            <h3 class="wpc-section-title"><?php _e( 'Display Options', 'wp-comparison-builder' ); ?></h3>
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <label>
                    <input type="radio" name="wpc_feature_table_options[display_mode]" value="full_table" <?php checked( ( $feature_table_options['display_mode'] ?? 'full_table' ), 'full_table' ); ?> />
                    <?php _e( 'Full Table (Plans + Check/X)', 'wp-comparison-builder' ); ?>
                </label>
                <label>
                    <input type="radio" name="wpc_feature_table_options[display_mode]" value="features_only" <?php checked( ( $feature_table_options['display_mode'] ?? '' ), 'features_only' ); ?> />
                    <?php _e( 'Features Only (No Plans)', 'wp-comparison-builder' ); ?>
                </label>
            </div>
        </div>
        <div class="wpc-col">
            <label class="wpc-label"><?php _e( 'Header Label', 'wp-comparison-builder' ); ?></label>
            <input type="text" name="wpc_feature_table_options[header_label]" value="<?php echo esc_attr( $feature_table_options['header_label'] ?? '' ); ?>" class="wpc-input" placeholder="Key Features" />
        </div>
    </div>
    
    <!-- Color Overrides -->
    <div class="wpc-row" style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e5e7eb;">
        <div class="wpc-col" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
            <div>
                <label class="wpc-label"><?php _e( 'Header BG', 'wp-comparison-builder' ); ?></label>
                <input type="color" name="wpc_feature_table_options[header_bg]" value="<?php echo esc_attr( $feature_table_options['header_bg'] ?? '#f3f4f6' ); ?>" style="width: 50px; height: 35px; padding: 0; border: 1px solid #ddd; cursor: pointer;" />
            </div>
            <div>
                <label class="wpc-label"><?php _e( 'Check Color', 'wp-comparison-builder' ); ?></label>
                <input type="color" name="wpc_feature_table_options[check_color]" value="<?php echo esc_attr( $feature_table_options['check_color'] ?? '#10b981' ); ?>" style="width: 50px; height: 35px; padding: 0; border: 1px solid #ddd; cursor: pointer;" />
            </div>
            <div>
                <label class="wpc-label"><?php _e( 'X Color', 'wp-comparison-builder' ); ?></label>
                <input type="color" name="wpc_feature_table_options[x_color]" value="<?php echo esc_attr( $feature_table_options['x_color'] ?? '#ef4444' ); ?>" style="width: 50px; height: 35px; padding: 0; border: 1px solid #ddd; cursor: pointer;" />
            </div>
            <div>
                <label class="wpc-label"><?php _e( 'Alt Row', 'wp-comparison-builder' ); ?></label>
                <input type="color" name="wpc_feature_table_options[alt_row_bg]" value="<?php echo esc_attr( $feature_table_options['alt_row_bg'] ?? '#f9fafb' ); ?>" style="width: 50px; height: 35px; padding: 0; border: 1px solid #ddd; cursor: pointer;" />
            </div>
        </div>
    </div>
    
    <?php if ( $has_variants ) : ?>
        <!-- CATEGORY-AWARE MODE -->
        <div id="wpc-cat-feature-editor">
            <!-- Category Selector -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                    <?php foreach ( $assigned_cats as $cat_idx => $cat ) : ?>
                        <button 
                            type="button" 
                            class="wpc-cat-tab <?php echo $cat_idx === 0 ? 'active' : ''; ?>" 
                            data-category="<?php echo esc_attr($cat->slug); ?>"
                            onclick="wpcSwitchFeatureCategory('<?php echo esc_js($cat->slug); ?>')"
                            style="padding: 8px 16px; cursor: pointer; border: 1px solid #e5e7eb; background: <?php echo $cat_idx === 0 ? '#6366f1' : '#f9fafb'; ?>; color: <?php echo $cat_idx === 0 ? '#fff' : '#6b7280'; ?>; border-radius: 6px; font-weight: 600; font-size: 13px; transition: all 0.2s;"
                        >
                            <?php echo esc_html($cat->name); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <span style="font-size: 12px; font-weight: 600; color: #475569;">Format:</span>
                    <div class="wpc-features-format-toggle-group" style="display: inline-flex; border: 1px solid #cbd5e1; border-radius: 8px; padding: 2px; background: #f8fafc; vertical-align: middle; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); margin-right: 5px;">
                        <button type="button" class="wpc-features-format-btn <?php echo $features_format === 'boolean' ? 'active' : ''; ?>" data-value="boolean" style="padding: 6px 14px; font-size: 12px; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; transition: all 0.2s ease; background: <?php echo $features_format === 'boolean' ? '#4f46e5' : 'transparent'; ?>; color: <?php echo $features_format === 'boolean' ? '#fff' : '#64748b'; ?>; box-shadow: <?php echo $features_format === 'boolean' ? '0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06)' : 'none'; ?>;">
                            Ticks & Crosses
                        </button>
                        <button type="button" class="wpc-features-format-btn <?php echo $features_format === 'text' ? 'active' : ''; ?>" data-value="text" style="padding: 6px 14px; font-size: 12px; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; transition: all 0.2s ease; background: <?php echo $features_format === 'text' ? '#4f46e5' : 'transparent'; ?>; color: <?php echo $features_format === 'text' ? '#fff' : '#64748b'; ?>; box-shadow: <?php echo $features_format === 'text' ? '0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06)' : 'none'; ?>;">
                            Custom Texts
                        </button>
                        <input type="hidden" class="wpc-features-format-input" name="wpc_features_format" value="<?php echo esc_attr( $features_format ); ?>" />
                    </div>
                    <?php if ( class_exists( 'WPC_AI_Handler' ) && ! empty( WPC_AI_Handler::get_profiles() ) ) : ?>
                    <button type="button" class="button button-small button-primary wpc-ai-generate-btn" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); border: none; color: white; display: inline-flex; align-items: center; gap: 4px;" onclick="wpcAIGenerateFeatures(wpcCurrentCategory)">✨ AI Generate Features</button>
                    <?php endif; ?>
                    <button type="button" class="button button-small" onclick="wpcToggleBulkPaste()">📋 Bulk Paste</button>
                    <button type="button" class="button button-small" onclick="wpcAddCatFeatureRow()">+ Add Feature</button>
                </div>
            </div>
            
            <!-- Bulk Paste Area -->
            <div id="wpc-bulk-paste-area" style="display: none; margin-bottom: 15px; padding: 15px; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 8px;">
                <label class="wpc-label" style="margin-bottom: 8px;">📋 <?php _e( 'Paste Features (one per line)', 'wp-comparison-builder' ); ?></label>
                <textarea id="wpc-bulk-features" rows="6" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;" placeholder="Feature 1&#10;Feature 2&#10;Feature 3&#10;..."></textarea>
                <div style="margin-top: 10px; display: flex; gap: 10px;">
                    <button type="button" class="button button-primary" onclick="wpcAddBulkCatFeatures()">Add All Features</button>
                    <button type="button" class="button" onclick="wpcToggleBulkPaste()">Cancel</button>
                </div>
                <p class="description" style="margin-top: 8px; font-size: 11px; color: #666;">
                    <?php _e( 'Features will be added to the currently selected category.', 'wp-comparison-builder' ); ?>
                </p>
            </div>
            
            <!-- Feature Tables (One per Category) -->
            <?php foreach ( $assigned_cats as $cat ) : 
                $cat_plans = isset( $plans_by_category[$cat->slug] ) ? $plans_by_category[$cat->slug] : array();
                $cat_plan_names = array();
                foreach ( $cat_plans as $plan_idx ) {
                    if ( isset( $pricing_plans[$plan_idx]['name'] ) ) {
                        $cat_plan_names[$plan_idx] = $pricing_plans[$plan_idx]['name'];
                    }
                }
                
                $cat_features = isset( $plan_features_by_category[$cat->slug] ) ? $plan_features_by_category[$cat->slug] : array();
            ?>
            <div class="wpc-cat-feature-table" data-category="<?php echo esc_attr($cat->slug); ?>" style="display: <?php echo $cat === reset($assigned_cats) ? 'block' : 'none'; ?>;">
                <?php if ( empty( $cat_plan_names ) ) : ?>
                    <div style="padding: 20px; background: #fef3c7; border: 1px solid #fcd34d; border-radius: 6px; text-align: center;">
                        <strong>⚠️ No plans assigned to "<?php echo esc_html($cat->name); ?>"</strong>
                        <p style="margin: 5px 0 0; font-size: 13px;">Please assign plans to this category in the "Pricing Plans" tab → "Product Variants" section.</p>
                    </div>
                <?php else : ?>
                    <div style="overflow-x: auto;">
                        <table class="wpc-features-table-cat" style="width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #e2e8f0;">
                            <thead>
                                <tr style="background: #f3f4f6;">
                                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #e2e8f0; width: 40px;">
                                        <span style="cursor: move;" title="Drag to reorder">☰</span>
                                    </th>
                                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #e2e8f0; min-width: 200px;"><?php _e( 'Feature Name', 'wp-comparison-builder' ); ?></th>
                                    <?php foreach ( $cat_plan_names as $plan_idx => $plan_name ) : ?>
                                        <th style="padding: 10px; text-align: center; border-bottom: 2px solid #e2e8f0; min-width: 100px;">
                                            <span><?php echo esc_html( $plan_name ); ?></span><br>
                                            <input type="checkbox" class="wpc-select-all-plan" data-plan-idx="<?php echo $plan_idx; ?>" data-category="<?php echo esc_attr($cat->slug); ?>" title="Select All" style="margin-top: 5px; width: 18px; height: 18px;" onclick="wpcSelectAllPlanFeatures(this, '<?php echo esc_attr($cat->slug); ?>', <?php echo $plan_idx; ?>)" />
                                        </th>
                                    <?php endforeach; ?>
                                    <th style="padding: 10px; text-align: center; border-bottom: 2px solid #e2e8f0; width: 80px;">Visible<br><input type="checkbox" class="wpc-select-all-visible" data-category="<?php echo esc_attr($cat->slug); ?>" title="Select All" style="margin-top: 5px; width: 18px; height: 18px;" onclick="wpcSelectAllVisible(this, '<?php echo esc_attr($cat->slug); ?>')" /></th>
                                    <th style="padding: 10px; width: 60px; border-bottom: 2px solid #e2e8f0;"></th>
                                </tr>
                            </thead>
                            <tbody class="wpc-features-tbody-sortable">
                                <?php if ( ! empty( $cat_features ) ) : ?>
                                    <?php foreach ( $cat_features as $f_idx => $feature ) : ?>
                                        <tr style="border-bottom: 1px solid #f0f0f0; cursor: move;" data-feature-index="<?php echo $f_idx; ?>">
                                            <td style="padding: 8px; text-align: center;">
                                                <span style="cursor: move; color: #9ca3af;">☰</span>
                                            </td>
                                            <td style="padding: 8px;">
                                                <input type="text" name="wpc_plan_features_by_category[<?php echo esc_attr($cat->slug); ?>][<?php echo $f_idx; ?>][name]" value="<?php echo esc_attr( $feature['name'] ?? '' ); ?>" placeholder="Feature name" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;" />
                                            </td>
                                            <?php foreach ( $cat_plan_names as $plan_idx => $plan_name ) : ?>
                                                <td style="padding: 8px; text-align: center; vertical-align: middle;">
                                                    <?php 
                                                    $val = $feature['plans'][$plan_idx] ?? '';
                                                    $is_checked = ($val === '1' || $val === true || $val === 'true' || (!empty($val) && $val !== '0' && $val !== 'false'));
                                                    $text_val = ($val !== '1' && $val !== '0' && $val !== true && $val !== false && $val !== 'true' && $val !== 'false') ? $val : '';
                                                    ?>
                                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                                                        <input type="checkbox" class="wpc-feature-checkbox" <?php checked($is_checked); ?> style="width: 18px; height: 18px; margin: 0;" />
                                                        <input type="text" class="wpc-feature-text" value="<?php echo esc_attr($text_val); ?>" placeholder="Text..." style="width: 80px; padding: 2px 4px; font-size: 11px; border: 1px solid #ddd; border-radius: 4px; text-align: center; margin: 0;" />
                                                        <input type="hidden" class="wpc-feature-submit" name="wpc_plan_features_by_category[<?php echo esc_attr($cat->slug); ?>][<?php echo $f_idx; ?>][plans][<?php echo $plan_idx; ?>]" value="<?php echo esc_attr($val); ?>" />
                                                    </div>
                                                </td>
                                            <?php endforeach; ?>
                                            <td style="padding: 8px; text-align: center;">
                                                <input type="hidden" name="wpc_plan_features_by_category[<?php echo esc_attr($cat->slug); ?>][<?php echo $f_idx; ?>][visible]" value="0">
                                                <input type="checkbox" name="wpc_plan_features_by_category[<?php echo esc_attr($cat->slug); ?>][<?php echo $f_idx; ?>][visible]" value="1" <?php checked( $feature['visible'] ?? true ); ?> style="width: 18px; height: 18px;" title="Show in shortcode output" />
                                            </td>
                                            <td style="padding: 8px; text-align: center;">
                                                <button type="button" class="button button-small" onclick="this.closest('tr').remove()" title="Remove">&times;</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
    <?php else : ?>
        <!-- LEGACY MODE (No Variants) -->
        <h3 class="wpc-section-title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <?php _e( 'Features List', 'wp-comparison-builder' ); ?>
            <div style="display: flex; gap: 8px; align-items: center;">
                <span style="font-size: 12px; font-weight: 600; color: #475569;">Format:</span>
                <div class="wpc-features-format-toggle-group" style="display: inline-flex; border: 1px solid #cbd5e1; border-radius: 8px; padding: 2px; background: #f8fafc; vertical-align: middle; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); margin-right: 5px;">
                    <button type="button" class="wpc-features-format-btn <?php echo $features_format === 'boolean' ? 'active' : ''; ?>" data-value="boolean" style="padding: 6px 14px; font-size: 12px; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; transition: all 0.2s ease; background: <?php echo $features_format === 'boolean' ? '#4f46e5' : 'transparent'; ?>; color: <?php echo $features_format === 'boolean' ? '#fff' : '#64748b'; ?>; box-shadow: <?php echo $features_format === 'boolean' ? '0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06)' : 'none'; ?>;">
                        Ticks & Crosses
                    </button>
                    <button type="button" class="wpc-features-format-btn <?php echo $features_format === 'text' ? 'active' : ''; ?>" data-value="text" style="padding: 6px 14px; font-size: 12px; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; transition: all 0.2s ease; background: <?php echo $features_format === 'text' ? '#4f46e5' : 'transparent'; ?>; color: <?php echo $features_format === 'text' ? '#fff' : '#64748b'; ?>; box-shadow: <?php echo $features_format === 'text' ? '0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06)' : 'none'; ?>;">
                        Custom Texts
                    </button>
                    <input type="hidden" class="wpc-features-format-input" name="wpc_features_format" value="<?php echo esc_attr( $features_format ); ?>" />
                </div>
                <?php if ( class_exists( 'WPC_AI_Handler' ) && ! empty( WPC_AI_Handler::get_profiles() ) ) : ?>
                <button type="button" class="button button-small button-primary wpc-ai-generate-btn" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); border: none; color: white; display: inline-flex; align-items: center; gap: 4px;" onclick="wpcAIGenerateFeatures('')">✨ AI Generate Features</button>
                <?php endif; ?>
                <button type="button" class="button button-small" onclick="wpcToggleBulkPaste()">📋 Bulk Paste</button>
                <button type="button" class="button button-small" onclick="wpcAddFeatureRow()">+ Add Feature</button>
            </div>
        </h3>
        
        <!-- Bulk Paste Area -->
        <div id="wpc-bulk-paste-area" style="display: none; margin-bottom: 15px; padding: 15px; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 8px;">
            <label class="wpc-label" style="margin-bottom: 8px;">📋 <?php _e( 'Paste Features (one per line)', 'wp-comparison-builder' ); ?></label>
            <textarea id="wpc-bulk-features" rows="6" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;" placeholder="Feature 1&#10;Feature 2&#10;Feature 3&#10;..."></textarea>
            <div style="margin-top: 10px; display: flex; gap: 10px;">
                <button type="button" class="button button-primary" onclick="wpcAddBulkFeatures()">Add All Features</button>
                <button type="button" class="button" onclick="wpcToggleBulkPaste()">Cancel</button>
            </div>
        </div>
        
        <!-- Features Table -->
        <div style="overflow-x: auto;">
            <table class="wpc-features-table" style="width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #e2e8f0;">
                <thead>
                    <tr style="background: #f3f4f6;">
                        <th style="padding: 10px; text-align: left; border-bottom: 2px solid #e2e8f0; width: 40px;">
                            <span style="cursor: move;" title="Drag to reorder">☰</span>
                        </th>
                        <th style="padding: 10px; text-align: left; border-bottom: 2px solid #e2e8f0; min-width: 200px;"><?php _e( 'Feature Name', 'wp-comparison-builder' ); ?></th>
                        <?php foreach ( $all_plan_names as $plan_idx => $plan_name ) : ?>
                            <th style="padding: 10px; text-align: center; border-bottom: 2px solid #e2e8f0; min-width: 100px;">
                                <span><?php echo esc_html( $plan_name ); ?></span><br>
                                <input type="checkbox" class="wpc-select-all-plan-legacy" data-plan-idx="<?php echo $plan_idx; ?>" title="Select All" style="margin-top: 5px; width: 18px; height: 18px;" onclick="wpcSelectAllPlanFeaturesLegacy(this, <?php echo $plan_idx; ?>)" />
                            </th>
                        <?php endforeach; ?>
                        <th style="padding: 10px; text-align: center; border-bottom: 2px solid #e2e8f0; width: 80px;">Visible<br><input type="checkbox" class="wpc-select-all-visible-legacy" title="Select All" style="margin-top: 5px; width: 18px; height: 18px;" onclick="wpcSelectAllVisibleLegacy(this)" /></th>
                        <th style="padding: 10px; width: 60px; border-bottom: 2px solid #e2e8f0;"></th>
                    </tr>
                </thead>
                <tbody class="wpc-features-tbody-sortable">
                    <?php if ( ! empty( $plan_features ) ) : ?>
                        <?php foreach ( $plan_features as $f_idx => $feature ) : ?>
                            <tr style="border-bottom: 1px solid #f0f0f0; cursor: move;" data-feature-index="<?php echo $f_idx; ?>">
                                <td style="padding: 8px; text-align: center;">
                                    <span style="cursor: move; color: #9ca3af;">☰</span>
                                </td>
                                <td style="padding: 8px;">
                                    <input type="text" name="wpc_plan_features[<?php echo $f_idx; ?>][name]" value="<?php echo esc_attr( $feature['name'] ?? '' ); ?>" placeholder="Feature name" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;" />
                                </td>
                                <?php foreach ( $all_plan_names as $plan_idx => $plan_name ) : ?>
                                    <td style="padding: 8px; text-align: center; vertical-align: middle;">
                                        <?php 
                                        $val = $feature['plans'][$plan_idx] ?? '';
                                        $is_checked = ($val === '1' || $val === true || $val === 'true' || (!empty($val) && $val !== '0' && $val !== 'false'));
                                        $text_val = ($val !== '1' && $val !== '0' && $val !== true && $val !== false && $val !== 'true' && $val !== 'false') ? $val : '';
                                        ?>
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                                            <input type="checkbox" class="wpc-feature-checkbox" <?php checked($is_checked); ?> style="width: 18px; height: 18px; margin: 0;" />
                                            <input type="text" class="wpc-feature-text" value="<?php echo esc_attr($text_val); ?>" placeholder="Text..." style="width: 80px; padding: 2px 4px; font-size: 11px; border: 1px solid #ddd; border-radius: 4px; text-align: center; margin: 0;" />
                                            <input type="hidden" class="wpc-feature-submit" name="wpc_plan_features[<?php echo $f_idx; ?>][plans][<?php echo $plan_idx; ?>]" value="<?php echo esc_attr($val); ?>" />
                                        </div>
                                    </td>
                                <?php endforeach; ?>
                                <td style="padding: 8px; text-align: center;">
                                    <input type="hidden" name="wpc_plan_features[<?php echo $f_idx; ?>][visible]" value="0">
                                    <input type="checkbox" name="wpc_plan_features[<?php echo $f_idx; ?>][visible]" value="1" <?php checked( $feature['visible'] ?? true ); ?> style="width: 18px; height: 18px;" title="Show in shortcode output" />
                                </td>
                                <td style="padding: 8px; text-align: center;">
                                    <button type="button" class="button button-small" onclick="this.closest('tr').remove()" title="Remove">&times;</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    <?php endif; ?>
    </div><!-- .wpc-features-editor-wrapper -->
    
    <?php endif; // end if plans exist ?>
    
    <script>
    // Category Features State
    var wpcCurrentCategory = '<?php echo $has_variants ? esc_js($assigned_cats[0]->slug) : ''; ?>';
    var wpcFeatureIndices = <?php echo json_encode( 
        array_map( function($cat) use ($plan_features_by_category) {
            $cat_features = $plan_features_by_category[$cat->slug] ?? array();
            return max( 0, count( $cat_features ) );
        }, $assigned_cats ? $assigned_cats : array() )
    ); ?>;
    var wpcCategoryPlans = <?php echo json_encode(
        array_combine(
            array_map(function($cat) { return $cat->slug; }, $assigned_cats ? $assigned_cats : array()),
            array_map(function($cat) use ($plans_by_category, $pricing_plans) {
                $cat_plans = $plans_by_category[$cat->slug] ?? array();
                $result = array();
                foreach ($cat_plans as $idx) {
                    if (isset($pricing_plans[$idx]['name'])) {
                        $result[$idx] = $pricing_plans[$idx]['name'];
                    }
                }
                return $result;
            }, $assigned_cats ? $assigned_cats : array())
        )
    ); ?>;
    var wpcLegacyPlans = <?php echo json_encode($all_plan_names); ?>;
    
    //  Copy Shortcode
    function wpcCopyFeatureShortcode(id, category, btn) {
        var text = category 
            ? '[wpc_feature_table id="' + id + '" category="' + category + '"]'
            : '[wpc_feature_table id="' + id + '"]';
            
        if (!navigator.clipboard) {
            var textArea = document.createElement("textarea");
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            btn.innerText = 'Copied!';
            setTimeout(function() { btn.innerText = 'Copy'; }, 2000);
            return;
        }
        navigator.clipboard.writeText(text).then(function() {
            btn.innerText = 'Copied!';
            setTimeout(function() { btn.innerText = 'Copy'; }, 2000);
        });
    }
    
    // Switch Category
    function wpcSwitchFeatureCategory(catSlug) {
        wpcCurrentCategory = catSlug;
        
        // Update active tab
        document.querySelectorAll('.wpc-cat-tab').forEach(function(btn) {
            if (btn.dataset.category === catSlug) {
                btn.style.background = '#6366f1';
                btn.style.color = '#fff';
                btn.classList.add('active');
            } else {
                btn.style.background = '#f9fafb';
                btn.style.color = '#6b7280';
                btn.classList.remove('active');
            }
        });
        
        // Show/hide tables
        document.querySelectorAll('.wpc-cat-feature-table').forEach(function(table) {
            table.style.display = table.dataset.category === catSlug ? 'block' : 'none';
        });
    }
    
    // Toggle Bulk Paste
    function wpcToggleBulkPaste() {
        var area = document.getElementById('wpc-bulk-paste-area');
        area.style.display = area.style.display === 'none' ? 'block' : 'none';
        if (area.style.display === 'block') {
            document.getElementById('wpc-bulk-features').focus();
        }
    }
    
    // Add Bulk Features (Category Mode)
    function wpcAddBulkCatFeatures() {
        var textarea = document.getElementById('wpc-bulk-features');
        var lines = textarea.value.split('\n');
        var tbody = document.querySelector('.wpc-cat-feature-table[data-category="' + wpcCurrentCategory + '"] .wpc-features-tbody-sortable');
        var plans = wpcCategoryPlans[wpcCurrentCategory];
        
        if (!tbody) return;
        
        lines.forEach(function(line) {
            line = line.trim();
            if (line) {
                wpcAddCatFeatureRow(line);
            }
        });
        
        textarea.value = '';
        wpcToggleBulkPaste();
    }
    
    // Add Single Feature Row (Category Mode)
    function wpcAddCatFeatureRow(featureName, checkedPlanIndices) {
        var tbody = document.querySelector('.wpc-cat-feature-table[data-category="' + wpcCurrentCategory + '"] .wpc-features-tbody-sortable');
        var plans = wpcCategoryPlans[wpcCurrentCategory];
        
        if (!tbody || !plans) return;
        
        var idx = tbody.children.length;
        var row = document.createElement('tr');
        row.style.borderBottom = '1px solid #f0f0f0';
        row.style.cursor = 'move';
        row.dataset.featureIndex = idx;
        
        var html = '<td style="padding: 8px; text-align: center;"><span style="cursor: move; color: #9ca3af;">☰</span></td>';
        html += '<td style="padding: 8px;"><input type="text" name="wpc_plan_features_by_category[' + wpcCurrentCategory + '][' + idx + '][name]" value="' + (featureName || '') + '" placeholder="Feature name" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;" /></td>';
        
        Object.keys(plans).forEach(function(planIdx) {
            var val = (checkedPlanIndices && checkedPlanIndices[planIdx] !== undefined) ? checkedPlanIndices[planIdx] : '0';
            var isTextFormat = jQuery('.wpc-features-format-input').val() === 'text';
            if (isTextFormat && val === '1') {
                val = 'Yes';
            }
            var isChecked = val !== '0' ? 'checked' : '';
            var textVal = (val !== '1' && val !== '0') ? val : '';
            
            html += '<td style="padding: 8px; text-align: center; vertical-align: middle;">';
            html += '  <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">';
            html += '    <input type="checkbox" class="wpc-feature-checkbox" ' + isChecked + ' style="width: 18px; height: 18px; margin: 0;" />';
            html += '    <input type="text" class="wpc-feature-text" value="' + textVal + '" placeholder="Text..." style="width: 80px; padding: 2px 4px; font-size: 11px; border: 1px solid #ddd; border-radius: 4px; text-align: center; margin: 0;" />';
            html += '    <input type="hidden" class="wpc-feature-submit" name="wpc_plan_features_by_category[' + wpcCurrentCategory + '][' + idx + '][plans][' + planIdx + ']" value="' + val + '" />';
            html += '  </div>';
            html += '</td>';
        });
        
        html += '<td style="padding: 8px; text-align: center;"><input type="hidden" name="wpc_plan_features_by_category[' + wpcCurrentCategory + '][' + idx + '][visible]" value="0"><input type="checkbox" name="wpc_plan_features_by_category[' + wpcCurrentCategory + '][' + idx + '][visible]" value="1" checked style="width: 18px; height: 18px;" title="Show in shortcode output" /></td>';
        html += '<td style="padding: 8px; text-align: center;"><button type="button" class="button button-small" onclick="this.closest(\'tr\').remove()" title="Remove">&times;</button></td>';
        
        row.innerHTML = html;
        tbody.appendChild(row);
    }
    
    // Initialize sortable for drag-and-drop
    jQuery(document).ready(function($) {
        if (typeof $.fn.sortable !== 'undefined') {
            $('.wpc-features-tbody-sortable').sortable({
                handle: 'td:first-child',
                axis: 'y',
                cursor: 'move',
                placeholder: 'ui-state-highlight',
                update: function() {
                    // Update indices after reordering
                    $(this).find('tr').each(function(newIdx) {
                        $(this).attr('data-feature-index', newIdx);
                    });
                }
            });
        }
    });
    
    // Add Bulk Features (Legacy Mode)
    function wpcAddBulkFeatures() {
        var textarea = document.getElementById('wpc-bulk-features');
        var lines = textarea.value.split('\n');
        
        lines.forEach(function(line) {
            line = line.trim();
            if (line) {
                wpcAddFeatureRow(line);
            }
        });
        
        textarea.value = '';
        wpcToggleBulkPaste();
    }
    
    // Add Single Feature Row (Legacy Mode)
    function wpcAddFeatureRow(featureName, checkedPlanIndices) {
        var tbody = document.querySelector('.wpc-features-table .wpc-features-tbody-sortable');
        if (!tbody) return;
        
        var idx = tbody.children.length;
        var row = document.createElement('tr');
        row.style.borderBottom = '1px solid #f0f0f0';
        row.style.cursor = 'move';
        row.dataset.featureIndex = idx;
        
        var html = '<td style="padding: 8px; text-align: center;"><span style="cursor: move; color: #9ca3af;">☰</span></td>';
        html += '<td style="padding: 8px;"><input type="text" name="wpc_plan_features[' + idx + '][name]" value="' + (featureName || '') + '" placeholder="Feature name" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;" /></td>';
        
        // Add checkboxes for all plans
        <?php foreach ( $all_plan_names as $plan_idx => $plan_name ) : ?>
        var val = (checkedPlanIndices && checkedPlanIndices[<?php echo $plan_idx; ?>] !== undefined) ? checkedPlanIndices[<?php echo $plan_idx; ?>] : '0';
        var isTextFormat = jQuery('.wpc-features-format-input').val() === 'text';
        if (isTextFormat && val === '1') {
            val = 'Yes';
        }
        var isChecked = val !== '0' ? 'checked' : '';
        var textVal = (val !== '1' && val !== '0') ? val : '';
        
        html += '<td style="padding: 8px; text-align: center; vertical-align: middle;">';
        html += '  <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">';
        html += '    <input type="checkbox" class="wpc-feature-checkbox" ' + isChecked + ' style="width: 18px; height: 18px; margin: 0;" />';
        html += '    <input type="text" class="wpc-feature-text" value="' + textVal + '" placeholder="Text..." style="width: 80px; padding: 2px 4px; font-size: 11px; border: 1px solid #ddd; border-radius: 4px; text-align: center; margin: 0;" />';
        html += '    <input type="hidden" class="wpc-feature-submit" name="wpc_plan_features[' + idx + '][plans][<?php echo $plan_idx; ?>]" value="' + val + '" />';
        html += '  </div>';
        html += '</td>';
        <?php endforeach; ?>
        
        html += '<td style="padding: 8px; text-align: center;"><input type="hidden" name="wpc_plan_features[' + idx + '][visible]" value="0"><input type="checkbox" name="wpc_plan_features[' + idx + '][visible]" value="1" checked style="width: 18px; height: 18px;" title="Show in shortcode output" /></td>';
        html += '<td style="padding: 8px; text-align: center;"><button type="button" class="button button-small" onclick="this.closest(\'tr\').remove()" title="Remove">&times;</button></td>';
        
        row.innerHTML = html;
        tbody.appendChild(row);
    }
    
    // Initialize sortable for drag-and-drop
    jQuery(document).ready(function($) {
        if (typeof $.fn.sortable !== 'undefined') {
            $('.wpc-features-tbody-sortable').sortable({
                handle: 'td:first-child',
                axis: 'y',
                cursor: 'move',
                placeholder: 'ui-state-highlight',
                update: function() {
                    // Update indices after reordering
                    $(this).find('tr').each(function(newIdx) {
                        $(this).attr('data-feature-index', newIdx);
                    });
                }
            });
        }
    });
    
    // Select All for Plan Column (Category Mode)
    function wpcSelectAllPlanFeatures(checkbox, categorySlug, planIdx) {
        const table = document.querySelector(`.wpc-cat-feature-table[data-category="${categorySlug}"] .wpc-features-tbody-sortable`);
        if (!table) return;
        
        const checkboxes = table.querySelectorAll(`input[name^="wpc_plan_features_by_category[${categorySlug}]"][name*="[plans][${planIdx}]"]`);
        checkboxes.forEach(cb => cb.checked = checkbox.checked);
    }
    
    // Select All for Visible Column (Category Mode)
    function wpcSelectAllVisible(checkbox, categorySlug) {
        const table = document.querySelector(`.wpc-cat-feature-table[data-category="${categorySlug}"] .wpc-features-tbody-sortable`);
        if (!table) return;
        
        const checkboxes = table.querySelectorAll(`input[name^="wpc_plan_features_by_category[${categorySlug}]"][name$="[visible]"][type="checkbox"]`);
        checkboxes.forEach(cb => cb.checked = checkbox.checked);
    }
    
    // Select All for Plan Column (Legacy Mode)
    function wpcSelectAllPlanFeaturesLegacy(checkbox, planIdx) {
        const table = document.querySelector('.wpc-features-table .wpc-features-tbody-sortable');
        if (!table) return;
        
        const checkboxes = table.querySelectorAll(`input[name^="wpc_plan_features"][name*="[plans][${planIdx}]"]`);
        checkboxes.forEach(cb => cb.checked = checkbox.checked);
    }
    
    // Select All for Visible Column (Legacy Mode)
    function wpcSelectAllVisibleLegacy(checkbox) {
        const table = document.querySelector('.wpc-features-table .wpc-features-tbody-sortable');
        if (!table) return;
        
        const checkboxes = table.querySelectorAll(`input[name^="wpc_plan_features"][name$="[visible]"][type="checkbox"]`);
        checkboxes.forEach(cb => cb.checked = checkbox.checked);
    }

    // AI Generate Features/Pricing
    function wpcAIGenerateFeatures(categorySlug) {
        var btns = document.querySelectorAll('.wpc-ai-generate-btn');
        var firstBtn = btns[0];
        var originalHtml = firstBtn ? firstBtn.innerHTML : '✨ AI Generate Features';
        
        var productName = document.getElementById('wpc-ai-product-name') ? document.getElementById('wpc-ai-product-name').value : '';
        if (!productName) {
            wpcShowToast('Please enter a product name in the AI Assistant panel first.', true);
            return;
        }
        
        var profileId = document.getElementById('wpc-ai-item-profile') ? document.getElementById('wpc-ai-item-profile').value : '';
        var userContext = document.getElementById('wpc-ai-custom-context') ? document.getElementById('wpc-ai-custom-context').value : '';
        var nonce = document.getElementById('wpc_ai_item_nonce') ? document.getElementById('wpc_ai_item_nonce').value : '';
        
        // Read format from toggle group input
        var featuresFormat = jQuery('.wpc-features-format-input').val() || 'boolean';
        
        // Add plan context to prompt so the AI tries to use our exact plan names
        var currentPlans = categorySlug ? wpcCategoryPlans[categorySlug] : wpcLegacyPlans;
        var planNames = [];
        if (currentPlans) {
            Object.keys(currentPlans).forEach(function(k) {
                planNames.push(currentPlans[k]);
            });
        }
        var planContext = '';
        if (planNames.length > 0) {
            planContext = "\nNote: The target plans to generate features for are: " + planNames.join(', ') + ". Make sure features correspond to these plans.";
        }
        
        btns.forEach(function(btn) {
            btn.disabled = true;
            btn.innerHTML = '✨ Generating...';
        });
        
        jQuery.post(ajaxurl, {
            action: 'wpc_ai_generate',
            nonce: nonce,
            prompt_type: 'pricing',
            product_name: productName,
            user_context: userContext + planContext,
            profile_id: profileId,
            features_format: featuresFormat
        }, function(response) {
            btns.forEach(function(btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
            
            if (response.success) {
                var data = response.data;
                if (typeof data === 'string') {
                    var cleanData = data.replace(/^```json\s*/i, '').replace(/```\s*$/, '').trim();
                    if (cleanData.indexOf('{') !== 0 || cleanData.lastIndexOf('}') !== cleanData.length - 1) {
                        var firstBrace = cleanData.indexOf('{');
                        var lastBrace = cleanData.lastIndexOf('}');
                        if (firstBrace !== -1 && lastBrace !== -1) {
                            cleanData = cleanData.substring(firstBrace, lastBrace + 1);
                        }
                    }
                    try {
                        data = JSON.parse(cleanData);
                    } catch (e) {
                        console.error('Failed to parse AI response as JSON:', e);
                        wpcShowToast('Failed to parse AI response: ' + e.message, true);
                        return;
                    }
                }
                
                var plans = data ? data.pricing_plans : null;
                if (!plans || plans.length === 0) {
                    wpcShowToast('No features/plans returned by AI.', true);
                    return;
                }
                
                // Map of unique features to plan values
                var featurePlanMap = {}; // e.g. { "Storage": { "free": "10 GB", "pro": "50 GB" } }
                plans.forEach(function(plan) {
                    var aiPlanName = String(plan.name || '').toLowerCase().trim();
                    if (plan.features && Array.isArray(plan.features)) {
                        plan.features.forEach(function(f) {
                            if (f && typeof f === 'object' && f.name !== undefined && f.name !== null) {
                                var cleanFeature = String(f.name).trim();
                                var val = (f.value !== undefined && f.value !== null) ? String(f.value).trim() : '1';
                                if (val === '1' && featuresFormat === 'text') {
                                    val = 'Yes';
                                }
                                if (cleanFeature) {
                                    if (!featurePlanMap[cleanFeature]) {
                                        featurePlanMap[cleanFeature] = {};
                                    }
                                    featurePlanMap[cleanFeature][aiPlanName] = val;
                                }
                            } else if (f !== undefined && f !== null) {
                                var cleanFeature = String(f).trim();
                                if (cleanFeature) {
                                    if (!featurePlanMap[cleanFeature]) {
                                        featurePlanMap[cleanFeature] = {};
                                    }
                                    featurePlanMap[cleanFeature][aiPlanName] = (featuresFormat === 'text') ? 'Yes' : '1';
                                }
                            }
                        });
                    }
                });
                
                var featureNames = Object.keys(featurePlanMap);
                if (featureNames.length === 0) {
                    wpcShowToast('No features list found in AI pricing data.', true);
                    return;
                }
                
                // Custom 3-button modal: Add vs. Replace vs. Cancel
                var existing = document.getElementById('wpc-feature-import-modal');
                if (existing) existing.remove();
                
                var modal = document.createElement('div');
                modal.id = 'wpc-feature-import-modal';
                modal.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:99999;display:flex;align-items:center;justify-content:center;';
                modal.innerHTML = `
                    <div style="background:white;padding:25px;border-radius:12px;width:90%;max-width:480px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);transform:scale(0.95);opacity:0;transition:all 0.2s;">
                        <h3 style="margin-top:0;font-size:18px;color:#1f2937;font-weight:600;">Import AI Features</h3>
                        <p style="color:#4b5563;line-height:1.5;margin-bottom:25px;">Found <strong>` + featureNames.length + `</strong> features. How would you like to import them into the table?</p>
                        <div style="display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;">
                            <button type="button" class="button" id="wpc-import-cancel" style="padding:6px 12px;">Cancel</button>
                            <button type="button" class="button button-link-delete" id="wpc-import-replace" style="background:#ef4444;border-color:#ef4444;color:white;padding:6px 12px;font-weight:600;border-radius:4px;cursor:pointer;">Replace Existing</button>
                            <button type="button" class="button button-primary" id="wpc-import-add" style="background:#6366f1;border-color:#6366f1;color:white;padding:6px 12px;font-weight:600;border-radius:4px;cursor:pointer;">Add / Append</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
                
                // Animation
                requestAnimationFrame(function() {
                    var inner = modal.querySelector('div');
                    if (inner) {
                        inner.style.transform = 'scale(1)';
                        inner.style.opacity = '1';
                    }
                });
                
                var runImport = function(replaceExisting) {
                    var currentPlanIds = Object.keys(currentPlans || {});
                    
                    // Clear existing features if replace is selected
                    if (replaceExisting) {
                        var tbody = categorySlug 
                            ? document.querySelector('.wpc-cat-feature-table[data-category="' + categorySlug + '"] .wpc-features-tbody-sortable')
                            : document.querySelector('.wpc-features-table .wpc-features-tbody-sortable');
                        if (tbody) {
                            tbody.innerHTML = '';
                        }
                    }
                    
                    // Map AI plans to table plan indices
                    var aiPlanToColumnIdx = {};
                    plans.forEach(function(plan, aiIdx) {
                        var aiPlanName = String(plan.name || '').toLowerCase().trim();
                        var matchedIdx = null;
                        
                        // 1. Try exact or partial name match
                        if (currentPlans) {
                            for (var idx in currentPlans) {
                                if (currentPlans.hasOwnProperty(idx)) {
                                    var colPlanName = String(currentPlans[idx]).toLowerCase().trim();
                                    if (aiPlanName.indexOf(colPlanName) !== -1 || colPlanName.indexOf(aiPlanName) !== -1) {
                                        matchedIdx = idx;
                                        break;
                                    }
                                }
                            }
                        }
                        
                        // 2. Fallback to index mapping if no name match and currentPlanIds has a column at this index
                        if (matchedIdx === null && aiIdx < currentPlanIds.length) {
                            matchedIdx = currentPlanIds[aiIdx];
                        }
                        
                        if (matchedIdx !== null) {
                            aiPlanToColumnIdx[aiPlanName] = matchedIdx;
                        }
                    });
                    
                    featureNames.forEach(function(featureName) {
                        var checkedPlanIndices = {};
                        var planValues = featurePlanMap[featureName] || {};
                        Object.keys(planValues).forEach(function(aiName) {
                            var colIdx = aiPlanToColumnIdx[aiName];
                            if (colIdx !== undefined) {
                                checkedPlanIndices[colIdx] = planValues[aiName];
                            }
                        });
                        
                        if (categorySlug) {
                            wpcAddCatFeatureRow(featureName, checkedPlanIndices);
                        } else {
                            wpcAddFeatureRow(featureName, checkedPlanIndices);
                        }
                    });
                    wpcShowToast('Imported ' + featureNames.length + ' features successfully!');
                    modal.remove();
                };
                
                document.getElementById('wpc-import-cancel').onclick = function() {
                    modal.remove();
                };
                
                document.getElementById('wpc-import-replace').onclick = function() {
                    runImport(true);
                };
                
                document.getElementById('wpc-import-add').onclick = function() {
                    runImport(false);
                };
                
            } else {
                wpcShowToast(response.data || 'Failed to generate features.', true);
            }
        }).fail(function() {
            btns.forEach(function(btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
            wpcShowToast('Server error during generation.', true);
        });
    }
    
    // Hybrid Feature Cells Handler: Checkbox & Text Sync
    jQuery(document).ready(function($) {
        // Toggle layout format on button group click
        $(document).on('click', '.wpc-features-format-btn', function() {
            var val = $(this).data('value');
            $('.wpc-features-format-input').val(val);
            
            $('.wpc-features-format-btn').each(function() {
                var btnVal = $(this).data('value');
                if (btnVal === val) {
                    $(this).addClass('active').css({
                        'background': '#4f46e5',
                        'color': '#fff',
                        'box-shadow': '0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06)'
                    });
                } else {
                    $(this).removeClass('active').css({
                        'background': 'transparent',
                        'color': '#64748b',
                        'box-shadow': 'none'
                    });
                }
            });
            
            $('.wpc-features-editor-wrapper')
                .removeClass('wpc-features-format-boolean wpc-features-format-text')
                .addClass('wpc-features-format-' + val);
        });

        $(document).on('change', '.wpc-feature-checkbox', function() {
            var parent = $(this).closest('div');
            var cb = this;
            var textInput = parent.find('.wpc-feature-text')[0];
            var hidden = parent.find('.wpc-feature-submit')[0];
            
            if (!cb.checked) {
                textInput.value = '';
                hidden.value = '0';
            } else {
                if (textInput.value.trim() !== '') {
                    hidden.value = textInput.value.trim();
                } else {
                    hidden.value = '1';
                }
            }
        });

        $(document).on('keyup change', '.wpc-feature-text', function() {
            var parent = $(this).closest('div');
            var textInput = this;
            var cb = parent.find('.wpc-feature-checkbox')[0];
            var hidden = parent.find('.wpc-feature-submit')[0];
            
            if (textInput.value.trim() !== '') {
                cb.checked = true;
                hidden.value = textInput.value.trim();
            } else {
                // If in text format mode, clearing text should uncheck it
                var isTextFormat = $('.wpc-features-format-input').val() === 'text';
                if (isTextFormat) {
                    cb.checked = false;
                    hidden.value = '0';
                } else {
                    hidden.value = cb.checked ? '1' : '0';
                }
            }
        });
    });
    </script>
    
    <style>
    .wpc-features-format-boolean .wpc-feature-text { display: none !important; }
    .wpc-features-format-boolean .wpc-feature-checkbox { display: inline-block !important; }
    .wpc-features-format-text .wpc-feature-checkbox { display: none !important; }
    .wpc-features-format-text .wpc-feature-text { display: inline-block !important; }
    .wpc-features-format-text .wpc-select-all-plan,
    .wpc-features-format-text .wpc-select-all-plan-legacy { display: none !important; }
    .wpc-features-format-btn:hover:not(.active) {
        background: #f3f4f6 !important;
    }
    .wpc-cat-tab:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .ui-state-highlight {
        height: 50px;
        background: #f0f9ff;
        border: 2px dashed #60a5fa;
    }
    </style>
    <?php
}
