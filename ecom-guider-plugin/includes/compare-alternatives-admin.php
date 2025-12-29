<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add Compare Alternatives Admin Page
 */
add_action( 'admin_menu', 'wpc_add_compare_menu' );
function wpc_add_compare_menu() {
    add_submenu_page(
        'edit.php?post_type=comparison_item',
        __( 'Compare Alternatives', 'wp-comparison-builder' ),
        __( 'Compare Alternatives', 'wp-comparison-builder' ),
        'manage_options',
        'wpc-compare-alternatives',
        'wpc_compare_alternatives_page'
    );
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

    // Get all items
    $items = get_posts( array(
        'post_type' => 'comparison_item',
        'posts_per_page' => -1,
        'post_status' => array( 'publish', 'draft' ),
        'orderby' => 'title',
        'order' => 'ASC'
    ));

    ?>
    <div class="wrap">
        <h1><?php _e( 'Compare Alternatives Settings', 'wp-comparison-builder' ); ?></h1>
        <p><?php _e( 'Configure competitor alternatives for each item. You can set default competitors or save custom comparison sets with specific names.', 'wp-comparison-builder' ); ?></p>

        <!-- Main Search Bar -->
        <div style="margin: 20px 0; padding: 15px; background: #f0f6ff; border-radius: 8px; border: 1px solid #c3c4c7;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <label for="wpc-main-search" style="font-weight: 600; white-space: nowrap;">
                    🔍 <?php _e( 'Search Items:', 'wp-comparison-builder' ); ?>
                </label>
                <input 
                    type="text" 
                    id="wpc-main-search" 
                    placeholder="<?php _e( 'Type to filter items...', 'wp-comparison-builder' ); ?>"
                    style="flex: 1; max-width: 400px; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 14px;"
                    autocomplete="off"
                />
                <span id="wpc-search-count" style="color: #666; font-size: 13px;"></span>
                <button type="button" id="wpc-clear-search" class="button" style="display: none;">
                    <?php _e( 'Clear', 'wp-comparison-builder' ); ?>
                </button>
            </div>
        </div>

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
                        <?php foreach ( $items as $item ) : 
                            $selected_competitors = get_post_meta( $item->ID, '_wpc_competitors', true );
                            if ( ! is_array( $selected_competitors ) ) {
                                $selected_competitors = array();
                            }
                            
                            // Get other items (exclude current)
                            $other_items = array_filter( $items, function( $p ) use ( $item ) {
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
                                        onclick="this.select(); document.execCommand('copy'); alert('Copied!');"
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
                            </td>
                            
                            <!-- Competitors Selection Column -->
                            <td>
                                <!-- Per-row alternatives search -->
                                <div style="margin-bottom: 10px;">
                                    <input 
                                        type="text" 
                                        class="wpc-alt-search" 
                                        data-item-id="<?php echo $item->ID; ?>"
                                        placeholder="🔍 Search alternatives..."
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
                                    <div class="competitors-list" id="competitors-list-<?php echo $item->ID; ?>" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 8px; padding: 10px; background: #f9f9f9; border-radius: 4px; max-height: 200px; overflow-y: auto;">
                                        <?php foreach ( $other_items as $alt ) : ?>
                                            <label style="display: flex; align-items: center; gap: 6px; font-size: 12px;">
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
                                    💾 Save Set
                                </button>
                                <button 
                                    type="button" 
                                    class="button button-small"
                                    onclick="document.getElementById('shortcode-<?php echo $item->ID; ?>').select(); document.execCommand('copy'); alert('Shortcode copied!');"
                                    style="width: 100%;">
                                    📋 Copy Code
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
                                    <strong style="display: block; margin-bottom: 8px; color: #2271b1;">📁 Saved Comparison Sets:</strong>
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
                                                        value='[wpc_compare_button id="<?php echo $item->ID; ?>" competitors="<?php echo esc_attr( $set->competitor_ids ); ?>"<?php if (!empty($set->button_text) && $set->button_text !== 'Compare Alternatives') echo ' text="' . esc_attr($set->button_text) . '"'; ?>]'
                                                        readonly 
                                                        onclick="this.select(); document.execCommand('copy'); alert('Copied!');"
                                                        style="width: 100%; padding: 4px; font-family: monospace; font-size: 10px; border: 1px solid #ddd; border-radius: 3px; background: #f9f9f9;"
                                                    />
                                                </td>
                                                <td style="padding: 10px; width: 90px; text-align: right;">
                                                    <button 
                                                        type="button" 
                                                        class="button button-small"
                                                        onclick="editComparisonSet(<?php echo $set->id; ?>, <?php echo $item->ID; ?>, '<?php echo esc_js($set->set_name); ?>', '<?php echo esc_attr($set->competitor_ids); ?>', '<?php echo esc_js($set->button_text); ?>')"
                                                        style="color: #2271b1; margin-right: 4px;"
                                                        title="Edit">
                                                        ✏️
                                                    </button>
                                                    <button 
                                                        type="button" 
                                                        class="button button-small button-link-delete"
                                                        onclick="deleteComparisonSet(<?php echo $set->id; ?>, this)"
                                                        style="color: #b32d2e;"
                                                        title="Delete">
                                                        🗑️
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
    // Main Search Functionality
    (function() {
        const mainSearch = document.getElementById('wpc-main-search');
        const searchCount = document.getElementById('wpc-search-count');
        const clearBtn = document.getElementById('wpc-clear-search');
        const allItemRows = document.querySelectorAll('tr[data-item-id]');
        const savedSetRows = document.querySelectorAll('tr.saved-sets-row');
        
        function updateMainSearch() {
            const query = mainSearch.value.toLowerCase().trim();
            let visibleCount = 0;
            
            clearBtn.style.display = query ? 'inline-block' : 'none';
            
            allItemRows.forEach((row, index) => {
                const itemName = row.querySelector('td:first-child strong');
                if (!itemName) return;
                
                const name = itemName.textContent.toLowerCase();
                const matches = !query || name.includes(query);
                
                row.style.display = matches ? '' : 'none';
                
                // Also hide/show the corresponding saved sets row
                if (savedSetRows[index]) {
                    savedSetRows[index].style.display = matches ? '' : 'none';
                }
                
                if (matches) visibleCount++;
            });
            
            searchCount.textContent = query ? `Showing ${visibleCount} of ${allItemRows.length} items` : '';
        }
        
        mainSearch.addEventListener('input', updateMainSearch);
        clearBtn.addEventListener('click', function() {
            mainSearch.value = '';
            updateMainSearch();
            mainSearch.focus();
        });
        
        // Per-row alternatives search
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
       
        let competitorIds = [];
        checkboxes.forEach(cb => competitorIds.push(cb.value));
        
        const buttonText = buttonTextInput ? buttonTextInput.value.trim() : '';
        
        let shortcodeParts = ['[wpc_compare_button id="' + itemId + '"'];
        
        if (selectAll && selectAll.checked) {
            // When select all is checked, potentially don't specify competitors or handle differently
        } else if (competitorIds.length > 0) {
            shortcodeParts.push(' competitors="' + competitorIds.join(',') + '"');
        }
        
        if (buttonText !== '') {
            shortcodeParts.push(' text="' + buttonText.replace(/"/g, '&quot;') + '"');
        }
        
        shortcodeParts.push(']');
        shortcodeInput.value = shortcodeParts.join('');
    }
    
    // Toggle Select All checkbox
    function toggleSelectAll(itemId) {
        const selectAllCheckbox = document.getElementById('select-all-' + itemId);
        const competitorCheckboxes = document.querySelectorAll('.competitor-' + itemId);
        const competitorsList = document.getElementById('competitors-list-' + itemId);
        
        if (selectAllCheckbox.checked) {
            // Check all competitors
            competitorCheckboxes.forEach(cb => cb.checked = true);
            // Optionally hide the list
            if (competitorsList) {
                competitorsList.style.opacity = '0.5';
            }
        } else {
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
    
    function editComparisonSet(setId, itemId, setName, competitorIds, buttonText) {
        // Store the set ID we're editing
        currentEditingSetId = setId;
        
        // Populate the set name field
        const setNameInput = document.getElementById('set-name-' + itemId);
        setNameInput.value = setName;
        
        // Populate button text field
        const buttonTextInput = document.getElementById('button-text-' + itemId);
        buttonTextInput.value = buttonText || '';
        
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
            saveButton.innerHTML = '💾 Update Set';
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
            alert('Please enter a name for this comparison set');
            return;
        }
        
        const competitorIds = Array.from(checkboxes).map(cb => cb.value).join(',');
        if (!competitorIds) {
            alert('Please select at least one item');
            return;
        }
        
        const buttonText = buttonTextInput.value.trim() || 'Compare Alternatives';
        
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
        
        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(isEditing ? 'Comparison set updated successfully!' : 'Comparison set saved successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.data || 'Failed to save'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }
    
    // Delete Comparison Set via AJAX
    function deleteComparisonSet(setId, button) {
        if (!confirm('Are you sure you want to delete this comparison set?')) {
            return;
        }
        
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
            if (data.success) {
                button.closest('tr').remove();
            } else {
                alert('Error: ' + (data.data || 'Failed to delete'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
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
