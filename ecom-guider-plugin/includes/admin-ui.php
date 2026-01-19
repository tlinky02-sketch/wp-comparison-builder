<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add Meta Boxes
 */
function wpc_add_meta_boxes() {
    add_meta_box(
        'wpc_details',
        __( 'Item Details', 'wp-comparison-builder' ),
        'wpc_render_meta_box',
        'comparison_item',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'wpc_add_meta_boxes' );

/**
 * Enqueue Admin Scripts
 */
function wpc_admin_ui_scripts() {
    global $post_type;
    if ( 'comparison_item' === $post_type || 'comparison_tool' === $post_type || 'comparison_list' === $post_type ) {
        wp_enqueue_media();
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        
        // Enqueue jQuery UI Sortable for drag-and-drop functionality
        wp_enqueue_script( 'jquery-ui-sortable' );
    }
}
add_action( 'admin_enqueue_scripts', 'wpc_admin_ui_scripts' );

/**
 * Remove Default Taxonomy Meta Boxes (To avoid confusion)
 */
function wpc_remove_tax_meta_boxes() {
    remove_meta_box( 'comparison_categorydiv', 'comparison_item', 'side' );
    remove_meta_box( 'tagsdiv-comparison_feature', 'comparison_item', 'side' );
}
add_action( 'admin_menu', 'wpc_remove_tax_meta_boxes' );

/**
 * Render Meta Box Content
 */
function wpc_render_meta_box( $post ) {
    // Nonce field for security
    wp_nonce_field( 'wpc_save_details', 'wpc_nonce' );

    // Get current values
    $price = get_post_meta( $post->ID, '_wpc_price', true );
    $rating = get_post_meta( $post->ID, '_wpc_rating', true );
    $pros = get_post_meta( $post->ID, '_wpc_pros', true ); 
    $cons = get_post_meta( $post->ID, '_wpc_cons', true );
    
    // Get Terms
    $current_cats = wp_get_post_terms( $post->ID, 'comparison_category', array( 'fields' => 'ids' ) );
    $current_cat = ! empty( $current_cats ) ? $current_cats[0] : '';
    
    $current_features = wp_get_post_terms( $post->ID, 'comparison_feature', array( 'fields' => 'ids' ) );

    // Get all available terms for UI
    $all_cats = get_terms( array( 'taxonomy' => 'comparison_category', 'hide_empty' => false ) );
    $all_features = get_terms( array( 'taxonomy' => 'comparison_feature', 'hide_empty' => false ) );
    ?>
    
    <style>
        .wpc-tabs-wrapper { display: flex; flex-direction: column; background: #fff; border: 1px solid #ddd; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .wpc-tab-nav { display: flex; background: #f3f4f6; border-bottom: 1px solid #ddd; margin: 0; padding: 0; list-style: none; }
        .wpc-tab-nav li { margin: 0; padding: 12px 20px; cursor: pointer; font-weight: 600; font-size: 13px; color: #64748b; border-right: 1px solid #eee; transition: all 0.2s; border-bottom: 2px solid transparent; }
        .wpc-tab-nav li:hover { background: #f9fafb; color: #3b82f6; }
        .wpc-tab-nav li.active { background: #fff; color: #2563eb; border-bottom: 2px solid #2563eb; margin-bottom: -1px; }
        .wpc-tab-content { display: none; padding: 20px; }
        .wpc-tab-content.active { display: block; }

        .wpc-row { display: flex; gap: 20px; margin-bottom: 15px; }
        .wpc-col { flex: 1; }
        .wpc-label { font-weight: bold; display: block; margin-bottom: 5px; color: #334155; font-size: 13px; }
        .wpc-input { width: 100%; border: 1px solid #cbd5e1; border-radius: 4px; padding: 6px 10px; }
        .wpc-checkbox-list, .wpc-radio-list { 
            border: 1px solid #e2e8f0; padding: 10px; max-height: 200px; overflow-y: auto; background: #fff; border-radius: 4px; 
        }
        .wpc-add-new-wrap { margin-top: 5px; display: flex; gap: 5px; }
        
        .wpc-section-title { font-size: 14px; font-weight: 700; color: #0f172a; margin: 0 0 15px 0; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0; }
    </style>
    
    <script>
    // Global Admin Utilities
    var wpcAdmin = {
        toast: function(msg, type) {
            var isError = type === 'error';
            var bg = isError ? '#dc2626' : '#10b981';
            var icon = isError ? '⚠' : '✓';
            var toast = document.createElement('div');
            toast.style.cssText = 'position:fixed;bottom:30px;right:30px;background:' + bg + ';color:white;padding:12px 20px;border-radius:8px;font-weight:500;z-index:100000;max-width:400px;box-shadow:0 4px 12px rgba(0,0,0,0.15);display:flex;align-items:center;gap:10px;animation:wpc-slide-up 0.3s ease-out;';
            toast.innerHTML = '<span style="font-size:18px;">' + icon + '</span> <span>' + msg + '</span>';
            document.body.appendChild(toast);
            setTimeout(function() { 
                toast.style.opacity = '0'; 
                setTimeout(function(){ toast.remove(); }, 300);
            }, isError ? 5000 : 3000);
        },
        
        confirm: function(title, message, onConfirm, confirmText, confirmColor) {
            // Remove existing modal if any
            var existing = document.getElementById('wpc-confirm-modal');
            if (existing) existing.remove();
            
            var modal = document.createElement('div');
            modal.id = 'wpc-confirm-modal';
            modal.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:99999;display:flex;align-items:center;justify-content:center;';
            modal.innerHTML = `
                <div style="background:white;padding:25px;border-radius:12px;width:90%;max-width:450px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);transform:scale(0.95);opacity:0;transition:all 0.2s;">
                    <h3 style="margin-top:0;font-size:18px;color:#1f2937;">${title}</h3>
                    <p style="color:#4b5563;line-height:1.5;margin-bottom:25px;">${message}</p>
                    <div style="display:flex;justify-content:flex-end;gap:10px;">
                        <button type="button" class="button" onclick="document.getElementById('wpc-confirm-modal').remove()">Cancel</button>
                        <button type="button" class="button button-primary" id="wpc-confirm-btn" style="background:${confirmColor || '#dc2626'};border-color:${confirmColor || '#dc2626'};">${confirmText || 'Confirm'}</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Animation
            requestAnimationFrame(() => {
                modal.querySelector('div').style.transform = 'scale(1)';
                modal.querySelector('div').style.opacity = '1';
            });
            
            document.getElementById('wpc-confirm-btn').addEventListener('click', function() {
                onConfirm();
                modal.remove();
            });
        },
        
        loading: function(btn, text) {
            if (!btn) return;
            btn.dataset.originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner is-active" style="float:none;margin:0 5px 0 0;"></span> ' + text;
        },
        
        reset: function(btn) {
            if (!btn) return;
            btn.disabled = false;
            if (btn.dataset.originalText) {
                btn.innerHTML = btn.dataset.originalText;
            }
        }
    };
    
    // Legacy support
    function wpcShowToast(msg, isError) {
        wpcAdmin.toast(msg, isError ? 'error' : 'success');
    }
    </script>
    <style>
        @keyframes wpc-slide-up { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>

    <div class="wpc-details-wrap wpc-tabs-wrapper">
        <?php
        // Check if AI is configured
        $ai_configured = false;
        $ai_profiles = [];
        if ( class_exists( 'WPC_AI_Handler' ) ) {
            $ai_profiles = WPC_AI_Handler::get_profiles();
            $ai_configured = ! empty( $ai_profiles );
        }
        
        // Fetch Admin Layout Preference
        $admin_layout = get_option( 'wpc_admin_layout_style', 'topbar' );
        
        // Find default profile name for display
        $default_profile_name = '';
        foreach ( $ai_profiles as $p ) {
            if ( ! empty( $p['is_default'] ) ) {
                $default_profile_name = $p['name'];
                break;
            }
        }
        if ( empty( $default_profile_name ) && ! empty( $ai_profiles ) ) {
            $default_profile_name = $ai_profiles[0]['name'];
        }
        
        // Prepare Global Defaults for JS Reset
        $global_defaults = array(
            'primary' => get_option('wpc_primary_color', '#6366f1'),
            'accent' => get_option('wpc_accent_color', '#818cf8'),
            'border' => get_option('wpc_card_border_color', '#e2e8f0'), // Note: Option name might differ, using fallback
            'coupon_bg' => get_option('wpc_color_coupon_bg', '#fef3c7'), // Assuming this option exists
            'coupon_text' => get_option('wpc_color_coupon_text', '#92400e'), // Assuming this option exists
            
            'pros_bg' => get_option('wpc_color_pros_bg', '#f0fdf4'),
            'pros_text' => get_option('wpc_color_pros_text', '#166534'),
            'cons_bg' => get_option('wpc_color_cons_bg', '#fef2f2'),
            'cons_text' => get_option('wpc_color_cons_text', '#991b1b')
        );
        ?>
        <script>
            var wpcGlobalDefaults = <?php echo json_encode($global_defaults); ?>;
            var wpcCurrentPostId = <?php echo isset($post) ? $post->ID : 0; ?>;
        </script>
        
        <!-- AI Assistant Panel - Always Visible -->
        <div id="wpc-ai-assistant" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; padding: 15px 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; margin-bottom: 10px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 24px;">&#x1F916;</span>
                    <div>
                        <strong style="font-size: 14px;">AI Assistant</strong>
                        <?php if ( $ai_configured ) : ?>
                        <span style="opacity: 0.8; font-size: 12px; margin-left: 8px;"><?php echo count( $ai_profiles ); ?> Profile(s) Available</span>
                        <?php else : ?>
                        <span style="opacity: 0.8; font-size: 12px; margin-left: 8px;">Not Configured</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ( $ai_configured ) : ?>
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <input type="text" id="wpc-ai-product-name" value="<?php echo esc_attr( $post->post_title ); ?>" placeholder="Enter the full product/service name (e.g. HostGator)" style="height: 36px; padding: 0 12px; border-radius: 6px; border: none; min-width: 250px; color: #1e293b; line-height: 20px;" />
                    
                    <div style="position: relative;">
                        <span class="dashicons dashicons-arrow-down-alt2" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); pointer-events: none; color: white; opacity: 0.8; font-size: 14px;"></span>
                        <select id="wpc-ai-item-profile" style="height: 36px; padding: 0 28px 0 12px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.2); color: white; appearance: none; -webkit-appearance: none; cursor: pointer; min-width: 150px;">
                            <option value="" style="color:#000;"><?php _e( 'Select Profile', 'wp-comparison-builder' ); ?></option>
                            <?php
                            $profiles = WPC_AI_Handler::get_profiles();
                            foreach ( $profiles as $prof ) :
                            ?>
                            <option value="<?php echo esc_attr( $prof['id'] ); ?>" style="color:#000;">
                                <?php echo esc_html( $prof['name'] ); ?> (<?php echo ucfirst( $prof['provider'] ); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <label style="height: 36px; padding: 0 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); font-size: 12px; display: flex; align-items: center; gap: 6px; color: white; cursor: pointer; user-select: none;">
                        <input type="checkbox" id="wpc-ai-gen-taxonomies" style="margin: 0;" checked />
                        Generate Tags and Categories
                    </label>

                    <button type="button" id="wpc-ai-generate-all" class="button" style="height: 36px; background: white; color: #6366f1; border: none; font-weight: 600; padding: 0 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-flex; align-items: center; gap: 5px;">
                        &#x2728; Generate All
                    </button>
                </div>
                <?php else : ?>
                <a href="<?php echo admin_url( 'edit.php?post_type=comparison_item&page=wpc-settings' ); ?>" class="button" style="background: white; color: #6366f1; border: none; font-weight: 600; padding: 8px 16px;">
                    &#x2699; Configure AI Provider
                </a>
                <?php endif; ?>
            </div>
            
            <?php if ( $ai_configured ) : ?>
            <!-- Custom Context (Optional) -->
            <div style="margin-top: 10px;">
                <label style="font-size: 12px; opacity: 0.9; display: block; margin-bottom: 5px;">
                    &#x1F4DD; Additional Context (pricing details, features AI might not know, etc.)
                </label>
                <textarea id="wpc-ai-custom-context" placeholder="e.g. Pricing starts at $5/mo for Basic, $15/mo for Pro. Has 24/7 support. Founded in 2020..." style="width: 100%; padding: 8px 12px; border-radius: 6px; border: none; color: #1e293b; resize: vertical; min-height: 60px; font-size: 13px;"></textarea>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- AI Styles -->
        <style>
            .wpc-ai-btn { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 500; transition: all 0.2s; display: inline-flex; align-items: center; gap: 5px; }
            .wpc-ai-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4); }
            .wpc-ai-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; box-shadow: none; }
            
            .wpc-spinner { 
                width: 14px; 
                height: 14px; 
                border: 2px solid rgba(99,102,241,0.3); 
                border-top-color: #6366f1; 
                border-radius: 50%; 
                animation: wpc-ai-spin 0.8s linear infinite; 
                display: inline-block; 
                vertical-align: middle;
            }
            @keyframes wpc-ai-spin { 
                0% { transform: rotate(0deg); } 
                100% { transform: rotate(360deg); } 
            }
            
            .wpc-ai-section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; }
            .wpc-ai-section-header h3 { margin: 0; }

            /* Admin Layout Styles */
            .wpc-admin-container { display: block; } /* Default */
            
            /* Sidebar Layout Mode */
            .wpc-admin-container.wpc-layout-sidebar {
                display: flex;
                gap: 20px;
                align-items: flex-start;
                margin-top: 20px;
            }
            .wpc-admin-container.wpc-layout-sidebar .wpc-tab-nav {
                flex-direction: column;
                width: 220px;
                flex-shrink: 0;
                position: sticky;
                top: 50px;
                border-bottom: none;
                margin: 0;
                padding: 0;
            }
            .wpc-admin-container.wpc-layout-sidebar .wpc-tab-nav li {
                width: 100%;
                margin: 0 0 5px 0;
                border: 1px solid transparent;
                border-radius: 4px;
                float: none; /* Override potential float */
                display: block;
                box-sizing: border-box;
            }
            .wpc-admin-container.wpc-layout-sidebar .wpc-tab-nav li.active {
                border-color: #e2e8f0;
                background: #fff;
                color: #6366f1;
                border-bottom: 1px solid #e2e8f0; /* Ensure border is uniform */
                border-right: 0;
                border-radius: 0;
            }
            .wpc-admin-container.wpc-layout-sidebar .wpc-tab-content {
                flex-grow: 1;
                width: 100%;
                min-width: 0; /* Prevent flex overflow */
                background: #fff;
                padding: 20px;
                border: 1px solid #e2e8f0;
                border-radius: 6px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            }
            
            /* Make Topbar scrollable on small screens */
            .wpc-tab-nav {
                display: flex;
                flex-wrap: wrap;
                gap: 2px;
            }
            
            /* Responsiveness */
            @media (max-width: 960px) {
                .wpc-admin-container.wpc-layout-sidebar {
                    flex-direction: column;
                }
                .wpc-admin-container.wpc-layout-sidebar .wpc-tab-nav {
                    width: 100%;
                    flex-direction: row;
                    flex-wrap: wrap;
                    position: static;
                    border-bottom: 1px solid #ccc;
                    margin-bottom: 20px;
                }
                .wpc-admin-container.wpc-layout-sidebar .wpc-tab-nav li {
                    width: auto;
                    margin-bottom: -1px;
                }
            }
        </style>
        
        <!-- AI Nonce -->
        <?php if ( $ai_configured ) : ?>
        <input type="hidden" id="wpc_ai_item_nonce" name="wpc_ai_item_nonce" value="<?php echo wp_create_nonce( 'wpc_ai_nonce' ); ?>" />
        <?php endif; ?>
        
        <!-- Admin Layout Wrapper -->
        <div class="wpc-admin-container wpc-layout-<?php echo esc_attr( $admin_layout ); ?>">
        
        <ul class="wpc-tab-nav">
            <li class="active" onclick="wpcOpenItemTab(event, 'general')">General Info</li>
            <li onclick="wpcOpenItemTab(event, 'taxonomy')">Categories & Tags</li>
            <li onclick="wpcOpenItemTab(event, 'visuals')">Visuals & Branding</li>
            <li onclick="wpcOpenItemTab(event, 'content')">Content & Footer</li>
            <li onclick="wpcOpenItemTab(event, 'pricing')">Pricing Plans</li>
            <li onclick="wpcOpenItemTab(event, 'plan_features')">Plan Features</li>
            <li onclick="wpcOpenItemTab(event, 'shortcodes')">Shortcodes</li>
            <li onclick="wpcOpenItemTab(event, 'seo')">SEO Schema</li>
            <li onclick="wpcOpenItemTab(event, 'use_cases')">Best Use Cases</li>
            <?php if ( get_option( 'wpc_enable_tools_module', false ) ) : ?>
            <li onclick="wpcOpenItemTab(event, 'tools_collections')">🔧 Tool Collections</li>
            <?php endif; ?>
            <li onclick="wpcOpenItemTab(event, 'import')">Data Import</li>
        </ul>

        <!-- TAB: GENERAL -->
        <div id="wpc-tab-general" class="wpc-tab-content active">

            <!-- MOVED FROM SEO TAB -->
            <div class="wpc-row" style="margin-bottom: 20px; border-bottom: 1px solid #e5e7eb; padding-bottom: 20px;">
                <div class="wpc-col">
                    <h3 class="wpc-section-title">Product & Schema Data</h3>
                    <?php $current_schema_cat = get_post_meta( $post->ID, '_wpc_product_category', true ) ?: 'SoftwareApplication'; ?>
                    <label class="wpc-label"><?php _e( 'Product Category', 'wp-comparison-builder' ); ?></label>
                    <select name="wpc_product_category" id="wpc_product_category" class="wpc-input" style="margin-bottom: 15px;">
                        <option value="SoftwareApplication" <?php selected( $current_schema_cat, 'SoftwareApplication' ); ?>>Digital / Software (Default)</option>
                        <option value="Product" <?php selected( $current_schema_cat, 'Product' ); ?>>Physical Product</option>
                        <option value="Service" <?php selected( $current_schema_cat, 'Service' ); ?>>Service</option>
                        <option value="Course" <?php selected( $current_schema_cat, 'Course' ); ?>>Course</option>
                    </select>
                    
                    <!-- Dynamic Fields Container -->
                    <div id="wpc-schema-fields">
                        <div class="wpc-field-group" data-show-for="SoftwareApplication Product Service Course">
                            <label class="wpc-label">Provider / Brand Name</label>
                            <input type="text" name="wpc_brand" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_brand', true ) ); ?>" class="wpc-input" placeholder="e.g. Sony, Coursera, Hostinger" />
                        </div>
                        
                        <div class="wpc-field-group" data-show-for="Product" style="display:none; margin-top: 10px; border-left: 3px solid #6366f1; padding-left: 10px;">
                            <h4 style="margin: 5px 0 10px;">Physical Product Details</h4>
                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <div style="flex:1;">
                                    <label class="wpc-label">SKU</label>
                                    <input type="text" name="wpc_sku" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_sku', true ) ); ?>" class="wpc-input" />
                                </div>
                                <div style="flex:1;">
                                    <label class="wpc-label">GTIN / MPN</label>
                                    <input type="text" name="wpc_gtin" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_gtin', true ) ); ?>" class="wpc-input" />
                                </div>
                            </div>
                             <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <div style="flex:1;">
                                    <label class="wpc-label">Condition</label>
                                    <select name="wpc_condition" class="wpc-input">
                                        <?php $cond = get_post_meta( $post->ID, '_wpc_condition', true ) ?: 'NewCondition'; ?>
                                        <option value="NewCondition" <?php selected($cond, 'NewCondition'); ?>>New</option>
                                        <option value="UsedCondition" <?php selected($cond, 'UsedCondition'); ?>>Used</option>
                                        <option value="RefurbishedCondition" <?php selected($cond, 'RefurbishedCondition'); ?>>Refurbished</option>
                                    </select>
                                </div>
                                <div style="flex:1;">
                                    <label class="wpc-label">Availability</label>
                                    <select name="wpc_availability" class="wpc-input">
                                        <?php $avail = get_post_meta( $post->ID, '_wpc_availability', true ) ?: 'InStock'; ?>
                                        <option value="InStock" <?php selected($avail, 'InStock'); ?>>In Stock</option>
                                        <option value="OutOfStock" <?php selected($avail, 'OutOfStock'); ?>>Out of Stock</option>
                                        <option value="PreOrder" <?php selected($avail, 'PreOrder'); ?>>Pre-Order</option>
                                    </select>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <div style="flex:1;">
                                    <label class="wpc-label">MFG Date</label>
                                    <input type="date" name="wpc_mfg_date" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_mfg_date', true ) ); ?>" class="wpc-input" />
                                </div>
                                <div style="flex:1;">
                                    <label class="wpc-label">Expiry Date</label>
                                    <input type="date" name="wpc_exp_date" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_exp_date', true ) ); ?>" class="wpc-input" />
                                </div>
                            </div>
                        </div>

                        <div class="wpc-field-group" data-show-for="Service" style="display:none; margin-top: 10px; border-left: 3px solid #10b981; padding-left: 10px;">
                            <h4 style="margin: 5px 0 10px;">Service Details</h4>
                            <div style="margin-bottom: 10px;">
                                <label class="wpc-label">Service Type</label>
                                <input type="text" name="wpc_service_type" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_service_type', true ) ); ?>" class="wpc-input" placeholder="e.g. Plumbing, Web Hosting, Consulting" />
                            </div>
                            <div>
                                <label class="wpc-label">Area Served (City/Country)</label>
                                <input type="text" name="wpc_area_served" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_area_served', true ) ); ?>" class="wpc-input" />
                            </div>
                        </div>

                        <div class="wpc-field-group" data-show-for="Course" style="display:none; margin-top: 10px; border-left: 3px solid #f59e0b; padding-left: 10px;">
                            <h4 style="margin: 5px 0 10px;">Course Details</h4>
                            <div style="margin-bottom: 10px;">
                                 <label class="wpc-label">Duration (ISO 8601)</label>
                                 <input type="text" name="wpc_duration" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_duration', true ) ); ?>" class="wpc-input" placeholder="e.g. PT10H (10 Hours)" />
                                 <p class="description"><a href="https://en.wikipedia.org/wiki/ISO_8601#Durations" target="_blank">ISO 8601 Format</a> required for Schema.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <script>
                jQuery(document).ready(function($) {
                    const catSelect = $('#wpc_product_category');
                    const fieldGroups = $('.wpc-field-group');
                    
                    function updateFields() {
                        const selected = catSelect.val();
                        fieldGroups.each(function() {
                            const showFor = $(this).data('show-for').split(' ');
                            if (showFor.includes(selected)) {
                                $(this).slideDown(200);
                            } else {
                                $(this).slideUp(200);
                            }
                        });
                    }
                    
                    catSelect.on('change', updateFields);
                    updateFields(); // Init
                });
                </script>
            </div>

            <h3 class="wpc-section-title">Basic Information</h3>
            <div class="wpc-row">
                <div class="wpc-col">
                    <label class="wpc-label"><?php _e( 'Public Name (Frontend Display)', 'wp-comparison-builder' ); ?></label>
                    <input type="text" name="wpc_public_name" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_public_name', true ) ); ?>" class="wpc-input" placeholder="e.g. Hostinger (Displayed to visitors)" />
                    <p class="description">Required. This name is shown on the website. The top title is for Admin only.</p>
                </div>
            </div>
            
            <div class="wpc-row">
                <div class="wpc-col">
                    <label class="wpc-label"><?php _e( 'Details / Short Description', 'wp-comparison-builder' ); ?></label>
                    <textarea name="wpc_short_description" id="wpc_short_description" rows="4" class="wpc-input" placeholder="Enter a brief description..."><?php echo esc_textarea( get_post_meta( $post->ID, '_wpc_short_description', true ) ); ?></textarea>
                    <p class="description">Used for comparison tables and summaries (formerly Excerpt).</p>
                </div>
            </div>

            <div class="wpc-row">
                <div class="wpc-col">
                    <label class="wpc-label"><?php _e( 'Price (e.g. $29.00)', 'wp-comparison-builder' ); ?></label>
                    <input type="text" name="wpc_price" value="<?php echo esc_attr( $price ); ?>" class="wpc-input" />
                </div>
                <div class="wpc-col">
                    <label class="wpc-label"><?php _e( 'Pricing Period (e.g. /mo, /yr)', 'wp-comparison-builder' ); ?></label>
                    <input type="text" name="wpc_period" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_period', true ) ); ?>" class="wpc-input" placeholder="/mo" />
                    <p class="description">Leave empty for "Free" or similar.</p>
                </div>
                <div class="wpc-col">
                    <label class="wpc-label"><?php _e( 'Rating (0-5)', 'wp-comparison-builder' ); ?></label>
                    <input type="number" step="0.1" min="0" max="5" name="wpc_rating" value="<?php echo esc_attr( $rating ); ?>" class="wpc-input" />
                </div>
            </div>

            <h3 class="wpc-section-title" style="margin-top: 20px;">Links & Buttons</h3>
            <div class="wpc-row">
                <div class="wpc-col">
                    <label class="wpc-label"><?php _e( 'Details Page Link (URL)', 'wp-comparison-builder' ); ?></label>
                    <input type="text" name="wpc_details_link" value="<?php echo esc_url( get_post_meta( $post->ID, '_wpc_details_link', true ) ); ?>" class="wpc-input" placeholder="https://example.com/review-page" />
                    <p class="description"><?php _e( 'Where the "View Details" button should link to.', 'wp-comparison-builder' ); ?></p>
                </div>
                <div class="wpc-col">
                    <label class="wpc-label"><?php _e( 'Direct / Non-Comparison Link (URL)', 'wp-comparison-builder' ); ?></label>
                    <input type="text" name="wpc_direct_link" value="<?php echo esc_url( get_post_meta( $post->ID, '_wpc_direct_link', true ) ); ?>" class="wpc-input" placeholder="https://example.com/go" />
                    <p class="description"><?php _e( 'Used when comparison is disabled. Steps over the popup.', 'wp-comparison-builder' ); ?></p>
                </div>
                <div class="wpc-col">
                    <label class="wpc-label"><?php _e( 'Button Text (Popup)', 'wp-comparison-builder' ); ?></label>
                    <input type="text" name="wpc_button_text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_button_text', true ) ); ?>" class="wpc-input" placeholder="Visit Website" />
                </div>
                <div class="wpc-col">
                    <label class="wpc-label"><?php _e( 'Hero Button Text', 'wp-comparison-builder' ); ?></label>
                    <input type="text" name="wpc_hero_button_text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_hero_button_text', true ) ); ?>" class="wpc-input" placeholder="Visit [Item Name]" />
                    <p class="description"><?php _e( 'Custom button text for hero section. Leave empty to use default.', 'wp-comparison-builder' ); ?></p>
                </div>
            </div>
            
            <div class="wpc-row">
                <div class="wpc-col">
                    <label class="wpc-label"><?php _e( 'Direct / Non-Comparison Link (URL)', 'wp-comparison-builder' ); ?></label>
                    <input type="text" name="wpc_direct_link" value="<?php echo esc_url( get_post_meta( $post->ID, '_wpc_direct_link', true ) ); ?>" class="wpc-input" placeholder="https://example.com/go" />
                    <p class="description"><?php _e( 'Used when comparison is disabled. Steps over the popup.', 'wp-comparison-builder' ); ?></p>
                </div>
            </div>
            
        </div>

        <!-- TAB: VISUALS -->
        <div id="wpc-tab-visuals" class="wpc-tab-content">
            <h3 class="wpc-section-title">Logo & Badges</h3>
            <div class="wpc-row">
                <div class="wpc-col">
                    <label class="wpc-label"><?php _e( 'Logo (External URL)', 'wp-comparison-builder' ); ?></label>
                    <div style="display: flex; gap: 10px;">
                        <input type="url" id="wpc_external_logo_url" name="wpc_external_logo_url" value="<?php echo esc_url( get_post_meta( $post->ID, '_wpc_external_logo_url', true ) ); ?>" class="wpc-input" placeholder="https://example.com/logo.png" />
                        <button type="button" class="button" id="wpc_upload_logo_url"><?php _e( 'Upload', 'wp-comparison-builder' ); ?></button>
                    </div>
                    <p class="description"><?php _e( 'Or use the standard "Featured Image" box on the right.', 'wp-comparison-builder' ); ?></p>
                </div>
                <div class="wpc-col">
                    <label class="wpc-label"><?php _e( 'Dashboard / Hero Image', 'wp-comparison-builder' ); ?></label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="wpc_dashboard_image" name="wpc_dashboard_image" value="<?php echo esc_url( get_post_meta( $post->ID, '_wpc_dashboard_image', true ) ); ?>" class="wpc-input" placeholder="https://..." />
                        <button type="button" class="button" id="wpc_upload_dashboard_image"><?php _e( 'Upload', 'wp-comparison-builder' ); ?></button>
                    </div>
                    <p class="description">Used in hero sections. <strong>Note:</strong> If a "Featured Image" is set on the right, it will override this field.</p>
                </div>
            </div>

            <div class="wpc-row">
                <div class="wpc-col">
                    <label class="wpc-label"><?php _e( 'Hero Subtitle', 'wp-comparison-builder' ); ?></label>
                    <input type="text" name="wpc_hero_subtitle" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_hero_subtitle', true ) ); ?>" class="wpc-input" placeholder="In-depth review and details" />
                    <p class="description">Appears below the title. Leave empty to hide.</p>
                </div>
                <div class="wpc-col">
                    <label class="wpc-label"><?php _e( 'Analysis Label', 'wp-comparison-builder' ); ?></label>
                    <input type="text" name="wpc_analysis_label" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_analysis_label', true ) ); ?>" class="wpc-input" placeholder="Based on our analysis" />
                    <p class="description">Appears next to the star rating. Leave empty to hide.</p>
                </div>
            </div>
            
            <div class="wpc-row">
                <div class="wpc-col">
                     <?php 
                     $show_hero_logo = get_post_meta( $post->ID, '_wpc_show_hero_logo', true );
                     if ( $show_hero_logo === '' ) $show_hero_logo = '1'; // Default to true if not set
                     ?>
                    <label style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" name="wpc_show_hero_logo" value="1" <?php checked($show_hero_logo, '1'); ?> />
                        <?php _e( 'Show Logo in Hero Section', 'wp-comparison-builder' ); ?>
                    </label>
                    <p class="description">If unchecked, the logo (icon) will be hidden in the hero banner.</p>
                </div>
            </div>

            <script>
            jQuery(document).ready(function($){
                // Media Uploader
                $('#wpc_upload_dashboard_image').click(function(e) {
                    e.preventDefault();
                    var button = $(this);
                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }
                    mediaUploader = wp.media.frames.file_frame = wp.media({
                        title: 'Choose Image',
                        button: {
                            text: 'Choose Image'
                        },
                        multiple: false
                    });
                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        // Determine which button was clicked
                        if (currentUploadInput) {
                            $(currentUploadInput).val(attachment.url);
                        } else {
                            // default fallback (original logic)
                            $('#wpc_dashboard_image').val(attachment.url);
                        }
                    });
                    mediaUploader.open();
                });
                
                // Allow dynamic targetting
                var currentUploadInput = null;
                
                $('#wpc_upload_dashboard_image').click(function(e) { 
                    currentUploadInput = '#wpc_dashboard_image'; 
                });
                
                $('#wpc_upload_logo_url').click(function(e) {
                    e.preventDefault();
                    currentUploadInput = '#wpc_external_logo_url';
                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }
                    // Init logic duplicated or shared? Shared above but need to careful about click handler binding.
                    // Better approach:
                    // Separate init function
                });

                // REWRITING UPLOADER LOGIC TO BE GENERIC
                var wpcMediaUploader;
                function wpcInitUploader(targetSelector, title) {
                     if (wpcMediaUploader) {
                        wpcMediaUploader.targetSelector = targetSelector; // custom property
                        wpcMediaUploader.open();
                        return;
                    }
                    wpcMediaUploader = wp.media.frames.file_frame = wp.media({
                        title: title || 'Choose Image',
                        button: { text: 'Choose Image' },
                        multiple: false
                    });
                    wpcMediaUploader.on('select', function() {
                        var attachment = wpcMediaUploader.state().get('selection').first().toJSON();
                        $(wpcMediaUploader.targetSelector).val(attachment.url);
                    });
                    wpcMediaUploader.targetSelector = targetSelector;
                    wpcMediaUploader.open();
                }
                
                $('#wpc_upload_dashboard_image').off('click').click(function(e){
                    e.preventDefault();
                    wpcInitUploader('#wpc_dashboard_image', 'Choose Dashboard Image');
                });
                
                $('#wpc_upload_logo_url').off('click').click(function(e){
                     e.preventDefault();
                     wpcInitUploader('#wpc_external_logo_url', 'Choose Logo');
                });
            });
            </script>

            <div class="wpc-row">
                <div class="wpc-col">
                    <label class="wpc-label"><?php _e( 'Featured Badge Text', 'wp-comparison-builder' ); ?></label>
                    <input type="text" name="wpc_featured_badge_text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_featured_badge_text', true ) ); ?>" class="wpc-input" placeholder="e.g. Top Choice" />
                </div>
                <div class="wpc-col">
                    <label class="wpc-label"><?php _e( 'Featured Color', 'wp-comparison-builder' ); ?></label>
                    <input type="color" name="wpc_featured_color" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_featured_color', true ) ); ?>" class="wpc-input" style="height:35px;" />
                </div>
            </div>

            <h3 class="wpc-section-title" style="margin-top:20px;">Design Overrides (Pricing Table/Popup)</h3>
             <div class="wpc-row">
                <div class="wpc-col">
                    <div style="background: #f8fafc; padding: 15px; border: 1px solid #e2e8f0; border-radius: 4px;">
                        <!-- Enable Overrides Toggle -->
                        <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0;">
                            <?php $enable_overrides = get_post_meta( $post->ID, '_wpc_enable_design_overrides', true ); ?>
                            <label style="font-weight:bold; color: #334155;">
                                <input type="checkbox" name="wpc_enable_design_overrides" value="1" <?php checked( $enable_overrides, '1' ); ?> />
                                <?php _e( 'Enable Design Overrides', 'wp-comparison-builder' ); ?>
                            </label>
                            <p class="description" style="margin-top:2px;">If unchecked, the default global/theme styles will be used.</p>
                        </div>

                        <div style="display:flex; gap: 20px; margin-bottom: 15px;">
                            <div>
                                <label class="wpc-label" style="font-size:11px; margin-bottom:2px;">Primary Color</label>
                                <input type="color" name="wpc_primary_color" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_primary_color', true ) ?: '#6366f1' ); ?>" style="height:35px; width:60px;" />
                            </div>
                            <div>
                                <label class="wpc-label" style="font-size:11px; margin-bottom:2px;">Accent Color</label>
                                <input type="color" name="wpc_accent_color" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_accent_color', true ) ?: '#818cf8' ); ?>" style="height:35px; width:60px;" />
                            </div>
                            <div>
                                <label class="wpc-label" style="font-size:11px; margin-bottom:2px;">Border Color</label>
                                <input type="color" name="wpc_border_color" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_border_color', true ) ?: '#e2e8f0' ); ?>" style="height:35px; width:60px;" />
                            </div>
                            <div>
                                <label class="wpc-label" style="font-size:11px; margin-bottom:2px;">Coupon BG</label>
                                <input type="color" name="wpc_color_coupon_bg" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_color_coupon_bg', true ) ?: '#fef3c7' ); ?>" style="height:35px; width:60px;" />
                            </div>
                            <div>
                                <label class="wpc-label" style="font-size:11px; margin-bottom:2px;">Coupon Text</label>
                                <input type="color" name="wpc_color_coupon_text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_color_coupon_text', true ) ?: '#92400e' ); ?>" style="height:35px; width:60px;" />
                            </div>
                        </div>
                        
                        <button type="button" class="button" onclick="wpcResetDesignOverrides(this)" style="margin-top: 10px;">
                            Reset to Global Settings
                        </button>
                    </div>
                </div>
             </div>

            <h3 class="wpc-section-title" style="margin-top:20px;">Pros & Cons Colors</h3>
             <div class="wpc-row">
                <div class="wpc-col">
                    <div style="background: #f8fafc; padding: 15px; border: 1px solid #e2e8f0; border-radius: 4px;">
                        <!-- Enable Custom Colors Toggle -->
                        <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0;">
                            <?php $enable_pros_colors = get_post_meta( $post->ID, '_wpc_enable_pros_cons_colors', true ); ?>
                            <label style="font-weight:bold; color: #334155;">
                                <input type="checkbox" id="wpc_enable_pros_cons_colors" name="wpc_enable_pros_cons_colors" value="1" <?php checked( $enable_pros_colors, '1' ); ?> onchange="wpcToggleProsCons(this)" />
                                <?php _e( 'Enable Custom Pros & Cons Colors', 'wp-comparison-builder' ); ?>
                            </label>
                            <p class="description" style="margin-top:2px;">If unchecked, the global default colors will be used.</p>
                        </div>
                        
                        <div id="wpc-pros-cons-inputs" style="display:flex; gap: 20px; margin-bottom: 15px; <?php echo $enable_pros_colors !== '1' ? 'opacity:0.5;pointer-events:none;' : ''; ?>">
                            <div>
                                <label class="wpc-label" style="font-size:11px; margin-bottom:2px;">Pros Background</label>
                                <input type="color" name="wpc_color_pros_bg" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_color_pros_bg', true ) ?: '#f0fdf4' ); ?>" style="height:35px; width:60px;" />
                            </div>
                            <div>
                                <label class="wpc-label" style="font-size:11px; margin-bottom:2px;">Pros Text</label>
                                <input type="color" name="wpc_color_pros_text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_color_pros_text', true ) ?: '#166534' ); ?>" style="height:35px; width:60px;" />
                            </div>
                            <div>
                                <label class="wpc-label" style="font-size:11px; margin-bottom:2px;">Cons Background</label>
                                <input type="color" name="wpc_color_cons_bg" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_color_cons_bg', true ) ?: '#fef2f2' ); ?>" style="height:35px; width:60px;" />
                            </div>
                            <div>
                                <label class="wpc-label" style="font-size:11px; margin-bottom:2px;">Cons Text</label>
                                <input type="color" name="wpc_color_cons_text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_color_cons_text', true ) ?: '#991b1b' ); ?>" style="height:35px; width:60px;" />
                            </div>
                        </div>
                        
                        <button type="button" class="button" onclick="wpcResetProsConsColors(this)" style="margin-top: 10px;">
                            Reset to Global Settings
                        </button>
                    </div>
                </div>
             </div>
        </div>

        <!-- TAB: PRICING PLANS -->
        <div id="wpc-tab-pricing" class="wpc-tab-content">
            
            <!-- Pricing Table Shortcode Display -->
            <?php
            $variants_enabled = get_post_meta( $post->ID, '_wpc_variants_enabled', true ) === '1';
            $assigned_cats = wp_get_post_terms( $post->ID, 'comparison_category', array( 'fields' => 'all' ) );
            $has_variants = $variants_enabled && !empty($assigned_cats);
            ?>
            <div style="background:#ecfdf5; border:1px solid #10b981; padding:15px; border-radius:6px; margin-bottom:20px;">
                <strong style="color:#059669; display:block; margin-bottom:10px;">💰 Pricing Table Shortcode:</strong>
                
                <?php if ( $has_variants ) : ?>
                    <p style="margin-bottom: 8px; font-size:13px; color: #64748b;">Show pricing plans for specific categories:</p>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <!-- All Plans -->
                        <div style="display:flex; align-items:center; gap:8px;">
                            <code style="flex:1; background:#fff; padding:6px 10px; border:1px solid #ddd; border-radius:4px; color:#059669; font-size:12px;">[wpc_pricing_table id="<?php echo $post->ID; ?>"]</code>
                            <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('[wpc_pricing_table id=<?php echo $post->ID; ?>]'); this.innerText='✓'; setTimeout(()=>this.innerText='Copy', 1500);">Copy</button>
                            <span style="font-size: 11px; color: #64748b;">(All)</span>
                        </div>
                        <!-- Category-Specific -->
                        <?php foreach ( $assigned_cats as $cat ) : ?>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <code style="flex:1; background:#fff; padding:6px 10px; border:1px solid #ddd; border-radius:4px; color:#059669; font-size:12px;">[wpc_pricing_table id="<?php echo $post->ID; ?>" category="<?php echo esc_attr($cat->slug); ?>"]</code>
                            <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('[wpc_pricing_table id=<?php echo $post->ID; ?> category=<?php echo esc_js($cat->slug); ?>]'); this.innerText='✓'; setTimeout(()=>this.innerText='Copy', 1500);">Copy</button>
                            <span style="padding: 2px 6px; background: #d1fae5; color: #065f46; border-radius: 9999px; font-size: 10px; font-weight: 600;"><?php echo esc_html($cat->name); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <code style="background:#fff; padding:6px 10px; border:1px solid #ddd; border-radius:4px; color:#059669; flex:1;">[wpc_pricing_table id="<?php echo $post->ID; ?>"]</code>
                        <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('[wpc_pricing_table id=<?php echo $post->ID; ?>]'); this.innerText='Copied!'; setTimeout(()=>this.innerText='Copy', 1500);">Copy</button>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php 
            // Product Variants Section (if module enabled)
            if ( function_exists( 'wpc_render_variants_section' ) ) {
                wpc_render_variants_section( $post );
            }
            ?>
            
            <h3 class="wpc-section-title">Pricing Plans & Coupons</h3>
            
            <div class="wpc-row">
                <div class="wpc-col">
                    <label class="wpc-label"><?php _e( 'Pricing Plans Configuration', 'wp-comparison-builder' ); ?></label>
                    <div style="margin-bottom: 15px; display: flex; flex-wrap: wrap; gap: 20px;">
                        <?php 
                        $hide_features = get_post_meta( $post->ID, '_wpc_hide_plan_features', true );
                        $show_plan_links = get_post_meta( $post->ID, '_wpc_show_plan_links', true );
                        $show_plan_links_popup = get_post_meta( $post->ID, '_wpc_show_plan_links_popup', true );
                        $table_btn_pos = get_post_meta( $post->ID, '_wpc_table_btn_pos', true ) ?: 'default';
                        $popup_btn_pos = get_post_meta( $post->ID, '_wpc_popup_btn_pos', true ) ?: 'default';
                        ?>
                        <div>
                            <strong style="display: block; margin-bottom: 5px;">Visibility:</strong>
                            <label style="display: block; margin-bottom: 3px;">
                                <input type="checkbox" name="wpc_hide_plan_features" value="1" <?php checked( $hide_features, '1' ); ?> />
                                <?php _e( 'Hide "Features" Column', 'wp-comparison-builder' ); ?>
                            </label>
                            <label style="display: block; margin-bottom: 3px;">
                                <input type="checkbox" name="wpc_show_plan_links" value="1" <?php checked( $show_plan_links, '1' ); ?> />
                                <?php _e( 'Show "Select" Buttons in Table', 'wp-comparison-builder' ); ?>
                            </label>
                            <label style="display: block;">
                                <input type="checkbox" name="wpc_show_plan_links_popup" value="1" <?php checked( $show_plan_links_popup, '1' ); ?> />
                                <?php _e( 'Show "Select" Buttons in Popup', 'wp-comparison-builder' ); ?>
                            </label>
                        </div>
                        <div>
                            <strong style="display: block; margin-bottom: 5px;">Button Position:</strong>
                            <label style="display: block; margin-bottom: 5px;">
                                Table:
                                <select name="wpc_table_btn_pos" style="margin-left: 5px;">
                                    <option value="default" <?php selected( $table_btn_pos, 'default' ); ?>>Default (Global)</option>
                                    <option value="after_price" <?php selected( $table_btn_pos, 'after_price' ); ?>>After Pricing</option>
                                    <option value="bottom" <?php selected( $table_btn_pos, 'bottom' ); ?>>Bottom (After Features)</option>
                                </select>
                            </label>
                            <label style="display: block;">
                                Popup:
                                <select name="wpc_popup_btn_pos" style="margin-left: 5px;">
                                    <option value="default" <?php selected( $popup_btn_pos, 'default' ); ?>>Default (Global)</option>
                                    <option value="after_price" <?php selected( $popup_btn_pos, 'after_price' ); ?>>After Pricing</option>
                                    <option value="bottom" <?php selected( $popup_btn_pos, 'bottom' ); ?>>Bottom (After Features)</option>
                                </select>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Billing Cycles Configuration -->
            <div class="wpc-row" style="margin-top: 15px;">
                <div class="wpc-col">
                    <label class="wpc-label"><?php _e( 'Billing Cycles Configuration', 'wp-comparison-builder' ); ?></label>
                    <div style="padding: 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
                        <p style="margin-top: 0; font-size: 13px; color: #64748b;">
                            Define the billing periods available for this item (e.g., Monthly, Yearly, Lifetime). 
                            These will appear as options in your pricing plans.
                        </p>

                        <!-- Display Style -->
                        <div style="margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0;">
                            <label style="font-size: 13px; font-weight: 600; margin-right: 10px;">Billing Display Style:</label>
                             <?php $billing_style = get_post_meta( $post->ID, '_wpc_billing_display_style', true ) ?: 'toggle'; ?>
                             <select name="wpc_billing_display_style">
                                <option value="toggle" <?php selected($billing_style, 'toggle'); ?>>Toggle Switch</option>
                                <option value="tabs" <?php selected($billing_style, 'tabs'); ?>>Tabs</option>
                                <option value="none" <?php selected($billing_style, 'none'); ?>>None (Show Default Only)</option>
                             </select>
                        </div>
                        
                        <div id="wpc-cycles-container">
                            <?php 
                            $cycles = get_post_meta( $post->ID, '_wpc_billing_cycles', true );
                            if ( ! is_array( $cycles ) || empty( $cycles ) ) {
                                // Default/Fallback: Monthly & Yearly
                                $cycles = array(
                                    array( 'slug' => 'monthly', 'label' => get_post_meta( $post->ID, '_wpc_monthly_label', true ) ?: 'Monthly' ),
                                    array( 'slug' => 'yearly', 'label' => get_post_meta( $post->ID, '_wpc_yearly_label', true ) ?: 'Yearly' )
                                );
                            }
                            
                            foreach ( $cycles as $idx => $cycle ) : 
                            ?>
                            <div class="wpc-cycle-row" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                                <input type="text" name="wpc_cycles[<?php echo $idx; ?>][slug]" value="<?php echo esc_attr( $cycle['slug'] ); ?>" placeholder="Slug (e.g. monthly)" style="width: 120px; font-family: monospace;" required />
                                <input type="text" name="wpc_cycles[<?php echo $idx; ?>][label]" value="<?php echo esc_attr( $cycle['label'] ); ?>" placeholder="Label (e.g. Pay Monthly)" style="flex: 1;" required />
                                <label style="font-size: 12px; display: flex; align-items: center; gap: 5px;">
                                    <input type="radio" name="wpc_default_cycle" value="<?php echo esc_attr( $cycle['slug'] ); ?>" <?php checked( get_post_meta( $post->ID, '_wpc_default_cycle', true ), $cycle['slug'] ); ?> />
                                    Default
                                </label>
                                <button type="button" class="button wpc-remove-cycle" onclick="this.closest('.wpc-cycle-row').remove(); wpcRefreshPlanInputs();">×</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="button" class="button button-secondary" onclick="wpcAddCycle()">+ Add Billing Cycle</button>
                    </div>
                </div>
            </div>

            <!-- Global Coupon -->
             <div class="wpc-row">
                <div class="wpc-col">
                    <label class="wpc-label"><?php _e( 'Main Coupon Code', 'wp-comparison-builder' ); ?></label>
                    <input type="text" name="wpc_coupon_code" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_coupon_code', true ) ); ?>" class="wpc-input" placeholder="e.g. SAVE20" />
                </div>
                <div class="wpc-col">
                    <?php $show_coupon = get_post_meta( $post->ID, '_wpc_show_coupon', true ); ?>
                    <label class="wpc-label"><?php _e( 'Show Coupon?', 'wp-comparison-builder' ); ?></label>
                    <label>
                        <input type="checkbox" name="wpc_show_coupon" value="1" <?php checked( $show_coupon, '1' ); ?> />
                        <?php _e( 'Show Coupon Button?', 'wp-comparison-builder' ); ?>
                    </label>
                </div>
            </div>

            <!-- PLANS REPEATER -->
            <div class="wpc-row">
                <div class="wpc-col">
                    <label class="wpc-label" style="display:flex; justify-content:space-between; align-items:center;">
                        <?php _e( 'Plans List', 'wp-comparison-builder' ); ?>
                        <button type="button" class="button button-small" onclick="wpcAddPlan()">+ Add Plan</button>
                    </label>
                    <div id="wpc-plans-container">
                        <?php 
                        $plans = get_post_meta( $post->ID, '_wpc_pricing_plans', true );
                        if ( ! is_array( $plans ) ) $plans = array();
                        
                        if ( empty( $plans ) ) {
                            $plans[] = array( 'name' => '', 'price' => '', 'period' => '', 'features' => '', 'link' => '', 'coupon' => '' );
                        }

                        foreach ( $plans as $index => $plan ) : 
                            $show_btn = isset($plan['show_button']) ? $plan['show_button'] : '0';
                            $show_popup = isset($plan['show_popup']) ? $plan['show_popup'] : $show_btn;
                            $show_table = isset($plan['show_table']) ? $plan['show_table'] : $show_btn;
                        ?>
                            <div class="wpc-plan-row" style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd; margin-bottom: 10px; border-radius: 4px;">
                                <div style="display: flex; gap: 10px; margin-bottom: 5px; flex-wrap: wrap;">
                                    <input type="text" name="wpc_plans[<?php echo $index; ?>][name]" value="<?php echo esc_attr( isset($plan['name']) ? $plan['name'] : '' ); ?>" placeholder="Plan Name (e.g. Basic)" style="flex: 2; min-width: 150px;" />
                                    <div class="wpc-plan-prices" style="display: flex; gap: 10px; flex: 3; flex-wrap: wrap;">
                                        <?php 
                                        // Render inputs for each cycle
                                        // Legacy fallback: map old 'price'/'yearly_price' to 'monthly'/'yearly' if prices array is missing
                                        $plan_prices = isset($plan['prices']) ? $plan['prices'] : array();
                                        if ( empty($plan_prices) ) {
                                            if ( isset($plan['price']) ) $plan_prices['monthly'] = array( 'amount' => $plan['price'], 'period' => isset($plan['period']) ? $plan['period'] : '/mo' );
                                            if ( isset($plan['yearly_price']) ) $plan_prices['yearly'] = array( 'amount' => $plan['yearly_price'], 'period' => isset($plan['yearly_period']) ? $plan['yearly_period'] : '/yr' );
                                        }

                                        foreach ( $cycles as $cycle ) {
                                            $slug = $cycle['slug'];
                                            $lbl = $cycle['label'];
                                            $val = isset($plan_prices[$slug]['amount']) ? $plan_prices[$slug]['amount'] : '';
                                            $per = isset($plan_prices[$slug]['period']) ? $plan_prices[$slug]['period'] : '';
                                            ?>
                                             <div class="wpc-price-group" data-slug="<?php echo esc_attr($slug); ?>" style="display: flex; gap: 2px; flex: 1 0 160px; min-width: 160px;">
                                                <input type="text" name="wpc_plans[<?php echo $index; ?>][prices][<?php echo esc_attr($slug); ?>][amount]" value="<?php echo esc_attr( $val ); ?>" placeholder="<?php echo esc_attr( $lbl ); ?> Price" style="flex: 2; min-width: 60px;" />
                                                <input type="text" name="wpc_plans[<?php echo $index; ?>][prices][<?php echo esc_attr($slug); ?>][period]" value="<?php echo esc_attr( $per ); ?>" placeholder="/suffix" style="width: 55px; flex: 0 0 55px;" />
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div style="margin-bottom: 5px;">
                                     <input type="text" name="wpc_plans[<?php echo $index; ?>][coupon]" value="<?php echo esc_attr( isset($plan['coupon']) ? $plan['coupon'] : '' ); ?>" placeholder="Coupon Code (e.g. SAVE20)" style="width: 100%;" />
                                </div>
                                <div style="display: flex; gap: 10px; margin-bottom: 5px;">
                                    <div style="flex: 1; display:flex; gap: 5px; align-items: center;">
                                        <label style="font-size: 11px;">
                                            <input type="checkbox" name="wpc_plans[<?php echo $index; ?>][show_banner]" value="1" <?php checked( isset($plan['show_banner']) ? $plan['show_banner'] : '0', '1' ); ?> />
                                            Show Banner
                                        </label>
                                        <input type="text" name="wpc_plans[<?php echo $index; ?>][banner_text]" value="<?php echo esc_attr( isset($plan['banner_text']) ? $plan['banner_text'] : '' ); ?>" placeholder="Banner (e.g. 70% OFF)" style="flex: 1;" />
                                        <input type="color" name="wpc_plans[<?php echo $index; ?>][banner_color]" value="<?php echo esc_attr( isset($plan['banner_color']) ? $plan['banner_color'] : '#10b981' ); ?>" style="height: 30px; width: 40px; padding: 0; border: none; cursor: pointer;" title="Banner Color" />
                                    </div>
                                </div>
                                <div style="margin-bottom: 5px;">
                                    <textarea name="wpc_plans[<?php echo $index; ?>][features]" rows="3" style="width:100%;" placeholder="Features (one per line)"><?php echo esc_textarea( isset($plan['features']) ? $plan['features'] : '' ); ?></textarea>
                                </div>
                                <div style="display: flex; gap: 10px; align-items: center; margin-top: 5px;">
                                    <input type="text" name="wpc_plans[<?php echo $index; ?>][link]" value="<?php echo esc_attr( isset($plan['link']) ? $plan['link'] : '' ); ?>" placeholder="Link (https://...)" style="flex: 1;" />
                                    <div style="flex: 1.5; display: flex; align-items: center; gap: 10px;">
                                        <label style="font-size: 11px; display:flex; align-items:center; gap:3px;">
                                            <input type="checkbox" name="wpc_plans[<?php echo $index; ?>][show_popup]" value="1" <?php checked( $show_popup, '1' ); ?> />
                                            Popup
                                        </label>
                                        <label style="font-size: 11px; display:flex; align-items:center; gap:3px;">
                                            <input type="checkbox" name="wpc_plans[<?php echo $index; ?>][show_table]" value="1" <?php checked( $show_table, '1' ); ?> />
                                            Table
                                        </label>
                                        
                                        <input type="text" name="wpc_plans[<?php echo $index; ?>][button_text]" value="<?php echo esc_attr( isset($plan['button_text']) ? $plan['button_text'] : '' ); ?>" placeholder="Btn Text" style="flex: 1;" />
                                    </div>
                                    <button type="button" class="button wpc-remove-plan" onclick="wpcRemovePlan(this)"><?php _e( 'Remove', 'wp-comparison-builder' ); ?></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
        </div>

        <!-- TAB: CONTENT -->
        <div id="wpc-tab-content" class="wpc-tab-content">
            <?php
            // Get global text settings as defaults
            $global_pros_label = get_option( 'wpc_text_pros', 'Pros' );
            $global_cons_label = get_option( 'wpc_text_cons', 'Cons' );
            
            // Get per-item overrides
            $item_pros_label = get_post_meta( $post->ID, '_wpc_txt_pros_label', true );
            $item_cons_label = get_post_meta( $post->ID, '_wpc_txt_cons_label', true );
            ?>
            
            <h3 class="wpc-section-title"><?php _e( 'Pros & Cons', 'wp-comparison-builder' ); ?></h3>
            
            <!-- Label Customization -->
            <div class="wpc-row" style="margin-bottom: 15px; padding: 10px; background: #f9fafb; border-radius: 4px;">
                <div class="wpc-col">
                    <label class="wpc-label" style="font-size: 11px; color: #64748b;"><?php _e( '"Pros" Label Override', 'wp-comparison-builder' ); ?></label>
                    <input type="text" name="wpc_txt_pros_label" value="<?php echo esc_attr( $item_pros_label ); ?>" class="wpc-input" placeholder="<?php echo esc_attr( $global_pros_label ); ?> (global default)" style="font-size: 12px;" />
                </div>
                <div class="wpc-col">
                    <label class="wpc-label" style="font-size: 11px; color: #64748b;"><?php _e( '"Cons" Label Override', 'wp-comparison-builder' ); ?></label>
                    <input type="text" name="wpc_txt_cons_label" value="<?php echo esc_attr( $item_cons_label ); ?>" class="wpc-input" placeholder="<?php echo esc_attr( $global_cons_label ); ?> (global default)" style="font-size: 12px;" />
                </div>
            </div>
            
            <div class="wpc-row">
                <div class="wpc-col">
                    <label class="wpc-label"><?php echo esc_html( $item_pros_label ?: $global_pros_label ); ?> <?php _e( '(One per line)', 'wp-comparison-builder' ); ?></label>
                    <textarea name="wpc_pros" rows="6" class="wpc-input"><?php echo esc_textarea( $pros ); ?></textarea>
                </div>
                <div class="wpc-col">
                    <label class="wpc-label"><?php echo esc_html( $item_cons_label ?: $global_cons_label ); ?> <?php _e( '(One per line)', 'wp-comparison-builder' ); ?></label>
                    <textarea name="wpc_cons" rows="6" class="wpc-input"><?php echo esc_textarea( $cons ); ?></textarea>
                </div>
            </div>
            
            <!-- Text Label Overrides (Collapsible) -->
            <div style="margin-top: 20px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                <div onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'block' : 'none'; this.querySelector('.toggle-icon').textContent = this.nextElementSibling.style.display === 'none' ? '▶' : '▼';" 
                     style="background: #f8fafc; padding: 12px 15px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                    <strong style="font-size: 13px;">📝 <?php _e( 'Text Label Overrides', 'wp-comparison-builder' ); ?></strong>
                    <span class="toggle-icon" style="font-size: 10px; color: #94a3b8;">▶</span>
                </div>
                <div style="display: none; padding: 15px; background: #fff;">
                    <p class="description" style="margin-top: 0; margin-bottom: 15px; font-size: 11px; color: #64748b;">
                        <?php _e( 'Leave blank to use global settings. These override global defaults for this item only.', 'wp-comparison-builder' ); ?>
                    </p>
                    
                    <?php
                    // Get current per-item overrides
                    $txt_price_label = get_post_meta( $post->ID, '_wpc_txt_price_label', true );
                    $txt_rating_label = get_post_meta( $post->ID, '_wpc_txt_rating_label', true );
                    $txt_mo_suffix = get_post_meta( $post->ID, '_wpc_txt_mo_suffix', true );
                    $txt_visit_site = get_post_meta( $post->ID, '_wpc_txt_visit_site', true );
                    $txt_coupon_label = get_post_meta( $post->ID, '_wpc_txt_coupon_label', true );
                    $txt_feature_header = get_post_meta( $post->ID, '_wpc_txt_feature_header', true );
                    $txt_copied_label = get_post_meta( $post->ID, '_wpc_txt_copied_label', true );
                    ?>
                    
                    <div class="wpc-row" style="gap: 10px; margin-bottom: 10px;">
                        <div class="wpc-col">
                            <label class="wpc-label" style="font-size: 11px;"><?php _e( '"Price" Column', 'wp-comparison-builder' ); ?></label>
                            <input type="text" name="wpc_txt_price_label" value="<?php echo esc_attr( $txt_price_label ); ?>" class="wpc-input" style="font-size: 12px;" placeholder="<?php echo esc_attr( get_option( 'wpc_text_price', 'Price' ) ); ?>" />
                        </div>
                        <div class="wpc-col">
                            <label class="wpc-label" style="font-size: 11px;"><?php _e( '"Rating" Column', 'wp-comparison-builder' ); ?></label>
                            <input type="text" name="wpc_txt_rating_label" value="<?php echo esc_attr( $txt_rating_label ); ?>" class="wpc-input" style="font-size: 12px;" placeholder="<?php echo esc_attr( get_option( 'wpc_text_rating', 'Rating' ) ); ?>" />
                        </div>
                        <div class="wpc-col">
                            <label class="wpc-label" style="font-size: 11px;"><?php _e( '"/mo" Suffix', 'wp-comparison-builder' ); ?></label>
                            <input type="text" name="wpc_txt_mo_suffix" value="<?php echo esc_attr( $txt_mo_suffix ); ?>" class="wpc-input" style="font-size: 12px;" placeholder="<?php echo esc_attr( get_option( 'wpc_text_mo_suffix', '/mo' ) ); ?>" />
                        </div>
                    </div>
                    
                    <div class="wpc-row" style="gap: 10px;">
                        <div class="wpc-col">
                            <label class="wpc-label" style="font-size: 11px;"><?php _e( '"Visit Site" Button', 'wp-comparison-builder' ); ?></label>
                            <input type="text" name="wpc_txt_visit_site" value="<?php echo esc_attr( $txt_visit_site ); ?>" class="wpc-input" style="font-size: 12px;" placeholder="<?php echo esc_attr( get_option( 'wpc_text_visit_site', 'Visit Site' ) ); ?>" />
                        </div>
                        <div class="wpc-col">
                            <label class="wpc-label" style="font-size: 11px;"><?php _e( '"Code" (Coupon)', 'wp-comparison-builder' ); ?></label>
                            <input type="text" name="wpc_txt_coupon_label" value="<?php echo esc_attr( $txt_coupon_label ); ?>" class="wpc-input" style="font-size: 12px;" placeholder="<?php echo esc_attr( get_option( 'wpc_text_coupon_label', 'Code' ) ); ?>" />
                        </div>
                        <div class="wpc-col">
                            <label class="wpc-label" style="font-size: 11px;"><?php _e( '"Feature" Header', 'wp-comparison-builder' ); ?></label>
                            <input type="text" name="wpc_txt_feature_header" value="<?php echo esc_attr( $txt_feature_header ); ?>" class="wpc-input" style="font-size: 12px;" placeholder="<?php echo esc_attr( get_option( 'wpc_text_feat_header', 'Feature' ) ); ?>" />
                        </div>
                    </div>
                     <div class="wpc-row" style="gap: 10px;">
                        <div class="wpc-col">
                             <label class="wpc-label" style="font-size: 11px;"><?php _e( '"Copied!" Text', 'wp-comparison-builder' ); ?></label>
                            <input type="text" name="wpc_txt_copied_label" value="<?php echo esc_attr( $txt_copied_label ); ?>" class="wpc-input" style="font-size: 12px;" placeholder="<?php echo esc_attr( get_option( 'wpc_text_copied', 'Copied!' ) ); ?>" />
                        </div>
                    </div>
                </div>
            </div>

            <h3 class="wpc-section-title" style="margin-top:20px;"><?php _e( 'Compare Alternatives', 'wp-comparison-builder' ); ?></h3>
             <div class="wpc-row">
                <div class="wpc-col">
                    <label class="wpc-label"><?php _e( 'Select Competitors', 'wp-comparison-builder' ); ?></label>
                    <?php
                    // Get all OTHER items (exclude current)
                    $all_other_items = get_posts( array(
                        'post_type' => 'comparison_item',
                        'posts_per_page' => -1,
                        'post_status' => 'publish',
                        'orderby' => 'title',
                        'order' => 'ASC',
                        'post__not_in' => array( $post->ID )
                    ));
                    
                    $selected_competitors = get_post_meta( $post->ID, '_wpc_competitors', true );
                    if ( ! is_array( $selected_competitors ) ) {
                        $selected_competitors = array();
                    }
                    ?>
                    <div class="wpc-checkbox-list" style="max-height: 200px;">
                        <?php if ( ! empty( $all_other_items ) ) : ?>
                            <?php foreach ( $all_other_items as $item ) : ?>
                                <label style="display:block;">
                                    <input 
                                        type="checkbox" 
                                        name="wpc_competitors[]" 
                                        value="<?php echo esc_attr( $item->ID ); ?>" 
                                        <?php checked( in_array( $item->ID, $selected_competitors ) ); ?>
                                    />
                                    <?php echo esc_html( $item->post_title ); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p style="color: #666; font-style: italic;">No other items available yet.</p>
                        <?php endif; ?>
                    </div>
                    <p class="description">Select items to show in "Compare Alternatives" dropdown on the frontend.</p>
                </div>
            </div>

            <h3 class="wpc-section-title" style="margin-top:20px;">Footer / Bottom Visibility</h3>
            <div class="wpc-row">
                <div class="wpc-col">
                    <label class="wpc-label"><?php _e( 'Footer Button (Visit Link)', 'wp-comparison-builder' ); ?></label>
                     <div style="margin-bottom:10px;">
                        <label style="display:block; margin-bottom:5px;">
                            <input type="checkbox" name="wpc_show_footer_popup" value="1" <?php checked( get_post_meta( $post->ID, '_wpc_show_footer_popup', true ) !== '0' ); ?> />
                            Show in Popup
                        </label>
                        <label style="display:block; margin-bottom:5px;">
                            <input type="checkbox" name="wpc_show_footer_table" value="1" <?php checked( get_post_meta( $post->ID, '_wpc_show_footer_table', true ) !== '0' ); ?> />
                            Show in Table
                        </label>
                     </div>
                    <input type="text" name="wpc_footer_button_text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_footer_button_text', true ) ); ?>" class="wpc-input" placeholder="Button Text (e.g. Visit Website)" />
                </div>
            </div>
            
            <!-- Custom Fields Repeater -->
            <h3 class="wpc-section-title" style="margin-top:20px;">Custom Fields <span style="font-weight: normal; font-size: 12px; color: #666;">(Add your own custom data)</span></h3>
            <p class="description" style="margin-bottom: 10px;">Add custom name/value pairs to store any additional data for this item. These will be included in schema output.</p>
            
            <?php 
            $custom_fields = get_post_meta( $post->ID, '_wpc_custom_fields', true );
            if ( ! is_array( $custom_fields ) ) $custom_fields = [];
            ?>
            
            <div id="wpc-custom-fields-container">
                <?php if ( ! empty( $custom_fields ) ) : ?>
                    <?php foreach ( $custom_fields as $index => $field ) : ?>
                        <div class="wpc-custom-field-row" style="display: flex; gap: 10px; margin-bottom: 8px; align-items: center;">
                            <input type="text" name="wpc_custom_fields[<?php echo $index; ?>][name]" value="<?php echo esc_attr( $field['name'] ?? '' ); ?>" placeholder="Field Name" style="flex: 1;" />
                            <input type="text" name="wpc_custom_fields[<?php echo $index; ?>][value]" value="<?php echo esc_attr( $field['value'] ?? '' ); ?>" placeholder="Field Value" style="flex: 2;" />
                            <button type="button" class="button wpc-remove-field" onclick="this.parentElement.remove();" title="Remove">&times;</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="button" id="wpc-add-custom-field">+ Add Custom Field</button>
            
            <script>
            (function() {
                var container = document.getElementById('wpc-custom-fields-container');
                var addBtn = document.getElementById('wpc-add-custom-field');
                var index = <?php echo max(0, count($custom_fields)); ?>;
                
                addBtn.addEventListener('click', function() {
                    var row = document.createElement('div');
                    row.className = 'wpc-custom-field-row';
                    row.style.cssText = 'display: flex; gap: 10px; margin-bottom: 8px; align-items: center;';
                    row.innerHTML = '<input type="text" name="wpc_custom_fields[' + index + '][name]" placeholder="Field Name" style="flex: 1;" />' +
                                    '<input type="text" name="wpc_custom_fields[' + index + '][value]" placeholder="Field Value" style="flex: 2;" />' +
                                    '<button type="button" class="button wpc-remove-field" onclick="this.parentElement.remove();" title="Remove">&times;</button>';
                    container.appendChild(row);
                    index++;
                });
            })();
            </script>
        </div>

        <!-- TAB: TAXONOMY -->
        <div id="wpc-tab-taxonomy" class="wpc-tab-content">
             <div class="wpc-row">
                <div class="wpc-col">
                    <h3 class="wpc-section-title"><?php _e( 'Categories', 'wp-comparison-builder' ); ?></h3>
                    <input type="text" id="wpc-cat-search" placeholder="Search categories..." style="width:100%; margin-bottom:5px;" onkeyup="wpcFilterList('wpc-cat-search', 'wpc-cat-list')" />
                    <div class="wpc-checkbox-list" id="wpc-cat-list">
                        <?php if ( ! empty( $all_cats ) && ! is_wp_error( $all_cats ) ) : ?>
                            <?php foreach ( $all_cats as $cat ) : ?>
                                <label style="display:block;">
                                    <input type="checkbox" name="wpc_category[]" value="<?php echo esc_attr( $cat->term_id ); ?>" <?php checked( in_array( $cat->term_id, $current_cats ) ); ?> onchange="wpcSyncPrimaryCats(this); wpcSyncVariantCats(this);" />
                                    <?php echo esc_html( $cat->name ); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p><?php _e( 'No categories found. Add one below.', 'wp-comparison-builder' ); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="wpc-add-new-wrap">
                        <input type="text" id="new-wpc-category" placeholder="New Category Name" />
                        <button type="button" class="button" onclick="wpcAddTerm('comparison_category')">Add</button>
                    </div>

                    <!-- Primary Categories Selection -->
                    <div style="margin-top: 15px;">
                        <label class="wpc-label"><?php _e( 'Primary Display Categories (Max 2)', 'wp-comparison-builder' ); ?></label>
                        <p class="description" style="margin-bottom:5px;">Select up to 2 categories to be shown by default on the card.</p>
                        <?php 
                        $primary_cats = get_post_meta( $post->ID, '_wpc_primary_cats', true ) ?: [];
                        ?>
                        <div class="wpc-checkbox-list" id="wpc-primary-cat-list" style="height: 100px;">
                             <?php if ( ! empty( $all_cats ) && ! is_wp_error( $all_cats ) ) : ?>
                                <?php foreach ( $all_cats as $cat ) : ?>
                                    <label style="display:block;" data-term-id="<?php echo esc_attr( $cat->term_id ); ?>" class="wpc-primary-option">
                                        <input type="checkbox" name="wpc_primary_cats[]" value="<?php echo esc_attr( $cat->term_id ); ?>" <?php checked( in_array( $cat->term_id, $primary_cats ) ); ?> />
                                        <?php echo esc_html( $cat->name ); ?>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Tags -->
                <div class="wpc-col">
                    <h3 class="wpc-section-title"><?php _e( 'Tags', 'wp-comparison-builder' ); ?></h3>
                    <input type="text" id="wpc-feature-search" placeholder="Search tags..." style="width:100%; margin-bottom:5px;" onkeyup="wpcFilterList('wpc-feature-search', 'wpc-feature-list')" />
                    <div class="wpc-checkbox-list" id="wpc-feature-list">
                        <?php if ( ! empty( $all_features ) && ! is_wp_error( $all_features ) ) : ?>
                            <?php foreach ( $all_features as $feature ) : ?>
                                <label style="display:block;">
                                    <input type="checkbox" name="wpc_features[]" value="<?php echo esc_attr( $feature->term_id ); ?>" <?php checked( in_array( $feature->term_id, $current_features ) ); ?> onchange="wpcSyncPrimaryFeatures(this)" />
                                    <?php echo esc_html( $feature->name ); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p>No tags found. Add one below.</p>
                        <?php endif; ?>
                    </div>
                    <div class="wpc-add-new-wrap">
                        <input type="text" id="new-wpc-feature" placeholder="New Tag Name" />
                        <button type="button" class="button" onclick="wpcAddTerm('comparison_feature')">Add</button>
                    </div>

                    <!-- Primary Display Tags (Max 3) -->
                    <div style="margin-top: 15px;">
                        <label class="wpc-label"><?php _e( 'Primary Display Tags (Max 3)', 'wp-comparison-builder' ); ?></label>
                        <p class="description" style="margin-bottom:5px;">Select up to 3 tags to be shown by default on the card (e.g. "Best Value").</p>
                        <?php 
                        $primary_features = get_post_meta( $post->ID, '_wpc_primary_features', true ) ?: [];
                        ?>
                        <div class="wpc-checkbox-list" id="wpc-primary-feature-list" style="height: 100px;">
                             <?php if ( ! empty( $all_features ) && ! is_wp_error( $all_features ) ) : ?>
                                <?php foreach ( $all_features as $feature ) : ?>
                                    <?php 
                                    $is_selected = in_array( $feature->term_id, $current_features );
                                    $style = $is_selected ? 'display:block;' : 'display:none;';
                                    ?>
                                    <label style="<?php echo $style; ?>" data-term-id="<?php echo esc_attr( $feature->term_id ); ?>" class="wpc-primary-feature-option">
                                        <input type="checkbox" name="wpc_primary_features[]" value="<?php echo esc_attr( $feature->term_id ); ?>" <?php checked( in_array( $feature->term_id, $primary_features ) ); ?> />
                                        <?php echo esc_html( $feature->name ); ?>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ( get_option('wpc_enable_variants_module') ) : ?>
            <!-- Active Categories for Variants -->
            <div style="margin-top: 25px; padding: 20px; background: #f0f9ff; border: 1px solid #0284c7; border-radius: 8px;">
                <h4 style="margin-top: 0; color: #0369a1; font-size: 15px;">📋 Active Categories for Variants</h4>
                <p style="margin-bottom: 15px; font-size: 13px; color: #64748b;">Select which categories should have Pricing Plans, Plan Features, and Best Use Cases tabs.</p>
                
                <div id="wpc-variant-categories-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 8px;">
                    <?php
                    $variant_cats = get_post_meta( $post->ID, '_wpc_variant_categories', true );
                    if ( ! is_array( $variant_cats ) ) $variant_cats = array();
                    
                    if ( ! empty( $all_cats ) && ! is_wp_error( $all_cats ) ) :
                        foreach ( $all_cats as $vcat ) :
                            $is_assigned = in_array( $vcat->term_id, $current_cats );
                            $style = $is_assigned ? 'display: flex;' : 'display: none;';
                    ?>
                    <label class="wpc-variant-cat-option" data-term-id="<?php echo esc_attr( $vcat->term_id ); ?>" style="<?php echo $style; ?> align-items: center; gap: 8px; padding: 8px; background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; cursor: pointer;">
                        <input type="checkbox" name="wpc_variant_categories[]" value="<?php echo esc_attr( $vcat->term_id ); ?>" <?php checked( in_array( $vcat->term_id, $variant_cats ) ); ?> style="width: 16px; height: 16px;" />
                        <span style="font-size: 13px;"><?php echo esc_html( $vcat->name ); ?></span>
                    </label>
                    <?php 
                        endforeach;
                    endif; ?>
                </div>
                
                <p style="margin-top: 10px; margin-bottom: 0; font-size: 11px; color: #64748b;">💡 If nothing selected, all assigned categories will show tabs.</p>
            </div>
            
            <?php endif; ?>
        </div>

          <!-- TAB: PLAN FEATURES -->
        <div id="wpc-tab-plan_features" class="wpc-tab-content">
            <?php
            // Use new category-aware Plan Features tab if function exists
            if ( function_exists( 'wpc_render_plan_features_tab' ) ) {
                wpc_render_plan_features_tab( $post );
            } else {
                echo '<p>Plan Features module not loaded. Please check plugin files.</p>';
            }
            ?>
        </div>



        <!-- TAB: SEO -->

        <!-- TAB: SEO -->
        <div id="wpc-tab-seo" class="wpc-tab-content">
             <!-- Schema & Product Category -->
            <div class="wpc-row">
                <div class="wpc-col">
                    <h3 class="wpc-section-title">Schema & Product Data</h3>
                    <?php $current_schema_cat = get_post_meta( $post->ID, '_wpc_product_category', true ) ?: 'SoftwareApplication'; ?>
                    <label class="wpc-label"><?php _e( 'Product Category (Schema Type)', 'wp-comparison-builder' ); ?></label>
                    <select name="wpc_product_category" id="wpc_product_category" class="wpc-input" style="margin-bottom: 15px;">
                        <option value="SoftwareApplication" <?php selected( $current_schema_cat, 'SoftwareApplication' ); ?>>Digital / Software (Default)</option>
                        <option value="Product" <?php selected( $current_schema_cat, 'Product' ); ?>>Physical Product</option>
                        <option value="Service" <?php selected( $current_schema_cat, 'Service' ); ?>>Service</option>
                        <option value="Course" <?php selected( $current_schema_cat, 'Course' ); ?>>Course</option>
                    </select>
                    
                    <!-- Dynamic Fields Container -->
                    <div id="wpc-schema-fields">
                        <!-- Common Identity Fields -->
                        <div class="wpc-field-group" data-show-for="SoftwareApplication Product Service Course">
                            <label class="wpc-label">Provider / Brand Name</label>
                            <input type="text" name="wpc_brand" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_brand', true ) ); ?>" class="wpc-input" placeholder="e.g. Sony, Coursera, Hostinger" />
                        </div>
                        
                        <!-- Physical Product Fields -->
                        <div class="wpc-field-group" data-show-for="Product" style="display:none; margin-top: 10px; border-left: 3px solid #6366f1; padding-left: 10px;">
                            <h4 style="margin: 5px 0 10px;">Physical Product Details</h4>
                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <div style="flex:1;">
                                    <label class="wpc-label">SKU</label>
                                    <input type="text" name="wpc_sku" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_sku', true ) ); ?>" class="wpc-input" />
                                </div>
                                <div style="flex:1;">
                                    <label class="wpc-label">GTIN / MPN</label>
                                    <input type="text" name="wpc_gtin" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_gtin', true ) ); ?>" class="wpc-input" />
                                </div>
                            </div>
                            <!-- ... rest of product fields ... -->
                             <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <div style="flex:1;">
                                    <label class="wpc-label">Condition</label>
                                    <select name="wpc_condition" class="wpc-input">
                                        <?php $cond = get_post_meta( $post->ID, '_wpc_condition', true ) ?: 'NewCondition'; ?>
                                        <option value="NewCondition" <?php selected($cond, 'NewCondition'); ?>>New</option>
                                        <option value="UsedCondition" <?php selected($cond, 'UsedCondition'); ?>>Used</option>
                                        <option value="RefurbishedCondition" <?php selected($cond, 'RefurbishedCondition'); ?>>Refurbished</option>
                                    </select>
                                </div>
                                <div style="flex:1;">
                                    <label class="wpc-label">Availability</label>
                                    <select name="wpc_availability" class="wpc-input">
                                        <?php $avail = get_post_meta( $post->ID, '_wpc_availability', true ) ?: 'InStock'; ?>
                                        <option value="InStock" <?php selected($avail, 'InStock'); ?>>In Stock</option>
                                        <option value="OutOfStock" <?php selected($avail, 'OutOfStock'); ?>>Out of Stock</option>
                                        <option value="PreOrder" <?php selected($avail, 'PreOrder'); ?>>Pre-Order</option>
                                    </select>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <div style="flex:1;">
                                    <label class="wpc-label">MFG Date</label>
                                    <input type="date" name="wpc_mfg_date" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_mfg_date', true ) ); ?>" class="wpc-input" />
                                </div>
                                <div style="flex:1;">
                                    <label class="wpc-label">Expiry Date</label>
                                    <input type="date" name="wpc_exp_date" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_exp_date', true ) ); ?>" class="wpc-input" />
                                </div>
                            </div>
                        </div>

                        <!-- Service Fields -->
                        <div class="wpc-field-group" data-show-for="Service" style="display:none; margin-top: 10px; border-left: 3px solid #10b981; padding-left: 10px;">
                            <h4 style="margin: 5px 0 10px;">Service Details</h4>
                            <div style="margin-bottom: 10px;">
                                <label class="wpc-label">Service Type</label>
                                <input type="text" name="wpc_service_type" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_service_type', true ) ); ?>" class="wpc-input" placeholder="e.g. Plumbing, Web Hosting, Consulting" />
                            </div>
                            <div>
                                <label class="wpc-label">Area Served (City/Country)</label>
                                <input type="text" name="wpc_area_served" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_area_served', true ) ); ?>" class="wpc-input" />
                            </div>
                        </div>

                        <!-- Course Fields -->
                        <div class="wpc-field-group" data-show-for="Course" style="display:none; margin-top: 10px; border-left: 3px solid #f59e0b; padding-left: 10px;">
                            <h4 style="margin: 5px 0 10px;">Course Details</h4>
                            <div style="margin-bottom: 10px;">
                                 <label class="wpc-label">Duration (ISO 8601)</label>
                                 <input type="text" name="wpc_duration" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_duration', true ) ); ?>" class="wpc-input" placeholder="e.g. PT10H (10 Hours)" />
                                 <p class="description"><a href="https://en.wikipedia.org/wiki/ISO_8601#Durations" target="_blank">ISO 8601 Format</a> required for Schema.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <script>
                jQuery(document).ready(function($) {
                    const catSelect = $('#wpc_product_category');
                    const fieldGroups = $('.wpc-field-group');
                    
                    function updateFields() {
                        const selected = catSelect.val();
                        fieldGroups.each(function() {
                            const showFor = $(this).data('show-for').split(' ');
                            if (showFor.includes(selected)) {
                                $(this).slideDown(200);
                            } else {
                                $(this).slideUp(200);
                            }
                        });
                    }
                    
                    catSelect.on('change', updateFields);
                    updateFields(); // Init
                });
                </script>
            </div>

            <!-- Schema Preview Section -->
            <?php 
            $schema_settings = function_exists('wpc_get_schema_settings') ? wpc_get_schema_settings() : array('enabled' => '1');
            if ( $schema_settings['enabled'] === '1' && $post->ID ): 
            ?>
            <div style="margin-top: 30px; padding: 20px; background: #f0fdf4; border: 2px solid #16a34a; border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin: 0; color: #166534;">Schema SEO Preview</h3>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <button type="button" class="button" id="wpc-copy-schema-btn" onclick="wpcCopySchema()">&#128203; Copy Schema</button>
                        <span id="wpc-copy-status" style="color: #16a34a; font-size: 12px; display: none;">&#10003; Copied!</span>
                        <a href="https://search.google.com/test/rich-results" target="_blank" class="button">&#128269; Test with Google</a>
                    </div>
                </div>
                <p style="margin: 0 0 10px 0; color: #15803d; font-size: 13px;">
                    This JSON-LD schema will be output when this item appears on the frontend. Save the item to see updated schema.
                </p>
                <pre id="wpc-schema-preview" style="background: #fff; padding: 15px; border-radius: 4px; border: 1px solid #bbf7d0; overflow-x: auto; font-size: 12px; max-height: 400px; overflow-y: auto;"><?php 
                    if ( function_exists( 'wpc_get_schema_preview' ) ) {
                        echo esc_html( wpc_get_schema_preview( $post->ID ) );
                    } else {
                        echo '// Schema preview not available. Make sure seo-schema.php is loaded.';
                    }
                ?></pre>
                
                <script>
                function wpcCopySchema() {
                    var schemaText = document.getElementById('wpc-schema-preview').textContent;
                    var btn = document.getElementById('wpc-copy-schema-btn');
                    // Removed statusEl as it was redundant
                    
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(schemaText).then(function() {
                            var originalText = btn.innerHTML;
                            btn.innerHTML = '&#10003; Copied!';
                            setTimeout(function() {
                                btn.innerHTML = originalText;
                            }, 2000);
                        }).catch(function() {
                            fallbackCopy(schemaText, btn);
                        });
                    } else {
                        fallbackCopy(schemaText, btn);
                    }
                }
                
                function fallbackCopy(text, btn) {
                    var textarea = document.createElement('textarea');
                    textarea.value = text;
                    textarea.style.position = 'fixed';
                    textarea.style.opacity = '0';
                    document.body.appendChild(textarea);
                    textarea.select();
                    try {
                        document.execCommand('copy');
                        var originalText = btn.innerHTML;
                        btn.innerHTML = '&#10003; Copied!';
                        setTimeout(function() {
                            btn.innerHTML = originalText;
                        }, 2000);
                    } catch(e) {
                        wpcShowToast('Copy failed. Please copy manually.', true);
                    }
                    document.body.removeChild(textarea);
                }
                </script>
            </div>
            
            <?php endif; ?>
        </div>

        <!-- TAB: SHORTCODES -->
        <div id="wpc-tab-shortcodes" class="wpc-tab-content">
            <h2 style="margin-top: 0; color: #1e293b; font-size: 18px; margin-bottom: 20px;">📋 Available Shortcodes</h2>
            <p style="margin-bottom: 25px; color: #64748b;">Click copy to get the shortcode for this item. Paste it anywhere on your site to display the content.</p>
            
            <!-- Hero Shortcode -->
            <div style="background:#faf5ff; border:2px solid #c084fc; padding:20px; border-radius:8px; margin-bottom:20px;">
                <div style="display:flex; align-items:start; gap:15px;">
                    <span style="font-size: 32px;">🎯</span>
                    <div style="flex: 1;">
                        <h3 style="margin-top:0; margin-bottom:8px; color: #7c3aed; font-size:16px;">Hero Section</h3>
                        <p style="margin-bottom: 12px; font-size:13px; color: #6b7280;">Displays a full-width hero section with logo, description, pricing, and call-to-action buttons.</p>
                        <div style="display:flex; align-items:center; gap:10px; flex-wrap: wrap;">
                            <code style="background:#fff; padding:10px 14px; border:1px solid #dde1e5; border-radius:6px; font-size:13px; color:#7c3aed; flex: 1; min-width: 200px;">
                                [wpc_hero id="<?php echo $post->ID; ?>"]
                            </code>
                            <button type="button" class="button button-primary" onclick="wpcCopyShortcodeGeneric('[wpc_hero id=<?php echo $post->ID; ?>]', this)">📋 Copy</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing Table Shortcode -->
            <div style="background:#f0f9ff; border:2px solid #60a5fa; padding:20px; border-radius:8px; margin-bottom:20px;">
                <div style="display:flex; align-items:start; gap:15px;">
                    <span style="font-size: 32px;">💰</span>
                    <div style="flex: 1;">
                        <h3 style="margin-top:0; margin-bottom:8px; color: #2563eb; font-size:16px;">Pricing Table</h3>
                        <p style="margin-bottom: 12px; font-size:13px; color: #6b7280;">Shows all pricing plans with features, pricing, and purchase buttons in a responsive table.</p>
                        <div style="display:flex; align-items:center; gap:10px; flex-wrap: wrap;">
                            <code style="background:#fff; padding:10px 14px; border:1px solid #dde1e5; border-radius:6px; font-size:13px; color:#2563eb; flex: 1; min-width: 200px;">
                                [wpc_pricing_table id="<?php echo $post->ID; ?>"]
                            </code>
                            <button type="button" class="button button-primary" onclick="wpcCopyShortcodeGeneric('[wpc_pricing_table id=<?php echo $post->ID; ?>]', this)">📋 Copy</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pros & Cons Shortcode -->
            <div style="background:#ecfdf5; border:2px solid #34d399; padding:20px; border-radius:8px; margin-bottom:20px;">
                <div style="display:flex; align-items:start; gap:15px;">
                    <span style="font-size: 32px;">⚖️</span>
                    <div style="flex: 1;">
                        <h3 style="margin-top:0; margin-bottom:8px; color: #047857; font-size:16px;">Pros & Cons</h3>
                        <p style="margin-bottom: 12px; font-size:13px; color: #6b7280;">Displays the advantages and disadvantages in a clean, easy-to-read format.</p>
                        <div style="display:flex; align-items:center; gap:10px; flex-wrap: wrap;">
                            <code style="background:#fff; padding:10px 14px; border:1px solid #dde1e5; border-radius:6px; font-size:13px; color:#047857; flex: 1; min-width: 200px;">
                                [wpc_pros_cons id="<?php echo $post->ID; ?>"]
                            </code>
                            <button type="button" class="button button-primary" onclick="wpcCopyShortcodeGeneric('[wpc_pros_cons id=<?php echo $post->ID; ?>]', this)">📋 Copy</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature Table Shortcode -->
            <div style="background:#fff7ed; border:2px solid #fb923c; padding:20px; border-radius:8px; margin-bottom:20px;">
                <div style="display:flex; align-items:start; gap:15px;">
                    <span style="font-size: 32px;">📊</span>
                    <div style="flex: 1;">
                        <h3 style="margin-top:0; margin-bottom:8px; color: #ea580c; font-size:16px;">Feature Comparison Table</h3>
                        <p style="margin-bottom: 12px; font-size:13px; color: #6b7280;">Shows detailed feature comparison across all pricing plans.</p>
                        <div style="display:flex; align-items:center; gap:10px; flex-wrap: wrap;">
                            <code style="background:#fff; padding:10px 14px; border:1px solid #dde1e5; border-radius:6px; font-size:13px; color:#ea580c; flex: 1; min-width: 200px;">
                                [wpc_feature_table id="<?php echo $post->ID; ?>"]
                            </code>
                            <button type="button" class="button button-primary" onclick="wpcCopyShortcodeGeneric('[wpc_feature_table id=<?php echo $post->ID; ?>]', this)">📋 Copy</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Best Use Cases Shortcode -->
            <div style="background:#f5f3ff; border:2px solid #8b5cf6; padding:20px; border-radius:8px; margin-bottom:20px;">
                <div style="display:flex; align-items:start; gap:15px;">
                    <div style="flex: 1;">
                        <h3 style="margin-top:0; margin-bottom:8px; color: #7c3aed; font-size:16px;">Best Use Cases</h3>
                        <p style="margin-bottom: 12px; font-size:13px; color: #6b7280;">Displays the "Best For..." highlights grid.</p>
                        <div style="display:flex; align-items:center; gap:10px; flex-wrap: wrap;">
                            <code style="background:#fff; padding:10px 14px; border:1px solid #dde1e5; border-radius:6px; font-size:13px; color:#7c3aed; flex: 1; min-width: 200px;">
                                [wpc_use_cases id="<?php echo $post->ID; ?>"]
                            </code>
                            <button type="button" class="button button-primary" onclick="wpcCopyShortcodeGeneric('[wpc_use_cases id=<?php echo $post->ID; ?>]', this)">📋 Copy</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            function wpcCopyShortcodeGeneric(text, btn) {
                if (!navigator.clipboard) {
                    // Fallback
                    var textArea = document.createElement("textarea");
                    textArea.value = text;
                    document.body.appendChild(textArea);
                    textArea.select();
                    try {
                        document.execCommand('copy');
                        var originalText = btn.innerHTML;
                        btn.innerHTML = '✓ Copied!';
                        setTimeout(function() { btn.innerHTML = originalText; }, 2000);
                    } catch (err) {
                        wpcShowToast('Copy failed. Please copy manually.', true);
                    }
                    document.body.removeChild(textArea);
                    return;
                }

                navigator.clipboard.writeText(text).then(function() {
                    var originalText = btn.innerHTML;
                    btn.innerHTML = '✓ Copied!';
                    setTimeout(function() { btn.innerHTML = originalText; }, 2000);
                }, function(err) {
                    wpcShowToast('Copy failed: ' + err, true);
                });
            }
            </script>
        </div>

        <!-- Toast Notification Container -->
        <div id="wpc-toast" style="visibility: hidden; min-width: 250px; background-color: #333; color: #fff; text-align: center; border-radius: 4px; padding: 16px; position: fixed; z-index: 9999; left: 50%; bottom: 30px; transform: translateX(-50%); opacity: 0; transition: opacity 0.3s, bottom 0.3s; font-size: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            Message Here
        </div>

        <!-- Custom Confirmation Modal -->
        <div id="wpc-confirm-modal" style="display:none; position:fixed; z-index:10000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
            <div style="background-color:#fff; margin:15% auto; padding:20px; border-radius:8px; width:400px; max-width:90%; position:relative; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <h3 style="margin-top:0;">Confirm Import</h3>
                <p>This will overwrite existing fields with the imported data. Are you sure you want to continue?</p>
                <div style="text-align:right; margin-top:20px;">
                    <button type="button" class="button" onclick="document.getElementById('wpc-confirm-modal').style.display='none'">Cancel</button>
                    <button type="button" class="button button-primary" id="wpc-confirm-btn">Yes, Import Data</button>
                </div>
            </div>
        </div>

        <!-- TAB: USE CASES -->
        <?php include WPC_PLUGIN_DIR . 'includes/use-cases-tab-enhanced.php'; ?>
        


        <?php if ( get_option( 'wpc_enable_tools_module', false ) ) : ?>
        <!-- TAB: TOOL COLLECTIONS -->
        <div id="wpc-tab-tools_collections" class="wpc-tab-content">
            <h3 class="wpc-section-title">Recommended Tools</h3>
            <p class="description">
                Select tools to associate with this item. Use the shortcode <code>[wpc_tools]</code> to display them on your site.
            </p>
            
            <?php
            // Get all published tools
            $tools_query = new WP_Query(array(
                'post_type' => 'comparison_tool',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC'
            ));
            
            $selected_tools = get_post_meta( $post->ID, '_wpc_selected_tools', true ) ?: array();
            ?>
            
            <?php if ( $tools_query->have_posts() ) : ?>
                <div style="background: #fff; border: 1px solid #ddd; border-radius: 6px; padding: 20px; margin: 20px 0;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                        <?php while ( $tools_query->have_posts() ) : $tools_query->the_post(); ?>
                            <?php
                            $tool_id = get_the_ID();
                            $checked = in_array( $tool_id, (array) $selected_tools );
                            $badge = get_post_meta( $tool_id, '_tool_badge', true );
                            $logo = get_the_post_thumbnail_url( $tool_id, 'thumbnail' );
                            ?>
                            <label style="display: flex; align-items: center; gap: 12px; padding: 12px; border: 2px solid <?php echo $checked ? '#6366f1' : '#e5e7eb'; ?>; border-radius: 8px; cursor: pointer; transition: all 0.2s; background: <?php echo $checked ? '#f0f4ff' : '#fff'; ?>;">
                                <input type="checkbox" name="wpc_selected_tools[]" value="<?php echo $tool_id; ?>" <?php checked( $checked ); ?> style="margin: 0;" />
                                <?php if ( $logo ) : ?>
                                <img src="<?php echo esc_url( $logo ); ?>" alt="" style="width: 40px; height: 40px; object-fit: contain; border-radius: 4px;" />
                                <?php endif; ?>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 600; font-size: 14px; color: #0f172a; margin-bottom: 2px;"><?php the_title(); ?></div>
                                    <?php if ( $badge ) : ?>
                                    <div style="font-size: 11px; color: #6366f1;"><?php echo esc_html( $badge ); ?></div>
                                    <?php endif; ?>
                                </div>
                            </label>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                </div>
                
                <div style="background: #f0f9ff; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; border-radius: 4px;">
                    <strong style="color: #1e40af;">Shortcode:</strong>
                    <p style="margin: 10px 0 0 0;">
                        Use <code>[wpc_tools]</code> on this item's page to display selected tools automatically.
                        <br>Or use <code>[wpc_tools ids="<?php echo implode(',', array_slice((array)$selected_tools, 0, 3)); ?>"]</code> to display specific tools anywhere.
                    </p>
                </div>
            <?php else : ?>
                <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 4px;">
                    <strong style="color: #92400e;">No tools found.</strong>
                    <p style="margin: 10px 0 0 0;">
                        <a href="<?php echo admin_url('post-new.php?post_type=comparison_tool'); ?>">Create your first tool</a> to get started.
                    </p>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- TAB: DATA IMPORT -->
        <div id="wpc-tab-import" class="wpc-tab-content">
            <h3 class="wpc-section-title">Import Data from JSON</h3>
            <p class="description">Paste a JSON object below to auto-fill this item's fields. Existing data will be overwritten/appended.</p>
            
            <div class="wpc-row">
                <div class="wpc-col">
                    <div style="margin-bottom: 10px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <button type="button" class="button button-primary button-large" onclick="wpcRequestImport()">📥 Import JSON</button>
                        <input type="file" id="wpc-import-file" accept=".json" style="line-height: 28px;" />
                        <span style="color:#abaaaa;">or paste below</span>
                        <div style="flex:1; text-align:right;">
                             <button type="button" class="button" onclick="wpcShowExampleJson()">View Example</button>
                             <button type="button" class="button" onclick="document.getElementById('wpc-import-json').value = '';">Clear</button>
                        </div>
                    </div>
                    <textarea id="wpc-import-json" rows="15" class="wpc-input" style="font-family: monospace; font-size: 12px; white-space: pre;" placeholder="{ ... }"></textarea>
                </div>
            </div>


            <script>
            // Toast Notification
            function wpcShowToast(message, isError = false) {
                var x = document.getElementById("wpc-toast");
                x.innerHTML = message;
                x.style.backgroundColor = isError ? "#ef4444" : "#10b981";
                x.style.visibility = "visible";
                x.style.opacity = "1";
                x.style.bottom = "30px";
                setTimeout(function(){ 
                    x.style.visibility = "hidden"; 
                    x.style.opacity = "0";
                    x.style.bottom = "0px";
                }, 3000);
            }

            function wpcShowExampleJson() {
                const example = {
                    "public_name": "Product Name",
                    "title": "Product Name Review 2024",
                    "general": {
                        "price": "$29.00",
                        "period": "/mo",
                        "rating": "4.8",
                        "details_link": "https://example.com/review",
                        "direct_link": "https://example.com/go",
                        "button_text": "Visit Website"
                    },
                    "content": {
                        "pros": "Fast Support\nCheap\nReliable",
                        "cons": "No Phone Support\nLimited Storage",
                        "labels": {
                            "pros": "Good Stuff",
                            "cons": "Bad Stuff",
                            "price": "Cost",
                            "rating": "Score",
                            "mo_suffix": "/month",
                            "visit_site": "Go Now",
                            "coupon": "Promo Code",
                            "copied": "Code Copied!"
                        }
                    },
                    "use_cases": [
                        { "name": "Startups", "desc": "Great for new businesses.", "icon": "fa-solid fa-rocket", "image": "" },
                        { "name": "Agencies", "desc": "Manage multiple clients.", "icon": "fa-solid fa-briefcase", "image": "" }
                    ],
                    "visuals": {
                        "logo": "https://example.com/logo.png",
                        "dashboard_image": "https://example.com/dash.jpg",
                        "badge_text": "Top Choice",
                        "badge_color": "#ff0000",
                        "colors": {
                            "primary": "#6366f1",
                            "accent": "#818cf8",
                            "border": "#e2e8f0",
                            "coupon_bg": "#fef3c7",
                            "coupon_text": "#92400e"
                        }
                    },
                    "pricing_plans": [
                        {
                            "name": "Basic",
                            "price": "$29",
                            "period": "/mo",
                            "features": "1 Site\n10GB Storage",
                            "link": "https://example.com/basic",
                            "coupon": "SAVE20",
                            "banner_text": "Best Value",
                            "banner_color": "#10b981",
                            "show_popup": true,
                            "show_table": true
                        }
                    ],
                    "seo": {
                        "schema_type": "SoftwareApplication",
                        "brand": "ProviderName",
                        "sku": "SKU123"
                    },
                    "categories": ["Category A", "Category B"],
                    "tags": ["Tag 1", "Tag 2"],
                    "competitors": ["Competitor A", "Competitor B"],
                    // Note: Ensure plans are loaded before importing features for best results
                    "plan_features": [
                        { "name": "Free SSL", "included_in": [0] }
                    ],
                    "custom_fields": [
                        { "name": "Server Location", "value": "USA" }
                    ],
                    "use_cases": [
                        { "name": "Startups", "desc": "Great for early stage...", "icon": "fa-solid fa-rocket", "image": "" }
                    ],
                    "recommended_tools": ["Tool Name A", "Tool Name B"]
                };
                document.getElementById('wpc-import-json').value = JSON.stringify(example, null, 2);
            }

            // Global to hold parsed data pending import
            let wpcPendingImportData = null;

            async function wpcRequestImport() {
                // 1. Handle File Upload Priority
                const fileInput = document.getElementById('wpc-import-file');
                let jsonStr = '';

                if (fileInput.files.length > 0) {
                    try {
                        jsonStr = await fileInput.files[0].text();
                    } catch (err) {
                        wpcShowToast('Failed to read file: ' + err.message, true);
                        return;
                    }
                } else {
                    jsonStr = document.getElementById('wpc-import-json').value;
                }

                if (!jsonStr.trim()) {
                    wpcShowToast('Please paste JSON or select a file.', true);
                    return;
                }

                try {
                    wpcPendingImportData = JSON.parse(jsonStr);
                } catch (e) {
                    wpcShowToast('Invalid JSON: ' + e.message, true);
                    return;
                }

                // Show Modal
                document.getElementById('wpc-confirm-modal').style.display = 'block';
                
                // Bind click event once
                const btn = document.getElementById('wpc-confirm-btn');
                btn.onclick = function() {
                    document.getElementById('wpc-confirm-modal').style.display = 'none';
                    if (wpcPendingImportData) {
                        wpcExecuteImport(wpcPendingImportData);
                    }
                };
            }

            async function wpcExecuteImport(data) {
                // Helper to set value
                const setVal = (name, val) => {
                    const el = document.querySelector(`[name="${name}"]`);
                    if (el) el.value = val || '';
                };
                const setCheck = (name, checked) => {
                    const el = document.querySelector(`[name="${name}"]`);
                    if (el) el.checked = !!checked;
                };

                // 0. Title & Description
                if (data.title) {
                    const titleEl = document.getElementById('title');
                    if (titleEl) {
                         titleEl.value = data.title;
                         titleEl.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
                if (data.public_name) {
                    setVal('wpc_public_name', data.public_name);
                }
                if (data.description) {
                    setVal('wpc_short_description', data.description);
                }

                // 1. General
                if (data.general) {
                    setVal('wpc_price', data.general.price);
                    setVal('wpc_period', data.general.period);
                    setVal('wpc_rating', data.general.rating);
                    setVal('wpc_details_link', data.general.details_link);
                    setVal('wpc_direct_link', data.general.direct_link);
                    setVal('wpc_button_text', data.general.button_text);
                }

                // 2. Content
                if (data.content) {
                    if (data.content.pros) {
                        const prosVal = Array.isArray(data.content.pros) ? data.content.pros.join('\n') : data.content.pros;
                        document.querySelector('[name="wpc_pros"]').value = prosVal;
                    }
                    if (data.content.cons) {
                        const consVal = Array.isArray(data.content.cons) ? data.content.cons.join('\n') : data.content.cons;
                        document.querySelector('[name="wpc_cons"]').value = consVal;
                    }
                    
                    if (data.content.labels) {
                        setVal('wpc_txt_pros_label', data.content.labels.pros);
                        setVal('wpc_txt_cons_label', data.content.labels.cons);
                        setVal('wpc_txt_price_label', data.content.labels.price);
                        setVal('wpc_txt_rating_label', data.content.labels.rating);
                        setVal('wpc_txt_mo_suffix', data.content.labels.mo_suffix);
                        setVal('wpc_txt_visit_site', data.content.labels.visit_site);
                        setVal('wpc_txt_coupon_label', data.content.labels.coupon);
                        setVal('wpc_txt_copied_label', data.content.labels.copied);
                    }
                }

                // 3. Visuals
                if (data.visuals) {
                    setVal('wpc_external_logo_url', data.visuals.logo);
                    setVal('wpc_dashboard_image', data.visuals.dashboard_image);
                    setVal('wpc_featured_badge_text', data.visuals.badge_text);
                    setVal('wpc_featured_color', data.visuals.badge_color);
                    setVal('wpc_hero_subtitle', data.visuals.hero_subtitle);
                    setVal('wpc_analysis_label', data.visuals.analysis_label);

                    if (data.visuals.colors) {
                        setCheck('wpc_enable_design_overrides', true);
                        const overrideCb = document.querySelector('[name="wpc_enable_design_overrides"]');
                        if (overrideCb) overrideCb.dispatchEvent(new Event('change')); 
                        
                        setVal('wpc_primary_color', data.visuals.colors.primary);
                        setVal('wpc_accent_color', data.visuals.colors.accent);
                        setVal('wpc_border_color', data.visuals.colors.border);
                        setVal('wpc_color_coupon_bg', data.visuals.colors.coupon_bg);
                        setVal('wpc_color_coupon_text', data.visuals.colors.coupon_text);
                    }
                }

                // 4. Pricing Plans
                if (data.pricing_plans && Array.isArray(data.pricing_plans)) {
                    const container = document.getElementById('wpc-plans-container');
                    // Optional: Clear existing plans before import to avoid duplicates
                    // Clear existing plans before import to avoid duplicates
                    if (data.pricing_plans.length > 0) {
                        container.innerHTML = '';
                    } 
                    
                    data.pricing_plans.forEach(plan => {
                        wpcAddPlan(); 
                        const row = container.lastElementChild;
                        if (!row) return;

                        const find = (sel) => row.querySelector(sel);
                        if (find('input[name*="[name]"]')) find('input[name*="[name]"]').value = plan.name || '';

                        // Handle Dynamic Price Inputs
                        const priceGenericInputs = row.querySelectorAll('.wpc-price-group');
                        priceGenericInputs.forEach(group => {
                            const slug = group.getAttribute('data-slug');
                            const amtInput = group.querySelector('input[name*="[amount]"]');
                            const prdInput = group.querySelector('input[name*="[period]"]');
                            
                            if (slug === 'monthly') {
                                if (amtInput) amtInput.value = plan.price || (plan.prices && plan.prices.monthly && plan.prices.monthly.amount) || '';
                                if (prdInput) prdInput.value = plan.period || (plan.prices && plan.prices.monthly && plan.prices.monthly.period) || '/mo';
                            } else if (slug === 'yearly') {
                                if (amtInput) amtInput.value = plan.yearly_price || (plan.prices && plan.prices.yearly && plan.prices.yearly.amount) || '';
                                if (prdInput) prdInput.value = plan.yearly_period || (plan.prices && plan.prices.yearly && plan.prices.yearly.period) || '/yr';
                            } else {
                                // Fallback for custom slugs if AI provides them in the new 'prices' object structure
                                if (plan.prices && plan.prices[slug]) {
                                     if (amtInput) amtInput.value = plan.prices[slug].amount || '';
                                     if (prdInput) prdInput.value = plan.prices[slug].period || '';
                                }
                            }
                        });
                        if (find('input[name*="[coupon]"]')) find('input[name*="[coupon]"]').value = plan.coupon || '';
                        
                        if (plan.show_banner) {
                             const cb = find('input[name*="[show_banner]"]');
                             if(cb) cb.checked = true;
                        }
                        if (find('input[name*="[banner_text]"]')) find('input[name*="[banner_text]"]').value = plan.banner_text || '';
                        if (find('input[name*="[banner_color]"]')) find('input[name*="[banner_color]"]').value = plan.banner_color || '#10b981';

                        if (find('textarea[name*="[features]"]')) {
                            const featVal = Array.isArray(plan.features) ? plan.features.join('\n') : (plan.features || '');
                            find('textarea[name*="[features]"]').value = featVal;
                        }
                        if (find('input[name*="[link]"]')) find('input[name*="[link]"]').value = plan.link || '';
                        if (find('input[name*="[button_text]"]')) find('input[name*="[button_text]"]').value = plan.button_text || '';

                        const popCb = find('input[name*="[show_popup]"]');
                        if (popCb) popCb.checked = !!plan.show_popup;
                        const tblCb = find('input[name*="[show_table]"]');
                        if (tblCb) tblCb.checked = !!plan.show_table;
                    });

                    // Sync Plan Headers for Feature Table (Crucial for Plan Features import)
                    wpcSyncPlanHeaders(data.pricing_plans);
                }

                // 5. SEO
                if (data.seo) {
                    const catSelect = document.getElementById('wpc_product_category');
                    if (data.seo.schema_type && catSelect) {
                        catSelect.value = data.seo.schema_type;
                        catSelect.dispatchEvent(new Event('change')); 
                    }
                    setVal('wpc_brand', data.seo.brand);
                    setVal('wpc_sku', data.seo.sku);
                    setVal('wpc_gtin', data.seo.gtin);
                    
                    setVal('wpc_condition', data.seo.condition);
                    setVal('wpc_availability', data.seo.availability);
                    setVal('wpc_mfg_date', data.seo.mfg_date);
                    setVal('wpc_exp_date', data.seo.exp_date);
                    
                    setVal('wpc_service_type', data.seo.service_type);
                    setVal('wpc_area_served', data.seo.area_served);
                    setVal('wpc_duration', data.seo.duration);
                }
                
                // 6. Competitors
                if (data.competitors && Array.isArray(data.competitors)) {
                    const labels = document.querySelectorAll('.wpc-checkbox-list label');
                    data.competitors.forEach(compName => {
                        labels.forEach(label => {
                            if (label.innerText.trim() === compName.trim()) {
                                const cb = label.querySelector('input[name="wpc_competitors[]"]');
                                if (cb) cb.checked = true;
                            }
                        });
                    });
                }

                // 7. Plan Features
                if (data.plan_features && Array.isArray(data.plan_features)) {
                   if (typeof wpcAddFeatureRow !== 'function') {
                       console.warn('wpcAddFeatureRow not available.');
                   } else {
                       // Clear existing features to allow clean import
                       const tbody = document.getElementById('wpc-features-tbody');
                       tbody.innerHTML = ''; 

                       data.plan_features.forEach(feat => {
                           wpcAddFeatureRow(feat.name);
                           const row = tbody.lastElementChild;
                           if (row && feat.included_in && Array.isArray(feat.included_in)) {
                               feat.included_in.forEach(planIdx => {
                                   const cb = row.querySelector('.wpc-feature-plan-checkbox.plan-' + planIdx);
                                   if (cb) cb.checked = true;
                               });
                           }
                       });
                   }
                }

                // 8. Custom Fields
                if (data.custom_fields && Array.isArray(data.custom_fields)) {
                    const container = document.getElementById('wpc-custom-fields-container');
                    const addBtn = document.getElementById('wpc-add-custom-field');
                    
                    data.custom_fields.forEach(field => {
                        if(addBtn) addBtn.click();
                        const row = container.lastElementChild;
                        if (!row) return;
                        
                        const nameInput = row.querySelector('input[name*="[name]"]');
                        const valInput = row.querySelector('input[name*="[value]"]');
                        
                        if (nameInput) nameInput.value = field.name || '';
                        if (valInput) valInput.value = field.value || '';
                    });
                }

                // 9. Categories & Tags (Async)
                if (data.categories && Array.isArray(data.categories)) {
                    await wpcProcessTerms('comparison_category', data.categories, 'wpc-cat-list');
                }
                if (data.tags && Array.isArray(data.tags)) {
                    await wpcProcessTerms('comparison_feature', data.tags, 'wpc-feature-list');
                }

                // 10. Use Cases (Meta)
                if (data.use_cases && Array.isArray(data.use_cases)) {
                    // Clear existing (optional, but cleaner)
                    const list = document.getElementById('wpc-use-cases-list');
                    if (list) {
                        list.innerHTML = ''; 
                        data.use_cases.forEach(uc => {
                            if (typeof wpcAddUseCase === 'function') {
                                wpcAddUseCase();
                                const row = list.lastElementChild;
                                if (row) {
                                    const nameIn = row.querySelector('input[name*="[name]"]');
                                    const iconIn = row.querySelector('input[name*="[icon]"]');
                                    const descIn = row.querySelector('textarea[name*="[desc]"]');
                                    const imgIn  = row.querySelector('input[name*="[image]"]');
                                    
                                    if (nameIn) nameIn.value = uc.name || '';
                                    if (iconIn) iconIn.value = uc.icon || '';
                                    if (descIn) descIn.value = uc.desc || '';
                                    if (imgIn) imgIn.value = uc.image || '';
                                }
                            }
                        });
                    }
                }

                // 11. Recommended Tools (Checkboxes)
                if (data.recommended_tools && Array.isArray(data.recommended_tools)) {
                    const toolsContainer = document.getElementById('wpc-tab-tools_collections');
                    if (toolsContainer) {
                        const labels = toolsContainer.querySelectorAll('label');
                        data.recommended_tools.forEach(toolName => {
                             labels.forEach(lbl => {
                                 // Check if label text contains tool name (basic matching)
                                 if (lbl.innerText.trim().toLowerCase().includes(toolName.trim().toLowerCase())) {
                                     const cb = lbl.querySelector('input[type="checkbox"]');
                                     if (cb) {
                                         cb.checked = true;
                                         // Trigger change for visual feedback if any
                                         cb.dispatchEvent(new Event('change'));
                                     }
                                 }
                             });
                        });
                    }
                }

                wpcShowToast('Import Complete! Please save the post.');
            }

            // Sync Plan Table Headers in DOM
            function wpcSyncPlanHeaders(pricingPlans) {
                // Update Global JS Variable
                window.wpcPlanNames = pricingPlans.map(p => p.name || 'Plan');
                
                // Rebuild Table Header
                const theadRow = document.querySelector('#wpc-features-table thead tr');
                if (!theadRow) return;

                // Keep First Column (Feature Name)
                const firstTh = theadRow.firstElementChild;
                
                // Clear the rest
                theadRow.innerHTML = '';
                theadRow.appendChild(firstTh);

                // Add Plan Columns
                window.wpcPlanNames.forEach((name, idx) => {
                    const th = document.createElement('th');
                    th.style.padding = '10px';
                    th.style.textAlign = 'center';
                    th.style.borderBottom = '2px solid #e2e8f0';
                    th.style.minWidth = '100px';
                    th.innerHTML = `
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                            <span>${name.replace(/</g, "&lt;")}</span>
                            <label style="font-size: 10px; color: #6366f1; cursor: pointer; font-weight: normal;">
                                <input type="checkbox" class="wpc-select-all-plan" data-plan-idx="${idx}" onchange="wpcToggleAllForPlan(${idx}, this.checked)" style="margin-right: 3px;" />
                                Select All
                            </label>
                        </div>
                    `;
                    theadRow.appendChild(th);
                });

                // Add Remove Column
                const lastTh = document.createElement('th');
                lastTh.style.padding = '10px';
                lastTh.style.width = '60px';
                lastTh.style.borderBottom = '2px solid #e2e8f0';
                theadRow.appendChild(lastTh);
            }

            async function wpcProcessTerms(taxonomy, names, listId) {
                const list = document.getElementById(listId);
                if (!list) return;

                for (const name of names) {
                    // Check if exists
                    let found = false;
                    const labels = list.querySelectorAll('label');
                    for (const label of labels) {
                        if (label.innerText.trim().toLowerCase() === name.toLowerCase()) {
                            const input = label.querySelector('input');
                            if (input) {
                                input.checked = true;
                                if (taxonomy === 'comparison_category') wpcSyncPrimaryCats(input);
                            }
                            found = true;
                            break;
                        }
                    }

                    if (!found) {
                        // Create new via AJAX
                        try {
                            await wpcAddTermAsync(taxonomy, name);
                        } catch (e) {
                            console.error('Failed to add term:', name, e);
                        }
                    }
                }
            }

            function wpcAddTermAsync(taxonomy, name) {
                return new Promise((resolve, reject) => {
                    jQuery.post(ajaxurl, {
                        action: 'wpc_add_term',
                        taxonomy: taxonomy,
                        term_name: name,
                        _ajax_nonce: '<?php echo wp_create_nonce('wpc_add_term_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            var term = response.data;
                            var listId = taxonomy === 'comparison_category' ? 'wpc-cat-list' : 'wpc-feature-list';
                            var html = '';
                            if (taxonomy === 'comparison_category') {
                                 html = '<label style="display:block;"><input type="checkbox" name="wpc_category[]" value="' + term.term_id + '" checked onchange="wpcSyncPrimaryCats(this)" /> ' + term.name + '</label>';
                                 var primaryHtml = '<label style="display:block;" data-term-id="' + term.term_id + '" class="wpc-primary-option"><input type="checkbox" name="wpc_primary_cats[]" value="' + term.term_id + '" /> ' + term.name + '</label>';
                                 jQuery('#wpc-primary-cat-list').append(primaryHtml);
                            } else {
                                 html = '<label style="display:block;"><input type="checkbox" name="wpc_features[]" value="' + term.term_id + '" checked onchange="wpcSyncPrimaryFeatures(this)" /> ' + term.name + '</label>';
                                 var primaryTagHtml = '<label style="display:block;" data-term-id="' + term.term_id + '" class="wpc-primary-feature-option"><input type="checkbox" name="wpc_primary_features[]" value="' + term.term_id + '" /> ' + term.name + '</label>';
                                 jQuery('#wpc-primary-feature-list').append(primaryTagHtml);
                            }
                            jQuery('#' + listId).append(html);
                            resolve(term);
                        } else {
                            reject(response);
                        }
                    });
                });
            }

            // Sync Primary Features visibility
            function wpcSyncPrimaryFeatures(checkbox) {
                var termId = checkbox.value;
                var list = document.getElementById('wpc-primary-feature-list');
                var option = list.querySelector('.wpc-primary-feature-option[data-term-id="' + termId + '"]');
                
                if (checkbox.checked) {
                    if (option) option.style.display = 'block';
                } else {
                    if (option) {
                        option.style.display = 'none';
                        option.querySelector('input').checked = false; // Uncheck if removed from main list
                    }
                }
            }

            // Limit Primary Features to 3
            jQuery(document).on('change', 'input[name="wpc_primary_features[]"]', function() {
                var checked = jQuery('input[name="wpc_primary_features[]"]:checked');
                if (checked.length > 3) {
                    this.checked = false;
                    alert('You can only select up to 3 primary tags.');
                }
            });
            </script>
        </div>
    </div> <!-- End wpc-details-wrap wpc-tabs-wrapper -->

    <script>
    // Tab Switching Logic
    function wpcOpenItemTab(evt, tabName) {
        var i, tabcontent, tablinks;

        // Hide all tab content
        tabcontent = document.getElementsByClassName("wpc-tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
            tabcontent[i].classList.remove("active");
        }

        // Remove active class from all tab links
        tablinks = document.getElementsByClassName("wpc-tab-nav")[0].getElementsByTagName("li");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].classList.remove("active");
        }

        // Show current tab and add active class
        document.getElementById("wpc-tab-" + tabName).style.display = "block";
        document.getElementById("wpc-tab-" + tabName).classList.add("active");
        evt.currentTarget.classList.add("active");
    }

    // Search Filter
    function wpcFilterList(inputId, listId) {
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
    }

    // Sync Primary Categories
    function wpcSyncPrimaryCats(checkbox) {
        var termId = checkbox.value;
        var isChecked = checkbox.checked;
        
        // Find corresponding primary option
        var primaryList = document.getElementById('wpc-primary-cat-list');
        var primaryOption = primaryList.querySelector('[data-term-id="' + termId + '"]');
        
        if (primaryOption) {
            if (isChecked) {
                primaryOption.style.display = 'block';
            } else {
                primaryOption.style.display = 'none';
                // Uncheck if it was checked
                var primaryCheckbox = primaryOption.querySelector('input[type="checkbox"]');
                if (primaryCheckbox) primaryCheckbox.checked = false;
            }
        }
    }
    
    // Sync Variant Categories
    function wpcSyncVariantCats(checkbox) {
        var termId = checkbox.value;
        var isChecked = checkbox.checked;
        
        // Find corresponding variant option
        var variantList = document.getElementById('wpc-variant-categories-list');
        if (!variantList) return;
        
        var variantOption = variantList.querySelector('.wpc-variant-cat-option[data-term-id="' + termId + '"]');
        
        if (variantOption) {
            if (isChecked) {
                variantOption.style.display = 'flex';
            } else {
                variantOption.style.display = 'none';
                // Uncheck if it was checked
                var variantCheckbox = variantOption.querySelector('input[type="checkbox"]');
                if (variantCheckbox) variantCheckbox.checked = false;
            }
        }
        
        // Update no-categories message
        var visibleOptions = variantList.querySelectorAll('.wpc-variant-cat-option[style*="display: flex"], .wpc-variant-cat-option:not([style*="display: none"])');
        var hasVisible = false;
        variantList.querySelectorAll('.wpc-variant-cat-option').forEach(function(opt) {
            if (opt.style.display !== 'none') hasVisible = true;
        });
        
        var noMsg = variantList.querySelector('.wpc-no-cats-msg');
        if (!hasVisible) {
            if (!noMsg) {
                var p = document.createElement('p');
                p.className = 'wpc-no-cats-msg';
                p.style.cssText = 'color: #94a3b8; font-size: 13px; grid-column: 1/-1;';
                p.textContent = 'No categories assigned yet.';
                variantList.appendChild(p);
            }
        } else {
            if (noMsg) noMsg.remove();
        }
    }
    
    // Run sync on load
    document.addEventListener('DOMContentLoaded', function() {
        var catList = document.getElementById('wpc-cat-list');
        if (catList) {
            var inputs = catList.querySelectorAll('input[type="checkbox"]');
            inputs.forEach(function(input) {
                wpcSyncPrimaryCats(input);
                wpcSyncVariantCats(input);
            });
        }
    });

    // Toggle billing settings visibility
    function wpcAddCycle() {
        var container = document.getElementById('wpc-cycles-container');
        var index = container.children.length;
        var html = `
            <div class="wpc-cycle-row" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                <input type="text" name="wpc_cycles[${index}][slug]" placeholder="Slug (e.g. lifetime)" style="width: 120px; font-family: monospace;" required />
                <input type="text" name="wpc_cycles[${index}][label]" placeholder="Label (e.g. Lifetime)" style="flex: 1;" required />
                <label style="font-size: 12px; display: flex; align-items: center; gap: 5px;">
                    <input type="radio" name="wpc_default_cycle" value="" onclick="this.value = this.closest('.wpc-cycle-row').querySelector('input[name*=\'[slug]\']').value" />
                    Default
                </label>
                <button type="button" class="button wpc-remove-cycle" onclick="this.closest('.wpc-cycle-row').remove(); wpcRefreshPlanInputs();">×</button>
            </div>
        `;
        var temp = document.createElement('div');
        temp.innerHTML = html;
        container.appendChild(temp.firstElementChild);
        
        // Also add inputs to existing plans? 
        // For simplicity, wpcRefreshPlanInputs() will handle re-rendering price inputs for all plans based on current cycles.
        wpcRefreshPlanInputs();
    }

    function wpcRefreshPlanInputs() {
        // Gather current cycles
        var cycles = [];
        document.querySelectorAll('.wpc-cycle-row').forEach(row => {
            var slug = row.querySelector('input[name*="[slug]"]').value;
            var label = row.querySelector('input[name*="[label]"]').value;
            if(slug) cycles.push({slug, label});
        });
        
        // Update all price groups in plans
        document.querySelectorAll('.wpc-plan-row').forEach((row, planIndex) => {
            var pricesContainer = row.querySelector('.wpc-plan-prices');
            if(!pricesContainer) return;
            
            // Preserve existing values
            var currentValues = {};
            pricesContainer.querySelectorAll('.wpc-price-group').forEach(group => {
                var slug = group.getAttribute('data-slug');
                var amt = group.querySelector('input[name*="[amount]"]').value;
                var per = group.querySelector('input[name*="[period]"]').value;
                currentValues[slug] = {amount: amt, period: per};
            });
            
            pricesContainer.innerHTML = '';
            
            cycles.forEach(cycle => {
                var val = currentValues[cycle.slug] ? currentValues[cycle.slug].amount : '';
                var per = currentValues[cycle.slug] ? currentValues[cycle.slug].period : '';
                
                var html = `
                    <div class="wpc-price-group" data-slug="${cycle.slug}" style="display: flex; gap: 2px; flex: 1 0 160px; min-width: 160px;">
                        <input type="text" name="wpc_plans[${planIndex}][prices][${cycle.slug}][amount]" value="${val}" placeholder="${cycle.label} Price" style="flex: 2; min-width: 60px;" />
                        <input type="text" name="wpc_plans[${planIndex}][prices][${cycle.slug}][period]" value="${per}" placeholder="/suffix" style="width: 55px; flex: 0 0 55px;" />
                    </div>
                `;
                var t = document.createElement('div');
                t.innerHTML = html;
                pricesContainer.appendChild(t.firstElementChild);
            });
        });
    }

    function wpcAddPlan() {
        var container = document.getElementById('wpc-plans-container');
        var index = container.children.length; // Approximate, but good enough for new unique keys
        
        // Gather cycles
        var cycles = [];
        var cycleRows = document.querySelectorAll('.wpc-cycle-row');
        if(cycleRows.length === 0) {
            alert('Please define at least one Billing Cycle first!');
            return;
        }
        cycleRows.forEach(row => {
            var slug = row.querySelector('input[name*="[slug]"]').value;
            var label = row.querySelector('input[name*="[label]"]').value;
            if(slug) cycles.push({slug, label});
        });

        var priceInputsHtml = '';
        cycles.forEach(c => {
             priceInputsHtml += `
                <div class="wpc-price-group" data-slug="${c.slug}" style="display: flex; gap: 2px; flex: 1 0 160px; min-width: 160px;">
                    <input type="text" name="wpc_plans[${index}][prices][${c.slug}][amount]" placeholder="${c.label} Price" style="flex: 2; min-width: 60px;" />
                    <input type="text" name="wpc_plans[${index}][prices][${c.slug}][period]" placeholder="/suffix" style="width: 55px; flex: 0 0 55px;" />
                </div>
             `;
        });

        var html = `
            <div class="wpc-plan-row" style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd; margin-bottom: 10px; border-radius: 4px;">
                <div style="display: flex; gap: 10px; margin-bottom: 5px; flex-wrap: wrap;">
                    <input type="text" name="wpc_plans[${index}][name]" placeholder="Plan Name" style="flex: 2; min-width: 150px;" />
                    <div class="wpc-plan-prices" style="display: flex; gap: 10px; flex: 3; flex-wrap: wrap;">
                        ${priceInputsHtml}
                    </div>
                </div>
                <!-- Coupon Field -->
                <div style="margin-bottom: 5px;">
                     <input type="text" name="wpc_plans[${index}][coupon]" placeholder="Coupon Code (e.g. SAVE20)" style="width: 100%;" />
                </div>
                <div style="display: flex; gap: 10px; margin-bottom: 5px;">
                    <div style="flex: 1; display:flex; gap: 5px; align-items: center;">
                        <label style="font-size: 11px;">
                            <input type="checkbox" name="wpc_plans[${index}][show_banner]" value="1" />
                            Show Banner
                        </label>
                        <input type="text" name="wpc_plans[${index}][banner_text]" placeholder="Banner (e.g. 70% OFF)" style="flex: 1;" />
                        <input type="color" name="wpc_plans[${index}][banner_color]" value="#10b981" style="height: 30px; width: 40px; padding: 0; border: none; cursor: pointer;" title="Banner Color" />
                    </div>
                </div>
                <div style="margin-bottom: 5px;">
                    <textarea name="wpc_plans[${index}][features]" rows="3" style="width:100%;" placeholder="Features (one per line)"></textarea>
                </div>
                <div style="display: flex; gap: 10px; align-items: center; margin-top: 5px;">
                    <input type="url" name="wpc_plans[${index}][link]" placeholder="Link" style="flex: 1;" />
                    <div style="flex: 1.5; display: flex; align-items: center; gap: 10px;">
                        <label style="font-size: 11px; display:flex; align-items:center; gap:3px;">
                             <input type="checkbox" name="wpc_plans[${index}][show_popup]" value="1" checked />
                             Show in Pop-up
                        </label>
                        <label style="font-size: 11px; display:flex; align-items:center; gap:3px;">
                             <input type="checkbox" name="wpc_plans[${index}][show_table]" value="1" checked />
                             Table
                        </label>
                        <input type="text" name="wpc_plans[${index}][button_text]" placeholder="Button Text" style="flex: 1;" />
                    </div>
                    <button type="button" class="button wpc-remove-plan" onclick="wpcRemovePlan(this)">Remove</button>
                </div>
            </div>
        `;
        // Use insertAdjacentHTML properly
        var temp = document.createElement('div');
        temp.innerHTML = html;
        container.appendChild(temp.firstElementChild);
    }
    
    function wpcRemovePlan(btn) {
        btn.closest('.wpc-plan-row').remove();
    }
    
    function wpcAddTerm(taxonomy) {
        var inputId = taxonomy === 'comparison_category' ? 'new-wpc-category' : 'new-wpc-feature';
        var listId = taxonomy === 'comparison_category' ? 'wpc-cat-list' : 'wpc-feature-list';
        var input = document.getElementById(inputId);
        var name = input.value;
        
        if (!name) return;

        // Simple AJAX to add term
        jQuery.post(ajaxurl, {
            action: 'wpc_add_term',
            taxonomy: taxonomy,
            term_name: name,
            _ajax_nonce: '<?php echo wp_create_nonce('wpc_add_term_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                var term = response.data;
                var html = '';
                if (taxonomy === 'comparison_category') {
                     html = '<label style="display:block;"><input type="checkbox" name="wpc_category[]" value="' + term.term_id + '" checked onchange="wpcSyncPrimaryCats(this)" /> ' + term.name + '</label>';
                     // Also append to primary list (and show it because it's checked)
                     var primaryHtml = '<label style="display:block;" data-term-id="' + term.term_id + '" class="wpc-primary-option"><input type="checkbox" name="wpc_primary_cats[]" value="' + term.term_id + '" /> ' + term.name + '</label>';
                     jQuery('#wpc-primary-cat-list').append(primaryHtml);
                } else {
                     html = '<label style="display:block;"><input type="checkbox" name="wpc_features[]" value="' + term.term_id + '" checked /> ' + term.name + '</label>';
                }
                
                // If "No types/features found" exists, remove it
                var list = document.getElementById(listId);
                if (list.querySelector('p')) { list.innerHTML = ''; }
                
                jQuery('#' + listId).append(html);
                input.value = '';
            } else {
                wpcShowToast('Error adding term', true);
            }
        });
    }
    
    // ======================
    // AI GENERATION FUNCTIONS
    // ======================
    
    <?php if ( $ai_configured ) : ?>
    (function() {
        var aiNonce = document.getElementById('wpc_ai_item_nonce') ? document.getElementById('wpc_ai_item_nonce').value : '';
        
        // AI Generation Prompts
        // Batched Prompts for Single Item
        var aiPrompts = {
            // Batch 1: Core Data (General, Content, Pricing, Visuals, SEO)
            core: `Generate comprehensive comparison data for "{name}".
Return a JSON object with this EXACT structure (for importing into a comparison tool):
{
  "public_name": "{name}",
  "title": "{name}",
  "description": "2-3 sentence marketing description of {name} including key selling points.",
  "general": {
    "price": "$29",
    "period": "/mo",
    "rating": "4.5",
    "details_link": "/{name}-review",
    "direct_link": "https://{name}.com",
    "button_text": "Visit Website"
  },
  "content": {
    "pros": ["Pro 1", "Pro 2", "Pro 3", "Pro 4", "Pro 5"],
    "cons": ["Con 1", "Con 2", "Con 3"],
     "labels": {
      "pros": "Pros",
      "cons": "Cons",
      "price": "Starting at",
      "rating": "Rating",
      "visit_site": "Visit Site",
      "coupon": "Coupon"
    }
  },
  "visuals": {
    "logo": "",
    "dashboard_image": "",
    "badge_text": "Editor's Choice",
    "badge_color": "#10b981",
    "hero_subtitle": "The best solution for...",
    "analysis_label": "Our Analysis",
    "colors": {
      "primary": "",
      "accent": "",
      "border": "",
      "coupon_bg": "",
      "coupon_text": ""
    }
  },
  "billing_cycles": [
    {"slug": "monthly", "label": "Monthly"},
    {"slug": "yearly", "label": "Yearly"},
    {"slug": "lifetime", "label": "Lifetime"}
  ],
  "default_cycle": "monthly",
  "billing_display_style": "toggle",
  "pricing_plans": [
    {
      "name": "Basic", 
      "prices": {
        "monthly": { "amount": "$9", "period": "/mo" },
        "yearly": { "amount": "$90", "period": "/yr" },
        "lifetime": { "amount": "$299", "period": "once" }
      }, 
      "features": ["Feature 1", "Feature 2", "Feature 3"], 
      "button_text": "Get Started", 
      "show_table": true, 
      "show_popup": true,
      "link": "",
      "coupon": ""
    },
    {
      "name": "Pro", 
      "prices": {
        "monthly": { "amount": "$29", "period": "/mo" },
        "yearly": { "amount": "$290", "period": "/yr" }
      },
      "features": ["All Basic features", "Pro Feature 1", "Pro Feature 2"], 
      "button_text": "Choose Pro", 
      "is_popular": true, 
      "show_table": true, 
      "show_popup": true, 
      "banner_text": "MOST POPULAR", 
      "banner_color": "#10b981",
      "link": "",
      "coupon": "SAVE20"
    }
  ],
  "custom_fields": [
    { "name": "Founded", "value": "2015"},
    { "name": "Headquarters", "value": "San Francisco, USA"}
  ],
  "seo": {
      "brand": "{name}",
      "sku": "",
      "gtin": "",
      "condition": "NewCondition",
      "availability": "InStock",
      "mfg_date": "",
      "exp_date": "",
      "service_type": "",
      "area_served": "",
      "duration": ""
  },
  "plan_features": [
      { "name": "Feature Name", "included_in": [0, 1] }
  ],
  "recommended_tools": ["Tool Name A", "Tool Name B"]
}
Be accurate and specific to the actual product/service. Use proper JSON quoting.`,

            // Batch 2: Use Cases (Dedicated to ensure quality)
            use_cases: `Generate 4 detailed Best Use Cases for "{name}".
Return JSON object:
{
  "use_cases": [
      { "name": "Startups", "desc": "Perfect for early stage...", "icon": "fa-solid fa-rocket", "image": "" },
      { "name": "Enterprise", "desc": "Scalable solution for...", "icon": "fa-solid fa-building", "image": "" },
      { "name": "Agencies", "desc": "Manage multiple clients...", "icon": "fa-solid fa-briefcase", "image": "" },
      { "name": "Developers", "desc": "API-first approach...", "icon": "fa-solid fa-code", "image": "" }
  ]
}
IMPORTANT: Must generate exactly 4 use cases with FontAwesome 6 icons.`,

            // Batch 3: Taxonomies (Categories & Tags)
            taxonomies: `Suggest relevant Categories and Tags for "{name}".
Return JSON object:
{
  "categories": ["Category 1", "Category 2"],
  "tags": ["Tag 1", "Tag 2", "Tag 3", "Tag 4"]
}
IMPORTANT: Categories should be high-level industry segments. Tags should be specific features or attributes.`
        };
        
        // Generate All button click (Batched Execution)
        var generateAllBtn = document.getElementById('wpc-ai-generate-all');
        if (generateAllBtn) {
            generateAllBtn.addEventListener('click', function() {
                var productName = document.getElementById('wpc-ai-product-name').value.trim();
                var profileSelect = document.getElementById('wpc-ai-item-profile');
                var profileId = profileSelect ? profileSelect.value : '';
                var customContext = document.getElementById('wpc-ai-custom-context');
                var contextText = customContext ? customContext.value.trim() : '';
                
                // Check Gen Tags checkbox
                var genTaxCheckbox = document.getElementById('wpc-ai-gen-taxonomies');
                var shouldGenTax = genTaxCheckbox ? genTaxCheckbox.checked : true;
                
                if (!productName) {
                    wpcShowAIToast('Please enter a product/service name', true);
                    return;
                }
                
                generateAllBtn.innerHTML = '<span class="wpc-spinner"></span> Generating Core Data...';
                generateAllBtn.disabled = true;
                
                // --- Step 1: Core Data ---
                var promptCore = aiPrompts.core.replace('{name}', productName);
                if (contextText) promptCore += "\nContext: " + contextText;
                
                jQuery.post(ajaxurl, {
                    action: 'wpc_ai_generate', 
                    item_profile_id: profileId,
                    prompt: promptCore,
                    nonce: aiNonce
                }, function(response) {
                    if (response.success && response.data) {
                        try {
                            var dataCore = response.data;
                            if (typeof dataCore === 'string') {
                                dataCore = JSON.parse(dataCore);
                            }
                            
                            // Adapter for Description
                            if (!dataCore.general && (dataCore.price || dataCore.description)) {
                                if (dataCore.description) {
                                    var desc = document.getElementById('wpc_short_description');
                                    if(desc) desc.value = dataCore.description;
                                }
                            }
                            
                            if (typeof wpcExecuteImport === 'function') {
                                wpcExecuteImport(dataCore);
                            } else {
                                wpcPopulateFromAI(dataCore);
                            }
                            
                            // --- Step 2: Use Cases ---
                            generateAllBtn.innerHTML = '<span class="wpc-spinner"></span> Generating Use Cases...';
                            var promptUC = aiPrompts.use_cases.replace('{name}', productName);
                            
                            jQuery.post(ajaxurl, {
                                action: 'wpc_ai_generate',
                                item_profile_id: profileId,
                                prompt: promptUC,
                                nonce: aiNonce
                            }, function(respUC) {
                                if (respUC.success && respUC.data) {
                                    try {
                                        var dataUC = respUC.data;
                                        if (typeof dataUC === 'string') {
                                            dataUC = JSON.parse(dataUC);
                                        }
                                        if (typeof wpcExecuteImport === 'function') wpcExecuteImport(dataUC);
                                    } catch(e) { console.error('UC Parse Error', e); }
                                }
                                
                                // --- Step 3: Taxonomies (Conditional) ---
                                if (shouldGenTax) {
                                    generateAllBtn.innerHTML = '<span class="wpc-spinner"></span> Generating Categories...';
                                    var promptTax = aiPrompts.taxonomies.replace('{name}', productName);
                                    
                                    jQuery.post(ajaxurl, {
                                        action: 'wpc_ai_generate',
                                        item_profile_id: profileId,
                                        prompt: promptTax,
                                        nonce: aiNonce
                                    }, function(respTax) {
                                        if (respTax.success && respTax.data) {
                                            try {
                                                var dataTax = respTax.data;
                                                if (typeof dataTax === 'string') {
                                                    dataTax = JSON.parse(dataTax);
                                                }
                                                if (typeof wpcExecuteImport === 'function') wpcExecuteImport(dataTax);
                                            } catch(e) { console.error('Tax Parse Error', e); }
                                        }
                                        finishBatch();
                                    }).fail(function() { finishBatch(); });
                                    
                                } else {
                                    finishBatch();
                                }
                                
                            }).fail(function() { 
                                // If Use Cases fail, still try to finish nicely
                                if (shouldGenTax) finishBatch(); // Simplify error handling, just stop
                                else finishBatch();
                            });
                            
                        } catch (e) {
                            console.error('Core Parse Error', e);
                            wpcShowAIToast('Error parsing AI response', true);
                            resetBtn();
                        }
                    } else {
                        wpcShowAIToast('AI Error: ' + (response.data || 'Unknown'), true);
                        resetBtn();
                    }
                }).fail(function() {
                    wpcShowAIToast('Failed to connect to AI', true);
                    resetBtn();
                });
                
                function finishBatch() {
                    generateAllBtn.innerHTML = '&#x2714; All Done!';
                    wpcShowAIToast('Content generated & populated! Please Update post.');
                    setTimeout(function() {
                        resetBtn();
                    }, 2000);
                }
                
                function resetBtn() {
                    generateAllBtn.innerHTML = '&#x2728; Generate All';
                    generateAllBtn.disabled = false;
                }
            });
        }
        
        // Populate form fields from AI response
        function wpcPopulateFromAI(data) {
            // Description
            if (data.description) {
                var descField = document.getElementById('wpc_short_description');
                if (descField) descField.value = data.description;
            }
            
            // Rating
            if (data.rating) {
                var ratingField = document.getElementById('wpc_rating');
                if (ratingField) ratingField.value = data.rating;
            }
            
            // Price
            if (data.price) {
                var priceField = document.getElementById('wpc_price');
                if (priceField) priceField.value = data.price;
            }
            
            // Period
            if (data.period) {
                var periodField = document.getElementById('wpc_price_period');
                if (periodField) periodField.value = data.period;
            }
            
            // Pros
            if (data.pros && Array.isArray(data.pros)) {
                var prosField = document.getElementById('wpc_pros');
                if (prosField) prosField.value = data.pros.join('\n');
            }
            
            // Cons
            if (data.cons && Array.isArray(data.cons)) {
                var consField = document.getElementById('wpc_cons');
                if (consField) consField.value = data.cons.join('\n');
            }
            
            // Pricing Plans
            if (data.pricing_plans && Array.isArray(data.pricing_plans)) {
                var container = document.getElementById('wpc-plans-container');
                if (container) {
                    // Clear existing plans
                    container.innerHTML = '';
                    
                    data.pricing_plans.forEach(function(plan, idx) {
                        wpcAddPlan();
                        var row = container.children[idx];
                        if (row) {
                            var nameInput = row.querySelector('input[name*="[name]"]');
                            var priceInput = row.querySelector('input[name*="[price]"]');
                            var periodInput = row.querySelector('input[name*="[period]"]');
                            var featuresInput = row.querySelector('textarea[name*="[features]"]');
                            var btnTextInput = row.querySelector('input[name*="[button_text]"]');
                            
                            if (nameInput) nameInput.value = plan.name || '';
                            if (priceInput) priceInput.value = plan.price || '';
                            if (periodInput) periodInput.value = plan.period || '/mo';
                            if (featuresInput) featuresInput.value = plan.features || '';
                            if (btnTextInput) btnTextInput.value = plan.button_text || 'Get Started';
                            
                            // Mark popular plan
                            if (plan.is_popular) {
                                var bannerCheck = row.querySelector('input[name*="[show_banner]"]');
                                var bannerText = row.querySelector('input[name*="[banner_text]"]');
                                if (bannerCheck) bannerCheck.checked = true;
                                if (bannerText) bannerText.value = 'MOST POPULAR';
                            }
                        }
                    });
                }
            }
            
            // Switch to relevant tab for review
            wpcOpenItemTab(null, 'general');
        }
        
        // Toast notification
        function wpcShowAIToast(msg, isError) {
            var bg = isError ? '#dc2626' : '#10b981';
            var icon = isError ? '&#x26A0;' : '&#x2728;';
            var toast = document.createElement('div');
            toast.style.cssText = 'position:fixed;bottom:30px;right:30px;background:' + bg + ';color:white;padding:12px 20px;border-radius:8px;font-weight:500;z-index:100000;animation:wpc-ai-toast-in 0.3s ease;max-width:400px;';
            toast.innerHTML = icon + ' ' + msg;
            document.body.appendChild(toast);
            
            setTimeout(function() {
                toast.remove();
            }, isError ? 5000 : 3000);
        }
    })();
    <?php endif; ?>
        
        // Reset Design Overrides to Global Settings
        function wpcResetDesignOverrides(btn) {
            wpcAdmin.confirm(
                'Reset Design Overrides',
                'Reset all design overrides to global settings?',
                function() {
                    wpcAdmin.loading(btn, 'Resetting...');
                    
                    setTimeout(function() {
                        // Reset Checkbox
                        var toggle = document.querySelector('input[name="wpc_enable_design_overrides"]');
                        if (toggle) toggle.checked = false;
                        
                        // Reset Inputs to Global Defaults (Visual only)
                        const mapping = {
                            'wpc_primary_color': 'primary',
                            'wpc_accent_color': 'accent',
                            'wpc_border_color': 'border',
                            'wpc_color_coupon_bg': 'coupon_bg',
                            'wpc_color_coupon_text': 'coupon_text'
                        };

                        for (var name in mapping) {
                            var input = document.querySelector('input[name="' + name + '"]');
                            if (input && wpcGlobalDefaults[mapping[name]]) {
                                input.value = wpcGlobalDefaults[mapping[name]];
                            }
                        }
                        
                        wpcAdmin.reset(btn);
                        wpcAdmin.toast('Design overrides disabled & reset to global.', 'success');
                    }, 600);
                },
                'Reset',
                '#d32f2f'
            );
        }
        
        // Toggle Pros/Cons
        function wpcToggleProsCons(checkbox) {
            var wrap = document.getElementById('wpc-pros-cons-inputs');
            if (!wrap) return;
            if (checkbox.checked) {
                wrap.style.opacity = '1';
                wrap.style.pointerEvents = 'auto';
            } else {
                wrap.style.opacity = '0.5';
                wrap.style.pointerEvents = 'none';
            }
        }

        // Reset Pros/Cons Colors to Global Settings
        function wpcResetProsConsColors(btn) {
            wpcAdmin.confirm(
                'Reset Pros & Cons Colors',
                'Disable custom colors and reset to global defaults?',
                function() {
                    wpcAdmin.loading(btn, 'Resetting...');
                    
                    setTimeout(function() {
                         // Reset Checkbox
                        var toggle = document.getElementById('wpc_enable_pros_cons_colors');
                        if (toggle) {
                            toggle.checked = false;
                            wpcToggleProsCons(toggle);
                        }
                        
                        // Reset Inputs to Global Defaults
                        const mapping = {
                            'wpc_color_pros_bg': 'pros_bg',
                            'wpc_color_pros_text': 'pros_text',
                            'wpc_color_cons_bg': 'cons_bg',
                            'wpc_color_cons_text': 'cons_text'
                        };

                        for (var name in mapping) {
                            var input = document.querySelector('input[name="' + name + '"]');
                            if (input && wpcGlobalDefaults[mapping[name]]) {
                                input.value = wpcGlobalDefaults[mapping[name]];
                            }
                        }
                        
                        wpcAdmin.reset(btn);
                        wpcAdmin.toast('Pros & cons colors disabled & reset.', 'success');
                    }, 600);
                },
                'Reset',
                '#d32f2f'
            );
        }

        // Reset Global Pros/Cons Colors (Settings Page)
        function wpcResetGlobalProsConsColors(btn, type) {
            wpcAdmin.confirm(
                'Reset Global Colors',
                'Reset global ' + (type === 'pros' ? 'Pros' : 'Cons') + ' colors to factory defaults?',
                function() {
                    wpcAdmin.loading(btn, 'Resetting...');
                    
                    setTimeout(function() {
                        if (type === 'pros') {
                            var bg = document.querySelector('input[name="wpc_color_pros_bg"]');
                            var txt = document.querySelector('input[name="wpc_color_pros_text"]');
                            // Also checking description (p tag) to update preview text if needed?
                            // Default behavior of color picker might update it on change, lets fire change event
                            
                            if (bg) { bg.value = '#f0fdf4'; bg.dispatchEvent(new Event('input')); bg.dispatchEvent(new Event('change')); }
                            if (txt) { txt.value = '#166534'; txt.dispatchEvent(new Event('input')); txt.dispatchEvent(new Event('change')); }
                        } else {
                            var bg = document.querySelector('input[name="wpc_color_cons_bg"]');
                            var txt = document.querySelector('input[name="wpc_color_cons_text"]');
                            
                            if (bg) { bg.value = '#fef2f2'; bg.dispatchEvent(new Event('input')); bg.dispatchEvent(new Event('change')); }
                            if (txt) { txt.value = '#991b1b'; txt.dispatchEvent(new Event('input')); txt.dispatchEvent(new Event('change')); }
                        }
                        
                        wpcAdmin.reset(btn);
                        wpcAdmin.toast((type === 'pros' ? 'Pros' : 'Cons') + ' colors reset to defaults.', 'success');
                    }, 600);
                },
                'Reset',
                '#d32f2f'
            );
        }
    </script>
    </div> <!-- .wpc-admin-container -->
    <?php
}

/**
 * Handle AJAX Add Term
 */
add_action('wp_ajax_wpc_add_term', 'wpc_ajax_add_term');
function wpc_ajax_add_term() {
    check_ajax_referer('wpc_add_term_nonce');
    if (!current_user_can('manage_categories')) wp_send_json_error();

    $taxonomy = sanitize_text_field($_POST['taxonomy']);
    $name = sanitize_text_field($_POST['term_name']);

    $term = wp_insert_term($name, $taxonomy);
    
    if (is_wp_error($term)) {
        wp_send_json_error($term->get_error_message());
    }
    
    $term_obj = get_term($term['term_id'], $taxonomy);
    wp_send_json_success($term_obj);
}

/**
 * Save Meta Box Data
 */
function wpc_save_meta_box( $post_id ) {
    if ( ! isset( $_POST['wpc_nonce'] ) || ! wp_verify_nonce( $_POST['wpc_nonce'], 'wpc_save_details' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Permissions Check
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Save Public Name
    if ( isset( $_POST['wpc_public_name'] ) ) {
        update_post_meta( $post_id, '_wpc_public_name', sanitize_text_field( $_POST['wpc_public_name'] ) );
    }

    // Save Short Description (Details)
    if ( isset( $_POST['wpc_short_description'] ) ) {
        $desc = sanitize_textarea_field( $_POST['wpc_short_description'] );
        update_post_meta( $post_id, '_wpc_short_description', $desc );
        
        // Sync to actual post_excerpt (Commented out for debugging save issue)
        /*
        remove_action( 'save_post', 'wpc_save_meta_box' );
        wp_update_post( array(
            'ID' => $post_id,
            'post_excerpt' => $desc
        ) );
        add_action( 'save_post', 'wpc_save_meta_box' );
        */
    }

    // Save Schema & Product Data
    if ( isset( $_POST['wpc_product_category'] ) ) {
        update_post_meta( $post_id, '_wpc_product_category', sanitize_text_field( $_POST['wpc_product_category'] ) );
    }
    if ( isset( $_POST['wpc_brand'] ) ) {
        update_post_meta( $post_id, '_wpc_brand', sanitize_text_field( $_POST['wpc_brand'] ) );
    }
    if ( isset( $_POST['wpc_sku'] ) ) {
        update_post_meta( $post_id, '_wpc_sku', sanitize_text_field( $_POST['wpc_sku'] ) );
    }
    if ( isset( $_POST['wpc_gtin'] ) ) {
        update_post_meta( $post_id, '_wpc_gtin', sanitize_text_field( $_POST['wpc_gtin'] ) );
    }
    if ( isset( $_POST['wpc_condition'] ) ) {
        update_post_meta( $post_id, '_wpc_condition', sanitize_text_field( $_POST['wpc_condition'] ) );
    }
    if ( isset( $_POST['wpc_availability'] ) ) {
        update_post_meta( $post_id, '_wpc_availability', sanitize_text_field( $_POST['wpc_availability'] ) );
    }
    if ( isset( $_POST['wpc_mfg_date'] ) ) {
        update_post_meta( $post_id, '_wpc_mfg_date', sanitize_text_field( $_POST['wpc_mfg_date'] ) );
    }
    if ( isset( $_POST['wpc_exp_date'] ) ) {
        update_post_meta( $post_id, '_wpc_exp_date', sanitize_text_field( $_POST['wpc_exp_date'] ) );
    }
    if ( isset( $_POST['wpc_service_type'] ) ) {
        update_post_meta( $post_id, '_wpc_service_type', sanitize_text_field( $_POST['wpc_service_type'] ) );
    }
    if ( isset( $_POST['wpc_area_served'] ) ) {
        update_post_meta( $post_id, '_wpc_area_served', sanitize_text_field( $_POST['wpc_area_served'] ) );
    }
    if ( isset( $_POST['wpc_duration'] ) ) {
        update_post_meta( $post_id, '_wpc_duration', sanitize_text_field( $_POST['wpc_duration'] ) );
    }

    // Save Meta
    if ( isset( $_POST['wpc_price'] ) ) {
        update_post_meta( $post_id, '_wpc_price', sanitize_text_field( $_POST['wpc_price'] ) );
    }
    if ( isset( $_POST['wpc_period'] ) ) {
        update_post_meta( $post_id, '_wpc_period', sanitize_text_field( $_POST['wpc_period'] ) );
    }
    if ( isset( $_POST['wpc_rating'] ) ) {
        update_post_meta( $post_id, '_wpc_rating', sanitize_text_field( $_POST['wpc_rating'] ) );
    }
    if ( isset( $_POST['wpc_external_logo_url'] ) ) {
        update_post_meta( $post_id, '_wpc_external_logo_url', esc_url_raw( $_POST['wpc_external_logo_url'] ) );
    }
    if ( isset( $_POST['wpc_details_link'] ) ) {
        update_post_meta( $post_id, '_wpc_details_link', esc_url_raw( $_POST['wpc_details_link'] ) );
    }
    if ( isset( $_POST['wpc_direct_link'] ) ) {
        update_post_meta( $post_id, '_wpc_direct_link', esc_url_raw( $_POST['wpc_direct_link'] ) );
    }
    if ( isset( $_POST['wpc_button_text'] ) ) {
        update_post_meta( $post_id, '_wpc_button_text', sanitize_text_field( $_POST['wpc_button_text'] ) );
    }
    if ( isset( $_POST['wpc_hero_button_text'] ) ) {
        update_post_meta( $post_id, '_wpc_hero_button_text', sanitize_text_field( $_POST['wpc_hero_button_text'] ) );
    }
    if ( isset( $_POST['wpc_pros'] ) ) {
        update_post_meta( $post_id, '_wpc_pros', sanitize_textarea_field( $_POST['wpc_pros'] ) );
    }
    if ( isset( $_POST['wpc_cons'] ) ) {
        update_post_meta( $post_id, '_wpc_cons', sanitize_textarea_field( $_POST['wpc_cons'] ) );
    }
    
    // Save Pros/Cons label overrides
    if ( isset( $_POST['wpc_txt_pros_label'] ) ) {
        update_post_meta( $post_id, '_wpc_txt_pros_label', sanitize_text_field( $_POST['wpc_txt_pros_label'] ) );
    }
    if ( isset( $_POST['wpc_txt_cons_label'] ) ) {
        update_post_meta( $post_id, '_wpc_txt_cons_label', sanitize_text_field( $_POST['wpc_txt_cons_label'] ) );
    }
    
    // Save additional text label overrides
    if ( isset( $_POST['wpc_txt_price_label'] ) ) {
        update_post_meta( $post_id, '_wpc_txt_price_label', sanitize_text_field( $_POST['wpc_txt_price_label'] ) );
    }
    if ( isset( $_POST['wpc_txt_rating_label'] ) ) {
        update_post_meta( $post_id, '_wpc_txt_rating_label', sanitize_text_field( $_POST['wpc_txt_rating_label'] ) );
    }
    if ( isset( $_POST['wpc_txt_mo_suffix'] ) ) {
        update_post_meta( $post_id, '_wpc_txt_mo_suffix', sanitize_text_field( $_POST['wpc_txt_mo_suffix'] ) );
    }
    if ( isset( $_POST['wpc_txt_visit_site'] ) ) {
        update_post_meta( $post_id, '_wpc_txt_visit_site', sanitize_text_field( $_POST['wpc_txt_visit_site'] ) );
    }
    if ( isset( $_POST['wpc_txt_coupon_label'] ) ) {
        update_post_meta( $post_id, '_wpc_txt_coupon_label', sanitize_text_field( $_POST['wpc_txt_coupon_label'] ) );
    }
    if ( isset( $_POST['wpc_txt_copied_label'] ) ) {
        update_post_meta( $post_id, '_wpc_txt_copied_label', sanitize_text_field( $_POST['wpc_txt_copied_label'] ) );
    }
    if ( isset( $_POST['wpc_txt_feature_header'] ) ) {
        update_post_meta( $post_id, '_wpc_txt_feature_header', sanitize_text_field( $_POST['wpc_txt_feature_header'] ) );
    }
    if ( isset( $_POST['wpc_coupon_code'] ) ) {
        update_post_meta( $post_id, '_wpc_coupon_code', sanitize_text_field( $_POST['wpc_coupon_code'] ) );
    }
    if ( isset( $_POST['wpc_featured_badge_text'] ) ) {
        update_post_meta( $post_id, '_wpc_featured_badge_text', sanitize_text_field( $_POST['wpc_featured_badge_text'] ) );
    }
    if ( isset( $_POST['wpc_featured_color'] ) ) {
        update_post_meta( $post_id, '_wpc_featured_color', sanitize_hex_color( $_POST['wpc_featured_color'] ) );
    }
    if ( isset( $_POST['wpc_dashboard_image'] ) ) {
        update_post_meta( $post_id, '_wpc_dashboard_image', esc_url_raw( $_POST['wpc_dashboard_image'] ) );
    }
    if ( isset( $_POST['wpc_hero_subtitle'] ) ) {
        update_post_meta( $post_id, '_wpc_hero_subtitle', sanitize_text_field( $_POST['wpc_hero_subtitle'] ) );
    }

    // Save Hero Logo Visibility
    if ( isset( $_POST['wpc_show_hero_logo'] ) ) {
        update_post_meta( $post_id, '_wpc_show_hero_logo', '1' );
    } else {
        update_post_meta( $post_id, '_wpc_show_hero_logo', '0' );
    }
    if ( isset( $_POST['wpc_analysis_label'] ) ) {
        update_post_meta( $post_id, '_wpc_analysis_label', sanitize_text_field( $_POST['wpc_analysis_label'] ) );
    }
    if ( isset( $_POST['wpc_show_coupon'] ) ) {
        update_post_meta( $post_id, '_wpc_show_coupon', '1' );
    } else {
        delete_post_meta( $post_id, '_wpc_show_coupon' );
    }

    // Save Footer Visibility
    if ( isset( $_POST['wpc_show_footer_table'] ) ) {
        update_post_meta( $post_id, '_wpc_show_footer_table', '1' );
    } else {
        update_post_meta( $post_id, '_wpc_show_footer_table', '0' ); // Save 0 to explicitly hide
    }

    if ( isset( $_POST['wpc_show_footer_popup'] ) ) {
        update_post_meta( $post_id, '_wpc_show_footer_popup', '1' );
    } else {
        update_post_meta( $post_id, '_wpc_show_footer_popup', '0' ); // Save 0 to explicitly hide
    }

    if ( isset( $_POST['wpc_footer_button_text'] ) ) {
        update_post_meta( $post_id, '_wpc_footer_button_text', sanitize_text_field( $_POST['wpc_footer_button_text'] ) );
    }

    // Save Primary Features (Tags)
    if ( isset( $_POST['wpc_primary_features'] ) ) {
        $primary = array_map( 'sanitize_text_field', $_POST['wpc_primary_features'] );
        update_post_meta( $post_id, '_wpc_primary_features', $primary );
    } else {
        delete_post_meta( $post_id, '_wpc_primary_features' );
    }

    // Save Compare Alternatives (Competitors)
    if ( isset( $_POST['wpc_competitors'] ) ) {
        $competitors = array_map( 'intval', $_POST['wpc_competitors'] );
        update_post_meta( $post_id, '_wpc_competitors', $competitors );
    } else {
        update_post_meta( $post_id, '_wpc_competitors', array() );
    }

    // Billing Display Style
    if ( isset( $_POST['wpc_billing_display_style'] ) ) {
        update_post_meta( $post_id, '_wpc_billing_display_style', sanitize_text_field( $_POST['wpc_billing_display_style'] ) );
    }

    // Billing Cycles
    if ( isset($_POST['wpc_cycles']) && is_array($_POST['wpc_cycles']) ) {
        $cycles = array_map(function($c) {
            return array(
                'slug' => sanitize_key($c['slug']),
                'label' => sanitize_text_field($c['label'])
            );
        }, $_POST['wpc_cycles']);
        update_post_meta( $post_id, '_wpc_billing_cycles', $cycles );
    } else {
        update_post_meta( $post_id, '_wpc_billing_cycles', array() );
    }
    
    // Default Cycle
    if ( isset($_POST['wpc_default_cycle']) ) {
        update_post_meta( $post_id, '_wpc_default_cycle', sanitize_key($_POST['wpc_default_cycle']) );
    }

    // Save Billing Mode Settings
    if ( isset( $_POST['wpc_billing_mode'] ) ) {
        update_post_meta( $post_id, '_wpc_billing_mode', sanitize_text_field( $_POST['wpc_billing_mode'] ) );
    }
    if ( isset( $_POST['wpc_monthly_label'] ) ) {
        update_post_meta( $post_id, '_wpc_monthly_label', sanitize_text_field( $_POST['wpc_monthly_label'] ) );
    }
    if ( isset( $_POST['wpc_yearly_label'] ) ) {
        update_post_meta( $post_id, '_wpc_yearly_label', sanitize_text_field( $_POST['wpc_yearly_label'] ) );
    }
    if ( isset( $_POST['wpc_default_billing'] ) ) {
        update_post_meta( $post_id, '_wpc_default_billing', sanitize_text_field( $_POST['wpc_default_billing'] ) );
    }

    // Pricing Plans with Dynamic Prices
    if ( isset( $_POST['wpc_plans'] ) && is_array( $_POST['wpc_plans'] ) ) {
        $plans = array();
        foreach ( $_POST['wpc_plans'] as $p ) {
            if ( ! empty( $p['name'] ) ) { // Save only if has name
                $plan_data = array(
                    'name'         => sanitize_text_field( $p['name'] ),
                    'prices'       => array(), // New dynamic structure
                    'features'     => sanitize_textarea_field( $p['features'] ),
                    'link'         => esc_url_raw( $p['link'] ),
                    'show_button'  => isset( $p['show_button'] ) ? '1' : '0',
                    'show_popup'   => isset( $p['show_popup'] ) ? '1' : '0',
                    'show_table'   => isset( $p['show_table'] ) ? '1' : '0',
                    'button_text'  => isset( $p['button_text'] ) ? sanitize_text_field( $p['button_text'] ) : '',
                    'coupon'       => isset( $p['coupon'] ) ? sanitize_text_field( $p['coupon'] ) : '', // Allow codes
                    'show_banner'  => isset( $p['show_banner'] ) ? '1' : '0',
                    'banner_text'  => isset( $p['banner_text'] ) ? sanitize_text_field( $p['banner_text'] ) : '',
                    'banner_color' => isset( $p['banner_color'] ) ? sanitize_hex_color( $p['banner_color'] ) : '',
                );

                // Process dynamic prices
                if ( isset($p['prices']) && is_array($p['prices']) ) {
                    foreach($p['prices'] as $slug => $price_info) {
                        $plan_data['prices'][sanitize_key($slug)] = array(
                            'amount' => sanitize_text_field($price_info['amount']),
                            'period' => sanitize_text_field($price_info['period'])
                        );
                    }
                }

                // Legacy Fallback for older code reading flat fields (Optional, but good for safety)
                // Pick first cycle as 'price' and second as 'yearly_price' just in case?
                // Or leave blank. Let's just store the robust 'prices' array.

                $plans[] = $plan_data;
            }
        }
        update_post_meta( $post_id, '_wpc_pricing_plans', $plans );
    } else {
        update_post_meta( $post_id, '_wpc_pricing_plans', array() );
    }

    if ( function_exists( 'wpc_save_variants_meta' ) ) {
        wpc_save_variants_meta( $post_id );
    }

    // Save Best Use Cases
    if ( isset( $_POST['wpc_use_cases'] ) && is_array( $_POST['wpc_use_cases'] ) ) {
        $use_cases = array();
        foreach ( $_POST['wpc_use_cases'] as $uc ) {
            if ( ! empty( $uc['name'] ) ) {
                $use_cases[] = array(
                    'name'  => sanitize_text_field( $uc['name'] ),
                    'desc'  => sanitize_textarea_field( $uc['desc'] ),
                    'icon'  => sanitize_text_field( $uc['icon'] ),
                    'image' => esc_url_raw( $uc['image'] ),
                    'icon_color' => sanitize_hex_color( $uc['icon_color'] ?? '' ),
                );
            }
        }
        update_post_meta( $post_id, '_wpc_use_cases', $use_cases );
    } else {
        delete_post_meta( $post_id, '_wpc_use_cases' );
    }

    // Save Selected Tools (Recommended Tools Module)
    if ( get_option( 'wpc_enable_tools_module', false ) ) {
        if ( isset( $_POST['wpc_selected_tools'] ) && is_array( $_POST['wpc_selected_tools'] ) ) {
            $selected_tools = array_map( 'intval', $_POST['wpc_selected_tools'] );
            update_post_meta( $post_id, '_wpc_selected_tools', $selected_tools );
        } else {
            delete_post_meta( $post_id, '_wpc_selected_tools' );
        }
    }

    // Save Visibility Flags
    if ( isset( $_POST['wpc_hide_plan_features'] ) ) {
        update_post_meta( $post_id, '_wpc_hide_plan_features', '1' );
    } else {
        delete_post_meta( $post_id, '_wpc_hide_plan_features' );
    }

    if ( isset( $_POST['wpc_show_plan_links'] ) ) {
        update_post_meta( $post_id, '_wpc_show_plan_links', '1' );
    } else {
        delete_post_meta( $post_id, '_wpc_show_plan_links' );
    }

    // Show Plan Links in Popup
    if ( isset( $_POST['wpc_show_plan_links_popup'] ) ) {
        update_post_meta( $post_id, '_wpc_show_plan_links_popup', '1' );
    } else {
        delete_post_meta( $post_id, '_wpc_show_plan_links_popup' );
    }

    // Button Positions
    if ( isset( $_POST['wpc_table_btn_pos'] ) ) {
        update_post_meta( $post_id, '_wpc_table_btn_pos', sanitize_text_field( $_POST['wpc_table_btn_pos'] ) );
    }
    if ( isset( $_POST['wpc_popup_btn_pos'] ) ) {
        update_post_meta( $post_id, '_wpc_popup_btn_pos', sanitize_text_field( $_POST['wpc_popup_btn_pos'] ) );
    }

    // Save Terms (Category - Multiple)
    // FIXED: wpc_category input does not exist (handled by WP sidebar).
    // Removing to prevent accidental wiping.
    if ( isset( $_POST['wpc_category'] ) ) {
        $cat_ids = array_map( 'intval', $_POST['wpc_category'] );
        wp_set_post_terms( $post_id, $cat_ids, 'comparison_category' );
    } else {
        wp_set_post_terms( $post_id, array(), 'comparison_category' );
    }

    // Save Primary Categories
    if ( isset( $_POST['wpc_primary_cats'] ) ) {
        $primary_ids = array_map( 'intval', $_POST['wpc_primary_cats'] );
        update_post_meta( $post_id, '_wpc_primary_cats', $primary_ids );
    } else {
        delete_post_meta( $post_id, '_wpc_primary_cats' );
    }

    // Save Primary Features (Tags)
    if ( isset( $_POST['wpc_primary_features'] ) ) {
        $primary_ids = array_map( 'intval', $_POST['wpc_primary_features'] );
        update_post_meta( $post_id, '_wpc_primary_features', $primary_ids );
    } else {
        delete_post_meta( $post_id, '_wpc_primary_features' );
    }

    // Variant Categories
    if ( isset( $_POST['wpc_variant_categories'] ) && is_array( $_POST['wpc_variant_categories'] ) ) {
        $variant_cats = array_map( 'intval', $_POST['wpc_variant_categories'] );
        update_post_meta( $post_id, '_wpc_variant_categories', $variant_cats );
    } else {
        delete_post_meta( $post_id, '_wpc_variant_categories' );
    }

    // Save Plan Features
    if ( isset( $_POST['wpc_plan_features'] ) && is_array( $_POST['wpc_plan_features'] ) ) {
        $features = array();
        foreach ( $_POST['wpc_plan_features'] as $f ) {
            if ( ! empty( $f['name'] ) ) { // Only save if has name
                $feature_data = array(
                    'name' => sanitize_text_field( $f['name'] ),
                    'visible' => isset($f['visible']) ? sanitize_text_field($f['visible']) : '1', // Default to 1 (visible)
                    'plans' => array()
                );
                if ( isset( $f['plans'] ) && is_array( $f['plans'] ) ) {
                    foreach ( $f['plans'] as $plan_idx => $val ) {
                        $feature_data['plans'][ intval( $plan_idx ) ] = '1';
                    }
                }
                $features[] = $feature_data;
            }
        }
        update_post_meta( $post_id, '_wpc_plan_features', $features );
    } else {
        delete_post_meta( $post_id, '_wpc_plan_features' );
    }

    // Save Feature Table Options
    if ( isset( $_POST['wpc_feature_table_options'] ) && is_array( $_POST['wpc_feature_table_options'] ) ) {
        $options = array(
            'display_mode' => sanitize_text_field( $_POST['wpc_feature_table_options']['display_mode'] ?? 'full_table' ),
            'header_label' => sanitize_text_field( $_POST['wpc_feature_table_options']['header_label'] ?? '' ),
            'header_bg'    => sanitize_hex_color( $_POST['wpc_feature_table_options']['header_bg'] ?? '#f3f4f6' ),
            'check_color'  => sanitize_hex_color( $_POST['wpc_feature_table_options']['check_color'] ?? '#10b981' ),
            'x_color'      => sanitize_hex_color( $_POST['wpc_feature_table_options']['x_color'] ?? '#ef4444' ),
            'alt_row_bg'   => sanitize_hex_color( $_POST['wpc_feature_table_options']['alt_row_bg'] ?? '#f9fafb' ),
        );
        update_post_meta( $post_id, '_wpc_feature_table_options', $options );
    }

    // Save Pricing Table Design
    if ( isset( $_POST['wpc_enable_design_overrides'] ) ) {
        update_post_meta( $post_id, '_wpc_enable_design_overrides', '1' );
    } else {
        update_post_meta( $post_id, '_wpc_enable_design_overrides', '0' );
    }

    if ( isset( $_POST['wpc_primary_color'] ) ) {
        update_post_meta( $post_id, '_wpc_primary_color', sanitize_hex_color( $_POST['wpc_primary_color'] ) );
    }
    if ( isset( $_POST['wpc_accent_color'] ) ) {
        update_post_meta( $post_id, '_wpc_accent_color', sanitize_hex_color( $_POST['wpc_accent_color'] ) );
    }
    if ( isset( $_POST['wpc_border_color'] ) ) {
        update_post_meta( $post_id, '_wpc_border_color', sanitize_hex_color( $_POST['wpc_border_color'] ) );
    }
    if ( isset( $_POST['wpc_color_coupon_bg'] ) ) {
        update_post_meta( $post_id, '_wpc_color_coupon_bg', sanitize_hex_color( $_POST['wpc_color_coupon_bg'] ) );
    }
    if ( isset( $_POST['wpc_color_coupon_text'] ) ) {
        update_post_meta( $post_id, '_wpc_color_coupon_text', sanitize_hex_color( $_POST['wpc_color_coupon_text'] ) );
    }
    
    // Pros & Cons Colors
    if ( isset( $_POST['wpc_color_pros_bg'] ) ) {
        update_post_meta( $post_id, '_wpc_color_pros_bg', sanitize_hex_color( $_POST['wpc_color_pros_bg'] ) );
    }
    if ( isset( $_POST['wpc_color_pros_text'] ) ) {
        update_post_meta( $post_id, '_wpc_color_pros_text', sanitize_hex_color( $_POST['wpc_color_pros_text'] ) );
    }
    if ( isset( $_POST['wpc_color_cons_bg'] ) ) {
        update_post_meta( $post_id, '_wpc_color_cons_bg', sanitize_hex_color( $_POST['wpc_color_cons_bg'] ) );
    }
    if ( isset( $_POST['wpc_color_cons_text'] ) ) {
        update_post_meta( $post_id, '_wpc_color_cons_text', sanitize_hex_color( $_POST['wpc_color_cons_text'] ) );
    }
    if ( isset( $_POST['wpc_enable_pros_cons_colors'] ) ) {
        update_post_meta( $post_id, '_wpc_enable_pros_cons_colors', '1' );
    } else {
        update_post_meta( $post_id, '_wpc_enable_pros_cons_colors', '0' );
    }

    if ( isset( $_POST['wpc_show_plan_buttons'] ) ) {
        update_post_meta( $post_id, '_wpc_show_plan_buttons', '1' );
    } else {
        update_post_meta( $post_id, '_wpc_show_plan_buttons', '0' );
    }
    
    if ( isset( $_POST['wpc_footer_button_text'] ) ) {
        update_post_meta( $post_id, '_wpc_footer_button_text', sanitize_text_field( $_POST['wpc_footer_button_text'] ) );
    }
    // Checkbox: If checked sent as 1. If unchecked not sent. 
    // Logic: We default to true. So we should store '0' if explicitly unchecked? 
    // Wait, standard HTML forms don't send anything if unchecked. 
    // So if isset => true. If not isset => false.
    // But earlier I checked ($val !== '0'). 
    // Let's adopt standard: Store '1' if present, '0' if not.
    // Footer Button Visibility (Granular)
    if ( isset( $_POST['wpc_show_footer_popup'] ) ) {
        update_post_meta( $post_id, '_wpc_show_footer_popup', '1' );
        // Sync legacy key for backward compat
        update_post_meta( $post_id, '_wpc_show_footer_button', '1' );
    } else {
        update_post_meta( $post_id, '_wpc_show_footer_popup', '0' );
        update_post_meta( $post_id, '_wpc_show_footer_button', '0' );
    }

    if ( isset( $_POST['wpc_show_footer_table'] ) ) {
        update_post_meta( $post_id, '_wpc_show_footer_table', '1' );
    } else {
        update_post_meta( $post_id, '_wpc_show_footer_table', '0' );
    }

    // Save Features Terms
    // FIXED: wpc_features input does not exist in our metabox (handled by WP sidebar).
    // This logic was ensuring terms were CLEARED on every save. Removing it.
    // Save Features Terms
    if ( isset( $_POST['wpc_features'] ) ) {
        $feature_ids = array_map( 'intval', $_POST['wpc_features'] );
        wp_set_post_terms( $post_id, $feature_ids, 'comparison_feature' );
    } else {
        // If empty, clear terms
        wp_set_post_terms( $post_id, array(), 'comparison_feature' );
    }
    
    // Save Custom Fields
    if ( isset( $_POST['wpc_custom_fields'] ) && is_array( $_POST['wpc_custom_fields'] ) ) {
        $custom_fields = [];
        foreach ( $_POST['wpc_custom_fields'] as $field ) {
            if ( ! empty( $field['name'] ) ) {
                $custom_fields[] = [
                    'name' => sanitize_text_field( $field['name'] ),
                    'value' => sanitize_text_field( $field['value'] ?? '' )
                ];
            }
        }
        update_post_meta( $post_id, '_wpc_custom_fields', $custom_fields );
    } else {
        delete_post_meta( $post_id, '_wpc_custom_fields' );
    }
    // DIRECT WRITE TO CUSTOM TABLE
    if ( class_exists('WPC_Database') ) {
        $db = new WPC_Database();
        
        // Prepare data for custom table (matching column names)
        $table_data = array(
            // Core Data
            'public_name' => isset($_POST['wpc_public_name']) ? sanitize_text_field($_POST['wpc_public_name']) : '',
            'short_description' => isset($_POST['wpc_short_description']) ? sanitize_textarea_field($_POST['wpc_short_description']) : '',
            'price' => isset($_POST['wpc_price']) ? sanitize_text_field($_POST['wpc_price']) : '',
            'period' => isset($_POST['wpc_period']) ? sanitize_text_field($_POST['wpc_period']) : '',
            'rating' => isset($_POST['wpc_rating']) ? sanitize_text_field($_POST['wpc_rating']) : '',
            
            // Links & Buttons
            'details_link' => isset($_POST['wpc_details_link']) ? esc_url_raw($_POST['wpc_details_link']) : '',
            'direct_link' => isset($_POST['wpc_direct_link']) ? esc_url_raw($_POST['wpc_direct_link']) : '',
            'button_text' => isset($_POST['wpc_button_text']) ? sanitize_text_field($_POST['wpc_button_text']) : '',
            'footer_button_text' => isset($_POST['wpc_footer_button_text']) ? sanitize_text_field($_POST['wpc_footer_button_text']) : '',
            
            // Visuals
            'logo_url' => isset($_POST['wpc_external_logo_url']) ? esc_url_raw($_POST['wpc_external_logo_url']) : '',
            'dashboard_image' => isset($_POST['wpc_dashboard_image']) ? esc_url_raw($_POST['wpc_dashboard_image']) : '',
            'hero_subtitle' => isset($_POST['wpc_hero_subtitle']) ? sanitize_text_field($_POST['wpc_hero_subtitle']) : '',
            'analysis_label' => isset($_POST['wpc_analysis_label']) ? sanitize_text_field($_POST['wpc_analysis_label']) : '',
            'badge_text' => isset($_POST['wpc_featured_badge_text']) ? sanitize_text_field($_POST['wpc_featured_badge_text']) : '',
            'badge_color' => isset($_POST['wpc_featured_color']) ? sanitize_hex_color($_POST['wpc_featured_color']) : '',
            
            // Product/Schema Details
            'condition_status' => isset($_POST['wpc_condition']) ? sanitize_text_field($_POST['wpc_condition']) : '',
            'availability' => isset($_POST['wpc_availability']) ? sanitize_text_field($_POST['wpc_availability']) : '',
            'mfg_date' => isset($_POST['wpc_mfg_date']) ? sanitize_text_field($_POST['wpc_mfg_date']) : '',
            'exp_date' => isset($_POST['wpc_exp_date']) ? sanitize_text_field($_POST['wpc_exp_date']) : '',
            'service_type' => isset($_POST['wpc_service_type']) ? sanitize_text_field($_POST['wpc_service_type']) : '',
            'area_served' => isset($_POST['wpc_area_served']) ? sanitize_text_field($_POST['wpc_area_served']) : '',
            'duration' => isset($_POST['wpc_duration']) ? sanitize_text_field($_POST['wpc_duration']) : '',
            'brand' => isset($_POST['wpc_brand']) ? sanitize_text_field($_POST['wpc_brand']) : '',
            'sku' => isset($_POST['wpc_sku']) ? sanitize_text_field($_POST['wpc_sku']) : '',
            'gtin' => isset($_POST['wpc_gtin']) ? sanitize_text_field($_POST['wpc_gtin']) : '',
            'product_category' => isset($_POST['wpc_product_category']) ? sanitize_text_field($_POST['wpc_product_category']) : '',
            
            // Pricing Settings
            'coupon_code' => isset($_POST['wpc_coupon_code']) ? sanitize_text_field($_POST['wpc_coupon_code']) : '',
            'show_coupon' => isset($_POST['wpc_show_coupon']) ? 1 : 0,
            'hide_plan_features' => isset($_POST['wpc_hide_plan_features']) ? 1 : 0,
            'show_plan_links' => isset($_POST['wpc_show_plan_links']) ? 1 : 0,
            'show_plan_links_popup' => isset($_POST['wpc_show_plan_links_popup']) ? 1 : 0,
            'show_plan_buttons' => isset($_POST['wpc_show_plan_buttons']) ? 1 : 0,
            'table_btn_pos' => isset($_POST['wpc_table_btn_pos']) ? sanitize_text_field($_POST['wpc_table_btn_pos']) : '',
            'popup_btn_pos' => isset($_POST['wpc_popup_btn_pos']) ? sanitize_text_field($_POST['wpc_popup_btn_pos']) : '',
            
            // Complex Data (Arrays - will be JSON encoded by WPC_Database)
            'pros' => isset($_POST['wpc_pros']) ? array_filter(array_map('trim', explode("\n", sanitize_textarea_field($_POST['wpc_pros'])))) : array(),
            'cons' => isset($_POST['wpc_cons']) ? array_filter(array_map('trim', explode("\n", sanitize_textarea_field($_POST['wpc_cons'])))) : array(),
            'pricing_plans' => isset($_POST['wpc_plans']) ? $plans : array(),
            'use_cases' => isset($_POST['wpc_use_cases']) ? $use_cases : array(),
            'plan_features' => isset($_POST['wpc_plan_features']) ? $features : array(),
            'competitors' => isset($_POST['wpc_competitors']) ? array_map('intval', $_POST['wpc_competitors']) : array(),
            'selected_tools' => (get_option('wpc_enable_tools_module', false) && isset($_POST['wpc_selected_tools'])) ? array_map('intval', $_POST['wpc_selected_tools']) : array(),
            
            // Design Overrides (JSON)
            'design_overrides' => array(
                'enabled' => isset($_POST['wpc_enable_design_overrides']) ? '1' : '0',
                'primary' => isset($_POST['wpc_primary_color']) ? sanitize_hex_color($_POST['wpc_primary_color']) : '',
                'accent' => isset($_POST['wpc_accent_color']) ? sanitize_hex_color($_POST['wpc_accent_color']) : '',
                'border' => isset($_POST['wpc_border_color']) ? sanitize_hex_color($_POST['wpc_border_color']) : '',
                'coupon_bg' => isset($_POST['wpc_color_coupon_bg']) ? sanitize_hex_color($_POST['wpc_color_coupon_bg']) : '',
                'coupon_text' => isset($_POST['wpc_color_coupon_text']) ? sanitize_hex_color($_POST['wpc_color_coupon_text']) : '',
                'show_footer_popup' => isset($_POST['wpc_show_footer_popup']) ? '1' : '0',
                'show_footer_table' => isset($_POST['wpc_show_footer_table']) ? '1' : '0',
                'show_hero_logo' => isset($_POST['wpc_show_hero_logo']) ? '1' : '0',
            ),
            
            // Pros/Cons Colors (JSON)
            'pros_cons_colors' => array(
                'enabled' => isset($_POST['wpc_enable_pros_cons_colors']) ? '1' : '0',
                'pros_bg' => isset($_POST['wpc_color_pros_bg']) ? sanitize_hex_color($_POST['wpc_color_pros_bg']) : '',
                'pros_text' => isset($_POST['wpc_color_pros_text']) ? sanitize_hex_color($_POST['wpc_color_pros_text']) : '',
                'cons_bg' => isset($_POST['wpc_color_cons_bg']) ? sanitize_hex_color($_POST['wpc_color_cons_bg']) : '',
                'cons_text' => isset($_POST['wpc_color_cons_text']) ? sanitize_hex_color($_POST['wpc_color_cons_text']) : '',
            ),
            
            // Feature Table Options (JSON)
            'feature_table_options' => isset($_POST['wpc_feature_table_options']) ? array(
                'display_mode' => sanitize_text_field($_POST['wpc_feature_table_options']['display_mode'] ?? 'full_table'),
                'header_label' => sanitize_text_field($_POST['wpc_feature_table_options']['header_label'] ?? ''),
                'header_bg' => sanitize_hex_color($_POST['wpc_feature_table_options']['header_bg'] ?? '#f3f4f6'),
                'check_color' => sanitize_hex_color($_POST['wpc_feature_table_options']['check_color'] ?? '#10b981'),
                'x_color' => sanitize_hex_color($_POST['wpc_feature_table_options']['x_color'] ?? '#ef4444'),
                'alt_row_bg' => sanitize_hex_color($_POST['wpc_feature_table_options']['alt_row_bg'] ?? '#f9fafb'),
            ) : array(),
            
            // Text Labels (JSON)
            'text_labels' => array(
                'pros_label' => isset($_POST['wpc_txt_pros_label']) ? sanitize_text_field($_POST['wpc_txt_pros_label']) : '',
                'cons_label' => isset($_POST['wpc_txt_cons_label']) ? sanitize_text_field($_POST['wpc_txt_cons_label']) : '',
                'price_label' => isset($_POST['wpc_txt_price_label']) ? sanitize_text_field($_POST['wpc_txt_price_label']) : '',
                'rating_label' => isset($_POST['wpc_txt_rating_label']) ? sanitize_text_field($_POST['wpc_txt_rating_label']) : '',
                'mo_suffix' => isset($_POST['wpc_txt_mo_suffix']) ? sanitize_text_field($_POST['wpc_txt_mo_suffix']) : '',
                'visit_site' => isset($_POST['wpc_txt_visit_site']) ? sanitize_text_field($_POST['wpc_txt_visit_site']) : '',
                'coupon_label' => isset($_POST['wpc_txt_coupon_label']) ? sanitize_text_field($_POST['wpc_txt_coupon_label']) : '',
                'copied_label' => isset($_POST['wpc_txt_copied_label']) ? sanitize_text_field($_POST['wpc_txt_copied_label']) : '',
                'feature_header' => isset($_POST['wpc_txt_feature_header']) ? sanitize_text_field($_POST['wpc_txt_feature_header']) : '',
            ),
        );
        


        // Save Design Overrides to Meta (JSON)
        update_post_meta( $post_id, '_wpc_design_overrides', $table_data['design_overrides'] );
        
        // Save Pros/Cons Colors to Meta (JSON)
        update_post_meta( $post_id, '_wpc_pros_cons_colors', $table_data['pros_cons_colors'] );
        
        // Save Feature Table Options to Meta (JSON)
        update_post_meta( $post_id, '_wpc_feature_table_options', $table_data['feature_table_options'] );
        
        // Save Text Labels to Meta (JSON)
        update_post_meta( $post_id, '_wpc_text_labels', $table_data['text_labels'] );

        // Write to custom table
        $result = $db->update_item($post_id, $table_data);
    }

    // Save Product Variants Meta (Module)
    if ( function_exists( 'wpc_save_variants_meta' ) ) {
        wpc_save_variants_meta( $post_id );
    }
}
add_action( 'save_post', 'wpc_save_meta_box' );

// Admin Notice to show save debug info


/**
 * Custom Admin Columns for Comparison Items
 */
function wpc_item_columns($columns) {
    $new_columns = array();
    // Insert ID after checkbox if possible
    if (isset($columns['cb'])) {
        $new_columns['cb'] = $columns['cb'];
        $new_columns['item_id'] = 'ID';
        unset($columns['cb']);
    } else {
        $new_columns['item_id'] = 'ID';
    }
    
    // Merge rest
    foreach($columns as $key => $value) {
        $new_columns[$key] = $value;
    }
    
    return $new_columns;
}
add_filter('manage_comparison_item_posts_columns', 'wpc_item_columns');

function wpc_item_custom_column($column, $post_id) {
    switch ($column) {
        case 'item_id':
            echo '<span style="font-family:monospace; background:#e5e7eb; padding:2px 6px; border-radius:4px; font-size:12px;">' . $post_id . '</span>';
            break;
        case 'logo':
            $logo_url = get_the_post_thumbnail_url($post_id, 'thumbnail');
            if (!$logo_url) {
                $logo_url = get_post_meta($post_id, '_wpc_external_logo_url', true);
            }
            if ($logo_url) {
                echo '<img src="' . esc_url($logo_url) . '" style="width: 50px; height: 50px; object-fit: contain; border-radius: 4px; border: 1px solid #ddd;" />';
            } else {
                echo '<span style="color:#ccc;">No Logo</span>';
            }
            break;
        case 'price':
            $price = get_post_meta($post_id, '_wpc_price', true);
            $period = get_post_meta($post_id, '_wpc_period', true);
            echo '<strong>' . esc_html($price) . '</strong>' . esc_html($period);
            break;
        case 'rating':
            $rating = get_post_meta($post_id, '_wpc_rating', true);
            echo '<span style="color: #f59e0b; font-weight: bold;">&#9733; ' . esc_html($rating) . '</span>';
            break;
        case 'type':
            $terms = get_the_terms($post_id, 'comparison_category');
            if ($terms && !is_wp_error($terms)) {
                $names = wp_list_pluck($terms, 'name');
                echo implode(', ', $names);
            } else {
                echo '&mdash;';
            }
            break;
    }
}
add_action('manage_comparison_item_posts_custom_column', 'wpc_item_custom_column', 10, 2);

function wpc_admin_column_width() {
    echo '<style>.column-item_id { width: 60px; text-align: center; } .column-logo { width: 80px; }</style>';
}
add_action('admin_head', 'wpc_admin_column_width');

/**
 * Dashboard Widget for Top Clicks
 */
function wpc_add_dashboard_widgets() {
    wp_add_dashboard_widget(
        'wpc_top_items',
        'Top Comparison Items (Clicks)',
        'wpc_dashboard_widget_function'
    );
}
add_action( 'wp_dashboard_setup', 'wpc_add_dashboard_widgets' );

function wpc_dashboard_widget_function() {
    $args = array(
        'post_type' => 'comparison_item',
        'posts_per_page' => 5,
        'meta_key' => '_wpc_clicks',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
    );
    $query = new WP_Query( $args );

    echo '<table style="width:100%; text-align:left;">';
    echo '<thead><tr><th>Item</th><th>Clicks</th></tr></thead>';
    echo '<tbody>';
    
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $clicks = get_post_meta( get_the_ID(), '_wpc_clicks', true ) ?: 0;
            echo '<tr>';
            echo '<td style="padding: 5px 0;">' . get_the_title() . '</td>';
            echo '<td style="padding: 5px 0; font-weight:bold;">' . esc_html( $clicks ) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="2">No data yet.</td></tr>';
    }
    
    echo '</tbody></table>';
    wp_reset_postdata();
}

/**
 * Rename Excerpt to Details for Comparison Item
 */
function wpc_change_excerpt_label( $translation, $original, $domain ) {
    global $post;
    if ( isset( $post ) && $post->post_type == 'comparison_item' ) {
        if ( 'Excerpt' == $original ) {
            return 'Details'; // Rename to Details
        }
    }
    return $translation;
}
add_filter( 'gettext', 'wpc_change_excerpt_label', 10, 3 );


