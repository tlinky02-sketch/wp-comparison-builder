<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Comparison Tool Custom Post Type (Only if module is enabled)
 */
add_action( 'init', 'wpc_register_tool_cpt' );
function wpc_register_tool_cpt() {
    // Check module status
    $module_enabled = get_option( 'wpc_enable_tools_module', false );
    $show_in_menu = $module_enabled ? 'edit.php?post_type=comparison_item' : false;

    register_post_type( 'comparison_tool', array(
        'labels' => array(
            'name' => __( 'Recommended Tools', 'wp-comparison-builder' ),
            'singular_name' => __( 'Tool', 'wp-comparison-builder' ),
            'add_new' => __( 'Add New Tool', 'wp-comparison-builder' ),
            'add_new_item' => __( 'Add New Tool', 'wp-comparison-builder' ),
            'edit_item' => __( 'Edit Tool', 'wp-comparison-builder' ),
            'all_items' => __( 'All Tools', 'wp-comparison-builder' ),
        ),
        'public' => true,
        'publicly_queryable' => false, // Hide permalink
        'has_archive' => false,
        'show_in_menu' => $show_in_menu,
        'supports' => array( 'title', 'editor', 'thumbnail' ),
        'menu_icon' => 'dashicons-admin-tools',
    ));

    register_taxonomy( 'tool_category', 'comparison_tool', array(
        'labels' => array(
            'name' => __( 'Tool Categories', 'wp-comparison-builder' ),
            'singular_name' => __( 'Category', 'wp-comparison-builder' ),
        ),
        'hierarchical' => true,
        'show_admin_column' => true,
    ));

    register_taxonomy( 'tool_tag', 'comparison_tool', array(
        'labels' => array(
            'name' => __( 'Tool Tags', 'wp-comparison-builder' ),
            'singular_name' => __( 'Tag', 'wp-comparison-builder' ),
        ),
        'hierarchical' => false,
        'show_admin_column' => true,
    ));
}

/**
 * Add Meta Boxes for Tool
 */
add_action( 'add_meta_boxes', 'wpc_add_tool_meta_boxes' );
function wpc_add_tool_meta_boxes() {
    if ( ! get_option( 'wpc_enable_tools_module', false ) ) return;

    add_meta_box(
        'wpc_tool_details',
        __( 'Tool Details', 'wp-comparison-builder' ),
        'wpc_render_tool_meta_box',
        'comparison_tool',
        'normal',
        'high'
    );

    add_meta_box(
        'wpc_tool_categories_side',
        __( 'Tool Categories', 'wp-comparison-builder' ),
        'wpc_render_tool_categories_side',
        'comparison_tool',
        'side',
        'default'
    );

    add_meta_box(
        'wpc_tool_tags_side',
        __( 'Tool Tags', 'wp-comparison-builder' ),
        'wpc_render_tool_tags_side',
        'comparison_tool',
        'side',
        'default'
    );
}

/**
 * Remove Default Taxonomy Meta Boxes for Tool and Add Submenu Pages
 */
function wpc_manage_tool_admin_menu() {
    remove_meta_box( 'tool_categorydiv', 'comparison_tool', 'side' );
    remove_meta_box( 'tagsdiv-tool_tag', 'comparison_tool', 'side' );

    if ( get_option( 'wpc_enable_tools_module', false ) ) {
        add_submenu_page(
            'edit.php?post_type=comparison_item',
            __( 'Tool Categories', 'wp-comparison-builder' ),
            __( 'Tool Categories', 'wp-comparison-builder' ),
            'manage_options',
            'edit-tags.php?taxonomy=tool_category&post_type=comparison_tool'
        );

        add_submenu_page(
            'edit.php?post_type=comparison_item',
            __( 'Tool Tags', 'wp-comparison-builder' ),
            __( 'Tool Tags', 'wp-comparison-builder' ),
            'manage_options',
            'edit-tags.php?taxonomy=tool_tag&post_type=comparison_tool'
        );
    }
}
add_action( 'admin_menu', 'wpc_manage_tool_admin_menu' );

/**
 * Reorder Comparison Item Submenu to ensure Tool Categories and Tool Tags appear after All Tools but before Settings
 */
function wpc_reorder_comparison_submenu() {
    global $submenu;
    $parent = 'edit.php?post_type=comparison_item';
    if ( ! isset( $submenu[ $parent ] ) ) {
        return;
    }

    $desired_order = array(
        'edit.php?post_type=comparison_item',
        'post-new.php?post_type=comparison_item',
        'edit-tags.php?taxonomy=comparison_category&post_type=comparison_item',
        'edit-tags.php?taxonomy=comparison_feature&post_type=comparison_item',
        'edit.php?post_type=comparison_list',
        'edit.php?post_type=comparison_review',
        'edit.php?post_type=comparison_tool',
        'edit-tags.php?taxonomy=tool_category&post_type=comparison_tool',
        'edit-tags.php?taxonomy=tool_tag&post_type=comparison_tool',
        'wpc-settings',
        'wpc-compare-alternatives'
    );

    $ordered_submenu = array();

    // First, place items matching our desired order list (insensitive to & vs &amp;)
    foreach ( $desired_order as $desired_slug ) {
        $normalized_desired = str_replace( '&amp;', '&', $desired_slug );
        foreach ( $submenu[ $parent ] as $key => $item ) {
            if ( isset( $item[2] ) ) {
                $normalized_item_slug = str_replace( '&amp;', '&', $item[2] );
                if ( $normalized_item_slug === $normalized_desired ) {
                    $ordered_submenu[] = $item;
                    unset( $submenu[ $parent ][ $key ] );
                }
            }
        }
    }

    // Append any remaining items that weren't in our desired list
    foreach ( $submenu[ $parent ] as $item ) {
        $ordered_submenu[] = $item;
    }

    // Replace the global submenu array with our ordered array
    $submenu[ $parent ] = $ordered_submenu;
}
add_action( 'admin_menu', 'wpc_reorder_comparison_submenu', 9999 );

/**
 * Fix WordPress Menu Highlight / Collapse bug for Tool Categories and Tool Tags
 */
function wpc_tool_menu_highlight_fix( $parent_file ) {
    global $current_screen;
    if ( $current_screen && isset( $current_screen->taxonomy ) ) {
        if ( $current_screen->taxonomy === 'tool_category' || $current_screen->taxonomy === 'tool_tag' ) {
            return 'edit.php?post_type=comparison_item';
        }
    }
    return $parent_file;
}
add_filter( 'parent_file', 'wpc_tool_menu_highlight_fix' );

function wpc_tool_submenu_highlight_fix( $submenu_file, $parent_file ) {
    global $current_screen;
    if ( $current_screen && isset( $current_screen->taxonomy ) ) {
        if ( $current_screen->taxonomy === 'tool_category' ) {
            return 'edit-tags.php?taxonomy=tool_category&post_type=comparison_tool';
        } elseif ( $current_screen->taxonomy === 'tool_tag' ) {
            return 'edit-tags.php?taxonomy=tool_tag&post_type=comparison_tool';
        }
    }
    return $submenu_file;
}
add_filter( 'submenu_file', 'wpc_tool_submenu_highlight_fix', 10, 2 );

/**
 * Render Tool Meta Box
 */
function wpc_render_tool_meta_box( $post ) {
    wp_nonce_field( 'wpc_save_tool', 'wpc_tool_nonce' );
    
    // Default values (fallback)
    $badge = get_post_meta( $post->ID, '_tool_badge', true );
    $link = get_post_meta( $post->ID, '_tool_link', true );
    $button_text = get_post_meta( $post->ID, '_tool_button_text', true ) ?: 'View Details';
    $short_desc = get_post_meta( $post->ID, '_wpc_tool_short_description', true );
    $rating = get_post_meta( $post->ID, '_wpc_tool_rating', true ) ?: '4.5';
    // Logic later in file handles features/pricing
    
    // Try Custom Table Check
    $pricing = get_post_meta( $post->ID, '_wpc_tool_pricing_plans', true );
    $features_val = ''; // Logic below text area usually handles this via get_post_meta call

    if ( class_exists('WPC_Tools_Database') ) {
        $db = new WPC_Tools_Database();
        $tool = $db->get_tool( $post->ID );
        if ( $tool ) {
            $badge = $tool->badge_text;
            $link = $tool->link;
            $button_text = $tool->button_text;
            $short_desc = $tool->short_description;
            $rating = $tool->rating;
            $pricing = $tool->pricing_plans;
        }
    }
    
    // AI Check
    $ai_profiles = class_exists( 'WPC_AI_Handler' ) ? WPC_AI_Handler::get_profiles() : array();
    $ai_configured = ! empty( $ai_profiles );
    ?>
    <style>
        .wpc-tool-field { margin-bottom: 15px; }
        .wpc-tool-label { display: block; font-weight: 600; margin-bottom: 5px; }
        .wpc-tool-input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    </style>

    <!-- AI Tool Generator (Always Visible) -->
    <div style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 15px;">
            <div style="flex: 1;">
                <strong>🤖 AI Tool Generator</strong>
                <?php if ( ! $ai_configured ) : ?>
                <p style="margin: 5px 0 0 0; font-size: 12px; opacity: 0.9;">
                    ⚠️ AI not configured. <a href="<?php echo admin_url('admin.php?page=comparison-builder-settings&tab=ai-settings'); ?>" style="color: white; text-decoration: underline;">Set up AI</a> to enable auto-generation.
                </p>
                <?php else : ?>
                <p style="margin: 5px 0 0 0; font-size: 12px; opacity: 0.9;">Generate detailed tool information automatically</p>
                <?php endif; ?>
            </div>
            <input type="text" id="wpc-ai-tool-name" placeholder="Tool name (e.g., Klaviyo)" style="padding: 8px 12px; border-radius: 6px; border: none; min-width: 200px; color: #1e293b;" value="<?php echo esc_attr( $post->post_title ); ?>" <?php echo ! $ai_configured ? 'disabled' : ''; ?> />
            <button type="button" id="wpc-ai-generate-tool" class="button" style="background: white; color: #6366f1; border: none; font-weight: 600; padding: 8px 16px;" <?php echo ! $ai_configured ? 'disabled' : ''; ?>>
                ✨ Generate
            </button>
        </div>
    </div>

    <div class="wpc-tool-field">
        <label class="wpc-tool-label">Badge Text (Optional)</label>
        <input type="text" name="wpc_tool_badge" value="<?php echo esc_attr( $badge ); ?>" class="wpc-tool-input" placeholder="e.g., Best Marketing" />
        <p class="description">Displayed as a badge on the tool card</p>
    </div>

    <div class="wpc-tool-field">
        <label class="wpc-tool-label">Link URL</label>
        <input type="url" name="wpc_tool_link" value="<?php echo esc_url( $link ); ?>" class="wpc-tool-input" placeholder="https://example.com" required />
    </div>

    <div class="wpc-tool-field">
        <label class="wpc-tool-label">Button Text</label>
        <input type="text" name="wpc_tool_button_text" value="<?php echo esc_attr( $button_text ); ?>" class="wpc-tool-input" placeholder="View Details" />
    </div>

    <!-- NEW: Item-Like Fields -->
    <hr style="margin: 30px 0; border: none; border-top: 2px solid #e5e7eb;" />
    
    <div class="wpc-tool-field">
        <label class="wpc-tool-label">Short Description</label>
        <?php
        $short_desc = get_post_meta( $post->ID, '_wpc_tool_short_description', true );
        ?>
        <textarea name="wpc_tool_short_description" class="wpc-tool-input" rows="3" placeholder="Brief description..."><?php echo esc_textarea( $short_desc ); ?></textarea>
        <p class="description">Shown on cards (optional - uses main content if empty)</p>
    </div>

    <div class="wpc-tool-field">
        <label class="wpc-tool-label">Rating (1-5)</label>
        <?php
        $rating = get_post_meta( $post->ID, '_wpc_tool_rating', true ) ?: '4.5';
        ?>
        <input type="number" name="wpc_tool_rating" value="<?php echo esc_attr( $rating ); ?>" class="wpc-tool-input" min="0" max="5" step="0.1" />
    </div>

    <div class="wpc-tool-field">
        <label class="wpc-tool-label">Pricing Plans</label>
        <?php
        $pricing = get_post_meta( $post->ID, '_wpc_tool_pricing_plans', true );
        if ( ! is_array( $pricing ) ) $pricing = array();
        ?>
        <div id="wpc-pricing-plans-container">
            <?php foreach ( $pricing as $idx => $plan ) : ?>
                <div class="wpc-plan-row" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 4px; margin-bottom: 10px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <strong>Plan #<?php echo $idx + 1; ?></strong>
                        <button type="button" class="button wpc-remove-plan" onclick="this.closest('.wpc-plan-row').remove()">&times; Remove</button>
                    </div>
                    <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                        <input type="text" name="wpc_tool_pricing_plans[<?php echo $idx; ?>][name]" value="<?php echo esc_attr( $plan['name'] ?? '' ); ?>" placeholder="Plan Name (e.g. Starter)" style="flex: 2;" />
                        <input type="text" name="wpc_tool_pricing_plans[<?php echo $idx; ?>][price]" value="<?php echo esc_attr( $plan['price'] ?? '' ); ?>" placeholder="Price (e.g. 29)" style="flex: 1;" />
                        <input type="text" name="wpc_tool_pricing_plans[<?php echo $idx; ?>][period]" value="<?php echo esc_attr( $plan['period'] ?? '' ); ?>" placeholder="Period (e.g. /mo)" style="flex: 1;" />
                    </div>
                    <textarea name="wpc_tool_pricing_plans[<?php echo $idx; ?>][features]" rows="3" placeholder="Features (one per line)" style="width: 100%; font-size: 12px;"><?php 
                        if ( ! empty( $plan['features'] ) && is_array( $plan['features'] ) ) {
                            echo esc_textarea( implode( "\n", $plan['features'] ) );
                        } elseif ( ! empty( $plan['features'] ) ) {
                            echo esc_textarea( $plan['features'] );
                        }
                    ?></textarea>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button" id="wpc-add-plan">+ Add Pricing Plan</button>
        <p class="description">Add pricing plans for this tool.</p>
        
        <script>
        jQuery(document).ready(function($) {
            var container = $('#wpc-pricing-plans-container');
            var idx = <?php echo count( $pricing ); ?>;
            
            $('#wpc-add-plan').on('click', function() {
                var row = `
                <div class="wpc-plan-row" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 4px; margin-bottom: 10px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <strong>Plan #${idx + 1}</strong>
                        <button type="button" class="button wpc-remove-plan" onclick="this.closest('.wpc-plan-row').remove()">&times; Remove</button>
                    </div>
                    <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                        <input type="text" name="wpc_tool_pricing_plans[${idx}][name]" placeholder="Plan Name (e.g. Starter)" style="flex: 2;" />
                        <input type="text" name="wpc_tool_pricing_plans[${idx}][price]" placeholder="Price (e.g. 29)" style="flex: 1;" />
                        <input type="text" name="wpc_tool_pricing_plans[${idx}][period]" placeholder="Period (e.g. /mo)" style="flex: 1;" />
                    </div>
                    <textarea name="wpc_tool_pricing_plans[${idx}][features]" rows="3" placeholder="Features (one per line)" style="width: 100%; font-size: 12px;"></textarea>
                </div>
                `;
                container.append(row);
                idx++;
            });
        });
        </script>
    </div>

    <!-- NEW: JSON Import -->
    <div class="wpc-tool-field" style="margin-top: 30px; border-top: 2px solid #e5e7eb; padding-top: 20px;">
        <h3 class="wpc-tool-label">Import Tool Data (JSON)</h3>
        <textarea id="wpc_tool_import_json" rows="5" class="wpc-tool-input" placeholder='Paste JSON here...'></textarea>
        <button type="button" class="button button-primary" id="wpc-import-tool-btn" style="margin-top: 10px;">Import JSON</button>
        <script>
        jQuery(document).ready(function($) {
            $('#wpc-import-tool-btn').on('click', function() {
                var jsonStr = $('#wpc_tool_import_json').val();
                if (!jsonStr) return;
                try {
                    var data = JSON.parse(jsonStr);
                    if (data.name) $('#title').val(data.name);
                    if (data.short_description) $('[name="wpc_tool_short_description"]').val(data.short_description);
                    if (data.rating) $('[name="wpc_tool_rating"]').val(data.rating);
                    if (data.badge) $('[name="wpc_tool_badge"]').val(data.badge);
                    if (data.link) $('[name="wpc_tool_link"]').val(data.link);
                    if (data.button_text) $('[name="wpc_tool_button_text"]').val(data.button_text);
                    if (data.features && Array.isArray(data.features)) $('[name="wpc_tool_features"]').val(data.features.join('\\n'));
                    
                    // Import Pricing
                    if (data.pricing_plans && Array.isArray(data.pricing_plans)) {
                        $('#wpc-pricing-plans-container').empty(); // Clear existing
                        var idx = 0;
                        data.pricing_plans.forEach(function(plan) {
                            var featuresText = '';
                            if (plan.features && Array.isArray(plan.features)) featuresText = plan.features.join('\\n');
                            
                            var row = \`
                            <div class="wpc-plan-row" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 4px; margin-bottom: 10px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <strong>Plan #\${idx + 1}</strong>
                                    <button type="button" class="button wpc-remove-plan" onclick="this.closest('.wpc-plan-row').remove()">&times; Remove</button>
                                </div>
                                <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                    <input type="text" name="wpc_tool_pricing_plans[\${idx}][name]" value="\${plan.name || ''}" placeholder="Plan Name" style="flex: 2;" />
                                    <input type="text" name="wpc_tool_pricing_plans[\${idx}][price]" value="\${plan.price || ''}" placeholder="Price" style="flex: 1;" />
                                    <input type="text" name="wpc_tool_pricing_plans[\${idx}][period]" value="\${plan.period || ''}" placeholder="Period" style="flex: 1;" />
                                </div>
                                <textarea name="wpc_tool_pricing_plans[\${idx}][features]" rows="3" placeholder="Features" style="width: 100%; font-size: 12px;">\${featuresText}</textarea>
                            </div>
                            \`;
                            $('#wpc-pricing-plans-container').append(row);
                            idx++;
                        });
                    }
                    alert('✅ Data imported! Please save the post.');
                } catch(e) {
                    alert('❌ Invalid JSON: ' + e.message);
                }
            });
        });
        </script>
    </div>

    <div class="wpc-tool-field">
        <label class="wpc-tool-label">Features (one per line)</label>
        <?php
        $features = get_post_meta( $post->ID, '_wpc_tool_features', true );
        
        // Custom Table Override for Features
        if ( isset($tool) && is_object($tool) && !empty($tool->features) ) {
             $features = implode("\n", $tool->features);
        }
        ?>
        <textarea name="wpc_tool_features" class="wpc-tool-input" rows="6" placeholder="Feature 1&#10;Feature 2&#10;Feature 3"><?php echo esc_textarea( $features ); ?></textarea>
        <p class="description">Main features of this tool (displayed in comparison)</p>
    </div>

    <div class="wpc-tool-field">
        <label class="wpc-tool-label">Pros (one per line)</label>
        <?php
        $pros_val = get_post_meta( $post->ID, '_wpc_pros', true );
        if ( isset($tool) && is_object($tool) && !empty($tool->pros) ) {
             $pros_val = is_array($tool->pros) ? implode("\n", $tool->pros) : $tool->pros;
        }
        ?>
        <textarea name="wpc_tool_pros" class="wpc-tool-input" rows="4" placeholder="Pro 1&#10;Pro 2"><?php echo esc_textarea( $pros_val ); ?></textarea>
        <p class="description">Positive aspects of this tool</p>
    </div>

    <div class="wpc-tool-field">
        <label class="wpc-tool-label">Cons (one per line)</label>
        <?php
        $cons_val = get_post_meta( $post->ID, '_wpc_cons', true );
        if ( isset($tool) && is_object($tool) && !empty($tool->cons) ) {
             $cons_val = is_array($tool->cons) ? implode("\n", $tool->cons) : $tool->cons;
        }
        ?>
        <textarea name="wpc_tool_cons" class="wpc-tool-input" rows="4" placeholder="Con 1&#10;Con 2"><?php echo esc_textarea( $cons_val ); ?></textarea>
        <p class="description">Negative aspects of this tool</p>
    </div>

    <input type="hidden" id="wpc_ai_tool_nonce" value="<?php echo wp_create_nonce( 'wpc_ai_nonce' ); ?>" />
    <script>
    jQuery(document).ready(function($) {
        $('#wpc-ai-generate-tool').on('click', function() {
            var btn = $(this);
            var toolName = $('#wpc-ai-tool-name').val();
            
            if (!toolName) {
                alert('Please enter a tool name');
                return;
            }

            btn.prop('disabled', true).text('⏳ Generating...');

            $.post(ajaxurl, {
                action: 'wpc_ai_generate_tool',
                tool_name: toolName,
                _ajax_nonce: $('#wpc_ai_tool_nonce').val()
            }, function(response) {
                btn.prop('disabled', false).text('✨ Generate');
                
                if (response.success && response.data) {
                    var data = response.data;
                    if (data.title) $('#title').val(data.title);
                    if (data.description) {
                        if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                            tinymce.get('content').setContent(data.description);
                        } else {
                            $('#content').val(data.description);
                        }
                    }
                    if (data.badge) $('[name="wpc_tool_badge"]').val(data.badge);
                    if (data.link) $('[name="wpc_tool_link"]').val(data.link);
                    alert('✅ Tool data generated successfully!');
                } else {
                    alert('❌ ' + (response.data || 'Failed to generate'));
                }
            }).fail(function() {
                btn.prop('disabled', false).text('✨ Generate');
                alert('❌ Network error');
            });
        });
    });
    </script>
    <?php
}

/**
 * Save Tool Meta
 */
add_action( 'save_post', 'wpc_save_tool_meta' );
function wpc_save_tool_meta( $post_id ) {
    if ( ! isset( $_POST['wpc_tool_nonce'] ) || ! wp_verify_nonce( $_POST['wpc_tool_nonce'], 'wpc_save_tool' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $badge = sanitize_text_field( $_POST['wpc_tool_badge'] ?? '' );
    $link = esc_url_raw( $_POST['wpc_tool_link'] ?? '' );
    $button_text = sanitize_text_field( $_POST['wpc_tool_button_text'] ?? 'View Details' );
    
    // Save new item-like fields
    $short_desc = sanitize_textarea_field( $_POST['wpc_tool_short_description'] ?? '' );
    $rating = floatval( $_POST['wpc_tool_rating'] ?? 4.5 );
    $features_raw = sanitize_textarea_field( $_POST['wpc_tool_features'] ?? '' );
    $pros_raw = sanitize_textarea_field( $_POST['wpc_tool_pros'] ?? '' );
    $cons_raw = sanitize_textarea_field( $_POST['wpc_tool_cons'] ?? '' );
    
    // Dual-Write: Post Meta (Backup)
    update_post_meta( $post_id, '_tool_badge', $badge );
    update_post_meta( $post_id, '_tool_link', $link );
    update_post_meta( $post_id, '_tool_button_text', $button_text );
    update_post_meta( $post_id, '_wpc_tool_short_description', $short_desc );
    update_post_meta( $post_id, '_wpc_tool_rating', $rating );
    update_post_meta( $post_id, '_wpc_tool_features', $features_raw );
    update_post_meta( $post_id, '_wpc_pros', $pros_raw );
    update_post_meta( $post_id, '_wpc_cons', $cons_raw );
    
    // Save pricing plans
    $plans = array();
    if ( isset( $_POST['wpc_tool_pricing_plans'] ) && is_array( $_POST['wpc_tool_pricing_plans'] ) ) {
        foreach ( $_POST['wpc_tool_pricing_plans'] as $plan ) {
            $plan_features = array();
            if ( ! empty( $plan['features'] ) ) {
                $plan_features = array_filter( array_map( 'trim', explode( "\n", $plan['features'] ) ) );
            }
            
            $plans[] = array(
                'name'     => sanitize_text_field( $plan['name'] ?? '' ),
                'price'    => sanitize_text_field( $plan['price'] ?? '' ),
                'period'   => sanitize_text_field( $plan['period'] ?? '' ),
                'features' => $plan_features
            );
        }
        update_post_meta( $post_id, '_wpc_tool_pricing_plans', $plans );
    } else {
        delete_post_meta( $post_id, '_wpc_tool_pricing_plans' );
    }

    // Dual-Write: Custom Table (Primary)
    if ( class_exists('WPC_Tools_Database') ) {
        $db = new WPC_Tools_Database();
        $db->create_table(); // Ensure exists

        $data = [
            'badge_text'        => $badge,
            'link'              => $link,
            'button_text'       => $button_text,
            'short_description' => $short_desc,
            'rating'            => $rating,
            'features'          => array_filter( array_map( 'trim', explode( "\n", $features_raw ) ) ),
            'pricing_plans'     => $plans,
            'pros'              => array_filter( array_map( 'trim', explode( "\n", $pros_raw ) ) ),
            'cons'              => array_filter( array_map( 'trim', explode( "\n", $cons_raw ) ) )
        ];
        
        $db->update_tool( $post_id, $data );
    }

    // Save Associated Comparison Item (supports multiple selected items)
    $enable_associated = get_option( 'wpc_enable_tools_associated_items', '1' ) === '1';
    if ( $enable_associated ) {
        if ( isset( $_POST['wpc_tool_associated_items'] ) ) {
            $associated_items = array_map( 'intval', $_POST['wpc_tool_associated_items'] );
            update_post_meta( $post_id, '_wpc_tool_associated_item', $associated_items );
        } else {
            delete_post_meta( $post_id, '_wpc_tool_associated_item' );
        }

        // Save Category Sourcing Settings
        $inherit_cats = isset( $_POST['wpc_tool_inherit_categories'] ) ? '1' : '0';
        update_post_meta( $post_id, '_wpc_tool_inherit_categories', $inherit_cats );

        // Save Tag Sourcing Settings
        $inherit_tags = isset( $_POST['wpc_tool_inherit_tags'] ) ? '1' : '0';
        update_post_meta( $post_id, '_wpc_tool_inherit_tags', $inherit_tags );
    } else {
        delete_post_meta( $post_id, '_wpc_tool_associated_item' );
        update_post_meta( $post_id, '_wpc_tool_inherit_categories', '0' );
        update_post_meta( $post_id, '_wpc_tool_inherit_tags', '0' );
    }

    // Always save custom checked categories
    if ( isset( $_POST['wpc_tool_category'] ) ) {
        $cat_ids = array_map( 'intval', $_POST['wpc_tool_category'] );
        update_post_meta( $post_id, '_wpc_tool_custom_categories', $cat_ids );
    } else {
        update_post_meta( $post_id, '_wpc_tool_custom_categories', array() );
    }

    // Save primary categories
    if ( isset( $_POST['wpc_tool_primary_cats'] ) ) {
        $primary_ids = array_map( 'intval', $_POST['wpc_tool_primary_cats'] );
        update_post_meta( $post_id, '_wpc_primary_cats', $primary_ids );
    } else {
        delete_post_meta( $post_id, '_wpc_primary_cats' );
    }

    // Always save custom checked tags
    if ( isset( $_POST['wpc_tool_tag'] ) ) {
        $tag_ids = array_map( 'intval', $_POST['wpc_tool_tag'] );
        update_post_meta( $post_id, '_wpc_tool_custom_tags', $tag_ids );
    } else {
        update_post_meta( $post_id, '_wpc_tool_custom_tags', array() );
    }

    // Save primary tags
    if ( isset( $_POST['wpc_tool_primary_tags'] ) ) {
        $primary_ids = array_map( 'intval', $_POST['wpc_tool_primary_tags'] );
        update_post_meta( $post_id, '_wpc_primary_features', $primary_ids );
    } else {
        delete_post_meta( $post_id, '_wpc_primary_features' );
    }

    // Synchronize taxonomies if sourced
    wpc_sync_tool_taxonomies( $post_id );
}

/**
 * AI Generation Handler
 */
add_action( 'wp_ajax_wpc_ai_generate_tool', 'wpc_ajax_ai_generate_tool' );
function wpc_ajax_ai_generate_tool() {
    check_ajax_referer( 'wpc_ai_nonce' );
    
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Unauthorized' );
    }

    $tool_name = sanitize_text_field( $_POST['tool_name'] ?? '' );
    if ( ! $tool_name ) {
        wp_send_json_error( 'Tool name required' );
    }

    if ( ! class_exists( 'WPC_AI_Handler' ) ) {
        wp_send_json_error( 'AI not configured' );
    }

    $ai = new WPC_AI_Handler();
    $result = $ai->generate_tool_data( $tool_name );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }

    wp_send_json_success( $result );
}

/**
 * Render Tool Categories Sidebar Box
 */
function wpc_render_tool_categories_side( $post ) {
    wp_nonce_field( 'wpc_save_tool_categories', 'wpc_tool_categories_nonce' );

    $all_cats = get_terms( array(
        'taxonomy' => 'tool_category',
        'hide_empty' => false,
    ));

    $enable_associated = get_option( 'wpc_enable_tools_associated_items', '1' ) === '1';
    $associated_items = get_post_meta( $post->ID, '_wpc_tool_associated_item', true );
    if ( ! is_array( $associated_items ) ) {
        $associated_items = ! empty( $associated_items ) ? array( $associated_items ) : array();
    }

    $inherit_cats = get_post_meta( $post->ID, '_wpc_tool_inherit_categories', true );
    if ( $inherit_cats === '' ) $inherit_cats = '1'; // Default to enabled

    if ( ! $enable_associated ) {
        $inherit_cats = '0';
    }

    $items = get_posts( array(
        'post_type' => 'comparison_item',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC'
    ));

    $item_to_tool_cats = array();
    foreach ( $items as $item ) {
        $item_cats = wp_get_post_terms( $item->ID, 'comparison_category' );
        $cat_ids = array();
        if ( ! is_wp_error( $item_cats ) && ! empty( $item_cats ) ) {
            foreach ( $item_cats as $cat ) {
                $tool_term = get_term_by( 'slug', $cat->slug, 'tool_category' );
                if ( $tool_term ) {
                    $cat_ids[] = intval( $tool_term->term_id );
                }
            }
        }
        $item_to_tool_cats[ $item->ID ] = $cat_ids;
    }
    ?>
    <?php
    $all_comparison_cats = get_terms( array(
        'taxonomy' => 'comparison_category',
        'hide_empty' => false,
    ));
    $all_comparison_cat_slugs = array();
    if ( ! is_wp_error( $all_comparison_cats ) && ! empty( $all_comparison_cats ) ) {
        foreach ( $all_comparison_cats as $c_cat ) {
            $all_comparison_cat_slugs[] = $c_cat->slug;
        }
    }
    
    $item_term_ids = array();
    foreach ( $all_cats as $cat ) {
        if ( in_array( $cat->slug, $all_comparison_cat_slugs ) ) {
            $item_term_ids[] = intval( $cat->term_id );
        }
    }
    ?>
    <script>
    var wpcItemToToolCatsMap = <?php echo json_encode( $item_to_tool_cats ); ?>;
    var wpcComparisonItemCatIds = <?php echo json_encode( $item_term_ids ); ?>;
    </script>
    <style>
        .wpc-checkbox-list { 
            border: 1px solid #cbd5e1; 
            padding: 8px; 
            max-height: 150px; 
            overflow-y: auto; 
            background: #fff; 
            border-radius: 4px; 
        }
        .wpc-add-new-wrap { 
            margin-top: 5px; 
            display: flex; 
            gap: 5px; 
        }
        .wpc-label { 
            font-weight: 600; 
            display: block; 
            margin-bottom: 5px; 
            color: #334155; 
            font-size: 13px; 
        }
    </style>
    
    <?php if ( $enable_associated ) : ?>
    <div class="wpc-side-field" style="margin-bottom: 15px;">
        <label class="wpc-label"><?php _e( 'Associated Comparison Items', 'wp-comparison-builder' ); ?></label>
        <input type="text" id="wpc-associated-items-search" placeholder="Search items..." style="width:100%; margin-bottom:5px;" onkeyup="wpcFilterList('wpc-associated-items-search', 'wpc-associated-items-list')" />
        <div class="wpc-checkbox-list" id="wpc-associated-items-list" style="max-height: 120px;">
            <?php if ( ! empty( $items ) ) : ?>
                <?php foreach ( $items as $item ) : ?>
                    <label style="display:block; margin-bottom: 3px;">
                        <input type="checkbox" name="wpc_tool_associated_items[]" value="<?php echo esc_attr( $item->ID ); ?>" <?php checked( in_array( $item->ID, $associated_items ) ); ?> />
                        <?php echo esc_html( $item->post_title ); ?>
                    </label>
                <?php endforeach; ?>
            <?php else : ?>
                <p style="margin: 0; font-size: 12px; color: #64748b;"><?php _e( 'No comparison items found.', 'wp-comparison-builder' ); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="wpc-side-field" style="margin-bottom: 15px;">
        <label style="display: block; font-weight: 600; color: #334155; font-size: 13px;">
            <input type="checkbox" name="wpc_tool_inherit_categories" value="1" <?php checked( $inherit_cats, '1' ); ?> />
            <?php _e( 'Inherit categories from associated items', 'wp-comparison-builder' ); ?>
        </label>
    </div>
    <?php endif; ?>

    <div id="wpc-tool-cat-custom-fields">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
            <label class="wpc-label" style="margin: 0;"><?php _e( 'Select Categories', 'wp-comparison-builder' ); ?></label>
            <div>
                <button type="button" class="button button-link" onclick="wpcSelectAllToolTerms('wpc-tool-cat-list', true)" style="font-size: 11px; text-decoration: none;"><?php _e( 'Select All', 'wp-comparison-builder' ); ?></button>
                <button type="button" class="button button-link" onclick="wpcSelectAllToolTerms('wpc-tool-cat-list', false)" style="font-size: 11px; text-decoration: none; margin-left: 5px;"><?php _e( 'Clear All', 'wp-comparison-builder' ); ?></button>
            </div>
        </div>
        <input type="text" id="wpc-tool-cat-search" placeholder="Search categories..." style="width:100%; margin-bottom:5px;" onkeyup="wpcFilterList('wpc-tool-cat-search', 'wpc-tool-cat-list')" />
        
        <?php
        if ( metadata_exists( 'post', $post->ID, '_wpc_tool_custom_categories' ) ) {
            $current_cats = get_post_meta( $post->ID, '_wpc_tool_custom_categories', true );
            if ( ! is_array( $current_cats ) ) {
                $current_cats = array();
            }
        } else {
            // Fallback for legacy posts before multi-association update
            $old_source = get_post_meta( $post->ID, '_wpc_tool_category_source', true ) ?: 'custom';
            if ( 'custom' === $old_source ) {
                $current_cats = wp_get_post_terms( $post->ID, 'tool_category', array( 'fields' => 'ids' ) );
                if ( is_wp_error( $current_cats ) ) $current_cats = array();
            } else {
                $current_cats = array();
            }
        }


        $item_cat_slugs = array();
        if ( ! empty( $associated_items ) ) {
            foreach ( $associated_items as $item_id ) {
                $item_cats = wp_get_post_terms( $item_id, 'comparison_category' );
                if ( ! is_wp_error( $item_cats ) && ! empty( $item_cats ) ) {
                    foreach ( $item_cats as $cat ) {
                        $item_cat_slugs[] = $cat->slug;
                    }
                }
            }
        }
        $item_cat_slugs = array_unique( $item_cat_slugs );

        $active_cat_ids = $current_cats;
        ?>
        <div class="wpc-checkbox-list" id="wpc-tool-cat-list" style="max-height: 150px;">
            <?php if ( ! empty( $all_cats ) && ! is_wp_error( $all_cats ) ) : ?>
                <?php foreach ( $all_cats as $cat ) : ?>
                    <?php
                    $is_item_cat = in_array( $cat->slug, $all_comparison_cat_slugs );
                    $is_associated_item_cat = in_array( $cat->slug, $item_cat_slugs );
                    $label_style = '';
                    $is_checked = in_array( $cat->term_id, $current_cats );
                    if ( $is_item_cat ) {
                        if ( '1' !== $inherit_cats || ! $is_associated_item_cat ) {
                            $label_style = 'display: none;';
                            $is_checked = false;
                        }
                    }
                    ?>
                    <label style="<?php echo $label_style; ?> display:block; margin-bottom: 3px;" class="wpc-tool-cat-option" data-is-item="<?php echo $is_item_cat ? '1' : '0'; ?>">
                        <input type="checkbox" name="wpc_tool_category[]" value="<?php echo esc_attr( $cat->term_id ); ?>" <?php checked( $is_checked ); ?> onchange="wpcSyncToolPrimaryCats(this);" />
                        <?php echo esc_html( $cat->name ); ?>
                        <span class="wpc-delete-term" onclick="wpcDeleteToolTerm(event, <?php echo esc_attr( $cat->term_id ); ?>, 'tool_category', this)" style="color:#d63638; cursor:pointer; font-size:11px; float:right; padding: 2px;" title="Delete Term">❌</span>
                    </label>
                <?php endforeach; ?>
            <?php else : ?>
                <p style="margin: 0; font-size: 12px; color: #64748b;"><?php _e( 'No categories found.', 'wp-comparison-builder' ); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="wpc-add-new-wrap">
            <input type="text" id="new-wpc-tool-category" placeholder="New Category Name" style="flex: 1; padding: 4px;" />
            <button type="button" class="button" onclick="wpcAddToolTerm('tool_category')"><?php _e( 'Add', 'wp-comparison-builder' ); ?></button>
        </div>

        <div style="margin-top: 15px;">
            <label class="wpc-label"><?php _e( 'Primary Display Categories (Max 2)', 'wp-comparison-builder' ); ?></label>
            <?php 
            $primary_cats = get_post_meta( $post->ID, '_wpc_primary_cats', true ) ?: [];
            ?>
            <div class="wpc-checkbox-list" id="wpc-tool-primary-cat-list" style="height: 80px;">
                 <?php if ( ! empty( $all_cats ) && ! is_wp_error( $all_cats ) ) : ?>
                    <?php foreach ( $all_cats as $cat ) : ?>
                        <?php 
                        $is_selected = in_array( $cat->term_id, $active_cat_ids );
                        $style = $is_selected ? 'display:block;' : 'display:none;';
                        ?>
                        <label style="<?php echo $style; ?> margin-bottom: 3px;" data-term-id="<?php echo esc_attr( $cat->term_id ); ?>" class="wpc-tool-primary-option">
                            <input type="checkbox" name="wpc_tool_primary_cats[]" value="<?php echo esc_attr( $cat->term_id ); ?>" <?php checked( in_array( $cat->term_id, $primary_cats ) ); ?> />
                            <?php echo esc_html( $cat->name ); ?>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if ( ! empty( $items ) ) : ?>
                    <?php foreach ( $items as $item ) : ?>
                        <?php
                        $is_selected = in_array( $item->ID, $associated_items );
                        $style = $is_selected ? 'display:block;' : 'display:none;';
                        ?>
                        <label style="<?php echo $style; ?> margin-bottom: 3px;" data-item-id="<?php echo esc_attr( $item->ID ); ?>" class="wpc-tool-primary-item-option">
                            <input type="checkbox" name="wpc_tool_primary_cats[]" value="<?php echo esc_attr( $item->ID ); ?>" <?php checked( in_array( $item->ID, $primary_cats ) ); ?> />
                            <?php echo esc_html( $item->post_title ); ?>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    function wpcSelectAllToolTerms(listId, check) {
        jQuery('#' + listId + ' input[type="checkbox"]').each(function() {
            this.checked = check;
            jQuery(this).trigger('change');
        });
    }

    if (typeof wpcFilterList !== 'function') {
        window.wpcFilterList = function(inputId, listId) {
            var input = document.getElementById(inputId);
            var filter = input.value.toLowerCase();
            var list = document.getElementById(listId);
            var labels = list.getElementsByTagName('label');

            for (var i = 0; i < labels.length; i++) {
                var txtValue = labels[i].textContent || labels[i].innerText;
                if (txtValue.toLowerCase().indexOf(filter) > -1) {
                    labels[i].style.display = "block";
                } else {
                    labels[i].style.display = "none";
                }
            }
        };
    }

    function wpcUpdateSelectCategories() {
        var checkedItemIds = [];
        jQuery('#wpc-associated-items-list input[name="wpc_tool_associated_items[]"]:checked').each(function() {
            checkedItemIds.push(parseInt(jQuery(this).val()));
        });

        var itemCatIds = [];
        if (typeof wpcItemToToolCatsMap !== 'undefined') {
            checkedItemIds.forEach(function(itemId) {
                var cats = wpcItemToToolCatsMap[itemId] || [];
                cats.forEach(function(catId) {
                    if (itemCatIds.indexOf(catId) === -1) {
                        itemCatIds.push(catId);
                    }
                });
            });
        }

        var inheritChecked = jQuery('input[name="wpc_tool_inherit_categories"]').is(':checked');

        jQuery('#wpc-tool-cat-list .wpc-tool-cat-option').each(function() {
            var checkbox = jQuery(this).find('input[name="wpc_tool_category[]"]');
            var catId = parseInt(checkbox.val());
            var isItemCat = (typeof wpcComparisonItemCatIds !== 'undefined') && wpcComparisonItemCatIds.indexOf(catId) !== -1;
            var isAssociatedCat = itemCatIds.indexOf(catId) !== -1;

            // Update data attribute dynamically
            jQuery(this).attr('data-is-item', isItemCat ? '1' : '0');

            if (isItemCat) {
                if (inheritChecked && isAssociatedCat) {
                    jQuery(this).show();
                } else {
                    jQuery(this).hide();
                    if (checkbox.is(':checked')) {
                        checkbox.prop('checked', false).trigger('change');
                    }
                }
            } else {
                jQuery(this).show();
            }
        });
    }

    function wpcUpdatePrimaryCategories() {
        var activeCatIds = [];
        jQuery('#wpc-tool-cat-list input[name="wpc_tool_category[]"]:checked').each(function() {
            activeCatIds.push(parseInt(jQuery(this).val()));
        });

        jQuery('#wpc-tool-primary-cat-list .wpc-tool-primary-option').each(function() {
            var termId = parseInt(jQuery(this).attr('data-term-id'));
            if (activeCatIds.indexOf(termId) !== -1) {
                jQuery(this).show();
            } else {
                jQuery(this).hide();
                var primaryCheckbox = jQuery(this).find('input[type="checkbox"]');
                if (primaryCheckbox.is(':checked')) {
                    primaryCheckbox.prop('checked', false).trigger('change');
                }
            }
        });

        var checkedItemIds = [];
        jQuery('#wpc-associated-items-list input[name="wpc_tool_associated_items[]"]:checked').each(function() {
            checkedItemIds.push(parseInt(jQuery(this).val()));
        });

        jQuery('#wpc-tool-primary-cat-list .wpc-tool-primary-item-option').each(function() {
            var itemId = parseInt(jQuery(this).attr('data-item-id'));
            if (checkedItemIds.indexOf(itemId) !== -1) {
                jQuery(this).show();
            } else {
                jQuery(this).hide();
                var primaryCheckbox = jQuery(this).find('input[type="checkbox"]');
                if (primaryCheckbox.is(':checked')) {
                    primaryCheckbox.prop('checked', false).trigger('change');
                }
            }
        });
    }

    function wpcSyncToolPrimaryCats(checkbox) {
        wpcUpdatePrimaryCategories();
    }

    jQuery(document).ready(function() {
        jQuery(document).on('change', 'input[name="wpc_tool_associated_items[]"]', function() {
            wpcUpdateSelectCategories();
            wpcUpdatePrimaryCategories();
        });
        jQuery(document).on('change', 'input[name="wpc_tool_inherit_categories"]', function() {
            wpcUpdateSelectCategories();
            wpcUpdatePrimaryCategories();
        });
        wpcUpdateSelectCategories();
        wpcUpdatePrimaryCategories();
    });

    jQuery(document).on('change', 'input[name="wpc_tool_primary_cats[]"]', function() {
        var checked = jQuery('input[name="wpc_tool_primary_cats[]"]:checked');
        if (checked.length > 2) {
            this.checked = false;
            alert('You can only select up to 2 primary categories.');
        }
    });
    </script>
    <?php
}

/**
 * Render Tool Tags Sidebar Box
 */
function wpc_render_tool_tags_side( $post ) {
    wp_nonce_field( 'wpc_save_tool_tags', 'wpc_tool_tags_nonce' );

    $all_tags = get_terms( array(
        'taxonomy' => 'tool_tag',
        'hide_empty' => false,
    ));

    $enable_associated = get_option( 'wpc_enable_tools_associated_items', '1' ) === '1';
    $inherit_tags = get_post_meta( $post->ID, '_wpc_tool_inherit_tags', true );
    if ( $inherit_tags === '' ) $inherit_tags = '1'; // Default to enabled

    if ( ! $enable_associated ) {
        $inherit_tags = '0';
    }

    $items = get_posts( array(
        'post_type' => 'comparison_item',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ));

    $item_to_tool_tags = array();
    foreach ( $items as $item ) {
        $item_tags = wp_get_post_terms( $item->ID, 'comparison_feature' );
        $tag_ids = array();
        if ( ! is_wp_error( $item_tags ) && ! empty( $item_tags ) ) {
            foreach ( $item_tags as $tag ) {
                $tool_term = get_term_by( 'slug', $tag->slug, 'tool_tag' );
                if ( $tool_term ) {
                    $tag_ids[] = intval( $tool_term->term_id );
                }
            }
        }
        $item_to_tool_tags[ $item->ID ] = $tag_ids;
    }
    ?>
    <?php
    $all_comparison_tags = get_terms( array(
        'taxonomy' => 'comparison_feature',
        'hide_empty' => false,
    ));
    $all_comparison_tag_slugs = array();
    if ( ! is_wp_error( $all_comparison_tags ) && ! empty( $all_comparison_tags ) ) {
        foreach ( $all_comparison_tags as $c_tag ) {
            $all_comparison_tag_slugs[] = $c_tag->slug;
        }
    }
    
    $item_tag_term_ids = array();
    foreach ( $all_tags as $tag ) {
        if ( in_array( $tag->slug, $all_comparison_tag_slugs ) ) {
            $item_tag_term_ids[] = intval( $tag->term_id );
        }
    }
    ?>
    <script>
    var wpcItemToToolTagsMap = <?php echo json_encode( $item_to_tool_tags ); ?>;
    var wpcComparisonItemTagIds = <?php echo json_encode( $item_tag_term_ids ); ?>;
    </script>
    <?php if ( $enable_associated ) : ?>
    <div class="wpc-side-field" style="margin-bottom: 15px;">
        <label style="display: block; font-weight: 600; color: #334155; font-size: 13px;">
            <input type="checkbox" name="wpc_tool_inherit_tags" value="1" <?php checked( $inherit_tags, '1' ); ?> />
            <?php _e( 'Inherit tags from associated items', 'wp-comparison-builder' ); ?>
        </label>
    </div>
    <?php endif; ?>

    <div id="wpc-tool-tag-custom-fields">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
            <label class="wpc-label" style="margin: 0;"><?php _e( 'Select Tags', 'wp-comparison-builder' ); ?></label>
            <div>
                <button type="button" class="button button-link" onclick="wpcSelectAllToolTerms('wpc-tool-tag-list', true)" style="font-size: 11px; text-decoration: none;"><?php _e( 'Select All', 'wp-comparison-builder' ); ?></button>
                <button type="button" class="button button-link" onclick="wpcSelectAllToolTerms('wpc-tool-tag-list', false)" style="font-size: 11px; text-decoration: none; margin-left: 5px;"><?php _e( 'Clear All', 'wp-comparison-builder' ); ?></button>
            </div>
        </div>
        <input type="text" id="wpc-tool-tag-search" placeholder="Search tags..." style="width:100%; margin-bottom:5px;" onkeyup="wpcFilterList('wpc-tool-tag-search', 'wpc-tool-tag-list')" />
        
        <?php
        if ( metadata_exists( 'post', $post->ID, '_wpc_tool_custom_tags' ) ) {
            $current_tags = get_post_meta( $post->ID, '_wpc_tool_custom_tags', true );
            if ( ! is_array( $current_tags ) ) {
                $current_tags = array();
            }
        } else {
            // Fallback for legacy posts before multi-association update
            $old_source = get_post_meta( $post->ID, '_wpc_tool_tag_source', true ) ?: 'custom';
            if ( 'custom' === $old_source ) {
                $current_tags = wp_get_post_terms( $post->ID, 'tool_tag', array( 'fields' => 'ids' ) );
                if ( is_wp_error( $current_tags ) ) $current_tags = array();
            } else {
                $current_tags = array();
            }
        }


        $item_tag_slugs = array();
        if ( ! empty( $associated_items ) ) {
            foreach ( $associated_items as $item_id ) {
                $item_tags = wp_get_post_terms( $item_id, 'comparison_feature' );
                if ( ! is_wp_error( $item_tags ) && ! empty( $item_tags ) ) {
                    foreach ( $item_tags as $tag ) {
                        $item_tag_slugs[] = $tag->slug;
                    }
                }
            }
        }
        $item_tag_slugs = array_unique( $item_tag_slugs );

        $active_tag_ids = $current_tags;
        ?>
        <div class="wpc-checkbox-list" id="wpc-tool-tag-list" style="max-height: 150px;">
            <?php if ( ! empty( $all_tags ) && ! is_wp_error( $all_tags ) ) : ?>
                <?php foreach ( $all_tags as $tag ) : ?>
                    <?php
                    $is_item_tag = in_array( $tag->slug, $all_comparison_tag_slugs );
                    $is_associated_item_tag = in_array( $tag->slug, $item_tag_slugs );
                    $label_style = '';
                    $is_checked = in_array( $tag->term_id, $current_tags );
                    if ( $is_item_tag ) {
                        if ( '1' !== $inherit_tags || ! $is_associated_item_tag ) {
                            $label_style = 'display: none;';
                            $is_checked = false;
                        }
                    }
                    ?>
                    <label style="<?php echo $label_style; ?> display:block; margin-bottom: 3px;" class="wpc-tool-tag-option" data-is-item="<?php echo $is_item_tag ? '1' : '0'; ?>">
                        <input type="checkbox" name="wpc_tool_tag[]" value="<?php echo esc_attr( $tag->term_id ); ?>" <?php checked( $is_checked ); ?> onchange="wpcSyncToolPrimaryTags(this);" />
                        <?php echo esc_html($tag->name); ?>
                        <span class="wpc-delete-term" onclick="wpcDeleteToolTerm(event, <?php echo esc_attr( $tag->term_id ); ?>, 'tool_tag', this)" style="color:#d63638; cursor:pointer; font-size:11px; float:right; padding: 2px;" title="Delete Term">❌</span>
                    </label>
                <?php endforeach; ?>
            <?php else : ?>
                <p style="margin: 0; font-size: 12px; color: #64748b;"><?php _e( 'No tags found.', 'wp-comparison-builder' ); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="wpc-add-new-wrap">
            <input type="text" id="new-wpc-tool-tag" placeholder="New Tag Name" style="flex: 1; padding: 4px;" />
            <button type="button" class="button" onclick="wpcAddToolTerm('tool_tag')"><?php _e( 'Add', 'wp-comparison-builder' ); ?></button>
        </div>

        <div style="margin-top: 15px;">
            <label class="wpc-label"><?php _e( 'Primary Display Tags (Max 3)', 'wp-comparison-builder' ); ?></label>
            <?php 
            $primary_tags = get_post_meta( $post->ID, '_wpc_primary_features', true ) ?: [];
            ?>
            <div class="wpc-checkbox-list" id="wpc-tool-primary-tag-list" style="height: 80px;">
                 <?php if ( ! empty( $all_tags ) && ! is_wp_error( $all_tags ) ) : ?>
                    <?php foreach ( $all_tags as $tag ) : ?>
                        <?php 
                        $is_selected = in_array( $tag->term_id, $active_tag_ids );
                        $style = $is_selected ? 'display:block;' : 'display:none;';
                        ?>
                        <label style="<?php echo $style; ?> margin-bottom: 3px;" data-term-id="<?php echo esc_attr( $tag->term_id ); ?>" class="wpc-tool-primary-tag-option">
                            <input type="checkbox" name="wpc_tool_primary_tags[]" value="<?php echo esc_attr( $tag->term_id ); ?>" <?php checked( in_array( $tag->term_id, $primary_tags ) ); ?> />
                            <?php echo esc_html( $tag->name ); ?>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    function wpcUpdateSelectTags() {
        var checkedItemIds = [];
        jQuery('#wpc-associated-items-list input[name="wpc_tool_associated_items[]"]:checked').each(function() {
            checkedItemIds.push(parseInt(jQuery(this).val()));
        });

        var itemTagIds = [];
        if (typeof wpcItemToToolTagsMap !== 'undefined') {
            checkedItemIds.forEach(function(itemId) {
                var tags = wpcItemToToolTagsMap[itemId] || [];
                tags.forEach(function(tagId) {
                    if (itemTagIds.indexOf(tagId) === -1) {
                        itemTagIds.push(tagId);
                    }
                });
            });
        }

        var inheritChecked = jQuery('input[name="wpc_tool_inherit_tags"]').is(':checked');

        jQuery('#wpc-tool-tag-list .wpc-tool-tag-option').each(function() {
            var checkbox = jQuery(this).find('input[name="wpc_tool_tag[]"]');
            var tagId = parseInt(checkbox.val());
            var isItemTag = (typeof wpcComparisonItemTagIds !== 'undefined') && wpcComparisonItemTagIds.indexOf(tagId) !== -1;
            var isAssociatedTag = itemTagIds.indexOf(tagId) !== -1;

            jQuery(this).attr('data-is-item', isItemTag ? '1' : '0');

            if (isItemTag) {
                if (inheritChecked && isAssociatedTag) {
                    jQuery(this).show();
                } else {
                    jQuery(this).hide();
                    if (checkbox.is(':checked')) {
                        checkbox.prop('checked', false).trigger('change');
                    }
                }
            } else {
                jQuery(this).show();
            }
        });
    }

    function wpcUpdatePrimaryTags() {
        var activeTagIds = [];
        jQuery('#wpc-tool-tag-list input[name="wpc_tool_tag[]"]:checked').each(function() {
            activeTagIds.push(parseInt(jQuery(this).val()));
        });

        jQuery('#wpc-tool-primary-tag-list .wpc-tool-primary-tag-option').each(function() {
            var termId = parseInt(jQuery(this).attr('data-term-id'));
            if (activeTagIds.indexOf(termId) !== -1) {
                jQuery(this).show();
            } else {
                jQuery(this).hide();
                var primaryCheckbox = jQuery(this).find('input[type="checkbox"]');
                if (primaryCheckbox.is(':checked')) {
                    primaryCheckbox.prop('checked', false).trigger('change');
                }
            }
        });
    }

    function wpcSyncToolPrimaryTags(checkbox) {
        wpcUpdatePrimaryTags();
    }

    jQuery(document).ready(function() {
        jQuery(document).on('change', 'input[name="wpc_tool_associated_items[]"]', function() {
            wpcUpdateSelectTags();
            wpcUpdatePrimaryTags();
        });
        jQuery(document).on('change', 'input[name="wpc_tool_inherit_tags"]', function() {
            wpcUpdateSelectTags();
            wpcUpdatePrimaryTags();
        });
        wpcUpdateSelectTags();
        wpcUpdatePrimaryTags();
    });

    jQuery(document).on('change', 'input[name="wpc_tool_primary_tags[]"]', function() {
        var checked = jQuery('input[name="wpc_tool_primary_tags[]"]:checked');
        if (checked.length > 3) {
            this.checked = false;
            alert('You can only select up to 3 primary tags.');
        }
    });

    function wpcDeleteToolTerm(event, term_id, taxonomy, element) {
        event.preventDefault();
        event.stopPropagation();
        
        if (!confirm('<?php _e('Are you sure you want to permanently delete this term?', 'wp-comparison-builder'); ?>')) {
            return;
        }

        jQuery.post(ajaxurl, {
            action: 'wpc_delete_term',
            term_id: term_id,
            taxonomy: taxonomy,
            _ajax_nonce: '<?php echo wp_create_nonce('wpc_add_term_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                // Remove the label from the UI
                jQuery(element).closest('label').remove();
                
                // Also remove from primary tags/categories list if it exists
                if (taxonomy === 'tool_category') {
                    jQuery('#wpc-tool-primary-cat-list .wpc-tool-primary-option[data-term-id="' + term_id + '"]').remove();
                    wpcUpdatePrimaryCategories();
                } else {
                    jQuery('#wpc-tool-primary-tag-list .wpc-tool-primary-tag-option[data-term-id="' + term_id + '"]').remove();
                    wpcUpdatePrimaryTags();
                }
            } else {
                alert('<?php _e('Error deleting term: ', 'wp-comparison-builder'); ?>' + (response.data || 'Unknown error'));
            }
        });
    }

    function wpcAddToolTerm(taxonomy) {
        var inputId = taxonomy === 'tool_category' ? 'new-wpc-tool-category' : 'new-wpc-tool-tag';
        var listId = taxonomy === 'tool_category' ? 'wpc-tool-cat-list' : 'wpc-tool-tag-list';
        var input = document.getElementById(inputId);
        var name = input.value;
        
        if (!name) return;

        jQuery.post(ajaxurl, {
            action: 'wpc_add_term',
            taxonomy: taxonomy,
            term_name: name,
            _ajax_nonce: '<?php echo wp_create_nonce('wpc_add_term_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                var term = response.data;
                var html = '';
                if (taxonomy === 'tool_category') {
                     html = '<label style="display:block; margin-bottom: 3px;"><input type="checkbox" name="wpc_tool_category[]" value="' + term.term_id + '" checked onchange="wpcSyncToolPrimaryCats(this)" /> ' + term.name + ' <span class="wpc-delete-term" onclick="wpcDeleteToolTerm(event, ' + term.term_id + ', \'tool_category\', this)" style="color:#d63638; cursor:pointer; font-size:11px; float:right; padding: 2px;" title="Delete Term">❌</span></label>';
                     var primaryHtml = '<label style="display:none; margin-bottom: 3px;" data-term-id="' + term.term_id + '" class="wpc-tool-primary-option"><input type="checkbox" name="wpc_tool_primary_cats[]" value="' + term.term_id + '" /> ' + term.name + '</label>';
                     jQuery('#wpc-tool-primary-cat-list').append(primaryHtml);
                } else {
                     html = '<label style="display:block; margin-bottom: 3px;"><input type="checkbox" name="wpc_tool_tag[]" value="' + term.term_id + '" checked onchange="wpcSyncToolPrimaryTags(this)" /> ' + term.name + ' <span class="wpc-delete-term" onclick="wpcDeleteToolTerm(event, ' + term.term_id + ', \'tool_tag\', this)" style="color:#d63638; cursor:pointer; font-size:11px; float:right; padding: 2px;" title="Delete Term">❌</span></label>';
                     var primaryHtml = '<label style="display:none; margin-bottom: 3px;" data-term-id="' + term.term_id + '" class="wpc-tool-primary-tag-option"><input type="checkbox" name="wpc_tool_primary_tags[]" value="' + term.term_id + '" /> ' + term.name + '</label>';
                     jQuery('#wpc-tool-primary-tag-list').append(primaryHtml);
                }
                
                var list = document.getElementById(listId);
                var p = list.querySelector('p');
                if (p) p.remove();
                
                jQuery('#' + listId).append(html);
                
                // Trigger primary sync
                if (taxonomy === 'tool_category') {
                    wpcSyncToolPrimaryCats({value: term.term_id, checked: true});
                } else {
                    wpcSyncToolPrimaryTags({value: term.term_id, checked: true});
                }
                
                input.value = '';
            } else {
                alert('Error adding term: ' + (response.data || 'Unknown error'));
            }
        });
    }
    </script>
    <?php
}

/**
 * Synchronize taxonomy terms and primary fields of a tool from its parent item
 */
function wpc_sync_tool_taxonomies( $tool_id ) {
    // 1. Sync Categories
    if ( metadata_exists( 'post', $tool_id, '_wpc_tool_custom_categories' ) ) {
        $custom_cat_ids = get_post_meta( $tool_id, '_wpc_tool_custom_categories', true );
        if ( ! is_array( $custom_cat_ids ) ) {
            $custom_cat_ids = array();
        }
    } else {
        $custom_cat_ids = wp_get_post_terms( $tool_id, 'tool_category', array( 'fields' => 'ids' ) );
        if ( is_wp_error( $custom_cat_ids ) ) $custom_cat_ids = array();
    }
    wp_set_post_terms( $tool_id, $custom_cat_ids, 'tool_category' );

    // Sync primary categories
    $primary_cats = get_post_meta( $tool_id, '_wpc_primary_cats', true ) ?: array();
    $associated_items = get_post_meta( $tool_id, '_wpc_tool_associated_item', true );
    if ( ! is_array( $associated_items ) ) {
        $associated_items = ! empty( $associated_items ) ? array( $associated_items ) : array();
    }
    $valid_primary_ids = array_merge( $custom_cat_ids, $associated_items );
    $primary_cats = array_intersect( $primary_cats, $valid_primary_ids );
    $primary_cats = array_slice( array_unique( $primary_cats ), 0, 2 );
    update_post_meta( $tool_id, '_wpc_primary_cats', $primary_cats );

    // 2. Sync Tags
    if ( metadata_exists( 'post', $tool_id, '_wpc_tool_custom_tags' ) ) {
        $custom_tag_ids = get_post_meta( $tool_id, '_wpc_tool_custom_tags', true );
        if ( ! is_array( $custom_tag_ids ) ) {
            $custom_tag_ids = array();
        }
    } else {
        $custom_tag_ids = wp_get_post_terms( $tool_id, 'tool_tag', array( 'fields' => 'ids' ) );
        if ( is_wp_error( $custom_tag_ids ) ) $custom_tag_ids = array();
    }
    wp_set_post_terms( $tool_id, $custom_tag_ids, 'tool_tag' );

    // Sync primary tags
    $primary_tags = get_post_meta( $tool_id, '_wpc_primary_features', true ) ?: array();
    $primary_tags = array_intersect( $primary_tags, $custom_tag_ids );
    $primary_tags = array_slice( array_unique( $primary_tags ), 0, 3 );
    update_post_meta( $tool_id, '_wpc_primary_features', $primary_tags );
}

/**
 * Propagate Comparison Item changes to associated recommended tools
 */
function wpc_sync_tools_on_item_save( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( 'comparison_item' !== get_post_type( $post_id ) ) return;

    $tools = get_posts( array(
        'post_type' => 'comparison_tool',
        'posts_per_page' => -1,
        'post_status' => 'any'
    ));

    foreach ( $tools as $tool ) {
        $associated_items = get_post_meta( $tool->ID, '_wpc_tool_associated_item', true );
        if ( ! is_array( $associated_items ) ) {
            $associated_items = ! empty( $associated_items ) ? array( $associated_items ) : array();
        }
        if ( in_array( $post_id, $associated_items ) ) {
            wpc_sync_tool_taxonomies( $tool->ID );
        }
    }
}
add_action( 'save_post', 'wpc_sync_tools_on_item_save' );
