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

    $schema_ids = get_post_meta( $post->ID, '_wpc_list_schema_ids', true ) ?: [];

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

    $schema_desc = get_post_meta( $post->ID, '_wpc_list_schema_desc', true );

    // List Source Type
    $source_type = get_post_meta( $post->ID, '_wpc_list_source_type', true ) ?: 'item';

    // Get Terms for filters configuration
    $all_cats = get_terms( array( 'taxonomy' => 'comparison_category', 'hide_empty' => false ) );
    $all_feats = get_terms( array( 'taxonomy' => 'comparison_feature', 'hide_empty' => false ) );

    // Get Tool Terms (New)
    $all_tool_cats = get_terms( array( 'taxonomy' => 'tool_category', 'hide_empty' => false ) );
    $all_tool_tags = get_terms( array( 'taxonomy' => 'tool_tag', 'hide_empty' => false ) );
    
    // Get filter values
    $filter_tool_cats = get_post_meta( $post->ID, '_wpc_list_filter_tool_cats', true ) ?: [];
    $filter_tool_tags = get_post_meta( $post->ID, '_wpc_list_filter_tool_tags', true ) ?: [];

    // Determine Post Types
    $query_post_types = array();
    if ( $source_type === 'item' || $source_type === 'both' ) $query_post_types[] = 'comparison_item';
    if ( $source_type === 'tool' || $source_type === 'both' ) $query_post_types[] = 'comparison_tool';
    if ( empty( $query_post_types ) ) $query_post_types = array( 'comparison_item' );

    // Get all comparison items/tools
    $all_items = get_posts( array(
        'post_type' => $query_post_types,
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
        <style>
            .wpc-tab-nav { border-bottom: 1px solid #c3c4c7; margin: 0 0 20px 0; padding: 0; list-style: none; display: flex; gap: 5px; }
            .wpc-tab-nav li { margin: 0; }
            .wpc-tab-nav li a { display: block; padding: 10px 15px; text-decoration: none; border: 1px solid transparent; border-bottom: none; color: #50575e; font-weight: 600; background: #eaeaea; border-radius: 4px 4px 0 0; }
            .wpc-tab-nav li.active a { border-color: #c3c4c7; border-bottom-color: #f0f0f1; background: #f0f0f1; color: #1d2327; }
            .wpc-tab-content { display: none; padding: 10px 0; }
            .wpc-tab-content.active { display: block; }
            .wpc-field-group { margin-bottom: 20px; padding: 15px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; }
            .wpc-field-label { font-weight: bold; display: block; margin-bottom: 5px; }
            .wpc-flex-row { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 15px; }
            .wpc-flex-item { flex: 1; min-width: 200px; }
        </style>
        <script>
            jQuery(document).ready(function($) {
                // Tab Switching Logic
                $('.wpc-tab-nav a').on('click', function(e) {
                    e.preventDefault();
                    var target = $(this).attr('href');
                    $('.wpc-tab-nav li').removeClass('active');
                    $(this).parent().addClass('active');
                    $('.wpc-tab-content').removeClass('active');
                    $(target).addClass('active');
                });

                // Dynamic Field Visibility
                function updateVisibility() {
                    // Master Comparison Toggle
                    var masterOn = $('input[name="wpc_list_enable_comparison"]').is(':checked');
                    $('.wpc-comparison-options').toggle(masterOn);
                    
                    // Search Visibility Toggle
                    var searchOpt = $('select[name="wpc_list_show_search_opt"]').val();
                    var searchVisible = (searchOpt === 'show' || searchOpt === 'default');
                    $('select[name="wpc_list_search_type"]').closest('.wpc-flex-item').toggle(searchVisible);

                    // Filter Visibility Toggle
                    var filterOpt = $('select[name="wpc_list_show_filters_opt"]').val();
                    var filterVisible = (filterOpt === 'show' || filterOpt === 'default');
                    $('select[name="wpc_list_filter_layout"]').closest('.wpc-flex-item').toggle(filterVisible);
                    // Targeted hide of the Custom Filters group (which is now in General)
                    $('.wpc-custom-filters-group').toggle(filterVisible); 
                }

                // Delegated Event Listeners for Robustness
                $(document).on('change', 'input[name="wpc_list_enable_comparison"], select[name="wpc_list_show_search_opt"], select[name="wpc_list_show_filters_opt"]', function() {
                    updateVisibility();
                });

                // Initial run
                updateVisibility();
            }); // Close document.ready
        </script>

        <ul class="wpc-tab-nav">
            <li class="active"><a href="#wpc-tab-general">General Items</a></li>
            <li><a href="#wpc-tab-layout">Layout & Style</a></li>
            <li><a href="#wpc-tab-text">Language & Labels</a></li>
            <li><a href="#wpc-tab-comparison">Comparison & Actions</a></li>
            <li><a href="#wpc-tab-features">Features</a></li>
        </ul>

        <!-- TAB 1: GENERAL -->
        <div id="wpc-tab-general" class="wpc-tab-content active">
            
            <!-- Source Selection -->
            <div style="margin-bottom: 20px; padding: 15px; background: #f0f6fc; border: 1px solid #cce5ff; border-radius: 4px;">
                <label class="wpc-label" style="font-weight: bold; margin-bottom: 5px; display: block;">List Source Type</label>
                <?php 
                $tools_module_enabled = get_option('wpc_enable_tools_module') === '1';
                ?>
                <select name="wpc_list_source_type" id="wpc_list_source_type" class="wpc-input" style="max-width: 300px;">
                    <option value="item" <?php selected( $source_type, 'item' ); ?>>Comparison Items Only</option>
                    <?php if ($tools_module_enabled) : ?>
                    <option value="tool" <?php selected( $source_type, 'tool' ); ?>>Recommended Tools Only</option>
                    <option value="both" <?php selected( $source_type, 'both' ); ?>>Both Items & Tools</option>
                    <?php endif; ?>
                </select>
                <p class="description">Select what type of content to include in this list. Click "Update" to refresh the list below.</p>
            </div>

            <p class="description">
                <?php _e( 'Select items to include. <strong>Drag and drop rows to reorder them (selected items first).</strong>', 'wp-comparison-builder' ); ?>
            </p>
            
            <!-- Search Box -->
            <input type="text" id="wpc-list-search" placeholder="Search items..." style="width: 100%; margin-bottom: 10px; padding: 8px; font-size: 14px; border: 1px solid #ddd; border-radius: 4px;" />

            <div style="background: #fff; border: 1px solid #c3c4c7; max-height: 500px; overflow-y: auto;">
                <table class="widefat fixed striped" id="wpc-items-table">
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
                            <th style="width: 80px; text-align: center;"><?php _e( 'Schema?', 'wp-comparison-builder' ); ?></th>
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
                                        <input type="checkbox" name="item_ids[]" value="<?php echo esc_attr( $item->ID ); ?>" class="wpc-item-select" <?php checked( $is_selected ); ?> />
                                    </td>
                                    <td><strong><?php echo esc_html( $item->post_title ); ?></strong></td>
                                    <td style="text-align: center;">
                                        <input type="checkbox" name="featured_ids[]" value="<?php echo esc_attr( $item->ID ); ?>" <?php checked( $is_featured ); ?> />
                                    </td>
                                    <td style="text-align: center;">
                                        <?php $is_schema = in_array( $item->ID, $schema_ids ); ?>
                                        <input type="checkbox" name="schema_ids[]" value="<?php echo esc_attr( $item->ID ); ?>" <?php checked( $is_schema ); ?> title="Include this item in JSON-LD Schema (ItemList)" />
                                    </td>
                                    <td>
                                        <input type="text" name="badge_texts[<?php echo esc_attr( $item->ID ); ?>]" value="<?php echo esc_attr( $badge_text ); ?>" placeholder="e.g., Top Choice" style="width: 100%; border: 1px solid #ddd; border-radius: 3px;" />
                                    </td>
                                    <td>
                                        <input type="color" name="badge_colors[<?php echo esc_attr( $item->ID ); ?>]" value="<?php echo esc_attr( $badge_color ); ?>" style="width: 60px; height: 32px; border: 1px solid #ddd; border-radius: 3px; cursor: pointer;" />
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="6"><?php _e( 'No items found.', 'wp-comparison-builder' ); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Custom Filters (Moved to General) -->
            <div class="wpc-field-group wpc-custom-filters-group">
                <label class="wpc-field-label">Custom Filter Terms (Optional)</label>
                <p class="description" style="margin-bottom: 10px;">Select terms to show as filter buttons above the list.</p>
                
                <!-- ITEM Filters -->
                <div id="wpc-item-filters" style="display: <?php echo ($source_type === 'item' || $source_type === 'both') ? 'block' : 'none'; ?>;">
                    <h4 style="margin: 5px 0; color: #666;">Comparison Item Filters</h4>
                    <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                        <div style="flex: 1; border: 1px solid #ddd; padding: 10px; max-height: 150px; overflow-y: auto;">
                            <strong>Item Categories</strong>
                            <?php if ( ! empty( $all_cats ) && ! is_wp_error( $all_cats ) ) : ?>
                                <?php foreach ( $all_cats as $cat ) : ?>
                                    <label style="display:block;"><input type="checkbox" name="wpc_list_filter_cats[]" value="<?php echo esc_attr( $cat->term_id ); ?>" <?php checked( in_array( $cat->term_id, $filter_cats ) ); ?> /> <?php echo esc_html( $cat->name ); ?></label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div style="flex: 1; border: 1px solid #ddd; padding: 10px; max-height: 150px; overflow-y: auto;">
                            <strong>Item Features (Tags)</strong>
                            <?php if ( ! empty( $all_feats ) && ! is_wp_error( $all_feats ) ) : ?>
                                <?php foreach ( $all_feats as $feat ) : ?>
                                    <label style="display:block;"><input type="checkbox" name="wpc_list_filter_feats[]" value="<?php echo esc_attr( $feat->term_id ); ?>" <?php checked( in_array( $feat->term_id, $filter_feats ) ); ?> /> <?php echo esc_html( $feat->name ); ?></label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- TOOL Filters -->
                <div id="wpc-tool-filters" style="display: <?php echo ($tools_module_enabled && ($source_type === 'tool' || $source_type === 'both')) ? 'block' : 'none'; ?>;">
                    <h4 style="margin: 5px 0; color: #666;">Tool Filters</h4>
                     <div style="display: flex; gap: 20px;">
                        <div style="flex: 1; border: 1px solid #ddd; padding: 10px; max-height: 150px; overflow-y: auto;">
                            <strong>Tool Categories</strong>
                            <?php if ( ! empty( $all_tool_cats ) && ! is_wp_error( $all_tool_cats ) ) : ?>
                                <?php foreach ( $all_tool_cats as $cat ) : ?>
                                    <label style="display:block;"><input type="checkbox" name="wpc_list_filter_tool_cats[]" value="<?php echo esc_attr( $cat->term_id ); ?>" <?php checked( in_array( $cat->term_id, $filter_tool_cats ) ); ?> /> <?php echo esc_html( $cat->name ); ?></label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div style="flex: 1; border: 1px solid #ddd; padding: 10px; max-height: 150px; overflow-y: auto;">
                            <strong>Tool Tags</strong>
                            <?php if ( ! empty( $all_tool_tags ) && ! is_wp_error( $all_tool_tags ) ) : ?>
                                <?php foreach ( $all_tool_tags as $tag ) : ?>
                                    <label style="display:block;"><input type="checkbox" name="wpc_list_filter_tool_tags[]" value="<?php echo esc_attr( $tag->term_id ); ?>" <?php checked( in_array( $tag->term_id, $filter_tool_tags ) ); ?> /> <?php echo esc_html( $tag->name ); ?></label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <script>
                jQuery(document).ready(function($) {
                    // Search Filter for Items Table
                    $('#wpc-list-search').on('keyup', function() {
                        var value = $(this).val().toLowerCase();
                        $('#wpc-items-table tbody tr').filter(function() {
                            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                        });
                    });

                    // Toggling Filter Sections based on Source Type
                    $('#wpc_list_source_type').on('change', function() {
                        var type = $(this).val();
                        if (type === 'item') {
                            $('#wpc-item-filters').show();
                            $('#wpc-tool-filters').hide();
                        } else if (type === 'tool') {
                            $('#wpc-item-filters').hide();
                            $('#wpc-tool-filters').show();
                        } else {
                            $('#wpc-tool-filters').show();
                        }
                    });
                });
                </script>

            </div>

            <div class="wpc-field-group">
                <label class="wpc-field-label">List SEO & Schema</label>
                <div style="margin-bottom: 15px;">
                    <label><strong>Schema Description (ItemList):</strong></label>
                    <textarea name="wpc_list_schema_desc" style="width: 100%; margin-top: 5px;" rows="3" placeholder="Enter a unique description for the ItemList schema (search snippets). If empty, the list title will be used."><?php echo esc_textarea( $schema_desc ); ?></textarea>
                </div>
                
                <label class="wpc-field-label">List Functionality</label>
                <div class="wpc-flex-row">
                    <div class="wpc-flex-item">
                        <label><strong>Limit Items:</strong></label>
                        <input type="number" name="wpc_list_limit" value="<?php echo $limit ? esc_attr( $limit ) : ''; ?>" style="width: 80px;" />
                        <span class="description">Leave empty for no limit.</span>
                    </div>
                    <div class="wpc-flex-item">
                        <label><strong>Initial Visible Count:</strong></label>
                        <input type="number" name="wpc_list_initial_visible" value="<?php echo esc_attr( $initial_visible_count ); ?>" min="3" max="100" style="width: 80px;" />
                    </div>
                    <div class="wpc-flex-item">
                        <label>
                            <input type="checkbox" name="wpc_list_show_all_enabled" value="1" <?php checked( $show_all_enabled, '1' ); ?> />
                            Show "Show All Items" card
                        </label>
                    </div>
                </div>
                
                <div style="background: #f0f0f1; border: 1px solid #ccd0d4; padding: 10px;">
                    <strong>Shortcode:</strong>
                    <code style="font-size: 1.2em; margin-left: 10px; user-select: all;">[wpc_list id="<?php echo $post->ID; ?>"]</code>
                </div>
            </div>
        </div>

        <!-- TAB 2: LAYOUT & STYLE -->
        <div id="wpc-tab-layout" class="wpc-tab-content">
            <div class="wpc-field-group">
                <h3 style="margin-top:0;">Appearance</h3>
                
                <div class="wpc-flex-row">
                    <!-- List Style -->
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">List Style</label>
                        <?php $list_style = get_post_meta($post->ID, '_wpc_list_style', true) ?: 'default'; ?>
                        <select name="wpc_list_style" style="width: 100%;">
                            <option value="default" <?php selected( $list_style, 'default' ); ?>>Default (As Global)</option>
                            <option value="grid" <?php selected( $list_style, 'grid' ); ?>>Grid (Cards)</option>
                            <option value="list" <?php selected( $list_style, 'list' ); ?>>List (Horizontal)</option>
                            <option value="detailed" <?php selected( $list_style, 'detailed' ); ?>>Detailed (Row)</option>
                            <option value="compact" <?php selected( $list_style, 'compact' ); ?>>Compact (Minimal)</option>
                        </select>
                    </div>

                    <!-- Table Button Position -->
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">Table Button Position</label>
                        <?php $btn_pos_table = get_post_meta($post->ID, '_wpc_list_pt_btn_pos_table', true) ?: 'default'; ?>
                        <select name="wpc_list_pt_btn_pos_table" style="width: 100%;">
                            <option value="default" <?php selected( $btn_pos_table, 'default' ); ?>>Default (As Global)</option>
                            <option value="after_price" <?php selected( $btn_pos_table, 'after_price' ); ?>>After Pricing</option>
                            <option value="bottom" <?php selected( $btn_pos_table, 'bottom' ); ?>>Bottom (After Features)</option>
                        </select>
                    </div>

                    <!-- Popup Button Position -->
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">Popup Button Position</label>
                        <?php $btn_pos_popup = get_post_meta($post->ID, '_wpc_list_pt_btn_pos_popup', true) ?: 'default'; ?>
                        <select name="wpc_list_pt_btn_pos_popup" style="width: 100%;">
                            <option value="default" <?php selected( $btn_pos_popup, 'default' ); ?>>Default (As Global)</option>
                            <option value="after_price" <?php selected( $btn_pos_popup, 'after_price' ); ?>>After Pricing</option>
                            <option value="bottom" <?php selected( $btn_pos_popup, 'bottom' ); ?>>Bottom (After Features)</option>
                        </select>
                    </div>


                    <!-- Badge Style -->
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">Badge Style</label>
                        <?php $badge_style = get_post_meta($post->ID, '_wpc_list_badge_style', true) ?: 'floating'; ?>
                        <select name="wpc_list_badge_style" style="width: 100%;">
                            <option value="floating" <?php selected( $badge_style, 'floating' ); ?>>Floating (Pill)</option>
                            <option value="flush" <?php selected( $badge_style, 'flush' ); ?>>Flush (Corner Ribbon)</option>
                        </select>
                    </div>

                    <!-- Element Visibility -->
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">Visible Elements</label>
                        <?php 
                        $show_rating = get_post_meta($post->ID, '_wpc_list_show_rating', true); if($show_rating === '') $show_rating = '1';
                        $show_price = get_post_meta($post->ID, '_wpc_list_show_price', true); if($show_price === '') $show_price = '1';
                        ?>
                        <label style="display: block;"><input type="checkbox" name="wpc_list_show_rating" value="1" <?php checked('1', $show_rating); ?> /> Show Rating</label>
                        <label style="display: block;"><input type="checkbox" name="wpc_list_show_price" value="1" <?php checked('1', $show_price); ?> /> Show Price</label>
                    </div>
                </div>

                <!-- Pricing Table & Popup Button Settings -->
                <div class="wpc-field-group" style="margin-top: 15px;">
                    <h3 style="margin-top:0;">Pricing Table & Popup Settings</h3>
                    <p class="description">Control button visibility and features column for pricing tables and popups in this list.</p>
                    <div class="wpc-flex-row">
                        <div class="wpc-flex-item">
                            <label class="wpc-field-label">"Select" Buttons</label>
                            <?php 
                            $show_select_table = get_post_meta($post->ID, '_wpc_list_show_select_table', true); if($show_select_table === '') $show_select_table = '1';
                            $show_select_popup = get_post_meta($post->ID, '_wpc_list_show_select_popup', true); if($show_select_popup === '') $show_select_popup = '1';
                            ?>
                            <label style="display: block;"><input type="checkbox" name="wpc_list_show_select_table" value="1" <?php checked('1', $show_select_table); ?> /> Show in Table</label>
                            <label style="display: block;"><input type="checkbox" name="wpc_list_show_select_popup" value="1" <?php checked('1', $show_select_popup); ?> /> Show in Popup</label>
                        </div>
                        <div class="wpc-flex-item">
                            <label class="wpc-field-label">Footer Button</label>
                            <?php 
                            $show_footer_table = get_post_meta($post->ID, '_wpc_list_show_footer_table', true); if($show_footer_table === '') $show_footer_table = '1';
                            $show_footer_popup = get_post_meta($post->ID, '_wpc_list_show_footer_popup', true); if($show_footer_popup === '') $show_footer_popup = '1';
                            ?>
                            <label style="display: block;"><input type="checkbox" name="wpc_list_show_footer_table" value="1" <?php checked('1', $show_footer_table); ?> /> Show in Table</label>
                            <label style="display: block;"><input type="checkbox" name="wpc_list_show_footer_popup" value="1" <?php checked('1', $show_footer_popup); ?> /> Show in Popup</label>
                        </div>
                        <div class="wpc-flex-item">
                            <label class="wpc-field-label">Features Column</label>
                            <?php 
                            $hide_features = get_post_meta($post->ID, '_wpc_list_hide_features', true);
                            ?>
                            <label style="display: block;"><input type="checkbox" name="wpc_list_hide_features" value="1" <?php checked('1', $hide_features); ?> /> Hide "Features" Column</label>
                        </div>
                    </div>
                    <button type="button" class="button button-small" style="margin-top: 10px;" onclick="wpcResetPricingSettings()">🔄 Reset to Defaults</button>
                </div>
                <script>
                function wpcResetPricingSettings() {
                    jQuery('[name="wpc_list_show_select_table"]').prop('checked', true);
                    jQuery('[name="wpc_list_show_select_popup"]').prop('checked', true);
                    jQuery('[name="wpc_list_show_footer_table"]').prop('checked', true);
                    jQuery('[name="wpc_list_show_footer_popup"]').prop('checked', true);
                    jQuery('[name="wpc_list_hide_features"]').prop('checked', false);
                    jQuery('[name="wpc_list_pt_btn_pos_table"]').val('default');
                    jQuery('[name="wpc_list_pt_btn_pos_popup"]').val('default');
                }
                </script>

                <div class="wpc-field-group">
                    <h3 style="margin-top:0;">Link Target Behavior (New Tab vs Same Tab)</h3>
                    <div class="wpc-flex-row">
                        <!-- Details Link Target -->
                        <div class="wpc-flex-item">
                            <label class="wpc-field-label">Details / Comparison Button</label>
                            <?php $target_details = get_post_meta($post->ID, '_wpc_list_target_details', true) ?: 'default'; ?>
                            <select name="wpc_list_target_details" style="width: 100%;">
                                <option value="default" <?php selected( $target_details, 'default' ); ?>>Default</option>
                                <option value="_blank" <?php selected( $target_details, '_blank' ); ?>>New Tab</option>
                                <option value="_self" <?php selected( $target_details, '_self' ); ?>>Same Tab</option>
                            </select>
                        </div>
                        <!-- Direct Link Target -->
                        <div class="wpc-flex-item">
                            <label class="wpc-field-label">Direct / Non-Comparison Button</label>
                            <?php $target_direct = get_post_meta($post->ID, '_wpc_list_target_direct', true) ?: 'default'; ?>
                            <select name="wpc_list_target_direct" style="width: 100%;">
                                <option value="default" <?php selected( $target_direct, 'default' ); ?>>Default</option>
                                <option value="_blank" <?php selected( $target_direct, '_blank' ); ?>>New Tab</option>
                                <option value="_self" <?php selected( $target_direct, '_self' ); ?>>Same Tab</option>
                            </select>
                        </div>
                        <!-- Pricing Plan Target -->
                        <div class="wpc-flex-item">
                            <label class="wpc-field-label">Pricing Plan Buttons</label>
                            <?php $target_pricing = get_post_meta($post->ID, '_wpc_list_target_pricing', true) ?: 'default'; ?>
                            <select name="wpc_list_target_pricing" style="width: 100%;">
                                <option value="default" <?php selected( $target_pricing, 'default' ); ?>>Default</option>
                                <option value="_blank" <?php selected( $target_pricing, '_blank' ); ?>>New Tab</option>
                                <option value="_self" <?php selected( $target_pricing, '_self' ); ?>>Same Tab</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="wpc-flex-row">
                     <!-- Filter Layout -->
                     <div class="wpc-flex-item">
                        <label class="wpc-field-label">Filter Layout</label>
                        <select name="wpc_list_filter_layout" style="width: 100%;">
                            <option value="default" <?php selected( $filter_layout, 'default' ); ?>>Default (As Global)</option>
                            <option value="top" <?php selected( $filter_layout, 'top' ); ?>>Horizontal (Top)</option>
                            <option value="sidebar" <?php selected( $filter_layout, 'sidebar' ); ?>>Vertical (Sidebar)</option>
                        </select>
                    </div>
                    <!-- Show Filters -->
                    <div class="wpc-flex-item">
                         <label class="wpc-field-label">Filter Visibility</label>
                         <?php $show_filters_opt = get_post_meta($post->ID, '_wpc_list_show_filters_opt', true) ?: 'default'; ?>
                         <select name="wpc_list_show_filters_opt" style="width: 100%;">
                            <option value="default" <?php selected( $show_filters_opt, 'default' ); ?>>Default</option>
                            <option value="show" <?php selected( $show_filters_opt, 'show' ); ?>>Show</option>
                            <option value="hide" <?php selected( $show_filters_opt, 'hide' ); ?>>Hide</option>
                         </select>
                    </div>
                </div>
                 <div class="wpc-flex-row">
                     <!-- Show Search -->
                    <div class="wpc-flex-item">
                         <label class="wpc-field-label">Search Visibility</label>
                         <?php $show_search_opt = get_post_meta($post->ID, '_wpc_list_show_search_opt', true) ?: 'default'; ?>
                         <select name="wpc_list_show_search_opt" style="width: 100%;">
                            <option value="default" <?php selected( $show_search_opt, 'default' ); ?>>Default</option>
                            <option value="show" <?php selected( $show_search_opt, 'show' ); ?>>Show</option>
                            <option value="hide" <?php selected( $show_search_opt, 'hide' ); ?>>Hide</option>
                         </select>
                    </div>
                    <!-- Search Type -->
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">Search Bar Type</label>
                        <?php $search_type = get_post_meta($post->ID, '_wpc_list_search_type', true) ?: 'default'; ?>
                        <select name="wpc_list_search_type" style="width: 100%;">
                            <option value="default" <?php selected( $search_type, 'default' ); ?>>Default</option>
                            <option value="text" <?php selected( $search_type, 'text' ); ?>>Standard Text Input</option>
                            <option value="combobox" <?php selected( $search_type, 'combobox' ); ?>>Advanced Combobox</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Language & Labels TAB -->
        </div>

        <div id="wpc-tab-text" class="wpc-tab-content">
            <div class="wpc-field-group">
                <h3 style="margin-top:0;">Global UI Labels</h3>
                <div class="wpc-flex-row">
                    <!-- Search Placeholder -->
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">Search Placeholder</label>
                        <input type="text" name="wpc_list_txt_search_ph" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_search_ph', true) ?: 'Search & Select...' ); ?>" style="width: 100%;" />
                    </div>
                </div>
                <div class="wpc-flex-row">
                    <!-- Active Filters -->
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Active filters:" Label</label>
                        <input type="text" name="wpc_list_txt_active_filt" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_active_filt', true) ?: 'Active filters:' ); ?>" style="width: 100%;" />
                    </div>
                    <!-- Clear All -->
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Clear all" Label</label>
                        <input type="text" name="wpc_list_txt_clear_all" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_clear_all', true) ?: 'Clear all' ); ?>" style="width: 100%;" />
                    </div>
                </div>
                 <div class="wpc-flex-row">
                    <!-- No Results -->
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"No items match" Message</label>
                        <input type="text" name="wpc_list_txt_no_results" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_no_results', true) ?: 'No items match your filters.' ); ?>" style="width: 100%;" />
                    </div>
                    <!-- Selected: -->
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Selected:" Label</label>
                        <input type="text" name="wpc_list_txt_selected" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_selected', true) ?: 'Selected:' ); ?>" style="width: 100%;" />
                    </div>
                </div>
            </div>

            <div class="wpc-field-group">
                <h3 style="margin-top:0;">Sorting Options</h3>
                <div class="wpc-flex-row">
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">Default Sort</label>
                        <input type="text" name="wpc_list_txt_sort_def" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_sort_def', true) ?: 'Sort: Default' ); ?>" style="width: 100%;" />
                    </div>
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">Name (A-Z)</label>
                        <input type="text" name="wpc_list_txt_sort_asc" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_sort_asc', true) ?: 'Name (A-Z)' ); ?>" style="width: 100%;" />
                    </div>
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">Name (Z-A)</label>
                        <input type="text" name="wpc_list_txt_sort_desc" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_sort_desc', true) ?: 'Name (Z-A)' ); ?>" style="width: 100%;" />
                    </div>
                </div>
                <div class="wpc-flex-row">
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">Highest Rated</label>
                        <input type="text" name="wpc_list_txt_sort_rating" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_sort_rating', true) ?: 'Highest Rated' ); ?>" style="width: 100%;" />
                    </div>
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">Lowest Price</label>
                        <input type="text" name="wpc_list_txt_sort_price" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_sort_price', true) ?: 'Lowest Price' ); ?>" style="width: 100%;" />
                    </div>
                </div>
            </div>

            <div class="wpc-field-group">
                <h3 style="margin-top:0;">Card & Feature Labels</h3>
                <div class="wpc-flex-row">
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Get Coupon:" Label</label>
                        <input type="text" name="wpc_list_txt_get_coupon" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_get_coupon', true) ?: 'Get Coupon:' ); ?>" style="width: 100%;" />
                    </div>
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Featured" Badge Text</label>
                        <input type="text" name="wpc_list_txt_featured" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_featured', true) ?: 'Featured' ); ?>" style="width: 100%;" />
                         <span class="description">Fallback if no specific badge set.</span>
                    </div>
                </div>
                <div class="wpc-flex-row">
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Products" Suffix</label>
                        <input type="text" name="wpc_list_txt_feat_prod" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_feat_prod', true) ?: 'Products' ); ?>" style="width: 100%;" />
                    </div>
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Trans. Fees" Suffix</label>
                        <input type="text" name="wpc_list_txt_feat_fees" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_feat_fees', true) ?: 'Trans. Fees' ); ?>" style="width: 100%;" />
                    </div>
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Support" Suffix</label>
                        <input type="text" name="wpc_list_txt_feat_supp" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_feat_supp', true) ?: 'Support' ); ?>" style="width: 100%;" />
                    </div>
                </div>
                <div class="wpc-flex-row">
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Sales Channels" Label</label>
                        <input type="text" name="wpc_list_txt_feat_channels" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_feat_channels', true) ?: 'Sales Channels' ); ?>" style="width: 100%;" />
                    </div>
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Free SSL" Label</label>
                        <input type="text" name="wpc_list_txt_feat_ssl" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_feat_ssl', true) ?: 'Free SSL' ); ?>" style="width: 100%;" />
                    </div>
                </div>
                <div class="wpc-flex-row">
                     <div class="wpc-flex-item">
                        <label class="wpc-field-label">Category Label</label>
                        <input type="text" name="wpc_list_cat_label" class="wpc-input-cat-label" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_cat_label', true) ?: 'Filter by Category' ); ?>" style="width: 100%;" />
                    </div>
                     <div class="wpc-flex-item">
                        <label class="wpc-field-label">Features Label</label>
                        <input type="text" name="wpc_list_feat_label" class="wpc-input-feat-label" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_feat_label', true) ?: 'Features' ); ?>" style="width: 100%;" />
                    </div>
                </div>
            </div>

            <div class="wpc-field-group">
                <h3 style="margin-top:0;">Comparison Logic Labels</h3>
                <div class="wpc-flex-row">
                    <!-- Select to Compare Text -->
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Select to Compare" Text</label>
                        <input type="text" name="wpc_list_txt_compare" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_compare', true) ?: 'Select to Compare' ); ?>" style="width: 100%;" />
                    </div>
                    <!-- Copied! Text -->
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Copied!" Text</label>
                        <input type="text" name="wpc_list_txt_copied" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_copied', true) ?: 'Copied!' ); ?>" style="width: 100%;" />
                    </div>
                </div>
                 <div class="wpc-flex-row">
                    <!-- View Details Text -->
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"View Details" Text</label>
                        <input type="text" name="wpc_list_txt_view" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_view', true) ?: 'View Details' ); ?>" style="width: 100%;" />
                    </div>
                    <!-- Visit Site Text -->
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Visit Site" Text</label>
                        <input type="text" name="wpc_list_txt_visit" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_visit', true) ?: 'Visit Site' ); ?>" style="width: 100%;" />
                    </div>
                </div>
                <div class="wpc-flex-row">
                    <!-- Compare Button Text -->
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Compare (X) Items" Text</label>
                        <input type="text" name="wpc_list_txt_compare_btn" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_compare_btn', true) ?: 'Compare (%s) Items' ); ?>" style="width: 100%;" />
                    </div>
                </div>
                <div class="wpc-flex-row">
                    <!-- Compare Now Text -->
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Compare Now" Text</label>
                        <input type="text" name="wpc_list_txt_compare_now" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_compare_now', true) ?: 'Compare Now' ); ?>" style="width: 100%;" />
                    </div>
                    <!-- Visit Platform Text -->
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Visit Platform" Text</label>
                        <input type="text" name="wpc_list_txt_visit_plat" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_visit_plat', true) ?: 'Visit %s' ); ?>" style="width: 100%;" />
                    </div>
                    <!-- Comparison Header -->
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Detailed Comparison" Header</label>
                        <input type="text" name="wpc_list_txt_comp_header" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_comp_header', true) ?: 'Detailed Comparison' ); ?>" style="width: 100%;" />
                    </div>
                </div>
            <!-- Comparison Table Headers -->
            <div class="wpc-field-group">
                <h3 style="margin-top:0;">Comparison Table Headers</h3>
                <div class="wpc-flex-row">
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Feature" Column Header</label>
                        <input type="text" name="wpc_list_txt_feat_header" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_feat_header', true) ?: 'Feature' ); ?>" style="width: 100%;" />
                    </div>
                     <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Price" Row Header</label>
                        <input type="text" name="wpc_list_txt_price" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_price', true) ?: 'Price' ); ?>" style="width: 100%;" />
                    </div>
                </div>
                <div class="wpc-flex-row">
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Pros" Row Header</label>
                        <input type="text" name="wpc_list_txt_pros" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_pros', true) ?: 'Pros' ); ?>" style="width: 100%;" />
                    </div>
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Cons" Row Header</label>
                        <input type="text" name="wpc_list_txt_cons" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_cons', true) ?: 'Cons' ); ?>" style="width: 100%;" />
                    </div>
                </div>
                 <div class="wpc-flex-row">
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Rating" Row Header</label>
                        <input type="text" name="wpc_list_txt_rating" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_rating', true) ?: 'Rating' ); ?>" style="width: 100%;" />
                    </div>
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">Month Suffix (/mo)</label>
                        <input type="text" name="wpc_list_txt_mo_suffix" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_mo_suffix', true) ?: '/mo' ); ?>" style="width: 100%;" />
                    </div>
                </div>
            </div>

            <!-- Pricing Table & Popup Labels -->
            <div class="wpc-field-group">
                <h3 style="margin-top:0;">Pricing Table & Popup Labels</h3>
                <div class="wpc-flex-row">
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Select Plan" Button Text</label>
                        <input type="text" name="wpc_list_txt_select_plan" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_select_plan', true) ?: 'Select' ); ?>" style="width: 100%;" />
                    </div>
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Plan" Column Header</label>
                        <input type="text" name="wpc_list_txt_pt_plan" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_pt_plan', true) ?: 'Plan' ); ?>" style="width: 100%;" />
                    </div>
                </div>
                <div class="wpc-flex-row">
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Price" Column Header (Table)</label>
                        <input type="text" name="wpc_list_txt_pt_price" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_pt_price', true) ?: 'Price' ); ?>" style="width: 100%;" />
                    </div>
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Features" Column Header (Table)</label>
                        <input type="text" name="wpc_list_txt_pt_features" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_pt_features', true) ?: 'Features' ); ?>" style="width: 100%;" />
                    </div>
                </div>
            </div>
            
            <!-- Miscellaneous Labels -->
            <div class="wpc-field-group">
                <h3 style="margin-top:0;">Miscellaneous Labels</h3>
                <div class="wpc-flex-row">
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"No Items to Compare" Text</label>
                        <input type="text" name="wpc_list_txt_no_compare" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_no_compare', true) ?: 'Select up to 4 items to compare' ); ?>" style="width: 100%;" />
                    </div>
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Remove" Text</label>
                        <input type="text" name="wpc_list_txt_remove" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_remove', true) ?: 'Remove' ); ?>" style="width: 100%;" />
                    </div>
                </div>
                 <div class="wpc-flex-row">
                    <div class="wpc-flex-item">
                         <label class="wpc-field-label">"Logo" Fallback Text</label>
                         <input type="text" name="wpc_list_txt_logo" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_logo', true) ?: 'Logo' ); ?>" style="width: 100%;" />
                     </div>
                     <div class="wpc-flex-item">
                         <label class="wpc-field-label">"Analysis" Suffix</label>
                         <input type="text" name="wpc_list_txt_analysis" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_analysis', true) ?: '(Based on our analysis)' ); ?>" style="width: 100%;" />
                     </div>
                 </div>
                 <div class="wpc-flex-row">
                    <div class="wpc-flex-item">
                         <label class="wpc-field-label">"Starting Price" Label</label>
                         <input type="text" name="wpc_list_txt_start_price" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_start_price', true) ?: 'Starting Price' ); ?>" style="width: 100%;" />
                     </div>
                     <div class="wpc-flex-item">
                         <label class="wpc-field-label">"Dashboard Preview" Label</label>
                         <input type="text" name="wpc_list_txt_dash_prev" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_dash_prev', true) ?: 'Dashboard Preview' ); ?>" style="width: 100%;" />
                     </div>
                 </div>
            </div>
            
            <div class="wpc-field-group">
                <h3 style="margin-top:0;">Filter & Search Internal Labels</h3>
                <div class="wpc-flex-row">
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Reset Filters" Text</label>
                        <input type="text" name="wpc_list_txt_reset_filt" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_reset_filt', true) ?: 'Reset Filters' ); ?>" style="width: 100%;" />
                    </div>
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Select %s" Format</label>
                        <input type="text" name="wpc_list_txt_select_fmt" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_select_fmt', true) ?: 'Select %s' ); ?>" style="width: 100%;" />
                    </div>
                </div>
                <div class="wpc-flex-row">
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Clear" Text</label>
                        <input type="text" name="wpc_list_txt_clear" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_clear', true) ?: 'Clear' ); ?>" style="width: 100%;" />
                    </div>
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Select provider..." Placeholder</label>
                        <input type="text" name="wpc_list_txt_sel_prov" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_sel_prov', true) ?: 'Select provider...' ); ?>" style="width: 100%;" />
                    </div>
                </div>
                <div class="wpc-flex-row">
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"No item found." Text</label>
                        <input type="text" name="wpc_list_txt_no_item" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_no_item', true) ?: 'No item found.' ); ?>" style="width: 100%;" />
                    </div>
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"more" (e.g. +2 more)</label>
                        <input type="text" name="wpc_list_txt_more" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_more', true) ?: 'more' ); ?>" style="width: 100%;" />
                    </div>
                </div>
                <div class="wpc-flex-row">
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Show All Items" Card Title</label>
                        <input type="text" name="wpc_list_txt_show_all" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_show_all', true) ?: 'Show All Items' ); ?>" style="width: 100%;" />
                    </div>
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"Click to reveal X more" Text</label>
                        <input type="text" name="wpc_list_txt_reveal_more" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_reveal_more', true) ?: 'Click to reveal' ); ?>" style="width: 100%;" />
                    </div>
                    <div class="wpc-flex-item">
                        <label class="wpc-field-label">"No Logo" Fallback Text</label>
                        <input type="text" name="wpc_list_txt_no_logo" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_txt_no_logo', true) ?: 'No Logo' ); ?>" style="width: 100%;" />
                    </div>
                </div>
            </div>
            
            </div>

            <!-- Colors & Overrides -->

            <!-- Colors & Overrides -->
            <div class="wpc-field-group">
                <h3 style="margin-top:0;">Color & Visual Overrides</h3>
                <p class="description">Global overrides for this specific list.</p>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                     <div>
                        <label>Primary Color</label> <input type="color" name="wpc_list_primary_color" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_primary_color', true) ?: '#6366f1' ); ?>" />
                        <label><input type="checkbox" name="wpc_list_use_primary" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_primary', true), '1' ); ?> /> Apply</label>
                    </div>
                    <div>
                        <label>Accent Color</label> <input type="color" name="wpc_list_accent_color" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_accent_color', true) ?: '#0d9488' ); ?>" />
                        <label><input type="checkbox" name="wpc_list_use_accent" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_accent', true), '1' ); ?> /> Apply</label>
                    </div>
                    <div>
                        <label>Button Hover</label> <input type="color" name="wpc_list_hover_color" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_hover_color', true) ?: '#059669' ); ?>" />
                         <label><input type="checkbox" name="wpc_list_use_hover" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_hover', true), '1' ); ?> /> Apply</label>
                    </div>
                    <div>
                        <label>Header BG</label> <input type="color" name="wpc_list_pt_header_bg" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_pt_header_bg', true) ?: '#f8fafc' ); ?>" />
                         <label><input type="checkbox" name="wpc_list_use_pt_header_bg" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_pt_header_bg', true), '1' ); ?> /> Apply</label>
                    </div>
                </div>
                
                <!-- Comparison Table Colors -->
                <h4 style="margin-top: 20px; margin-bottom: 10px;">Comparison Table Colors</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="font-size: 11px; display: block;">Pros Background</label>
                        <div style="display:flex; align-items:center; gap:5px;">
                            <input type="color" name="wpc_list_color_pros_bg" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_color_pros_bg', true) ?: '#f0fdf4' ); ?>" />
                            <label><input type="checkbox" name="wpc_list_use_pros_bg" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_pros_bg', true), '1' ); ?> /> Set</label>
                        </div>
                    </div>
                    <div>
                        <label style="font-size: 11px; display: block;">Pros Text</label>
                        <div style="display:flex; align-items:center; gap:5px;">
                            <input type="color" name="wpc_list_color_pros_text" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_color_pros_text', true) ?: '#166534' ); ?>" />
                             <label><input type="checkbox" name="wpc_list_use_pros_text" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_pros_text', true), '1' ); ?> /> Set</label>
                        </div>
                    </div>
                    <div>
                        <label style="font-size: 11px; display: block;">Cons Background</label>
                        <div style="display:flex; align-items:center; gap:5px;">
                            <input type="color" name="wpc_list_color_cons_bg" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_color_cons_bg', true) ?: '#fef2f2' ); ?>" />
                             <label><input type="checkbox" name="wpc_list_use_cons_bg" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_cons_bg', true), '1' ); ?> /> Set</label>
                        </div>
                    </div>
                    <div>
                        <label style="font-size: 11px; display: block;">Cons Text</label>
                        <div style="display:flex; align-items:center; gap:5px;">
                            <input type="color" name="wpc_list_color_cons_text" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_color_cons_text', true) ?: '#991b1b' ); ?>" />
                             <label><input type="checkbox" name="wpc_list_use_cons_text" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_cons_text', true), '1' ); ?> /> Set</label>
                        </div>
                    </div>
                </div>
                
                <!-- Coupon Colors -->
                <h4 style="margin-top: 20px; margin-bottom: 10px;">Coupon Button Colors</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="font-size: 11px; display: block;">Background</label>
                        <div style="display:flex; align-items:center; gap:5px;">
                            <input type="color" name="wpc_list_color_coupon_bg" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_color_coupon_bg', true) ?: '#fef3c7' ); ?>" />
                             <label><input type="checkbox" name="wpc_list_use_coupon_bg" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_coupon_bg', true), '1' ); ?> /> Set</label>
                        </div>
                    </div>
                    <div>
                        <label style="font-size: 11px; display: block;">Text</label>
                        <div style="display:flex; align-items:center; gap:5px;">
                            <input type="color" name="wpc_list_color_coupon_text" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_color_coupon_text', true) ?: '#92400e' ); ?>" />
                             <label><input type="checkbox" name="wpc_list_use_coupon_text" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_coupon_text', true), '1' ); ?> /> Set</label>
                        </div>
                    </div>
                    <div>
                        <label style="font-size: 11px; display: block;">Hover</label>
                        <div style="display:flex; align-items:center; gap:5px;">
                            <input type="color" name="wpc_list_color_coupon_hover" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_color_coupon_hover', true) ?: '#fde68a' ); ?>" />
                             <label><input type="checkbox" name="wpc_list_use_coupon_hover" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_coupon_hover', true), '1' ); ?> /> Set</label>
                        </div>
                    </div>
                    <div>
                        <label style="font-size: 11px; display: block;">Copied State</label>
                        <div style="display:flex; align-items:center; gap:5px;">
                            <input type="color" name="wpc_list_color_copied" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_color_copied', true) ?: '#10b981' ); ?>" />
                             <label><input type="checkbox" name="wpc_list_use_copied" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_copied', true), '1' ); ?> /> Set</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: COMPARISON & ACTIONS -->
        <div id="wpc-tab-comparison" class="wpc-tab-content">
            <div class="wpc-field-group">
                <h3 style="margin-top:0;">Split Comparison Settings</h3>
                <?php 
                    // Retrieve Settings
                    $enable_comparison = get_post_meta( $post->ID, '_wpc_list_enable_comparison', true );
                    if ($enable_comparison === '') $enable_comparison = '1';

                    $show_checkboxes = get_post_meta($post->ID, '_wpc_list_show_checkboxes', true);
                    if ($show_checkboxes === '') $show_checkboxes = '1'; 
                    
                    $view_action = get_post_meta($post->ID, '_wpc_list_view_action', true) ?: 'popup'; 
                ?>

                <h3 style="margin-top:0;">Comparison Master Switch</h3>
                
                <div style="background: #fdfdfd; padding: 20px; border: 1px solid #e5e7eb; border-left: 4px solid #6366f1; border-radius: 4px; margin-bottom: 25px;">
                    <label style="font-weight: bold; font-size: 15px; display: block; margin-bottom: 10px;">
                        <input type="checkbox" name="wpc_list_enable_comparison" value="1" <?php checked( $enable_comparison, '1' ); ?> />
                        Enable Comparison Feature (Master Switch)
                    </label>
                    <p class="description" style="margin-left: 25px;">
                        <strong>ON</strong>: Users can select cards to compare. Selection circles and Footer bar can be enabled.<br>
                        <strong>OFF</strong>: All comparison features are disabled. Selection is blocked. Cards only allow "Details".
                    </p>
                </div>

                <div class="wpc-comparison-options" style="margin-bottom: 20px; padding-left: 25px; <?php echo ($enable_comparison === '1') ? '' : 'opacity:0.6; pointer-events:none;'; ?>">
                    <label class="wpc-field-label" style="font-weight:600;">UI Settings (Only if Master is ON)</label>
                    <div style="margin-bottom: 15px;">
                        <label>
                            <input type="checkbox" name="wpc_list_show_checkboxes" value="1" <?php checked('1', $show_checkboxes); ?> />
                            Show Selection Checkboxes (Circles) on Cards
                        </label>
                    </div>
                </div>

                <div style="margin-bottom: 25px; border-top: 1px solid #eee; padding-top: 20px;">
                     <label class="wpc-field-label">"View" Button Behavior (When comparison is OFF or not focused)</label>
                     <select name="wpc_list_view_action" style="width: 100%; max-width: 400px;">
                        <option value="popup" <?php selected($view_action, 'popup'); ?>>Open Product Details Popup</option>
                        <option value="link" <?php selected($view_action, 'link'); ?>>Open Affiliate Link (Direct)</option>
                     </select>
                     <p class="description">Choose what happens when the user clicks "View" or "View Details".</p>
                </div>
            </div>

            <div class="wpc-field-group">
                 <h3 style="margin-top:0;">Additional Options</h3>
                 <div class="wpc-flex-row">
                     <div class="wpc-flex-item">
                        <label class="wpc-field-label">Button Text Override</label>
                        <input type="text" name="wpc_list_button_text" value="<?php echo esc_attr( $custom_button_text ); ?>" placeholder="e.g. Visit Site" style="width:100%;" />
                     </div>
                     <div class="wpc-flex-item">
                         <label class="wpc-field-label">Show Plans in Popup</label>
                         <?php $show_plans = get_post_meta($post->ID, '_wpc_list_show_plans', true); ?>
                         <select name="wpc_list_show_plans" style="width:100%;">
                             <option value="" <?php selected($show_plans, ''); ?>>Default</option>
                             <option value="1" <?php selected($show_plans, '1'); ?>>Yes</option>
                             <option value="0" <?php selected($show_plans, '0'); ?>>No</option>
                         </select>
                     </div>
                 </div>
            </div>
        </div>

        <!-- TAB 4: FEATURES -->
        <div id="wpc-tab-features" class="wpc-tab-content">
            <div class="wpc-field-group">
                <h3 style="margin-top:0;"><?php _e( 'Comparison Table Features & Hierarchy', 'wp-comparison-builder' ); ?></h3>
                <p class="description"><?php _e( 'Customize the features and their order specifically for this list.', 'wp-comparison-builder' ); ?></p>
                
                <?php
                $override_global = get_post_meta($post->ID, '_wpc_list_features_override', true) === '1';
                $list_features = get_post_meta($post->ID, '_wpc_list_features', true);
                
                // Fetch Global if override is not set or list features empty? 
                // Actually if override is OFF, the UI is hidden.
                // If override is ON, we show what is saved in list_features.
                // If list_features is empty, we show empty active list (user builds from scratch) OR we could pre-fill with global?
                // Let's start with empty/saved state to avoid confusion. User explicitly adds features.
                
                if (!is_array($list_features)) $list_features = array();

                // Define all available items
                $all_items = array(
                    'price' => array('label' => __('Price', 'wp-comparison-builder'), 'type' => 'builtin'),
                    'rating' => array('label' => __('Rating', 'wp-comparison-builder'), 'type' => 'builtin'),
                    'pros' => array('label' => __('Pros', 'wp-comparison-builder'), 'type' => 'builtin'),
                    'cons' => array('label' => __('Cons', 'wp-comparison-builder'), 'type' => 'builtin'),
                );
                
                $tag_terms = get_terms( array( 'taxonomy' => 'comparison_feature', 'hide_empty' => false ) );
                if ( ! empty($tag_terms) && ! is_wp_error($tag_terms) ) {
                    foreach ( $tag_terms as $term ) {
                        $all_items['tag_' . $term->term_id] = array(
                            'label' => $term->name,
                            'type' => 'tag'
                        );
                    }
                }
                
                // Active Keys
                $active_keys = $list_features;
                
                // Available Keys
                $available_keys = array_diff(array_keys($all_items), $active_keys);
                ?>
                
                <div style="background: #fdfdfd; padding: 20px; border: 1px solid #e5e7eb; border-left: 4px solid #6366f1; border-radius: 4px; margin-bottom: 25px;">
                    <label style="font-weight: bold; font-size: 15px; display: block;">
                        <input type="checkbox" name="wpc_list_features_override" value="1" <?php checked( $override_global ); ?> />
                        Enable Custom Feature Selection for this List
                    </label>
                    <p class="description" style="margin-left: 20px; margin-top: 5px;">
                        Check this box to manually select which columns appear in this specific table. If unchecked, the Global Settings features will be used.
                    </p>
                    
                    <!-- Icon Color Overrides -->
                    <!-- Placing this here as it relates to feature visualization -->
                    <div style="margin-top: 15px; margin-left: 20px; padding-top: 15px; border-top: 1px dashed #ddd;">
                        <strong style="display:block; margin-bottom: 8px;">Override Icon Colors (Optional):</strong>
                        <div style="display:flex; gap: 20px;">
                            <div>
                                <label style="display:block; font-size: 12px; margin-bottom: 3px;">Tick Color</label>
                                <div style="display:flex; align-items:center; gap:5px;">
                                    <input type="color" name="wpc_list_color_tick" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_color_tick', true) ?: '#10b981' ); ?>" style="height:30px; cursor:pointer;" />
                                    <label><input type="checkbox" name="wpc_list_use_tick" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_tick', true), '1' ); ?> /> Set</label>
                                </div>
                            </div>
                            <div>
                                <label style="display:block; font-size: 12px; margin-bottom: 3px;">Cross Color</label>
                                <div style="display:flex; align-items:center; gap:5px;">
                                    <input type="color" name="wpc_list_color_cross" value="<?php echo esc_attr( get_post_meta($post->ID, '_wpc_list_color_cross', true) ?: '#ef4444' ); ?>" style="height:30px; cursor:pointer;" />
                                     <label><input type="checkbox" name="wpc_list_use_cross" value="1" <?php checked( get_post_meta($post->ID, '_wpc_list_use_cross', true), '1' ); ?> /> Set</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="wpc-list-features-ui" style="<?php echo $override_global ? '' : 'display:none;'; ?>">
                    
                     <div class="wpc-dual-listbox-wrapper">
                         <!-- Reusing CSS from global settings if available or inline here -->
                         <style>
                            .wpc-dual-listbox-wrapper { display: flex; gap: 20px; align-items: flex-start; margin-top: 20px; }
                            .wpc-list-col { flex: 1; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; display: flex; flex-direction: column; }
                            .wpc-list-header { background: #f9f9f9; padding: 10px; border-bottom: 1px solid #ccd0d4; font-weight: 600; display: flex; justify-content: space-between; align-items: center; }
                            .wpc-list-search { padding: 10px; border-bottom: 1px solid #eee; }
                            .wpc-list-search input { width: 100%; }
                            .wpc-sortable-list { list-style: none; margin: 0; padding: 0; height: 300px; overflow-y: auto; background: #fff; }
                            .wpc-sortable-list li { 
                                padding: 8px 12px; border-bottom: 1px solid #f0f0f1; cursor: grab; display: flex; justify-content: space-between; align-items: center; background: #fff;
                                transition: background 0.1s;
                            }
                            .wpc-sortable-list li:hover { background: #f0f6fc; }
                            .wpc-sortable-list li.ui-sortable-helper { box-shadow: 0 5px 15px rgba(0,0,0,0.1); background: #fff; cursor: grabbing; }
                            .wpc-badge { font-size: 10px; padding: 2px 6px; border-radius: 4px; background: #e5e7eb; color: #374151; margin-left: 8px; }
                            .wpc-badge.builtin { background: #dbeafe; color: #1e40af; }
                            .wpc-action-btn { cursor: pointer; color: #2271b1; font-weight: 500; font-size: 12px; }
                            .wpc-empty-msg { padding: 20px; text-align: center; color: #646970; font-style: italic; display: none; }
                            ul:empty + .wpc-empty-msg { display: block; }
                        </style>

                        <!-- Available Column -->
                        <div class="wpc-list-col">
                            <div class="wpc-list-header">
                                <?php _e('Available Features', 'wp-comparison-builder'); ?>
                                <button type="button" class="button button-small" id="wpc-list-add-all"><?php _e('Add All', 'wp-comparison-builder'); ?></button>
                            </div>
                            <div class="wpc-list-search">
                                <input type="text" id="wpc-list-search-available" placeholder="<?php _e('Search tags...', 'wp-comparison-builder'); ?>">
                            </div>
                            <ul id="wpc-list-available-list" class="wpc-sortable-list">
                                <?php foreach ($available_keys as $key) : 
                                    if (!isset($all_items[$key])) continue;
                                    $item = $all_items[$key];
                                ?>
                                <li data-key="<?php echo esc_attr($key); ?>">
                                    <span class="wpc-item-label">
                                        <?php echo esc_html($item['label']); ?>
                                        <span class="wpc-badge <?php echo esc_attr($item['type']); ?>"><?php echo $item['type'] === 'builtin' ? 'Built-in' : 'Tag'; ?></span>
                                    </span>
                                    <span class="wpc-action-btn wpc-add-btn">Add &rarr;</span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="wpc-empty-msg"><?php _e('No features available', 'wp-comparison-builder'); ?></div>
                        </div>

                        <!-- Active Column -->
                        <div class="wpc-list-col">
                            <div class="wpc-list-header">
                                <?php _e('Active Columns (Ordered)', 'wp-comparison-builder'); ?>
                                <button type="button" class="button button-small" id="wpc-list-remove-all"><?php _e('Remove All', 'wp-comparison-builder'); ?></button>
                            </div>
                             <div class="wpc-list-search" style="visibility: hidden;">
                                <input type="text" disabled>
                            </div>
                            <ul id="wpc-list-active-list" class="wpc-sortable-list">
                                <?php foreach ($active_keys as $key) : 
                                     if (!isset($all_items[$key])) continue;
                                     $item = $all_items[$key];
                                ?>
                                <li data-key="<?php echo esc_attr($key); ?>">
                                    <span class="wpc-item-label">
                                        <?php echo esc_html($item['label']); ?>
                                        <span class="wpc-badge <?php echo esc_attr($item['type']); ?>"><?php echo $item['type'] === 'builtin' ? 'Built-in' : 'Tag'; ?></span>
                                    </span>
                                    <span class="wpc-action-btn wpc-remove-btn">&larr; Remove</span>
                                    <input type="hidden" name="wpc_list_features[]" value="<?php echo esc_attr($key); ?>">
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="wpc-empty-msg"><?php _e('Drag items here to active', 'wp-comparison-builder'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                 // Toggle Visibility
                 $('input[name="wpc_list_features_override"]').change(function() {
                     if($(this).is(':checked')) {
                         $('#wpc-list-features-ui').slideDown();
                     } else {
                         $('#wpc-list-features-ui').slideUp();
                     }
                 });

                 // Sortable
                 $('#wpc-list-active-list').sortable({
                    placeholder: "placeholder",
                    axis: "y",
                    containment: "parent",
                    tolerance: "pointer"
                 });
                 
                 // Reuse logic logic for Add/Remove but scoped to #wpc-list-features-ui
                 var container = $('#wpc-list-features-ui');
                 var availableList = $('#wpc-list-available-list');
                 var activeList = $('#wpc-list-active-list');

                 // Add Item
                container.on('click', '.wpc-add-btn', function() {
                    var li = $(this).closest('li');
                    var key = li.data('key');
                    li.find('.wpc-action-btn').removeClass('wpc-add-btn').addClass('wpc-remove-btn').html('&larr; Remove');
                    li.append('<input type="hidden" name="wpc_list_features[]" value="'+key+'">');
                    li.appendTo(activeList);
                });

                // Remove Item
                container.on('click', '.wpc-remove-btn', function() {
                    var li = $(this).closest('li');
                    li.find('input[name="wpc_list_features[]"]').remove();
                    li.find('.wpc-action-btn').removeClass('wpc-remove-btn').addClass('wpc-add-btn').html('Add &rarr;');
                    li.appendTo(availableList);
                });
                
                // Add All
                $('#wpc-list-add-all').click(function() {
                    availableList.find('li:visible .wpc-add-btn').click();
                });
                
                // Remove All
                $('#wpc-list-remove-all').click(function() {
                    activeList.find('li .wpc-remove-btn').click();
                });

                // Search
                $('#wpc-list-search-available').on('keyup', function() {
                    var value = $(this).val().toLowerCase();
                    availableList.find('li').filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                    });
                });
            });
            </script>
        </div>

    <script>
    jQuery(document).ready(function($) {
        function toggleFeaturedInputs(checkbox) {
            var row = $(checkbox).closest('tr');
            var inputs = row.find('input[type="text"], input[type="color"]');
            var labels = row.find('small');
            
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

    // Save schema IDs
    if ( isset( $_POST['schema_ids'] ) && is_array( $_POST['schema_ids'] ) ) {
        $schema_ids = array_map( 'intval', $_POST['schema_ids'] );
        update_post_meta( $post_id, '_wpc_list_schema_ids', $schema_ids );
    } else {
        update_post_meta( $post_id, '_wpc_list_schema_ids', [] );
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

    // Save Show Checkboxes (Selection Circles)
    if ( isset( $_POST['wpc_list_show_checkboxes'] ) ) {
        update_post_meta( $post_id, '_wpc_list_show_checkboxes', '1' );
    } else {
        // Default to enabled if not present? No, checkbox presence determines state. 
        // But for backward compatibility, if field is missing (e.g. older form submit?), we might want default?
        // But nonce ensures form submission. So safe to disable if unchecked.
        update_post_meta( $post_id, '_wpc_list_show_checkboxes', '0' );
    }

    // Save View Action (Popup vs Link)
    if ( isset( $_POST['wpc_list_view_action'] ) ) {
        update_post_meta( $post_id, '_wpc_list_view_action', sanitize_text_field( $_POST['wpc_list_view_action'] ) );
    }

    // Save Filter Layout
    if ( isset( $_POST['wpc_list_filter_layout'] ) ) {
        update_post_meta( $post_id, '_wpc_list_filter_layout', sanitize_text_field( $_POST['wpc_list_filter_layout'] ) );
    }

    // Save Search Type
    if ( isset( $_POST['wpc_list_search_type'] ) ) {
        update_post_meta( $post_id, '_wpc_list_search_type', sanitize_text_field( $_POST['wpc_list_search_type'] ) );
    }

    // Save Show Search Bar Option
    if ( isset( $_POST['wpc_list_show_search_opt'] ) ) {
        update_post_meta( $post_id, '_wpc_list_show_search_opt', sanitize_text_field( $_POST['wpc_list_show_search_opt'] ) );
    }

    // Save Show Filters Option
    if ( isset( $_POST['wpc_list_show_filters_opt'] ) ) {
        update_post_meta( $post_id, '_wpc_list_show_filters_opt', sanitize_text_field( $_POST['wpc_list_show_filters_opt'] ) );
    }

    // Layout & Behavior Settings
    $layout_fields = [
        '_wpc_list_pt_btn_pos_table',
        '_wpc_list_pt_btn_pos_popup',
        '_wpc_list_target_details',
        '_wpc_list_target_direct',
        '_wpc_list_target_pricing',
        // Existing
        '_wpc_list_default_style',
        '_wpc_list_badge_style',
        '_wpc_list_show_rating',
        '_wpc_list_show_price',
        '_wpc_list_filter_layout',
        '_wpc_list_show_filters_opt',
        '_wpc_list_show_search_opt',
        '_wpc_list_search_type'
    ];
    
    foreach ($layout_fields as $field) {
        $post_key = substr($field, 1); // remove leading underscore for POST key if naming convention matches
        // Actually for these I used name="wpc_list_..." which matches _wpc_list_... convention minus the underscore
        if ( isset( $_POST[$post_key] ) ) {
            update_post_meta( $post_id, $field, sanitize_text_field( $_POST[$post_key] ) );
        }
    }

    // Save Icon Color Overrides (Sanitize as hex)
    if ( isset( $_POST['wpc_list_use_tick'] ) ) {
        update_post_meta( $post_id, '_wpc_list_use_tick', '1' );
        if ( isset( $_POST['wpc_list_color_tick'] ) ) {
            update_post_meta( $post_id, '_wpc_list_color_tick', sanitize_hex_color( $_POST['wpc_list_color_tick'] ) );
        }
    } else {
        delete_post_meta( $post_id, '_wpc_list_use_tick' );
        delete_post_meta( $post_id, '_wpc_list_color_tick' );
    }

    if ( isset( $_POST['wpc_list_use_cross'] ) ) {
        update_post_meta( $post_id, '_wpc_list_use_cross', '1' );
        if ( isset( $_POST['wpc_list_color_cross'] ) ) {
            update_post_meta( $post_id, '_wpc_list_color_cross', sanitize_hex_color( $_POST['wpc_list_color_cross'] ) );
        }
    } else {
        delete_post_meta( $post_id, '_wpc_list_use_cross' );
        delete_post_meta( $post_id, '_wpc_list_color_cross' );
    }

    // Save Visibility Flags
    update_post_meta( $post_id, '_wpc_list_show_rating', isset($_POST['wpc_list_show_rating']) ? '1' : '0' );
    update_post_meta( $post_id, '_wpc_list_show_price', isset($_POST['wpc_list_show_price']) ? '1' : '0' );

    // Save Pricing Table & Popup Visibility Flags
    update_post_meta( $post_id, '_wpc_list_show_select_table', isset($_POST['wpc_list_show_select_table']) ? '1' : '0' );
    update_post_meta( $post_id, '_wpc_list_show_select_popup', isset($_POST['wpc_list_show_select_popup']) ? '1' : '0' );
    update_post_meta( $post_id, '_wpc_list_show_footer_table', isset($_POST['wpc_list_show_footer_table']) ? '1' : '0' );
    update_post_meta( $post_id, '_wpc_list_show_footer_popup', isset($_POST['wpc_list_show_footer_popup']) ? '1' : '0' );
    update_post_meta( $post_id, '_wpc_list_hide_features', isset($_POST['wpc_list_hide_features']) ? '1' : '0' );
    
    // Save List Style
    if ( isset( $_POST['wpc_list_style'] ) ) {
        update_post_meta( $post_id, '_wpc_list_style', sanitize_text_field( $_POST['wpc_list_style'] ) );
    }

    // Save Button Text
    if ( isset( $_POST['wpc_list_button_text'] ) ) {
        update_post_meta( $post_id, '_wpc_list_button_text', sanitize_text_field( $_POST['wpc_list_button_text'] ) );
    } else {
        delete_post_meta( $post_id, '_wpc_list_button_text' );
    }

    // Save List Source Type
    if ( isset( $_POST['wpc_list_source_type'] ) ) {
        update_post_meta( $post_id, '_wpc_list_source_type', sanitize_text_field( $_POST['wpc_list_source_type'] ) );
    }

    // Save Schema Description
    if ( isset( $_POST['wpc_list_schema_desc'] ) ) {
        update_post_meta( $post_id, '_wpc_list_schema_desc', sanitize_textarea_field( $_POST['wpc_list_schema_desc'] ) );
    }

    // Save Tool Filters
    if ( isset( $_POST['wpc_list_filter_tool_cats'] ) ) {
        $tool_cats = array_map( 'intval', $_POST['wpc_list_filter_tool_cats'] );
        update_post_meta( $post_id, '_wpc_list_filter_tool_cats', $tool_cats );
    } else {
        delete_post_meta( $post_id, '_wpc_list_filter_tool_cats' );
    }

    if ( isset( $_POST['wpc_list_filter_tool_tags'] ) ) {
        $tool_tags = array_map( 'intval', $_POST['wpc_list_filter_tool_tags'] );
        update_post_meta( $post_id, '_wpc_list_filter_tool_tags', $tool_tags );
    } else {
        delete_post_meta( $post_id, '_wpc_list_filter_tool_tags' );
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

    // --- SAVE TEXT LABELS ---
    $text_fields = [
        '_wpc_list_txt_compare',
        '_wpc_list_txt_copied',
        '_wpc_list_txt_view',
        '_wpc_list_txt_visit',
        '_wpc_list_txt_compare_btn',
        '_wpc_list_txt_compare_now',
        '_wpc_list_txt_visit_plat',
        '_wpc_list_txt_search_ph',
        '_wpc_list_txt_active_filt',
        '_wpc_list_txt_clear_all',
        '_wpc_list_txt_no_results',
        '_wpc_list_txt_selected',
        '_wpc_list_txt_comp_header',
        '_wpc_list_txt_sort_def',
        '_wpc_list_txt_sort_asc',
        '_wpc_list_txt_sort_desc',
        '_wpc_list_txt_sort_rating',
        '_wpc_list_txt_sort_price',
        '_wpc_list_txt_get_coupon',
        '_wpc_list_txt_featured',
        '_wpc_list_txt_feat_prod',
        '_wpc_list_txt_feat_fees',
        '_wpc_list_txt_feat_channels', // New
        '_wpc_list_txt_feat_ssl',      // New
        '_wpc_list_txt_feat_supp',
        '_wpc_list_cat_label',
        '_wpc_list_feat_label',
        '_wpc_list_txt_feat_header',
        '_wpc_list_txt_pros',
        '_wpc_list_txt_cons',
        '_wpc_list_txt_price',
        '_wpc_list_txt_rating',
        '_wpc_list_txt_mo_suffix',
        // New Misc Labels
        '_wpc_list_txt_no_compare',
        '_wpc_list_txt_remove',
        '_wpc_list_txt_logo',
        '_wpc_list_txt_analysis',
        '_wpc_list_txt_start_price',
        '_wpc_list_txt_dash_prev',
        // Filter & Search Internal Labels
        '_wpc_list_txt_reset_filt',
        '_wpc_list_txt_select_fmt',
        '_wpc_list_txt_clear',
        '_wpc_list_txt_sel_prov',
        '_wpc_list_txt_no_item',
        '_wpc_list_txt_more',
        // Additional UI Texts
        '_wpc_list_txt_show_all',
        '_wpc_list_txt_reveal_more',
        '_wpc_list_txt_no_logo',
        // Pricing Table & Popup Labels
        '_wpc_list_txt_select_plan',
        '_wpc_list_txt_pt_plan',
        '_wpc_list_txt_pt_price',
        '_wpc_list_txt_pt_features'
    ];

    foreach ($text_fields as $field) {
        // Strip the leading underscore for the POST key if naming convention differs, 
        // BUT our inputs use 'wpc_list_txt_...' which matches meta key without initial underscore if we are careful.
        // Actually inputs are named 'wpc_list_txt_search_ph' and meta is '_wpc_list_txt_search_ph'.
        // So we strip first char '_' from meta key to get POST key.
        $post_key = substr($field, 1); 
        
        if ( isset( $_POST[$post_key] ) ) {
             update_post_meta( $post_id, $field, sanitize_text_field( $_POST[$post_key] ) );
        }
    }

    // Save Features Override
    if ( isset( $_POST['wpc_list_features_override'] ) ) {
        update_post_meta( $post_id, '_wpc_list_features_override', '1' );
        
        // Save Features List
        if ( isset( $_POST['wpc_list_features'] ) && is_array( $_POST['wpc_list_features'] ) ) {
             $features = array_map( 'sanitize_text_field', $_POST['wpc_list_features'] );
             update_post_meta( $post_id, '_wpc_list_features', $features );
        } else {
             update_post_meta( $post_id, '_wpc_list_features', array() );
        }
    } else {
        update_post_meta( $post_id, '_wpc_list_features_override', '0' );
    }

    // Save Visbility Options (New)
    if ( isset( $_POST['wpc_list_show_plans'] ) ) {
        update_post_meta( $post_id, '_wpc_list_show_plans', sanitize_text_field( $_POST['wpc_list_show_plans'] ) );
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

    // Configurable Texts and Colors handled by loop above


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
    
    // Save Comparison Table Colors (Pros/Cons/Coupon)
    $table_color_fields = [
        'color_pros_bg' => 'use_pros_bg', 
        'color_pros_text' => 'use_pros_text', 
        'color_cons_bg' => 'use_cons_bg', 
        'color_cons_text' => 'use_cons_text',
        'color_coupon_bg' => 'use_coupon_bg', 
        'color_coupon_text' => 'use_coupon_text', 
        'color_coupon_hover' => 'use_coupon_hover', 
        'color_copied' => 'use_copied'
    ];
    foreach ($table_color_fields as $field => $use_field) {
        $key = "_wpc_list_{$field}";
        $use_key = "_wpc_list_{$use_field}";
        
        // Check if "Set" checkbox is checked
        if (isset($_POST["wpc_list_{$use_field}"])) {
            update_post_meta($post_id, $use_key, '1');
            if (isset($_POST["wpc_list_{$field}"])) {
                update_post_meta($post_id, $key, sanitize_hex_color($_POST["wpc_list_{$field}"]));
            }
        } else {
            // Unchecked -> Remove overrides to fallback to Global Defaults
            delete_post_meta($post_id, $key);
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
