<?php
/**
 * WPC Coupon Popup Shortcode & Gutenberg Block
 * Usage: [wpc_coupon_popup id="338"]
 * Fully controllable, looping countdown timer, auto-open, exit-intent, cookie control.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Gutenberg Block & PHP Render Callback
 */
function wpc_register_coupon_popup_block() {
    register_block_type( 'wp-comparison-builder/coupon-popup', array(
        'render_callback' => 'wpc_coupon_popup_block_render_callback',
        'attributes'      => array(
            'id'               => array( 'type' => 'string', 'default' => '' ),
            'logoUrl'          => array( 'type' => 'string', 'default' => '' ),
            'title'            => array( 'type' => 'string', 'default' => '' ),
            'titleColor'       => array( 'type' => 'string', 'default' => '' ),
            'titleSize'        => array( 'type' => 'string', 'default' => '' ),
            'subtitle'         => array( 'type' => 'string', 'default' => '' ),
            'subtitleColor'    => array( 'type' => 'string', 'default' => '' ),
            'subtitleSize'     => array( 'type' => 'string', 'default' => '' ),
            'timer'            => array( 'type' => 'string', 'default' => '15m' ),
            'timerLabel'       => array( 'type' => 'string', 'default' => 'Expires in:' ),
            'timerBgColor'     => array( 'type' => 'string', 'default' => '' ),
            'timerTextColor'   => array( 'type' => 'string', 'default' => '' ),
            'timerSize'        => array( 'type' => 'string', 'default' => '' ),
            'mascotUrl'        => array( 'type' => 'string', 'default' => '' ),
            'exclusiveLabel'   => array( 'type' => 'string', 'default' => 'Exclusive Deal' ),
            'exclusiveBgColor' => array( 'type' => 'string', 'default' => '' ),
            'exclusiveTextColor'=> array( 'type' => 'string', 'default' => '' ),
            'verifiedLabel'    => array( 'type' => 'string', 'default' => 'Verified' ),
            'verifiedBgColor'  => array( 'type' => 'string', 'default' => '' ),
            'verifiedTextColor'=> array( 'type' => 'string', 'default' => '' ),
            'buttonText'       => array( 'type' => 'string', 'default' => 'Show Code' ),
            'copiedText'       => array( 'type' => 'string', 'default' => 'Copied!' ),
            'btnBgColor'       => array( 'type' => 'string', 'default' => '' ),
            'btnTextColor'     => array( 'type' => 'string', 'default' => '' ),
            'btnHoverColor'    => array( 'type' => 'string', 'default' => '' ),
            'btnSize'          => array( 'type' => 'string', 'default' => '' ),
            'features'         => array( 'type' => 'string', 'default' => '' ),
            'featuresColor'    => array( 'type' => 'string', 'default' => '' ),
            'featuresSize'     => array( 'type' => 'string', 'default' => '' ),
            'maskText'         => array( 'type' => 'string', 'default' => 'SPECIAL' ),
            'cardShadow'       => array( 'type' => 'string', 'default' => 'heavy' ),
            'couponCode'       => array( 'type' => 'string', 'default' => '' ),
            'affiliateLink'    => array( 'type' => 'string', 'default' => '' ),
            'layout'           => array( 'type' => 'string', 'default' => 'modal' ),
            'copiedBgColor'    => array( 'type' => 'string', 'default' => '' ),
            'copiedTextColor'  => array( 'type' => 'string', 'default' => '' ),
            'cardBorderStyle'  => array( 'type' => 'string', 'default' => 'none' ),
            'cardBorderColor'  => array( 'type' => 'string', 'default' => '' ),
            'cardBorderWidth'  => array( 'type' => 'string', 'default' => '2px' ),
            'buttonStyle'      => array( 'type' => 'string', 'default' => 'ticket' ),
            'clickAction'      => array( 'type' => 'string', 'default' => 'copy_reveal_redirect' ),
            'showTriggerBtn'   => array( 'type' => 'boolean', 'default' => true ),
            'triggerText'      => array( 'type' => 'string', 'default' => 'Claim Deal' ),
            'triggerClass'     => array( 'type' => 'string', 'default' => '' ),
            'triggerSelector'  => array( 'type' => 'string', 'default' => '' ),
            'autoOpen'         => array( 'type' => 'string', 'default' => '' ),
            'exitIntent'       => array( 'type' => 'boolean', 'default' => false ),
            'triggerFrequency' => array( 'type' => 'string', 'default' => 'cookie' ),
            'cookieExpiry'     => array( 'type' => 'string', 'default' => '1' ),
            'primaryColor'     => array( 'type' => 'string', 'default' => '' )
        )
    ));
}
add_action( 'init', 'wpc_register_coupon_popup_block' );

/**
 * Gutenberg Block Render Callback
 */
function wpc_coupon_popup_block_render_callback( $attributes ) {
    // Map block attributes (camelCase) to shortcode attributes (snake_case)
    $mapped_atts = array(
        'id'               => $attributes['id'] ?? '',
        'logo_url'         => $attributes['logoUrl'] ?? '',
        'title'            => $attributes['title'] ?? '',
        'title_color'      => $attributes['titleColor'] ?? '',
        'title_size'       => $attributes['titleSize'] ?? '',
        'subtitle'         => $attributes['subtitle'] ?? '',
        'subtitle_color'   => $attributes['subtitleColor'] ?? '',
        'subtitle_size'    => $attributes['subtitleSize'] ?? '',
        'timer'            => $attributes['timer'] ?? '15m',
        'timer_label'      => $attributes['timerLabel'] ?? 'Expires in:',
        'timer_bg_color'   => $attributes['timerBgColor'] ?? '',
        'timer_text_color' => $attributes['timerTextColor'] ?? '',
        'timer_size'       => $attributes['timerSize'] ?? '',
        'mascot_url'       => $attributes['mascotUrl'] ?? '',
        'exclusive_label'  => $attributes['exclusiveLabel'] ?? 'Exclusive Deal',
        'exclusive_bg_color'=> $attributes['exclusiveBgColor'] ?? '',
        'exclusive_text_color'=> $attributes['exclusiveTextColor'] ?? '',
        'verified_label'   => $attributes['verifiedLabel'] ?? 'Verified',
        'verified_bg_color' => $attributes['verifiedBgColor'] ?? '',
        'verified_text_color'=> $attributes['verifiedTextColor'] ?? '',
        'button_text'      => $attributes['buttonText'] ?? 'Show Code',
        'copied_text'      => $attributes['copiedText'] ?? 'Copied!',
        'btn_bg_color'     => $attributes['btnBgColor'] ?? '',
        'btn_text_color'   => $attributes['btnTextColor'] ?? '',
        'btn_hover_color'  => $attributes['btnHoverColor'] ?? '',
        'btn_size'         => $attributes['btnSize'] ?? '',
        'features'         => $attributes['features'] ?? '',
        'features_color'   => $attributes['featuresColor'] ?? '',
        'features_size'    => $attributes['featuresSize'] ?? '',
        'mask_text'        => $attributes['maskText'] ?? 'SPECIAL',
        'card_shadow'      => $attributes['cardShadow'] ?? 'heavy',
        'coupon_code'      => $attributes['couponCode'] ?? '',
        'affiliate_link'   => $attributes['affiliateLink'] ?? '',
        'layout'           => $attributes['layout'] ?? 'modal',
        'copied_bg_color'  => $attributes['copiedBgColor'] ?? '',
        'copied_text_color'=> $attributes['copiedTextColor'] ?? '',
        'card_border_style'=> $attributes['cardBorderStyle'] ?? 'none',
        'card_border_color'=> $attributes['cardBorderColor'] ?? '',
        'card_border_width'=> $attributes['cardBorderWidth'] ?? '2px',
        'button_style'     => $attributes['buttonStyle'] ?? 'ticket',
        'click_action'     => $attributes['clickAction'] ?? 'copy_reveal_redirect',
        'show_trigger_btn' => $attributes['showTriggerBtn'] ?? true,
        'trigger_text'     => $attributes['triggerText'] ?? 'Claim Deal',
        'trigger_class'    => $attributes['triggerClass'] ?? '',
        'trigger_selector' => $attributes['triggerSelector'] ?? '',
        'auto_open'        => $attributes['autoOpen'] ?? '',
        'exit_intent'      => $attributes['exitIntent'] ?? false,
        'trigger_freq'     => $attributes['triggerFrequency'] ?? 'cookie',
        'cookie_expiry'    => $attributes['cookieExpiry'] ?? '1',
        'primary_color'    => $attributes['primaryColor'] ?? ''
    );

    return wpc_coupon_popup_shortcode( $mapped_atts );
}

/**
 * Enqueue Gutenberg Editor Block Scripts
 */
function wpc_coupon_popup_editor_assets() {
    wp_enqueue_script(
        'wpc-coupon-popup-block',
        WPC_PLUGIN_URL . 'assets/js/coupon-popup-block.js',
        array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-api-fetch' ),
        WPC_VERSION,
        true
    );
}
add_action( 'enqueue_block_editor_assets', 'wpc_coupon_popup_editor_assets' );

/**
 * WPC Coupon Popup Shortcode Implementation
 */
function wpc_coupon_popup_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'id'               => '',
        'logo_url'         => '',
        'title'            => '',
        'title_color'      => '',
        'title_size'       => '',
        'subtitle'         => '',
        'subtitle_color'   => '',
        'subtitle_size'    => '',
        'timer'            => '15m',
        'timer_label'      => 'Expires in:',
        'timer_bg_color'   => '',
        'timer_text_color' => '',
        'timer_size'       => '',
        'mascot_url'       => '',
        'exclusive_label'  => 'Exclusive Deal',
        'exclusive_bg_color'=> '',
        'exclusive_text_color'=> '',
        'verified_label'   => 'Verified',
        'verified_bg_color' => '',
        'verified_text_color'=> '',
        'button_text'      => 'Show Code',
        'copied_text'      => 'Copied!',
        'features'         => '',
        'features_color'   => '',
        'features_size'    => '',
        'mask_text'        => 'SPECIAL',
        'card_shadow'      => 'heavy',
        'card_border_style'=> 'none',
        'card_border_color'=> '',
        'card_border_width'=> '2px',
        'coupon_code'      => '',
        'affiliate_link'   => '',
        'show_trigger_btn' => true,
        'trigger_text'     => 'Claim Deal',
        'trigger_class'    => '',
        'trigger_selector' => '',
        'layout'           => 'modal', // modal | inline
        'copied_bg_color'  => '',
        'copied_text_color'=> '',
        'button_style'     => 'ticket', // ticket | solid | outline | glow
        'click_action'     => 'copy_reveal_redirect', // copy_reveal_redirect | copy_reveal_only | redirect_only
        'primary_color'    => '',
        'auto_open'        => '', // delay in seconds, e.g. 5
        'exit_intent'      => '', // true/false
        'trigger_freq'     => 'cookie', // cookie | page | session | always
        'cookie_expiry'    => '1', // days
    ), $atts );

    $item_id = intval( $atts['id'] );
    $item = null;

    if ( $item_id ) {
        // Fetch Item Data from Database
        if ( ! function_exists( 'wpc_fetch_items_data' ) ) {
            require_once WPC_PLUGIN_DIR . 'includes/api-endpoints.php';
        }
        $data = wpc_fetch_items_data( array( $item_id ) );
        $item = ! empty( $data['items'] ) ? $data['items'][0] : null;
    }

    // Resolve Overrides & Fallbacks
    $name           = $item ? esc_html( $item['name'] ) : ( ! empty( $atts['title'] ) ? esc_html( $atts['title'] ) : 'Special Offer' );
    $logo           = ! empty( $atts['logo_url'] ) ? esc_url( $atts['logo_url'] ) : ( $item ? esc_url( $item['logo'] ) : '' );
    $coupon         = ! empty( $atts['coupon_code'] ) ? esc_html( $atts['coupon_code'] ) : ( $item ? esc_html( $item['coupon_code'] ) : '' );
    $link           = ! empty( $atts['affiliate_link'] ) ? esc_url( $atts['affiliate_link'] ) : ( $item ? esc_url( $item['direct_link'] ?: $item['details_link'] ) : '#' );
    
    // Resolve theme inheritance color logic
    $primary_color  = ! empty( $atts['primary_color'] ) ? esc_attr( $atts['primary_color'] ) : ( ( $item && isset($item['design_overrides']['primary']) ) ? $item['design_overrides']['primary'] : get_option( 'wpc_primary_color', '#0ea5e9' ) );
    
    // Resolve Headline Title — use item name (not full description) as the headline
    $headline = ! empty( $atts['title'] ) ? esc_html( $atts['title'] ) : sprintf( __( 'Get Exclusive %s Deal', 'wp-comparison-builder' ), $name );
    // Use item description as subtitle only if no custom subtitle set
    $subtitle = ! empty( $atts['subtitle'] ) ? esc_html( $atts['subtitle'] ) : ( $item && ! empty( $item['description'] ) ? esc_html( wp_trim_words( $item['description'], 20, '...' ) ) : '' );

    // Resolve Features
    $feature_list = array();
    if ( ! empty( $atts['features'] ) ) {
        $feature_list = array_map( 'trim', explode( ',', $atts['features'] ) );
    } else {
        // Fallback to item pros
        $feature_list = ( $item && ! empty( $item['pros'] ) ) ? array_slice( $item['pros'], 0, 3 ) : array(
            __( '30-Day Money-back Guarantee', 'wp-comparison-builder' ),
            __( 'Verified Premium Provider', 'wp-comparison-builder' ),
            __( '24/7 Priority Support', 'wp-comparison-builder' )
        );
    }

    // Unique Identifier for this instance to prevent collisions
    $uid = uniqid( 'wpc_coupon_' );

    // Inline styles for this instance (Ensures colors/sizes get theme defaults if not specified)
    $css_rules = array();
    
    $css_rules[] = "--wpc-coupon-primary: {$primary_color};";
    $css_rules[] = "--wpc-coupon-primary-rgb: " . wpc_coupon_hex_to_rgb( $primary_color ) . ";";

    if ( ! empty( $atts['title_color'] ) ) {
        $css_rules[] = "--wpc-coupon-title-color: " . esc_attr( $atts['title_color'] ) . ";";
    }
    if ( ! empty( $atts['title_size'] ) ) {
        $css_rules[] = "--wpc-coupon-title-size: " . esc_attr( $atts['title_size'] ) . ";";
    }
    if ( ! empty( $atts['subtitle_color'] ) ) {
        $css_rules[] = "--wpc-coupon-subtitle-color: " . esc_attr( $atts['subtitle_color'] ) . ";";
    }
    if ( ! empty( $atts['subtitle_size'] ) ) {
        $css_rules[] = "--wpc-coupon-subtitle-size: " . esc_attr( $atts['subtitle_size'] ) . ";";
    }
    if ( ! empty( $atts['features_color'] ) ) {
        $css_rules[] = "--wpc-coupon-features-color: " . esc_attr( $atts['features_color'] ) . ";";
    }
    if ( ! empty( $atts['features_size'] ) ) {
        $css_rules[] = "--wpc-coupon-features-size: " . esc_attr( $atts['features_size'] ) . ";";
    }
    if ( ! empty( $atts['timer_bg_color'] ) ) {
        $css_rules[] = "--wpc-coupon-timer-bg: " . esc_attr( $atts['timer_bg_color'] ) . ";";
    }
    if ( ! empty( $atts['timer_text_color'] ) ) {
        $css_rules[] = "--wpc-coupon-timer-color: " . esc_attr( $atts['timer_text_color'] ) . ";";
    }
    if ( ! empty( $atts['timer_size'] ) ) {
        $css_rules[] = "--wpc-coupon-timer-size: " . esc_attr( $atts['timer_size'] ) . ";";
    }
    if ( ! empty( $atts['exclusive_bg_color'] ) ) {
        $css_rules[] = "--wpc-badge-exclusive-bg: " . esc_attr( $atts['exclusive_bg_color'] ) . ";";
    }
    if ( ! empty( $atts['exclusive_text_color'] ) ) {
        $css_rules[] = "--wpc-badge-exclusive-text: " . esc_attr( $atts['exclusive_text_color'] ) . ";";
    }
    if ( ! empty( $atts['verified_bg_color'] ) ) {
        $css_rules[] = "--wpc-badge-verified-bg: " . esc_attr( $atts['verified_bg_color'] ) . ";";
    }
    if ( ! empty( $atts['verified_text_color'] ) ) {
        $css_rules[] = "--wpc-badge-verified-text: " . esc_attr( $atts['verified_text_color'] ) . ";";
    }
    if ( ! empty( $atts['btn_bg_color'] ) ) {
        $css_rules[] = "--wpc-btn-bg: " . esc_attr( $atts['btn_bg_color'] ) . ";";
        $css_rules[] = "--wpc-btn-bg-rgb: " . wpc_coupon_hex_to_rgb( $atts['btn_bg_color'] ) . ";";
    }
    if ( ! empty( $atts['btn_text_color'] ) ) {
        $css_rules[] = "--wpc-btn-text: " . esc_attr( $atts['btn_text_color'] ) . ";";
    }
    if ( ! empty( $atts['btn_hover_color'] ) ) {
        $css_rules[] = "--wpc-btn-hover: " . esc_attr( $atts['btn_hover_color'] ) . ";";
    }
    if ( ! empty( $atts['btn_size'] ) ) {
        $css_rules[] = "--wpc-btn-size: " . esc_attr( $atts['btn_size'] ) . ";";
    }

    // Shadow logic
    $shadow_css = '0 30px 60px -15px rgba(15, 23, 42, 0.25)'; // heavy
    if ( $atts['card_shadow'] === 'none' ) {
        $shadow_css = 'none';
    } elseif ( $atts['card_shadow'] === 'light' ) {
        $shadow_css = '0 10px 25px -5px rgba(0,0,0,0.1)';
    } elseif ( $atts['card_shadow'] === 'medium' ) {
        $shadow_css = '0 20px 40px -10px rgba(15,23,42,0.15)';
    }
    $css_rules[] = "--wpc-coupon-card-shadow: {$shadow_css};";

    // Border logic
    if ( $atts['card_border_style'] !== 'none' ) {
        $border_color = ! empty( $atts['card_border_color'] ) ? esc_attr( $atts['card_border_color'] ) : $primary_color;
        $border_width = ! empty( $atts['card_border_width'] ) ? esc_attr( $atts['card_border_width'] ) : '2px';
        $border_style = esc_attr( $atts['card_border_style'] );
        $css_rules[] = "--wpc-coupon-card-border: {$border_width} {$border_style} {$border_color};";
    } else {
        $css_rules[] = "--wpc-coupon-card-border: none;";
    }

    $css = "
        #{$uid} {
            " . implode( "\n", $css_rules ) . "
        }
    ";
    wp_register_style( 'wpc-coupon-popup-styles', false );
    wp_enqueue_style( 'wpc-coupon-popup-styles' );
    wp_add_inline_style( 'wpc-coupon-popup-styles', $css );

    // Parse loop timer duration
    $timer_seconds = 900; // default 15 minutes
    $timer_val = trim( strtolower( $atts['timer'] ) );
    if ( $timer_val !== 'off' ) {
        $parsed_total = 0;
        
        // Match standard time formats: 1y 2mo 3d 4h 5m 6s
        // We use (?<!m)o to differentiate mo (month) from m (minute)
        preg_match_all('/(\d+)\s*(y|mo|d|h|m(?!o)|s)/i', $timer_val, $matches, PREG_SET_ORDER);
        
        if ( ! empty( $matches ) ) {
            foreach ( $matches as $match ) {
                $val = intval( $match[1] );
                $unit = strtolower( $match[2] );
                if ( $unit === 'y' ) $parsed_total += $val * 31536000;
                elseif ( $unit === 'mo' ) $parsed_total += $val * 2592000;
                elseif ( $unit === 'd' ) $parsed_total += $val * 86400;
                elseif ( $unit === 'h' ) $parsed_total += $val * 3600;
                elseif ( $unit === 'm' ) $parsed_total += $val * 60;
                elseif ( $unit === 's' ) $parsed_total += $val;
            }
            if ( $parsed_total > 0 ) {
                $timer_seconds = $parsed_total;
            }
        } elseif ( intval( $timer_val ) > 0 ) {
            $timer_seconds = intval( $timer_val ); // plain number = seconds
        }
    }

    // Resolve Coupon text: fallback to "DEAL" placeholder if empty, so the ticket layout behaves and looks premium
    $display_coupon = ! empty( $coupon ) ? $coupon : 'DEAL';

    // The placeholder text (e.g., 'SPECIAL') sits statically underneath the sliding button.
    // We no longer prepend dots so the text is fully controlled by the user.
    $masked_code = ! empty( $atts['mask_text'] ) ? esc_html( $atts['mask_text'] ) : 'SPECIAL';

    // Check if trigger button should be shown
    $show_trigger = ( $atts['show_trigger_btn'] === true || $atts['show_trigger_btn'] === 'true' || $atts['show_trigger_btn'] === '1' || $atts['show_trigger_btn'] === 1 );

    ob_start();
    ?>
    <!-- WPC Coupon Instance Wrapper -->
    <div id="<?php echo esc_attr( $uid ); ?>" class="wpc-coupon-popup-wrapper" 
         data-timer-seconds="<?php echo esc_attr( $timer_seconds ); ?>" 
         data-timer-enabled="<?php echo esc_attr( $timer_val !== 'off' ? 'true' : 'false' ); ?>"
         data-auto-open="<?php echo esc_attr( $atts['auto_open'] ); ?>"
         data-exit-intent="<?php echo esc_attr( $atts['exit_intent'] ? 'true' : 'false' ); ?>"
         data-trigger-freq="<?php echo esc_attr( $atts['trigger_freq'] ); ?>"
         data-cookie-expiry="<?php echo esc_attr( $atts['cookie_expiry'] ); ?>">

        <?php if ( $atts['layout'] === 'modal' ) : ?>
            <!-- Default Trigger Button -->
            <?php if ( $show_trigger && ! empty( $atts['trigger_text'] ) && empty( $atts['trigger_selector'] ) ) : ?>
                <button type="button" class="wpc-coupon-trigger-btn <?php echo esc_attr( $atts['trigger_class'] ); ?>" onclick="wpcOpenCouponPopup('<?php echo esc_js( $uid ); ?>')">
                    <?php echo esc_html( $atts['trigger_text'] ); ?>
                </button>
            <?php endif; ?>

            <!-- Modal Overlay -->
            <div class="wpc-coupon-popup-overlay" id="overlay-<?php echo esc_attr( $uid ); ?>" style="display: none;" onclick="wpcCloseCouponPopupOnOverlay(event, '<?php echo esc_js( $uid ); ?>')">
        <?php endif; ?>

        <!-- Coupon Card Box (Directly embedded in overlay or inline page flow) -->
        <div class="wpc-coupon-card <?php echo $atts['layout'] === 'inline' ? 'wpc-coupon-inline' : 'wpc-coupon-modal-card'; ?>">
            <?php if ( $atts['layout'] === 'modal' ) : ?>
                <button type="button" class="wpc-coupon-close-btn" onclick="wpcCloseCouponPopup('<?php echo esc_js( $uid ); ?>')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            <?php endif; ?>

            <div class="wpc-coupon-body">
                <!-- Left Section: Logo & Countdown -->
                <div class="wpc-coupon-left">
                    <?php if ( ! empty( $logo ) ) : ?>
                        <div class="wpc-coupon-logo-container">
                            <img src="<?php echo $logo; ?>" alt="<?php echo $name; ?>" class="wpc-coupon-logo" />
                        </div>
                    <?php endif; ?>

                    <?php if ( $timer_val !== 'off' ) : ?>
                        <div class="wpc-coupon-timer-container">
                            <div class="wpc-coupon-timer-clock">
                                <!-- Pair A: Years & Months (hidden by default) -->
                                <div class="wpc-timer-pair wpc-timer-pair-ym" style="display:none">
                                    <span class="wpc-timer-block years">00y</span>
                                    <span class="wpc-timer-sep">:</span>
                                    <span class="wpc-timer-block months">00mo</span>
                                </div>
                                <!-- Between-pair separator: A↔B -->
                                <span class="wpc-pair-sep wpc-pair-sep-ab" style="display:none">:</span>
                                <!-- Pair B: Days & Hours (hidden by default) -->
                                <div class="wpc-timer-pair wpc-timer-pair-dh" style="display:none">
                                    <span class="wpc-timer-block days">00d</span>
                                    <span class="wpc-timer-sep">:</span>
                                    <span class="wpc-timer-block hours">00h</span>
                                </div>
                                <!-- Between-pair separator: B↔C -->
                                <span class="wpc-pair-sep wpc-pair-sep-bc" style="display:none">:</span>
                                <!-- Pair C: Minutes & Seconds (always shown) -->
                                <div class="wpc-timer-pair wpc-timer-pair-ms">
                                    <span class="wpc-timer-block minutes">00m</span>
                                    <span class="wpc-timer-sep">:</span>
                                    <span class="wpc-timer-block seconds">00s</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right Section: Details & Coupon Copy Button -->
                <div class="wpc-coupon-right">
                    <h3 class="wpc-coupon-title"><?php echo $headline; ?></h3>
                    
                    <?php if ( ! empty( $subtitle ) ) : ?>
                        <p class="wpc-coupon-subtitle"><?php echo $subtitle; ?></p>
                    <?php endif; ?>

                    <ul class="wpc-coupon-features">
                        <?php foreach ( $feature_list as $feature ) : ?>
                            <li>
                                <svg class="wpc-feat-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                <span><?php echo esc_html( $feature ); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="wpc-coupon-badges">
                        <?php if ( ! empty( $atts['exclusive_label'] ) ) : ?>
                            <span class="wpc-coupon-badge badge-exclusive">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:4px;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                <?php echo esc_html( $atts['exclusive_label'] ); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ( ! empty( $atts['verified_label'] ) ) : ?>
                            <span class="wpc-coupon-badge badge-verified">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:4px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                <?php echo esc_html( $atts['verified_label'] ); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Coupon Action Button & Reveal Code Box -->
                    <div class="wpc-coupon-action-box">
                        <div class="wpc-coupon-btn-container preset-<?php echo esc_attr( $atts['button_style'] ); ?>">
                            <button type="button" 
                                    class="wpc-coupon-showcode-btn preset-<?php echo esc_attr( $atts['button_style'] ); ?>" 
                                    data-coupon="<?php echo esc_attr( $display_coupon ); ?>" 
                                    data-link="<?php echo esc_url( $link ); ?>" 
                                    data-button-text="<?php echo esc_attr( $atts['button_text'] ); ?>" 
                                    data-copied-text="<?php echo esc_attr( $atts['copied_text'] ); ?>" 
                                    data-copied-bg="<?php echo esc_attr( $atts['copied_bg_color'] ); ?>"
                                    data-copied-text-color="<?php echo esc_attr( $atts['copied_text_color'] ); ?>"
                                    data-click-action="<?php echo esc_attr( $atts['click_action'] ); ?>"
                                    onclick="wpcHandleCouponClick(this)">
                                <span class="btn-icon">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16"/><path d="M12 10a2 2 0 0 0 0-4 2 2 0 0 0 0 4z"/><path d="M12 12v3"/></svg>
                                </span>
                                <span class="btn-text-label"><?php echo esc_html( $atts['button_text'] ); ?></span>
                            </button>
                            <div class="wpc-coupon-code-reveal">
                                <span class="code-masked"><?php echo esc_html( $masked_code ); ?></span>
                                <span class="code-revealed" style="display: none;"><?php echo esc_html( $display_coupon ); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mascot Overlay (Optional) -->
                <?php if ( ! empty( $atts['mascot_url'] ) ) : ?>
                    <div class="wpc-coupon-mascot-container">
                        <img src="<?php echo esc_url( $atts['mascot_url'] ); ?>" alt="Mascot" class="wpc-coupon-mascot" />
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ( $atts['layout'] === 'modal' ) : ?>
            </div> <!-- End Modal Overlay -->
        <?php endif; ?>

        <!-- Bind Custom Trigger Selector Click Event if present -->
        <?php if ( ! empty( $atts['trigger_selector'] ) ) : ?>
            <script type="text/javascript">
                document.addEventListener('DOMContentLoaded', function() {
                    const selector = <?php echo json_encode( $atts['trigger_selector'] ); ?>;
                    const uid = <?php echo json_encode( $uid ); ?>;
                    const elements = document.querySelectorAll(selector);
                    elements.forEach(function(el) {
                        el.style.cursor = 'pointer';
                        el.addEventListener('click', function(e) {
                            e.preventDefault();
                            wpcOpenCouponPopup(uid);
                        });
                    });
                });
            </script>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'wpc_coupon_popup', 'wpc_coupon_popup_shortcode' );


/**
 * Helper: HEX to RGB string
 */
if ( ! function_exists( 'wpc_coupon_hex_to_rgb' ) ) {
    function wpc_coupon_hex_to_rgb( $hex ) {
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
        return "$r, $g, $b";
    }
}


/**
 * Enqueue Coupon Popup Frontend Scripts & Styles
 */
function wpc_enqueue_coupon_popup_assets() {
    ?>
    <style type="text/css">
        /* Base wrapper variables (inherits defaults from theme typography if not set) */
        .wpc-coupon-popup-wrapper {
            display: inline-block;
            font-family: inherit;
        }
        
        /* Modal Overlay */
        .wpc-coupon-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.51);
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            box-sizing: border-box;
            overflow-y: auto;
            animation: wpcFadeIn 0.3s ease-out forwards;
        }

        /* Default Trigger Button */
        .wpc-coupon-trigger-btn {
            background-color: var(--wpc-coupon-primary, #0ea5e9);
            color: #ffffff;
            border: none;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(var(--wpc-coupon-primary-rgb), 0.2);
            font-family: inherit;
        }
        .wpc-coupon-trigger-btn:hover {
            opacity: 0.95;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(var(--wpc-coupon-primary-rgb), 0.3);
        }

        /* Coupon Card Main Box - Styled with primary dashed border matching the Hostinger reference */
        .wpc-coupon-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: var(--wpc-coupon-card-shadow, 0 30px 60px -15px rgba(15, 23, 42, 0.25));
            width: 100%;
            max-width: 820px;
            position: relative;
            padding: 36px 30px;
            box-sizing: border-box;
            border: var(--wpc-coupon-card-border, none);
            font-family: inherit;
            text-align: left;
            /* Prevent overflow outside overlay on any screen */
            min-width: 0;
        }

        .wpc-coupon-inline {
            margin: 20px 0;
            max-width: 100%;
            overflow: hidden;
        }
        
        .wpc-coupon-modal-card {
            animation: wpcSlideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        
        .wpc-coupon-modal-card.wpc-no-animation {
            animation: none !important;
            opacity: 1 !important;
            transform: translateY(0) !important;
        }

        /* Close Button (Premium Circular Style) */
        .wpc-coupon-close-btn {
            position: absolute;
            top: 14px;
            right: 14px;
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            padding: 0;
            z-index: 10;
        }
        .wpc-coupon-close-btn:hover {
            color: #0f172a;
            background: #f1f5f9;
            transform: scale(1.1);
        }

        /* Body Grid Layout */
        .wpc-coupon-body {
            display: flex;
            gap: 24px;
            align-items: center;
            position: relative;
        }

        /* Left Section (Logo & Timer) - strict 40% column */
        .wpc-coupon-left {
            width: 40%;
            max-width: 40%;
            min-width: 0;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0 4px 0 0;
            box-sizing: border-box;
        }

        .wpc-coupon-logo-container {
            width: 100%;
            height: 110px;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
        }
        .wpc-coupon-logo {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* Styled Urgent Timer Blocks */
        .wpc-coupon-timer-container {
            text-align: center;
            width: 100%;
            /* No overflow:hidden here — it clips the borders on first/last blocks */
        }
        /* Desktop: all pairs in one horizontal row */
        .wpc-coupon-timer-clock {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 4px;
            width: 100%;
            flex-wrap: nowrap;
            /* Small padding so box-shadows on edge blocks are not clipped */
            padding: 3px 2px;
            box-sizing: border-box;
        }
        /* Each pair is a flex row (block : block) */
        .wpc-timer-pair {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 4px;
            flex-shrink: 0;
        }
        /* Between-pair separator (desktop: visible inline) */
        .wpc-pair-sep {
            font-size: 14px;
            font-weight: 800;
            color: #9ca3af;
            flex-shrink: 0;
        }
        /* Inner separator between two blocks in a pair */
        .wpc-timer-pair .wpc-timer-sep {
            font-size: 14px;
            font-weight: 800;
            color: #9ca3af;
            flex-shrink: 0;
        }
        .wpc-coupon-timer-clock .wpc-timer-block {
            background: #f3f4f6;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 17px;
            font-weight: 800;
            color: #111827;
            min-width: 46px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06);
            font-variant-numeric: tabular-nums;
            border: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        /* Right Section */
        .wpc-coupon-right {
            flex-grow: 1;
            padding-right: 40px; /* Space for mascot overlay */
        }

        /* Badges (Glassmorphic border-only style, moved below features) */
        .wpc-coupon-badges {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
        }
        .wpc-coupon-badge {
            display: inline-flex;
            align-items: center;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 4px 10px;
            border-radius: 9999px;
            line-height: 1;
        }
        .wpc-coupon-badge.badge-exclusive {
            background-color: var(--wpc-badge-exclusive-bg, rgba(249, 115, 22, 0.08));
            color: var(--wpc-badge-exclusive-text, #ea580c);
            border: 1px solid rgba(249, 115, 22, 0.15);
        }
        .wpc-coupon-badge.badge-verified {
            background-color: var(--wpc-badge-verified-bg, rgba(34, 197, 94, 0.08));
            color: var(--wpc-badge-verified-text, #16a34a);
            border: 1px solid rgba(34, 197, 94, 0.15);
        }

        /* Typography overrides (Large punchy headlines) */
        .wpc-coupon-title {
            font-size: var(--wpc-coupon-title-size, 22px);
            color: var(--wpc-coupon-title-color, #0f172a);
            font-weight: 800;
            margin: 0 0 10px 0;
            line-height: 1.3;
            letter-spacing: -0.01em;
        }
        .wpc-coupon-subtitle {
            font-size: var(--wpc-coupon-subtitle-size, 14px);
            color: var(--wpc-coupon-subtitle-color, #475569);
            margin: 0 0 18px 0;
            line-height: 1.6;
        }

        /* Features List (Slightly larger checklist spacing) */
        .wpc-coupon-features {
            list-style: none;
            padding: 0;
            margin: 0 0 20px 0;
        }
        .wpc-coupon-features li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: var(--wpc-coupon-features-size, 14px);
            color: var(--wpc-coupon-features-color, #4b5563);
            margin-bottom: 8px;
            line-height: 1.4;
        }
        .wpc-coupon-features li svg.wpc-feat-check {
            color: var(--wpc-coupon-primary, #0ea5e9);
            margin-top: 2px;
            flex-shrink: 0;
        }

        /* Coupon Action Box */
        .wpc-coupon-action-box {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            width: 100%;
            overflow: hidden;
        }

        /* ----------------------------------------------------
           BUTTON PRESETS & DESIGNS
           ---------------------------------------------------- */

        /* 1. TICKET STYLE — absolute overlap architecture */
        .wpc-coupon-btn-container.preset-ticket {
            position: relative;
            width: 280px;
            max-width: 100%;
            height: 48px;
            border: 2px solid rgba(var(--wpc-coupon-primary-rgb), 0.16);
            background: #ffffff;
            border-radius: 99px;
            box-sizing: border-box;
            overflow: hidden;
            display: block;
        }
        .wpc-coupon-btn-container.preset-ticket .wpc-coupon-code-reveal {
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            z-index: 1;
            color: #94a3b8;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 18px;
        }
        .wpc-coupon-showcode-btn.preset-ticket {
            position: absolute;
            top: 2px; left: 2px; bottom: 2px;
            z-index: 2;
            width: calc(100% - 50px);
            background-color: var(--wpc-btn-bg, var(--wpc-coupon-primary, #0ea5e9));
            color: var(--wpc-btn-text, #ffffff);
            font-size: var(--wpc-btn-size, 14px);
            border: none;
            font-weight: 700;
            border-radius: 99px 0 0 99px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.2s ease;
            font-family: inherit;
            white-space: nowrap;
            clip-path: polygon(0 0, calc(100% - 12px) 0, 100% 12px, 100% 100%, 0 100%);
            padding: 0;
        }
        .wpc-coupon-btn-container.preset-ticket:hover .wpc-coupon-showcode-btn {
            width: calc(100% - 90px);
            background-color: var(--wpc-btn-hover, var(--wpc-coupon-primary, #0ea5e9));
            opacity: 0.95;
        }
        .wpc-coupon-showcode-btn.preset-ticket::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 12px;
            height: 12px;
            background: rgba(0, 0, 0, 0.22);
            border-bottom-left-radius: 4px;
            z-index: 6;
            clip-path: polygon(0 0, 100% 100%, 0 100%);
        }

        /* 2. SOLID ROUNDED PILL STYLE */
        .wpc-coupon-btn-container.preset-solid {
            position: relative;
            width: 280px;
            max-width: 100%;
            height: 48px;
            background: #f1f5f9;
            border-radius: 99px;
            box-sizing: border-box;
            overflow: hidden;
            display: block;
        }
        .wpc-coupon-btn-container.preset-solid .wpc-coupon-code-reveal {
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            z-index: 1;
            color: #475569;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 18px;
        }
        .wpc-coupon-showcode-btn.preset-solid {
            position: absolute;
            top: 4px; left: 4px; bottom: 4px;
            z-index: 2;
            width: calc(100% - 54px);
            background-color: var(--wpc-btn-bg, var(--wpc-coupon-primary, #0ea5e9));
            color: var(--wpc-btn-text, #ffffff);
            font-size: var(--wpc-btn-size, 14px);
            border: none;
            font-weight: 700;
            border-radius: 99px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.2s ease;
            font-family: inherit;
            white-space: nowrap;
            box-shadow: 0 4px 10px rgba(var(--wpc-coupon-primary-rgb), 0.2);
            padding: 0;
        }
        .wpc-coupon-btn-container.preset-solid:hover .wpc-coupon-showcode-btn {
            width: calc(100% - 94px);
        }

        /* 3. OUTLINE PILL STYLE */
        .wpc-coupon-btn-container.preset-outline {
            position: relative;
            width: 280px;
            max-width: 100%;
            height: 48px;
            border: 2px solid var(--wpc-coupon-primary, #0ea5e9);
            background: transparent;
            border-radius: 99px;
            box-sizing: border-box;
            overflow: hidden;
            display: block;
        }
        .wpc-coupon-btn-container.preset-outline .wpc-coupon-code-reveal {
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            z-index: 1;
            color: var(--wpc-coupon-primary, #0ea5e9);
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 18px;
        }
        .wpc-coupon-showcode-btn.preset-outline {
            position: absolute;
            top: 2px; left: 2px; bottom: 2px;
            z-index: 2;
            width: calc(100% - 50px);
            background-color: transparent;
            color: var(--wpc-coupon-primary, #0ea5e9);
            font-size: var(--wpc-btn-size, 14px);
            border: none;
            font-weight: 700;
            border-radius: 99px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.2s ease, color 0.2s ease;
            font-family: inherit;
            white-space: nowrap;
            padding: 0;
        }
        .wpc-coupon-btn-container.preset-outline:hover .wpc-coupon-showcode-btn {
            width: calc(100% - 90px);
            background-color: var(--wpc-coupon-primary, #0ea5e9);
            color: #ffffff;
        }

        /* 4. GLOWING PULSE PILL STYLE */
        .wpc-coupon-btn-container.preset-glow {
            position: relative;
            width: 280px;
            max-width: 100%;
            height: 48px;
            background: #ffffff;
            border-radius: 99px;
            border: 1px solid #e2e8f0;
            box-sizing: border-box;
            overflow: hidden;
            display: block;
        }
        .wpc-coupon-btn-container.preset-glow .wpc-coupon-code-reveal {
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            z-index: 1;
            color: #475569;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 18px;
        }
        .wpc-coupon-showcode-btn.preset-glow {
            position: absolute;
            top: 3px; left: 3px; bottom: 3px;
            z-index: 2;
            width: calc(100% - 52px);
            background-color: var(--wpc-btn-bg, var(--wpc-coupon-primary, #0ea5e9));
            color: var(--wpc-btn-text, #ffffff);
            font-size: var(--wpc-btn-size, 14px);
            border: none;
            font-weight: 700;
            border-radius: 99px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.2s ease;
            font-family: inherit;
            white-space: nowrap;
            box-shadow: 0 0 0 0 rgba(var(--wpc-coupon-primary-rgb), 0.7);
            animation: wpcGlowPulse 2s infinite;
            padding: 0;
        }
        .wpc-coupon-btn-container.preset-glow:hover .wpc-coupon-showcode-btn {
            width: calc(100% - 92px);
        }

        /* Mascot Container (Overlaps the border and pops out elegantly) */
        .wpc-coupon-mascot-container {
            position: absolute;
            bottom: -5px;
            right: 25px;
            width: 160px;
            pointer-events: none;
            z-index: 10;
        }
        .wpc-coupon-mascot {
            width: 100%;
            height: auto;
            display: block;
        }

        /* Animations */
        @keyframes wpcFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes wpcSlideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes wpcCodeReveal {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        @keyframes wpcGlowPulse {
            0% {
                box-shadow: 0 0 0 0 rgba(var(--wpc-coupon-primary-rgb), 0.5);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(var(--wpc-coupon-primary-rgb), 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(var(--wpc-coupon-primary-rgb), 0);
            }
        }

        /* Inline layout: full width */
        .wpc-coupon-inline {
            max-width: 100%;
            overflow: hidden;
        }

        /* Responsive: mobile */
        /* ====================== RESPONSIVE: Mobile ====================== */
        @media (max-width: 768px) {
            .wpc-coupon-popup-overlay {
                align-items: flex-start;
                padding: 12px;
                box-sizing: border-box;
            }
            .wpc-coupon-card {
                padding: 20px 16px;
                border-radius: 16px;
                max-width: 100%;
                width: 100%;
                box-sizing: border-box;
                overflow: hidden;
            }
            .wpc-coupon-close-btn {
                top: 5px;
                right: 5px;
            }
            /* Stack body vertically on mobile */
            .wpc-coupon-body {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }
            /* Left section on mobile: full width, stack logo on top, timer below */
            .wpc-coupon-left {
                width: 100%;
                max-width: 100%;
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
                padding-right: 0;
                overflow: hidden;
            }
            /* Logo on mobile: small, left-aligned */
            .wpc-coupon-logo-container {
                width: 72px;
                height: 60px;
                margin-bottom: 0;
                flex-shrink: 0;
            }
            /* Timer on mobile: full width, pairs stack as rows (2×2 grid) */
            .wpc-coupon-timer-container {
                width: 100%;
            }
            /* On mobile: pairs stack vertically, between-pair seps hidden */
            .wpc-coupon-timer-clock {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
                justify-content: flex-start;
            }
            /* Each pair stays as a horizontal row on mobile */
            .wpc-timer-pair {
                gap: 6px;
            }
            .wpc-timer-pair .wpc-timer-block {
                min-width: 56px;
                font-size: 18px;
                padding: 8px 10px;
            }
            .wpc-timer-pair .wpc-timer-sep {
                font-size: 16px;
            }
            /* Hide between-pair separators on mobile (they become row breaks) */
            .wpc-pair-sep {
                display: none !important;
            }
            .wpc-coupon-right {
                width: 100%;
                padding-right: 0;
                min-width: 0;
            }
            .wpc-coupon-title {
                font-size: var(--wpc-coupon-title-size, 18px);
            }
            .wpc-coupon-subtitle {
                font-size: 13px;
            }
            .wpc-coupon-mascot-container {
                display: none;
            }
            .wpc-coupon-btn-container {
                max-width: 100% !important;
            }
            .wpc-coupon-action-box {
                width: 100%;
                overflow: hidden;
            }
            .wpc-coupon-features li {
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .wpc-coupon-card {
                padding: 16px 14px;
                border-radius: 14px;
            }
            .wpc-coupon-timer-clock .wpc-timer-block {
                font-size: 16px;
                padding: 6px 8px;
                min-width: 50px;
            }
            .wpc-coupon-timer-clock .wpc-timer-sep {
                font-size: 14px;
            }
            .wpc-coupon-btn-container {
                max-width: 100% !important;
            }
            .wpc-coupon-showcode-btn {
                font-size: 13px !important;
            }
        }
    </style>

    <script type="text/javascript">
        // Cookie Helpers
        function wpcSetCookie(name, value, days) {
            let expires = "";
            if (days) {
                let date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + (value || "")  + expires + "; path=/";
        }

        // Global Coupon Handlers
        window.wpcOpenCouponPopup = function(uid, isAutoTrigger = false, isExitIntent = false) {
            let isRetrigger = false;
            
            // Check trigger limits ONLY if this is triggered automatically (e.g. exit-intent or delay timer)
            if (isAutoTrigger) {
                const wrapper = document.getElementById(uid);
                const freq = wrapper ? wrapper.getAttribute('data-trigger-freq') : 'cookie';

                if (freq === 'cookie' && wpcGetCookie('wpc_closed_' + uid)) {
                    return; // Blocked by cookie
                } else if (freq === 'session' && sessionStorage.getItem('wpc_closed_' + uid)) {
                    return; // Blocked by session storage
                } else if (freq === 'page' && window['wpc_triggered_' + uid]) {
                    return; // Blocked by local JS variable (already triggered this page load)
                }
                
                // Track if this is a subsequent trigger on the same page (for aggressive exit intent logic)
                if (window['wpc_triggered_' + uid]) {
                    isRetrigger = true;
                }
                
                // Mark local variable if it triggered this page load
                window['wpc_triggered_' + uid] = true;
            }

            const overlay = document.getElementById('overlay-' + uid);
            if (overlay) {
                const card = overlay.querySelector('.wpc-coupon-modal-card');
                
                // Aggressive Exit Intent Logic: If triggered by exit intent, NEVER animate. Snap instantly.
                // Auto-Open timers will still retain animation.
                if (isExitIntent && card) {
                    card.classList.add('wpc-no-animation');
                } else if (card) {
                    card.classList.remove('wpc-no-animation');
                }

                overlay.style.display = 'flex';
                document.body.style.overflow = 'hidden'; // Lock background scrolling
                wpcStartTimer(uid);
            }
        };

        window.wpcCloseCouponPopup = function(uid) {
            const overlay = document.getElementById('overlay-' + uid);
            if (overlay) {
                overlay.style.display = 'none';
                document.body.style.overflow = ''; // Restore scrolling
                
                // Set cookie so it doesn't reopen automatically on future exit-intent/delays
                const wrapper = document.getElementById(uid);
                const cookieExpiry = wrapper ? parseFloat(wrapper.getAttribute('data-cookie-expiry')) : 1;
                if (cookieExpiry > 0) {
                    wpcSetCookie('wpc_closed_' + uid, '1', cookieExpiry);
                }
            }
        };

        window.wpcCloseCouponPopupOnOverlay = function(event, uid) {
            if (event.target.classList.contains('wpc-coupon-popup-overlay')) {
                wpcCloseCouponPopup(uid);
            }
        };

        window.wpcHandleCouponClick = function(btn) {
            const couponCode = btn.getAttribute('data-coupon');
            const affiliateLink = btn.getAttribute('data-link');
            const copiedText = btn.getAttribute('data-copied-text') || 'Copied!';
            const copiedBg = btn.getAttribute('data-copied-bg');
            const copiedTextColor = btn.getAttribute('data-copied-text-color');
            const clickAction = btn.getAttribute('data-click-action') || 'copy_reveal_redirect';
            
            // 1. Copy & Reveal Logic (If the action calls for copying/revealing)
            if (clickAction !== 'redirect_only') {
                if (couponCode) {
                    // Copy code to clipboard
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(couponCode);
                    } else {
                        // Fallback copy
                        const textarea = document.createElement('textarea');
                        textarea.value = couponCode;
                        textarea.style.position = 'fixed';
                        textarea.style.left = '-9999px';
                        document.body.appendChild(textarea);
                        textarea.select();
                        try {
                            document.execCommand('copy');
                        } catch (err) {
                            console.error('Fallback copy failed', err);
                        }
                        document.body.removeChild(textarea);
                    }
                }

                // Change Button text to "Copied!"
                const btnText = btn.querySelector('.btn-text-label');
                const originalText = btn.getAttribute('data-button-text');
                const originalBg = btn.style.backgroundColor;
                const originalColor = btn.style.color;

                if (btnText) btnText.textContent = copiedText;
                
                // Apply custom copied colors if defined, else default to green (#10b981) and white
                btn.style.backgroundColor = (copiedBg && copiedBg.trim() !== '') ? copiedBg : '#10b981';
                btn.style.color = (copiedTextColor && copiedTextColor.trim() !== '') ? copiedTextColor : '#ffffff';
                
                // Swap placeholder (SPECIAL) for real coupon code (SAVE50)
                const container = btn.closest('.wpc-coupon-btn-container');
                if (container) {
                    const masked = container.querySelector('.code-masked');
                    const revealedSpan = container.querySelector('.code-revealed');
                    
                    if (masked && revealedSpan) {
                        masked.style.display = 'none';
                        revealedSpan.style.display = 'inline-block';
                    }
                }

                // Reset after 3 seconds
                setTimeout(function() {
                    if (btnText) btnText.textContent = originalText;
                    btn.style.backgroundColor = originalBg;
                    btn.style.color = originalColor;
                    
                    // Revert the code reveal back to placeholder
                    if (container) {
                        const masked = container.querySelector('.code-masked');
                        const revealedSpan = container.querySelector('.code-revealed');
                        if (masked && revealedSpan) {
                            masked.style.display = 'inline-block';
                            revealedSpan.style.display = 'none';
                        }
                    }
                }, 3000);
            }

            // 2. Redirect Logic (If the action calls for opening link)
            if (clickAction === 'copy_reveal_redirect' || clickAction === 'redirect_only') {
                if (affiliateLink && affiliateLink !== '#' && affiliateLink.trim() !== '') {
                    window.open(affiliateLink, '_blank');
                }
            }
        };

        // Timer initialization map
        const activeTimers = {};

        window.wpcStartTimer = function(uid) {
            if (activeTimers[uid]) return; // Timer already running for this instance

            const wrapper = document.getElementById(uid);
            if (!wrapper) return;

            const timerEnabled = wrapper.getAttribute('data-timer-enabled') === 'true';
            if (!timerEnabled) return;

            let duration = parseInt(wrapper.getAttribute('data-timer-seconds'), 10) || 900;
            // If the user hasn't explicitly set a custom timer (or uses the default 15m), 
            // randomize it between 15m (900s) and 30m (1800s) to look organic.
            if (duration === 900) {
                duration = 900 + Math.floor(Math.random() * 900);
            }
            let timeRemaining = duration;

            const pairYM   = wrapper.querySelector('.wpc-timer-pair-ym');
            const pairDH   = wrapper.querySelector('.wpc-timer-pair-dh');
            const pairMS   = wrapper.querySelector('.wpc-timer-pair-ms');
            const sepAB    = wrapper.querySelector('.wpc-pair-sep-ab');
            const sepBC    = wrapper.querySelector('.wpc-pair-sep-bc');
            const yearsBlock  = wrapper.querySelector('.wpc-timer-block.years');
            const monthsBlock = wrapper.querySelector('.wpc-timer-block.months');
            const daysBlock   = wrapper.querySelector('.wpc-timer-block.days');
            const hoursBlock  = wrapper.querySelector('.wpc-timer-block.hours');
            const minutesBlock = wrapper.querySelector('.wpc-timer-block.minutes');
            const secondsBlock = wrapper.querySelector('.wpc-timer-block.seconds');

            const show = function(el, inline) { if (el) el.style.display = inline ? 'inline' : 'flex'; };
            const hide = function(el) { if (el) el.style.display = 'none'; };

            const updateClock = function() {
                let y  = Math.floor(timeRemaining / 31536000);
                let mo = Math.floor((timeRemaining % 31536000) / 2592000);
                let d  = Math.floor((timeRemaining % 2592000) / 86400);
                let h  = Math.floor((timeRemaining % 86400) / 3600);
                let m  = Math.floor((timeRemaining % 3600) / 60);
                let s  = timeRemaining % 60;

                // --- Pair A (years + months) ---
                const showPairYM = y > 0 || mo > 0;
                if (showPairYM) {
                    show(pairYM);
                    if (yearsBlock) yearsBlock.textContent = String(y).padStart(2, '0') + 'y';
                    if (monthsBlock) monthsBlock.textContent = String(mo).padStart(2, '0') + 'mo';
                } else {
                    hide(pairYM);
                }

                // --- Pair B (days + hours) ---
                const showPairDH = d > 0 || h > 0 || showPairYM;
                if (showPairDH) {
                    show(pairDH);
                    if (daysBlock) daysBlock.textContent = String(d).padStart(2, '0') + 'd';
                    if (hoursBlock) hoursBlock.textContent = String(h).padStart(2, '0') + 'h';
                } else {
                    hide(pairDH);
                }

                // --- Pair C (minutes + seconds) always shown ---
                if (minutesBlock) minutesBlock.textContent = String(m).padStart(2, '0') + 'm';
                if (secondsBlock) secondsBlock.textContent = String(s).padStart(2, '0') + 's';

                // --- Between-pair separators (only on desktop, CSS hides on mobile) ---
                if (showPairYM && showPairDH) { show(sepAB, true); } else { hide(sepAB); }
                if (showPairDH) { show(sepBC, true); } else { hide(sepBC); }

                if (timeRemaining <= 0) {
                    timeRemaining = duration; // Loop/reset timer to keep urgency alive!
                } else {
                    timeRemaining--;
                }
            };

            updateClock();
            activeTimers[uid] = setInterval(updateClock, 1000);
        };

        // Helper cookie getter
        function wpcGetCookie(name) {
            let nameEQ = name + "=";
            let ca = document.cookie.split(';');
            for(let i=0;i < ca.length;i++) {
                let c = ca[i];
                while (c.charAt(0)==' ') c = c.substring(1,c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
            }
            return null;
        }

        // Auto-initialize triggers (autoOpen / exitIntent / inline timers)
        document.addEventListener('DOMContentLoaded', function() {
            const wrappers = document.querySelectorAll('.wpc-coupon-popup-wrapper');
            wrappers.forEach(function(wrapper) {
                const uid = wrapper.getAttribute('id');
                const autoOpenDelay = wrapper.getAttribute('data-auto-open');
                const exitIntentEnabled = wrapper.getAttribute('data-exit-intent') === 'true';
                
                // 1. Check if it is Inline layout
                const isInline = wrapper.querySelector('.wpc-coupon-inline') !== null;
                if (isInline) {
                    wpcStartTimer(uid);
                    return;
                }

                // 2. Handle Auto Open Trigger (delayed popup)
                if (autoOpenDelay !== null && autoOpenDelay !== '' && !isNaN(autoOpenDelay)) {
                    setTimeout(function() {
                        wpcOpenCouponPopup(uid, true); // Pass true to verify cookie gating
                    }, parseFloat(autoOpenDelay) * 1000);
                }

                // 3. Handle Exit Intent Trigger
                if (exitIntentEnabled) {
                    document.addEventListener('mouseleave', function(e) {
                        if (e.clientY < 20) {
                            wpcOpenCouponPopup(uid, true, true); // (uid, isAutoTrigger, isExitIntent)
                        }
                    });
                }
            });
        });
    </script>
    <?php
}
add_action( 'wp_footer', 'wpc_enqueue_coupon_popup_assets' );
