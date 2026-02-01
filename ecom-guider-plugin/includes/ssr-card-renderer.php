<?php
/**
 * WPC SSR Card Renderer
 * Renders item cards as pure HTML - no React needed
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render a single item card in SSR mode
 */
function wpc_render_card_ssr($item, $config = array())
{
    // Default config
    $config = wp_parse_args($config, array(
        'show_rating' => true,
        'show_price' => true,
        'show_badge' => true,
        'badge_text' => '',
        'badge_color' => '',
        'badge_style' => 'floating', // floating | inline
        'enable_comparison' => true,
        'button_text' => '',
        'txt_compare' => 'Compare',
        'txt_view' => 'View Details',
        'txt_visit' => 'Visit Site',
        'show_plans' => true,
    ));

    // Get global colors
    $primary_color = get_option('wpc_primary_color', '#6366f1');
    $hover_color = get_option('wpc_button_hover_color', '') ?: '#4f46e5';
    $star_color = get_option('wpc_star_rating_color', '#fbbf24');
    $border_color = get_option('wpc_border_color', '#e2e8f0');
    $featured_color = get_option('wpc_featured_color', '#6366f1');
    $btn_text_color = get_option('wpc_button_text_color', '#ffffff');

    // Item data
    $id = $item['id'];
    $name = esc_html($item['name']);
    $logo = esc_url($item['logo'] ?? '');
    $rating = floatval($item['rating'] ?? 0);
    $price = esc_html($item['price'] ?? '');
    $price_period = esc_html($item['price_period'] ?? '');
    $description = esc_html($item['short_description'] ?? $item['description'] ?? '');
    $badge = $config['badge_text'] ?: esc_html($item['badge'] ?? '');
    $link = esc_url($item['details_link'] ?? $item['link'] ?? '');
    $categories = isset($item['category']) ? (array) $item['category'] : array();
    $features = isset($item['features']) ? (array) $item['features'] : array();
    $is_featured = !empty($item['is_featured']);

    // Badge styling
    $badge_bg = $config['badge_color'] ?: $featured_color;

    // Categories/features as data attributes for JS filtering
    // Handle WP_Term objects, arrays, and strings
    $cat_strings = array();
    foreach ($categories as $cat) {
        if (is_object($cat) && isset($cat->name)) {
            $cat_strings[] = $cat->name;
        } elseif (is_string($cat)) {
            $cat_strings[] = $cat;
        }
    }
    $cat_data = esc_attr(implode(',', $cat_strings));

    $feat_strings = array();
    foreach ($features as $feat) {
        if (is_object($feat) && isset($feat->name)) {
            $feat_strings[] = $feat->name;
        } elseif (is_array($feat) && isset($feat['name']) && is_string($feat['name'])) {
            $feat_strings[] = $feat['name'];
        } elseif (is_string($feat)) {
            $feat_strings[] = $feat;
        }
    }
    $feat_data = esc_attr(implode(',', $feat_strings));

    ob_start();
    ?>
    <div class="wpc-card<?php echo $is_featured ? ' wpc-card-featured' : ''; ?>" data-wpc-card="<?php echo $id; ?>"
        data-wpc-name="<?php echo esc_attr($name); ?>" data-wpc-cats="<?php echo $cat_data; ?>"
        data-wpc-feats="<?php echo $feat_data; ?>" style="
            background: hsl(var(--card));
            border-radius: 1rem;
            border: 2px solid <?php echo esc_attr($is_featured ? $featured_color : $border_color); ?>;
            box-shadow: <?php echo $is_featured ? '0 4px 12px rgba(99,102,241,0.15)' : '0 1px 3px rgba(0,0,0,0.05)'; ?>;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            position: relative;
            transition: box-shadow 0.2s, transform 0.2s;
        ">
        <?php if (!empty($badge) && $config['show_badge'] && $config['badge_style'] === 'floating'): ?>
            <!-- Floating Badge -->
            <div class="wpc-badge-fixed" style="
            position: absolute;
            top: -0.5rem;
            right: 1rem;
            background: <?php echo esc_attr($badge_bg); ?>;
            color: <?php echo esc_attr($btn_text_color); ?> !important;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        "><?php echo esc_html($badge); ?></div>
        <?php endif; ?>

        <!-- Header: Logo + Name -->
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; padding-top: 0.5rem;">
            <?php if (!empty($logo)): ?>
                <div style="
                width: 3rem;
                height: 3rem;
                border-radius: 0.75rem;
                background: #ffffff;
                border: 1px solid hsl(var(--border) / 0.5);
                padding: 0.25rem;
                flex-shrink: 0;
                box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            ">
                    <img src="<?php echo $logo; ?>" alt="<?php echo $name; ?>"
                        style="width: 100%; height: 100%; object-fit: contain;" loading="lazy" />
                </div>
            <?php endif; ?>
            <div style="flex: 1; min-width: 0;">
                <h3 class="wpc-heading" style="font-weight: 600; margin: 0; font-size: var(--wpc-font-size-h3);">
                    <?php echo $name; ?>
                </h3>

                <?php if (!empty($badge) && $config['badge_style'] === 'inline'): ?>
                    <span class="wpc-badge-fixed" style="
                    display: inline-block;
                    margin-top: 0.25rem;
                    background: <?php echo esc_attr($badge_bg); ?>20;
                    color: <?php echo esc_attr($badge_bg); ?>;
                    font-weight: 500;
                    padding: 0.125rem 0.5rem;
                    border-radius: 9999px;
                "><?php echo esc_html($badge); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($config['show_rating'] && $rating > 0): ?>
            <!-- Rating (same partial fill logic as React StarRating component) -->
            <div class="wpc-star-rating" style="display: flex; align-items: center; gap: 0.25rem; margin-bottom: 0.75rem;">
                <?php
                $star_size = 14;
                $empty_color = '#e2e8f0';
                for ($i = 0; $i < 5; $i++):
                    $star_index = $i + 1;
                    // Calculate fill percentage for this specific star (same as React)
                    if ($rating >= $star_index) {
                        $fill_percentage = 100;
                    } elseif ($rating > $i) {
                        $raw_percent = ($rating - $i) * 100;
                        // Visual Correction: Account for SVG padding (same as React component)
                        $fill_percentage = 8.33 + ($raw_percent * 0.8334);
                        $fill_percentage = min(100, max(0, $fill_percentage));
                    } else {
                        $fill_percentage = 0;
                    }
                    ?>
                    <div class="wpc-star-container"
                        style="position: relative; width: <?php echo $star_size; ?>px; height: <?php echo $star_size; ?>px;">
                        <!-- Empty Star Background -->
                        <svg class="wpc-star-icon" width="<?php echo $star_size; ?>" height="<?php echo $star_size; ?>"
                            viewBox="0 0 24 24" fill="none" stroke="none"
                            style="display: block; position: absolute; top: 0; left: 0;">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"
                                fill="<?php echo esc_attr($empty_color); ?>"></path>
                        </svg>
                        <!-- Filled Star Overlay (Clipped) -->
                        <div class="wpc-star-fill"
                            style="width: <?php echo $fill_percentage; ?>%; overflow: hidden; position: absolute; top: 0; left: 0; height: 100%;">
                            <svg class="wpc-star-icon" width="<?php echo $star_size; ?>" height="<?php echo $star_size; ?>"
                                viewBox="0 0 24 24" fill="none" stroke="none" style="display: block;">
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"
                                    fill="<?php echo esc_attr($star_color); ?>"></path>
                            </svg>
                        </div>
                    </div>
                <?php endfor; ?>
                <span class="wpc-text-muted"
                    style="margin-left: 0.25rem; font-weight: bold; color: <?php echo esc_attr($star_color); ?>;"><?php echo number_format($rating, 1); ?>/5</span>
            </div>
        <?php endif; ?>

        <?php if ($config['show_price'] && !empty($price)): ?>
            <!-- Price -->
            <div
                style="margin-bottom: 1rem; padding: 0.75rem; background: hsl(var(--muted) / 0.3); border-radius: 0.5rem; text-align: center;">
                <span class="wpc-text-body wpc-card-price"
                    style="font-weight: 700; color: <?php echo esc_attr($primary_color); ?>; font-size: 1.875rem;"><?php echo $price; ?></span>
                <?php if (!empty($price_period)): ?>
                    <span class="wpc-text-muted" style="color: hsl(var(--muted-foreground));">/<?php echo $price_period; ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($description)): ?>
            <!-- Description -->
            <p class="wpc-text-muted" style="
            line-height: 1.5;
            margin: 0 0 1rem 0;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            color: hsl(var(--muted-foreground));
        "><?php echo $description; ?></p>
        <?php endif; ?>

        <!-- Actions -->
        <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: auto;">
            <?php if ($config['enable_comparison']): ?>
                <!-- Compare Button -->
                <button data-wpc-compare-btn="<?php echo $id; ?>" data-wpc-name="<?php echo esc_attr($name); ?>"
                    class="wpc-text-muted" style="
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 0.5rem;
                    padding: 0.5rem 1rem;
                    background: transparent;
                    border: 1px solid hsl(var(--border));
                    border-radius: 0.5rem;
                    cursor: pointer;
                    transition: all 0.2s;
                    color: hsl(var(--muted-foreground));
                ">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5" />
                    </svg>
                    <span class="wpc-compare-text"><?php echo esc_html($config['txt_compare']); ?></span>
                </button>
            <?php endif; ?>

            <?php if (!empty($link)): ?>
                <!-- Visit Button (onclick to hide affiliate URL in status bar) -->
                <button type="button" onclick="window.open('<?php echo esc_js($link); ?>', '_blank');" class="wpc-text-body"
                    style="
                    display: block;
                    width: 100%;
                    text-align: center;
                    padding: 0.625rem 1rem;
                    background: <?php echo esc_attr($primary_color); ?>;
                    color: <?php echo esc_attr($btn_text_color); ?>;
                    font-size: var(--wpc-font-size-btn, 1rem);
                    font-weight: 500;
                    border-radius: 0.5rem;
                    border: none;
                    cursor: pointer;
                    transition: background 0.2s;
                " onmouseover="this.style.backgroundColor='<?php echo esc_js($hover_color); ?>';"
                    onmouseout="this.style.backgroundColor='<?php echo esc_js($primary_color); ?>';"><?php echo esc_html($config['button_text'] ?: $config['txt_visit']); ?></button>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Register the script
 */
function wpc_register_ssr_scripts()
{
    wp_register_script(
        'wpc-frontend',
        WPC_PLUGIN_URL . 'assets/js/wpc-frontend.js',
        array(),
        WPC_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'wpc_register_ssr_scripts');
