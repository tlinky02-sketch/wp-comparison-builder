<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add Compare Alternatives Admin Page
 */
add_action( 'admin_menu', 'wpc_add_compare_menu' );
function wpc_add_compare_menu() {
    $hook = add_submenu_page(
        'edit.php?post_type=comparison_item',
        __( 'Compare Alternatives', 'wp-comparison-builder' ),
        __( 'Compare Alternatives', 'wp-comparison-builder' ),
        'manage_options',
        'wpc-compare-alternatives',
        'wpc_compare_alternatives_page'
    );
    
    // Add Screen Options
    add_action( 'load-' . $hook, 'wpc_compare_alternatives_screen_options' );
}

/**
 * Add Screen Options for Compare Alternatives Page
 */
function wpc_compare_alternatives_screen_options() {
    $option = 'per_page';
    $args = array(
        'label'   => __( 'Items per page', 'wp-comparison-builder' ),
        'default' => 15,
        'option'  => 'wpc_compare_items_per_page'
    );
    add_screen_option( $option, $args );
}

/**
 * Save Screen Options
 */
add_filter( 'set-screen-option', 'wpc_compare_set_screen_option', 10, 3 );
function wpc_compare_set_screen_option( $status, $option, $value ) {
    if ( 'wpc_compare_items_per_page' === $option ) {
        return $value;
    }
    return $status;
}

/**
 * Render Compare Alternatives Admin Page
 */
function wpc_compare_alternatives_page() {
    // Handle form submission for default competitors
    if ( isset( $_POST['save_competitors'] ) && check_admin_referer( 'save_competitors_nonce' ) ) {
        if ( isset( $_POST['competitors'] ) && is_array( $_POST['competitors'] ) ) {
            foreach ( $_POST['competitors'] as $item_id => $competitor_ids ) {
                $item_id = intval( $item_id );
                $competitor_ids = array_map( 'intval', $competitor_ids );
                update_post_meta( $item_id, '_wpc_competitors', $competitor_ids );
            }
            echo '<div class="notice notice-success"><p>Default competitor settings saved successfully!</p></div>';
        }
    }

    // Pagination Setup - Get user's screen option preference
    $user_id = get_current_user_id();
    $per_page = get_user_meta( $user_id, 'wpc_compare_items_per_page', true );
    if ( empty( $per_page ) || $per_page < 1 ) {
        $per_page = 15; // Default
    }
    $paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
    
    // Server-Side Search
    $search_query = isset( $_GET['wpc_search'] ) ? sanitize_text_field( $_GET['wpc_search'] ) : '';
    
    // Main Query
    $query_args = array(
        'post_type' => 'comparison_item',
        'posts_per_page' => $per_page,
        'paged' => $paged,
        'post_status' => array( 'publish', 'draft' ),
        'orderby' => 'title',
        'order' => 'ASC'
    );
    
    // Add search to query if present
    if ( ! empty( $search_query ) ) {
        $query_args['s'] = $search_query;
    }
    
    $query = new WP_Query( $query_args );
    $items = $query->posts;
    $total_items = $query->found_posts;
    $total_pages = $query->max_num_pages;

    ?>
    <!-- Premium UI Helpers (Injected) -->
    <style>
        .wpc-modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; display: none; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
        .wpc-modal { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); width: 90%; max-width: 450px; animation: wpcSlideDown 0.2s ease-out; border: 1px solid #e2e8f0; }
        .wpc-modal h3 { margin-top: 0; color: #0f172a; font-size: 20px; font-weight: 600; margin-bottom: 10px; }
        .wpc-modal p { color: #64748b; font-size: 15px; line-height: 1.6; margin-bottom: 25px; }
        .wpc-modal-actions { display: flex; justify-content: flex-end; gap: 12px; }
        .wpc-modal-actions .button { padding: 6px 16px; height: auto; font-size: 14px; }
        .wpc-modal-actions .button-primary { background: #6366f1; border-color: #6366f1; transition: all 0.2s; }
        .wpc-modal-actions .button-primary:hover { background: #4f46e5; border-color: #4f46e5; }
        
        .wpc-toast { position: fixed; top: 32px; left: 50%; transform: translateX(-50%); background: #fff; border-left: 5px solid #10b981; padding: 16px 24px; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); z-index: 10001; font-weight: 500; font-size: 14px; color: #334155; animation: wpcSlideDown 0.3s ease-out; display: none; align-items: center; gap: 12px; max-width: 400px; width: 90%; }
        .wpc-toast.error { border-left-color: #ef4444; }
        .wpc-toast-icon { font-size: 18px; }
        
        @keyframes wpcSlideDown { from { opacity: 0; transform: translate(-50%, -20px); } to { opacity: 1; transform: translate(-50%, 0); } }
        
        /* Loading Spinner */
        .wpc-spinner-icon { width: 16px; height: 16px; border: 2px solid #e2e8f0; border-top-color: currentColor; border-radius: 50%; animation: wpcSpin 0.6s linear infinite; display: inline-block; vertical-align: middle; margin-right: 8px; }
        @keyframes wpcSpin { to { transform: rotate(360deg); } }
        
        /* Pagination Styles */
        .wpc-pagination { margin-top: 20px; text-align: center; }
        .wpc-pagination a, .wpc-pagination span { display: inline-block; padding: 6px 12px; margin: 0 4px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #2271b1; background: #fff; }
        .wpc-pagination span.current { background: #2271b1; color: #fff; border-color: #2271b1; font-weight: bold; }
        .wpc-pagination a:hover { background: #f0f6ff; }
    </style>
    
    <div id="wpc-premium-modal" class="wpc-modal-overlay">
        <div class="wpc-modal">
            <h3 id="wpc-modal-title">Confirm Action</h3>
            <p id="wpc-modal-message">Are you sure you want to proceed?</p>
            <div class="wpc-modal-actions">
                <button id="wpc-modal-cancel-btn" class="button">Cancel</button>
                <button id="wpc-modal-confirm-btn" class="button button-primary">Confirm</button>
            </div>
        </div>
    </div>
    
    <div id="wpc-toast" class="wpc-toast">
        <span class="wpc-toast-icon">&#x2713;</span>
        <span id="wpc-toast-message">Operation successful</span>
    </div>
    
    <script>
    window.wpcAdmin = {
        toast: function(msg, type='success') {
            var t = document.getElementById('wpc-toast');
            var m = document.getElementById('wpc-toast-message');
            var i = t.querySelector('.wpc-toast-icon');
            
            // Handle 3 arguments (type, title, msg) usage vs 2 arguments
            if (arguments.length === 3) {
                 type = arguments[0];
                 // arguments[1] is title (ignored for now in this simple toast)
                 msg = arguments[2];
            }
            
            t.className = 'wpc-toast ' + type;
            m.innerText = msg;
            i.innerHTML = type === 'success' ? '&#x2713;' : '&#x26A0;&#xFE0F;';
            i.style.color = type === 'success' ? '#10b981' : '#ef4444';
            
            t.style.display = 'flex';
            setTimeout(function() {
                t.style.display = 'none';
            }, 4000);
        },
        
        confirm: function(title, msg, confirmText='Confirm', confirmColor='#6366f1') {
            return new Promise((resolve) => {
                var modal = document.getElementById('wpc-premium-modal');
                document.getElementById('wpc-modal-title').innerText = title;
                document.getElementById('wpc-modal-message').innerHTML = msg; 
                
                var btn = document.getElementById('wpc-modal-confirm-btn');
                btn.innerText = confirmText;
                if (confirmColor) {
                    btn.style.background = confirmColor;
                    btn.style.borderColor = confirmColor;
                } else {
                    btn.style.background = ''; // reset to class default
                    btn.style.borderColor = '';
                }
                
                var newBtn = btn.cloneNode(true);
                btn.parentNode.replaceChild(newBtn, btn);
                
                newBtn.addEventListener('click', function() {
                    modal.style.display = 'none';
                    resolve(true);
                });
                
                var cancelBtn = document.getElementById('wpc-modal-cancel-btn');
                var newCancel = cancelBtn.cloneNode(true);
                cancelBtn.parentNode.replaceChild(newCancel, cancelBtn);
                
                newCancel.addEventListener('click', function() {
                    modal.style.display = 'none';
                    resolve(false);
                });
                
                modal.style.display = 'flex';
            });
        },
        
        loading: function(btn, isLoading=true, text='Processing...') {
           if (!btn) return;
           if (isLoading) {
               btn.dataset.originalText = btn.innerText;
               btn.disabled = true;
               btn.innerHTML = '<span class="wpc-spinner-icon"></span> ' + text;
           } else {
               btn.disabled = false;
               btn.innerText = btn.dataset.originalText || 'Save';
           }
        }
    };
    </script>

    <div class="wrap">
        <h1><?php _e( 'Compare Alternatives Settings', 'wp-comparison-builder' ); ?></h1>
        <p><?php _e( 'Configure competitor alternatives for each item. You can set default competitors or save custom comparison sets with specific names.', 'wp-comparison-builder' ); ?></p>

        <!-- Server-Side Search Bar -->
        <form method="get" action="" style="margin: 20px 0; padding: 15px; background: #f0f6ff; border-radius: 8px; border: 1px solid #c3c4c7;">
            <input type="hidden" name="post_type" value="comparison_item" />
            <input type="hidden" name="page" value="wpc-compare-alternatives" />
            <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                <label for="wpc-main-search" style="font-weight: 600; white-space: nowrap;">
                    &#128269; <?php _e( 'Search Items:', 'wp-comparison-builder' ); ?>
                </label>
                <input 
                    type="text" 
                    id="wpc-main-search" 
                    name="wpc_search"
                    value="<?php echo esc_attr( $search_query ); ?>"
                    placeholder="<?php _e( 'Search all items...', 'wp-comparison-builder' ); ?>"
                    style="flex: 1; max-width: 400px; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 14px;"
                    autocomplete="off"
                />
                <button type="submit" class="button button-primary">
                    <?php _e( 'Search', 'wp-comparison-builder' ); ?>
                </button>
                <?php if ( ! empty( $search_query ) ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=comparison_item&page=wpc-compare-alternatives' ) ); ?>" class="button">
                        <?php _e( 'Clear', 'wp-comparison-builder' ); ?>
                    </a>
                    <span style="color: #666; font-size: 13px;">
                        <?php printf( __( 'Showing %d results for "%s"', 'wp-comparison-builder' ), $total_items, esc_html( $search_query ) ); ?>
                    </span>
                <?php else : ?>
                    <span style="color: #666; font-size: 13px;">
                        <?php printf( __( 'Total: %d items', 'wp-comparison-builder' ), $total_items ); ?>
                    </span>
                <?php endif; ?>
            </div>
        </form>

        <?php wp_nonce_field( 'wpc_comparison_nonce', 'wpc_comparison_nonce_field' ); ?>

        <form method="post" action="">
            <?php wp_nonce_field( 'save_competitors_nonce' ); ?>

            <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th style="width: 150px;"><?php _e( 'Item', 'wp-comparison-builder' ); ?></th>
                        <th style="width: 300px;"><?php _e( 'Shortcode Generator', 'wp-comparison-builder' ); ?></th>
                        <th><?php _e( 'Select Alternatives', 'wp-comparison-builder' ); ?></th>
                        <th style="width: 150px;"><?php _e( 'Actions', 'wp-comparison-builder' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $items ) ) : ?>
                        <?php 
                        // Perform a single query for ALL items to populate the choice list
                        // This ensures that the "Select Alternatives" dropdown has all possible items,
                        // regardless of the current pagination.
                        if (!isset($all_competitor_candidates)) {
                            $all_competitor_candidates = get_posts(array(
                                'post_type' => 'comparison_item',
                                'posts_per_page' => -1,
                                'post_status' => 'publish',
                                'orderby' => 'title',
                                'order' => 'ASC'
                            ));
                        }
                        ?>
                        <?php foreach ( $items as $item ) : 
                            $selected_competitors = get_post_meta( $item->ID, '_wpc_competitors', true );
                            if ( ! is_array( $selected_competitors ) ) {
                                $selected_competitors = array();
                            }
                            
                            // Get all PUBLISHED items for alternatives list (not just this page)
                            // We need a lightweight way to get ALL potential competitors, 
                            // but loading ALL posts inside loop is bad.
                            // Optimization: Fetch ALL ID/Titles ONCE at top if list is small, or use AJAX for searching?
                            // For now, let's keep the existing logic but optimize if possible.
                            // Actually, fetching all posts per row is N+1 query problem.
                            // Let's optimize: fetch all IDs and titles once above loop.
                            
                            // Re-using logic:
                            // We need to exclude current item from its own list.
                            
                            // For this "Select Alternatives" box, user wants scroll.
                            // We essentially need all other items here.
                            
                             // Perform a single query for ALL items to populate the choice list
                            if (!isset($all_competitor_candidates)) {
                                $all_competitor_candidates = get_posts(array(
                                    'post_type' => 'comparison_item',
                                    'posts_per_page' => -1,
                                    'post_status' => 'publish',
                                    'orderby' => 'title',
                                    'order' => 'ASC'
                                ));
                            }
                            
                            $other_items = array_filter( $all_competitor_candidates, function( $p ) use ( $item ) {
                                return $p->ID !== $item->ID;
                            });
                            
                            // Get all item IDs for "select all" functionality
                            $all_item_ids = array_map( function($p) { return $p->ID; }, $other_items );
                        ?>
                        <tr data-item-id="<?php echo $item->ID; ?>">
                            <td>
                                <strong><?php echo esc_html( $item->post_title ); ?></strong>
                                <?php if ( $item->post_status !== 'publish' ) : ?>
                                    <br><span style="color: #999; font-size: 11px;"><?php echo ucfirst( $item->post_status ); ?></span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Shortcode Generator Column -->
                            <td>
                                <div style="margin-bottom: 10px;">
                                    <input 
                                        type="text" 
                                        value='[wpc_compare_button id="<?php echo $item->ID; ?>"]' 
                                        readonly 
                                        id="shortcode-<?php echo $item->ID; ?>"
                                        data-item-id="<?php echo $item->ID; ?>"
                                        onclick="this.select(); document.execCommand('copy'); wpcAdmin.toast('success', 'Copied!', 'Shortcode copied to clipboard');"
                                        style="width: 100%; padding: 6px; font-family: monospace; font-size: 11px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;"
                                    />
                                </div>
                                
                                <!-- Set Name Input -->
                                <input 
                                    type="text" 
                                    id="set-name-<?php echo $item->ID; ?>"
                                    placeholder="Set name (e.g., Premium Alternatives)"
                                    style="width: 100%; padding: 5px; font-size: 11px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 5px;"
                                />
                                
                                <!-- Button Text Input -->
                                <input 
                                    type="text" 
                                    id="button-text-<?php echo $item->ID; ?>"
                                    placeholder="Button text (optional)"
                                    onkeyup="updateShortcode(<?php echo $item->ID; ?>)"
                                    style="width: 100%; padding: 5px; font-size: 11px; border: 1px solid #ddd; border-radius: 4px;"
                                />
                                
                                <!-- Display Mode Toggle -->
                                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #ddd;">
                                    <span style="font-size: 11px; font-weight: 700; color: #444; margin-right: 8px;">Display Mode:</span>
                                    <label style="font-size: 11px; margin-right: 10px; cursor: pointer;">
                                        <input type="radio" name="display_mode_<?php echo $item->ID; ?>" value="button" checked onchange="updateShortcode(<?php echo $item->ID; ?>)"> Button
                                    </label>
                                    <label style="font-size: 11px; cursor: pointer;">
                                        <input type="radio" name="display_mode_<?php echo $item->ID; ?>" value="table" onchange="updateShortcode(<?php echo $item->ID; ?>)"> Table
                                    </label>
                                </div>
                            </td>
                            
                            <!-- Competitors Selection Column -->
                            <td>
                                <!-- Per-row alternatives search -->
                                <div style="margin-bottom: 10px;">
                                    <input 
                                        type="text" 
                                        class="wpc-alt-search" 
                                        data-item-id="<?php echo $item->ID; ?>"
                                        placeholder="&#128269; Search alternatives..."
                                        style="width: 100%; padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px;"
                                        autocomplete="off"
                                    />
                                </div>
                                
                                <div style="margin-bottom: 8px;">
                                    <label style="font-weight: 600; display: flex; align-items: center; gap: 6px;">
                                        <input 
                                            type="checkbox" 
                                            id="select-all-<?php echo $item->ID; ?>"
                                            data-item-id="<?php echo $item->ID; ?>"
                                            data-all-ids="<?php echo esc_attr( implode(',', $all_item_ids) ); ?>"
                                            onchange="toggleSelectAll(<?php echo $item->ID; ?>)"
                                        />
                                        Select All Items
                                    </label>
                                </div>
                                
                                <?php if ( ! empty( $other_items ) ) : ?>
                                    <!-- Use max-height: 150px for compact scrollable list -->
                                    <div class="competitors-list" id="competitors-list-<?php echo $item->ID; ?>" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 4px 12px; padding: 10px; background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; max-height: 150px; overflow-y: auto;">
                                        <?php foreach ( $other_items as $alt ) : ?>
                                            <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; padding: 2px 0;">
                                                <input 
                                                    type="checkbox" 
                                                    name="competitors[<?php echo $item->ID; ?>][]" 
                                                    class="competitor-checkbox competitor-<?php echo $item->ID; ?>"
                                                    value="<?php echo $alt->ID; ?>"
                                                    data-item-id="<?php echo $item->ID; ?>"
                                                    onchange="updateShortcode(<?php echo $item->ID; ?>); updateSelectAllState(<?php echo $item->ID; ?>);"
                                                    <?php checked( in_array( $alt->ID, $selected_competitors ) ); ?>
                                                />
                                                <?php echo esc_html( $alt->post_title ); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else : ?>
                                    <em style="color: #999; font-size: 11px;">No other items available</em>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Actions Column -->
                            <td>
                                <button 
                                    type="button" 
                                    class="button button-primary button-small"
                                    onclick="saveComparisonSet(<?php echo $item->ID; ?>)"
                                    style="width: 100%; margin-bottom: 5px;">
                                    &#128190; Save Set
                                </button>
                                <button 
                                    type="button" 
                                    class="button button-small"
                                    onclick="document.getElementById('shortcode-<?php echo $item->ID; ?>').select(); document.execCommand('copy'); wpcAdmin.toast('success', 'Copied!', 'Shortcode copied to clipboard');"
                                    style="width: 100%;">
                                    &#128203; Copy Code
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Saved Sets for this Item -->
                        <?php 
                        // NOTE: wpc_get_saved_sets must be defined in comparison-sets-db.php
                        if ( function_exists('wpc_get_saved_sets') ) {
                            $saved_sets = wpc_get_saved_sets( $item->ID );
                        } else {
                            $saved_sets = array();
                        }

                        if ( ! empty( $saved_sets ) ) : ?>
                            <tr class="saved-sets-row">
                                <td colspan="4" style="padding: 0 12px 20px 40px; background: #f0f6ff;">
                                    <strong style="display: block; margin-bottom: 8px; color: #2271b1;">&#128204; Saved Comparison Sets:</strong>
                                    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 4px; overflow: hidden;">
                                        <?php foreach ( $saved_sets as $set ) : 
                                            // Split and filter out empty strings
                                            $comp_ids = array_filter(explode(',', $set->competitor_ids));
                                            
                                            $set_competitors = !empty($comp_ids) ? get_posts( array(
                                                'post_type' => 'comparison_item',
                                                'post__in' => $comp_ids,
                                                'posts_per_page' => -1
                                            )) : array();
                                            
                                            $competitor_names = array_map( function($p) { return $p->post_title; }, $set_competitors );
                                        ?>
                                            <tr style="border-bottom: 1px solid #f0f0f0;" data-set-id="<?php echo $set->id; ?>">
                                                <td style="padding: 10px; font-weight: 600; width: 200px;"><?php echo esc_html( $set->set_name ); ?></td>
                                                <td style="padding: 10px; font-size: 11px; color: #666;">
                                                    <?php echo esc_html( implode( ', ', $competitor_names ) ); ?>
                                                </td>
                                                <td style="padding: 10px; width: 300px;">
                                                    <input 
                                                        type="text" 
                                                        value='[wpc_compare_button id="<?php echo $item->ID; ?>" competitors="<?php echo esc_attr( $set->competitor_ids ); ?>"<?php if (!empty($set->button_text) && $set->button_text !== 'Compare Alternatives') echo ' text="' . esc_attr($set->button_text) . '"'; ?><?php if (isset($set->display_mode) && $set->display_mode === 'table') echo ' mode="table"'; ?>]'
                                                        readonly 
                                                        onclick="this.select(); document.execCommand('copy'); wpcAdmin.toast('success', 'Copied!', 'Shortcode copied to clipboard');"
                                                        style="width: 100%; padding: 4px; form-family: monospace; font-size: 10px; border: 1px solid #ddd; border-radius: 3px; background: #f9f9f9;"
                                                    />
                                                    <?php if (isset($set->display_mode) && $set->display_mode === 'table'): ?>
                                                        <span style="font-size: 9px; padding: 2px 4px; background: #e0e7ff; color: #4338ca; border-radius: 4px; font-weight: 600; text-transform: uppercase; margin-top: 4px; display: inline-block;">Table Mode</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="padding: 10px; width: 90px; text-align: right;">
                                                    <button 
                                                        type="button" 
                                                        class="button button-small"
                                                        onclick="editComparisonSet(<?php echo $set->id; ?>, <?php echo $item->ID; ?>, '<?php echo esc_js($set->set_name); ?>', '<?php echo esc_attr($set->competitor_ids); ?>', '<?php echo esc_js($set->button_text); ?>', '<?php echo esc_js($set->display_mode ?? 'button'); ?>')"
                                                        style="color: #2271b1; margin-right: 4px;"
                                                        title="Edit">
                                                        &#x270F;&#xFE0F;
                                                    </button>
                                                    <button 
                                                        type="button" 
                                                        class="button button-small button-link-delete"
                                                        onclick="deleteComparisonSet(<?php echo $set->id; ?>, this, event)"
                                                    >
                                                        &#x274C;
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </table>
                                </td>
                            </tr>
                        <?php endif; ?>
                        
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4">
                                <p style="text-align: center; padding: 40px 0; color: #999;">
                                    <?php _e( 'No items found. Please add some items first.', 'wp-comparison-builder' ); ?>
                                </p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Pagination Controls -->
            <?php if ( $total_pages > 1 ) : ?>
                <div class="wpc-pagination">
                    <?php
                    echo paginate_links( array(
                        'base' => add_query_arg( 'paged', '%#%' ),
                        'format' => '',
                        'current' => $paged,
                        'total' => $total_pages,
                        'prev_text' => '&laquo; Previous',
                        'next_text' => 'Next &raquo;',
                    ) );
                    ?>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $items ) ) : ?>
                <p class="submit">
                    <button type="submit" name="save_competitors" class="button button-secondary button-large">
                        <?php _e( 'Save Default Competitor Settings', 'wp-comparison-builder' ); ?>
                    </button>
                </p>
            <?php endif; ?>
        </form>

        <div style="margin-top: 40px; padding: 20px; background: #f0f6ff; border-left: 4px solid #0d9488; border-radius: 4px;">
            <h3 style="margin-top: 0;"><?php _e( 'How to Use', 'wp-comparison-builder' ); ?></h3>
            <ol>
                <li><strong>Select alternatives</strong> for each item using checkboxes (or use "Select All")</li>
                <li><strong>Optional:</strong> Enter a set name and save it for future reference</li>
                <li><strong>Copy the shortcode</strong> and paste it on any page</li>
                <li>Saved sets appear below each item with their own shortcodes</li>
            </ol>
        </div>
    </div>
    
    <script>
    // Per-row alternatives search (client-side filter within each item's competitor list)
    (function() {
        document.querySelectorAll('.wpc-alt-search').forEach(searchInput => {
            searchInput.addEventListener('input', function() {
                const itemId = this.dataset.itemId;
                const query = this.value.toLowerCase().trim();
                const competitorsList = document.getElementById('competitors-list-' + itemId);
                
                if (!competitorsList) return;
                
                const labels = competitorsList.querySelectorAll('label');
                labels.forEach(label => {
                    const text = label.textContent.toLowerCase();
                    label.style.display = (!query || text.includes(query)) ? '' : 'none';
                });
            });
        });
    })();
    
    // Update shortcode based on selected competitors and button text
    function updateShortcode(itemId) {
        const checkboxes = document.querySelectorAll('.competitor-' + itemId + ':checked');
        const selectAll = document.getElementById('select-all-' + itemId);
        const buttonTextInput = document.getElementById('button-text-' + itemId);
        const shortcodeInput = document.getElementById('shortcode-' + itemId);
       
        const modeRadio = document.querySelector('input[name="display_mode_' + itemId + '"]:checked');
        const mode = modeRadio ? modeRadio.value : 'button';
        
        let shortcodeParts = ['[wpc_compare_button id="' + itemId + '"'];
        
        if (selectAll && selectAll.checked) {
            // When select all is checked, potentially don't specify competitors or handle differently
        } else if (competitorIds.length > 0) {
            shortcodeParts.push(' competitors="' + competitorIds.join(',') + '"');
        }
        
        if (buttonText !== '' && buttonText !== 'Compare Alternatives') {
            shortcodeParts.push(' text="' + buttonText.replace(/"/g, '&quot;') + '"');
        }
        
        if (mode === 'table') {
            shortcodeParts.push(' mode="table"');
        }
        
        shortcodeParts.push(']');
        shortcodeInput.value = shortcodeParts.join('');
    }
    
    // Toggle Select All checkbox (Check all / Uncheck all)
    function toggleSelectAll(itemId) {
        const selectAllCheckbox = document.getElementById('select-all-' + itemId);
        const competitorCheckboxes = document.querySelectorAll('.competitor-' + itemId);
        const competitorsList = document.getElementById('competitors-list-' + itemId);
        
        if (selectAllCheckbox.checked) {
            // Check all competitors
            competitorCheckboxes.forEach(cb => cb.checked = true);
            if (competitorsList) {
                competitorsList.style.opacity = '0.5';
            }
        } else {
            // Uncheck all competitors
            competitorCheckboxes.forEach(cb => cb.checked = false);
            if (competitorsList) {
                competitorsList.style.opacity = '1';
            }
        }
        
        updateShortcode(itemId);
    }
    
    // Update Select All state based on individual checkboxes
    function updateSelectAllState(itemId) {
        const selectAllCheckbox = document.getElementById('select-all-' + itemId);
        const competitorCheckboxes = document.querySelectorAll('.competitor-' + itemId);
        const total = competitorCheckboxes.length;
        const checked = document.querySelectorAll('.competitor-' + itemId + ':checked').length;
        
        if (checked === total && total > 0) {
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.checked = false;
        }
    }
    
    // Edit Comparison Set - Populate form with existing data
    var currentEditingSetId = null;
    
    function editComparisonSet(setId, itemId, setName, competitorIds, buttonText, displayMode) {
        // Store the set ID we're editing
        currentEditingSetId = setId;
        
        // Populate the set name field
        const setNameInput = document.getElementById('set-name-' + itemId);
        setNameInput.value = setName;
        
        // Populate button text field
        const buttonTextInput = document.getElementById('button-text-' + itemId);
        buttonTextInput.value = buttonText || '';
        
        // Populate display mode
        const modeRadio = document.querySelector('input[name="display_mode_' + itemId + '"][value="' + (displayMode || 'button') + '"]');
        if (modeRadio) modeRadio.checked = true;
        
        // Uncheck all competitors first
        const competitorCheckboxes = document.querySelectorAll('.competitor-' + itemId);
        competitorCheckboxes.forEach(cb => cb.checked = false);
        
        // Check the saved competitors
        const savedIds = competitorIds.split(',');
        savedIds.forEach(id => {
            const checkbox = document.querySelector('.competitor-' + itemId + '[value="' + id + '"]');
            if (checkbox) checkbox.checked = true;
        });
        
        // Update shortcode and select all state
        updateShortcode(itemId);
        updateSelectAllState(itemId);
        
        // Change button text to "Update Set"
        const saveButton = document.querySelector('[onclick*="saveComparisonSet(' + itemId + ')"]');
        if (saveButton) {
            saveButton.innerHTML = '\uD83D\uDCBE Update Set';
            saveButton.style.background = '#2271b1';
            saveButton.style.color = '#fff';
        }
        
        // Scroll to the form
        const row = document.querySelector('[data-item-id="' + itemId + '"]');
        if (row) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        // Highlight the form briefly
        if (setNameInput) {
            setNameInput.focus();
            setNameInput.style.border = '2px solid #2271b1';
            setTimeout(() => {
                setNameInput.style.border = '';
            }, 2000);
        }
    }
    
    // Cancel logic... skipping implementation for brevity as it's just UI reset
    
    // Save Comparison Set via AJAX (handles both create and update)
    function saveComparisonSet(itemId) {
        const setNameInput = document.getElementById('set-name-' + itemId);
        const buttonTextInput = document.getElementById('button-text-' + itemId);
        const checkboxes = document.querySelectorAll('.competitor-' + itemId + ':checked');
        
        const setName = setNameInput.value.trim();
        if (!setName) {
            wpcAdmin.toast('error', 'Validation Error', 'Please enter a name for this comparison set');
            return;
        }
        
        const competitorIds = Array.from(checkboxes).map(cb => cb.value).join(',');
        if (!competitorIds) {
            wpcAdmin.toast('error', 'Validation Error', 'Please select at least one item');
            return;
        }
        
        const buttonText = buttonTextInput.value.trim() || 'Compare Alternatives';
        
        // Get Display Mode
        const modeRadio = document.querySelector('input[name="display_mode_' + itemId + '"]:checked');
        const displayMode = modeRadio ? modeRadio.value : 'button';
        
        const formData = new FormData();
        const isEditing = currentEditingSetId !== null;
        
        formData.append('action', isEditing ? 'wpc_update_comparison_set' : 'wpc_save_comparison_set');
        formData.append('nonce', document.getElementById('wpc_comparison_nonce_field').value);
        
        if (isEditing) {
            formData.append('set_id', currentEditingSetId);
        } else {
            formData.append('item_id', itemId);
        }
        
        formData.append('set_name', setName);
        formData.append('competitor_ids', competitorIds);
        formData.append('button_text', buttonText);
        formData.append('display_mode', displayMode);
        
        // Show loading state on button
        const saveButton = document.querySelector('[onclick*="saveComparisonSet(' + itemId + ')"]');
        if (saveButton) wpcAdmin.loading(saveButton, true);
        
        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (saveButton) wpcAdmin.loading(saveButton, false);
            
            if (data.success) {
                wpcAdmin.toast('success', 'Saved!', isEditing ? 'Comparison set updated successfully!' : 'Comparison set saved successfully!');
                setTimeout(() => location.reload(), 1000);
            } else {
                wpcAdmin.toast('error', 'Save Failed', data.data || 'Failed to save');
            }
        })
        .catch(error => {
            if (saveButton) wpcAdmin.loading(saveButton, false);
            console.error('Error:', error);
            wpcAdmin.toast('error', 'Error', 'An error occurred');
        });
    }
    
    // Delete Comparison Set via AJAX
    function deleteComparisonSet(setId, button, event) {
        // Prevent row selection or other events
        if (event) event.stopPropagation();
        
        wpcAdmin.confirm(
            'Delete Comparison Set?',
            'Are you sure you want to delete this comparison set? This action cannot be undone.',
            'Delete',
            null
        ).then((isConfirmed) => {
            if (!isConfirmed) return;
            
            // Show simple loading state (opacity) on button
            button.style.opacity = '0.5';
            
            const formData = new FormData();
            formData.append('action', 'wpc_delete_comparison_set');
            formData.append('nonce', document.getElementById('wpc_comparison_nonce_field').value);
            formData.append('set_id', setId);
            
            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                button.style.opacity = '1';
                if (data.success) {
                    wpcAdmin.toast('success', 'Deleted', 'Comparison set deleted.');
                    button.closest('tr').remove();
                } else {
                    wpcAdmin.toast('error', 'Delete Failed', data.data || 'Failed to delete');
                }
            })
            .catch(error => {
                button.style.opacity = '1';
                console.error('Error:', error);
                wpcAdmin.toast('error', 'Error', 'An error occurred');
            });
        });
    }
    
    // Initialize shortcodes on page load
    document.addEventListener('DOMContentLoaded', function() {
        <?php foreach ( $items as $item ) : ?>
            updateShortcode(<?php echo $item->ID; ?>);
            updateSelectAllState(<?php echo $item->ID; ?>);
        <?php endforeach; ?>
    });
    </script>
    <?php
}
