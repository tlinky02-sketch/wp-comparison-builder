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
        .wpc-row { display: flex; gap: 20px; margin-bottom: 15px; }
        .wpc-col { flex: 1; }
        .wpc-label { font-weight: bold; display: block; margin-bottom: 5px; }
        .wpc-input { width: 100%; }
        .wpc-checkbox-list, .wpc-radio-list { 
            border: 1px solid #ddd; padding: 10px; max-height: 150px; overflow-y: auto; background: #fff; 
        }
        .wpc-add-new-wrap { margin-top: 5px; display: flex; gap: 5px; }
    </style>

    <div class="wpc-details-wrap">
        
        <!-- Shortcode Info (New) -->
        <div class="wpc-row" style="background: #f0f9ff; border: 1px solid #bae6fd; padding: 15px; border-radius: 6px;">
            <div class="wpc-col">
                 <h3 style="margin-top:0; color: #0284c7;">Pricing Table Shortcode</h3>
                 <p style="margin-bottom: 10px;">Use the following shortcode to display specifically THIS item's pricing table anywhere on your site (inline):</p>
                 
                 <div style="display:flex; align-items:center; gap: 10px;">
                     <code style="background: #fff; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; display: block; flex: 1;">
                         [wpc_pricing_table id="<?php echo $post->ID; ?>"]
                     </code>
                     <button type="button" class="button" onclick="navigator.clipboard.writeText('[wpc_pricing_table id=&quot;<?php echo $post->ID; ?>&quot;]')">Copy</button>
                 </div>
                 
                 <p class="description" style="margin-top: 10px;">
                     <strong>Optional Attributes:</strong><br/>
                     <code>show_plan_buttons="0"</code> (hide plan buttons)<br/>
                     <code>show_footer_button="0"</code> (hide footer button)<br/>
                     <code>primary_color="#ff0000"</code> (override color)<br/>
                 </p>
            </div>
        </div>

        <!-- Price & Rating -->
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

        <!-- Logo (External Option) -->
        <div class="wpc-row">
            <div class="wpc-col">
                <label class="wpc-label"><?php _e( 'Logo (External URL)', 'wp-comparison-builder' ); ?></label>
                <input type="url" name="wpc_external_logo_url" value="<?php echo esc_url( get_post_meta( $post->ID, '_wpc_external_logo_url', true ) ); ?>" class="wpc-input" placeholder="https://example.com/logo.png" />
                <p class="description"><?php _e( 'Or use the standard "Featured Image" box on the right.', 'wp-comparison-builder' ); ?></p>
            </div>
        </div>

        <!-- Details Link & Button Text -->
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
        </div>

        <!-- Coupon Section -->
        <div class="wpc-row">
            <div class="wpc-col">
                <label class="wpc-label"><?php _e( 'Coupon Code', 'wp-comparison-builder' ); ?></label>
                <input type="text" name="wpc_coupon_code" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_coupon_code', true ) ); ?>" class="wpc-input" placeholder="e.g. SAVE20" />
            </div>
            <div class="wpc-col">
                <?php $show_coupon = get_post_meta( $post->ID, '_wpc_show_coupon', true ); ?>
                <label class="wpc-label"><?php _e( 'Show Coupon?', 'wp-comparison-builder' ); ?></label>
                <label>
                    <input type="checkbox" name="wpc_show_coupon" value="1" <?php checked( $show_coupon, '1' ); ?> />
                    <?php _e( 'Display coupon button on the frontend', 'wp-comparison-builder' ); ?>
                </label>
            </div>
            <div class="wpc-col">
                <label class="wpc-label"><?php _e( 'Featured Badge Text', 'wp-comparison-builder' ); ?></label>
                <input type="text" name="wpc_featured_badge_text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_featured_badge_text', true ) ); ?>" class="wpc-input" placeholder="e.g. Top Choice" />
            </div>
            <div class="wpc-col">
                <label class="wpc-label"><?php _e( 'Featured Color', 'wp-comparison-builder' ); ?></label>
                <input type="color" name="wpc_featured_color" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_featured_color', true ) ); ?>" class="wpc-input" style="height:35px;" />
            </div>
        </div>

        <!-- Schema & Product Category -->
        <div class="wpc-row" style="border-top: 1px solid #eee; padding-top: 20px; margin-top: 20px;">
            <div class="wpc-col">
                <h3 style="margin-top: 0; margin-bottom: 15px;">Schema & Product Data</h3>
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

        <!-- Footer Button / Visibility Settings -->
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
            <div class="wpc-col">
                <!-- Spacer or additional visibility settings could go here -->
            </div>
        </div>

        <!-- Design Overrides (Colors) -->
        <div class="wpc-row">
            <div class="wpc-col">
                <label class="wpc-label" style="display:flex; align-items:center; gap:5px;">
                    <input type="checkbox" name="wpc_enable_design_overrides" value="1" <?php checked( get_post_meta( $post->ID, '_wpc_enable_design_overrides', true ), '1' ); ?> />
                    <?php _e( 'Enable Design Overrides', 'wp-comparison-builder' ); ?>
                </label>
                <p class="description">Enable to customize colors for this specific item in the pricing popup/table.</p>
                
                <div style="margin-top: 10px; padding-left: 20px; border-left: 2px solid #ddd;">
                    <div style="margin-bottom: 5px;">
                        <label style="font-size:12px; display:block;">Primary Color</label>
                        <input type="color" name="wpc_primary_color" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_primary_color', true ) ?: '#6366f1' ); ?>" style="height:35px;" />
                    </div>
                     <div style="margin-bottom: 5px;">
                        <label style="font-size:12px; display:block;">Accent Color</label>
                        <input type="color" name="wpc_accent_color" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_accent_color', true ) ?: '#818cf8' ); ?>" style="height:35px;" />
                    </div>
                     <div style="margin-bottom: 5px;">
                        <label style="font-size:12px; display:block;">Border Color</label>
                        <input type="color" name="wpc_border_color" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_border_color', true ) ?: '#e2e8f0' ); ?>" style="height:35px;" />
                    </div>
                </div>
            </div>
        </div>

        
        <!-- Dashboard / Hero Image -->
        <div class="wpc-row">
            <div class="wpc-col">
                <label class="wpc-label"><?php _e( 'Dashboard / Hero Image (External URL)', 'wp-comparison-builder' ); ?></label>
                <input type="url" name="wpc_dashboard_image" value="<?php echo esc_url( get_post_meta( $post->ID, '_wpc_dashboard_image', true ) ); ?>" class="wpc-input" placeholder="https://example.com/dashboard.jpg" />
                <p class="description">Used in hero sections and detailed views.</p>
            </div>
        </div>

        <!-- Compare Alternatives - Select Competitors -->
        <div class="wpc-row">
            <div class="wpc-col">
                <label class="wpc-label"><?php _e( 'Compare Alternatives (Select Items)', 'wp-comparison-builder' ); ?></label>
                <?php
                // Get all OTHER items (exclude current)
                $all_other_items = get_posts( array(
                    'post_type' => 'comparison_item',
                    'posts_per_page' => -1,
                    'post_status' => array( 'publish', 'draft' ),
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

        <!-- Category -->
        <div class="wpc-row">
            <div class="wpc-col">
                <label class="wpc-label"><?php _e( 'Categories', 'wp-comparison-builder' ); ?></label>
                <input type="text" id="wpc-cat-search" placeholder="Search categories..." style="width:100%; margin-bottom:5px;" onkeyup="wpcFilterList('wpc-cat-search', 'wpc-cat-list')" />
                <div class="wpc-checkbox-list" id="wpc-cat-list">
                    <?php if ( ! empty( $all_cats ) && ! is_wp_error( $all_cats ) ) : ?>
                        <?php foreach ( $all_cats as $cat ) : ?>
                            <label style="display:block;">
                                <input type="checkbox" name="wpc_category[]" value="<?php echo esc_attr( $cat->term_id ); ?>" <?php checked( in_array( $cat->term_id, $current_cats ) ); ?> onchange="wpcSyncPrimaryCats(this)" />
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
                    <p class="description" style="margin-bottom:5px;">Select up to 2 categories to be shown by default on the card. Only selected categories match.</p>
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
            
            <!-- Features -->
            <div class="wpc-col">
                <label class="wpc-label"><?php _e( 'Features', 'wp-comparison-builder' ); ?></label>
                <input type="text" id="wpc-feature-search" placeholder="Search features..." style="width:100%; margin-bottom:5px;" onkeyup="wpcFilterList('wpc-feature-search', 'wpc-feature-list')" />
                <div class="wpc-checkbox-list" id="wpc-feature-list">
                    <?php if ( ! empty( $all_features ) && ! is_wp_error( $all_features ) ) : ?>
                        <?php foreach ( $all_features as $feature ) : ?>
                            <label style="display:block;">
                                <input type="checkbox" name="wpc_features[]" value="<?php echo esc_attr( $feature->term_id ); ?>" <?php checked( in_array( $feature->term_id, $current_features ) ); ?> />
                                <?php echo esc_html( $feature->name ); ?>
                            </label>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p>No features found. Add one below.</p>
                    <?php endif; ?>
                </div>
                <div class="wpc-add-new-wrap">
                    <input type="text" id="new-wpc-feature" placeholder="New Feature Name" />
                    <button type="button" class="button" onclick="wpcAddTerm('comparison_feature')">Add</button>
                </div>
            </div>
        </div>

        <!-- Pricing Table Design -->
        <div class="wpc-row">
            <div class="wpc-col">
                <label class="wpc-label" style="color:#0284c7;"><?php _e( 'Pricing Table Design (Overrides)', 'wp-comparison-builder' ); ?></label>
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
                            <input type="color" name="wpc_primary_color" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_primary_color', true ) ); ?>" style="height:35px; width:60px;" />
                        </div>
                        <div>
                            <label class="wpc-label" style="font-size:11px; margin-bottom:2px;">Accent Color</label>
                            <input type="color" name="wpc_accent_color" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_accent_color', true ) ); ?>" style="height:35px; width:60px;" />
                        </div>
                        <div>
                            <label class="wpc-label" style="font-size:11px; margin-bottom:2px;">Border Color</label>
                            <input type="color" name="wpc_border_color" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_border_color', true ) ); ?>" style="height:35px; width:60px;" />
                        </div>
                     </div>
                     
                     <div style="display:flex; gap: 20px;">
                        <div style="flex:1;">
                             <?php 
                                $show_footer = get_post_meta( $post->ID, '_wpc_show_footer_button', true ); 
                                $show_plan_btns = get_post_meta( $post->ID, '_wpc_show_plan_buttons', true );
                             ?>
                             <label class="wpc-label"><?php _e( 'Visibility Options', 'wp-comparison-builder' ); ?></label>
                             <label style="display:block; margin-bottom:5px;">
                                <input type="checkbox" name="wpc_show_footer_button" value="1" <?php checked( $show_footer !== '0' ); ?> />
                                <?php _e( 'Show "Visit Website" Footer Button', 'wp-comparison-builder' ); ?>
                             </label>
                             <label style="display:block; margin-bottom:5px;">
                                <input type="checkbox" name="wpc_show_plan_buttons" value="1" <?php checked( $show_plan_btns !== '0' ); ?> />
                                <?php _e( 'Show "Select" Buttons in Plans', 'wp-comparison-builder' ); ?>
                             </label>
                        </div>
                        <div style="flex:1;">
                            <label class="wpc-label"><?php _e( 'Footer Button Text', 'wp-comparison-builder' ); ?></label>
                            <input type="text" name="wpc_footer_button_text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wpc_footer_button_text', true ) ); ?>" class="wpc-input" placeholder="Default: Visit Website" />
                        </div>
                     </div>
                </div>
            </div>
        </div>

        <!-- Pricing Plans Configuration -->
        <div class="wpc-row">
            <div class="wpc-col">
                <label class="wpc-label"><?php _e( 'Pricing Plans Configuration', 'wp-comparison-builder' ); ?></label>
                <div style="margin-bottom: 15px;">
                    <?php 
                    $hide_features = get_post_meta( $post->ID, '_wpc_hide_plan_features', true );
                    $show_plan_links = get_post_meta( $post->ID, '_wpc_show_plan_links', true );
                    ?>
                    <label style="margin-right: 20px;">
                        <input type="checkbox" name="wpc_hide_plan_features" value="1" <?php checked( $hide_features, '1' ); ?> />
                        <?php _e( 'Hide "Features" Column', 'wp-comparison-builder' ); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="wpc_show_plan_links" value="1" <?php checked( $show_plan_links, '1' ); ?> />
                        <?php _e( 'Show "Select" Link Buttons', 'wp-comparison-builder' ); ?>
                    </label>
                </div>
            </div>
        </div>

        <!-- Pricing Plans Repeater -->
        <div class="wpc-row">
            <div class="wpc-col">
                <label class="wpc-label"><?php _e( 'Pricing Plans', 'wp-comparison-builder' ); ?></label>
                <div id="wpc-plans-container">
                    <?php 
                    $plans = get_post_meta( $post->ID, '_wpc_pricing_plans', true );
                    if ( ! is_array( $plans ) ) $plans = array();
                    
                    if ( empty( $plans ) ) {
                        // Empty default row
                        $plans[] = array( 'name' => '', 'price' => '', 'period' => '', 'features' => '', 'link' => '' );
                    }

                    foreach ( $plans as $index => $plan ) : 
                        // Backwards compatibility for show_button
                        $show_btn = isset($plan['show_button']) ? $plan['show_button'] : '0';
                        $show_popup = isset($plan['show_popup']) ? $plan['show_popup'] : $show_btn;
                        $show_table = isset($plan['show_table']) ? $plan['show_table'] : $show_btn;
                    ?>
                        <div class="wpc-plan-row" style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd; margin-bottom: 10px; border-radius: 4px;">
                            <div style="display: flex; gap: 10px; margin-bottom: 5px;">
                                <input type="text" name="wpc_plans[<?php echo $index; ?>][name]" value="<?php echo esc_attr( isset($plan['name']) ? $plan['name'] : '' ); ?>" placeholder="Plan Name (e.g. Basic)" style="flex: 2;" />
                                <input type="text" name="wpc_plans[<?php echo $index; ?>][price]" value="<?php echo esc_attr( isset($plan['price']) ? $plan['price'] : '' ); ?>" placeholder="Price (e.g. $29)" style="flex: 1;" />
                                <input type="text" name="wpc_plans[<?php echo $index; ?>][period]" value="<?php echo esc_attr( isset($plan['period']) ? $plan['period'] : '' ); ?>" placeholder="/mo or /yr" style="flex: 1;" />
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
                                        Show in Pop-up
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
                <button type="button" class="button button-primary" onclick="wpcAddPlan()"><?php _e( '+ Add Plan', 'wp-comparison-builder' ); ?></button>
            </div>
        </div>

        <!-- Pros & Cons -->
        <div class="wpc-row">
            <div class="wpc-col">
                <label class="wpc-label">Pros (One per line)</label>
                <textarea name="wpc_pros" rows="5" class="wpc-input"><?php echo esc_textarea( $pros ); ?></textarea>
            </div>
            <div class="wpc-col">
                <label class="wpc-label">Cons (One per line)</label>
                <textarea name="wpc_cons" rows="5" class="wpc-input"><?php echo esc_textarea( $cons ); ?></textarea>
            </div>
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
                    <button type="button" class="button" id="wpc-copy-schema-btn">📋 Copy Schema</button>
                    <span id="wpc-copy-status" style="color: #16a34a; font-size: 12px; display: none;">✓ Copied!</span>
                    <a href="https://search.google.com/test/rich-results" target="_blank" class="button">🔍 Test with Google</a>
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
        </div>
        <script>
        document.getElementById('wpc-copy-schema-btn').addEventListener('click', function() {
            var schemaText = document.getElementById('wpc-schema-preview').textContent;
            var statusEl = document.getElementById('wpc-copy-status');
            var btn = this;
            
            // Try modern clipboard API first
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(schemaText).then(function() {
                    statusEl.style.display = 'inline';
                    setTimeout(function() { statusEl.style.display = 'none'; }, 2000);
                }).catch(function() {
                    wpcFallbackCopy(schemaText, statusEl);
                });
            } else {
                wpcFallbackCopy(schemaText, statusEl);
            }
        });
        
        function wpcFallbackCopy(text, statusEl) {
            // Fallback for older browsers or non-HTTPS
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                statusEl.style.display = 'inline';
                setTimeout(function() { statusEl.style.display = 'none'; }, 2000);
            } catch (e) {
                alert('Copy failed. Please select and copy manually.');
            }
            document.body.removeChild(textarea);
        }
        </script>
        <?php endif; ?>

    </div>

    <script>
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
    
    // Run sync on load
    document.addEventListener('DOMContentLoaded', function() {
        var catList = document.getElementById('wpc-cat-list');
        if (catList) {
            var inputs = catList.querySelectorAll('input[type="checkbox"]');
            inputs.forEach(function(input) {
                wpcSyncPrimaryCats(input);
            });
        }
    });

    function wpcAddPlan() {
        var container = document.getElementById('wpc-plans-container');
        var index = container.children.length;
        var html = `
            <div class="wpc-plan-row" style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd; margin-bottom: 10px; border-radius: 4px;">
                <div style="display: flex; gap: 10px; margin-bottom: 5px;">
                    <input type="text" name="wpc_plans[${index}][name]" placeholder="Plan Name" style="flex: 2;" />
                    <input type="text" name="wpc_plans[${index}][price]" placeholder="Price" style="flex: 1;" />
                    <input type="text" name="wpc_plans[${index}][period]" placeholder="/mo" style="flex: 1;" />
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
    if ( isset( $_POST['wpc_pros'] ) ) {
        update_post_meta( $post_id, '_wpc_pros', sanitize_textarea_field( $_POST['wpc_pros'] ) );
    }
    if ( isset( $_POST['wpc_cons'] ) ) {
        update_post_meta( $post_id, '_wpc_cons', sanitize_textarea_field( $_POST['wpc_cons'] ) );
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
    if ( isset( $_POST['wpc_show_coupon'] ) ) {
        update_post_meta( $post_id, '_wpc_show_coupon', '1' );
    } else {
        delete_post_meta( $post_id, '_wpc_show_coupon' );
    }

    // Save Compare Alternatives (Competitors)
    if ( isset( $_POST['wpc_competitors'] ) ) {
        $competitors = array_map( 'intval', $_POST['wpc_competitors'] );
        update_post_meta( $post_id, '_wpc_competitors', $competitors );
    } else {
        update_post_meta( $post_id, '_wpc_competitors', array() );
    }

    // Save Pricing Plans
    if ( isset( $_POST['wpc_plans'] ) && is_array( $_POST['wpc_plans'] ) ) {
        $plans = array();
        foreach ( $_POST['wpc_plans'] as $p ) {
            if ( ! empty( $p['name'] ) ) { // Save only if has name
                $plans[] = array(
                    'name'        => sanitize_text_field( $p['name'] ),
                    'price'       => sanitize_text_field( $p['price'] ),
                    'period'      => sanitize_text_field( $p['period'] ),
                    'features'    => sanitize_textarea_field( $p['features'] ),
                    'link'        => esc_url_raw( $p['link'] ),
                    'show_button' => isset( $p['show_button'] ) ? '1' : '0',
                    'button_text' => isset( $p['button_text'] ) ? sanitize_text_field( $p['button_text'] ) : '',
                    'show_banner' => isset( $p['show_banner'] ) ? '1' : '0',
                    'banner_text' => isset( $p['banner_text'] ) ? sanitize_text_field( $p['banner_text'] ) : '',
                    'banner_color' => isset( $p['banner_color'] ) ? sanitize_hex_color( $p['banner_color'] ) : '',
                    'show_popup'  => isset( $p['show_popup'] ) ? '1' : '0',
                    'show_table'  => isset( $p['show_table'] ) ? '1' : '0',
                );
            }
        }
        update_post_meta( $post_id, '_wpc_pricing_plans', $plans );
    } else {
        delete_post_meta( $post_id, '_wpc_pricing_plans' );
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

    // Save Terms (Category - Multiple)
    if ( isset( $_POST['wpc_category'] ) ) {
        $cat_ids = array_map( 'intval', $_POST['wpc_category'] );
        wp_set_post_terms( $post_id, $cat_ids, 'comparison_category' );
    } else {
        wp_set_post_terms( $post_id, array(), 'comparison_category' );
    }

    // Save Primary Categories
    if ( isset( $_POST['wpc_primary_cats'] ) ) {
        $primary_ids = array_map( 'intval', $_POST['wpc_primary_cats'] );
        // Limit to 2? No, let's just save input and limit on frontend if needed, but UI says Max 2.
        update_post_meta( $post_id, '_wpc_primary_cats', $primary_ids );
    } else {
        delete_post_meta( $post_id, '_wpc_primary_cats' );
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
    if ( isset( $_POST['wpc_features'] ) ) {
        $feature_ids = array_map( 'intval', $_POST['wpc_features'] );
        wp_set_post_terms( $post_id, $feature_ids, 'comparison_feature' );
    } else {
        // If empty, clear terms
        wp_set_post_terms( $post_id, array(), 'comparison_feature' );
    }
}
add_action( 'save_post', 'wpc_save_meta_box' );

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
            echo '<span style="color: #f59e0b; font-weight: bold;">★ ' . esc_html($rating) . '</span>';
            break;
        case 'type':
            $terms = get_the_terms($post_id, 'comparison_category');
            if ($terms && !is_wp_error($terms)) {
                $names = wp_list_pluck($terms, 'name');
                echo implode(', ', $names);
            } else {
                echo '—';
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
        'post_status' => 'publish'
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
