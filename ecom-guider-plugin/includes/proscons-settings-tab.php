<?php
/**
 * Pros & Cons Settings Tab
 * Global settings for pros/cons colors, icons, and text
 */

function wpc_render_proscons_tab() {
    ?>
    <div class="wrap">
        <form method="post" action="options.php">
            <?php settings_fields( 'wpc_proscons_settings' ); ?>
            
            <div style="max-width: 1200px;">
                <h2 style="margin-top: 20px;">Pros & Cons Configuration</h2>
                <p class="description">Configure default colors, icons, and text for pros and cons. These can be overridden per-item.</p>
                
                <!-- Colors Section -->
                <table class="form-table" style="margin-top: 20px;">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label>Pros Colors</label>
                            </th>
                            <td>
                                <div style="display: flex; gap: 20px; align-items: center;">
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-size: 12px; color: #666;">Background</label>
                                        <input type="color" name="wpc_color_pros_bg" value="<?php echo esc_attr( get_option( 'wpc_color_pros_bg', '#f0fdf4' ) ); ?>" style="width: 60px; height: 40px;" />
                                        <p class="description" style="margin-top: 5px;"><?php echo esc_html( get_option( 'wpc_color_pros_bg', '#f0fdf4' ) ); ?></p>
                                    </div>
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-size: 12px; color: #666;">Text Color</label>
                                        <input type="color" name="wpc_color_pros_text" value="<?php echo esc_attr( get_option( 'wpc_color_pros_text', '#166534' ) ); ?>" style="width: 60px; height: 40px;" />
                                         <p class="description" style="margin-top: 5px;"><?php echo esc_html( get_option( 'wpc_color_pros_text', '#166534' ) ); ?></p>
                                    </div>
                                    <button type="button" class="button" onclick="wpcResetGlobalProsConsColors(this, 'pros')">Reset to Default</button>
                                </div>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label>Cons Colors</label>
                            </th>
                            <td>
                                <div style="display: flex; gap: 20px; align-items: center;">
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-size: 12px; color: #666;">Background</label>
                                        <input type="color" name="wpc_color_cons_bg" value="<?php echo esc_attr( get_option( 'wpc_color_cons_bg', '#fef2f2' ) ); ?>" style="width: 60px; height: 40px;" />
                                        <p class="description" style="margin-top: 5px;"><?php echo esc_html( get_option( 'wpc_color_cons_bg', '#fef2f2' ) ); ?></p>
                                    </div>
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-size: 12px; color: #666;">Text Color</label>
                                        <input type="color" name="wpc_color_cons_text" value="<?php echo esc_attr( get_option( 'wpc_color_cons_text', '#991b1b' ) ); ?>" style="width: 60px; height: 40px;" />
                                        <p class="description" style="margin-top: 5px;"><?php echo esc_html( get_option( 'wpc_color_cons_text', '#991b1b' ) ); ?></p>
                                    </div>
                                    <button type="button" class="button" onclick="wpcResetGlobalProsConsColors(this, 'cons')">Reset to Default</button>
                                </div>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="wpc_pros_icon">Pros Icon</label>
                            </th>
                            <td>
                                <select name="wpc_pros_icon" id="wpc_pros_icon" style="width: 200px;">
                                    <?php
                                    $pros_icon = get_option( 'wpc_pros_icon', '✓' );
                                    $icons = array(
                                        '✓' => '✓ Checkmark',
                                        '✔' => '✔ Heavy Check',
                                        '👍' => '👍 Thumbs Up',
                                        '➕' => '➕ Plus',
                                        '✅' => '✅ Check Box',
                                        '⭐' => '⭐ Star',
                                        '💚' => '💚 Green Heart',
                                    );
                                    foreach ( $icons as $value => $label ) {
                                        printf(
                                            '<option value="%s" %s>%s</option>',
                                            esc_attr( $value ),
                                            selected( $pros_icon, $value, false ),
                                            esc_html( $label )
                                        );
                                    }
                                    ?>
                                </select>
                                <p class="description">Choose the icon to display for each pro item</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="wpc_cons_icon">Cons Icon</label>
                            </th>
                            <td>
                                <select name="wpc_cons_icon" id="wpc_cons_icon" style="width: 200px;">
                                    <?php
                                    $cons_icon = get_option( 'wpc_cons_icon', '✗' );
                                    $icons = array(
                                        '✗' => '✗ X Mark',
                                        '✘' => '✘ Heavy X',
                                        '👎' => '👎 Thumbs Down',
                                        '➖' => '➖ Minus',
                                        '❌' => '❌ Cross Mark',
                                        '⛔' => '⛔ No Entry',
                                        '💔' => '💔 Broken Heart',
                                    );
                                    foreach ( $icons as $value => $label ) {
                                        printf(
                                            '<option value="%s" %s>%s</option>',
                                            esc_attr( $value ),
                                            selected( $cons_icon, $value, false ),
                                            esc_html( $label )
                                        );
                                    }
                                    ?>
                                </select>
                                <p class="description">Choose the icon to display for each con item</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="wpc_text_loading_proscons">Loading Text</label>
                            </th>
                            <td>
                                <input type="text" name="wpc_text_loading_proscons" id="wpc_text_loading_proscons" value="<?php echo esc_attr( get_option( 'wpc_text_loading_proscons', 'Loading Pros & Cons...' ) ); ?>" class="regular-text" />
                                <p class="description">Text shown while pros/cons table is loading</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <?php submit_button(); ?>
            </div>
        </form>
    </div>
    <?php
}

// Register settings
add_action( 'admin_init', 'wpc_register_proscons_settings' );
function wpc_register_proscons_settings() {
    register_setting( 'wpc_proscons_settings', 'wpc_color_pros_bg', array( 'sanitize_callback' => 'sanitize_hex_color' ) );
    register_setting( 'wpc_proscons_settings', 'wpc_color_pros_text', array( 'sanitize_callback' => 'sanitize_hex_color' ) );
    register_setting( 'wpc_proscons_settings', 'wpc_color_cons_bg', array( 'sanitize_callback' => 'sanitize_hex_color' ) );
    register_setting( 'wpc_proscons_settings', 'wpc_color_cons_text', array( 'sanitize_callback' => 'sanitize_hex_color' ) );
    register_setting( 'wpc_proscons_settings', 'wpc_pros_icon', array( 'sanitize_callback' => 'sanitize_text_field' ) );
    register_setting( 'wpc_proscons_settings', 'wpc_cons_icon', array( 'sanitize_callback' => 'sanitize_text_field' ) );
    register_setting( 'wpc_proscons_settings', 'wpc_text_loading_proscons', array( 'sanitize_callback' => 'sanitize_text_field' ) );
}
