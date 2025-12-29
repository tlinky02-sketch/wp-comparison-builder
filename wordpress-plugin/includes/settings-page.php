<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Settings
 */
function hosting_guider_register_settings() {
    register_setting( 'hosting_guider_options', 'hosting_guider_settings', 'hosting_guider_sanitize_settings' );

    add_settings_section(
        'hosting_guider_styles_section',
        __( 'Visual Style', 'hosting-guider' ),
        'hosting_guider_styles_section_callback',
        'hosting-guider-settings'
    );

    add_settings_field(
        'primary_color',
        __( 'Primary Color (Buttons, Badges)', 'hosting-guider' ),
        'hosting_guider_color_input',
        'hosting-guider-settings',
        'hosting_guider_styles_section',
        array( 'field' => 'primary_color', 'default' => '#0d9488' ) // Teal
    );

    add_settings_field(
        'accent_color',
        __( 'Accent Color (Active Filters, Highlights)', 'hosting-guider' ),
        'hosting_guider_color_input',
        'hosting-guider-settings',
        'hosting_guider_styles_section',
        array( 'field' => 'accent_color', 'default' => '#06b6d4' ) // Cyan
    );

    add_settings_field(
        'secondary_color',
        __( 'Secondary Color (Dark Accents)', 'hosting-guider' ),
        'hosting_guider_color_input',
        'hosting-guider-settings',
        'hosting_guider_styles_section',
        array( 'field' => 'secondary_color', 'default' => '#334155' ) // Navy
    );

    add_settings_field(
        'show_plan_buttons',
        __( 'Show Plan Selection Buttons', 'hosting-guider' ),
        'hosting_guider_checkbox_input',
        'hosting-guider-settings',
        'hosting_guider_styles_section',
        array( 'field' => 'show_plan_buttons' )
    );
}
add_action( 'admin_init', 'hosting_guider_register_settings' );

/**
 * Sanitize Settings
 */
function hosting_guider_sanitize_settings( $input ) {
    $output = array();
    $output['primary_color'] = sanitize_hex_color( $input['primary_color'] );
    $output['accent_color'] = sanitize_hex_color( $input['accent_color'] );
    $output['secondary_color'] = sanitize_hex_color( $input['secondary_color'] );
    $output['show_plan_buttons'] = isset( $input['show_plan_buttons'] ) ? '1' : '0';
    return $output;
}

/**
 * Section Callback
 */
function hosting_guider_styles_section_callback() {
    echo '<p>' . __( 'Customize the look and feel of the comparison tool. These colors will override the defaults.', 'hosting-guider' ) . '</p>';
}

/**
 * Color Input Callback
 */
function hosting_guider_color_input( $args ) {
    $options = get_option( 'hosting_guider_settings' );
    $field = $args['field'];
    $default = $args['default'];
    $value = isset( $options[$field] ) ? $options[$field] : $default;
    ?>
    <input type="text" 
           name="hosting_guider_settings[<?php echo esc_attr( $field ); ?>]" 
           value="<?php echo esc_attr( $value ); ?>" 
           class="my-color-field" 
           data-default-color="<?php echo esc_attr( $default ); ?>" />
           <p class="description"><?php _e( 'Select the color.', 'hosting-guider' ); ?></p>
    <?php
}

/**
 * Checkbox Input Callback
 */
function hosting_guider_checkbox_input( $args ) {
    $options = get_option( 'hosting_guider_settings' );
    $field = $args['field'];
    $value = isset( $options[$field] ) ? $options[$field] : '0';
    ?>
    <label>
        <input type="checkbox" 
               name="hosting_guider_settings[<?php echo esc_attr( $field ); ?>]" 
               value="1" 
               <?php checked( $value, '1' ); ?> />
        <?php _e( 'Enable this option to show "Select" buttons in pricing tables.', 'hosting-guider' ); ?>
    </label>
    <?php
}

/**
 * Add Menu Item
 */
function hosting_guider_add_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=hosting_provider',
        'Settings',
        'Settings',
        'manage_options',
        'hosting-guider-settings',
        'hosting_guider_settings_page'
    );
}
add_action( 'admin_menu', 'hosting_guider_add_admin_menu' );

/**
 * Render Settings Page
 */
function hosting_guider_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e( 'Hosting Guider Settings', 'hosting-guider' ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'hosting_guider_options' );
            do_settings_sections( 'hosting-guider-settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Enqueue Color Picker
 */
function hosting_guider_admin_enqueue_scripts( $hook_suffix ) {
    if ( 'hosting_provider_page_hosting-guider-settings' === $hook_suffix ) {
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'hosting-guider-settings-js', plugins_url( 'admin-settings.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
    }
}
add_action( 'admin_enqueue_scripts', 'hosting_guider_admin_enqueue_scripts' );


/**
 * Helper: HEX to HSL
 * Used to convert WP settings into the HSL format Tailwind expects
 */
function hosting_guider_hex_to_hsl( $hex ) {
    $hex = str_replace('#', '', $hex);
    if(strlen($hex) == 3) {
        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
    } else {
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
    }

    $r /= 255;
    $g /= 255;
    $b /= 255;

    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $h = $s = $l = ($max + $min) / 2;

    if($max == $min){
        $h = $s = 0; // achromatic
    } else {
        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        switch($max){
            case $r: $h = ($g - $b) / $d + ($g < $b ? 6 : 0); break;
            case $g: $h = ($b - $r) / $d + 2; break;
            case $b: $h = ($r - $g) / $d + 4; break;
        }
        $h /= 6;
    }
    
    // Tailwind specific output: "H S% L%" (no commas)
    $h = round( $h * 360 );
    $s = round( $s * 100 ) . '%';
    $l = round( $l * 100 ) . '%';

    return "{$h} {$s} {$l}";
}
