<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add Meta Boxes
 */
function hosting_guider_add_meta_boxes() {
    add_meta_box(
        'hosting_guider_details',
        __( 'Hosting Provider Details (Simplified)', 'hosting-guider' ),
        'hosting_guider_render_meta_box',
        'hosting_provider',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'hosting_guider_add_meta_boxes' );

/**
 * Remove Default Taxonomy Meta Boxes (To avoid confusion)
 */
function hosting_guider_remove_tax_meta_boxes() {
    remove_meta_box( 'hosting_typediv', 'hosting_provider', 'side' );
    remove_meta_box( 'tagsdiv-hosting_feature', 'hosting_provider', 'side' );
}
add_action( 'admin_menu', 'hosting_guider_remove_tax_meta_boxes' );

/**
 * Render Meta Box Content
 */
function hosting_guider_render_meta_box( $post ) {
    // Nonce field for security
    wp_nonce_field( 'hosting_guider_save_details', 'hosting_guider_nonce' );

    // Get current values
    $price = get_post_meta( $post->ID, '_hosting_price', true );
    $rating = get_post_meta( $post->ID, '_hosting_rating', true );
    $pros = get_post_meta( $post->ID, '_hosting_pros', true ); // Stored as array or newline separated string? string for ease.
    $cons = get_post_meta( $post->ID, '_hosting_cons', true );
    
    // Get Terms
    $current_types = wp_get_post_terms( $post->ID, 'hosting_type', array( 'fields' => 'ids' ) );
    $current_type = ! empty( $current_types ) ? $current_types[0] : '';
    
    $current_features = wp_get_post_terms( $post->ID, 'hosting_feature', array( 'fields' => 'ids' ) );

    // Get all available terms for UI
    $all_types = get_terms( array( 'taxonomy' => 'hosting_type', 'hide_empty' => false ) );
    $all_features = get_terms( array( 'taxonomy' => 'hosting_feature', 'hide_empty' => false ) );
    ?>
    
    <style>
        .hg-row { display: flex; gap: 20px; margin-bottom: 15px; }
        .hg-col { flex: 1; }
        .hg-label { font-weight: bold; display: block; margin-bottom: 5px; }
        .hg-input { width: 100%; }
        .hg-checkbox-list, .hg-radio-list { 
            border: 1px solid #ddd; padding: 10px; max-height: 150px; overflow-y: auto; background: #fff; 
        }
        .hg-add-new-wrap { margin-top: 5px; display: flex; gap: 5px; }
    </style>

    <div class="hg-details-wrap">
        
        <!-- Price & Rating -->
        <div class="hg-row">
            <div class="hg-col">
                <label class="hg-label"><?php _e( 'Price (e.g. $2.99)', 'hosting-guider' ); ?></label>
                <input type="text" name="hosting_price" value="<?php echo esc_attr( $price ); ?>" class="hg-input" />
            </div>
            <div class="hg-col">
                <label class="hg-label"><?php _e( 'Rating (0-5)', 'hosting-guider' ); ?></label>
                <input type="number" step="0.1" min="0" max="5" name="hosting_rating" value="<?php echo esc_attr( $rating ); ?>" class="hg-input" />
            </div>
        </div>

        <!-- Logo (External Option) -->
        <div class="hg-row">
            <div class="hg-col">
                <label class="hg-label"><?php _e( 'Logo (External URL)', 'hosting-guider' ); ?></label>
                <input type="url" name="hosting_external_logo_url" value="<?php echo esc_url( get_post_meta( $post->ID, '_hosting_external_logo_url', true ) ); ?>" class="hg-input" placeholder="https://example.com/logo.png" />
                <p class="description"><?php _e( 'Or use the standard "Featured Image" box on the right.', 'hosting-guider' ); ?></p>
            </div>
        </div>

        <!-- Details Link & Button Text -->
        <div class="hg-row">
            <div class="hg-col">
                <label class="hg-label"><?php _e( 'Details Page Link (URL)', 'hosting-guider' ); ?></label>
                <input type="text" name="hosting_details_link" value="<?php echo esc_url( get_post_meta( $post->ID, '_hosting_details_link', true ) ); ?>" class="hg-input" placeholder="https://example.com/review-page" />
                <p class="description"><?php _e( 'Where the "View Details" button should link to.', 'hosting-guider' ); ?></p>
            </div>
            <div class="hg-col">
                <label class="hg-label"><?php _e( 'Button Text (Popup)', 'hosting-guider' ); ?></label>
                <input type="text" name="hosting_button_text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_hosting_button_text', true ) ); ?>" class="hg-input" placeholder="Visit Website" />
            </div>
        </div>

        <!-- Featured Badge Customization -->
        <div class="hg-row">
            <div class="hg-col">
                <label class="hg-label"><?php _e( 'Featured Badge Text', 'hosting-guider' ); ?></label>
                <input type="text" name="hosting_featured_badge_text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_hosting_featured_badge_text', true ) ); ?>" class="hg-input" placeholder="e.g., Editors Pick, Best Deal, Top Choice" />
                <p class="description"><?php _e( 'Only applies if this provider is marked as Featured.', 'hosting-guider' ); ?></p>
            </div>
            <div class="hg-col">
                <label class="hg-label"><?php _e( 'Badge Color', 'hosting-guider' ); ?></label>
                <input type="color" name="hosting_featured_badge_color" value="<?php echo esc_attr( get_post_meta( $post->ID, '_hosting_featured_badge_color', true ) ?: '#f59e0b' ); ?>" class="hg-input" style="height: 40px;" />
                <p class="description"><?php _e( 'Choose the badge background color.', 'hosting-guider' ); ?></p>
            </div>
        </div>

        <!-- Hosting Type -->
        <div class="hg-row">
            <div class="hg-col">
                <label class="hg-label"><?php _e( 'Hosting Type', 'hosting-guider' ); ?></label>
                <div class="hg-radio-list" id="hg-type-list">
                    <?php if ( ! empty( $all_types ) && ! is_wp_error( $all_types ) ) : ?>
                        <?php foreach ( $all_types as $type ) : ?>
                            <label style="display:block;">
                                <input type="radio" name="hosting_type" value="<?php echo esc_attr( $type->term_id ); ?>" <?php checked( $current_type, $type->term_id ); ?> />
                                <?php echo esc_html( $type->name ); ?>
                            </label>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p><?php _e( 'No types found. Add one below.', 'hosting-guider' ); ?></p>
                    <?php endif; ?>
                </div>
                <div class="hg-add-new-wrap">
                    <input type="text" id="new-hosting-type" placeholder="New Type Name" />
                    <button type="button" class="button" onclick="hgAddTerm('hosting_type')">Add</button>
                </div>
            </div>
            
            <!-- Features -->
            <div class="hg-col">
                <label class="hg-label"><?php _e( 'Features', 'hosting-guider' ); ?></label>
                <div class="hg-checkbox-list" id="hg-feature-list">
                    <?php if ( ! empty( $all_features ) && ! is_wp_error( $all_features ) ) : ?>
                        <?php foreach ( $all_features as $feature ) : ?>
                            <label style="display:block;">
                                <input type="checkbox" name="hosting_features[]" value="<?php echo esc_attr( $feature->term_id ); ?>" <?php checked( in_array( $feature->term_id, $current_features ) ); ?> />
                                <?php echo esc_html( $feature->name ); ?>
                            </label>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p>No features found. Add one below.</p>
                    <?php endif; ?>
                </div>
                <div class="hg-add-new-wrap">
                    <input type="text" id="new-hosting-feature" placeholder="New Feature Name" />
                    <button type="button" class="button" onclick="hgAddTerm('hosting_feature')">Add</button>
                </div>
            </div>
        </div>



        <!-- Pricing Plans Configuration -->
        <div class="hg-row">
            <div class="hg-col">
                <label class="hg-label"><?php _e( 'Pricing Plans Configuration', 'hosting-guider' ); ?></label>
                <div style="margin-bottom: 15px;">
                    <?php 
                    $hide_features = get_post_meta( $post->ID, '_hosting_hide_plan_features', true );
                    $hide_action = get_post_meta( $post->ID, '_hosting_hide_plan_action', true );
                    ?>
                    <label style="margin-right: 20px;">
                        <input type="checkbox" name="hg_hide_plan_features" value="1" <?php checked( $hide_features, '1' ); ?> />
                        <?php _e( 'Hide "Features" Column', 'hosting-guider' ); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="hg_hide_plan_action" value="1" <?php checked( $hide_action, '1' ); ?> />
                        <?php _e( 'Hide "Action" Column', 'hosting-guider' ); ?>
                    </label>
                </div>
            </div>
        </div>

        <!-- Pricing Plans Repeater -->
        <div class="hg-row">
            <div class="hg-col">
                <label class="hg-label"><?php _e( 'Pricing Plans', 'hosting-guider' ); ?></label>
                <div id="hg-plans-container">
                    <?php 
                    $plans = get_post_meta( $post->ID, '_hosting_pricing_plans', true );
                    if ( ! is_array( $plans ) ) $plans = array();
                    
                    if ( empty( $plans ) ) {
                        // Empty default row
                        $plans[] = array( 'name' => '', 'price' => '', 'period' => '', 'features' => '', 'link' => '' );
                    }

                    foreach ( $plans as $index => $plan ) : 
                    ?>
                        <div class="hg-plan-row" style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd; margin-bottom: 10px; border-radius: 4px;">
                            <div style="display: flex; gap: 10px; margin-bottom: 5px;">
                                <input type="text" name="hg_plans[<?php echo $index; ?>][name]" value="<?php echo esc_attr( isset($plan['name']) ? $plan['name'] : '' ); ?>" placeholder="Plan Name (e.g. Starter)" style="flex: 2;" />
                                <input type="text" name="hg_plans[<?php echo $index; ?>][price]" value="<?php echo esc_attr( isset($plan['price']) ? $plan['price'] : '' ); ?>" placeholder="Price (e.g. $2.99)" style="flex: 1;" />
                                <input type="text" name="hg_plans[<?php echo $index; ?>][period]" value="<?php echo esc_attr( isset($plan['period']) ? $plan['period'] : '' ); ?>" placeholder="/mo or /yr" style="flex: 1;" />
                            </div>
                            <div style="margin-bottom: 5px;">
                                <textarea name="hg_plans[<?php echo $index; ?>][features]" rows="3" style="width:100%;" placeholder="Features (one per line)"><?php echo esc_textarea( isset($plan['features']) ? $plan['features'] : '' ); ?></textarea>
                            </div>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="text" name="hg_plans[<?php echo $index; ?>][link]" value="<?php echo esc_attr( isset($plan['link']) ? $plan['link'] : '' ); ?>" placeholder="Checkout Link (https://...)" style="flex: 1;" />
                                <button type="button" class="button hg-remove-plan" onclick="hgRemovePlan(this)"><?php _e( 'Remove', 'hosting-guider' ); ?></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button button-primary" onclick="hgAddPlan()"><?php _e( '+ Add Plan', 'hosting-guider' ); ?></button>
            </div>
        </div>

        <!-- Pros & Cons -->
        <div class="hg-row">
            <div class="hg-col">
                <label class="hg-label">Pros (One per line)</label>
                <textarea name="hosting_pros" rows="5" class="hg-input"><?php echo esc_textarea( $pros ); ?></textarea>
            </div>
            <div class="hg-col">
                <label class="hg-label">Cons (One per line)</label>
                <textarea name="hosting_cons" rows="5" class="hg-input"><?php echo esc_textarea( $cons ); ?></textarea>
            </div>
        </div>

    </div>

    <script>
    function hgAddPlan() {
        var container = document.getElementById('hg-plans-container');
        var index = container.children.length;
        var html = `
            <div class="hg-plan-row" style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd; margin-bottom: 10px; border-radius: 4px;">
                <div style="display: flex; gap: 10px; margin-bottom: 5px;">
                    <input type="text" name="hg_plans[${index}][name]" placeholder="Plan Name" style="flex: 2;" />
                    <input type="text" name="hg_plans[${index}][price]" placeholder="Price" style="flex: 1;" />
                    <input type="text" name="hg_plans[${index}][period]" placeholder="/mo" style="flex: 1;" />
                </div>
                <div style="margin-bottom: 5px;">
                    <textarea name="hg_plans[${index}][features]" rows="3" style="width:100%;" placeholder="Features (one per line)"></textarea>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="url" name="hg_plans[${index}][link]" placeholder="Checkout Link" style="flex: 1;" />
                    <button type="button" class="button hg-remove-plan" onclick="hgRemovePlan(this)">Remove</button>
                </div>
            </div>
        `;
        // Use insertAdjacentHTML properly
        var temp = document.createElement('div');
        temp.innerHTML = html;
        container.appendChild(temp.firstElementChild);
    }
    
    function hgRemovePlan(btn) {
        btn.closest('.hg-plan-row').remove();
    }
    
    function hgAddTerm(taxonomy) {
        var inputId = taxonomy === 'hosting_type' ? 'new-hosting-type' : 'new-hosting-feature';
        var listId = taxonomy === 'hosting_type' ? 'hg-type-list' : 'hg-feature-list';
        var input = document.getElementById(inputId);
        var name = input.value;
        
        if (!name) return;

        // Simple AJAX to add term
        jQuery.post(ajaxurl, {
            action: 'hosting_guider_add_term',
            taxonomy: taxonomy,
            term_name: name,
            _ajax_nonce: '<?php echo wp_create_nonce('hosting_guider_add_term_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                var term = response.data;
                var html = '';
                if (taxonomy === 'hosting_type') {
                     html = '<label style="display:block;"><input type="radio" name="hosting_type" value="' + term.term_id + '" checked /> ' + term.name + '</label>';
                } else {
                     html = '<label style="display:block;"><input type="checkbox" name="hosting_features[]" value="' + term.term_id + '" checked /> ' + term.name + '</label>';
                }
                
                // If "No types/features found" exists, remove it
                var list = document.getElementById(listId);
                if (list.querySelector('p')) { list.innerHTML = ''; }
                
                jQuery('#' + listId).append(html);
                input.value = '';
            } else {
                alert('Error adding term');
            }
        });
    }
    </script>
    <?php
}

/**
 * Handle AJAX Add Term
 */
add_action('wp_ajax_hosting_guider_add_term', 'hosting_guider_ajax_add_term');
function hosting_guider_ajax_add_term() {
    check_ajax_referer('hosting_guider_add_term_nonce');
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
function hosting_guider_save_meta_box( $post_id ) {
    if ( ! isset( $_POST['hosting_guider_nonce'] ) || ! wp_verify_nonce( $_POST['hosting_guider_nonce'], 'hosting_guider_save_details' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Save Meta
    if ( isset( $_POST['hosting_price'] ) ) {
        update_post_meta( $post_id, '_hosting_price', sanitize_text_field( $_POST['hosting_price'] ) );
    }
    if ( isset( $_POST['hosting_rating'] ) ) {
        update_post_meta( $post_id, '_hosting_rating', sanitize_text_field( $_POST['hosting_rating'] ) );
    }
    if ( isset( $_POST['hosting_external_logo_url'] ) ) {
        update_post_meta( $post_id, '_hosting_external_logo_url', esc_url_raw( $_POST['hosting_external_logo_url'] ) );
    }
    if ( isset( $_POST['hosting_details_link'] ) ) {
        update_post_meta( $post_id, '_hosting_details_link', esc_url_raw( $_POST['hosting_details_link'] ) );
    }
    if ( isset( $_POST['hosting_button_text'] ) ) {
        update_post_meta( $post_id, '_hosting_button_text', sanitize_text_field( $_POST['hosting_button_text'] ) );
    }
    if ( isset( $_POST['hosting_featured_badge_text'] ) ) {
        update_post_meta( $post_id, '_hosting_featured_badge_text', sanitize_text_field( $_POST['hosting_featured_badge_text'] ) );
    }
    if ( isset( $_POST['hosting_featured_badge_color'] ) ) {
        update_post_meta( $post_id, '_hosting_featured_badge_color', sanitize_hex_color( $_POST['hosting_featured_badge_color'] ) );
    }
    if ( isset( $_POST['hosting_pros'] ) ) {
        update_post_meta( $post_id, '_hosting_pros', sanitize_textarea_field( $_POST['hosting_pros'] ) );
    }
    if ( isset( $_POST['hosting_cons'] ) ) {
        update_post_meta( $post_id, '_hosting_cons', sanitize_textarea_field( $_POST['hosting_cons'] ) );
    }

    // Save Pricing Plans
    if ( isset( $_POST['hg_plans'] ) && is_array( $_POST['hg_plans'] ) ) {
        $plans = array();
        foreach ( $_POST['hg_plans'] as $p ) {
            if ( ! empty( $p['name'] ) ) { // Save only if has name
                $plans[] = array(
                    'name'     => sanitize_text_field( $p['name'] ),
                    'price'    => sanitize_text_field( $p['price'] ),
                    'period'   => sanitize_text_field( $p['period'] ),
                    'features' => sanitize_textarea_field( $p['features'] ),
                    'link'     => esc_url_raw( $p['link'] )
                );
            }
        }
        update_post_meta( $post_id, '_hosting_pricing_plans', $plans );
    } else {
        delete_post_meta( $post_id, '_hosting_pricing_plans' );
    }

    // Save Visibility Flags
    if ( isset( $_POST['hg_hide_plan_features'] ) ) {
        update_post_meta( $post_id, '_hosting_hide_plan_features', '1' );
    } else {
        delete_post_meta( $post_id, '_hosting_hide_plan_features' );
    }

    if ( isset( $_POST['hg_hide_plan_action'] ) ) {
        update_post_meta( $post_id, '_hosting_hide_plan_action', '1' );
    } else {
        delete_post_meta( $post_id, '_hosting_hide_plan_action' );
    }

    // Save Terms (Type)
    if ( isset( $_POST['hosting_type'] ) ) {
        wp_set_post_terms( $post_id, array( intval( $_POST['hosting_type'] ) ), 'hosting_type' );
    }

    // Save Terms (Features)
    if ( isset( $_POST['hosting_features'] ) ) {
        $feature_ids = array_map( 'intval', $_POST['hosting_features'] );
        wp_set_post_terms( $post_id, $feature_ids, 'hosting_feature' );
    } else {
        // If empty, clear terms
        wp_set_post_terms( $post_id, array(), 'hosting_feature' );
    }
}
add_action( 'save_post', 'hosting_guider_save_meta_box' );

/**
 * Custom Admin Columns for Hosting Providers
 */
function hosting_guider_provider_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['logo'] = 'Logo';
    $new_columns['title'] = 'Provider Name';
    $new_columns['price'] = 'Price';
    $new_columns['rating'] = 'Rating';
    $new_columns['type'] = 'Type';
    $new_columns['date'] = $columns['date'];
    return $new_columns;
}
add_filter('manage_hosting_provider_posts_columns', 'hosting_guider_provider_columns');

function hosting_guider_provider_custom_column($column, $post_id) {
    switch ($column) {
        case 'logo':
            $logo_url = get_the_post_thumbnail_url($post_id, 'thumbnail');
            if (!$logo_url) {
                $logo_url = get_post_meta($post_id, '_hosting_external_logo_url', true);
            }
            if ($logo_url) {
                echo '<img src="' . esc_url($logo_url) . '" style="width: 50px; height: 50px; object-fit: contain; border-radius: 4px; border: 1px solid #ddd;" />';
            } else {
                echo '<span style="color:#ccc;">No Logo</span>';
            }
            break;
        case 'price':
            echo '<strong>' . esc_html(get_post_meta($post_id, '_hosting_price', true)) . '</strong>/mo';
            break;
        case 'rating':
            $rating = get_post_meta($post_id, '_hosting_rating', true);
            echo '<span style="color: #f59e0b; font-weight: bold;">★ ' . esc_html($rating) . '</span>';
            break;
        case 'type':
            $terms = get_the_terms($post_id, 'hosting_type');
            if ($terms && !is_wp_error($terms)) {
                $names = wp_list_pluck($terms, 'name');
                echo implode(', ', $names);
            } else {
                echo '—';
            }
            break;
    }
}
add_action('manage_hosting_provider_posts_custom_column', 'hosting_guider_provider_custom_column', 10, 2);
