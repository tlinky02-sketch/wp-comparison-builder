<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function wpc_render_modules_tab() {
    $tools_enabled = get_option( 'wpc_enable_tools_module', false );
    ?>
    <form method="post" action="options.php">
        <?php settings_fields( 'wpc_modules_settings' ); ?>
        <?php do_settings_sections( 'wpc_modules_settings' ); ?>
        
        <h2><?php _e( 'Feature Modules', 'wp-comparison-builder' ); ?></h2>
        <p class="description"><?php _e( 'Enable or disable plugin features to keep your interface clean.', 'wp-comparison-builder' ); ?></p>
        
        <table class="form-table">
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
