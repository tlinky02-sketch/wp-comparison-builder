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
}

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
    
    // Dual-Write: Post Meta (Backup)
    update_post_meta( $post_id, '_tool_badge', $badge );
    update_post_meta( $post_id, '_tool_link', $link );
    update_post_meta( $post_id, '_tool_button_text', $button_text );
    update_post_meta( $post_id, '_wpc_tool_short_description', $short_desc );
    update_post_meta( $post_id, '_wpc_tool_rating', $rating );
    update_post_meta( $post_id, '_wpc_tool_features', $features_raw );
    
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
            'pricing_plans'     => $plans
        ];
        
        $db->update_tool( $post_id, $data );
    }
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
