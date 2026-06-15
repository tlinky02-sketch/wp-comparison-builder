<?php
/**
 * Comparison Cards Block
 * 
 * Registers the Gutenberg block and renders the selected comparison items.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the Comparison Cards Block
 */
function wpc_register_comparison_cards_block() {
    register_block_type( 'wp-comparison-builder/promotion-block', array(
        'render_callback' => 'wpc_comparison_cards_render_callback',
        'attributes'      => array(
            'itemIds' => array(
                'type'    => 'array',
                'default' => array(),
            ),
            'itemOverrides' => array(
                'type'    => 'object',
                'default' => array(),
            ),
            'layout'  => array(
                'type'    => 'string',
                'default' => 'horizontal', // horizontal, grid, compact
            ),
            'titleSize'       => array('type' => 'string', 'default' => ''),
            'ratingSize'      => array('type' => 'string', 'default' => ''),
            'btnSize'         => array('type' => 'string', 'default' => ''),
            'cardBgColor'     => array('type' => 'string', 'default' => ''),
            'textColor'       => array('type' => 'string', 'default' => ''),
            'starColor'       => array('type' => 'string', 'default' => ''),
            'btnBgColor'      => array('type' => 'string', 'default' => ''),
            'btnTextColor'    => array('type' => 'string', 'default' => ''),
            'cardBorderColor' => array('type' => 'string', 'default' => ''),
            'cardBorderWidth' => array('type' => 'string', 'default' => ''),
            'cardShadow'      => array('type' => 'string', 'default' => 'default'),
            'promoBannerText' => array('type' => 'string', 'default' => ''),
            'promoBannerBg'   => array('type' => 'string', 'default' => '#fee2e2'),
            'promoBannerColor'=> array('type' => 'string', 'default' => '#b91c1c'),
        ),
    ) );
}
add_action( 'init', 'wpc_register_comparison_cards_block' );

/**
 * Enqueue Editor Assets
 */
function wpc_comparison_cards_editor_assets() {
    wp_enqueue_script(
        'wpc-comparison-cards-block',
        WPC_PLUGIN_URL . 'assets/js/comparison-cards-block.js',
        array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-api-fetch' ),
        WPC_VERSION,
        true
    );
}
add_action( 'enqueue_block_editor_assets', 'wpc_comparison_cards_editor_assets' );

/**
 * Render Callback for the Block
 */
function wpc_comparison_cards_render_callback( $attributes, $content ) {
    $item_ids  = ! empty( $attributes['itemIds'] ) ? $attributes['itemIds'] : array();
    $overrides = ! empty( $attributes['itemOverrides'] ) ? $attributes['itemOverrides'] : array();
    $layout    = ! empty( $attributes['layout'] ) ? $attributes['layout'] : 'horizontal';

    if ( empty( $item_ids ) ) {
        if ( is_admin() ) {
            return '<div style="padding: 20px; border: 2px dashed #ccc; text-align: center; border-radius: 8px;">Select comparison items to display cards.</div>';
        }
        return '';
    }

    $args = array(
        'post_type'      => 'comparison_item',
        'post_status'    => 'publish',
        'post__in'       => $item_ids,
        'orderby'        => 'post__in',
        'posts_per_page' => -1,
    );

    $query = new WP_Query( $args );

    if ( ! $query->have_posts() ) {
        return '';
    }

    $theme_colors = array(
        'primary' => get_option('wpc_primary_color') ?: '#6366f1',
        'stars'   => get_option('wpc_star_rating_color') ?: '#fbbf24',
        'button'  => get_option('wpc_button_bg_color') ?: '#6366f1',
        'btnText' => get_option('wpc_button_text_color') ?: '#ffffff',
        'hover'   => get_option('wpc_button_hover_color') ?: '#4f46e5',
    );

    // Override with block attributes if present
    $c_bg       = !empty($attributes['cardBgColor']) ? $attributes['cardBgColor'] : 'hsl(var(--card))';
    $c_text     = !empty($attributes['textColor']) ? $attributes['textColor'] : 'hsl(var(--foreground))';
    $c_star     = !empty($attributes['starColor']) ? $attributes['starColor'] : $theme_colors['stars'];
    $c_btn_bg   = !empty($attributes['btnBgColor']) ? $attributes['btnBgColor'] : $theme_colors['button'];
    $c_btn_text = !empty($attributes['btnTextColor']) ? $attributes['btnTextColor'] : $theme_colors['btnText'];
    
    $f_title  = !empty($attributes['titleSize']) ? $attributes['titleSize'] : '20px';
    $f_rating = !empty($attributes['ratingSize']) ? $attributes['ratingSize'] : '14px';
    $f_btn    = !empty($attributes['btnSize']) ? $attributes['btnSize'] : '15px';
    
    $border_color = !empty($attributes['cardBorderColor']) ? $attributes['cardBorderColor'] : 'hsl(var(--border))';
    $border_width = !empty($attributes['cardBorderWidth']) ? $attributes['cardBorderWidth'] : '1px';
    $shadow_type  = !empty($attributes['cardShadow']) ? $attributes['cardShadow'] : 'default';

    ob_start();
    
    // Dynamic Styles for this block
    $block_id = 'wpc-promo-' . uniqid();
    ?>
    <style>
        .<?php echo $block_id; ?> {
            display: flex;
            gap: 16px;
            width: 100%;
            box-sizing: border-box;
        }
        .<?php echo $block_id; ?>.wpc-layout-horizontal, 
        .<?php echo $block_id; ?>.wpc-layout-compact {
            flex-direction: column;
        }
        .<?php echo $block_id; ?>.wpc-layout-grid {
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: center;
        }
        .<?php echo $block_id; ?> .wpc-card-item {
            background: <?php echo esc_attr($c_bg); ?>;
            border: <?php echo esc_attr($border_width); ?> solid <?php echo esc_attr($border_color); ?>;
            border-radius: 12px;
            transition: transform 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
            position: relative;
            margin-top: 15px; /* Space for the banner */
            <?php 
            if ($shadow_type === 'none') {
                echo 'box-shadow: none;';
            } elseif ($shadow_type === 'soft') {
                echo 'box-shadow: 0 2px 10px rgba(0,0,0,0.05);';
            } elseif ($shadow_type === 'heavy') {
                echo 'box-shadow: 0 10px 25px rgba(0,0,0,0.15);';
            } else {
                echo 'box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);';
            }
            ?>
        }
        .<?php echo $block_id; ?> .wpc-promo-banner {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: <?php echo esc_attr($c_btn_bg); ?>;
            color: <?php echo esc_attr($c_btn_text); ?>;
            padding: 4px 16px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 20px;
            white-space: nowrap;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            z-index: 2;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .<?php echo $block_id; ?> .wpc-card-item:hover {
            <?php if ($shadow_type !== 'none') : ?>
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            <?php endif; ?>
        }
        .<?php echo $block_id; ?> .wpc-card-logo-wrap img {
            max-width: 100%;
            height: auto;
            max-height: 50px;
            object-fit: contain;
        }
        .<?php echo $block_id; ?> .wpc-card-button {
            text-decoration: none !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            transition: opacity 0.2s;
            box-sizing: border-box;
        }
        .<?php echo $block_id; ?> .wpc-card-button:hover {
            opacity: 0.9;
        }
        /* Horizontal Layout Specifics */
        .<?php echo $block_id; ?>.wpc-layout-horizontal .wpc-card-item {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            width: 100%;
        }
        .<?php echo $block_id; ?>.wpc-layout-horizontal .wpc-card-left {
            display: flex;
            align-items: center;
            gap: 24px;
            flex-grow: 1;
        }
        /* Grid Layout Specifics */
        .<?php echo $block_id; ?>.wpc-layout-grid .wpc-card-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 24px;
            width: calc(33.333% - 16px);
            min-width: 250px;
            flex-grow: 1;
        }
        .<?php echo $block_id; ?>.wpc-layout-grid .wpc-card-logo-wrap {
            margin-bottom: 16px;
        }
        .<?php echo $block_id; ?>.wpc-layout-grid .wpc-card-button {
            width: 100%;
            margin-top: 20px;
        }

        /* Compact Layout Specifics */
        .<?php echo $block_id; ?>.wpc-layout-compact .wpc-card-item {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            width: 100%;
        }
        .<?php echo $block_id; ?>.wpc-layout-compact .wpc-card-logo-wrap img {
            max-height: 30px;
        }
        .<?php echo $block_id; ?>.wpc-layout-compact .wpc-card-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .<?php echo $block_id; ?>.wpc-layout-compact h3 {
            margin: 0 !important;
        }

        /* Mobile Responsiveness for Horizontal and Compact */
        @media (max-width: 640px) {
            .<?php echo $block_id; ?>.wpc-layout-horizontal .wpc-card-item,
            .<?php echo $block_id; ?>.wpc-layout-compact .wpc-card-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            .<?php echo $block_id; ?>.wpc-layout-horizontal .wpc-card-left,
            .<?php echo $block_id; ?>.wpc-layout-compact .wpc-card-left {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
                width: 100%;
            }
            .<?php echo $block_id; ?>.wpc-layout-horizontal .wpc-card-right,
            .<?php echo $block_id; ?>.wpc-layout-compact .wpc-card-right {
                width: 100%;
            }
            .<?php echo $block_id; ?>.wpc-layout-horizontal .wpc-card-button,
            .<?php echo $block_id; ?>.wpc-layout-compact .wpc-card-button {
                width: 100%;
            }
        }
    </style>

    <div class="<?php echo esc_attr( $block_id ); ?> wpc-layout-<?php echo esc_attr( $layout ); ?>">
        <?php while ( $query->have_posts() ) : $query->the_post(); 
            $post_id = get_the_ID();
            
            // Meta Data
            $logo_url = get_post_meta( $post_id, '_wpc_external_logo_url', true );
            if ( ! $logo_url ) {
                $logo_url = get_the_post_thumbnail_url( $post_id, 'medium' );
            }
            
            // Check for overrides
            $item_override = isset($overrides[$post_id]) ? $overrides[$post_id] : array();
            
            $score = !empty($item_override['rating']) ? $item_override['rating'] : get_post_meta( $post_id, '_wpc_rating', true );
            if ( ! $score || ! is_numeric( $score ) ) $score = '5.0';
            
            $promo_banner_text = !empty($item_override['promoBannerText']) ? $item_override['promoBannerText'] : get_post_meta( $post_id, '_wpc_promo_banner_text', true );
            if ( empty($promo_banner_text) && !empty($attributes['promoBannerText']) ) {
                $promo_banner_text = $attributes['promoBannerText'];
            }
            
            $pb_bg = !empty($attributes['promoBannerBg']) ? $attributes['promoBannerBg'] : '#fee2e2';
            $pb_color = !empty($attributes['promoBannerColor']) ? $attributes['promoBannerColor'] : '#b91c1c';

            $total_reviews = get_post_meta( $post_id, '_wpc_total_reviews', true );
            
            // Link Data
            $txt_visit_site = get_post_meta( $post_id, '_wpc_txt_visit_site', true );
            $button_text    = !empty($txt_visit_site) ? $txt_visit_site : get_option('wpc_text_visit_site', 'Visit Site');
            
            if ( !empty($item_override['buttonText']) ) {
                $btn_label = $item_override['buttonText'];
            } else {
                $btn_label = $button_text;
            }
            
            if ( trim($btn_label) === '' ) {
                $btn_label = 'Visit Site';
            }
            
            $target_direct = get_option('wpc_target_direct', '_blank');
            $aff_link = get_post_meta( $post_id, '_wpc_affiliate_link', true ) ?: '#';
            
            // Calculate accurate star percentage
            $numeric_score = floatval($score);
            $star_percent  = min(100, max(0, ($numeric_score / 5) * 100));
        ?>
            <div class="wpc-card-item">
                <?php if ( ! empty( $promo_banner_text ) ) : ?>
                    <div class="wpc-promo-banner" style="background-color: <?php echo esc_attr($pb_bg); ?>; color: <?php echo esc_attr($pb_color); ?>;"><?php echo esc_html( $promo_banner_text ); ?></div>
                <?php endif; ?>
                <div class="wpc-card-left">
                    <?php if ( $logo_url ) : ?>
                    <div class="wpc-card-logo-wrap" style="width: 100px; display: flex; justify-content: <?php echo $layout === 'grid' ? 'center' : 'flex-start'; ?>;">
                        <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?> logo" />
                    </div>
                    <?php endif; ?>
                    
                    <div class="wpc-card-details">
                        <h3 style="font-size: <?php echo esc_attr($f_title); ?> !important; font-weight: 700; margin: 0 0 4px 0; color: <?php echo esc_attr($c_text); ?> !important;"><?php the_title(); ?></h3>
                        
                        <div class="wpc-card-rating" style="display: flex; align-items: center; gap: 6px;">
                            <div class="wpc-stars-wrap" style="position: relative; display: inline-flex;">
                                <!-- Empty stars background layer -->
                                <div style="display: flex; color: #e2e8f0 !important;">
                                    <?php for($i=0; $i<5; $i++) echo '<svg width="16" height="16" viewBox="0 0 24 24" style="fill: currentColor !important;"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>'; ?>
                                </div>
                                <!-- Filled stars foreground layer -->
                                <div style="display: flex; color: <?php echo esc_attr($c_star); ?> !important; position: absolute; top: 0; left: 0; overflow: hidden; width: <?php echo $star_percent; ?>%; white-space: nowrap;">
                                    <?php for($i=0; $i<5; $i++) echo '<svg width="16" height="16" viewBox="0 0 24 24" style="fill: currentColor !important; flex-shrink: 0;"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>'; ?>
                                </div>
                            </div>
                            <span style="font-size: <?php echo esc_attr($f_rating); ?> !important; font-weight: 600; color: <?php echo esc_attr($c_text); ?> !important;"><?php echo esc_html( $score ); ?>/5</span>
                            <?php if ( ! empty( $total_reviews ) ) : ?>
                            <span style="font-size: 13px; color: <?php echo esc_attr($c_text); ?>; opacity: 0.8;">(<?php echo esc_html( $total_reviews ); ?>)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="wpc-card-right">
                    <a href="<?php echo esc_url( $aff_link ); ?>" target="<?php echo esc_attr( $target_direct ); ?>" rel="nofollow noopener" class="wpc-card-button" style="background-color: <?php echo esc_attr( $c_btn_bg ); ?> !important; color: <?php echo esc_attr( $c_btn_text ); ?> !important; padding: 10px 24px; border-radius: 8px; font-size: <?php echo esc_attr($f_btn); ?> !important; min-width: 140px; text-align: center;">
                        <?php echo esc_html( $btn_label ); ?>
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    <?php

    wp_reset_postdata();
    return ob_get_clean();
}
