<?php
/**
 * Hero Section Shortcode - Pure PHP SSR
 * Usage: [wpc_hero id="123"]
 * No React dependency - Fast initial load
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function wpc_hero_shortcode( $atts ) {
    // Enqueue only styles (no React!)
    wp_enqueue_style( 'wpc-styles' );

    $atts = shortcode_atts( array(
        'id' => '',
        'category' => '', // Product Variants Module
    ), $atts );

    if ( empty( $atts['id'] ) ) return '';
    
    // Category Context
    $category_slug = ! empty( $atts['category'] ) ? sanitize_text_field( $atts['category'] ) : '';

    $item_id = intval( $atts['id'] );
    $post = get_post( $item_id );
    
    if ( ! $post || $post->post_type !== 'comparison_item' ) {
        return '<!-- WPC Hero: Invalid ID -->';
    }

    // Fetch item data
    $name = get_post_meta( $item_id, '_wpc_public_name', true ) ?: $post->post_title;
    $description = get_post_meta( $item_id, '_wpc_short_description', true );
    $rating = floatval( get_post_meta( $item_id, '_wpc_rating', true ) ?: 0 );
    $details_link = get_post_meta( $item_id, '_wpc_details_link', true );
    $hero_subtitle = get_post_meta( $item_id, '_wpc_hero_subtitle', true );
    $analysis_label = get_post_meta( $item_id, '_wpc_analysis_label', true );
    $show_hero_logo = get_post_meta( $item_id, '_wpc_show_hero_logo', true );
    $hero_button_text = get_post_meta( $item_id, '_wpc_hero_button_text', true );
    
    // Logo - prefer external URL, fall back to featured image
    $logo = get_post_meta( $item_id, '_wpc_external_logo_url', true );
    if ( empty( $logo ) ) {
        $thumb_id = get_post_thumbnail_id( $item_id );
        if ( $thumb_id ) {
            $logo = wp_get_attachment_image_url( $thumb_id, 'thumbnail' );
        }
    }
    
    // Dashboard/Hero image - Featured Image has HIGHER priority
    $thumb_id = get_post_thumbnail_id( $item_id );
    if ( $thumb_id ) {
        // Featured Image takes priority
        $dashboard_image = wp_get_attachment_image_url( $thumb_id, 'large' );
    } else {
        // Fall back to Dashboard Image field
        $dashboard_image = get_post_meta( $item_id, '_wpc_dashboard_image', true );
    }

    // Colors
    $primary_color = get_option( 'wpc_primary_color', '#6366f1' );
    $hover_color = get_option( 'wpc_button_hover_color', '' ) ?: '#4f46e5';
    $star_color = get_option( 'wpc_star_rating_color', '#fbbf24' );
    $border_color = get_option( 'wpc_border_color', '#e2e8f0' );
    
    // Texts
    $visit_text = get_option( 'wpc_text_visit', 'Visit' );
    $open_new_tab = get_option( 'wpc_open_new_tab', '1' ) === '1' ? '_blank' : '_self';

    // Typography Options - Check if they are Set
    $opt_h1 = get_option('wpc_font_size_h1', '');
    $opt_sub = get_option('wpc_font_size_subheading', '');
    $opt_body = get_option('wpc_font_size_body', '');
    $opt_small = get_option('wpc_font_size_small', '');
    $opt_btn = get_option('wpc_font_size_btn', '');
    
    // Construct style strings ONLY if option is set, otherwise empty (inherit theme)
    $style_h1 = !empty($opt_h1) ? "font-size: var(--wpc-font-size-h1) !important;" : "";
    $style_sub = !empty($opt_sub) ? "font-size: var(--wpc-font-size-subheading) !important;" : "";
    $style_body = !empty($opt_body) ? "font-size: var(--wpc-font-size-body) !important;" : "";
    $style_small = !empty($opt_small) ? "font-size: var(--wpc-font-size-small) !important;" : "";
    $style_btn = !empty($opt_btn) ? "font-size: var(--wpc-font-size-btn) !important;" : "";

    // Escape all the things

    // Escape all the things
    $name = esc_html( $name );
    $description = esc_html( $description );
    $hero_subtitle = esc_html( $hero_subtitle );
    $analysis_label = esc_html( $analysis_label );
    $details_link = esc_url( $details_link );
    $logo = esc_url( $logo );
    $dashboard_image = esc_url( $dashboard_image );

    // Star rating helper
    $full_stars = floor( $rating );
    $decimal = $rating - $full_stars;
    $has_half = $decimal >= 0.1; // Show partial fill if decimal >= 0.1
    $partial_percent = round($decimal * 100); // Calculate percentage for partial fill
    $empty_stars = 5 - $full_stars - ( $has_half ? 1 : 0 );

    ob_start();
    ?>
    <div class="wpc-hero wpc-root" data-wpc-category="<?php echo esc_attr( $category_slug ); ?>" style="margin-bottom: 3rem;">
        <div style="display: grid; grid-template-columns: 1fr; gap: 3rem; align-items: start;">
            
            <!-- Left Column: Content -->
            <div style="order: 1;">
                
                <?php if ( $show_hero_logo !== '0' && ! empty( $logo ) ) : ?>
                <!-- Logo -->
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                    <div style="
                        width: 4rem;
                        height: 4rem;
                        border-radius: 1rem;
                        background: hsl(var(--card));
                        padding: 0.5rem;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                        border: 1px solid hsl(var(--border));
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        overflow: hidden;
                    ">
                        <img src="<?php echo $logo; ?>" alt="<?php echo $name; ?>" style="width: 100%; height: 100%; object-fit: contain;" />
                    </div>
                </div>
                <?php endif; ?>

                <!-- Title -->
                <h1 class="wpc-heading" style="
                    font-weight: 700;
                    margin: 0 0 1rem 0;
                    line-height: 1.2;
                    line-height: 1.2;
                    <?php echo $style_h1; ?>
                "><?php echo $name; ?></h1>

                <?php if ( ! empty( $hero_subtitle ) ) : ?>
                <!-- Subtitle -->
                <p class="wpc-text-muted" style="
                    margin: 0 0 1.5rem 0;
                    line-height: 1.6;
                    line-height: 1.6;
                    <?php echo $style_sub; ?>
                "><?php echo $hero_subtitle; ?></p>
                <?php endif; ?>

                <?php if ( ! empty( $description ) ) : ?>
                <!-- Description -->
                <div class="wpc-text-muted" style="
                    margin: 0 0 2rem 0;
                    line-height: 1.7;
                ">
                    <p style="margin: 0; <?php echo $style_body; ?>"><?php echo $description; ?></p>
                </div>
                <?php endif; ?>

                <!-- Rating -->
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 2rem; flex-wrap: wrap;">
                    <div style="display: flex; gap: 2px;">
                        <?php for ( $i = 0; $i < $full_stars; $i++ ) : ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="<?php echo esc_attr( $star_color ); ?>" stroke="<?php echo esc_attr( $star_color ); ?>" stroke-width="1"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        <?php endfor; ?>
                        <?php if ( $has_half ) : ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="none">
                                <defs><linearGradient id="star-partial-<?php echo $item_id; ?>"><stop offset="<?php echo $partial_percent; ?>%" stop-color="<?php echo esc_attr( $star_color ); ?>"/><stop offset="<?php echo $partial_percent; ?>%" stop-color="hsl(var(--muted))"/></linearGradient></defs>
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" fill="url(#star-partial-<?php echo $item_id; ?>)"></polygon>
                            </svg>
                        <?php endif; ?>
                        <?php for ( $i = 0; $i < $empty_stars; $i++ ) : ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        <?php endfor; ?>
                    </div>
                    <span class="wpc-heading" style="font-weight: 700;"><?php echo number_format( $rating, 1 ); ?>/5</span>
                    <?php if ( ! empty( $analysis_label ) ) : ?>
                        <span class="wpc-text-muted" style="<?php echo $style_small; ?>">(<?php echo $analysis_label; ?>)</span>
                    <?php endif; ?>
                </div>

                <!-- CTA Button (onclick to hide affiliate URL in status bar) -->
                <?php if ( ! empty( $details_link ) ) : ?>
                <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                    <button 
                        type="button"
                        onclick="window.open('<?php echo esc_js( $details_link ); ?>', '<?php echo $open_new_tab; ?>');" 
                        class="wpc-text-body"
                        style="
                            display: inline-flex;
                            align-items: center;
                            gap: 0.5rem;
                            padding: 0.875rem 2rem;
                            background: <?php echo esc_attr( $primary_color ); ?>;
                            color: <?php echo esc_attr( get_option('wpc_button_text_color', '#ffffff') ); ?> !important;
                            <?php echo $style_btn; ?>
                            font-weight: 600;
                            border-radius: 0.5rem;
                            border: none;
                            cursor: pointer;
                            box-shadow: 0 4px 14px <?php echo esc_attr( $primary_color ); ?>40;
                            transition: all 0.2s;
                        "
                        onmouseover="this.style.background='<?php echo esc_attr( $hover_color ); ?>'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px <?php echo esc_attr( $primary_color ); ?>50';"
                        onmouseout="this.style.background='<?php echo esc_attr( $primary_color ); ?>'; this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 14px <?php echo esc_attr( $primary_color ); ?>40';"
                    >
                        <?php echo !empty($hero_button_text) ? esc_html($hero_button_text) : esc_html($visit_text) . ' ' . $name; ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="<?php echo esc_attr( get_option('wpc_button_text_color', '#ffffff') ); ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Dashboard Image -->
            <?php if ( ! empty( $dashboard_image ) ) : ?>
            <div style="order: 2;" class="wpc-hero-image-col">
                <style>
                    @media (min-width: 1024px) {
                        .wpc-hero > div { grid-template-columns: 1fr 1fr !important; }
                        .wpc-hero-image-col { display: block !important; order: 2 !important; }
                        .wpc-hero > div > div:first-child { order: 1 !important; }
                    }
                </style>
                <div style="
                    border-radius: 0.75rem;
                    overflow: hidden;
                    border: 1px solid <?php echo esc_attr( $border_color ); ?>;
                    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15);
                    background: #fff;
                    transform: rotate(1deg);
                    transition: transform 0.5s;
                " onmouseover="this.style.transform='rotate(0)'" onmouseout="this.style.transform='rotate(1deg)'">
                    <!-- Browser Chrome -->
                    <div style="
                        background: #f9fafb;
                        padding: 0.5rem 1rem;
                        border-bottom: 1px solid <?php echo esc_attr( $border_color ); ?>;
                        display: flex;
                        align-items: center;
                        gap: 0.5rem;
                    ">
                        <div style="display: flex; gap: 0.375rem;">
                            <div style="width: 0.625rem; height: 0.625rem; border-radius: 50%; background: rgba(239,68,68,0.5);"></div>
                            <div style="width: 0.625rem; height: 0.625rem; border-radius: 50%; background: rgba(251,191,36,0.5);"></div>
                            <div style="width: 0.625rem; height: 0.625rem; border-radius: 50%; background: rgba(34,197,94,0.5);"></div>
                        </div>
                    </div>
                    <img 
                        src="<?php echo $dashboard_image; ?>" 
                        alt="<?php echo $name; ?> Preview"
                        style="width: 100%; height: auto; display: block;"
                        loading="lazy"
                    />
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Register shortcodes (will override the existing registration)
add_shortcode( 'wpc_hero', 'wpc_hero_shortcode' );
add_shortcode( 'ecommerce_guider_hero', 'wpc_hero_shortcode' ); // Legacy Support
