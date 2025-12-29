<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add Meta Box to List
 */
function wpc_list_add_meta_box() {
    add_meta_box(
        'wpc_list_config',
        __( 'List Configuration', 'wp-comparison-builder' ),
        'wpc_render_list_meta_box',
        'comparison_list',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'wpc_list_add_meta_box' );

/**
 * Render List Config Meta Box
 */
// Enqueue scripts for list sorting
function wpc_list_admin_scripts( $hook ) {
    global $post;
    
    if ( ( $hook === 'post-new.php' || $hook === 'post.php' ) ) {
        if ( 'comparison_list' === $post->post_type ) {
            wp_enqueue_script( 'jquery-ui-sortable' );
        }
    }
}
add_action( 'admin_enqueue_scripts', 'wpc_list_admin_scripts' );

/**
 * Render List Config Meta Box
 */
function wpc_render_list_meta_box( $post ) {
    wp_nonce_field( 'wpc_save_list', 'wpc_list_nonce' );

    $selected_ids = get_post_meta( $post->ID, '_wpc_list_ids', true ) ?: [];
    // Fallback/Migration
    if (empty($selected_ids)) $selected_ids = get_post_meta( $post->ID, '_hg_list_ids', true ) ?: [];
    // Ensure it's an array of integers for comparison
    $selected_ids = array_map('intval', (array)$selected_ids);

    $featured_ids = get_post_meta( $post->ID, '_wpc_list_featured', true ) ?: [];
    if (empty($featured_ids)) $featured_ids = get_post_meta( $post->ID, '_hg_list_featured', true ) ?: [];

    $badge_texts = get_post_meta( $post->ID, '_wpc_list_badge_texts', true ) ?: [];
    if (empty($badge_texts)) $badge_texts = get_post_meta( $post->ID, '_hg_list_badge_texts', true ) ?: [];

    $badge_colors = get_post_meta( $post->ID, '_wpc_list_badge_colors', true ) ?: [];
    if (empty($badge_colors)) $badge_colors = get_post_meta( $post->ID, '_hg_list_badge_colors', true ) ?: [];

    $limit = get_post_meta( $post->ID, '_wpc_list_limit', true );
    if (empty($limit)) $limit = get_post_meta( $post->ID, '_hg_list_limit', true );

    // Get all comparison items
    $enable_comparison = get_post_meta( $post->ID, '_wpc_list_enable_comparison', true );
    if ($enable_comparison === '') $enable_comparison = '1'; // Default to true

    $custom_button_text = get_post_meta( $post->ID, '_wpc_list_button_text', true );

    $filter_cats = get_post_meta( $post->ID, '_wpc_list_filter_cats', true ) ?: [];
    $filter_feats = get_post_meta( $post->ID, '_wpc_list_filter_feats', true ) ?: [];
    $filter_layout = get_post_meta( $post->ID, '_wpc_list_filter_layout', true ) ?: 'default';

    // Show All Items card settings
    $show_all_enabled = get_post_meta( $post->ID, '_wpc_list_show_all_enabled', true );
    if ($show_all_enabled === '') $show_all_enabled = '1'; // Default to enabled
    $initial_visible_count = get_post_meta( $post->ID, '_wpc_list_initial_visible', true );
    if (empty($initial_visible_count)) $initial_visible_count = '8'; // Default to 8

    // Get Terms for filters configuration
    $all_cats = get_terms( array( 'taxonomy' => 'comparison_category', 'hide_empty' => false ) );
    $all_feats = get_terms( array( 'taxonomy' => 'comparison_feature', 'hide_empty' => false ) );

    // Get all comparison items
    $all_items = get_posts( array(
        'post_type' => 'comparison_item',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC'
    ) );
    
    // Sort items: Selected items first (in saved order), then unselected items (alphabetical)
    $mapped_items = array();
    foreach($all_items as $p) {
        $mapped_items[$p->ID] = $p;
    }
    
    $sorted_items = array();
    
    // 1. Add selected items in order
    foreach ($selected_ids as $sid) {
        if (isset($mapped_items[$sid])) {
            $sorted_items[] = $mapped_items[$sid];
            unset($mapped_items[$sid]); // Remove so we don't add it again
        }
    }
    
    // 2. Add remaining items (already sorted alphabetically from get_posts)
    foreach ($mapped_items as $p) {
        $sorted_items[] = $p;
    }
    
    ?>
    <div class="wpc-list-config">
        
        <!-- General Settings -->
        <div style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; margin-bottom: 20px;">
            <h2 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">List Settings</h2>
            
            <div style="display: flex; gap: 30px; margin-bottom: 15px;">
                <!-- Enable Comparison -->
                <div style="flex: 1;">
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Comparison Functionality</label>
                    <label>
                        <input type="checkbox" name="wpc_list_enable_comparison" value="1" <?php checked( $enable_comparison, '1' ); ?> />
                        Enable "Compare" checkbox and popup
                    </label>
                    <p class="description">If disabled, checkboxes are hidden and "View Details" can link directly to a URL.</p>
                </div>

                <!-- Custom Button Text -->
                <div style="flex: 1;">
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">"View Details" Button Text</label>
                    <input type="text" name="wpc_list_button_text" value="<?php echo esc_attr( $custom_button_text ); ?>" placeholder="View Details" style="width: 100%;" />
                    <p class="description">Override the default button text for this list.</p>
                </div>

                <!-- Filter Layout -->
                <div style="flex: 1;">
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Filter Layout</label>
                    <select name="wpc_list_filter_layout" style="width: 100%;">
                        <option value="default" <?php selected( $filter_layout, 'default' ); ?>>Default (As Global)</option>
                        <option value="top" <?php selected( $filter_layout, 'top' ); ?>>Horizontal (Top)</option>
                        <option value="sidebar" <?php selected( $filter_layout, 'sidebar' ); ?>>Vertical (Sidebar)</option>
                    </select>
                    <p class="description">Choose layout for this list.</p>
                </div>
            </div>

            <!-- Show All Items Card Settings -->
            <div style="display: flex; gap: 30px; margin-top: 15px; padding: 15px; background: #f9f9f9; border-radius: 4px;">
                <div style="flex: 1;">
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">"Show All Items" Card</label>
                    <label>
                        <input type="checkbox" name="wpc_list_show_all_enabled" value="1" <?php checked( $show_all_enabled, '1' ); ?> />
                        Show "Show All Items" card after initial visible count
                    </label>
                    <p class="description">If unchecked, no "Show All" card will appear.</p>
                </div>
                
                <div style="flex: 1;">
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Initial Visible Count</label>
                    <input type="number" name="wpc_list_initial_visible" value="<?php echo esc_attr( $initial_visible_count ); ?>" min="3" max="100" style="width: 80px;" />
                    <p class="description">How many cards to show before the "Show All Items" card appears. (Min: 3, Default: 8)</p>
                </div>
            </div>

            <!-- Custom Filters -->
            <div style="margin-top: 20px;">
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Custom Filter Visibility (Optional)</label>
                <p class="description" style="margin-bottom: 10px;">Select specific categories/features to show in the sidebar filters. Leave empty to show all relevant ones automatically.</p>
                
                <div style="display: flex; gap: 20px;">
                    <!-- Categories -->
                    <div style="flex: 1; border: 1px solid #ddd; padding: 10px; max-height: 150px; overflow-y: auto;">
                        <strong>Categories</strong>
                        <?php if ( ! empty( $all_cats ) && ! is_wp_error( $all_cats ) ) : ?>
                            <?php foreach ( $all_cats as $cat ) : ?>
                                <label style="display:block;">
                                    <input type="checkbox" name="wpc_list_filter_cats[]" value="<?php echo esc_attr( $cat->term_id ); ?>" <?php checked( in_array( $cat->term_id, $filter_cats ) ); ?> />
                                    <?php echo esc_html( $cat->name ); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Features -->
                    <div style="flex: 1; border: 1px solid #ddd; padding: 10px; max-height: 150px; overflow-y: auto;">
                        <strong>Features</strong>
                        <?php if ( ! empty( $all_feats ) && ! is_wp_error( $all_feats ) ) : ?>
                            <?php foreach ( $all_feats as $feat ) : ?>
                                <label style="display:block;">
                                    <input type="checkbox" name="wpc_list_filter_feats[]" value="<?php echo esc_attr( $feat->term_id ); ?>" <?php checked( in_array( $feat->term_id, $filter_feats ) ); ?> />
                                    <?php echo esc_html( $feat->name ); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <hr style="margin: 20px 0;">

            <!-- RENAME LABELS -->
            <div style="margin-top: 20px;">
                 <h3 style="margin-top: 0; margin-bottom: 15px;">Rename Labels</h3>
                 <div style="display: flex; gap: 30px;">
                    <div style="flex: 1;">
                        <label style="font-weight: bold; display: block; margin-bottom: 5px;">Categories Label</label>
                        <input type="text" name="wpc_list_cat_label" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_cat_label', true) ); ?>" placeholder="Default: Categories" style="width: 100%;" />
                    </div>
                    <div style="flex: 1;">
                        <label style="font-weight: bold; display: block; margin-bottom: 5px;">Features Label</label>
                        <input type="text" name="wpc_list_feat_label" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_feat_label', true) ); ?>" placeholder="Default: Features" style="width: 100%;" />
                    </div>
                 </div>
            </div>

            <hr style="margin: 20px 0;">

            <!-- COMPARISON BUILDER SETTINGS (OVERRIDES) -->
            <div style="margin-top: 20px;">
                 <div style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="jQuery('#wpc_visual_settings').slideToggle();">
                     <h3 style="margin: 0;">Comparison Builder Settings (Override Global)</h3>
                     <span class="dashicons dashicons-arrow-down-alt2"></span>
                 </div>
                 
                 <div id="wpc_visual_settings" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                    <p class="description" style="margin-bottom: 15px;">Leave fields empty to use global default settings.</p>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        
                        <!-- Primary Color -->
                        <div>
                            <label style="display: block; margin-bottom: 5px;">Primary Color (Buttons, Badges)</label>
                            <input type="color" name="wpc_list_primary_color" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_primary_color', true) ?: '#6366f1' ); ?>" style="height: 30px; vertical-align: middle;" />
                            <label><input type="checkbox" name="wpc_list_use_primary" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_primary', true), '1' ); ?> /> Apply Override</label>
                        </div>

                        <!-- Accent Color -->
                        <div>
                            <label style="display: block; margin-bottom: 5px;">Accent Color (Active Filters)</label>
                            <input type="color" name="wpc_list_accent_color" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_accent_color', true) ?: '#0d9488' ); ?>" style="height: 30px; vertical-align: middle;" />
                            <label><input type="checkbox" name="wpc_list_use_accent" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_accent', true), '1' ); ?> /> Apply Override</label>
                        </div>

                        <!-- Secondary Color -->
                        <div>
                            <label style="display: block; margin-bottom: 5px;">Secondary Color (Dark Accents)</label>
                            <input type="color" name="wpc_list_secondary_color" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_secondary_color', true) ?: '#1e293b' ); ?>" style="height: 30px; vertical-align: middle;" />
                            <label><input type="checkbox" name="wpc_list_use_secondary" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_secondary', true), '1' ); ?> /> Apply Override</label>
                        </div>
                        
                        <!-- Card Border Color -->
                        <div>
                            <label style="display: block; margin-bottom: 5px;">Card Border Color</label>
                            <input type="color" name="wpc_list_border_color" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_border_color', true) ?: '#e2e8f0' ); ?>" style="height: 30px; vertical-align: middle;" />
                            <label><input type="checkbox" name="wpc_list_use_border" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_border', true), '1' ); ?> /> Apply Override</label>
                        </div>

                         <!-- Pricing Banner Color -->
                        <div>
                            <label style="display: block; margin-bottom: 5px;">Pricing Banner Color</label>
                            <input type="color" name="wpc_list_banner_color" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_banner_color', true) ?: '#10b981' ); ?>" style="height: 30px; vertical-align: middle;" />
                            <label><input type="checkbox" name="wpc_list_use_banner" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_banner', true), '1' ); ?> /> Apply Override</label>
                        </div>

                        <!-- Button Hover Color -->
                        <div>
                            <label style="display: block; margin-bottom: 5px;">Button Hover Color</label>
                            <input type="color" name="wpc_list_hover_color" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_hover_color', true) ?: '#059669' ); ?>" style="height: 30px; vertical-align: middle;" />
                            <label><input type="checkbox" name="wpc_list_use_hover" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_hover', true), '1' ); ?> /> Apply Override</label>
                        </div>

                    </div>
                    
                    <hr style="margin: 20px 0;">
                    
                    <!-- Pricing Table Visual Style -->
                    <h4 style="margin: 0 0 15px 0; color: #1e40af;">Pricing Table Visual Style</h4>
                    <p class="description" style="margin-bottom: 15px;">Customize the pricing table header and button appearance for this list.</p>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <!-- Header Background -->
                        <div>
                            <label style="display: block; margin-bottom: 5px;">Header Background</label>
                            <?php $pt_header_bg = get_post_meta($post->ID, '_wpc_list_pt_header_bg', true); ?>
                            <input type="color" name="wpc_list_pt_header_bg" value="<?php echo esc_attr( $pt_header_bg ?: '#f8fafc' ); ?>" style="height: 30px; vertical-align: middle;" />
                            <label><input type="checkbox" name="wpc_list_use_pt_header_bg" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_pt_header_bg', true), '1' ); ?> /> Apply Override</label>
                        </div>
                        
                        <!-- Header Text Color -->
                        <div>
                            <label style="display: block; margin-bottom: 5px;">Header Text Color</label>
                            <?php $pt_header_text = get_post_meta($post->ID, '_wpc_list_pt_header_text', true); ?>
                            <input type="color" name="wpc_list_pt_header_text" value="<?php echo esc_attr( $pt_header_text ?: '#0f172a' ); ?>" style="height: 30px; vertical-align: middle;" />
                            <label><input type="checkbox" name="wpc_list_use_pt_header_text" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_pt_header_text', true), '1' ); ?> /> Apply Override</label>
                        </div>
                        
                        <!-- Button Background -->
                        <div>
                            <label style="display: block; margin-bottom: 5px;">Button Background</label>
                            <?php $pt_btn_bg = get_post_meta($post->ID, '_wpc_list_pt_btn_bg', true); ?>
                            <input type="color" name="wpc_list_pt_btn_bg" value="<?php echo esc_attr( $pt_btn_bg ?: '#6366f1' ); ?>" style="height: 30px; vertical-align: middle;" />
                            <label><input type="checkbox" name="wpc_list_use_pt_btn_bg" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_pt_btn_bg', true), '1' ); ?> /> Apply Override</label>
                        </div>
                        
                        <!-- Button Text Color -->
                        <div>
                            <label style="display: block; margin-bottom: 5px;">Button Text Color</label>
                            <?php $pt_btn_text = get_post_meta($post->ID, '_wpc_list_pt_btn_text', true); ?>
                            <input type="color" name="wpc_list_pt_btn_text" value="<?php echo esc_attr( $pt_btn_text ?: '#ffffff' ); ?>" style="height: 30px; vertical-align: middle;" />
                            <label><input type="checkbox" name="wpc_list_use_pt_btn_text" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_pt_btn_text', true), '1' ); ?> /> Apply Override</label>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                        <!-- Table Button Position -->
                        <div>
                            <label style="display: block; margin-bottom: 5px;">Table Button Position</label>
                            <?php $pt_btn_pos_table = get_post_meta($post->ID, '_wpc_list_pt_btn_pos_table', true); ?>
                            <select name="wpc_list_pt_btn_pos_table" style="width: 100%;">
                                <option value="" <?php selected($pt_btn_pos_table, ''); ?>>Default (Global)</option>
                                <option value="after_price" <?php selected($pt_btn_pos_table, 'after_price'); ?>>After Price</option>
                                <option value="after_features" <?php selected($pt_btn_pos_table, 'after_features'); ?>>After Features</option>
                            </select>
                            <p class="description">Where to show "Select Plan" button in inline tables.</p>
                        </div>
                        
                        <!-- Popup Button Position -->
                        <div>
                            <label style="display: block; margin-bottom: 5px;">Popup Button Position</label>
                            <?php $pt_btn_pos_popup = get_post_meta($post->ID, '_wpc_list_pt_btn_pos_popup', true); ?>
                            <select name="wpc_list_pt_btn_pos_popup" style="width: 100%;">
                                <option value="" <?php selected($pt_btn_pos_popup, ''); ?>>Default (Global)</option>
                                <option value="after_price" <?php selected($pt_btn_pos_popup, 'after_price'); ?>>After Price</option>
                                <option value="after_features" <?php selected($pt_btn_pos_popup, 'after_features'); ?>>After Features</option>
                            </select>
                            <p class="description">Where to show "Select Plan" button in popups.</p>
                        </div>
                    </div>
                    
                    <hr style="margin: 20px 0;">
                    
                    <div style="margin-top: 20px;">
                        <label style="font-weight: bold; display: block; margin-bottom: 5px;">Settings</label>
                         <label style="display: block; margin-bottom: 5px;">
                            <input type="hidden" name="wpc_list_show_plans_set" value="1">
                            <?php $show_plans = get_post_meta($post->ID, '_wpc_list_show_plans', true); ?>
                            <!-- Use a select for 3 states: Default, Yes, No -->
                            <select name="wpc_list_show_plans">
                                <option value="" <?php selected($show_plans, ''); ?>>Default (Global Setting)</option>
                                <option value="1" <?php selected($show_plans, '1'); ?>>Yes (Show Buttons)</option>
                                <option value="0" <?php selected($show_plans, '0'); ?>>No (Hide Buttons)</option>
                            </select>
                            Show "Select Plan" buttons in pricing popups
                        </label>
                    </div>

                 </div>
            </div>
        </div>

        <p class="description">
            <?php _e( 'Select items to include. <strong>Drag and drop rows to reorder them (selected items first).</strong>', 'wp-comparison-builder' ); ?>
        </p>
        
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 30px;"><span class="dashicons dashicons-menu" title="Drag to reorder"></span></th>
                    <th style="width: 60px;">
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="checkbox" id="wpc-select-all" title="Select All / Deselect All" />
                            <span style="font-size: 11px;"><?php _e( 'All', 'wp-comparison-builder' ); ?></span>
                        </label>
                    </th>
                    <th><?php _e( 'Item Name', 'wp-comparison-builder' ); ?></th>
                    <th style="width: 100px; text-align: center;"><?php _e( 'Featured?', 'wp-comparison-builder' ); ?></th>
                    <th style="width: 180px;"><?php _e( 'Featured Badge Text', 'wp-comparison-builder' ); ?></th>
                    <th style="width: 120px;"><?php _e( 'Badge Color', 'wp-comparison-builder' ); ?></th>
                </tr>
            </thead>
            <tbody class="wpc-sortable-list">
                <?php if ( $sorted_items ) : ?>
                    <?php foreach ( $sorted_items as $item ) :
                        $is_selected = in_array( $item->ID, $selected_ids );
                        $is_featured = in_array( $item->ID, $featured_ids );
                        $badge_text = isset( $badge_texts[ $item->ID ] ) ? $badge_texts[ $item->ID ] : '';
                        $badge_color = isset( $badge_colors[ $item->ID ] ) ? $badge_colors[ $item->ID ] : '#6366f1';
                        
                        $row_style = $is_selected ? 'background-color: #f0f6fc;' : '';
                    ?>
                        <tr class="wpc-item-row <?php echo $is_selected ? 'selected-row' : ''; ?>" style="<?php echo $row_style; ?>" data-id="<?php echo $item->ID; ?>">
                            <td class="drag-handle" style="cursor: move; color: #ccc;">
                                <span class="dashicons dashicons-menu"></span>
                            </td>
                            <td>
                                <input 
                                    type="checkbox" 
                                    name="item_ids[]" 
                                    value="<?php echo esc_attr( $item->ID ); ?>"
                                    class="wpc-item-select"
                                    <?php checked( $is_selected ); ?>
                                />
                            </td>
                            <td>
                                <strong><?php echo esc_html( $item->post_title ); ?></strong>
                            </td>
                            <td style="text-align: center;">
                                <input 
                                    type="checkbox" 
                                    name="featured_ids[]" 
                                    value="<?php echo esc_attr( $item->ID ); ?>"
                                    <?php checked( $is_featured ); ?>
                                />
                            </td>
                            <td>
                                <input 
                                    type="text" 
                                    name="badge_texts[<?php echo esc_attr( $item->ID ); ?>]" 
                                    value="<?php echo esc_attr( $badge_text ); ?>"
                                    placeholder="e.g., Top Choice"
                                    style="width: 100%; padding: 4px 8px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px;"
                                />
                                <small style="display: block; color: #666; margin-top: 2px; font-size: 10px;">Only if Featured</small>
                            </td>
                            <td>
                                <input 
                                    type="color" 
                                    name="badge_colors[<?php echo esc_attr( $item->ID ); ?>]" 
                                    value="<?php echo esc_attr( $badge_color ); ?>"
                                    style="width: 60px; height: 32px; border: 1px solid #ddd; border-radius: 3px; cursor: pointer;"
                                    title="Choose badge color"
                                />
                                <small style="display: block; color: #666; margin-top: 2px; font-size: 10px;">Badge color</small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="6"><?php _e( 'No items found.', 'wp-comparison-builder' ); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <p style="margin-top: 20px;">
            <label><strong><?php _e( 'Limit Number of Items:', 'wp-comparison-builder' ); ?></strong></label>
            <input type="number" name="wpc_list_limit" value="<?php echo $limit ? esc_attr( $limit ) : ''; ?>" style="width: 80px;" />
            <span class="description"><?php _e( '(Leave empty for no limit)', 'wp-comparison-builder' ); ?></span>
        </p>

        <div style="margin-top: 20px; padding: 15px; background: #f0f0f1; border: 1px solid #ccd0d4;">
            <strong><?php _e( 'Shortcode:', 'wp-comparison-builder' ); ?></strong>
            <code style="font-size: 1.2em; display: inline-block; margin-left: 10px; user-select: all;">[wpc_list id="<?php echo $post->ID; ?>"]</code>
            <p class="description" style="margin-top: 5px;"><?php _e( 'Copy and paste this shortcode to display this specific list.', 'wp-comparison-builder' ); ?></p>
        </div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        // Initialize Sortable
        $('.wpc-sortable-list').sortable({
            handle: '.drag-handle',
            placeholder: 'ui-state-highlight',
            axis: 'y',
            start: function(e, ui) {
                ui.placeholder.height(ui.item.height());
                ui.placeholder.css('background-color', '#fff9c4');
            }
        });

        function toggleFeaturedInputs(checkbox) {
            var row = $(checkbox).closest('tr');
            var inputs = row.find('input[type="text"], input[type="color"]');
            var labels = row.find('small');
            
            // Also highlight row if selected (using the main item checkbox really, but feature check might be related contextually? No, usually main check handles main opacity)
            // Actually let's assume this handles the featured inputs based on FEATURED checkbox
            if ($(checkbox).is(':checked')) {
                inputs.prop('disabled', false).css('opacity', '1');
                labels.css('opacity', '1');
            } else {
                inputs.prop('disabled', true).css('opacity', '0.5');
                labels.css('opacity', '0.5');
            }
        }
        
        // Highlight row when item is selected
        function toggleRowHighlight(checkbox) {
            var row = $(checkbox).closest('tr');
            if ($(checkbox).is(':checked')) {
                row.addClass('selected-row').css('background-color', '#f0f6fc');
            } else {
                row.removeClass('selected-row').css('background-color', '');
            }
        }

        // Initialize Featured Inputs
        $('input[name="featured_ids[]"]').each(function() {
            toggleFeaturedInputs(this);
        });

        // Toggle Featured Inputs on change
        $(document).on('change', 'input[name="featured_ids[]"]', function() {
            toggleFeaturedInputs(this);
        });
        
        // Highlight logic initialization
        $('.wpc-item-select').on('change', function() {
            toggleRowHighlight(this);
            updateSelectAllState();
        });
        
        // Select All functionality
        $('#wpc-select-all').on('change', function() {
            var isChecked = $(this).is(':checked');
            $('.wpc-item-select').each(function() {
                $(this).prop('checked', isChecked);
                toggleRowHighlight(this);
            });
        });
        
        // Update Select All checkbox state based on item checkboxes
        function updateSelectAllState() {
            var allItems = $('.wpc-item-select');
            var checkedItems = $('.wpc-item-select:checked');
            
            if (checkedItems.length === 0) {
                $('#wpc-select-all').prop('checked', false).prop('indeterminate', false);
            } else if (checkedItems.length === allItems.length) {
                $('#wpc-select-all').prop('checked', true).prop('indeterminate', false);
            } else {
                $('#wpc-select-all').prop('checked', false).prop('indeterminate', true);
            }
        }
        
        // Initialize Select All state on page load
        updateSelectAllState();
    });
    </script>
    <?php
}

/**
 * Save List Data
 */
function wpc_save_list_meta( $post_id ) {
    if ( ! isset( $_POST['wpc_list_nonce'] ) || ! wp_verify_nonce( $_POST['wpc_list_nonce'], 'wpc_save_list' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // Save item IDs
    if ( isset( $_POST['item_ids'] ) && is_array( $_POST['item_ids'] ) ) {
        $ids = array_map( 'intval', $_POST['item_ids'] );
        update_post_meta( $post_id, '_wpc_list_ids', $ids );
    } else {
        update_post_meta( $post_id, '_wpc_list_ids', [] );
    }

    // Save featured IDs
    if ( isset( $_POST['featured_ids'] ) && is_array( $_POST['featured_ids'] ) ) {
        $featured_ids = array_map( 'intval', $_POST['featured_ids'] );
        update_post_meta( $post_id, '_wpc_list_featured', $featured_ids );
    } else {
        update_post_meta( $post_id, '_wpc_list_featured', [] );
    }
    
    // Save badge texts
    if ( isset( $_POST['badge_texts'] ) && is_array( $_POST['badge_texts'] ) ) {
        $badge_texts = array();
        foreach ( $_POST['badge_texts'] as $item_id => $text ) {
            $badge_texts[ intval( $item_id ) ] = sanitize_text_field( $text );
        }
        update_post_meta( $post_id, '_wpc_list_badge_texts', $badge_texts );
    } else {
        update_post_meta( $post_id, '_wpc_list_badge_texts', [] );
    }
    
    // Save badge colors
    if ( isset( $_POST['badge_colors'] ) && is_array( $_POST['badge_colors'] ) ) {
        $badge_colors = array();
        foreach ( $_POST['badge_colors'] as $item_id => $color ) {
            $sanitized_color = sanitize_hex_color( $color );
            if ( $sanitized_color ) {
                $badge_colors[ intval( $item_id ) ] = $sanitized_color;
            }
        }
        update_post_meta( $post_id, '_wpc_list_badge_colors', $badge_colors );
    } else {
        update_post_meta( $post_id, '_wpc_list_badge_colors', [] );
    }

    // Save limit
    if ( isset( $_POST['wpc_list_limit'] ) ) {
        $limit = sanitize_text_field( $_POST['wpc_list_limit'] );
        update_post_meta( $post_id, '_wpc_list_limit', $limit );
    } else {
        update_post_meta( $post_id, '_wpc_list_limit', '' );
    }

    // Save Enable Comparison
    if ( isset( $_POST['wpc_list_enable_comparison'] ) ) {
        update_post_meta( $post_id, '_wpc_list_enable_comparison', '1' );
    } else {
        // If unchecked, it won't submit, so we assume '0' (disabled) if not present?
        // Wait, checkboxes only send if checked. So if missing, it's disabled.
        // But our default is enabled. So we need to handle the case where it's unchecked explicitly.
        // If the nonce is verified (which it is above), and the checkbox is missing, it means user unchecked it.
        update_post_meta( $post_id, '_wpc_list_enable_comparison', '0' );
    }

    // Save Filter Layout
    if ( isset( $_POST['wpc_list_filter_layout'] ) ) {
        update_post_meta( $post_id, '_wpc_list_filter_layout', sanitize_text_field( $_POST['wpc_list_filter_layout'] ) );
    }

    // Save Button Text
    if ( isset( $_POST['wpc_list_button_text'] ) ) {
        update_post_meta( $post_id, '_wpc_list_button_text', sanitize_text_field( $_POST['wpc_list_button_text'] ) );
    } else {
        delete_post_meta( $post_id, '_wpc_list_button_text' );
    }

    // Save Filter Cats
    if ( isset( $_POST['wpc_list_filter_cats'] ) ) {
        $cats = array_map( 'intval', $_POST['wpc_list_filter_cats'] );
        update_post_meta( $post_id, '_wpc_list_filter_cats', $cats );
    } else {
        update_post_meta( $post_id, '_wpc_list_filter_cats', [] );
    }

    // Save Filter Feats
    if ( isset( $_POST['wpc_list_filter_feats'] ) ) {
        $feats = array_map( 'intval', $_POST['wpc_list_filter_feats'] );
        update_post_meta( $post_id, '_wpc_list_filter_feats', $feats );
    } else {
        update_post_meta( $post_id, '_wpc_list_filter_feats', [] );
    }

    // --- NEW SETTINGS ---

    // Show All Items settings
    if ( isset( $_POST['wpc_list_show_all_enabled'] ) ) {
        update_post_meta( $post_id, '_wpc_list_show_all_enabled', '1' );
    } else {
        update_post_meta( $post_id, '_wpc_list_show_all_enabled', '0' );
    }
    
    if ( isset( $_POST['wpc_list_initial_visible'] ) ) {
        $initial_count = intval( $_POST['wpc_list_initial_visible'] );
        if ($initial_count < 3) $initial_count = 3; // Minimum 3
        update_post_meta( $post_id, '_wpc_list_initial_visible', $initial_count );
    }

    // Labels
    update_post_meta($post_id, '_wpc_list_cat_label', sanitize_text_field($_POST['wpc_list_cat_label']));
    update_post_meta($post_id, '_wpc_list_feat_label', sanitize_text_field($_POST['wpc_list_feat_label']));

    // Colors & Overrides
    $colors = ['primary', 'accent', 'secondary', 'border', 'banner', 'hover'];
    foreach ($colors as $c) {
        $key = "_wpc_list_{$c}_color";
        $use_key = "_wpc_list_use_{$c}";
        
        if (isset($_POST["wpc_list_{$c}_color"])) {
            update_post_meta($post_id, $key, sanitize_hex_color($_POST["wpc_list_{$c}_color"]));
        }
        
        if (isset($_POST["wpc_list_use_{$c}"])) {
            update_post_meta($post_id, $use_key, '1');
        } else {
            delete_post_meta($post_id, $use_key);
        }
    }

    // Show Plans (Select: '', '1', '0')
    if (isset($_POST['wpc_list_show_plans'])) {
        update_post_meta($post_id, '_wpc_list_show_plans', sanitize_text_field($_POST['wpc_list_show_plans']));
    }
    
    // --- PRICING TABLE VISUAL STYLE OVERRIDES ---
    
    // PT Color fields with override toggles
    $pt_color_fields = ['pt_header_bg', 'pt_header_text', 'pt_btn_bg', 'pt_btn_text'];
    foreach ($pt_color_fields as $field) {
        $key = "_wpc_list_{$field}";
        $use_key = "_wpc_list_use_{$field}";
        
        if (isset($_POST["wpc_list_{$field}"])) {
            update_post_meta($post_id, $key, sanitize_hex_color($_POST["wpc_list_{$field}"]));
        }
        
        if (isset($_POST["wpc_list_use_{$field}"])) {
            update_post_meta($post_id, $use_key, '1');
        } else {
            delete_post_meta($post_id, $use_key);
        }
    }
    
    // PT Button Position fields (select dropdowns)
    if (isset($_POST['wpc_list_pt_btn_pos_table'])) {
        update_post_meta($post_id, '_wpc_list_pt_btn_pos_table', sanitize_text_field($_POST['wpc_list_pt_btn_pos_table']));
    }
    if (isset($_POST['wpc_list_pt_btn_pos_popup'])) {
        update_post_meta($post_id, '_wpc_list_pt_btn_pos_popup', sanitize_text_field($_POST['wpc_list_pt_btn_pos_popup']));
    }
}
add_action( 'save_post_comparison_list', 'wpc_save_list_meta' );

/**
 * Add Shortcode Column to Admin List
 */
function wpc_list_columns( $columns ) {
    $columns['shortcode'] = __( 'Shortcode', 'wp-comparison-builder' );
    return $columns;
}
add_filter( 'manage_comparison_list_posts_columns', 'wpc_list_columns' );

function wpc_list_custom_column( $column, $post_id ) {
    if ( $column === 'shortcode' ) {
        echo '<code style="user-select: all;">[wpc_list id="' . $post_id . '"]</code>';
    }
}
add_action( 'manage_comparison_list_posts_custom_column', 'wpc_list_custom_column', 10, 2 );
