<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function wpc_render_modules_tab() {
    $tools_enabled = get_option( 'wpc_enable_tools_module', false );
    $variants_enabled = get_option( 'wpc_enable_variants_module', false );
    $variants_selector_style = get_option( 'wpc_variants_selector_style', 'tabs' );
    $variants_show_badge = get_option( 'wpc_variants_show_badge', '1' );
    $variants_remember_selection = get_option( 'wpc_variants_remember_selection', '1' );
    ?>
    <form method="post" action="options.php">
        <?php settings_fields( 'wpc_modules_settings' ); ?>
        <?php do_settings_sections( 'wpc_modules_settings' ); ?>
        
        <h2><?php _e( 'Feature Modules', 'wp-comparison-builder' ); ?></h2>
        <p class="description"><?php _e( 'Enable or disable plugin features to keep your interface clean.', 'wp-comparison-builder' ); ?></p>
        
        <table class="form-table">
            <!-- Recommended Tools Module -->
            <tr valign="top">
                <th scope="row">
                    <label for="wpc_enable_tools_module"><?php _e( 'Recommended Tools', 'wp-comparison-builder' ); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="wpc_enable_tools_module" id="wpc_enable_tools_module" value="1" <?php checked( '1', $tools_enabled ); ?> />
                        <?php _e( 'Enable Recommended Tools Module', 'wp-comparison-builder' ); ?>
                    </label>
                    <p class="description">
                        <?php _e( 'Adds a "Recommended Tools" section with a central tool library, collections, and frontend display capabilities. When enabled, a new menu item will appear under Comparison Items.', 'wp-comparison-builder' ); ?>
                    </p>
                    <?php if ( $tools_enabled ) : ?>
                    <p style="margin-top: 12px;">
                        <a href="<?php echo admin_url( 'edit.php?post_type=comparison_tool' ); ?>" class="button button-secondary">
                            🔧 Manage Tools Library
                        </a>
                    </p>
                    <?php endif; ?>
                </td>
            </tr>
            
            <!-- Product Variants Module -->
            <tr valign="top">
                <th scope="row">
                    <label for="wpc_enable_variants_module"><?php _e( 'Product Variants', 'wp-comparison-builder' ); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="wpc_enable_variants_module" id="wpc_enable_variants_module" value="1" <?php checked( '1', $variants_enabled ); ?> />
                        <?php _e( 'Enable Product Variants Module', 'wp-comparison-builder' ); ?>
                    </label>
                    <p class="description">
                        <?php _e( 'Allows items to have different plans, features, and use cases per category. For example, Hostinger can have separate plans for Cloud Hosting, VPS Hosting, and WordPress Hosting.', 'wp-comparison-builder' ); ?>
                    </p>
                    
                    <?php if ( $variants_enabled ) : ?>
                    <div style="margin-top: 16px; padding: 16px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                        <h4 style="margin: 0 0 12px 0;"><?php _e( 'Variants Module Settings', 'wp-comparison-builder' ); ?></h4>
                        
                        <p>
                            <label for="wpc_variants_selector_style"><strong><?php _e( 'Default Category Selector Style:', 'wp-comparison-builder' ); ?></strong></label><br>
                            <select name="wpc_variants_selector_style" id="wpc_variants_selector_style" style="width: 200px; margin-top: 4px;">
                                <option value="tabs" <?php selected( $variants_selector_style, 'tabs' ); ?>><?php _e( 'Tabs', 'wp-comparison-builder' ); ?></option>
                                <option value="dropdown" <?php selected( $variants_selector_style, 'dropdown' ); ?>><?php _e( 'Dropdown', 'wp-comparison-builder' ); ?></option>
                                <option value="hidden" <?php selected( $variants_selector_style, 'hidden' ); ?>><?php _e( 'Hidden', 'wp-comparison-builder' ); ?></option>
                            </select>
                        </p>
                        
                        <p style="margin-top: 12px;">
                            <label>
                                <input type="checkbox" name="wpc_variants_show_badge" value="1" <?php checked( '1', $variants_show_badge ); ?> />
                                <?php _e( 'Show category badge on cards', 'wp-comparison-builder' ); ?>
                            </label>
                        </p>
                        
                        <p style="margin-top: 8px;">
                            <label>
                                <input type="checkbox" name="wpc_variants_remember_selection" value="1" <?php checked( '1', $variants_remember_selection ); ?> />
                                <?php _e( 'Remember user\'s category selection', 'wp-comparison-builder' ); ?>
                            </label>
                        </p>
                    </div>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
    <?php
}

/**
 * Handle Module Activation Logic
 */
add_action( 'update_option_wpc_enable_tools_module', 'wpc_on_tools_module_update', 10, 3 );
function wpc_on_tools_module_update( $old_value, $new_value, $option ) {
    // If module is being enabled
    if ( $new_value === '1' ) {
        if ( class_exists('WPC_Tools_Database') ) {
            $db = new WPC_Tools_Database();
            $db->create_table();
        }
    }
}
