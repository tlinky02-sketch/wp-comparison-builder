<?php
/**
 * Plugin Name: WP Comparison Builder
 * Plugin URI:  https://example.com/
 * Description: A powerful, generic comparison builder for WordPress. Create beautiful comparison tables, lists, and hero sections for any type of item (hosting, software, products, etc.).
 * Version:     1.0.1
 * Author:      Your Name
 * Author URI:  https://example.com/
 * Text Domain: wp-comparison-builder
 * Domain Path: /languages
 * License:     GPL-2.0+
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define Plugin Constants
define( 'WPC_VERSION', '1.0.0' );
define( 'WPC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Includes
require_once WPC_PLUGIN_DIR . 'includes/cpt-setup.php';
require_once WPC_PLUGIN_DIR . 'includes/admin-ui.php';
require_once WPC_PLUGIN_DIR . 'includes/settings-page.php';
require_once WPC_PLUGIN_DIR . 'includes/api-endpoints.php';
require_once WPC_PLUGIN_DIR . 'includes/shortcode-helper.php';
require_once WPC_PLUGIN_DIR . 'includes/sample-data.php';
require_once WPC_PLUGIN_DIR . 'includes/seo-schema.php';
require_once WPC_PLUGIN_DIR . 'includes/compare-button-shortcode.php';
require_once WPC_PLUGIN_DIR . 'includes/feature-table-shortcode.php';
require_once WPC_PLUGIN_DIR . 'includes/pros-cons-shortcode.php';
require_once WPC_PLUGIN_DIR . 'includes/use-cases-shortcode.php'; // New Use Cases Feature
require_once WPC_PLUGIN_DIR . 'includes/hero-shortcode.php'; // Hero Section SSR
require_once WPC_PLUGIN_DIR . 'includes/tools-shortcode.php'; // Recommended Tools
require_once WPC_PLUGIN_DIR . 'includes/ssr-card-renderer.php'; // SSR Card Template
require_once WPC_PLUGIN_DIR . 'includes/list-shortcode-ssr.php'; // SSR List Shortcode
require_once WPC_PLUGIN_DIR . 'includes/compare-shortcode-ssr.php'; // SSR Compare Shortcode
require_once WPC_PLUGIN_DIR . 'includes/comparison-sets-db.php';
require_once WPC_PLUGIN_DIR . 'includes/compare-alternatives-admin.php';

require_once WPC_PLUGIN_DIR . 'includes/list-meta-box.php';
require_once WPC_PLUGIN_DIR . 'includes/migration.php';
require_once WPC_PLUGIN_DIR . 'includes/ai-handler.php';
require_once WPC_PLUGIN_DIR . 'includes/tools-cpt.php'; // Recommended Tools Module
require_once WPC_PLUGIN_DIR . 'includes/settings-page-modules.php'; // Modules Settings Tab
require_once WPC_PLUGIN_DIR . 'includes/variants-admin-ui.php'; // Product Variants Module
require_once WPC_PLUGIN_DIR . 'includes/plan-features-tab.php'; // Plan Features Tab (Category-Aware)
require_once WPC_PLUGIN_DIR . 'includes/class-wpc-database.php'; // Database Class
require_once WPC_PLUGIN_DIR . 'includes/class-wpc-migrator.php'; // Migrator Class
require_once WPC_PLUGIN_DIR . 'includes/class-wpc-tools-db.php'; // Tools Database Class


// Initialize Database Table on Activation
register_activation_hook( __FILE__, 'wpc_install_db' );
function wpc_install_db() {
    // Items Table
    if ( class_exists('WPC_Database') ) {
        $db = new WPC_Database();
        $db->create_table();
    }
    
    // Tools Table (Conditional)
    if ( class_exists('WPC_Tools_Database') ) {
        // We create it if the module is enabled OR if we just want to ensure it exists for safety 
        // given that activation usually implies "setup everything needed".
        // Use module check to be consistent with Migrator logic.
        if ( get_option( 'wpc_enable_tools_module' ) === '1' ) {
            $tools_db = new WPC_Tools_Database();
            $tools_db->create_table();
        }
    }
}

/**
 * Get comparison feature tag terms for frontend
 */
function wpc_get_compare_tag_terms() {
    $terms = get_terms( array( 
        'taxonomy' => 'comparison_feature', 
        'hide_empty' => false 
    ));
    
    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return array();
    }
    
    $result = array();
    foreach ( $terms as $term ) {
        $result[] = array(
            'id' => $term->term_id,
            'key' => 'tag_' . $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
        );
    }
    
    return $result;
}

/**
 * Register Scripts and Styles
 */
function wpc_register_scripts() {
    // Register the main app script
    wp_register_script(
        'wpc-app',
        WPC_PLUGIN_URL . 'dist/assets/wp-plugin.js', 
        array( 'wp-element', 'wp-api' ),
        WPC_VERSION,
        true
    );

    // Register Compare Button Script
    wp_register_script(
        'wpc-compare-button',
        WPC_PLUGIN_URL . 'assets/compare-button.js',
        array(),
        WPC_VERSION,
        true
    );

    // Localize script with settings
    // Default colors if not set
    $primary_color = get_option( 'wpc_primary_color', '#6366f1' );
    $accent_color = get_option( 'wpc_accent_color', '#0d9488' );
    $secondary_color = get_option( 'wpc_secondary_color', '#1e293b' );
    $featured_color = get_option( 'wpc_featured_color', '#6366f1' );
    $pricing_banner_color = get_option( 'wpc_pricing_banner_color', '#10b981' );
    $button_hover_color = get_option( 'wpc_button_hover_color', '' );
    
    // Get filter style
    $filter_style = get_option( 'wpc_filter_style', 'top' );
    $search_type = get_option( 'wpc_search_type', 'text' );
    
    // Get Pricing Table Visuals (New)
    $pt_header_bg = get_option( 'wpc_pt_header_bg', '#f8fafc' );
    $pt_header_text = get_option( 'wpc_pt_header_text', '#0f172a' );
    $pt_btn_bg = get_option( 'wpc_pt_btn_bg', '' ); // Default empty to fallback to primary
    $pt_btn_text = get_option( 'wpc_pt_btn_text', '#ffffff' );
    $pt_btn_pos_table = get_option( 'wpc_pt_btn_pos_table', 'after_price' );
    $pt_btn_pos_popup = get_option( 'wpc_pt_btn_pos_popup', 'after_price' );
    $show_plan_buttons = get_option( 'wpc_show_plan_buttons', '1' );
    $show_footer_button_global = get_option( 'wpc_show_footer_button_global', '1' );
    $open_links_new_tab = get_option( 'wpc_open_links_new_tab', '1' );

    // Text Labels (Get values or rely on defaults in JS, but passing them empty means "use default")
    $text_labels = array(
        'viewDetails' => get_option('wpc_text_view_details', ''),
        'visit' => get_option('wpc_text_visit', ''),
        'compareAlternatives' => get_option('wpc_text_compare_alternatives', ''),
        'compareNow' => get_option('wpc_text_compare_now', ''),
        'reviews' => get_option('wpc_text_reviews', ''),
        'backToReviews' => get_option('wpc_text_back_to_reviews', ''),
        'filters' => get_option('wpc_text_filters', ''),
        'searchPlaceholder' => get_option('wpc_text_search_placeholder', ''),
        'sortDefault' => get_option('wpc_text_sort_default', ''),
        'category' => get_option('wpc_text_category', ''),
        'features' => get_option('wpc_text_features', 'Tags'),
        'itemsCount' => get_option('wpc_text_items_count', ''),
        'selected' => get_option('wpc_text_selected', ''),
        'clearAll' => get_option('wpc_text_clear_all', ''),
        'about' => get_option('wpc_text_about', ''),
        'preview' => get_option('wpc_text_preview', 'Preview'),
        'selectPlan' => get_option('wpc_text_select_plan', 'Select'),
        'pricingHeader' => get_option('wpc_text_pricing_header', 'Pricing Plans: {name}'),
        'pricingSub' => get_option('wpc_text_pricing_sub', 'Compare available plans explicitly'),
        'tablePrice' => get_option('wpc_text_table_price', 'Price'),
        'tableFeatures' => get_option('wpc_text_table_features', 'Features'),
        'close' => get_option('wpc_text_close', 'Close'),
        'noPlans' => get_option('wpc_text_no_plans', 'No specific pricing plans available for display.'),
        'emptyPrice' => get_option('wpc_text_empty_price', 'Free'),
    );

    // Hybrid Approach: Preload Data Globally
    // Ensure API functions are loaded
    if ( ! function_exists( 'wpc_get_items' ) ) {
        // Should be loaded, but just in case
    }
    $initial_data = function_exists('wpc_get_items') ? wpc_get_items() : [];

    $localize_data = array(
        'apiUrl' => site_url( '/wp-json/wpc/v1/items' ),
        'nonce'  => wp_create_nonce( 'wp_rest' ),
        'colors' => array(
            'primary' => $primary_color,
            'accent' => $accent_color,
            'secondary' => $secondary_color,
            'featured' => $featured_color,
            'banner' => $pricing_banner_color,
            'hoverButton' => $button_hover_color,
            'prosBg' => get_option( 'wpc_color_pros_bg', '#f0fdf4' ),
            'prosText' => get_option( 'wpc_color_pros_text', '#166534' ),
            'consBg' => get_option( 'wpc_color_cons_bg', '#fef2f2' ),
            'consText' => get_option( 'wpc_color_cons_text', '#991b1b' ),
            'couponBg' => get_option( 'wpc_color_coupon_bg', '#fef3c7' ),
            'couponText' => get_option( 'wpc_color_coupon_text', '#92400e' ),
            'couponHover' => get_option( 'wpc_color_coupon_hover', '#fde68a' ),
            'copied' => get_option( 'wpc_color_copied', '#10b981' ),
            'stars' => get_option( 'wpc_star_rating_color', '#fbbf24' ), // Star Color
            'usecaseIcon' => get_option( 'wpc_usecase_icon_color', '#6366f1' ), // Use Case Icon Color
        ),
        'texts' => $text_labels, // <--- NEW TEXTS OBJECT
        'visuals' => array( // New object for PT visuals
            'wpc_pt_header_bg' => $pt_header_bg,
            'wpc_pt_header_text' => $pt_header_text,
            'wpc_pt_btn_bg' => $pt_btn_bg,
            'wpc_pt_btn_text' => $pt_btn_text,
            'wpc_pt_btn_pos_table' => $pt_btn_pos_table,
            'wpc_pt_btn_pos_popup' => $pt_btn_pos_popup,
        ),
        'showPlanButtons' => $show_plan_buttons,
        'showFooterButtonGlobal' => $show_footer_button_global,
        'openNewTab' => $open_links_new_tab === '1', // <--- NEW SETTING
        'primary_color' => $primary_color, // Explicitly pass top-level too if needed
        'filterStyle' => $filter_style,
        'searchType' => $search_type,
        'target_details' => get_option( 'wpc_target_details', '_blank' ),
        'target_direct' => get_option( 'wpc_target_direct', '_blank' ),
        'target_pricing' => get_option( 'wpc_target_pricing', '_blank' ),
        'compareFeatures' => get_option( 'wpc_compare_features', array() ),
        'compareTagTerms' => wpc_get_compare_tag_terms(),
        'initialData' => $initial_data // <--- INJECTED HERE
    );

    // Pass settings to wpcSettings global
    wp_localize_script( 'wpc-app', 'wpcSettings', $localize_data );
    // Legacy fallback
    wp_localize_script( 'wpc-app', 'ecommerceGuiderSettings', $localize_data );

    // Register Styles
    wp_register_style(
        'wpc-styles',
        WPC_PLUGIN_URL . 'dist/assets/wp-plugin.css',
        array(),
        WPC_VERSION
    );
    
    // Register FontAwesome 6 for icons (Best Use Cases, etc.)
    wp_register_style(
        'fontawesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
        array(),
        '6.5.1'
    );
    
    // Inject Custom CSS Variables using wp_add_inline_style (like working plugin)
    $primary_color = get_option( 'wpc_primary_color', '#6366f1' );
    $accent_color = get_option( 'wpc_accent_color', '#0d9488' );
    $secondary_color = get_option( 'wpc_secondary_color', '#1e293b' );
    $border_color = get_option( 'wpc_card_border_color', '' );

    $primary_hsl = wpc_hex2hsl( $primary_color );
    $accent_hsl = wpc_hex2hsl( $accent_color );
    $secondary_hsl = wpc_hex2hsl( $secondary_color );
    
    
    // Button Hover Color
    $button_hover_color = get_option( 'wpc_button_hover_color', '' );
    
    // If not set, we default to injecting a fallback OR handling it in CSS.
    // However, the cleanest way is to always define the variable, either to the custom value or to the calculated darker primary.
    // For "Automatic", we usually want HSL manipulation in CSS, but here we can't easily do calc() on the HSL variable for text hex.
    // So if empty, we won't define --wpc-btn-hover here, and let CSS fallback to its default.
    
    $custom_css = "
        :root {
            --primary: {$primary_hsl};
            --accent: {$accent_hsl};
            --secondary: {$secondary_hsl};
            --ring: {$primary_hsl};
    ";
    
    if ( ! empty( $button_hover_color ) ) {
        $custom_css .= " --wpc-btn-hover: " . esc_attr($button_hover_color) . "; ";
    }
    
    $custom_css .= " } ";

    if ( ! empty( $border_color ) ) {
        $custom_css .= " .bg-card { border-color: " . esc_attr($border_color) . " !important; }";
    }
    
    wp_add_inline_style( 'wpc-styles', $custom_css );

    // Check for shortcode presence
    global $post;
    
    // ========================================
    // SSR SHORTCODES (NO REACT NEEDED):
    // - wpc_compare, wpc_list, wpc_hero
    // - wpc_pros_cons, wpc_use_cases
    // - wpc_feature_table, wpc_tools
    // ========================================
    // REACT BUNDLE ONLY NEEDED FOR:
    // - wpc_pricing_table (interactive pricing popup)
    // - wpc_compare_button (comparison table popup)
    // ========================================
    
    $react_shortcodes = array(
        'wpc_pricing_table',
        'wpc_compare_button',
        'ecommerce_compare_button' // Legacy
    );
    
    $needs_react = false;
    if ( is_a( $post, 'WP_Post' ) ) {
        foreach ( $react_shortcodes as $tag ) {
            if ( has_shortcode( $post->post_content, $tag ) ) {
                $needs_react = true;
                break;
            }
        }
    }
    
    // Only enqueue React bundle on pages that REALLY need it (pricing table popups)
    if ( $needs_react ) {
        wp_enqueue_script( 'wpc-app' );
        wp_enqueue_style( 'wpc-styles' );
        wp_enqueue_style( 'fontawesome' );
    }
}
add_action( 'wp_enqueue_scripts', 'wpc_register_scripts' );

/**
 * Convert Hex to HSL
 */
function wpc_hex2hsl( $hex ) {
    $hex = str_replace( '#', '', $hex );
    
    if ( strlen( $hex ) == 3 ) {
        $r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
        $g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
        $b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
    } else {
        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );
    }
    
    $r /= 255;
    $g /= 255;
    $b /= 255;
    
    $max = max( $r, $g, $b );
    $min = min( $r, $g, $b );
    $h; $s; $l = ( $max + $min ) / 2;
    $d = $max - $min;
    
    if ( $d == 0 ) {
        $h = $s = 0; // achromatic
    } else {
        $s = $l > 0.5 ? $d / ( 2 - $max - $min ) : $d / ( $max + $min );
        switch( $max ) {
            case $r: $h = ( $g - $b ) / $d + ( $g < $b ? 6 : 0 ); break;
            case $g: $h = ( $b - $r ) / $d + 2; break;
            case $b: $h = ( $r - $g ) / $d + 4; break;
        }
        $h /= 6;
    }
    
    $h = floor( $h * 360 );
    $s = floor( $s * 100 );
    $l = floor( $l * 100 );
    
    return "$h $s% $l%"; 
}

/**
 * Resolve a boolean setting with List > Item > Global cascade
 * 
 * @param int    $list_id      The List post ID
 * @param string $list_meta    Meta key for List-level setting
 * @param int    $item_id      Item post ID (null if not applicable)
 * @param string $global_opt   Option key for Global setting
 * @param bool   $default      Default value if nothing is set
 * @return bool
 */
function wpc_resolve_bool_setting( $list_id, $list_meta, $item_id = null, $global_opt = '', $default = true ) {
    // 1. List-level (highest priority)
    $list_val = get_post_meta( $list_id, $list_meta, true );
    if ( $list_val !== '' ) {
        return $list_val === '1';
    }
    
    // 2. Item-level (if provided)
    // Note: For list shortcode, item is not used as the setting is per-list, not per-item
    // But this function is reusable for other contexts
    
    // 3. Global setting (fallback)
    if ( $global_opt ) {
        $global_val = get_option( $global_opt, $default ? '1' : '0' );
        return $global_val === '1';
    }
    
    return $default;
}

/**
 * Add type="module" to the app script
 */
function wpc_add_module_type_attribute( $tag, $handle, $src ) {
    if ( 'wpc-app' === $handle ) {
        $tag = '<script type="module" src="' . esc_url( $src ) . '"></script>';
    }
    return $tag;
}
add_filter( 'script_loader_tag', 'wpc_add_module_type_attribute', 10, 3 );

/**
 * Shortcode to render the comparison tool
 * Usage: [wpc_compare ids="1,2" featured="1" category="cat_slug" limit="4"]
 */
function wpc_shortcode( $atts ) {
    // Enqueue assets
    wp_enqueue_script( 'wpc-app' );
    wp_enqueue_style( 'wpc-styles' );

    $attributes = shortcode_atts( array(
        'ids'      => '',
        'featured' => '',
        'category' => '',
        'limit'    => '',
    ), $atts );

    // 1. Get Data (Server Side)
    // Ensure API functions are loaded
    if ( ! function_exists( 'wpc_get_items' ) ) {
        // Fallback or load if needed, but it should be loaded by main plugin file
    }
    
    $data = wpc_get_items(); // Returns ['items' => ..., 'categories' => ..., 'filterableFeatures' => ...]
    $items = $data['items'];

    // 2. Filter Data (PHP Side - Basic Replication of JS Logic for Initial View)
    // Filter by specific IDs
    $specific_ids = !empty($attributes['ids']) ? array_map('trim', explode(',', $attributes['ids'])) : [];
    if ( ! empty( $specific_ids ) ) {
        $items = array_filter( $items, function($item) use ($specific_ids) {
            return in_array( $item['id'], $specific_ids );
        });
    }

    // Filter by Category
    $category_slug = sanitize_text_field( $attributes['category'] );
    if ( ! empty( $category_slug ) ) {
        $items = array_filter( $items, function($item) use ($category_slug) {
            // Check if any category matches (case insensitive)
            foreach ($item['category'] as $cat) {
                if ( md5(strtolower($cat)) === md5(strtolower($category_slug)) || strtolower($cat) === strtolower($category_slug)) return true;
                // Note: Real slug matching might be better but names are used in JS currently
            }
            return false;
        });
    }

    // Limit
    $limit = intval( $attributes['limit'] );
    if ( $limit > 0 ) {
        $items = array_slice( $items, 0, $limit );
    }
    
    // Sort (Optional: Featured logic could be here, but let's just stick to default order for now)

    // 3. Prepare Config for JS
    // Determine Filter Style first
    $filter_style = get_option( 'wpc_filter_style', 'top' );
    
    $config = array(
        'ids'      => $specific_ids,
        'featured' => !empty($attributes['featured']) ? array_map('trim', explode(',', $attributes['featured'])) : [],
        'category' => $category_slug,
        'limit'    => $limit,
        'filterLayout' => $filter_style, // Explicitly pass to React
        // 'initialData' => $data // REMOVED: Hybrid approach uses global wpcSettings.initialData
    );

    $config_json = htmlspecialchars(json_encode($config), ENT_QUOTES, 'UTF-8');

    // --- SIMPLE CONTAINER (No skeleton needed - data is preloaded via wpcSettings) ---
    // React renders instantly because initialData is passed via wp_localize_script
    ob_start();
    ?>
    <div class="wpc-root" data-config="<?php echo $config_json; ?>"></div>
    <?php
    return ob_get_clean();
    
    // Icons (Inline SVG)
    $icon_search = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>';
    $icon_chevron = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none"><path d="m6 9 6 6 6-6"/></svg>';
    $icon_filter = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-filter w-5 h-5 text-muted-foreground"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>';
    $icon_plus = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus-circle mr-2 h-4 w-4"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/><path d="M12 8v8"/></svg>';

    // Common Components HTML
    $search_bar_html = '
    <div class="mb-6 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <div class="relative flex-1">
            ' . $icon_search . '
            <input type="text" placeholder="' . __('Search by name...', 'wp-comparison-builder') . '" class="w-full pl-10 pr-4 py-2.5 bg-card border border-border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors" disabled />
        </div>
        <div class="relative min-w-[160px]">
            <select class="w-full appearance-none pl-4 pr-10 py-2.5 bg-card border border-border rounded-xl text-sm cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors" disabled>
                <option>' . __('Sort: Default', 'wp-comparison-builder') . '</option>
            </select>
            ' . $icon_chevron . '
        </div>
        <span class="text-sm text-muted-foreground whitespace-nowrap">' . count($items) . ' ' . (count($items) === 1 ? __('item', 'wp-comparison-builder') : __('items', 'wp-comparison-builder')) . '</span>
    </div>';

    $top_filter_html = '
    <div class="mb-8 p-4 bg-card rounded-xl border border-border shadow-sm">
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex items-center gap-2 mr-2">
                ' . $icon_filter . '
                <span class="font-display font-bold text-lg text-foreground">' . __('Filters', 'wp-comparison-builder') . '</span>
            </div>
            <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 border-dashed px-3" type="button" disabled>
                ' . $icon_plus . '
                Category
            </button>
            <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 border-dashed px-3" type="button" disabled>
                ' . $icon_plus . '
                Platform Features
            </button>
        </div>
    </div>';
    
    $grid_class = "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6";
    if ( $filter_style === 'sidebar' ) {
         $grid_class .= " xl:grid-cols-3";
    } else {
         $grid_class .= " xl:grid-cols-4";
    }

    ob_start();
    ?>
    <div class="wpc-root" data-config="<?php echo $config_json; ?>">
        <div class="wpc-comparison-wrapper bg-background text-foreground min-h-[100px] py-4">
             
             <?php if ($filter_style === 'top'): ?>
                <!-- Top Layout -->
                <?php echo $top_filter_html; ?>
                
                <div class="w-full flex flex-col lg:flex-row gap-8">
                     <div class="flex-1">
                        <?php echo $search_bar_html; ?>
                        
                        <div class="<?php echo esc_attr($grid_class); ?>">
                            <?php foreach ( $items as $item ): 
                                // Render Item (Simplified for Skeleton)
                                $item_id = $item['id'];
                                $rating = $item['rating'];
                                $logo = $item['logo'];
                                $name = $item['name'];
                                $price = $item['price'];
                                $period = $item['period']; 
                                $badge = isset($item['badge']) ? $item['badge'] : null;
                                $featured_badge_text = isset($item['featured_badge_text']) ? $item['featured_badge_text'] : '';
                                
                                $style_attr = "";
                                if (isset($item['design_overrides']) && $item['design_overrides']['enabled'] === true) {
                                    if(!empty($item['design_overrides']['primary'])) {
                                        $primary = wpc_hex2hsl($item['design_overrides']['primary']);
                                        $style_attr .= "--primary: $primary; --ring: $primary; ";
                                    }
                                    if(!empty($item['design_overrides']['accent'])) {
                                        $accent = wpc_hex2hsl($item['design_overrides']['accent']);
                                        $style_attr .= "--accent: $accent; ";
                                    }
                                }
                            ?>
                            <div class="group relative rounded-2xl border bg-card text-card-foreground shadow-sm transition-all hover:shadow-md" style="<?php echo esc_attr($style_attr); ?>">
                                <?php if ( ! empty( $featured_badge_text ) ): ?>
                                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 z-10 px-3 py-1 bg-primary text-primary-foreground text-xs font-bold uppercase tracking-wider rounded-full shadow-sm">
                                        <?php echo esc_html( $featured_badge_text ); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="p-6">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex items-center gap-3">
                                            <?php if ( $logo ): ?>
                                                <div class="w-12 h-12 rounded-lg bg-muted/10 flex items-center justify-center p-1">
                                                    <img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $name ); ?>" class="w-full h-full object-contain" />
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h3 class="font-bold text-lg leading-none mb-1"><?php echo esc_html( $name ); ?></h3>
                                                <div class="flex items-center gap-1">
                                                    <div class="flex text-yellow-400">
                                                        <?php for($i=0; $i<5; $i++) echo ($i < floor($rating)) ? '&#9733;' : '&#9734;'; ?>
                                                    </div>
                                                    <span class="text-xs text-muted-foreground font-medium">(<?php echo esc_html($rating); ?>)</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-6 p-3 bg-muted/30 rounded-xl text-center">
                                         <div class="flex items-baseline justify-center gap-1">
                                            <span class="text-3xl font-bold text-primary"><?php echo esc_html($price); ?></span>
                                            <?php if($period): ?><span class="text-sm font-medium text-muted-foreground"><?php echo esc_html($period); ?></span><?php endif; ?>
                                         </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="h-10 bg-primary/20 rounded"></div>
                                        <div class="h-10 border border-input rounded"></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                     </div>
                </div>

            <?php else: ?>
                <!-- Sidebar Layout (Shortcode) -->
                <div class="w-full flex flex-col lg:grid lg:grid-cols-4 lg:gap-8">
                     <!-- Static Sidebar Skeleton -->
                <!-- Sidebar Layout (Shortcode) -->
                <div class="w-full flex flex-col lg:grid lg:grid-cols-4 lg:gap-8">
                     <!-- Static Sidebar Skeleton -->
                     <div class="lg:col-span-1 border border-border rounded-xl p-6 bg-card mb-8 lg:mb-0 h-fit lg:sticky lg:top-24">
                        <div class="flex items-center gap-2 mb-2 pb-2 border-b border-border">
                            <?php echo $icon_filter; ?>
                            <span class="font-display font-bold text-lg text-foreground"><?php _e('Filters', 'wp-comparison-builder'); ?></span>
                        </div>
                        <div class="space-y-2 py-2 opacity-60">
                            <!-- Categories Skeleton -->
                            <div class="space-y-3 pt-2">
                                <h4 class="text-sm font-bold text-foreground uppercase tracking-wider mb-2">Categories</h4>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-3">
                                        <div class="h-4 w-4 rounded border border-input bg-background"></div>
                                        <div class="h-4 bg-muted rounded w-2/3"></div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="h-4 w-4 rounded border border-input bg-background"></div>
                                        <div class="h-4 bg-muted rounded w-1/2"></div>
                                    </div>
                                </div>
                            </div>
                            <!-- Features Skeleton -->
                            <div class="space-y-3 pt-2">
                                <h4 class="text-sm font-bold text-foreground uppercase tracking-wider mb-2">Features</h4>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-3">
                                        <div class="h-4 w-4 rounded border border-input bg-background"></div>
                                        <div class="h-4 bg-muted rounded w-3/4"></div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="h-4 w-4 rounded border border-input bg-background"></div>
                                        <div class="h-4 bg-muted rounded w-1/2"></div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="h-4 w-4 rounded border border-input bg-background"></div>
                                        <div class="h-4 bg-muted rounded w-2/3"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                     </div>

                     <div class="lg:col-span-3">
                        <?php echo $search_bar_html; ?>
                        <div class="<?php echo esc_attr($grid_class); ?>">
                            <?php foreach ( $items as $item ): 
                                // Render Item (Simplified for Skeleton)
                                $name = $item['name'];
                                $price = $item['price'];
                                $logo = $item['logo'];
                            ?>
                              <div class="group relative rounded-2xl border bg-card text-card-foreground shadow-sm">
                                  <div class="p-6">
                                     <div class="flex items-start justify-between mb-4">
                                        <div class="flex items-center gap-3">
                                             <?php if ( $logo ): ?>
                                                <div class="w-12 h-12 rounded-lg bg-muted/10 p-1 flex items-center justify-center"><img src="<?php echo esc_url( $logo ); ?>" class="w-full h-full object-contain" /></div>
                                            <?php endif; ?>
                                            <div><h3 class="font-bold text-lg"><?php echo esc_html( $name ); ?></h3></div>
                                        </div>
                                     </div>
                                     <div class="mb-6 p-3 bg-muted/30 rounded-xl text-center">
                                         <span class="text-3xl font-bold text-primary"><?php echo esc_html($price); ?></span>
                                     </div>
                                     <div class="grid grid-cols-2 gap-3 opacity-60">
                                         <div class="h-10 bg-primary/20 rounded"></div>
                                         <div class="h-10 border border-input rounded"></div>
                                     </div>
                                  </div>
                              </div>
                            <?php endforeach; ?>
                        </div>
                     </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'wpc_compare', 'wpc_shortcode' );
add_shortcode( 'ecommerce_guider_compare', 'wpc_shortcode' ); // Legacy Support

/**
 * Shortcode for Saved Lists
 * Usage: [wpc_list id="123"]
 */
function wpc_list_shortcode( $atts ) {
    // Ensure assets are loaded
    wp_enqueue_script( 'wpc-app' );
    wp_enqueue_style( 'wpc-styles' );

    $attributes = shortcode_atts( array(
        'id' => '',
        'style' => '',
        'category' => '', // Product Variants Module
    ), $atts );

    // Category Context
    $category_slug = ! empty( $attributes['category'] ) ? sanitize_text_field( $attributes['category'] ) : '';

    if ( empty( $attributes['id'] ) ) return '';

    $post_id = intval( $attributes['id'] );
    
    // Fetch Saved Meta (try new keys first, then legacy)
    // Prepare Billing Cycles
    $billing_cycles = get_post_meta( $post_id, '_wpc_billing_cycles', true );
    if ( ! is_array( $billing_cycles ) || empty( $billing_cycles ) ) {
        // Fallback for legacy data or if not set: create virtual cycles based on old mode
        $billing_mode = get_post_meta( $post_id, '_wpc_billing_mode', true ) ?: 'monthly_only';
        $monthly_label = get_post_meta( $post_id, '_wpc_monthly_label', true ) ?: 'Monthly';
        $yearly_label = get_post_meta( $post_id, '_wpc_yearly_label', true ) ?: 'Yearly';

        $billing_cycles = array();
        // If mode was 'monthly_only' or 'both', add monthly
        if ( $billing_mode !== 'yearly_only' ) {
            $billing_cycles[] = array( 'slug' => 'monthly', 'label' => $monthly_label );
        }
        // If mode was 'yearly_only' or 'both', add yearly
        if ( $billing_mode !== 'monthly_only' ) {
            $billing_cycles[] = array( 'slug' => 'yearly', 'label' => $yearly_label );
        }
    }
    
    // Normalize Plans Data (ensure 'prices' array exists)
    $plans = get_post_meta( $post_id, '_wpc_pricing_plans', true );
    if ( is_array( $plans ) ) {
        foreach ( $plans as &$p ) {
            if ( ! isset($p['prices']) || empty($p['prices']) ) {
                $p['prices'] = array();
                // Map legacy fields
                if ( ! empty($p['price']) ) {
                    $p['prices']['monthly'] = array( 
                        'amount' => $p['price'], 
                        'period' => isset($p['period']) ? $p['period'] : '/mo' 
                    );
                }
                if ( ! empty($p['yearly_price']) ) {
                     $p['prices']['yearly'] = array( 
                        'amount' => $p['yearly_price'], 
                        'period' => isset($p['yearly_period']) ? $p['yearly_period'] : '/yr' 
                    );
                }
            }
        }
    } else {
        $plans = array();
    }

    $default_billing = get_post_meta( $post_id, '_wpc_default_cycle', true );
    if ( ! $default_billing ) {
        $default_billing = get_post_meta( $post_id, '_wpc_default_billing', true ) ?: 'monthly';
    }

    // Initialize variables to prevent undefined warnings
    $hide_features = get_post_meta( $post_id, '_wpc_list_hide_features', true );
    $show_plan_links = get_post_meta( $post_id, '_wpc_list_show_select_table', true );
    $show_plan_links_popup = get_post_meta( $post_id, '_wpc_list_show_select_popup', true );
    $table_btn_pos = get_post_meta( $post_id, '_wpc_list_pt_btn_pos_table', true );
    $popup_btn_pos = get_post_meta( $post_id, '_wpc_list_pt_btn_pos_popup', true );

    $widget_config = array(
        'postId' => $post_id,
        'plans' => $plans,
        'billingCycles' => $billing_cycles, // Pass dynamic cycles
        'defaultBilling' => $default_billing,
        // Legacy props passed for fail-safe, though frontend should prefer billingCycles
        'billingMode' => get_post_meta( $post_id, '_wpc_billing_mode', true ), 
        'monthlyLabel' => get_post_meta( $post_id, '_wpc_monthly_label', true ),
        'yearlyLabel' => get_post_meta( $post_id, '_wpc_yearly_label', true ),
        'category'    => $attributes['category'],
        'showFeatures' => $hide_features !== '1',
        'showPlanLinks' => $show_plan_links === '1',
        'showPlanLinksPopup' => $show_plan_links_popup === '1',
        'tableBtnPos' => $table_btn_pos,
        'popupBtnPos' => $popup_btn_pos, 
        'couponCode'  => get_post_meta( $post_id, '_wpc_coupon_code', true ),
        'showCoupon'  => get_post_meta( $post_id, '_wpc_show_coupon', true ) === '1',
        // Text Overrides...
        'textEmpty' => get_option( 'wpc_text_empty_price', 'Free' ),
        'textFeatures' => get_post_meta( $post_id, '_wpc_txt_feature_header', true ) ?: get_option( 'wpc_text_features', 'Features' ),
        'textCopied'   => get_post_meta( $post_id, '_wpc_txt_copied_label', true ) ?: 'Copied!',
        'textCouponLabel' => get_post_meta( $post_id, '_wpc_txt_coupon_label', true ) ?: 'Use Coupon:',
    );
    
    $ids = get_post_meta( $post_id, '_wpc_list_ids', true );
    if (empty($ids)) $ids = get_post_meta( $post_id, '_hg_list_ids', true );
    
    $featured = get_post_meta( $post_id, '_wpc_list_featured', true );
    if (empty($featured)) $featured = get_post_meta( $post_id, '_hg_list_featured', true );

    $limit = get_post_meta( $post_id, '_wpc_list_limit', true );
    if (empty($limit)) $limit = get_post_meta( $post_id, '_hg_list_limit', true );
    
    // Badge Texts
    $badge_texts_raw = get_post_meta( $post_id, '_wpc_list_badge_texts', true );
    if (empty($badge_texts_raw)) $badge_texts_raw = get_post_meta( $post_id, '_hg_list_badge_texts', true );

    // Badge Colors
    $badge_colors_raw = get_post_meta( $post_id, '_wpc_list_badge_colors', true );
    if (empty($badge_colors_raw)) $badge_colors_raw = get_post_meta( $post_id, '_hg_list_badge_colors', true );

    // New List Features
    $enable_comparison = get_post_meta( $post_id, '_wpc_list_enable_comparison', true );
    if ($enable_comparison === '') $enable_comparison = '1';

    $list_button_text = get_post_meta( $post_id, '_wpc_list_button_text', true );
    $filter_layout = get_post_meta( $post_id, '_wpc_list_filter_layout', true ) ?: 'default';

    $filter_cats = get_post_meta( $post_id, '_wpc_list_filter_cats', true ) ?: [];
    $filter_feats = get_post_meta( $post_id, '_wpc_list_filter_feats', true ) ?: [];
    
    // Show All Items card settings
    $show_all_enabled = get_post_meta( $post_id, '_wpc_list_show_all_enabled', true );
    if ($show_all_enabled === '') $show_all_enabled = '1'; // Default to enabled
    $initial_visible = get_post_meta( $post_id, '_wpc_list_initial_visible', true );
    if (empty($initial_visible)) $initial_visible = 8;

    // --- NEW SETTINGS READ ---
    $cat_label = get_post_meta( $post_id, '_wpc_list_cat_label', true );
    $feat_label = get_post_meta( $post_id, '_wpc_list_feat_label', true );
    
    // Resolve Show Filters (Now just Filter Section)
    $show_filters_opt = get_post_meta( $post_id, '_wpc_list_show_filters_opt', true ) ?: 'default';
    $show_filters = true;
    if ($show_filters_opt === 'show') $show_filters = true;
    elseif ($show_filters_opt === 'hide') $show_filters = false;
    else {
        // Default to Global Filter Setting
        $show_filters = get_option('wpc_show_filters', '1') === '1';
    }

    // Resolve Show Search Bar
    $show_search_opt = get_post_meta( $post_id, '_wpc_list_show_search_opt', true ) ?: 'default';
    $show_search = true;
    if ($show_search_opt === 'show') $show_search = true;
    elseif ($show_search_opt === 'hide') $show_search = false;
    else {
        // Default to Global Search Setting
        $show_search = get_option('wpc_show_search', '1') === '1';
    }

    // --- CONFIGURABLE TEXTS ---
    $txt_compare = get_post_meta( $post_id, '_wpc_list_txt_compare', true ) ?: 'Select to Compare';
    $txt_copied = get_post_meta( $post_id, '_wpc_list_txt_copied', true ) ?: 'Copied!';
    $txt_view = get_post_meta( $post_id, '_wpc_list_txt_view', true ) ?: 'View Details';
    $txt_visit = get_post_meta( $post_id, '_wpc_list_txt_visit', true ) ?: 'Visit Site';

    // Resolve Display Options
    $badge_style = get_post_meta( $post_id, '_wpc_list_badge_style', true ) ?: 'floating';
    $show_rating = get_post_meta( $post_id, '_wpc_list_show_rating', true );
    if($show_rating === '') $show_rating = '1';
    $show_price = get_post_meta( $post_id, '_wpc_list_show_price', true );
    if($show_price === '') $show_price = '1';

    $show_plans_override = get_post_meta( $post_id, '_wpc_list_show_plans', true ); 
    $show_plans = true; 
    if ($show_plans_override === '1') $show_plans = true;
    elseif ($show_plans_override === '0') $show_plans = false;
    else {
        $show_plans = get_option( 'wpc_show_plan_buttons', '1' ) === '1';
    }

    // Verify Source Type to prevent contamination
    $source_type = get_post_meta( $post_id, '_wpc_list_source_type', true ) ?: 'item';
    
    // Fallback: If Tools module is disabled globally, force source to 'item'
    // so we don't try to load tool filters that aren't available/wanted.
    if ( get_option( 'wpc_enable_tools_module' ) !== '1' ) {
        $source_type = 'item';
    }

    // Convert Filter IDs to Names for Frontend
    $filter_cat_names = [];
    $filter_feat_names = [];

    // Process Item Filters (Only if source includes items)
    if ( $source_type === 'item' || $source_type === 'both' ) {
        if (is_array($filter_cats)) {
            foreach($filter_cats as $fcid) {
                // Robust Term Lookup (Handle Migration/Legacy)
                $term = get_term($fcid, 'comparison_category');
                if (!$term || is_wp_error($term)) {
                    $term = get_term($fcid, 'ecommerce_type'); // Legacy Check
                }
                if (!$term || is_wp_error($term)) {
                    $term = get_term($fcid); // Generic Check
                }

                if($term && !is_wp_error($term)) $filter_cat_names[] = $term->name;
            }
        }

        if (is_array($filter_feats)) {
            foreach($filter_feats as $ffid) {
                // Robust Term Lookup (Handle Migration/Legacy)
                $term = get_term($ffid, 'comparison_feature');
                if (!$term || is_wp_error($term)) {
                    $term = get_term($ffid, 'ecommerce_feature'); // Legacy Check
                }
                if (!$term || is_wp_error($term)) {
                    $term = get_term($ffid); // Generic Check
                }

                if($term && !is_wp_error($term)) $filter_feat_names[] = $term->name;
            }
        }
    }
    
    // Process Tool Filters (Only if source includes tools)
    if ( $source_type === 'tool' || $source_type === 'both' ) {
        $filter_tool_cats = get_post_meta( $post_id, '_wpc_list_filter_tool_cats', true ) ?: [];
        $filter_tool_tags = get_post_meta( $post_id, '_wpc_list_filter_tool_tags', true ) ?: [];

        if (is_array($filter_tool_cats)) {
            foreach($filter_tool_cats as $fcid) {
                $term = get_term($fcid, 'tool_category');
                if($term && !is_wp_error($term)) $filter_cat_names[] = $term->name;
            }
        }

        if (is_array($filter_tool_tags)) {
            foreach($filter_tool_tags as $ffid) {
                $term = get_term($ffid, 'tool_tag');
                if($term && !is_wp_error($term)) $filter_feat_names[] = $term->name;
            }
        }
    }
    
    // Normalize Badge Data for JSON
    $badge_texts = array();
    if ( ! empty( $badge_texts_raw ) && is_array( $badge_texts_raw ) ) {
        foreach ( $badge_texts_raw as $pid => $text ) {
            if ( ! empty( $text ) ) $badge_texts[ strval( $pid ) ] = $text;
        }
    }
    
    $badge_colors = array();
    if ( ! empty( $badge_colors_raw ) && is_array( $badge_colors_raw ) ) {
        foreach ( $badge_colors_raw as $pid => $color ) {
            if ( ! empty( $color ) ) $badge_colors[ strval( $pid ) ] = $color;
        }
    }

    // Convert arrays to comma-separated strings
    $ids_str = !empty($ids) ? implode(',', (array)$ids) : '';
    $featured_str = !empty($featured) ? implode(',', (array)$featured) : '';
    $specific_ids = !empty($ids_str) ? array_map('trim', explode(',', $ids_str)) : [];

    // 1. Fetch Data
    if ( ! function_exists( 'wpc_get_items' ) ) { }
    $data = wpc_get_items();
    $items = $data['items'];

    // 2. Filter & Modify Data
    if ( ! empty( $specific_ids ) ) {
        $items = array_filter( $items, function($item) use ($specific_ids) {
            return in_array( $item['id'], $specific_ids );
        });
    }
    
    // Product Variants Module: Filter by Category
    if ( ! empty( $category_slug ) ) {
        $items = array_filter( $items, function($item) use ($category_slug) {
            // If item has variants enabled, strict check category availability
            if ( isset($item['variants']) && $item['variants']['enabled'] === true ) {
                $plans_map = $item['variants']['plans_by_category'] ?? [];
                if ( empty( $plans_map[ $category_slug ] ) ) {
                    return false; // Exclude if no plans for this category
                }
            }
            return true;
        });
    }

    if ( ! empty( $specific_ids ) ) {
        // Sort items: Featured First, then by Saved Order
        // Prepare featured IDs for easy lookup
        $featured_ids_array = !empty($featured) ? (array)$featured : [];
        
        usort($items, function($a, $b) use ($specific_ids, $featured_ids_array) {
            $is_a_featured = in_array($a['id'], $featured_ids_array);
            $is_b_featured = in_array($b['id'], $featured_ids_array);
            
            // Primary Sort: Featured items first
            if ($is_a_featured && !$is_b_featured) return -1;
            if (!$is_a_featured && $is_b_featured) return 1;
            
            // Secondary Sort: Saved Drag-and-Drop Order
            $pos_a = array_search($a['id'], $specific_ids);
            $pos_b = array_search($b['id'], $specific_ids);
            return $pos_a - $pos_b;
        });
        
        // Apply Badge Overrides
        $items = array_map(function($item) use ($badge_texts, $badge_colors) {
            if (isset($badge_texts[$item['id']])) {
                $item['featured_badge_text'] = $badge_texts[$item['id']];
            }
            if (isset($badge_colors[$item['id']])) {
                // Determine if we should override the main featured color or just the badge logic
                // React logic overrides 'featured_badge_color' property.
                $item['featured_badge_color'] = $badge_colors[$item['id']];
            }
            return $item;
        }, $items);
    } else {
        // If no IDs are specifically saved, we should show NOTHING (empty list), 
        // DO NOT fallback to showing all items.
        $items = [];
    }
    
    // Limit
    if ( $limit > 0 ) {
        $items = array_slice( $items, 0, $limit );
    }

    // Extract sorted IDs to ensure JS follows the exact same order
    $sorted_ids = array_column($items, 'id');

    // Get list-specific color overrides for React inline styles
    $colors_override = array();
    
    if (get_post_meta($post_id, '_wpc_list_use_primary', true)) {
        $colors_override['primary'] = get_post_meta($post_id, '_wpc_list_primary_color', true);
    }
    if (get_post_meta($post_id, '_wpc_list_use_accent', true)) {
        $colors_override['accent'] = get_post_meta($post_id, '_wpc_list_accent_color', true);
    }
    if (get_post_meta($post_id, '_wpc_list_use_hover', true)) {
        $colors_override['hoverButton'] = get_post_meta($post_id, '_wpc_list_hover_color', true);
    }
    if (get_post_meta($post_id, '_wpc_list_use_secondary', true)) {
        $colors_override['secondary'] = get_post_meta($post_id, '_wpc_list_secondary_color', true);
    }
    
    // PT Visual Style Overrides
    $pt_visuals_override = array();
    
    if (get_post_meta($post_id, '_wpc_list_use_pt_header_bg', true)) {
        $pt_visuals_override['wpc_pt_header_bg'] = get_post_meta($post_id, '_wpc_list_pt_header_bg', true);
    }
    if (get_post_meta($post_id, '_wpc_list_use_pt_header_text', true)) {
        $pt_visuals_override['wpc_pt_header_text'] = get_post_meta($post_id, '_wpc_list_pt_header_text', true);
    }
    if (get_post_meta($post_id, '_wpc_list_use_pt_btn_bg', true)) {
        $pt_visuals_override['wpc_pt_btn_bg'] = get_post_meta($post_id, '_wpc_list_pt_btn_bg', true);
    }
    if (get_post_meta($post_id, '_wpc_list_use_pt_btn_text', true)) {
        $pt_visuals_override['wpc_pt_btn_text'] = get_post_meta($post_id, '_wpc_list_pt_btn_text', true);
    }
    
    // PT Button Positions (select dropdowns - no checkbox needed)
    $pt_btn_pos_table = get_post_meta($post_id, '_wpc_list_pt_btn_pos_table', true);
    if (!empty($pt_btn_pos_table)) {
        $pt_visuals_override['wpc_pt_btn_pos_table'] = $pt_btn_pos_table;
    }
    $pt_btn_pos_popup = get_post_meta($post_id, '_wpc_list_pt_btn_pos_popup', true);
    if (!empty($pt_btn_pos_popup)) {
        $pt_visuals_override['wpc_pt_btn_pos_popup'] = $pt_btn_pos_popup;
    }

    // 3. Render HTML - Prepare Config
    // Determine Grid Class & Filter Style (Resolved)
    $filter_style = get_option( 'wpc_filter_style', 'top' );
    if ($filter_layout !== 'default' && !empty($filter_layout)) {
        $filter_style = $filter_layout;
    }

    // Determine Search Type (Resolved)
    $global_search_type = get_option( 'wpc_search_type', 'text' );
    $list_search_type = get_post_meta( $post_id, '_wpc_list_search_type', true );
    $final_search_type = ($list_search_type && $list_search_type !== 'default') ? $list_search_type : $global_search_type;

    // Determine List Style (Resolved)
    $global_style = get_option( 'wpc_default_list_style', 'grid' );
    $list_style = get_post_meta( $post_id, '_wpc_list_style', true );
    
    // 1. Shortcode Attribute
    if (!empty($attributes['style'])) {
        $final_style = $attributes['style'];
    } 
    // 2. List Settings
    elseif ($list_style && $list_style !== 'default') {
        $final_style = $list_style;
    }
    // 3. Global Default
    else {
        $final_style = $global_style;
    }


    $grid_class = "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6";
    if ( $filter_style === 'sidebar' ) {
         $grid_class .= " xl:grid-cols-3";
    } else {
         $grid_class .= " xl:grid-cols-4";
    }

    // Badge Style & Element Visibility
    $badge_style_opt = get_post_meta($post_id, '_wpc_list_badge_style', true);
    $badge_style = ($badge_style_opt && $badge_style_opt !== 'default') ? $badge_style_opt : 'floating';

    // Element Visibility
    $show_rating = get_post_meta($post_id, '_wpc_list_show_rating', true);
    $show_price = get_post_meta($post_id, '_wpc_list_show_price', true);

    // --- CONFIGURABLE TEXTS (List Override > Global Default > Hardcoded Fallback) ---
    // Map: Frontend Key => [ Meta Key, Global Opt, Default ]
    $text_fields = [
        // General UI
        'viewDetails'     => ['meta' => '_wpc_list_txt_view_details', 'global' => 'wpc_text_view_details', 'default' => 'View Details'],
        'compareAlternatives' => ['meta' => '_wpc_list_txt_compare_alts', 'global' => 'wpc_text_compare_alternatives', 'default' => 'Compare Alternatives'],
        'compareNow'      => ['meta' => '_wpc_list_txt_compare_now', 'global' => 'wpc_text_compare_now', 'default' => 'Compare Now'],
        'readReview'      => ['meta' => '_wpc_list_txt_reviews', 'global' => 'wpc_text_reviews', 'default' => 'Read Review'],
        'backToReviews'   => ['meta' => '_wpc_list_txt_back_reviews', 'global' => 'wpc_text_back_to_reviews', 'default' => 'Back to Reviews'],
        'filters'         => ['meta' => '_wpc_list_txt_filters', 'global' => 'wpc_text_filters', 'default' => 'Filters'],
        'searchPlaceholder'=> ['meta' => '_wpc_list_txt_search_ph', 'global' => 'wpc_text_search_placeholder', 'default' => 'Search...'],
        'categoryLabel'   => ['meta' => '_wpc_list_cat_label', 'global' => 'wpc_text_category', 'default' => 'Filter by Category'],
        'featuresLabel'   => ['meta' => '_wpc_list_feat_label', 'global' => 'wpc_text_features', 'default' => 'Features'],
        'itemsFound'      => ['meta' => '_wpc_list_items_count', 'global' => 'wpc_text_items_count', 'default' => 'items found'],
        'selected'        => ['meta' => '_wpc_list_txt_selected', 'global' => 'wpc_text_selected', 'default' => 'Selected:'],
        'clearAll'        => ['meta' => '_wpc_list_txt_clear_all', 'global' => 'wpc_text_clear_all', 'default' => 'Clear All'],
        'compareBtn'      => ['meta' => '_wpc_list_txt_compare_btn', 'global' => 'wpc_text_compare_btn', 'default' => 'Compare'], 
        'visitSite'       => ['meta' => '_wpc_list_txt_visit', 'global' => 'wpc_text_visit', 'default' => 'Visit Site'],
        'visitPlat'       => ['meta' => '_wpc_list_txt_visit_plat', 'global' => 'wpc_text_visit_plat', 'default' => 'Visit %s'],
        'about'           => ['meta' => '_wpc_list_txt_about', 'global' => 'wpc_text_about', 'default' => 'About'],
        'comparisonHeader'=> ['meta' => '_wpc_list_txt_comp_header', 'global' => 'wpc_text_comp_header', 'default' => 'Detailed Comparison'],
        'selectToCompare' => ['meta' => '_wpc_list_txt_compare', 'global' => 'wpc_text_compare', 'default' => 'Select to Compare'],
        'copied'          => ['meta' => '_wpc_list_txt_copied', 'global' => 'wpc_text_copied', 'default' => 'Copied!'],
        'noResults'       => ['meta' => '_wpc_list_txt_no_results', 'global' => 'wpc_text_no_results', 'default' => 'No items match your filters.'],
        'activeFilters'   => ['meta' => '_wpc_list_txt_active_filt', 'global' => 'wpc_text_active_filt', 'default' => 'Active filters:'],

        // Sorting
        'sortDefault'     => ['meta' => '_wpc_list_txt_sort_def', 'global' => 'wpc_text_sort_def', 'default' => 'Sort: Default'],
        'sortNameAsc'     => ['meta' => '_wpc_list_txt_sort_asc', 'global' => 'wpc_text_sort_asc', 'default' => 'Name (A-Z)'],
        'sortNameDesc'    => ['meta' => '_wpc_list_txt_sort_desc', 'global' => 'wpc_text_sort_desc', 'default' => 'Name (Z-A)'],
        'sortRating'      => ['meta' => '_wpc_list_txt_sort_rating', 'global' => 'wpc_text_sort_rating', 'default' => 'Highest Rated'],
        'sortPrice'       => ['meta' => '_wpc_list_txt_sort_price', 'global' => 'wpc_text_sort_price', 'default' => 'Lowest Price'],
        
        // Features
        'getCoupon'       => ['meta' => '_wpc_list_txt_get_coupon', 'global' => 'wpc_text_get_coupon', 'default' => 'Get Coupon:'],
        'featuredBadge'   => ['meta' => '_wpc_list_txt_featured', 'global' => 'wpc_text_featured', 'default' => 'Featured'],
        'featureProducts' => ['meta' => '_wpc_list_txt_feat_prod', 'global' => 'wpc_text_feat_prod', 'default' => 'Products'],
        'featureFees'     => ['meta' => '_wpc_list_txt_feat_fees', 'global' => 'wpc_text_feat_fees', 'default' => 'Trans. Fees'],
        'featureChannels' => ['meta' => '_wpc_list_txt_feat_channels', 'global' => 'wpc_text_feat_channels', 'default' => 'Sales Channels'],
        'featureSsl'      => ['meta' => '_wpc_list_txt_feat_ssl', 'global' => 'wpc_text_feat_ssl', 'default' => 'Free SSL'],
        'featureSupport'  => ['meta' => '_wpc_list_txt_feat_supp', 'global' => 'wpc_text_feat_supp', 'default' => 'Support'],

        // New Comparison Table Headers (CamelCase for consistency)
        'featureHeader'   => ['meta' => '_wpc_list_txt_feat_header', 'global' => 'wpc_text_feat_header', 'default' => 'Feature'],
        'prosLabel'       => ['meta' => '_wpc_list_txt_pros', 'global' => 'wpc_text_pros', 'default' => 'Pros'],
        'consLabel'       => ['meta' => '_wpc_list_txt_cons', 'global' => 'wpc_text_cons', 'default' => 'Cons'],
        'priceLabel'      => ['meta' => '_wpc_list_txt_price', 'global' => 'wpc_text_price', 'default' => 'Price'],
        'ratingLabel'     => ['meta' => '_wpc_list_txt_rating', 'global' => 'wpc_text_rating', 'default' => 'Rating'],
        'moSuffix'        => ['meta' => '_wpc_list_txt_mo_suffix', 'global' => 'wpc_text_mo_suffix', 'default' => '/mo'],
        
        // Missing Frontend Labels
        'noItemsToCompare'=> ['meta' => '_wpc_list_txt_no_compare', 'global' => 'wpc_text_no_compare', 'default' => 'Select up to 4 items to compare'],
        'remove'          => ['meta' => '_wpc_list_txt_remove', 'global' => 'wpc_text_remove', 'default' => 'Remove'],
        'logoLabel'       => ['meta' => '_wpc_list_txt_logo', 'global' => 'wpc_text_logo', 'default' => 'Logo'], // "Logo" fallback text
        'analysisBase'    => ['meta' => '_wpc_list_txt_analysis', 'global' => 'wpc_text_analysis', 'default' => '(Based on our analysis)'],
        'startingPrice'   => ['meta' => '_wpc_list_txt_start_price', 'global' => 'wpc_text_start_price', 'default' => 'Starting Price'],
        'dashboardPreview'=> ['meta' => '_wpc_list_txt_dash_prev', 'global' => 'wpc_text_dash_prev', 'default' => 'Dashboard Preview'],
        
        // Filter & Search Internal Labels
        'resetFilters'    => ['meta' => '_wpc_list_txt_reset_filt', 'global' => 'wpc_text_reset_filt', 'default' => 'Reset Filters'],
        'select'          => ['meta' => '_wpc_list_txt_select_fmt', 'global' => 'wpc_text_select_fmt', 'default' => 'Select %s'],
        'clear'           => ['meta' => '_wpc_list_txt_clear', 'global' => 'wpc_text_clear', 'default' => 'Clear'],
        'selectProvider'  => ['meta' => '_wpc_list_txt_sel_prov', 'global' => 'wpc_text_sel_prov', 'default' => 'Select provider...'],
        'noItemFound'     => ['meta' => '_wpc_list_txt_no_item', 'global' => 'wpc_text_no_item', 'default' => 'No item found.'],
        'more'            => ['meta' => '_wpc_list_txt_more', 'global' => 'wpc_text_more', 'default' => 'more'],
        
        // Additional UI Texts
        'showAllItems'    => ['meta' => '_wpc_list_txt_show_all', 'global' => 'wpc_text_show_all', 'default' => 'Show All Items'],
        'revealMore'      => ['meta' => '_wpc_list_txt_reveal_more', 'global' => 'wpc_text_reveal_more', 'default' => 'Click to reveal'],
        'noLogo'          => ['meta' => '_wpc_list_txt_no_logo', 'global' => 'wpc_text_no_logo', 'default' => 'No Logo'],
    ];

    $labels = [];
    foreach ($text_fields as $key => $source) {
        $val = get_post_meta($post_id, $source['meta'], true);
        if (empty($val)) {
            $val = get_option($source['global'], $source['default']);
            if (empty($val)) $val = $source['default']; // extra safety
        }
        $labels[$key] = $val;
    }

    // Split Comparison Settings
    $show_checkboxes_opt = get_post_meta($post_id, '_wpc_list_show_checkboxes', true);
    // Default to true if not set (backwards compatibility) or explicitly "1"
    $show_checkboxes = ($show_checkboxes_opt === '' || $show_checkboxes_opt === '1');
    
    $view_action = get_post_meta($post_id, '_wpc_list_view_action', true) ?: 'popup';

    // Check for Features Override
    $features_override = get_post_meta( $post_id, '_wpc_list_features_override', true ) === '1';
    $custom_features = null;
    if ( $features_override ) {
        $custom_features = get_post_meta( $post_id, '_wpc_list_features', true );
        // Ensure it's an array if empty
        if ( ! is_array( $custom_features ) ) {
            $custom_features = array();
        }
    }

    $config = array(
        'initialItems' => array_values($items), // Pass the heavily filtered items to frontend
        'ids'      => $sorted_ids,
        'style'    => $final_style, // Pass resolved style
        'featured' => !empty($featured_str) ? array_map('trim', explode(',', $featured_str)) : [],
        // Pass Override Features if enabled
        'compareFeatures' => $features_override ? $custom_features : null, 
        'category' => '', 
        'limit'    => intval($limit),
        'enableComparison' => $enable_comparison === '1', // Footer Bar
        'showCheckboxes' => $show_checkboxes, // Selection Circles
        'viewAction' => $view_action, // "popup" or "link"
        'buttonText' => $list_button_text,
        'filterLayout' => $filter_style, // Pass RESOLVED style
        'showFilters' => $show_filters, // Filter Section Only
        'showSearchBar' => $show_search, // Search Bar Only
        'searchType' => $final_search_type,
        'filterCats' => $filter_cat_names,
        'filterFeats' => $filter_feat_names,
        'badge_texts' => $badge_texts,
        'badge_colors' => $badge_colors,
        'categoriesLabel' => $cat_label,
        'featuresLabel' => $feat_label,
        'showPlanButtons' => $show_plans,
        'showAllEnabled' => $show_all_enabled === '1',
        'initialVisible' => intval($initial_visible),
        'badgeStyle' => $badge_style,
        'showRating' => $show_rating === '1',
        'showPrice' => $show_price === '1',
        'colorsOverride' => !empty($colors_override) ? $colors_override : null,
        'ptVisuals' => !empty($pt_visuals_override) ? $pt_visuals_override : null, // Fix key name to match React prop if needed, or use ptVisuals
        'visualsOverride' => !empty($pt_visuals_override) ? $pt_visuals_override : null,
        
        // Layout & Behavior (List > Global > Default)
        'ptBtnPosTable' => ($l_btn = get_post_meta($post_id, '_wpc_list_pt_btn_pos_table', true)) && $l_btn !== 'default' ? $l_btn : get_option('wpc_pt_btn_pos_table', 'after_price'),
        'ptBtnPosPopup' => ($l_btn_p = get_post_meta($post_id, '_wpc_list_pt_btn_pos_popup', true)) && $l_btn_p !== 'default' ? $l_btn_p : get_option('wpc_pt_btn_pos_popup', 'after_price'),
        
        // Button Visibility (List > Item > Global)
        'showSelectTable'  => wpc_resolve_bool_setting($post_id, '_wpc_list_show_select_table', null, 'wpc_show_select_table', true),
        'showSelectPopup'  => wpc_resolve_bool_setting($post_id, '_wpc_list_show_select_popup', null, 'wpc_show_select_popup', true),
        'showFooterTable'  => wpc_resolve_bool_setting($post_id, '_wpc_list_show_footer_table', null, 'wpc_show_footer_table', true),
        'showFooterPopup'  => wpc_resolve_bool_setting($post_id, '_wpc_list_show_footer_popup', null, 'wpc_show_footer_popup', true),
        'hideFeatures'     => wpc_resolve_bool_setting($post_id, '_wpc_list_hide_features', null, 'wpc_hide_features', false),
        
        'targetDetails' => ($l_tgt_d = get_post_meta($post_id, '_wpc_list_target_details', true)) && $l_tgt_d !== 'default' ? $l_tgt_d : (get_option('wpc_link_target_details', '1') === '1' ? '_blank' : '_self'),
        'targetDirect'  => ($l_tgt_dir = get_post_meta($post_id, '_wpc_list_target_direct', true)) && $l_tgt_dir !== 'default' ? $l_tgt_dir : (get_option('wpc_link_target_direct', '1') === '1' ? '_blank' : '_self'),
        'targetPricing' => ($l_tgt_pr = get_post_meta($post_id, '_wpc_list_target_pricing', true)) && $l_tgt_pr !== 'default' ? $l_tgt_pr : (get_option('wpc_link_target_pricing', '1') === '1' ? '_blank' : '_self'),

        // Configurable Texts
        'labels' => $labels,
        
        // Configurable Colors (Per-List > Global > Default)
        'colors' => [
            'prosBg'      => get_post_meta($post_id, '_wpc_list_color_pros_bg', true) ?: get_option('wpc_color_pros_bg', '#f0fdf4'),
            'prosText'    => get_post_meta($post_id, '_wpc_list_color_pros_text', true) ?: get_option('wpc_color_pros_text', '#166534'),
            'consBg'      => get_post_meta($post_id, '_wpc_list_color_cons_bg', true) ?: get_option('wpc_color_cons_bg', '#fef2f2'),
            'consText'    => get_post_meta($post_id, '_wpc_list_color_cons_text', true) ?: get_option('wpc_color_cons_text', '#991b1b'),
            'couponBg'    => get_post_meta($post_id, '_wpc_list_color_coupon_bg', true) ?: get_option('wpc_color_coupon_bg', '#fef3c7'),
            'couponText'  => get_post_meta($post_id, '_wpc_list_color_coupon_text', true) ?: get_option('wpc_color_coupon_text', '#92400e'),
            'couponHover' => get_post_meta($post_id, '_wpc_list_color_coupon_hover', true) ?: get_option('wpc_color_coupon_hover', '#fde68a'),
            'copied'      => get_post_meta($post_id, '_wpc_list_color_copied', true) ?: get_option('wpc_color_copied', '#10b981'),
            'tick'        => get_post_meta($post_id, '_wpc_list_color_tick', true) ?: get_option('wpc_color_tick', '#10b981'),
            'cross'       => get_post_meta($post_id, '_wpc_list_color_cross', true) ?: get_option('wpc_color_cross', '#94a3b8'),
            'category'    => $category_slug, // Product Variants Module
        ],
    );
    
    $config_json = htmlspecialchars(json_encode($config), ENT_QUOTES, 'UTF-8');

    // SCOPED CSS GENERATION
    
    // SCOPED CSS GENERATION
    $unique_id = 'wpc-list-' . $post_id . '-' . mt_rand(1000, 9999);
    $scoped_styles = "";
    
    $use_primary = get_post_meta( $post_id, '_wpc_list_use_primary', true );
    if ($use_primary) {
        $c = get_post_meta( $post_id, '_wpc_list_primary_color', true );
        if($c) {
             $hsl = wpc_hex2hsl($c);
             $scoped_styles .= "--primary: {$hsl}; --ring: {$hsl}; "; 
        }
    }
    
    $use_accent = get_post_meta( $post_id, '_wpc_list_use_accent', true );
    if ($use_accent) {
        $c = get_post_meta( $post_id, '_wpc_list_accent_color', true );
        if($c) {
             $hsl = wpc_hex2hsl($c);
             $scoped_styles .= "--accent: {$hsl}; "; 
        }
    }

    $use_secondary = get_post_meta( $post_id, '_wpc_list_use_secondary', true );
    if ($use_secondary) {
        $c = get_post_meta( $post_id, '_wpc_list_secondary_color', true );
        if($c) {
             $hsl = wpc_hex2hsl($c);
             $scoped_styles .= "--secondary: {$hsl}; "; 
        }
    }
    
    $use_hover = get_post_meta( $post_id, '_wpc_list_use_hover', true );
    if ($use_hover) {
        $c = get_post_meta( $post_id, '_wpc_list_hover_color', true );
        if($c) {
             $scoped_styles .= "--wpc-btn-hover: {$c}; "; 
        }
    }
    
    $css_rules = "";
    if (!empty($scoped_styles)) {
        $css_rules .= "#{$unique_id} { {$scoped_styles} } ";
    }
    
    // Non-variable overrides
    $use_border = get_post_meta( $post_id, '_wpc_list_use_border', true );
    if($use_border) {
        $c = get_post_meta( $post_id, '_wpc_list_border_color', true );
        if($c) $css_rules .= "#{$unique_id} .bg-card { border-color: {$c} !important; } ";
    }
    
    $use_banner = get_post_meta( $post_id, '_wpc_list_use_banner', true );
    if($use_banner) {
        $c = get_post_meta( $post_id, '_wpc_list_banner_color', true );
         // Assuming we can pass this as a variable or we need to target a specific class. 
         // For now let's set a CSS var --wpc-banner
        if($c) $scoped_styles .= "--wpc-banner: {$c}; "; 
    }

    // --- SIMPLE CONTAINER (No skeleton needed - data is preloaded via wpcSettings) ---
    // React renders instantly because initialData is passed via wp_localize_script
    ob_start();
    ?>
    <?php if(!empty($css_rules)): ?>
    <style><?php echo $css_rules; ?></style>
    <?php endif; ?>

    <!-- Skeleton placeholder to prevent CLS while React loads -->
    <div id="<?php echo esc_attr($unique_id); ?>" class="wpc-root" data-config="<?php echo $config_json; ?>" style="min-height: 600px;">
        <div class="wpc-skeleton" style="padding: 1rem;">
            <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                <div style="flex: 1; height: 40px; background: #f3f4f6; border-radius: 8px;"></div>
                <div style="width: 120px; height: 40px; background: #f3f4f6; border-radius: 8px;"></div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;">
                <?php for ($i = 0; $i < 6; $i++) : ?>
                <div style="height: 280px; background: #f9fafb; border-radius: 12px; border: 1px solid #e5e7eb;"></div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
    <?php
    $html = ob_get_clean();
    
    // Add schema output for SEO
    $schema_output = '';
    if ( function_exists( 'wpc_generate_list_schema' ) ) {
        $schema_output = wpc_generate_list_schema( $post_id );
    }
    
    return $schema_output . $html;
}

add_shortcode( 'wpc_list', 'wpc_list_shortcode' );
add_shortcode( 'ecommerce_guider_list', 'wpc_list_shortcode' ); // Legacy Support


/**
 * Shortcode: [wpc_pricing_table id="123"]
 * Displays the pricing table for a specifically identified item inline.
 * Supports overrides for colors and button visibility.
 */
function wpc_pricing_table_shortcode( $atts ) {
    // Ensure assets are loaded
    wp_enqueue_script( 'wpc-app' );
    wp_enqueue_style( 'wpc-styles' );

    $attributes = shortcode_atts( array(
        'id' => '',
        'primary_color' => '',
        'accent_color' => '',
        'banner_color' => '',
        'show_plan_buttons' => '', // '1'/'true' or '0'/'false'
        'show_footer_button' => '1',
        'footer_button_text' => '',
        'category' => '', // Product Variants Module
    ), $atts );

    if ( empty( $attributes['id'] ) ) return ''; // ID is required

    $item_id = $attributes['id'];

    // 1. Fetch Data (Optimized)
    if ( ! function_exists( 'wpc_fetch_items_data' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'includes/api-endpoints.php';
    }

    $data = wpc_fetch_items_data( array( $item_id ) );
    
    if ( empty( $data['items'] ) ) {
        return "<!-- WPC Pricing Table: Item ID {$item_id} not found -->";
    }
    
    $item = $data['items'][0];

    // 2. Resolve Settings
    // Priority: Shortcode Attr > Post Meta > Default
    
    // Plan Buttons
    // Priority: Shortcode Attr > Post Meta (explicit setting) > Global Default
    $show_plan_links_meta = get_post_meta($item['id'], '_wpc_show_plan_links', true); // Old meta
    $show_plan_btns_meta = get_post_meta($item['id'], '_wpc_show_plan_buttons', true); // New specific meta (1 or 0)
    
    $show_plan_buttons_val = $attributes['show_plan_buttons'];
    $show_plan_buttons = null; 
    
    // 1. Shortcode explicit
    if ($show_plan_buttons_val === '1' || strtolower($show_plan_buttons_val) === 'true') {
        $show_plan_buttons = true;
    } elseif ($show_plan_buttons_val === '0' || strtolower($show_plan_buttons_val) === 'false') {
        $show_plan_buttons = false;
    } 
    // 2. New Meta (Pricing Table Design)
    elseif ($show_plan_btns_meta !== '') {
        $show_plan_buttons = ($show_plan_btns_meta === '1');
    }
    // 3. Old Meta (List config) - check for explicit values
    elseif ( $show_plan_links_meta !== '' ) {
         $show_plan_buttons = ($show_plan_links_meta === '1');
    }
    // 4. Default: show buttons
    else {
        $show_plan_buttons = true;
    }
    
    // Footer Button
    $show_footer_meta = get_post_meta($item['id'], '_wpc_show_footer_button', true);
    if ($attributes['show_footer_button'] !== '1' && $attributes['show_footer_button'] !== '') {
         if ($attributes['show_footer_button'] === '0' || strtolower($attributes['show_footer_button']) === 'false') {
             $show_footer_button = false;
         } else {
             $show_footer_button = true; 
         }
    } else {
        if ($show_footer_meta === '0') {
            $show_footer_button = false;
        } else {
            $show_footer_button = true;
        }
    }

    $footer_text_meta = get_post_meta($item['id'], '_wpc_footer_button_text', true);
    $footer_button_text = !empty($attributes['footer_button_text']) ? $attributes['footer_button_text'] : $footer_text_meta;

    // 3. Scoped CSS
    $unique_id = 'wpc-pricing-' . $item_id . '-' . mt_rand(1000, 9999);
    $scoped_styles = "";
    
    // Check if Overrides are Enabled
    $enable_overrides = get_post_meta($item['id'], '_wpc_enable_design_overrides', true);

    // Only apply overriding styles if enabled (or if shortcode explicitly forces them via attrs?)
    // Decision: Shortcode attrs always win. Meta overrides only if enabled.
    
    // Colors
    $primary_meta = ($enable_overrides === '1') ? get_post_meta($item['id'], '_wpc_primary_color', true) : '';
    $accent_meta = ($enable_overrides === '1') ? get_post_meta($item['id'], '_wpc_accent_color', true) : '';
    $border_meta = ($enable_overrides === '1') ? get_post_meta($item['id'], '_wpc_border_color', true) : '';

    $primary_col = !empty($attributes['primary_color']) ? $attributes['primary_color'] : $primary_meta;
    $accent_col = !empty($attributes['accent_color']) ? $attributes['accent_color'] : $accent_meta;
    $border_col = $border_meta; // No shortcode attr for border currently, use meta

    // Helper closure to add var if color present
    $add_color_var = function($hex, $var_name) use (&$scoped_styles) {
        if (!empty($hex)) {
            // HSL conversion for primary/accent
            if (in_array($var_name, ['primary', 'accent'])) {
                $hsl = wpc_hex2hsl($hex);
                $scoped_styles .= "--{$var_name}: {$hsl}; ";
                if ($var_name === 'primary') $scoped_styles .= "--ring: {$hsl}; "; 
            } else {
                // Direct hex for border or other vars
                $scoped_styles .= "--{$var_name}: {$hex}; ";
            }
        }
    };
    
    $add_color_var($primary_col, 'primary');
    $add_color_var($accent_col, 'accent');
    $add_color_var($border_col, 'border-color-override'); // Custom var name to avoid conflict with tailwind base --border

    $css_rules = "";
    if (!empty($scoped_styles)) {
        $css_rules = "#{$unique_id} { {$scoped_styles} }";
        // Also apply to nested children that might need it if they use variables
        // But variables cascade, so ID wrapper should be enough.
    }

    if (!empty($css_rules)) {
        wp_register_style( 'wpc-pricing-inline-' . $unique_id, false );
        wp_enqueue_style( 'wpc-pricing-inline-' . $unique_id );
        wp_add_inline_style( 'wpc-pricing-inline-' . $unique_id, $css_rules );
    }

    // Config for React App
    // We pass the "view mode" as 'pricing-table'
    // item_id allows the app to fetch the full item data via API if needed, 
    // BUT we usually rely on `wpc_get_items` data embedded in window or fetched.
    // Here we might need to seed the data if it's a standalone shortcode.
    // The main-wp.tsx uses `window.wpc_items` or fetches. 
    // For single item shortcode, we should ensure this item is available.
    
    // We'll construct a direct "initial data" object for this widget instance if possible,
    // or rely on the standard `ecommerce-guider-data` localization.
    
    // Pass specific display settings for this instance
    // Category Context
    $category_slug = !empty($attributes['category']) ? sanitize_text_field($attributes['category']) : '';

    // Billing Mode Settings
    $billing_mode = get_post_meta($item['id'], '_wpc_billing_mode', true) ?: 'monthly_only';
    $monthly_label = get_post_meta($item['id'], '_wpc_monthly_label', true) ?: 'Pay monthly';
    $yearly_label = get_post_meta($item['id'], '_wpc_yearly_label', true) ?: 'Pay yearly (save 25%)*';
    $default_billing = get_post_meta($item['id'], '_wpc_default_billing', true) ?: 'monthly';

    $widget_config = [
        'viewMode' => 'pricing-table', // MATCHES main-wp.tsx check
        'item' => $item, // REQUIRED by main-wp.tsx to enter the block
        'showPlanButtons' => $show_plan_buttons,
        'showFooterButton' => $show_footer_button,
        'footerButtonText' => $footer_button_text,
        'displayContext' => 'inline', // Tell React this is the Shortcode view (Table)
        'category' => $category_slug, // Product Variants
        
        // Billing Mode Settings
        'billingMode' => $billing_mode, // 'monthly_only', 'yearly_only', or 'both'
        'monthlyLabel' => $monthly_label,
        'yearlyLabel' => $yearly_label,
        'defaultBilling' => $default_billing,
        'billingCycles' => get_post_meta($item['id'], '_wpc_billing_cycles', true) ?: [],
        'defaultCycle' => get_post_meta($item['id'], '_wpc_default_cycle', true) ?: 'monthly',
        'billingDisplay' => get_post_meta($item['id'], '_wpc_billing_display_style', true) ?: 'toggle',
        
        // Per-item pricing configuration settings
        'hideFeatures' => get_post_meta($item['id'], '_wpc_hide_plan_features', true) === '1',
        'showSelectTable' => get_post_meta($item['id'], '_wpc_show_plan_links', true) === '1',
        'showSelectPopup' => get_post_meta($item['id'], '_wpc_show_plan_links_popup', true) === '1',
        'ptBtnPosTable' => get_post_meta($item['id'], '_wpc_table_btn_pos', true) ?: 'default',
        'ptBtnPosPopup' => get_post_meta($item['id'], '_wpc_popup_btn_pos', true) ?: 'default',
    ];

    $config_json = htmlspecialchars(json_encode($widget_config), ENT_QUOTES, 'UTF-8');

    ob_start();
    ?>
    <div id="<?php echo esc_attr($unique_id); ?>" class="wpc-root" data-config="<?php echo $config_json; ?>" data-category="<?php echo esc_attr($category_slug); ?>">
        <!-- SSR Preview: Matches React PricingTable output exactly -->
        <div class="wpc-ssr-preview" style="width:100%;">
                
                <!-- Pricing Plans (Full SSR Table) -->
                 <?php if (!empty($item['pricing_plans']) && is_array($item['pricing_plans'])) : 
                     $plans = $item['pricing_plans'];
                     $billing_cycles = get_post_meta($item['id'], '_wpc_billing_cycles', true) ?: [];
                     $default_cycle = get_post_meta($item['id'], '_wpc_default_cycle', true) ?: 'monthly';
                     $empty_price_text = get_option('wpc_text_empty_price', 'Free');
                 ?>
                    <!-- Billing Toggle SSR (only if multiple cycles) -->
                    <?php if (is_array($billing_cycles) && count($billing_cycles) > 1) : ?>
                    <div style="display:flex; justify-content:center; margin-bottom:1rem; width:100%;">
                        <div style="display:inline-flex; border-radius:0.5rem; border:1px solid var(--pt-border,#e5e7eb); background:rgba(0,0,0,0.02); padding:0.25rem; gap:0.25rem;">
                            <?php foreach ($billing_cycles as $cycle) : 
                                $is_active = ($cycle['slug'] === $default_cycle);
                            ?>
                            <span style="padding:0.5rem 1rem; border-radius:0.375rem; font-size:0.875rem; font-weight:500; cursor:pointer; <?php echo $is_active ? 'background:var(--primary,#f97316); color:white; box-shadow:0 1px 2px rgba(0,0,0,0.05);' : 'color:#6b7280; background:transparent;'; ?>">
                                <?php echo esc_html($cycle['label']); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Main Table Container (matches React's card container) -->
                    <div style="width:100%; border:1px solid var(--pt-border,#e5e7eb); border-radius:0.75rem; background:white; overflow:hidden;">
                        <table style="width:100%; border-collapse:collapse; text-align:center; table-layout:fixed;">
                            <thead>
                                <tr>
                                    <?php foreach ($plans as $idx => $plan) : ?>
                                        <th style="padding:1rem; border-bottom:1px solid var(--pt-border); background-color:var(--pt-header-bg); color:var(--pt-header-text); width:<?php echo 100/count($plans); ?>%; vertical-align:top; border-right:<?php echo ($idx < count($plans)-1) ? '1px solid var(--pt-border)' : 'none'; ?>;">
                                            <div style="font-size:1.125rem; font-weight:700;"><?php echo esc_html($plan['name'] ?? 'Plan'); ?></div>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Price Row -->
                                <tr>
                                    <?php foreach ($plans as $idx => $plan) : 
                                         // Get price logic (copied from previous step)
                                         $ssr_price = '';
                                         $ssr_period = '';
                                         if (!empty($plan['prices']) && is_array($plan['prices'])) {
                                             if (!empty($plan['prices'][$default_cycle])) {
                                                 $ssr_price = $plan['prices'][$default_cycle]['amount'] ?? '';
                                                 $ssr_period = $plan['prices'][$default_cycle]['period'] ?? '';
                                             } else {
                                                 $first_cycle = array_values($plan['prices'])[0] ?? [];
                                                 $ssr_price = $first_cycle['amount'] ?? '';
                                                 $ssr_period = $first_cycle['period'] ?? '';
                                             }
                                         } else {
                                             $ssr_price = $plan['price'] ?? '';
                                             $ssr_period = $plan['period'] ?? '/mo';
                                         }
                                         if (empty($ssr_price) || $ssr_price === '0') {
                                             $ssr_price = $empty_price_text;
                                             $ssr_period = '';
                                         }
                                    ?>
                                        <td style="padding:1.5rem 1rem; vertical-align:top; border-right:<?php echo ($idx < count($plans)-1) ? '1px solid var(--pt-border)' : 'none'; ?>;">
                                            <div style="display:flex; flex-wrap:wrap; align-items:baseline; justify-content:center; gap:0.25rem;">
                                                <span style="font-size:1.875rem; font-weight:700; color:var(--primary);"><?php echo esc_html($ssr_price); ?></span>
                                                <?php if (!empty($ssr_period)) : ?>
                                                    <span style="color:#6b7280; font-size:0.875rem;"><?php echo esc_html($ssr_period); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                                <!-- Features Row (Simplified list) -->
                                <tr style="border-top:1px solid var(--pt-border);">
                                    <?php foreach ($plans as $idx => $plan) : 
                                        $features = !empty($plan['features']) ? explode("\n", $plan['features']) : [];
                                    ?>
                                        <td style="padding:1rem; vertical-align:top; text-align:left; border-right:<?php echo ($idx < count($plans)-1) ? '1px solid var(--pt-border)' : 'none'; ?>;">
                                            <?php if (!empty($features)) : ?>
                                                <ul style="list-style:none; padding:0; margin:0; font-size:0.875rem; color:#4b5563;">
                                                    <?php foreach ($features as $feature) : 
                                                        if (empty(trim($feature))) continue;
                                                    ?>
                                                        <li style="margin-bottom:0.5rem; display:flex; gap:0.5rem;">
                                                            <span style="color:var(--primary);">✓</span> <?php echo esc_html(trim($feature)); ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                                <!-- Button Row (After Features - matches React) -->
                                <?php if ($show_plan_buttons) : ?>
                                <tr>
                                    <?php foreach ($plans as $idx => $plan) : ?>
                                        <td style="padding:1rem; vertical-align:middle; border-right:<?php echo ($idx < count($plans)-1) ? '1px solid var(--pt-border)' : 'none'; ?>;">
                                            <?php if (!empty($plan['link']) || !empty($plan['button_text'])) : ?>
                                                <a href="<?php echo esc_url($plan['link'] ?: '#'); ?>" 
                                                   style="display:block; width:100%; padding:0.75rem 1rem; background-color:var(--pt-btn-bg,var(--primary,#f97316)); color:var(--pt-btn-text,white); border-radius:0.5rem; font-weight:600; text-decoration:none; font-size:0.875rem; text-align:center;">
                                                    <?php echo esc_html($plan['button_text'] ?? 'Select'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Footer Button SSR (dynamic) -->
                    <?php if ($show_footer_button && !empty($item['direct_link'])) : ?>
                    <div style="padding:1.25rem; text-align:center; border-top:1px solid var(--pt-border,#e5e7eb);">
                        <a href="<?php echo esc_url($item['direct_link']); ?>" 
                           target="_blank"
                           rel="noopener noreferrer"
                           style="display:inline-flex; align-items:center; gap:0.5rem; padding:0.75rem 2rem; background:var(--pt-btn-bg,var(--primary,#f97316)); color:var(--pt-btn-text,white); border-radius:0.5rem; font-weight:600; text-decoration:none; font-size:0.9375rem;">
                            <?php echo esc_html(!empty($footer_button_text) ? $footer_button_text : ($item['name'] ?: 'Visit Site')); ?>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                        </a>
                    </div>
                    <?php endif; ?>
                 <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'wpc_pricing_table', 'wpc_pricing_table_shortcode' );

// Load Pros/Cons Shortcode
require_once plugin_dir_path( __FILE__ ) . 'includes/pros-cons-shortcode.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/proscons-settings-tab.php';

// Hero shortcode is now loaded from includes/hero-shortcode.php (SSR version)

/**
 * Load custom template for single comparison_item
 */
function wpc_template_include( $template ) {
    if ( is_singular( 'comparison_item' ) ) {
        $new_template = plugin_dir_path( __FILE__ ) . 'templates/single-comparison_item.php';
        if ( file_exists( $new_template ) ) {
            return $new_template;
        }
    }
    // Backward compatibility for old CPT if somehow it still exists/is accessed (though we renamed register call)
    if ( is_singular( 'ecommerce_provider' ) ) {
         $new_template = plugin_dir_path( __FILE__ ) . 'templates/single-comparison_item.php';
         if ( file_exists( $new_template ) ) {
            return $new_template;
        }
    }
    return $template;
}
add_filter( 'template_include', 'wpc_template_include' );
