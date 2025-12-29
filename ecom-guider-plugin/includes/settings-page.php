<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add Settings Page for Global Plugin Settings
 */
add_action( 'admin_menu', 'wpc_add_settings_page' );
function wpc_add_settings_page() {
    add_submenu_page(
        'edit.php?post_type=comparison_item',
        __( 'Settings', 'wp-comparison-builder' ),
        __( 'Settings', 'wp-comparison-builder' ),
        'manage_options',
        'wpc-settings',
        'wpc_render_settings_page'
    );
}

/**
 * Register Settings
 */
add_action( 'admin_init', 'wpc_register_settings' );
function wpc_register_settings() {
    // Visual Style Settings
    register_setting( 'wpc_settings_group', 'wpc_primary_color' );
    register_setting( 'wpc_settings_group', 'wpc_accent_color' );
    register_setting( 'wpc_settings_group', 'wpc_secondary_color' );
    register_setting( 'wpc_settings_group', 'wpc_card_border_color' );
    register_setting( 'wpc_settings_group', 'wpc_pricing_banner_color' );
    register_setting( 'wpc_settings_group', 'wpc_button_hover_color' );
    
    // Show Plan Buttons Setting
    register_setting( 'wpc_settings_group', 'wpc_show_plan_buttons' );
    register_setting( 'wpc_settings_group', 'wpc_show_footer_button_global' );
    
    // Filter Style Setting
    register_setting( 'wpc_settings_group', 'wpc_filter_style' );

    // Pricing Table Visuals
    register_setting( 'wpc_settings_group', 'wpc_pt_header_bg' );
    register_setting( 'wpc_settings_group', 'wpc_pt_header_text' );
    register_setting( 'wpc_settings_group', 'wpc_pt_btn_bg' );
    register_setting( 'wpc_settings_group', 'wpc_pt_btn_text' );
    // Position Settings
    register_setting( 'wpc_settings_group', 'wpc_pt_btn_pos_table' );
    register_setting( 'wpc_settings_group', 'wpc_pt_btn_pos_popup' );
}

/**
 * Handle Import/Export AJAX
 */
add_action( 'wp_ajax_wpc_export_data', 'wpc_handle_export_data' );
function wpc_handle_export_data() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized' );
    }

    check_ajax_referer( 'wpc_import_export_nonce', 'nonce' );
    
    // Get selected export types (default to all)
    $export_types = isset( $_POST['export_types'] ) ? json_decode( stripslashes( $_POST['export_types'] ), true ) : array();
    if ( empty( $export_types ) ) {
        $export_types = array( 'items', 'categories', 'features', 'lists', 'settings' );
    }
    $should_export = array_flip( $export_types );

    $export_data = array(
        'version' => '1.0',
        'exported_at' => current_time( 'mysql' ),
        'site_url' => get_site_url(),
        'categories' => array(),
        'features' => array(),
        'comparison_items' => array(),
        'custom_lists' => array(),
        'settings' => array(),
    );

    // Export Categories (Taxonomy Terms)
    if ( isset( $should_export['categories'] ) ) {
        $cat_terms = get_terms( array( 'taxonomy' => 'comparison_category', 'hide_empty' => false ) );
        if ( ! is_wp_error( $cat_terms ) ) {
            foreach ( $cat_terms as $term ) {
                $export_data['categories'][] = array(
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'description' => $term->description,
                );
            }
        }
    }

    // Export Features (Taxonomy Terms)
    if ( isset( $should_export['features'] ) ) {
        $feat_terms = get_terms( array( 'taxonomy' => 'comparison_feature', 'hide_empty' => false ) );
        if ( ! is_wp_error( $feat_terms ) ) {
            foreach ( $feat_terms as $term ) {
                $export_data['features'][] = array(
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'description' => $term->description,
                );
            }
        }
    }

    // Export Comparison Items
    if ( isset( $should_export['items'] ) ) {
        $items = get_posts( array(
            'post_type' => 'comparison_item',
            'posts_per_page' => -1,
            'post_status' => array( 'publish', 'draft' ),
        ));

        foreach ( $items as $item ) {
            $item_data = array(
                'post_title' => $item->post_title,
                'post_name' => $item->post_name,
                'post_status' => $item->post_status,
                'post_content' => $item->post_content,
                'meta' => array(),
            );

            // Get all meta for this item
            $all_meta = get_post_meta( $item->ID );
            foreach ( $all_meta as $key => $value ) {
                if ( strpos( $key, '_wpc_' ) === 0 || strpos( $key, 'wpc_' ) === 0 ) {
                    $item_data['meta'][ $key ] = maybe_unserialize( $value[0] );
                }
            }

            // Get taxonomies
            $categories = wp_get_post_terms( $item->ID, 'comparison_category', array( 'fields' => 'slugs' ) );
            $features = wp_get_post_terms( $item->ID, 'comparison_feature', array( 'fields' => 'slugs' ) );
            $item_data['categories'] = is_array( $categories ) ? $categories : array();
            $item_data['features'] = is_array( $features ) ? $features : array();

            $export_data['comparison_items'][] = $item_data;
        }
    }

    // Export Custom Lists
    if ( isset( $should_export['lists'] ) ) {
        $lists = get_posts( array(
            'post_type' => 'comparison_list',
            'posts_per_page' => -1,
            'post_status' => array( 'publish', 'draft' ),
        ));

        foreach ( $lists as $list ) {
            $list_data = array(
                'post_title' => $list->post_title,
                'post_name' => $list->post_name,
                'post_status' => $list->post_status,
                'meta' => array(),
            );

            $all_meta = get_post_meta( $list->ID );
            foreach ( $all_meta as $key => $value ) {
                if ( strpos( $key, '_wpc_' ) === 0 || strpos( $key, 'wpc_' ) === 0 ) {
                    $list_data['meta'][ $key ] = maybe_unserialize( $value[0] );
                }
            }

            $export_data['custom_lists'][] = $list_data;
        }
    }

    // Export Settings
    if ( isset( $should_export['settings'] ) ) {
        $settings_to_export = array(
            'wpc_primary_color',
            'wpc_accent_color',
            'wpc_secondary_color',
            'wpc_card_border_color',
            'wpc_pricing_banner_color',
            'wpc_button_hover_color',
            'wpc_show_plan_buttons',
            'wpc_show_footer_button_global',
            'wpc_filter_style',
            'wpc_pt_header_bg',
            'wpc_pt_header_text',
            'wpc_pt_btn_bg',
            'wpc_pt_btn_text',
            'wpc_pt_btn_pos_table',
            'wpc_pt_btn_pos_popup',
            'wpc_schema_settings',
        );

        foreach ( $settings_to_export as $setting ) {
            $export_data['settings'][ $setting ] = get_option( $setting );
        }
    }

    wp_send_json_success( $export_data );
}

add_action( 'wp_ajax_wpc_import_data', 'wpc_handle_import_data' );
function wpc_handle_import_data() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized' );
    }

    check_ajax_referer( 'wpc_import_export_nonce', 'nonce' );

    $json_data = isset( $_POST['json_data'] ) ? $_POST['json_data'] : '';
    $overwrite = isset( $_POST['overwrite'] ) && $_POST['overwrite'] === 'true';

    if ( empty( $json_data ) ) {
        wp_send_json_error( 'No data provided' );
    }

    $data = json_decode( stripslashes( $json_data ), true );
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        wp_send_json_error( 'Invalid JSON: ' . json_last_error_msg() );
    }

    $results = array(
        'items_created' => 0,
        'items_updated' => 0,
        'lists_created' => 0,
        'lists_updated' => 0,
        'settings_updated' => 0,
    );

    // Import Comparison Items (only if allowed)
    $import_items = isset( $_POST['import_items'] ) && $_POST['import_items'] === 'true';
    if ( $import_items && ! empty( $data['comparison_items'] ) ) {
        foreach ( $data['comparison_items'] as $item_data ) {
            $existing = get_page_by_path( $item_data['post_name'], OBJECT, 'comparison_item' );
            
            if ( $existing && $overwrite ) {
                // Update existing
                wp_update_post( array(
                    'ID' => $existing->ID,
                    'post_title' => $item_data['post_title'],
                    'post_content' => $item_data['post_content'] ?? '',
                    'post_status' => $item_data['post_status'],
                ));
                $post_id = $existing->ID;
                $results['items_updated']++;
            } elseif ( ! $existing ) {
                // Create new
                $post_id = wp_insert_post( array(
                    'post_type' => 'comparison_item',
                    'post_title' => $item_data['post_title'],
                    'post_name' => $item_data['post_name'],
                    'post_content' => $item_data['post_content'] ?? '',
                    'post_status' => $item_data['post_status'],
                ));
                $results['items_created']++;
            } else {
                continue; // Skip if exists and not overwriting
            }

            if ( $post_id && ! is_wp_error( $post_id ) ) {
                // Import meta
                if ( ! empty( $item_data['meta'] ) ) {
                    foreach ( $item_data['meta'] as $key => $value ) {
                        // Convert arrays to newline-separated strings for textarea fields
                        if ( in_array( $key, ['_wpc_pros', '_wpc_cons'], true ) && is_array( $value ) ) {
                            $value = implode( "\n", $value );
                        }
                        // Convert pricing plan fields and map JSON names to WordPress field names
                        if ( $key === '_wpc_pricing_plans' && is_array( $value ) ) {
                            foreach ( $value as &$plan ) {
                                // Map JSON field names to WordPress field names
                                if ( isset( $plan['cta_url'] ) && !isset( $plan['link'] ) ) {
                                    $plan['link'] = $plan['cta_url'];
                                    unset( $plan['cta_url'] );
                                }
                                if ( isset( $plan['cta_text'] ) && !isset( $plan['button_text'] ) ) {
                                    $plan['button_text'] = $plan['cta_text'];
                                    unset( $plan['cta_text'] );
                                }
                                // Handle discount_badge -> banner_text mapping
                                if ( isset( $plan['discount_badge'] ) && !isset( $plan['banner_text'] ) ) {
                                    $plan['banner_text'] = $plan['discount_badge'];
                                    $plan['show_banner'] = '1';
                                    unset( $plan['discount_badge'] );
                                }
                                // Handle original_price -> show_banner
                                if ( isset( $plan['original_price'] ) && !isset( $plan['show_banner'] ) ) {
                                    $plan['show_banner'] = '1';
                                }
                                // Set default visibility flags if not present
                                if ( !isset( $plan['show_popup'] ) ) {
                                    $plan['show_popup'] = '1';
                                }
                                if ( !isset( $plan['show_table'] ) ) {
                                    $plan['show_table'] = '1';
                                }
                                if ( !isset( $plan['banner_color'] ) && isset( $plan['show_banner'] ) ) {
                                    $plan['banner_color'] = '#10b981';
                                }
                                // Convert features array to newline string
                                if ( isset( $plan['features'] ) && is_array( $plan['features'] ) ) {
                                    $plan['features'] = implode( "\n", $plan['features'] );
                                }
                            }
                        }
                        update_post_meta( $post_id, $key, $value );
                    }
                }

                // Import taxonomies
                if ( ! empty( $item_data['categories'] ) ) {
                    wp_set_object_terms( $post_id, $item_data['categories'], 'comparison_category' );
                }
                if ( ! empty( $item_data['features'] ) ) {
                    wp_set_object_terms( $post_id, $item_data['features'], 'comparison_feature' );
                }
            }
        }
    }

    // Import Custom Lists (only if allowed)
    $import_lists = isset( $_POST['import_lists'] ) && $_POST['import_lists'] === 'true';
    if ( $import_lists && ! empty( $data['custom_lists'] ) ) {
        foreach ( $data['custom_lists'] as $list_data ) {
            $existing = get_page_by_path( $list_data['post_name'], OBJECT, 'comparison_list' );
            
            if ( $existing && $overwrite ) {
                wp_update_post( array(
                    'ID' => $existing->ID,
                    'post_title' => $list_data['post_title'],
                    'post_status' => $list_data['post_status'],
                ));
                $post_id = $existing->ID;
                $results['lists_updated']++;
            } elseif ( ! $existing ) {
                $post_id = wp_insert_post( array(
                    'post_type' => 'comparison_list',
                    'post_title' => $list_data['post_title'],
                    'post_name' => $list_data['post_name'],
                    'post_status' => $list_data['post_status'],
                ));
                $results['lists_created']++;
            } else {
                continue;
            }

            if ( $post_id && ! is_wp_error( $post_id ) ) {
                if ( ! empty( $list_data['meta'] ) ) {
                    foreach ( $list_data['meta'] as $key => $value ) {
                        update_post_meta( $post_id, $key, $value );
                    }
                }
            }
        }
    }

    // Import Settings (only if allowed)
    $import_settings = isset( $_POST['import_settings'] ) && $_POST['import_settings'] === 'true';
    if ( $import_settings && ! empty( $data['settings'] ) ) {
        foreach ( $data['settings'] as $key => $value ) {
            if ( strpos( $key, 'wpc_' ) === 0 ) {
                update_option( $key, $value );
                $results['settings_updated']++;
            }
        }
    }

    // Import Categories (taxonomy terms)
    $import_categories = isset( $_POST['import_categories'] ) && $_POST['import_categories'] === 'true';
    if ( $import_categories && ! empty( $data['categories'] ) ) {
        foreach ( $data['categories'] as $cat ) {
            if ( ! term_exists( $cat['slug'], 'comparison_category' ) ) {
                wp_insert_term( $cat['name'], 'comparison_category', array(
                    'slug' => $cat['slug'],
                    'description' => $cat['description'] ?? '',
                ));
                $results['categories_created'] = ( $results['categories_created'] ?? 0 ) + 1;
            }
        }
    }

    // Import Features (taxonomy terms)
    $import_features = isset( $_POST['import_features'] ) && $_POST['import_features'] === 'true';
    if ( $import_features && ! empty( $data['features'] ) ) {
        foreach ( $data['features'] as $feat ) {
            if ( ! term_exists( $feat['slug'], 'comparison_feature' ) ) {
                wp_insert_term( $feat['name'], 'comparison_feature', array(
                    'slug' => $feat['slug'],
                    'description' => $feat['description'] ?? '',
                ));
                $results['features_created'] = ( $results['features_created'] ?? 0 ) + 1;
            }
        }
    }

    wp_send_json_success( $results );
}

/**
 * Detect conflicts before import
 */
add_action( 'wp_ajax_wpc_detect_conflicts', 'wpc_detect_conflicts' );
function wpc_detect_conflicts() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized' );
    }

    check_ajax_referer( 'wpc_import_export_nonce', 'nonce' );

    $json_data = isset( $_POST['json_data'] ) ? $_POST['json_data'] : '';
    if ( empty( $json_data ) ) {
        wp_send_json_error( 'No data provided' );
    }

    $data = json_decode( stripslashes( $json_data ), true );
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        wp_send_json_error( 'Invalid JSON' );
    }

    $conflicts = array();
    $new_items = array();

    // Check items
    if ( ! empty( $data['comparison_items'] ) ) {
        foreach ( $data['comparison_items'] as $item_data ) {
            $existing = get_page_by_path( $item_data['post_name'], OBJECT, 'comparison_item' );
            if ( $existing ) {
                $conflicts[] = array(
                    'type' => 'item',
                    'slug' => $item_data['post_name'],
                    'title' => $item_data['post_title'],
                );
            } else {
                $new_items[] = array(
                    'type' => 'item',
                    'slug' => $item_data['post_name'],
                    'title' => $item_data['post_title'],
                );
            }
        }
    }

    // Check lists
    if ( ! empty( $data['custom_lists'] ) ) {
        foreach ( $data['custom_lists'] as $list_data ) {
            $existing = get_page_by_path( $list_data['post_name'], OBJECT, 'comparison_list' );
            if ( $existing ) {
                $conflicts[] = array(
                    'type' => 'list',
                    'slug' => $list_data['post_name'],
                    'title' => $list_data['post_title'],
                );
            } else {
                $new_items[] = array(
                    'type' => 'list',
                    'slug' => $list_data['post_name'],
                    'title' => $list_data['post_title'],
                );
            }
        }
    }

    wp_send_json_success( array(
        'conflicts' => $conflicts,
        'new_items' => $new_items,
        'summary' => array(
            'categories_count' => count( $data['categories'] ?? array() ),
            'features_count' => count( $data['features'] ?? array() ),
            'items_count' => count( $data['comparison_items'] ?? array() ),
            'lists_count' => count( $data['custom_lists'] ?? array() ),
        ),
    ));
}

/**
 * Reset settings to defaults
 */
add_action( 'wp_ajax_wpc_reset_settings', 'wpc_reset_settings' );
function wpc_reset_settings() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized' );
    }

    check_ajax_referer( 'wpc_import_export_nonce', 'nonce' );

    $defaults = array(
        'wpc_primary_color' => '#6366f1',
        'wpc_accent_color' => '#0d9488',
        'wpc_secondary_color' => '#1e293b',
        'wpc_card_border_color' => '#e2e8f0',
        'wpc_pricing_banner_color' => '#10b981',
        'wpc_button_hover_color' => '',
        'wpc_show_plan_buttons' => '1',
        'wpc_show_footer_button_global' => '1',
        'wpc_filter_style' => 'top',
        'wpc_pt_header_bg' => '#f8fafc',
        'wpc_pt_header_text' => '#0f172a',
        'wpc_pt_btn_bg' => '#0f172a',
        'wpc_pt_btn_text' => '#ffffff',
        'wpc_pt_btn_pos_table' => 'after_price',
        'wpc_pt_btn_pos_popup' => 'after_price',
    );

    foreach ( $defaults as $key => $value ) {
        update_option( $key, $value );
    }

    wp_send_json_success( 'Settings reset to defaults' );
}

/**
 * Delete all data and reset settings
 */
add_action( 'wp_ajax_wpc_delete_all_data', 'wpc_delete_all_data' );
function wpc_delete_all_data() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized' );
    }

    check_ajax_referer( 'wpc_import_export_nonce', 'nonce' );

    $results = array(
        'items_deleted' => 0,
        'lists_deleted' => 0,
        'categories_deleted' => 0,
        'features_deleted' => 0,
    );

    // Delete all comparison items
    $items = get_posts( array( 'post_type' => 'comparison_item', 'posts_per_page' => -1, 'fields' => 'ids' ) );
    foreach ( $items as $id ) {
        wp_delete_post( $id, true );
        $results['items_deleted']++;
    }

    // Delete all custom lists
    $lists = get_posts( array( 'post_type' => 'comparison_list', 'posts_per_page' => -1, 'fields' => 'ids' ) );
    foreach ( $lists as $id ) {
        wp_delete_post( $id, true );
        $results['lists_deleted']++;
    }

    // Delete all categories
    $cats = get_terms( array( 'taxonomy' => 'comparison_category', 'hide_empty' => false, 'fields' => 'ids' ) );
    if ( ! is_wp_error( $cats ) ) {
        foreach ( $cats as $term_id ) {
            wp_delete_term( $term_id, 'comparison_category' );
            $results['categories_deleted']++;
        }
    }

    // Delete all features
    $feats = get_terms( array( 'taxonomy' => 'comparison_feature', 'hide_empty' => false, 'fields' => 'ids' ) );
    if ( ! is_wp_error( $feats ) ) {
        foreach ( $feats as $term_id ) {
            wp_delete_term( $term_id, 'comparison_feature' );
            $results['features_deleted']++;
        }
    }

    // Reset settings to defaults
    $defaults = array(
        'wpc_primary_color' => '#6366f1',
        'wpc_accent_color' => '#0d9488',
        'wpc_secondary_color' => '#1e293b',
        'wpc_card_border_color' => '#e2e8f0',
        'wpc_pricing_banner_color' => '#10b981',
        'wpc_button_hover_color' => '',
        'wpc_show_plan_buttons' => '1',
        'wpc_show_footer_button_global' => '1',
        'wpc_filter_style' => 'top',
    );

    foreach ( $defaults as $key => $value ) {
        update_option( $key, $value );
    }

    wp_send_json_success( $results );
}

/**
 * Render Settings Page
 */
function wpc_render_settings_page() {
    ?>
    <div class="wrap" style="padding-bottom: 60px;">
        <h1><?php _e( 'Comparison Builder Settings', 'wp-comparison-builder' ); ?></h1>
        
        <!-- Tab Navigation -->
        <nav class="nav-tab-wrapper wpc-tabs-nav" style="margin-bottom: 20px;">
            <a href="#" class="nav-tab nav-tab-active" data-tab="general">
                <?php _e( 'General & Visuals', 'wp-comparison-builder' ); ?>
            </a>
            <a href="#" class="nav-tab" data-tab="pricing">
                <?php _e( 'Pricing Table', 'wp-comparison-builder' ); ?>
            </a>
            <a href="#" class="nav-tab" data-tab="schema-seo">
                <?php _e( 'Schema SEO', 'wp-comparison-builder' ); ?>
            </a>
            <a href="#" class="nav-tab" data-tab="import-export">
                <?php _e( 'Import / Export', 'wp-comparison-builder' ); ?>
            </a>
            <a href="#" class="nav-tab" data-tab="json-schema">
                <?php _e( 'JSON Schema', 'wp-comparison-builder' ); ?>
            </a>
            <a href="#" class="nav-tab" data-tab="danger-zone" style="color: #d32f2f;">
                <?php _e( '⚠️ Danger Zone', 'wp-comparison-builder' ); ?>
            </a>
        </nav>

        <!-- Tab Contents -->
        <div class="wpc-tab-content" id="wpc-tab-general">
            <?php wpc_render_general_tab(); ?>
        </div>
        
        <div class="wpc-tab-content" id="wpc-tab-pricing" style="display: none;">
            <?php wpc_render_pricing_tab(); ?>
        </div>
        
        <div class="wpc-tab-content" id="wpc-tab-schema-seo" style="display: none;">
            <?php wpc_render_schema_seo_tab(); ?>
        </div>
        
        <div class="wpc-tab-content" id="wpc-tab-import-export" style="display: none;">
            <?php wpc_render_import_export_tab(); ?>
        </div>
        
        <div class="wpc-tab-content" id="wpc-tab-json-schema" style="display: none;">
            <?php wpc_render_json_schema_tab(); ?>
        </div>
        
        <div class="wpc-tab-content" id="wpc-tab-danger-zone" style="display: none;">
            <?php wpc_render_danger_zone_tab(); ?>
        </div>
    </div>
    
    <script>
    (function() {
        // Tab switching functionality
        const tabs = document.querySelectorAll('.wpc-tabs-nav .nav-tab');
        const contents = document.querySelectorAll('.wpc-tab-content');
        
        // Check for saved tab in localStorage
        const savedTab = localStorage.getItem('wpc_active_tab');
        if (savedTab) {
            switchTab(savedTab);
        }
        
        tabs.forEach(function(tab) {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                const targetTab = this.getAttribute('data-tab');
                switchTab(targetTab);
                // Save to localStorage
                localStorage.setItem('wpc_active_tab', targetTab);
            });
        });
        
        function switchTab(tabName) {
            // Remove active class from all tabs
            tabs.forEach(function(t) {
                t.classList.remove('nav-tab-active');
            });
            
            // Hide all content
            contents.forEach(function(c) {
                c.style.display = 'none';
            });
            
            // Activate selected tab
            const activeTab = document.querySelector('.nav-tab[data-tab="' + tabName + '"]');
            const activeContent = document.getElementById('wpc-tab-' + tabName);
            
            if (activeTab && activeContent) {
                activeTab.classList.add('nav-tab-active');
                activeContent.style.display = 'block';
            }
        }
    })();
    </script>
    <?php
}

/**
 * General & Visuals Tab
 */
function wpc_render_general_tab() {
    ?>
    <form method="post" action="options.php">
        <?php settings_fields( 'wpc_settings_group' ); ?>
        <?php do_settings_sections( 'wpc_settings_group' ); ?>
        
        <h2><?php _e( 'Visual Style', 'wp-comparison-builder' ); ?></h2>
        <p><?php _e( 'Customize the look and feel of the comparison tool. These colors will override the defaults.', 'wp-comparison-builder' ); ?></p>
        
        <table class="form-table">
            <!-- Primary Color -->
            <tr valign="top">
                <th scope="row">
                    <label for="wpc_primary_color"><?php _e( 'Primary Color (Buttons, Badges)', 'wp-comparison-builder' ); ?></label>
                </th>
                <td>
                    <input 
                        type="color" 
                        id="wpc_primary_color" 
                        name="wpc_primary_color" 
                        value="<?php echo esc_attr( get_option( 'wpc_primary_color', '#6366f1' ) ); ?>"
                        style="width: 100px; height: 40px; cursor: pointer;"
                    />
                    <p class="description">
                        Choose the primary color for buttons, badges, and highlights. Default: <code>#6366f1</code> (indigo)
                    </p>
                </td>
            </tr>
            
            <!-- Accent Color -->
            <tr valign="top">
                <th scope="row">
                    <label for="wpc_accent_color"><?php _e( 'Accent Color (Active Filters, Highlights)', 'wp-comparison-builder' ); ?></label>
                </th>
                <td>
                    <input 
                        type="color" 
                        id="wpc_accent_color" 
                        name="wpc_accent_color" 
                        value="<?php echo esc_attr( get_option( 'wpc_accent_color', '#0d9488' ) ); ?>"
                        style="width: 100px; height: 40px; cursor: pointer;"
                    />
                    <p class="description">
                        Choose the accent color for active filters and highlights. Default: <code>#0d9488</code> (teal)
                    </p>
                </td>
            </tr>
            
            <!-- Secondary Color -->
            <tr valign="top">
                <th scope="row">
                    <label for="wpc_secondary_color"><?php _e( 'Secondary Color (Dark Accents)', 'wp-comparison-builder' ); ?></label>
                </th>
                <td>
                    <input 
                        type="color" 
                        id="wpc_secondary_color" 
                        name="wpc_secondary_color" 
                        value="<?php echo esc_attr( get_option( 'wpc_secondary_color', '#1e293b' ) ); ?>"
                        style="width: 100px; height: 40px; cursor: pointer;"
                    />
                    <p class="description">
                        Choose the secondary color for dark accents and text. Default: <code>#1e293b</code> (slate)
                    </p>
                </td>
            </tr>
            
            <!-- Card Border Color -->
            <tr valign="top">
                <th scope="row">
                    <label for="wpc_card_border_color"><?php _e( 'Card Border Color', 'wp-comparison-builder' ); ?></label>
                </th>
                <td>
                    <input 
                        type="color" 
                        id="wpc_card_border_color" 
                        name="wpc_card_border_color" 
                        value="<?php echo esc_attr( get_option( 'wpc_card_border_color', '' ) ); ?>"
                        style="width: 100px; height: 40px; cursor: pointer;"
                    />
                    <p class="description">
                        Choose the border color for standard items (non-featured). Leave empty to use default (light gray).
                    </p>
                    
                    <div style="margin-top: 15px;">
                        <strong>Quick Presets:</strong><br>
                        <button type="button" onclick="setAllColors('#6366f1', '#0d9488', '#1e293b')" class="button" style="margin: 5px;">Indigo Theme</button>
                        <button type="button" onclick="setAllColors('#10b981', '#059669', '#1e293b')" class="button" style="margin: 5px;">Green Theme</button>
                        <button type="button" onclick="setAllColors('#f59e0b', '#d97706', '#1e293b')" class="button" style="margin: 5px;">Orange Theme</button>
                        <button type="button" onclick="setAllColors('#8b5cf6', '#7c3aed', '#1e293b')" class="button" style="margin: 5px;">Purple Theme</button>
                        <button type="button" onclick="setAllColors('#0ea5e9', '#0284c7', '#1e293b')" class="button" style="margin: 5px;">Blue Theme</button>
                    </div>
                </td>
            </tr>
            
            <!-- Pricing Banner Color -->
            <tr valign="top">
                <th scope="row">
                    <label for="wpc_pricing_banner_color"><?php _e( 'Pricing Banner Color', 'wp-comparison-builder' ); ?></label>
                </th>
                <td>
                    <input 
                        type="color" 
                        id="wpc_pricing_banner_color" 
                        name="wpc_pricing_banner_color" 
                        value="<?php echo esc_attr( get_option( 'wpc_pricing_banner_color', '#10b981' ) ); ?>"
                        style="width: 100px; height: 40px; cursor: pointer;"
                    />
                    <p class="description">
                        Choose the background color for pricing plan discount banners (e.g., "70% OFF"). Default: <code>#10b981</code> (green)
                    </p>
                </td>
            </tr>

            <!-- Button Hover Color -->
            <tr valign="top">
                <th scope="row">
                    <label for="wpc_button_hover_color"><?php _e( 'Button Hover Color', 'wp-comparison-builder' ); ?></label>
                </th>
                <td>
                    <?php $hover_color = get_option( 'wpc_button_hover_color', '' ); ?>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input 
                            type="color" 
                            id="wpc_button_hover_color_picker" 
                            value="<?php echo esc_attr( $hover_color ? $hover_color : '#059669' ); ?>"
                            style="width: 50px; height: 40px; cursor: pointer;"
                            onchange="document.getElementById('wpc_button_hover_color').value = this.value"
                        />
                        <input 
                            type="text" 
                            id="wpc_button_hover_color" 
                            name="wpc_button_hover_color" 
                            value="<?php echo esc_attr( $hover_color ); ?>"
                            placeholder="Default (Auto)"
                            style="width: 120px;"
                            onchange="document.getElementById('wpc_button_hover_color_picker').value = this.value"
                        />
                        <button type="button" class="button" onclick="resetButtonHover()">Clear / Reset</button>
                    </div>
                    <p class="description">
                        Choose a custom hover color for primary buttons. Leave empty to use the default automatic hover effect (slightly darker than Primary Color).
                    </p>
                </td>
            </tr>

            <!-- Show Plan Selection Buttons -->
            <tr valign="top">
                <th scope="row">
                    <label for="wpc_show_plan_buttons"><?php _e( 'Show Plan Selection Buttons', 'wp-comparison-builder' ); ?></label>
                </th>
                <td>
                    <input 
                        type="checkbox" 
                        id="wpc_show_plan_buttons" 
                        name="wpc_show_plan_buttons" 
                        value="1"
                        <?php checked( get_option( 'wpc_show_plan_buttons', '1' ), '1' ); ?>
                    />
                    <label for="wpc_show_plan_buttons">
                        Show "Select Plan" buttons in pricing popups
                    </label>
                    <p class="description">
                        When enabled, pricing plan popups will include action buttons for each plan.
                    </p>
                </td>
            </tr>

            <!-- Show Footer Button (Global) -->
            <tr valign="top">
                <th scope="row">
                    <label for="wpc_show_footer_button_global"><?php _e( 'Show "Visit Website" Footer Button', 'wp-comparison-builder' ); ?></label>
                </th>
                <td>
                    <input 
                        type="checkbox" 
                        id="wpc_show_footer_button_global" 
                        name="wpc_show_footer_button_global" 
                        value="1"
                        <?php checked( get_option( 'wpc_show_footer_button_global', '1' ), '1' ); ?>
                    />
                    <label for="wpc_show_footer_button_global">
                        Show the footer button (Visit Website) in pricing tables/popups by default
                    </label>
                </td>
            </tr>
        </table>

        <hr style="margin: 40px 0;">
        
        <h2><?php _e( 'Filter Layout', 'wp-comparison-builder' ); ?></h2>
        <p><?php _e( 'Choose how the filters should look on the comparison page.', 'wp-comparison-builder' ); ?></p>
        
        <?php $current_style = get_option( 'wpc_filter_style', 'top' ); ?>
        
        <div class="filter-style-options" style="display: flex; gap: 30px; margin-top: 20px; margin-bottom: 40px;">
            <!-- Top Option -->
            <label style="cursor: pointer; flex: 1; max-width: 400px;">
                <input type="radio" name="wpc_filter_style" value="top" <?php checked( $current_style, 'top' ); ?> style="margin-bottom: 10px; display: block;" />
                <div style="border: 2px solid <?php echo ($current_style === 'top' ? '#6366f1' : '#ddd'); ?>; border-radius: 8px; padding: 15px; background: #fff;">
                    <strong style="display: block; margin-bottom: 10px;">Horizontal (Top)</strong>
                    <div style="background: #f3f4f6; height: 100px; border-radius: 4px; display: flex; flex-direction: column; gap: 8px; padding: 10px;">
                        <div style="background: #fff; height: 20px; width: 100%; border: 1px solid #e5e7eb; border-radius: 4px;"></div>
                        <div style="display: flex; gap: 8px; flex: 1;">
                            <div style="background: #fff; flex: 1; border: 1px solid #e5e7eb; border-radius: 4px;"></div>
                            <div style="background: #fff; flex: 1; border: 1px solid #e5e7eb; border-radius: 4px;"></div>
                            <div style="background: #fff; flex: 1; border: 1px solid #e5e7eb; border-radius: 4px;"></div>
                        </div>
                    </div>
                    <p class="description" style="margin-top: 10px;">Filters appear in a horizontal bar above the list. Best for few filters.</p>
                </div>
            </label>

            <!-- Sidebar Option -->
            <label style="cursor: pointer; flex: 1; max-width: 400px;">
                <input type="radio" name="wpc_filter_style" value="sidebar" <?php checked( $current_style, 'sidebar' ); ?> style="margin-bottom: 10px; display: block;" />
                <div style="border: 2px solid <?php echo ($current_style === 'sidebar' ? '#6366f1' : '#ddd'); ?>; border-radius: 8px; padding: 15px; background: #fff;">
                    <strong style="display: block; margin-bottom: 10px;">Vertical (Sidebar)</strong>
                    <div style="background: #f3f4f6; height: 100px; border-radius: 4px; display: flex; gap: 8px; padding: 10px;">
                        <div style="background: #fff; width: 30%; border: 1px solid #e5e7eb; border-radius: 4px;"></div>
                        <div style="display: flex; flex-direction: column; gap: 8px; flex: 1;">
                            <div style="background: #fff; height: 25px; width: 100%; border: 1px solid #e5e7eb; border-radius: 4px;"></div>
                            <div style="background: #fff; height: 25px; width: 100%; border: 1px solid #e5e7eb; border-radius: 4px;"></div>
                            <div style="background: #fff; height: 25px; width: 100%; border: 1px solid #e5e7eb; border-radius: 4px;"></div>
                        </div>
                    </div>
                    <p class="description" style="margin-top: 10px;">Filters appear in a sidebar on the left. Best for many features and categories.</p>
                </div>
            </label>
        </div>
        
        <style>
            input[type="radio"]:checked + div {
                border-color: #6366f1 !important;
                box-shadow: 0 0 0 1px #6366f1;
            }
        </style>
        
        <script>
        function setAllColors(primary, accent, secondary) {
            document.getElementById('wpc_primary_color').value = primary;
            document.getElementById('wpc_accent_color').value = accent;
            document.getElementById('wpc_secondary_color').value = secondary;
        }
        
        function resetButtonHover() {
            document.getElementById('wpc_button_hover_color').value = '';
            document.getElementById('wpc_button_hover_color_picker').value = '#059669';
        }
        </script>
        
        <?php submit_button(); ?>
    </form>
    
    <hr style="margin: 40px 0;">
    
    <div style="background: #f9fafb; padding: 20px; border-left: 4px solid #6366f1; border-radius: 4px;">
        <h3 style="margin-top: 0;"><?php _e( 'How to Use Featured Cards', 'wp-comparison-builder' ); ?></h3>
        <ol>
            <li><strong>In Custom Lists:</strong> Mark items as "Featured" and customize their badge text and border color individually</li>
            <li><strong>In Shortcodes:</strong> Use the <code>featured</code> attribute (e.g., <code>[wpc_compare featured="8,12"]</code>)</li>
            <li><strong>Per Item:</strong> Edit each item and set a custom "Featured Badge Text" (e.g., "Editor's Pick", "Best Value") and badge color</li>
        </ol>
        <p><em>Note: Featured card styling (badge and border color) is controlled per Custom List or per individual item, not globally.</em></p>
    </div>
    <?php
}

/**
 * Pricing Table Tab
 */
function wpc_render_pricing_tab() {
    ?>
    <form method="post" action="options.php">
        <?php settings_fields( 'wpc_settings_group' ); ?>
        
        <h2><?php _e( 'Pricing Table Visual Style', 'wp-comparison-builder' ); ?></h2>
        <p><?php _e( 'Customize the default appearance of the pricing table header and buttons (used when overrides are disabled).', 'wp-comparison-builder' ); ?></p>
        
        <table class="form-table">
            <!-- Header BG -->
            <tr valign="top">
                <th scope="row">
                    <label for="wpc_pt_header_bg"><?php _e( 'Header Background', 'wp-comparison-builder' ); ?></label>
                </th>
                <td>
                     <input type="color" name="wpc_pt_header_bg" value="<?php echo esc_attr( get_option( 'wpc_pt_header_bg', '#f8fafc' ) ); ?>" style="height:35px; cursor:pointer;" />
                </td>
            </tr>
            <!-- Header Text -->
            <tr valign="top">
                <th scope="row">
                    <label for="wpc_pt_header_text"><?php _e( 'Header Text Color', 'wp-comparison-builder' ); ?></label>
                </th>
                <td>
                     <input type="color" name="wpc_pt_header_text" value="<?php echo esc_attr( get_option( 'wpc_pt_header_text', '#0f172a' ) ); ?>" style="height:35px; cursor:pointer;" />
                </td>
            </tr>
             <!-- Button BG -->
             <tr valign="top">
                <th scope="row">
                    <label for="wpc_pt_btn_bg"><?php _e( 'Button Background', 'wp-comparison-builder' ); ?></label>
                </th>
                <td>
                     <input type="color" name="wpc_pt_btn_bg" value="<?php echo esc_attr( get_option( 'wpc_pt_btn_bg', '#0f172a' ) ); ?>" style="height:35px; cursor:pointer;" />
                </td>
            </tr>
             <!-- Button Text -->
             <tr valign="top">
                <th scope="row">
                    <label for="wpc_pt_btn_text"><?php _e( 'Button Text Color', 'wp-comparison-builder' ); ?></label>
                </th>
                <td>
                     <input type="color" name="wpc_pt_btn_text" value="<?php echo esc_attr( get_option( 'wpc_pt_btn_text', '#ffffff' ) ); ?>" style="height:35px; cursor:pointer;" />
                </td>
            </tr>
             <!-- Table Button Position -->
            <tr valign="top">
                <th scope="row"><label for="wpc_pt_btn_pos_table"><?php _e('Table Button Position', 'wp-comparison-builder'); ?></label></th>
                <td>
                    <select name="wpc_pt_btn_pos_table" id="wpc_pt_btn_pos_table">
                        <option value="after_price" <?php selected(get_option('wpc_pt_btn_pos_table', 'after_price'), 'after_price'); ?>>After Pricing</option>
                        <option value="bottom" <?php selected(get_option('wpc_pt_btn_pos_table', 'after_price'), 'bottom'); ?>>Bottom (After Features)</option>
                    </select>
                    <p class="description">Where to show the "Select Plan" button in inline tables.</p>
                </td>
            </tr>
             <!-- Popup Button Position -->
            <tr valign="top">
                <th scope="row"><label for="wpc_pt_btn_pos_popup"><?php _e('Popup Button Position', 'wp-comparison-builder'); ?></label></th>
                <td>
                    <select name="wpc_pt_btn_pos_popup" id="wpc_pt_btn_pos_popup">
                        <option value="after_price" <?php selected(get_option('wpc_pt_btn_pos_popup', 'after_price'), 'after_price'); ?>>After Pricing</option>
                        <option value="bottom" <?php selected(get_option('wpc_pt_btn_pos_popup', 'after_price'), 'bottom'); ?>>Bottom (After Features)</option>
                    </select>
                     <p class="description">Where to show the "Select Plan" button in popups.</p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
    <?php
}

/**
 * Import / Export Tab
 */
function wpc_render_import_export_tab() {
    ?>
    <div style="max-width: 900px;">
        <p><?php _e( 'Backup your data or migrate to another site. Export/Import includes Comparison Items, Categories, Features, Custom Lists, and Plugin Settings.', 'wp-comparison-builder' ); ?></p>
        
        <?php wp_nonce_field( 'wpc_import_export_nonce', 'wpc_ie_nonce' ); ?>
        
        <!-- Export Section -->
        <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px; margin-bottom: 20px;">
            <h3 style="margin-top: 0;"><?php _e( 'Export Data', 'wp-comparison-builder' ); ?></h3>
            <p><?php _e( 'Download a JSON file containing selected plugin data.', 'wp-comparison-builder' ); ?></p>
            
            <!-- What to Export -->
            <div style="margin-bottom: 15px; padding: 15px; background: #f9f9f9; border-radius: 4px;">
                <strong style="display: block; margin-bottom: 10px;"><?php _e( 'What to Export:', 'wp-comparison-builder' ); ?></strong>
                <label style="display: inline-block; margin-right: 15px; margin-bottom: 5px;">
                    <input type="checkbox" id="wpc-export-all" checked /> <strong>Select All</strong>
                </label>
                <div style="margin-top: 10px; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px;">
                    <label style="display: flex; align-items: center; gap: 5px;">
                        <input type="checkbox" class="wpc-export-opt" data-type="items" checked /> Comparison Items
                    </label>
                    <label style="display: flex; align-items: center; gap: 5px;">
                        <input type="checkbox" class="wpc-export-opt" data-type="categories" checked /> Categories
                    </label>
                    <label style="display: flex; align-items: center; gap: 5px;">
                        <input type="checkbox" class="wpc-export-opt" data-type="features" checked /> Features
                    </label>
                    <label style="display: flex; align-items: center; gap: 5px;">
                        <input type="checkbox" class="wpc-export-opt" data-type="lists" checked /> Custom Lists
                    </label>
                    <label style="display: flex; align-items: center; gap: 5px;">
                        <input type="checkbox" class="wpc-export-opt" data-type="settings" checked /> Settings
                    </label>
                </div>
            </div>
            
            <button type="button" id="wpc-export-btn" class="button button-primary">
                <?php _e( 'Download Export File', 'wp-comparison-builder' ); ?>
            </button>
            <span id="wpc-export-status" style="margin-left: 10px;"></span>
        </div>
        
        <!-- Import Section -->
        <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px; margin-bottom: 20px;">
            <h3 style="margin-top: 0;"><?php _e( 'Import Data', 'wp-comparison-builder' ); ?></h3>
            <p><?php _e( 'Upload a JSON file. You can control what gets imported below.', 'wp-comparison-builder' ); ?></p>
            
            <div style="margin-bottom: 15px;">
                <input type="file" id="wpc-import-file" accept=".json" />
            </div>
            
            <!-- What to Import -->
            <div style="margin-bottom: 15px; padding: 15px; background: #f9f9f9; border-radius: 4px;">
                <strong style="display: block; margin-bottom: 10px;"><?php _e( 'What to Import:', 'wp-comparison-builder' ); ?></strong>
                <label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox" id="wpc-import-items" checked /> Comparison Items
                </label>
                <label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox" id="wpc-import-categories" checked /> Categories
                </label>
                <label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox" id="wpc-import-features" checked /> Features
                </label>
                <label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox" id="wpc-import-lists" checked /> Custom Lists
                </label>
                <label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox" id="wpc-import-settings" /> Settings (Colors, Layout, etc.)
                </label>
            </div>
            
            <button type="button" id="wpc-import-btn" class="button button-primary" disabled>
                <?php _e( 'Preview Import', 'wp-comparison-builder' ); ?>
            </button>
            <span id="wpc-import-status" style="margin-left: 10px;"></span>
        </div>
        
        <!-- Sample JSON -->
        <div style="background: #f0f6ff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px;">
            <h3 style="margin-top: 0;"><?php _e( 'Sample JSON Template', 'wp-comparison-builder' ); ?></h3>
            <p><?php _e( 'Download a comprehensive sample showing all supported fields.', 'wp-comparison-builder' ); ?></p>
            <button type="button" id="wpc-sample-btn" class="button">
                <?php _e( 'Download Sample JSON', 'wp-comparison-builder' ); ?>
            </button>
        </div>
    </div>
    
    <!-- Conflict Resolution Modal -->
    <div id="wpc-conflict-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 99999; align-items: center; justify-content: center;">
        <div style="background: #fff; padding: 30px; border-radius: 8px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <h2 style="margin-top: 0;"><?php _e( 'Import Preview', 'wp-comparison-builder' ); ?></h2>
            
            <div id="wpc-import-summary" style="margin-bottom: 20px; padding: 15px; background: #f0f9ff; border-radius: 4px;"></div>
            
            <div id="wpc-conflicts-section" style="display: none;">
                <h4 style="color: #e65100;"><?php _e( 'Conflicts Found', 'wp-comparison-builder' ); ?></h4>
                <p><?php _e( 'The following items already exist. Select which to overwrite:', 'wp-comparison-builder' ); ?></p>
                
                <label style="display: block; margin-bottom: 10px; padding: 10px; background: #fff3e0; border-radius: 4px;">
                    <input type="checkbox" id="wpc-override-all" /> <strong><?php _e( 'Override All Without Asking', 'wp-comparison-builder' ); ?></strong>
                </label>
                
                <div id="wpc-conflicts-list" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;"></div>
            </div>
            
            <div style="margin-top: 20px; text-align: right;">
                <button type="button" id="wpc-modal-cancel" class="button"><?php _e( 'Cancel', 'wp-comparison-builder' ); ?></button>
                <button type="button" id="wpc-modal-confirm" class="button button-primary" style="margin-left: 10px;"><?php _e( 'Continue Import', 'wp-comparison-builder' ); ?></button>
            </div>
        </div>
    </div>
    
    <script>
    (function() {
        const nonce = document.getElementById('wpc_ie_nonce').value;
        let pendingJsonData = null;
        let detectedConflicts = [];
        
        // Export Select All
        document.getElementById('wpc-export-all').addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('.wpc-export-opt').forEach(cb => cb.checked = isChecked);
        });
        
        // Update Export Select All when individual options change
        document.querySelectorAll('.wpc-export-opt').forEach(cb => {
            cb.addEventListener('change', function() {
                const allOpts = document.querySelectorAll('.wpc-export-opt');
                const checkedOpts = document.querySelectorAll('.wpc-export-opt:checked');
                document.getElementById('wpc-export-all').checked = allOpts.length === checkedOpts.length;
            });
        });
        
        // Export
        document.getElementById('wpc-export-btn').addEventListener('click', function() {
            const statusEl = document.getElementById('wpc-export-status');
            
            // Get selected export options
            const selectedOpts = [];
            document.querySelectorAll('.wpc-export-opt:checked').forEach(cb => {
                selectedOpts.push(cb.getAttribute('data-type'));
            });
            
            if (selectedOpts.length === 0) {
                statusEl.textContent = '⚠️ Select at least one option to export';
                statusEl.style.color = '#dc2626';
                return;
            }
            
            statusEl.textContent = 'Exporting...';
            statusEl.style.color = '';
            
            const formData = new FormData();
            formData.append('action', 'wpc_export_data');
            formData.append('nonce', nonce);
            formData.append('export_types', JSON.stringify(selectedOpts));
            
            fetch(ajaxurl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const blob = new Blob([JSON.stringify(data.data, null, 2)], { type: 'application/json' });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'wpc-export-' + new Date().toISOString().slice(0,10) + '.json';
                        a.click();
                        URL.revokeObjectURL(url);
                        statusEl.textContent = '✓ Downloaded!';
                        statusEl.style.color = '#16a34a';
                    } else {
                        statusEl.textContent = '✗ Error: ' + data.data;
                        statusEl.style.color = '#dc2626';
                    }
                })
                .catch(e => { 
                    statusEl.textContent = '✗ Request failed'; 
                    statusEl.style.color = '#dc2626';
                });
        });
        
        // Import file selection
        document.getElementById('wpc-import-file').addEventListener('change', function() {
            document.getElementById('wpc-import-btn').disabled = !this.files.length;
        });
        
        // Import Preview (detect conflicts)
        document.getElementById('wpc-import-btn').addEventListener('click', function() {
            const fileInput = document.getElementById('wpc-import-file');
            const statusEl = document.getElementById('wpc-import-status');
            
            if (!fileInput.files.length) {
                statusEl.textContent = 'Please select a file';
                return;
            }
            
            statusEl.textContent = 'Analyzing...';
            
            const reader = new FileReader();
            reader.onload = function(e) {
                pendingJsonData = e.target.result;
                
                const formData = new FormData();
                formData.append('action', 'wpc_detect_conflicts');
                formData.append('nonce', nonce);
                formData.append('json_data', pendingJsonData);
                
                fetch(ajaxurl, { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            statusEl.textContent = '';
                            showConflictModal(data.data);
                        } else {
                            statusEl.textContent = '✗ Error: ' + data.data;
                        }
                    })
                    .catch(e => { statusEl.textContent = '✗ Analysis failed'; });
            };
            reader.readAsText(fileInput.files[0]);
        });
        
        function showConflictModal(result) {
            detectedConflicts = result.conflicts || [];
            const summary = result.summary || {};
            
            // Build summary
            let summaryHtml = '<strong>Import Summary:</strong><br>';
            summaryHtml += `• Categories: ${summary.categories_count || 0}<br>`;
            summaryHtml += `• Features: ${summary.features_count || 0}<br>`;
            summaryHtml += `• Items: ${summary.items_count || 0}<br>`;
            summaryHtml += `• Lists: ${summary.lists_count || 0}`;
            document.getElementById('wpc-import-summary').innerHTML = summaryHtml;
            
            // Build conflicts
            const conflictsSection = document.getElementById('wpc-conflicts-section');
            const conflictsList = document.getElementById('wpc-conflicts-list');
            
            if (detectedConflicts.length > 0) {
                conflictsSection.style.display = 'block';
                let html = '';
                detectedConflicts.forEach((c, i) => {
                    html += `<label style="display: block; padding: 5px 0;"><input type="checkbox" class="wpc-conflict-cb" data-slug="${c.slug}" checked /> ${c.type === 'item' ? '📦' : '📋'} ${c.title} <span style="color: #888;">(${c.slug})</span></label>`;
                });
                conflictsList.innerHTML = html;
            } else {
                conflictsSection.style.display = 'none';
            }
            
            document.getElementById('wpc-override-all').checked = false;
            document.getElementById('wpc-conflict-modal').style.display = 'flex';
        }
        
        // Override all toggle
        document.getElementById('wpc-override-all').addEventListener('change', function() {
            const checked = this.checked;
            document.querySelectorAll('.wpc-conflict-cb').forEach(cb => {
                cb.checked = checked;
                cb.disabled = checked;
            });
        });
        
        // Modal Cancel
        document.getElementById('wpc-modal-cancel').addEventListener('click', function() {
            document.getElementById('wpc-conflict-modal').style.display = 'none';
            pendingJsonData = null;
        });
        
        // Modal Confirm Import
        document.getElementById('wpc-modal-confirm').addEventListener('click', function() {
            const statusEl = document.getElementById('wpc-import-status');
            const modal = document.getElementById('wpc-conflict-modal');
            
            // Collect which to override
            const overrideSlugs = [];
            document.querySelectorAll('.wpc-conflict-cb:checked').forEach(cb => {
                overrideSlugs.push(cb.dataset.slug);
            });
            
            const formData = new FormData();
            formData.append('action', 'wpc_import_data');
            formData.append('nonce', nonce);
            formData.append('json_data', pendingJsonData);
            formData.append('overwrite', overrideSlugs.length > 0 ? 'true' : 'false');
            formData.append('override_slugs', JSON.stringify(overrideSlugs));
            formData.append('import_items', document.getElementById('wpc-import-items').checked ? 'true' : 'false');
            formData.append('import_lists', document.getElementById('wpc-import-lists').checked ? 'true' : 'false');
            formData.append('import_settings', document.getElementById('wpc-import-settings').checked ? 'true' : 'false');
            formData.append('import_categories', document.getElementById('wpc-import-categories').checked ? 'true' : 'false');
            formData.append('import_features', document.getElementById('wpc-import-features').checked ? 'true' : 'false');
            
            modal.style.display = 'none';
            statusEl.textContent = 'Importing...';
            
            fetch(ajaxurl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const r = data.data;
                        let msg = '✓ Import complete!<br>';
                        if (r.items_created || r.items_updated) msg += `Items: ${r.items_created || 0} created, ${r.items_updated || 0} updated<br>`;
                        if (r.lists_created || r.lists_updated) msg += `Lists: ${r.lists_created || 0} created, ${r.lists_updated || 0} updated<br>`;
                        if (r.categories_created) msg += `Categories: ${r.categories_created} created<br>`;
                        if (r.features_created) msg += `Features: ${r.features_created} created<br>`;
                        if (r.settings_updated) msg += `Settings: ${r.settings_updated} updated`;
                        statusEl.innerHTML = msg;
                    } else {
                        statusEl.textContent = '✗ Error: ' + data.data;
                    }
                    pendingJsonData = null;
                })
                .catch(e => { statusEl.textContent = '✗ Import failed'; });
        });
        
        // Sample JSON
        document.getElementById('wpc-sample-btn').addEventListener('click', function() {
            const sample = {
                "version": "1.0",
                "categories": [
                    { "name": "Web Hosting", "slug": "web-hosting", "description": "General web hosting services" },
                    { "name": "E-commerce", "slug": "ecommerce", "description": "E-commerce platforms" }
                ],
                "features": [
                    { "name": "Free SSL", "slug": "free-ssl", "description": "" },
                    { "name": "24/7 Support", "slug": "247-support", "description": "" },
                    { "name": "Free Domain", "slug": "free-domain", "description": "" }
                ],
                "comparison_items": [
                    {
                        "post_title": "Sample Platform",
                        "post_name": "sample-platform",
                        "post_status": "publish",
                        "post_content": "",
                        "meta": {
                            "_wpc_website_url": "https://example.com",
                            "_wpc_short_description": "A sample platform showing all available fields",
                            "_wpc_rating": "4.5",
                            "_wpc_price": "$29",
                            "_wpc_price_period": "/mo",
                            "_wpc_external_logo_url": "",
                            "_wpc_details_link": "https://example.com/review",
                            "_wpc_direct_link": "https://example.com/go",
                            "_wpc_button_text": "Visit Website",
                            "_wpc_featured_badge_text": "Editor's Pick",
                            "_wpc_featured_badge_color": "#6366f1",
                            "_wpc_coupon_code": "SAVE20",
                            "_wpc_coupon_label": "Get 20% Off",
                            "_wpc_show_coupon": "1",
                            "_wpc_pros": "Pro item 1\nPro item 2\nPro item 3",
                            "_wpc_cons": "Con item 1\nCon item 2",
                            "_wpc_pricing_plans": [
                                {
                                    "name": "Basic",
                                    "price": "$9.99",
                                    "period": "/mo",
                                    "features": "Feature 1\nFeature 2\nFeature 3",
                                    "link": "https://example.com/basic",
                                    "button_text": "Get Started",
                                    "show_popup": "1",
                                    "show_table": "1",
                                    "show_banner": "0",
                                    "banner_text": "",
                                    "banner_color": "#10b981"
                                },
                                {
                                    "name": "Professional",
                                    "price": "$29.99",
                                    "period": "/mo",
                                    "features": "All Basic features\nPriority Support\nAdvanced Analytics",
                                    "link": "https://example.com/pro",
                                    "button_text": "Choose Pro",
                                    "show_popup": "1",
                                    "show_table": "1",
                                    "show_banner": "1",
                                    "banner_text": "MOST POPULAR",
                                    "banner_color": "#10b981"
                                },
                                {
                                    "name": "Enterprise",
                                    "price": "$99.99",
                                    "period": "/mo",
                                    "features": "All Pro features\nDedicated Support\nCustom Solutions",
                                    "link": "https://example.com/enterprise",
                                    "button_text": "Contact Sales",
                                    "show_popup": "1",
                                    "show_table": "1",
                                    "show_banner": "1",
                                    "banner_text": "BEST VALUE",
                                    "banner_color": "#f59e0b"
                                }
                            ]
                        },
                        "categories": ["web-hosting", "ecommerce"],
                        "features": ["free-ssl", "247-support", "free-domain"]
                    }
                ],
                "custom_lists": [
                    {
                        "post_title": "Sample Custom List",
                        "post_name": "sample-custom-list",
                        "post_status": "publish",
                        "meta": {
                            "_wpc_list_items": [],
                            "_wpc_enable_comparison": "1",
                            "_wpc_show_filters": "1",
                            "_wpc_initial_visible_count": "6",
                            "_wpc_filter_layout": "top"
                        }
                    }
                ],
                "settings": {
                    "wpc_primary_color": "#6366f1",
                    "wpc_accent_color": "#0d9488",
                    "wpc_secondary_color": "#1e293b",
                    "wpc_card_border_color": "#e2e8f0",
                    "wpc_pricing_banner_color": "#10b981"
                }
            };
            
            const blob = new Blob([JSON.stringify(sample, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'wpc-sample-import.json';
            a.click();
            URL.revokeObjectURL(url);
        });
        

    })();
    </script>
    <?php
}

/**
 * JSON Schema Tab
 */
function wpc_render_json_schema_tab() {
    ?>
    <div style="max-width: 900px;">
        <h2><?php _e( 'JSON Schema Reference', 'wp-comparison-builder' ); ?></h2>
        <p><?php _e( 'This page documents the JSON structure used for import/export operations.', 'wp-comparison-builder' ); ?></p>
        
        <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px; margin-bottom: 20px;">
            <h3 style="margin-top: 0;"><?php _e( 'Root Structure', 'wp-comparison-builder' ); ?></h3>
            <pre style="background: #f6f7f7; padding: 15px; border-radius: 4px; overflow-x: auto;">{
  "version": "1.0",
  "categories": [...],
  "features": [...],
  "comparison_items": [...],
  "custom_lists": [...],
  "settings": {...}
}</pre>
        </div>
        
        <!-- Complete Example -->
        <div style="background: #e8f5e9; border: 1px solid #4caf50; border-radius: 4px; padding: 20px; margin-bottom: 20px;">
            <h3 style="margin-top: 0; color: #2e7d32;"><?php _e( 'Complete Import Example', 'wp-comparison-builder' ); ?></h3>
            <p>Copy this JSON to import multiple categories, features, and items at once:</p>
            <textarea id="wpc-complete-json" readonly style="width: 100%; height: 400px; font-family: monospace; font-size: 12px;">{
  "version": "1.0",
  "categories": [
    { "name": "All-in-One Platform", "slug": "all-in-one-platform", "description": "Complete ecommerce solutions" },
    { "name": "Open Source", "slug": "open-source", "description": "Free and open-source platforms" },
    { "name": "Enterprise", "slug": "enterprise", "description": "Solutions for large businesses" },
    { "name": "WordPress Plugin", "slug": "wordpress-plugin", "description": "WooCommerce and other WP solutions" }
  ],
  "features": [
    { "name": "24/7 Support", "slug": "247-support", "description": "" },
    { "name": "Free SSL", "slug": "free-ssl", "description": "" },
    { "name": "Multi-Currency", "slug": "multi-currency", "description": "" },
    { "name": "Abandoned Cart", "slug": "abandoned-cart", "description": "" },
    { "name": "SEO Tools", "slug": "seo-tools", "description": "" },
    { "name": "Dropshipping", "slug": "dropshipping", "description": "" }
  ],
  "comparison_items": [
    {
      "post_title": "Shopify",
      "post_name": "shopify",
      "post_status": "publish",
      "meta": {
        "_wpc_website_url": "https://shopify.com",
        "_wpc_short_description": "Leading ecommerce platform for online stores",
        "_wpc_rating": "4.8",
        "_wpc_price": "$29",
        "_wpc_price_period": "/mo",
        "_wpc_featured_badge_text": "Top Choice",
        "_wpc_featured_badge_color": "#96bf48",
        "_wpc_coupon_code": "SAVE10",
        "_wpc_coupon_label": "Get 10% Off",
        "_wpc_pros": ["Easy to use", "Great themes", "24/7 support"],
        "_wpc_cons": ["Transaction fees", "Limited customization"],
        "_wpc_pricing_plans": [
          {"name": "Basic", "price": "$29", "period": "/mo", "features": ["Online store", "Unlimited products", "24/7 support"], "cta_text": "Start Free Trial", "cta_url": "https://shopify.com/basic"},
          {"name": "Shopify", "price": "$79", "period": "/mo", "features": ["Everything in Basic", "5 staff accounts", "Professional reports"], "cta_text": "Start Free Trial", "cta_url": "https://shopify.com/standard", "is_popular": true},
          {"name": "Advanced", "price": "$299", "period": "/mo", "features": ["Everything in Shopify", "15 staff accounts", "Advanced reports"], "cta_text": "Start Free Trial", "cta_url": "https://shopify.com/advanced"}
        ]
      },
      "categories": ["all-in-one-platform"],
      "features": ["247-support", "free-ssl", "abandoned-cart"]
    },
    {
      "post_title": "WooCommerce",
      "post_name": "woocommerce",
      "post_status": "publish",
      "meta": {
        "_wpc_website_url": "https://woocommerce.com",
        "_wpc_short_description": "The most customizable ecommerce platform",
        "_wpc_rating": "4.6",
        "_wpc_price": "Free",
        "_wpc_price_period": "",
        "_wpc_featured_badge_text": "",
        "_wpc_pros": ["Free to use", "Highly customizable", "Large community"],
        "_wpc_cons": ["Requires hosting", "Steeper learning curve"]
      },
      "categories": ["open-source", "wordpress-plugin"],
      "features": ["multi-currency", "seo-tools", "dropshipping"]
    },
    {
      "post_title": "BigCommerce",
      "post_name": "bigcommerce",
      "post_status": "publish",
      "meta": {
        "_wpc_website_url": "https://bigcommerce.com",
        "_wpc_short_description": "Enterprise-grade ecommerce for growth",
        "_wpc_rating": "4.5",
        "_wpc_price": "$29",
        "_wpc_price_period": "/mo",
        "_wpc_coupon_code": "BC15OFF",
        "_wpc_coupon_label": "15% Discount"
      },
      "categories": ["all-in-one-platform", "enterprise"],
      "features": ["247-support", "multi-currency", "seo-tools"]
    }
  ],
  "settings": {
    "wpc_primary_color": "#6366f1",
    "wpc_accent_color": "#0d9488",
    "wpc_secondary_color": "#1e293b"
  }
}</textarea>
            <button type="button" class="button" onclick="navigator.clipboard.writeText(document.getElementById('wpc-complete-json').value); this.textContent='Copied!'; setTimeout(()=>this.textContent='Copy JSON', 2000);" style="margin-top: 10px;">Copy JSON</button>
            <button type="button" class="button button-primary" onclick="var a=document.createElement('a');a.href=URL.createObjectURL(new Blob([document.getElementById('wpc-complete-json').value],{type:'application/json'}));a.download='wpc-complete-import.json';a.click();" style="margin-top: 10px; margin-left: 5px;">Download as File</button>
        </div>
        
        <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px; margin-bottom: 20px;">
            <h3 style="margin-top: 0;"><?php _e( 'Comparison Item Structure', 'wp-comparison-builder' ); ?></h3>
            <pre style="background: #f6f7f7; padding: 15px; border-radius: 4px; overflow-x: auto;">{
  "post_title": "Platform Name",
  "post_name": "platform-slug",
  "post_status": "publish",
  "post_content": "",
  "meta": {
    "_wpc_website_url": "https://example.com",
    "_wpc_short_description": "Brief description",
    "_wpc_rating": "4.5",
    "_wpc_featured_badge_text": "Editor's Pick",
    "_wpc_featured_badge_color": "#6366f1",
    "_wpc_pricing_plans": [...],
    "_wpc_pros": [...],
    "_wpc_cons": [...]
  },
  "categories": ["category-slug-1", "category-slug-2"],
  "features": ["feature-slug-1", "feature-slug-2"]
}</pre>
        </div>
        
        <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px; margin-bottom: 20px;">
            <h3 style="margin-top: 0;"><?php _e( 'Pricing Plan Structure', 'wp-comparison-builder' ); ?></h3>
            <pre style="background: #f6f7f7; padding: 15px; border-radius: 4px; overflow-x: auto;">{
  "name": "Basic Plan",
  "price": "$9.99",
  "period": "/month",
  "features": "Feature 1\nFeature 2",
  "link": "https://example.com/signup",
  "button_text": "Get Started",
  "show_popup": "1",
  "show_table": "1",
  "show_banner": "1",
  "banner_text": "50% OFF",
  "banner_color": "#10b981"
}</pre>
            <p class="description" style="margin-top: 10px;">
                <strong>Note:</strong> Legacy field names <code>cta_url</code> and <code>cta_text</code> are also supported and will be automatically mapped to <code>link</code> and <code>button_text</code>.
            </p>
        </div>
        
        <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px;">
            <h3 style="margin-top: 0;"><?php _e( 'Available Meta Keys', 'wp-comparison-builder' ); ?></h3>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e( 'Meta Key', 'wp-comparison-builder' ); ?></th>
                        <th><?php _e( 'Description', 'wp-comparison-builder' ); ?></th>
                        <th><?php _e( 'Type', 'wp-comparison-builder' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td><code>_wpc_website_url</code></td><td>Main website URL</td><td>string</td></tr>
                    <tr><td><code>_wpc_short_description</code></td><td>Brief description</td><td>string</td></tr>
                    <tr><td><code>_wpc_rating</code></td><td>Rating (0-5)</td><td>number</td></tr>
                    <tr><td><code>_wpc_pricing_plans</code></td><td>Array of pricing plans</td><td>array</td></tr>
                    <tr><td><code>_wpc_pros</code></td><td>List of pros (array or newline-separated string)</td><td>array/string</td></tr>
                    <tr><td><code>_wpc_cons</code></td><td>List of cons (array or newline-separated string)</td><td>array/string</td></tr>
                    <tr><td><code>_wpc_featured_badge_text</code></td><td>Featured badge text</td><td>string</td></tr>
                    <tr><td><code>_wpc_featured_badge_color</code></td><td>Featured badge color (hex)</td><td>string</td></tr>
                    <tr><td><code>_wpc_competitors</code></td><td>Default competitor IDs</td><td>array</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

/**
 * Danger Zone Tab
 */
function wpc_render_danger_zone_tab() {
    ?>
    <div style="max-width: 800px;">
        <?php wp_nonce_field( 'wpc_import_export_nonce', 'wpc_ie_nonce' ); ?>
        
        <!-- Spinner Style -->
        <style>
            .wpc-spinner {
                display: inline-block;
                width: 16px;
                height: 16px;
                border: 2px solid #e2e8f0;
                border-top-color: #0284c7;
                border-radius: 50%;
                animation: wpc-spin 0.8s linear infinite;
                margin-right: 8px;
                vertical-align: middle;
            }
            @keyframes wpc-spin {
                to { transform: rotate(360deg); }
            }
            .wpc-tool-status {
                margin-top: 12px;
                padding: 10px 12px;
                border-radius: 4px;
                font-size: 13px;
                display: none;
            }
            .wpc-tool-status.success {
                background: #f0fdf4;
                border: 1px solid #86efac;
                color: #166534;
            }
            .wpc-tool-status.error {
                background: #fef2f2;
                border: 1px solid #fca5a5;
                color: #dc2626;
            }
            .wpc-tool-status.loading {
                background: #f0f9ff;
                border: 1px solid #bae6fd;
                color: #0369a1;
            }
        </style>
        
        <!-- Maintenance Tools Section -->
        <div style="background: #f0f9ff; border: 2px solid #0284c7; border-radius: 8px; padding: 30px; margin-bottom: 30px;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                <span style="font-size: 40px;">🔧</span>
                <div>
                    <h2 style="margin: 0; color: #0369a1;"><?php _e( 'Maintenance Tools', 'wp-comparison-builder' ); ?></h2>
                    <p style="margin: 5px 0 0 0; color: #0c4a6e;">Safe utilities to maintain and troubleshoot your data.</p>
                </div>
            </div>
            
            <!-- Clear Transients -->
            <div style="background: #fff; border: 1px solid #bae6fd; border-radius: 6px; padding: 20px; margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h3 style="margin: 0 0 5px 0; color: #0369a1;"><?php _e( 'Clear Plugin Cache', 'wp-comparison-builder' ); ?></h3>
                        <p style="margin: 0; color: #6b7280; font-size: 13px;">
                            Remove all cached data and transients. Useful if you see stale or incorrect data.
                        </p>
                    </div>
                    <button type="button" id="wpc-clear-cache-btn" class="button button-secondary" style="white-space: nowrap;">
                        <?php _e( 'Clear Cache', 'wp-comparison-builder' ); ?>
                    </button>
                </div>
                <div id="wpc-cache-status" class="wpc-tool-status"></div>
            </div>
            
            <!-- Fix Orphaned Data -->
            <div style="background: #fff; border: 1px solid #bae6fd; border-radius: 6px; padding: 20px; margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h3 style="margin: 0 0 5px 0; color: #0369a1;"><?php _e( 'Clean Orphaned Data', 'wp-comparison-builder' ); ?></h3>
                        <p style="margin: 0; color: #6b7280; font-size: 13px;">
                            Remove leftover meta data from deleted items. Keeps your database clean.
                        </p>
                    </div>
                    <button type="button" id="wpc-orphan-btn" class="button button-secondary" style="white-space: nowrap;">
                        <?php _e( 'Clean Up', 'wp-comparison-builder' ); ?>
                    </button>
                </div>
                <div id="wpc-orphan-status" class="wpc-tool-status"></div>
            </div>
            
            <!-- Rebuild Term Counts -->
            <div style="background: #fff; border: 1px solid #bae6fd; border-radius: 6px; padding: 20px; margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h3 style="margin: 0 0 5px 0; color: #0369a1;"><?php _e( 'Rebuild Term Counts', 'wp-comparison-builder' ); ?></h3>
                        <p style="margin: 0; color: #6b7280; font-size: 13px;">
                            Recalculate category and feature item counts. Fixes incorrect numbers.
                        </p>
                    </div>
                    <button type="button" id="wpc-recount-btn" class="button button-secondary" style="white-space: nowrap;">
                        <?php _e( 'Rebuild Counts', 'wp-comparison-builder' ); ?>
                    </button>
                </div>
                <div id="wpc-recount-status" class="wpc-tool-status"></div>
            </div>
            
            <!-- Data Integrity Check -->
            <div style="background: #fff; border: 1px solid #bae6fd; border-radius: 6px; padding: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h3 style="margin: 0 0 5px 0; color: #0369a1;"><?php _e( 'Check Data Integrity', 'wp-comparison-builder' ); ?></h3>
                        <p style="margin: 0; color: #6b7280; font-size: 13px;">
                            Scan all items and identify potential issues (missing fields, broken links, etc.).
                        </p>
                    </div>
                    <button type="button" id="wpc-integrity-btn" class="button button-secondary" style="white-space: nowrap;">
                        <?php _e( 'Run Check', 'wp-comparison-builder' ); ?>
                    </button>
                </div>
                <div id="wpc-integrity-status" class="wpc-tool-status"></div>
            </div>
        </div>
        
        <!-- Danger Zone Section -->
        <div style="background: #fef2f2; border: 2px solid #dc2626; border-radius: 8px; padding: 30px; margin-bottom: 30px;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                <span style="font-size: 40px;">⚠️</span>
                <div>
                    <h2 style="margin: 0; color: #dc2626;"><?php _e( 'Danger Zone', 'wp-comparison-builder' ); ?></h2>
                    <p style="margin: 5px 0 0 0; color: #991b1b;">These actions can cause data loss and cannot be undone.</p>
                </div>
            </div>
            
            <!-- Reset Settings -->
            <div style="background: #fff; border: 1px solid #fca5a5; border-radius: 6px; padding: 20px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h3 style="margin: 0 0 5px 0; color: #b91c1c;"><?php _e( 'Reset Settings to Default', 'wp-comparison-builder' ); ?></h3>
                        <p style="margin: 0; color: #6b7280; font-size: 13px;">
                            Select which settings to reset. Your items, lists, categories, and features will NOT be affected.
                        </p>
                    </div>
                    <button type="button" id="wpc-reset-expand-btn" class="button" style="border-color: #f97316; color: #ea580c; white-space: nowrap;">
                        <?php _e( 'Reset Settings', 'wp-comparison-builder' ); ?>
                    </button>
                </div>
                
                <!-- Inline Confirmation Panel -->
                <div id="wpc-reset-panel" style="display: none; margin-top: 15px; padding: 15px; background: #fef3c7; border: 1px solid #fbbf24; border-radius: 6px;">
                    <h4 style="margin: 0 0 10px 0; color: #92400e;">Select settings to reset:</h4>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px; margin-bottom: 15px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" id="wpc-reset-all" checked>
                            <strong>Select All</strong>
                        </label>
                    </div>
                    
                    <div id="wpc-reset-options" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px; margin-bottom: 15px; padding: 10px; background: #fff; border-radius: 4px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="reset_option" value="colors" checked class="wpc-reset-opt">
                            Colors (Primary, Accent, etc.)
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="reset_option" value="filter" checked class="wpc-reset-opt">
                            Filter Style
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="reset_option" value="card" checked class="wpc-reset-opt">
                            Card Layout
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="reset_option" value="pricing" checked class="wpc-reset-opt">
                            Pricing Table Settings
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="reset_option" value="comparison" checked class="wpc-reset-opt">
                            Comparison Popup Settings
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="reset_option" value="schema" checked class="wpc-reset-opt">
                            Schema SEO Settings
                        </label>
                    </div>
                    
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <button type="button" id="wpc-reset-confirm-btn" class="button" style="background: #f97316; border-color: #f97316; color: #fff;">
                            Confirm Reset
                        </button>
                        <button type="button" id="wpc-reset-cancel-btn" class="button">
                            Cancel
                        </button>
                    </div>
                </div>
                
                <div id="wpc-reset-status" class="wpc-tool-status"></div>
            </div>
            
            <!-- Delete All Data -->
            <div style="background: #fff; border: 2px solid #dc2626; border-radius: 6px; padding: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h3 style="margin: 0 0 5px 0; color: #dc2626;"><?php _e( 'Delete Data', 'wp-comparison-builder' ); ?></h3>
                        <p style="margin: 0; color: #6b7280; font-size: 13px;">
                            Select what to delete. <strong style="color: #dc2626;">This action cannot be undone!</strong>
                        </p>
                    </div>
                    <button type="button" id="wpc-delete-expand-btn" class="button" style="background: #dc2626; border-color: #dc2626; color: #fff; white-space: nowrap;">
                        <?php _e( 'Delete Data', 'wp-comparison-builder' ); ?>
                    </button>
                </div>
                
                <!-- Inline Confirmation Panel -->
                <div id="wpc-delete-panel" style="display: none; margin-top: 15px; padding: 15px; background: #fef2f2; border: 1px solid #fca5a5; border-radius: 6px;">
                    <h4 style="margin: 0 0 10px 0; color: #dc2626;">⚠️ Critical: Select what to delete:</h4>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px; margin-bottom: 15px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" id="wpc-delete-all">
                            <strong>Select All</strong>
                        </label>
                    </div>
                    
                    <div id="wpc-delete-options" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px; margin-bottom: 15px; padding: 10px; background: #fff; border-radius: 4px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="delete_option" value="items" class="wpc-delete-opt">
                            All Comparison Items
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="delete_option" value="lists" class="wpc-delete-opt">
                            All Custom Lists
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="delete_option" value="categories" class="wpc-delete-opt">
                            All Categories
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="delete_option" value="features" class="wpc-delete-opt">
                            All Features
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="delete_option" value="settings" class="wpc-delete-opt">
                            All Settings
                        </label>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; color: #991b1b; font-weight: bold;">Type "DELETE" to confirm:</label>
                        <input type="text" id="wpc-delete-confirm-text" placeholder="Type DELETE here" style="width: 200px; padding: 8px; border: 2px solid #dc2626; border-radius: 4px;">
                    </div>
                    
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <button type="button" id="wpc-delete-confirm-btn" class="button" style="background: #dc2626; border-color: #dc2626; color: #fff;">
                            Confirm Delete
                        </button>
                        <button type="button" id="wpc-delete-cancel-btn" class="button">
                            Cancel
                        </button>
                    </div>
                </div>
                
                <div id="wpc-delete-status" class="wpc-tool-status"></div>
            </div>
        </div>
        
        <!-- Tips Section -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px;">
            <h3 style="margin: 0 0 15px 0; color: #334155;">💡 Tips</h3>
            <ul style="margin: 0; padding-left: 20px; color: #64748b;">
                <li><strong>Maintenance Tools</strong> are safe to run anytime – they don't delete your content</li>
                <li><strong>Export your data first</strong> before using Danger Zone options</li>
                <li>Run <strong>Data Integrity Check</strong> periodically to catch potential issues early</li>
            </ul>
        </div>
    </div>
    
    <script>
    (function() {
        const nonce = document.getElementById('wpc_ie_nonce').value;
        
        function showStatus(elementId, message, type = 'loading') {
            const el = document.getElementById(elementId);
            el.className = 'wpc-tool-status ' + type;
            el.innerHTML = message;
            el.style.display = 'block';
        }
        
        function showLoading(elementId, message) {
            showStatus(elementId, '<span class="wpc-spinner"></span>' + message, 'loading');
        }
        
        // Clear Cache
        document.getElementById('wpc-clear-cache-btn').addEventListener('click', function() {
            const btn = this;
            btn.disabled = true;
            showLoading('wpc-cache-status', 'Clearing cache...');
            
            const formData = new FormData();
            formData.append('action', 'wpc_clear_cache');
            formData.append('nonce', nonce);
            
            fetch(ajaxurl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    if (data.success) {
                        showStatus('wpc-cache-status', '✓ Cache cleared! ' + data.data.message, 'success');
                    } else {
                        showStatus('wpc-cache-status', '✗ Error: ' + data.data, 'error');
                    }
                })
                .catch(e => { 
                    btn.disabled = false;
                    showStatus('wpc-cache-status', '✗ Operation failed', 'error'); 
                });
        });
        
        // Clean Orphaned Data
        document.getElementById('wpc-orphan-btn').addEventListener('click', function() {
            const btn = this;
            btn.disabled = true;
            showLoading('wpc-orphan-status', 'Cleaning orphaned data...');
            
            const formData = new FormData();
            formData.append('action', 'wpc_clean_orphans');
            formData.append('nonce', nonce);
            
            fetch(ajaxurl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    if (data.success) {
                        showStatus('wpc-orphan-status', '✓ Cleanup complete! Removed ' + data.data.cleaned + ' orphaned entries.', 'success');
                    } else {
                        showStatus('wpc-orphan-status', '✗ Error: ' + data.data, 'error');
                    }
                })
                .catch(e => { 
                    btn.disabled = false;
                    showStatus('wpc-orphan-status', '✗ Operation failed', 'error'); 
                });
        });
        
        // Rebuild Term Counts
        document.getElementById('wpc-recount-btn').addEventListener('click', function() {
            const btn = this;
            btn.disabled = true;
            showLoading('wpc-recount-status', 'Rebuilding term counts...');
            
            const formData = new FormData();
            formData.append('action', 'wpc_rebuild_counts');
            formData.append('nonce', nonce);
            
            fetch(ajaxurl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    if (data.success) {
                        showStatus('wpc-recount-status', '✓ Term counts rebuilt! Categories: ' + data.data.categories + ', Features: ' + data.data.features, 'success');
                    } else {
                        showStatus('wpc-recount-status', '✗ Error: ' + data.data, 'error');
                    }
                })
                .catch(e => { 
                    btn.disabled = false;
                    showStatus('wpc-recount-status', '✗ Operation failed', 'error'); 
                });
        });
        
        // Data Integrity Check
        document.getElementById('wpc-integrity-btn').addEventListener('click', function() {
            const btn = this;
            btn.disabled = true;
            showLoading('wpc-integrity-status', 'Running integrity check...');
            
            const formData = new FormData();
            formData.append('action', 'wpc_integrity_check');
            formData.append('nonce', nonce);
            
            fetch(ajaxurl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    if (data.success) {
                        const d = data.data;
                        let html = '<strong>✓ Integrity Check Complete</strong><br><br>';
                        html += '<strong>Summary:</strong><br>';
                        html += '• Total Items: ' + d.total_items + '<br>';
                        html += '• Total Lists: ' + d.total_lists + '<br>';
                        html += '• Categories: ' + d.total_categories + '<br>';
                        html += '• Features: ' + d.total_features + '<br><br>';
                        
                        if (d.issues.length > 0) {
                            html += '<strong style="color: #f59e0b;">⚠️ Issues Found (' + d.issues.length + '):</strong><br>';
                            d.issues.forEach(function(issue) {
                                html += '• ' + issue + '<br>';
                            });
                            showStatus('wpc-integrity-status', html, 'success');
                        } else {
                            html += '<strong style="color: #16a34a;">✓ No issues found!</strong>';
                            showStatus('wpc-integrity-status', html, 'success');
                        }
                    } else {
                        showStatus('wpc-integrity-status', '✗ Error: ' + data.data, 'error');
                    }
                })
                .catch(e => { 
                    btn.disabled = false;
                    showStatus('wpc-integrity-status', '✗ Operation failed', 'error'); 
                });
        });
        
        // ========== RESET SETTINGS ==========
        
        // Expand Reset Panel
        document.getElementById('wpc-reset-expand-btn').addEventListener('click', function() {
            const panel = document.getElementById('wpc-reset-panel');
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        });
        
        // Cancel Reset
        document.getElementById('wpc-reset-cancel-btn').addEventListener('click', function() {
            document.getElementById('wpc-reset-panel').style.display = 'none';
        });
        
        // Select All Reset Options
        document.getElementById('wpc-reset-all').addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('.wpc-reset-opt').forEach(cb => cb.checked = isChecked);
        });
        
        // Update Select All when individual options change
        document.querySelectorAll('.wpc-reset-opt').forEach(cb => {
            cb.addEventListener('change', function() {
                const allOpts = document.querySelectorAll('.wpc-reset-opt');
                const checkedOpts = document.querySelectorAll('.wpc-reset-opt:checked');
                document.getElementById('wpc-reset-all').checked = allOpts.length === checkedOpts.length;
            });
        });
        
        // Confirm Reset
        document.getElementById('wpc-reset-confirm-btn').addEventListener('click', function() {
            const selectedOpts = [];
            document.querySelectorAll('.wpc-reset-opt:checked').forEach(cb => selectedOpts.push(cb.value));
            
            if (selectedOpts.length === 0) {
                showStatus('wpc-reset-status', '⚠️ Please select at least one setting to reset.', 'error');
                return;
            }
            
            const btn = this;
            btn.disabled = true;
            showLoading('wpc-reset-status', 'Resetting selected settings...');
            
            const formData = new FormData();
            formData.append('action', 'wpc_reset_settings');
            formData.append('nonce', nonce);
            formData.append('options', JSON.stringify(selectedOpts));
            
            fetch(ajaxurl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    if (data.success) {
                        document.getElementById('wpc-reset-panel').style.display = 'none';
                        showStatus('wpc-reset-status', '✓ Selected settings reset to defaults! <a href="">Refresh page to see changes</a>', 'success');
                    } else {
                        showStatus('wpc-reset-status', '✗ Error: ' + data.data, 'error');
                    }
                })
                .catch(e => { 
                    btn.disabled = false;
                    showStatus('wpc-reset-status', '✗ Reset failed', 'error'); 
                });
        });
        
        // ========== DELETE DATA ==========
        
        // Expand Delete Panel
        document.getElementById('wpc-delete-expand-btn').addEventListener('click', function() {
            const panel = document.getElementById('wpc-delete-panel');
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        });
        
        // Cancel Delete
        document.getElementById('wpc-delete-cancel-btn').addEventListener('click', function() {
            document.getElementById('wpc-delete-panel').style.display = 'none';
            document.getElementById('wpc-delete-confirm-text').value = '';
        });
        
        // Select All Delete Options
        document.getElementById('wpc-delete-all').addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('.wpc-delete-opt').forEach(cb => cb.checked = isChecked);
        });
        
        // Update Select All when individual options change
        document.querySelectorAll('.wpc-delete-opt').forEach(cb => {
            cb.addEventListener('change', function() {
                const allOpts = document.querySelectorAll('.wpc-delete-opt');
                const checkedOpts = document.querySelectorAll('.wpc-delete-opt:checked');
                document.getElementById('wpc-delete-all').checked = allOpts.length === checkedOpts.length;
            });
        });
        
        // Confirm Delete
        document.getElementById('wpc-delete-confirm-btn').addEventListener('click', function() {
            const confirmText = document.getElementById('wpc-delete-confirm-text').value;
            if (confirmText !== 'DELETE') {
                showStatus('wpc-delete-status', '⚠️ Please type "DELETE" to confirm this action.', 'error');
                return;
            }
            
            const selectedOpts = [];
            document.querySelectorAll('.wpc-delete-opt:checked').forEach(cb => selectedOpts.push(cb.value));
            
            if (selectedOpts.length === 0) {
                showStatus('wpc-delete-status', '⚠️ Please select at least one data type to delete.', 'error');
                return;
            }
            
            const btn = this;
            btn.disabled = true;
            showLoading('wpc-delete-status', 'Deleting selected data... Please wait...');
            
            const formData = new FormData();
            formData.append('action', 'wpc_delete_all_data');
            formData.append('nonce', nonce);
            formData.append('options', JSON.stringify(selectedOpts));
            
            fetch(ajaxurl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    if (data.success) {
                        const r = data.data;
                        document.getElementById('wpc-delete-panel').style.display = 'none';
                        document.getElementById('wpc-delete-confirm-text').value = '';
                        showStatus('wpc-delete-status', `✓ Data deleted successfully!<br><br>
                            <strong>Deleted:</strong><br>
                            • Items: ${r.items_deleted || 0}<br>
                            • Lists: ${r.lists_deleted || 0}<br>
                            • Categories: ${r.categories_deleted || 0}<br>
                            • Features: ${r.features_deleted || 0}<br>
                            ${r.settings_reset ? '• Settings: Reset to defaults' : ''}`, 'success');
                    } else {
                        showStatus('wpc-delete-status', '✗ Error: ' + data.data, 'error');
                    }
                })
                .catch(e => { 
                    btn.disabled = false;
                    showStatus('wpc-delete-status', '✗ Delete operation failed', 'error'); 
                });
        });
    })();
    </script>
    <?php
}

/**
 * AJAX: Clear plugin cache/transients
 */
add_action( 'wp_ajax_wpc_clear_cache', 'wpc_ajax_clear_cache' );
function wpc_ajax_clear_cache() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized' );
    }
    check_ajax_referer( 'wpc_import_export_nonce', 'nonce' );
    
    global $wpdb;
    
    // Delete all transients with our prefix
    $deleted = $wpdb->query(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wpc_%' OR option_name LIKE '_transient_timeout_wpc_%'"
    );
    
    // Also clear any object cache if available
    if ( function_exists( 'wp_cache_flush' ) ) {
        wp_cache_flush();
    }
    
    wp_send_json_success( array( 'message' => 'Removed ' . intval($deleted / 2) . ' cached entries.' ) );
}

/**
 * AJAX: Clean orphaned post meta
 */
add_action( 'wp_ajax_wpc_clean_orphans', 'wpc_ajax_clean_orphans' );
function wpc_ajax_clean_orphans() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized' );
    }
    check_ajax_referer( 'wpc_import_export_nonce', 'nonce' );
    
    global $wpdb;
    
    // Find and delete orphaned post meta (meta for posts that no longer exist)
    $deleted = $wpdb->query(
        "DELETE pm FROM {$wpdb->postmeta} pm
         LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
         WHERE p.ID IS NULL AND pm.meta_key LIKE '_wpc_%'"
    );
    
    wp_send_json_success( array( 'cleaned' => intval($deleted) ) );
}

/**
 * AJAX: Rebuild term counts
 */
add_action( 'wp_ajax_wpc_rebuild_counts', 'wpc_ajax_rebuild_counts' );
function wpc_ajax_rebuild_counts() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized' );
    }
    check_ajax_referer( 'wpc_import_export_nonce', 'nonce' );
    
    // Get all terms and recount
    $cat_terms = get_terms( array( 'taxonomy' => 'comparison_category', 'hide_empty' => false, 'fields' => 'ids' ) );
    $feat_terms = get_terms( array( 'taxonomy' => 'comparison_feature', 'hide_empty' => false, 'fields' => 'ids' ) );
    
    $cat_count = 0;
    $feat_count = 0;
    
    if ( ! is_wp_error( $cat_terms ) ) {
        foreach ( $cat_terms as $term_id ) {
            wp_update_term_count_now( array( $term_id ), 'comparison_category' );
            $cat_count++;
        }
    }
    
    if ( ! is_wp_error( $feat_terms ) ) {
        foreach ( $feat_terms as $term_id ) {
            wp_update_term_count_now( array( $term_id ), 'comparison_feature' );
            $feat_count++;
        }
    }
    
    wp_send_json_success( array( 'categories' => $cat_count, 'features' => $feat_count ) );
}

/**
 * AJAX: Data integrity check
 */
add_action( 'wp_ajax_wpc_integrity_check', 'wpc_ajax_integrity_check' );
function wpc_ajax_integrity_check() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized' );
    }
    check_ajax_referer( 'wpc_import_export_nonce', 'nonce' );
    
    $issues = array();
    
    // Get all comparison items
    $items = get_posts( array( 'post_type' => 'comparison_item', 'posts_per_page' => -1, 'post_status' => 'any' ) );
    $lists = get_posts( array( 'post_type' => 'comparison_list', 'posts_per_page' => -1, 'post_status' => 'any' ) );
    $categories = get_terms( array( 'taxonomy' => 'comparison_category', 'hide_empty' => false ) );
    $features = get_terms( array( 'taxonomy' => 'comparison_feature', 'hide_empty' => false ) );
    
    // Check items
    foreach ( $items as $item ) {
        $title = $item->post_title;
        
        // Check for missing website URL
        $url = get_post_meta( $item->ID, '_wpc_website_url', true );
        if ( empty( $url ) ) {
            $issues[] = "Item \"{$title}\" (ID: {$item->ID}) has no website URL";
        }
        
        // Check for missing description
        $desc = get_post_meta( $item->ID, '_wpc_short_description', true );
        if ( empty( $desc ) ) {
            $issues[] = "Item \"{$title}\" (ID: {$item->ID}) has no description";
        }
        
        // Check for no categories
        $cats = wp_get_post_terms( $item->ID, 'comparison_category' );
        if ( empty( $cats ) || is_wp_error( $cats ) ) {
            $issues[] = "Item \"{$title}\" (ID: {$item->ID}) has no categories assigned";
        }
    }
    
    // Check lists for orphaned item references
    foreach ( $lists as $list ) {
        $list_items = get_post_meta( $list->ID, '_wpc_list_items', true );
        if ( is_array( $list_items ) ) {
            foreach ( $list_items as $list_item ) {
                $item_id = isset( $list_item['id'] ) ? intval( $list_item['id'] ) : 0;
                if ( $item_id && get_post_status( $item_id ) === false ) {
                    $issues[] = "List \"{$list->post_title}\" references deleted item ID: {$item_id}";
                }
            }
        }
    }
    
    // Limit issues to 20 for readability
    if ( count( $issues ) > 20 ) {
        $total = count( $issues );
        $issues = array_slice( $issues, 0, 20 );
        $issues[] = "... and " . ($total - 20) . " more issues.";
    }
    
    wp_send_json_success( array(
        'total_items' => count( $items ),
        'total_lists' => count( $lists ),
        'total_categories' => is_wp_error( $categories ) ? 0 : count( $categories ),
        'total_features' => is_wp_error( $features ) ? 0 : count( $features ),
        'issues' => $issues
    ) );
}

/**
 * Schema SEO Tab
 */
function wpc_render_schema_seo_tab() {
    $settings = function_exists('wpc_get_schema_settings') ? wpc_get_schema_settings() : array(
        'enabled' => '1',
        'product_type' => 'Product',
        'currency' => 'USD',
        'include_rating' => '1',
        'include_offers' => '1',
        'include_pros_cons' => '1',
    );
    ?>
    <div style="max-width: 800px;">
        <?php wp_nonce_field( 'wpc_schema_settings_nonce', 'wpc_schema_nonce' ); ?>
        
        <div style="background: #f0fdf4; border: 2px solid #16a34a; border-radius: 8px; padding: 30px; margin-bottom: 30px;">
            <div style="margin-bottom: 20px;">
                <h2 style="margin: 0; color: #166534;"><?php _e( 'Schema SEO Settings', 'wp-comparison-builder' ); ?></h2>
                <p style="margin: 5px 0 0 0; color: #15803d;">Configure structured data for better search engine visibility.</p>
            </div>
            
            <!-- Enable/Disable Schema -->
            <div style="background: #fff; border: 1px solid #bbf7d0; border-radius: 6px; padding: 20px; margin-bottom: 15px;">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" id="wpc-schema-enabled" <?php checked( $settings['enabled'], '1' ); ?>>
                    <div>
                        <strong style="color: #166534;">Enable Schema Output</strong>
                        <p style="margin: 0; color: #6b7280; font-size: 13px;">Generate JSON-LD structured data for comparison items</p>
                    </div>
                </label>
            </div>
            
            <!-- Product Type -->
            <div style="background: #fff; border: 1px solid #bbf7d0; border-radius: 6px; padding: 20px; margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 10px;">
                    <strong style="color: #166534;">Product Type</strong>
                    <p style="margin: 5px 0 10px 0; color: #6b7280; font-size: 13px;">Choose the Schema.org type that best describes your items</p>
                </label>
                <select id="wpc-schema-product-type" style="width: 100%; padding: 8px;">
                    <option value="Product" <?php selected( $settings['product_type'], 'Product' ); ?>>Product (Generic)</option>
                    <option value="SoftwareApplication" <?php selected( $settings['product_type'], 'SoftwareApplication' ); ?>>SoftwareApplication (Apps, SaaS)</option>
                    <option value="Service" <?php selected( $settings['product_type'], 'Service' ); ?>>Service (Services, Hosting)</option>
                    <option value="WebApplication" <?php selected( $settings['product_type'], 'WebApplication' ); ?>>WebApplication (Web Apps)</option>
                </select>
            </div>
            
            <!-- Currency -->
            <div style="background: #fff; border: 1px solid #bbf7d0; border-radius: 6px; padding: 20px; margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 10px;">
                    <strong style="color: #166534;">Default Currency</strong>
                    <p style="margin: 5px 0 10px 0; color: #6b7280; font-size: 13px;">Currency code for pricing (ISO 4217)</p>
                </label>
                <select id="wpc-schema-currency" style="width: 100%; padding: 8px;">
                    <option value="USD" <?php selected( $settings['currency'], 'USD' ); ?>>USD - US Dollar</option>
                    <option value="EUR" <?php selected( $settings['currency'], 'EUR' ); ?>>EUR - Euro</option>
                    <option value="GBP" <?php selected( $settings['currency'], 'GBP' ); ?>>GBP - British Pound</option>
                    <option value="CAD" <?php selected( $settings['currency'], 'CAD' ); ?>>CAD - Canadian Dollar</option>
                    <option value="AUD" <?php selected( $settings['currency'], 'AUD' ); ?>>AUD - Australian Dollar</option>
                    <option value="INR" <?php selected( $settings['currency'], 'INR' ); ?>>INR - Indian Rupee</option>
                    <option value="BDT" <?php selected( $settings['currency'], 'BDT' ); ?>>BDT - Bangladeshi Taka</option>
                </select>
            </div>
            
            <!-- Include Options -->
            <div style="background: #fff; border: 1px solid #bbf7d0; border-radius: 6px; padding: 20px; margin-bottom: 15px;">
                <strong style="display: block; margin-bottom: 15px; color: #166534;">Include in Schema</strong>
                
                <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; cursor: pointer;">
                    <input type="checkbox" id="wpc-schema-rating" <?php checked( $settings['include_rating'], '1' ); ?>>
                    <span>Aggregate Rating (star ratings)</span>
                </label>
                
                <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; cursor: pointer;">
                    <input type="checkbox" id="wpc-schema-offers" <?php checked( $settings['include_offers'], '1' ); ?>>
                    <span>Offers (pricing information)</span>
                </label>
                
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" id="wpc-schema-pros-cons" <?php checked( $settings['include_pros_cons'], '1' ); ?>>
                    <span>Pros & Cons (as review positiveNotes/negativeNotes)</span>
                </label>
            </div>
            
            <button type="button" id="wpc-save-schema-settings" class="button button-primary" style="background: #16a34a; border-color: #16a34a;">
                <?php _e( 'Save Schema Settings', 'wp-comparison-builder' ); ?>
            </button>
            
            <span id="wpc-schema-status" style="margin-left: 15px;"></span>
        </div>
        
        <!-- Info Section -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px;">
            <h3 style="margin: 0 0 15px 0; color: #334155;">💡 How Schema Works</h3>
            <ul style="margin: 0; padding-left: 20px; color: #64748b;">
                <li><strong>Single Item Pages:</strong> Schema is automatically output in the &lt;head&gt;</li>
                <li><strong>Custom Lists:</strong> ItemList schema is generated with all items in the list</li>
                <li><strong>Preview:</strong> View the generated schema at the bottom of each item's edit page</li>
                <li><strong>Validation:</strong> Use <a href="https://search.google.com/test/rich-results" target="_blank">Google Rich Results Test</a> to validate</li>
            </ul>
        </div>
    </div>
    
    <script>
    (function() {
        document.getElementById('wpc-save-schema-settings').addEventListener('click', function() {
            const statusEl = document.getElementById('wpc-schema-status');
            statusEl.textContent = 'Saving...';
            
            const settings = {
                enabled: document.getElementById('wpc-schema-enabled').checked ? '1' : '0',
                product_type: document.getElementById('wpc-schema-product-type').value,
                currency: document.getElementById('wpc-schema-currency').value,
                include_rating: document.getElementById('wpc-schema-rating').checked ? '1' : '0',
                include_offers: document.getElementById('wpc-schema-offers').checked ? '1' : '0',
                include_pros_cons: document.getElementById('wpc-schema-pros-cons').checked ? '1' : '0',
            };
            
            const formData = new FormData();
            formData.append('action', 'wpc_save_schema_settings');
            formData.append('nonce', document.getElementById('wpc_schema_nonce').value);
            formData.append('settings', JSON.stringify(settings));
            
            fetch(ajaxurl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        statusEl.innerHTML = '<span style="color: #16a34a;">✓ Saved!</span>';
                    } else {
                        statusEl.innerHTML = '<span style="color: #dc2626;">✗ Error</span>';
                    }
                    setTimeout(() => { statusEl.textContent = ''; }, 3000);
                })
                .catch(e => { statusEl.innerHTML = '<span style="color: #dc2626;">✗ Failed</span>'; });
        });
    })();
    </script>
    <?php
}

/**
 * AJAX: Save schema settings
 */
add_action( 'wp_ajax_wpc_save_schema_settings', 'wpc_ajax_save_schema_settings' );
function wpc_ajax_save_schema_settings() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized' );
    }
    
    check_ajax_referer( 'wpc_schema_settings_nonce', 'nonce' );
    
    $settings = json_decode( stripslashes( $_POST['settings'] ), true );
    
    if ( ! is_array( $settings ) ) {
        wp_send_json_error( 'Invalid settings' );
    }
    
    // Sanitize
    $clean_settings = array(
        'enabled' => isset( $settings['enabled'] ) ? sanitize_text_field( $settings['enabled'] ) : '1',
        'product_type' => isset( $settings['product_type'] ) ? sanitize_text_field( $settings['product_type'] ) : 'Product',
        'currency' => isset( $settings['currency'] ) ? sanitize_text_field( $settings['currency'] ) : 'USD',
        'include_rating' => isset( $settings['include_rating'] ) ? sanitize_text_field( $settings['include_rating'] ) : '1',
        'include_offers' => isset( $settings['include_offers'] ) ? sanitize_text_field( $settings['include_offers'] ) : '1',
        'include_pros_cons' => isset( $settings['include_pros_cons'] ) ? sanitize_text_field( $settings['include_pros_cons'] ) : '1',
    );
    
    update_option( 'wpc_schema_settings', $clean_settings );
    
    wp_send_json_success( 'Settings saved' );
}
