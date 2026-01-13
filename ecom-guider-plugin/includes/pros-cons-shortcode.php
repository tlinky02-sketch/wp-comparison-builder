<?php
/**
 * Shortcode: [wpc_pros_cons id="123"]
 * Displays the pros/cons table for a specific item inline.
 * Matches the design and behavior of the pricing table shortcode.
 */
function wpc_pros_cons_shortcode( $atts ) {
    // Ensure assets are loaded
    wp_enqueue_script( 'wpc-app' );
    wp_enqueue_style( 'wpc-styles' );

    $attributes = shortcode_atts( array(
        'id' => '',
    ), $atts );

    if ( empty( $attributes['id'] ) ) return ''; // ID is required

    $item_id = $attributes['id'];

    // 1. Fetch Data (Optimized)
    if ( ! function_exists( 'wpc_fetch_items_data' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'api-endpoints.php';
    }

    $data = wpc_fetch_items_data( array( $item_id ) );
    
    if ( empty( $data['items'] ) ) {
        return "<!-- WPC Pros/Cons: Item ID {$item_id} not found -->";
    }
    
    $item = $data['items'][0];

    // 2. Generate unique ID
    $unique_id = 'wpc-proscons-' . $item_id . '-' . mt_rand(1000, 9999);

    // 3. Config for React App
    $widget_config = [
        'viewMode' => 'pros-cons-table',
        'item' => $item,
        'displayContext' => 'inline',
        // Per-item label overrides
        'prosLabel' => get_post_meta($item['id'], '_wpc_txt_pros_label', true) ?: get_option('wpc_text_pros', 'Pros'),
        'consLabel' => get_post_meta($item['id'], '_wpc_txt_cons_label', true) ?: get_option('wpc_text_cons', 'Cons'),
        // Per-item color overrides (respecting the enable flag)
        'prosBg' => (get_post_meta($item['id'], '_wpc_enable_pros_cons_colors', true) === '1' ? get_post_meta($item['id'], '_wpc_color_pros_bg', true) : '') ?: get_option('wpc_color_pros_bg', '#f0fdf4'),
        'prosText' => (get_post_meta($item['id'], '_wpc_enable_pros_cons_colors', true) === '1' ? get_post_meta($item['id'], '_wpc_color_pros_text', true) : '') ?: get_option('wpc_color_pros_text', '#166534'),
        'consBg' => (get_post_meta($item['id'], '_wpc_enable_pros_cons_colors', true) === '1' ? get_post_meta($item['id'], '_wpc_color_cons_bg', true) : '') ?: get_option('wpc_color_cons_bg', '#fef2f2'),
        'consText' => (get_post_meta($item['id'], '_wpc_enable_pros_cons_colors', true) === '1' ? get_post_meta($item['id'], '_wpc_color_cons_text', true) : '') ?: get_option('wpc_color_cons_text', '#991b1b'),
        // Icon settings (global only for now, per-item can be added later)
        'prosIcon' => get_option('wpc_pros_icon', '✓'),
        'consIcon' => get_option('wpc_cons_icon', '✗'),
    ];

    $config_json = htmlspecialchars(json_encode($widget_config), ENT_QUOTES, 'UTF-8');

    ob_start();
    ?>
    <div id="<?php echo esc_attr($unique_id); ?>" class="wpc-root" data-config="<?php echo $config_json; ?>">
        <!-- SSR Lite Preview -->
        <div class="wpc-ssr-preview" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
            <!-- Pros -->
            <div style="background: <?php echo esc_attr($widget_config['prosBg']); ?>; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid rgba(0,0,0,0.05);">
                <h3 style="color: <?php echo esc_attr($widget_config['prosText']); ?>; font-weight: 600; margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                    <span><?php echo esc_html($widget_config['prosIcon']); ?></span>
                    <?php echo esc_html($widget_config['prosLabel']); ?>
                </h3>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php if (!empty($item['pros']) && is_array($item['pros'])) : foreach($item['pros'] as $pro) : ?>
                        <li style="display: flex; gap: 0.5rem; align-items: start; color: #374151;">
                            <span style="color: <?php echo esc_attr($widget_config['prosText']); ?>;">✓</span>
                            <span><?php echo esc_html($pro); ?></span>
                        </li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>
            
            <!-- Cons -->
            <div style="background: <?php echo esc_attr($widget_config['consBg']); ?>; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid rgba(0,0,0,0.05);">
                <h3 style="color: <?php echo esc_attr($widget_config['consText']); ?>; font-weight: 600; margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                    <span><?php echo esc_html($widget_config['consIcon']); ?></span>
                    <?php echo esc_html($widget_config['consLabel']); ?>
                </h3>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php if (!empty($item['cons']) && is_array($item['cons'])) : foreach($item['cons'] as $con) : ?>
                        <li style="display: flex; gap: 0.5rem; align-items: start; color: #374151;">
                            <span style="color: <?php echo esc_attr($widget_config['consText']); ?>;">✗</span>
                            <span><?php echo esc_html($con); ?></span>
                        </li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'wpc_pros_cons', 'wpc_pros_cons_shortcode' );
