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
            'primaryColor'     => array( 'type' => 'string', 'default' => '' ),
            'cardBgColor'      => array( 'type' => 'string', 'default' => '#ffffff' ),
            'cardPadding'      => array( 'type' => 'string', 'default' => '36px 30px' ),
            'cardBorderRadius' => array( 'type' => 'string', 'default' => '24px' ),
            'leftWidth'        => array( 'type' => 'string', 'default' => '40%' ),
            'leftBgColor'      => array( 'type' => 'string', 'default' => 'transparent' ),
            'leftPadding'      => array( 'type' => 'string', 'default' => '0 4px 0 0' ),
            'dividerShow'      => array( 'type' => 'boolean', 'default' => false ),
            'dividerStyle'     => array( 'type' => 'string', 'default' => 'dashed' ),
            'dividerColor'     => array( 'type' => 'string', 'default' => '#e2e8f0' ),
            'dividerWidth'     => array( 'type' => 'string', 'default' => '1px' ),
            'mascotWidth'      => array( 'type' => 'string', 'default' => '160px' ),
            'mascotBottom'     => array( 'type' => 'string', 'default' => '-5px' ),
            'mascotPosition'   => array( 'type' => 'string', 'default' => 'right' ),
            'mascotOffset'     => array( 'type' => 'string', 'default' => '25px' ),
            'mascotBehind'     => array( 'type' => 'boolean', 'default' => true ),
            'mascotOpacity'    => array( 'type' => 'string', 'default' => '1' ),
            'timerBlockRadius' => array( 'type' => 'string', 'default' => '6px' ),
            'timerBlockBorderWidth' => array( 'type' => 'string', 'default' => '1px' ),
            'timerBlockBorderColor' => array( 'type' => 'string', 'default' => '#e5e7eb' ),
            'timerBlockPadding'=> array( 'type' => 'string', 'default' => '8px 10px' ),
            'timerBlockShadow' => array( 'type' => 'string', 'default' => 'light' ),
            'badgeRadius'      => array( 'type' => 'string', 'default' => '9999px' ),
            'badgeBorderWidth' => array( 'type' => 'string', 'default' => '1px' ),
            'badgePadding'     => array( 'type' => 'string', 'default' => '4px 10px' ),
            'closeBtnBg'       => array( 'type' => 'string', 'default' => 'transparent' ),
            'closeBtnColor'    => array( 'type' => 'string', 'default' => '#94a3b8' ),
            'closeBtnHoverBg'  => array( 'type' => 'string', 'default' => '#f1f5f9' ),
            'closeBtnHoverColor'=>array( 'type' => 'string', 'default' => '#0f172a' )
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
        'primary_color'    => $attributes['primaryColor'] ?? '',
        'card_bg_color'      => $attributes['cardBgColor'] ?? '#ffffff',
        'card_padding'      => $attributes['cardPadding'] ?? '36px 30px',
        'card_border_radius' => $attributes['cardBorderRadius'] ?? '24px',
        'left_width'        => $attributes['leftWidth'] ?? '40%',
        'left_bg_color'      => $attributes['leftBgColor'] ?? 'transparent',
        'left_padding'      => $attributes['leftPadding'] ?? '0 4px 0 0',
        'divider_show'      => $attributes['dividerShow'] ?? false,
        'divider_style'     => $attributes['dividerStyle'] ?? 'dashed',
        'divider_color'     => $attributes['dividerColor'] ?? '#e2e8f0',
        'divider_width'     => $attributes['dividerWidth'] ?? '1px',
        'mascot_width'      => $attributes['mascotWidth'] ?? '160px',
        'mascot_bottom'     => $attributes['mascotBottom'] ?? '-5px',
        'mascot_position'   => $attributes['mascotPosition'] ?? 'right',
        'mascot_offset'     => $attributes['mascotOffset'] ?? '25px',
        'mascot_behind'     => $attributes['mascotBehind'] ?? true,
        'mascot_opacity'    => $attributes['mascotOpacity'] ?? '1',
        'timer_block_radius' => $attributes['timerBlockRadius'] ?? '6px',
        'timer_block_border_width' => $attributes['timerBlockBorderWidth'] ?? '1px',
        'timer_block_border_color' => $attributes['timerBlockBorderColor'] ?? '#e5e7eb',
        'timer_block_padding'=> $attributes['timerBlockPadding'] ?? '8px 10px',
        'timer_block_shadow' => $attributes['timerBlockShadow'] ?? 'light',
        'badge_radius'      => $attributes['badgeRadius'] ?? '9999px',
        'badge_border_width' => $attributes['badgeBorderWidth'] ?? '1px',
        'badge_padding'     => $attributes['badgePadding'] ?? '4px 10px',
        'close_btn_bg'       => $attributes['closeBtnBg'] ?? 'transparent',
        'close_btn_color'    => $attributes['closeBtnColor'] ?? '#94a3b8',
        'close_btn_hover_bg'  => $attributes['closeBtnHoverBg'] ?? '#f1f5f9',
        'close_btn_hover_color'=> $attributes['closeBtnHoverColor'] ?? '#0f172a'
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
        'btn_size'         => '',
        'btn_bg_color'     => '',
        'btn_text_color'   => '',
        'btn_hover_color'  => '',
        'click_action'     => 'copy_reveal_redirect', // copy_reveal_redirect | copy_reveal_only | redirect_only
        'primary_color'    => '',
        'auto_open'        => '', // delay in seconds, e.g. 5
        'exit_intent'      => '', // true/false
        'trigger_freq'     => 'cookie', // cookie | page | session | always
        'cookie_expiry'    => '1', // days
        'itemOverrides'    => '{}', // JSON map of specific item overrides
        'card_bg_color'      => '#ffffff',
        'card_padding'      => '36px 30px',
        'card_border_radius' => '24px',
        'left_width'        => '40%',
        'left_bg_color'      => 'transparent',
        'left_padding'      => '0 4px 0 0',
        'divider_show'      => false,
        'divider_style'     => 'dashed',
        'divider_color'     => '#e2e8f0',
        'divider_width'     => '1px',
        'mascot_width'      => '160px',
        'mascot_bottom'     => '-5px',
        'mascot_position'   => 'right',
        'mascot_offset'     => '25px',
        'mascot_behind'     => true,
        'mascot_opacity'    => '1',
        'timer_block_radius' => '6px',
        'timer_block_border_width' => '1px',
        'timer_block_border_color' => '#e5e7eb',
        'timer_block_padding'=> '8px 10px',
        'timer_block_shadow' => 'light',
        'badge_radius'      => '9999px',
        'badge_border_width' => '1px',
        'badge_padding'     => '4px 10px',
        'close_btn_bg'       => 'transparent',
        'close_btn_color'    => '#94a3b8',
        'close_btn_hover_bg'  => '#f1f5f9',
        'close_btn_hover_color'=> '#0f172a',
    ), $atts );

    $item_id = 0;
    if ( ! empty( $atts['id'] ) ) {
        // Support multi-select randomizer: ID can be a comma-separated list
        $item_ids_raw = array_filter( array_map( 'trim', explode( ',', $atts['id'] ) ) );
        
        // If inline layout and multiple items, render a stack instead of picking a random one
        if ( $atts['layout'] === 'inline' && count( $item_ids_raw ) > 1 && ! isset( $atts['_wpc_is_recursive'] ) ) {
            $output = '<div class="wpc-coupon-inline-stack" style="display: flex; flex-direction: column; gap: 24px;">';
            foreach ( $item_ids_raw as $single_id ) {
                $single_atts = $atts;
                $single_atts['id'] = $single_id;
                $single_atts['_wpc_is_recursive'] = true;
                $output .= wpc_coupon_popup_shortcode( $single_atts );
            }
            $output .= '</div>';
            return $output;
        }

        if ( ! empty( $item_ids_raw ) ) {
            $random_key = array_rand( $item_ids_raw );
            $item_id = intval( $item_ids_raw[ $random_key ] );
        }
    }

    // Apply Per-Company Overrides if available
    if ( ! empty( $atts['itemOverrides'] ) && $item_id > 0 ) {
        $overrides_map = json_decode( $atts['itemOverrides'], true );
        if ( is_array( $overrides_map ) && isset( $overrides_map[ (string) $item_id ] ) ) {
            $specific = $overrides_map[ (string) $item_id ];
            // Merge specific overrides over the global shortcode attributes
            if ( ! empty( $specific['title'] ) ) $atts['title'] = $specific['title'];
            if ( ! empty( $specific['titleColor'] ) ) $atts['title_color'] = $specific['titleColor'];
            if ( ! empty( $specific['titleSize'] ) ) $atts['title_size'] = $specific['titleSize'];
            if ( ! empty( $specific['subtitle'] ) ) $atts['subtitle'] = $specific['subtitle'];
            if ( ! empty( $specific['subtitleColor'] ) ) $atts['subtitle_color'] = $specific['subtitleColor'];
            if ( ! empty( $specific['subtitleSize'] ) ) $atts['subtitle_size'] = $specific['subtitleSize'];
            
            if ( ! empty( $specific['timer'] ) ) $atts['timer'] = $specific['timer'];
            if ( ! empty( $specific['timerLabel'] ) ) $atts['timer_label'] = $specific['timerLabel'];
            if ( ! empty( $specific['timerBgColor'] ) ) $atts['timer_bg_color'] = $specific['timerBgColor'];
            if ( ! empty( $specific['timerTextColor'] ) ) $atts['timer_text_color'] = $specific['timerTextColor'];
            if ( ! empty( $specific['timerSize'] ) ) $atts['timer_size'] = $specific['timerSize'];
            
            if ( ! empty( $specific['couponCode'] ) ) $atts['coupon_code'] = $specific['couponCode'];
            if ( ! empty( $specific['affiliateLink'] ) ) $atts['affiliate_link'] = $specific['affiliateLink'];
            if ( ! empty( $specific['logoUrl'] ) ) $atts['logo_url'] = $specific['logoUrl'];
            if ( ! empty( $specific['mascotUrl'] ) ) $atts['mascot_url'] = $specific['mascotUrl'];
            if ( ! empty( $specific['primaryColor'] ) ) $atts['primary_color'] = $specific['primaryColor'];
            
            if ( ! empty( $specific['features'] ) ) $atts['features'] = $specific['features'];
            if ( ! empty( $specific['featuresColor'] ) ) $atts['features_color'] = $specific['featuresColor'];
            if ( ! empty( $specific['featuresSize'] ) ) $atts['features_size'] = $specific['featuresSize'];
            
            if ( ! empty( $specific['exclusiveLabel'] ) ) $atts['exclusive_label'] = $specific['exclusiveLabel'];
            if ( ! empty( $specific['exclusiveBgColor'] ) ) $atts['exclusive_bg_color'] = $specific['exclusiveBgColor'];
            if ( ! empty( $specific['exclusiveTextColor'] ) ) $atts['exclusive_text_color'] = $specific['exclusiveTextColor'];
            
            if ( ! empty( $specific['verifiedLabel'] ) ) $atts['verified_label'] = $specific['verifiedLabel'];
            if ( ! empty( $specific['verifiedBgColor'] ) ) $atts['verified_bg_color'] = $specific['verifiedBgColor'];
            if ( ! empty( $specific['verifiedTextColor'] ) ) $atts['verified_text_color'] = $specific['verifiedTextColor'];
            
            if ( ! empty( $specific['buttonStyle'] ) ) $atts['button_style'] = $specific['buttonStyle'];
            if ( ! empty( $specific['clickAction'] ) ) $atts['click_action'] = $specific['clickAction'];
            if ( ! empty( $specific['buttonText'] ) ) $atts['button_text'] = $specific['buttonText'];
            if ( ! empty( $specific['maskText'] ) ) $atts['mask_text'] = $specific['maskText'];
            if ( ! empty( $specific['copiedText'] ) ) $atts['copied_text'] = $specific['copiedText'];
            if ( ! empty( $specific['btnSize'] ) ) $atts['btn_size'] = $specific['btnSize'];
            if ( ! empty( $specific['btnBgColor'] ) ) $atts['btn_bg_color'] = $specific['btnBgColor'];
            if ( ! empty( $specific['btnTextColor'] ) ) $atts['btn_text_color'] = $specific['btnTextColor'];
            if ( ! empty( $specific['btnHoverColor'] ) ) $atts['btn_hover_color'] = $specific['btnHoverColor'];
            if ( ! empty( $specific['copiedBgColor'] ) ) $atts['copied_bg_color'] = $specific['copiedBgColor'];
            if ( ! empty( $specific['copiedTextColor'] ) ) $atts['copied_text_color'] = $specific['copiedTextColor'];
            
            if ( ! empty( $specific['cardShadow'] ) ) $atts['card_shadow'] = $specific['cardShadow'];
            if ( ! empty( $specific['cardBorderStyle'] ) ) $atts['card_border_style'] = $specific['cardBorderStyle'];
            if ( ! empty( $specific['cardBorderColor'] ) ) $atts['card_border_color'] = $specific['cardBorderColor'];
            if ( ! empty( $specific['cardBorderWidth'] ) ) $atts['card_border_width'] = $specific['cardBorderWidth'];

            if ( ! empty( $specific['cardBgColor'] ) ) $atts['card_bg_color'] = $specific['cardBgColor'];
            if ( ! empty( $specific['cardPadding'] ) ) $atts['card_padding'] = $specific['cardPadding'];
            if ( ! empty( $specific['cardBorderRadius'] ) ) $atts['card_border_radius'] = $specific['cardBorderRadius'];
            if ( ! empty( $specific['leftWidth'] ) ) $atts['left_width'] = $specific['leftWidth'];
            if ( ! empty( $specific['leftBgColor'] ) ) $atts['left_bg_color'] = $specific['leftBgColor'];
            if ( ! empty( $specific['leftPadding'] ) ) $atts['left_padding'] = $specific['leftPadding'];
            if ( isset( $specific['dividerShow'] ) ) $atts['divider_show'] = $specific['dividerShow'];
            if ( ! empty( $specific['dividerStyle'] ) ) $atts['divider_style'] = $specific['dividerStyle'];
            if ( ! empty( $specific['dividerColor'] ) ) $atts['divider_color'] = $specific['dividerColor'];
            if ( ! empty( $specific['dividerWidth'] ) ) $atts['divider_width'] = $specific['dividerWidth'];
            if ( ! empty( $specific['mascotWidth'] ) ) $atts['mascot_width'] = $specific['mascotWidth'];
            if ( ! empty( $specific['mascotBottom'] ) ) $atts['mascot_bottom'] = $specific['mascotBottom'];
            if ( ! empty( $specific['mascotPosition'] ) ) $atts['mascot_position'] = $specific['mascotPosition'];
            if ( ! empty( $specific['mascotOffset'] ) ) $atts['mascot_offset'] = $specific['mascotOffset'];
            if ( isset( $specific['mascotBehind'] ) ) $atts['mascot_behind'] = $specific['mascotBehind'];
            if ( ! empty( $specific['mascotOpacity'] ) ) $atts['mascot_opacity'] = $specific['mascotOpacity'];
            if ( ! empty( $specific['timerBlockRadius'] ) ) $atts['timer_block_radius'] = $specific['timerBlockRadius'];
            if ( ! empty( $specific['timerBlockBorderWidth'] ) ) $atts['timer_block_border_width'] = $specific['timerBlockBorderWidth'];
            if ( ! empty( $specific['timerBlockBorderColor'] ) ) $atts['timer_block_border_color'] = $specific['timerBlockBorderColor'];
            if ( ! empty( $specific['timerBlockPadding'] ) ) $atts['timer_block_padding'] = $specific['timerBlockPadding'];
            if ( ! empty( $specific['timerBlockShadow'] ) ) $atts['timer_block_shadow'] = $specific['timerBlockShadow'];
            if ( ! empty( $specific['badgeRadius'] ) ) $atts['badge_radius'] = $specific['badgeRadius'];
            if ( ! empty( $specific['badgeBorderWidth'] ) ) $atts['badge_border_width'] = $specific['badgeBorderWidth'];
            if ( ! empty( $specific['badgePadding'] ) ) $atts['badge_padding'] = $specific['badgePadding'];
            if ( ! empty( $specific['closeBtnBg'] ) ) $atts['close_btn_bg'] = $specific['closeBtnBg'];
            if ( ! empty( $specific['closeBtnColor'] ) ) $atts['close_btn_color'] = $specific['closeBtnColor'];
            if ( ! empty( $specific['closeBtnHoverBg'] ) ) $atts['close_btn_hover_bg'] = $specific['closeBtnHoverBg'];
            if ( ! empty( $specific['closeBtnHoverColor'] ) ) $atts['close_btn_hover_color'] = $specific['closeBtnHoverColor'];
        }
    }

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
    $mascot         = ! empty( $atts['mascot_url'] ) ? esc_url( $atts['mascot_url'] ) : '';
    
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

    // Advanced Design Customization Variables
    $card_bg = ! empty( $atts['card_bg_color'] ) ? esc_attr( $atts['card_bg_color'] ) : '#ffffff';
    $card_pad = ! empty( $atts['card_padding'] ) ? esc_attr( $atts['card_padding'] ) : '36px 30px';
    $card_radius = ! empty( $atts['card_border_radius'] ) ? esc_attr( $atts['card_border_radius'] ) : '24px';
    $left_w = ! empty( $atts['left_width'] ) ? esc_attr( $atts['left_width'] ) : '40%';
    $left_bg = ! empty( $atts['left_bg_color'] ) ? esc_attr( $atts['left_bg_color'] ) : 'transparent';
    $left_pad = ! empty( $atts['left_padding'] ) ? esc_attr( $atts['left_padding'] ) : '0 4px 0 0';

    $divider_style = ! empty( $atts['divider_style'] ) ? esc_attr( $atts['divider_style'] ) : 'dashed';
    $divider_color = ! empty( $atts['divider_color'] ) ? esc_attr( $atts['divider_color'] ) : '#e2e8f0';
    $divider_width = ! empty( $atts['divider_width'] ) ? esc_attr( $atts['divider_width'] ) : '1px';
    $divider_css = ( $atts['divider_show'] === true || $atts['divider_show'] === 'true' || $atts['divider_show'] === '1' || $atts['divider_show'] === 1 )
        ? "border-right: {$divider_width} {$divider_style} {$divider_color} !important;" 
        : "border-right: none !important;";

    // Mascot
    $masc_width = ! empty( $atts['mascot_width'] ) ? esc_attr( $atts['mascot_width'] ) : '160px';
    $masc_bottom = ! empty( $atts['mascot_bottom'] ) ? esc_attr( $atts['mascot_bottom'] ) : '-5px';
    $masc_offset = ! empty( $atts['mascot_offset'] ) ? esc_attr( $atts['mascot_offset'] ) : '25px';
    $masc_pos = ! empty( $atts['mascot_position'] ) ? esc_attr( $atts['mascot_position'] ) : 'right';
    $masc_behind_val = ( $atts['mascot_behind'] === true || $atts['mascot_behind'] === 'true' || $atts['mascot_behind'] === '1' || $atts['mascot_behind'] === 1 || ! isset($atts['mascot_behind']) );
    $masc_z = $masc_behind_val ? '1' : '10';
    $masc_opacity = ! empty( $atts['mascot_opacity'] ) ? esc_attr( $atts['mascot_opacity'] ) : '1';
    $masc_pos_css = ( $masc_pos === 'left' )
        ? "left: {$masc_offset} !important; right: auto !important;"
        : "right: {$masc_offset} !important; left: auto !important;";

    // Timer blocks style
    $timer_radius = ! empty( $atts['timer_block_radius'] ) ? esc_attr( $atts['timer_block_radius'] ) : '6px';
    $timer_b_width = ! empty( $atts['timer_block_border_width'] ) ? esc_attr( $atts['timer_block_border_width'] ) : '1px';
    $timer_b_color = ! empty( $atts['timer_block_border_color'] ) ? esc_attr( $atts['timer_block_border_color'] ) : '#e5e7eb';
    $timer_padding = ! empty( $atts['timer_block_padding'] ) ? esc_attr( $atts['timer_block_padding'] ) : '8px 10px';
    $timer_shadow_val = ! empty( $atts['timer_block_shadow'] ) ? esc_attr( $atts['timer_block_shadow'] ) : 'light';
    $timer_shadow = '0 2px 4px rgba(0,0,0,0.06)';
    if ( $timer_shadow_val === 'none' ) $timer_shadow = 'none';
    elseif ( $timer_shadow_val === 'medium' ) $timer_shadow = '0 4px 10px rgba(0,0,0,0.1)';
    elseif ( $timer_shadow_val === 'heavy' ) $timer_shadow = '0 8px 20px rgba(0,0,0,0.15)';

    // Badges style
    $badge_rad = ! empty( $atts['badge_radius'] ) ? esc_attr( $atts['badge_radius'] ) : '9999px';
    $badge_b_width = ! empty( $atts['badge_border_width'] ) ? esc_attr( $atts['badge_border_width'] ) : '1px';
    $badge_pad = ! empty( $atts['badge_padding'] ) ? esc_attr( $atts['badge_padding'] ) : '4px 10px';

    // Close button style
    $close_bg = ! empty( $atts['close_btn_bg'] ) ? esc_attr( $atts['close_btn_bg'] ) : 'transparent';
    $close_color = ! empty( $atts['close_btn_color'] ) ? esc_attr( $atts['close_btn_color'] ) : '#94a3b8';
    $close_h_bg = ! empty( $atts['close_btn_hover_bg'] ) ? esc_attr( $atts['close_btn_hover_bg'] ) : '#f1f5f9';
    $close_h_color = ! empty( $atts['close_btn_hover_color'] ) ? esc_attr( $atts['close_btn_hover_color'] ) : '#0f172a';

    $css = "
        #{$uid}, #overlay-{$uid} {
            " . implode( "\n", $css_rules ) . "
        }
        #{$uid} .wpc-coupon-card, #overlay-{$uid} .wpc-coupon-card {
            background: {$card_bg} !important;
            padding: {$card_pad} !important;
            border-radius: {$card_radius} !important;
        }
        #{$uid} .wpc-coupon-left, #overlay-{$uid} .wpc-coupon-left {
            width: {$left_w} !important;
            max-width: {$left_w} !important;
            background: {$left_bg} !important;
            padding: {$left_pad} !important;
            {$divider_css}
            position: relative !important;
            z-index: 2 !important;
        }
        #{$uid} .wpc-coupon-right, #overlay-{$uid} .wpc-coupon-right {
            position: relative !important;
            z-index: 2 !important;
        }
        #{$uid} .wpc-coupon-mascot-container, #overlay-{$uid} .wpc-coupon-mascot-container {
            width: {$masc_width} !important;
            bottom: {$masc_bottom} !important;
            z-index: {$masc_z} !important;
            opacity: {$masc_opacity} !important;
            {$masc_pos_css}
        }
        #{$uid} .wpc-coupon-timer-clock .wpc-timer-block, #overlay-{$uid} .wpc-coupon-timer-clock .wpc-timer-block {
            border-radius: {$timer_radius} !important;
            border: {$timer_b_width} solid {$timer_b_color} !important;
            padding: {$timer_padding} !important;
            box-shadow: {$timer_shadow} !important;
        }
        #{$uid} .wpc-coupon-badge, #overlay-{$uid} .wpc-coupon-badge {
            border-radius: {$badge_rad} !important;
            border-width: {$badge_b_width} !important;
            padding: {$badge_pad} !important;
        }
        #{$uid} .wpc-coupon-close-btn, #overlay-{$uid} .wpc-coupon-close-btn {
            background: {$close_bg} !important;
            color: {$close_color} !important;
        }
        #{$uid} .wpc-coupon-close-btn:hover, #overlay-{$uid} .wpc-coupon-close-btn:hover {
            background: {$close_h_bg} !important;
            color: {$close_h_color} !important;
        }
        /* Absolute centering fixes for WooCommerce logo and timer in any theme */
        #{$uid} .wpc-coupon-logo-container, #overlay-{$uid} .wpc-coupon-logo-container {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 100% !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }
        #{$uid} .wpc-coupon-logo, #overlay-{$uid} .wpc-coupon-logo {
            display: block !important;
            margin: 0 auto !important;
        }
        #{$uid} .wpc-coupon-timer-container, #overlay-{$uid} .wpc-coupon-timer-container {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            width: 100% !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }
        #{$uid} .wpc-coupon-timer-clock, #overlay-{$uid} .wpc-coupon-timer-clock {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            width: 100% !important;
            margin-left: auto !important;
            margin-right: auto !important;
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

    // Prepare the client-side randomizer pool (only needed if count > 1)
    $pool_json = '';
    if ( ! empty( $item_ids_raw ) && count( $item_ids_raw ) > 1 ) {
        $pool_data = array();
        if ( ! function_exists( 'wpc_fetch_items_data' ) ) {
            require_once WPC_PLUGIN_DIR . 'includes/api-endpoints.php';
        }
        $all_data = wpc_fetch_items_data( $item_ids_raw );
        $all_items = ! empty( $all_data['items'] ) ? $all_data['items'] : array();
        $overrides_map = json_decode( $atts['itemOverrides'], true );
        if ( ! is_array( $overrides_map ) ) $overrides_map = array();

        foreach ( $item_ids_raw as $cid ) {
            $cid_int = intval( $cid );
            $c_item = null;
            foreach ( $all_items as $i ) {
                if ( $i['id'] == $cid_int ) {
                    $c_item = $i;
                    break;
                }
            }
            
            $c_atts = $atts;
            // Unset the specific global override merging done earlier for the generic fallback
            // and apply the specific ones for this cid
            if ( isset( $overrides_map[ (string) $cid_int ] ) ) {
                $specific = $overrides_map[ (string) $cid_int ];
                if ( ! empty( $specific['title'] ) ) $c_atts['title'] = $specific['title'];
                if ( ! empty( $specific['titleColor'] ) ) $c_atts['title_color'] = $specific['titleColor'];
                if ( ! empty( $specific['titleSize'] ) ) $c_atts['title_size'] = $specific['titleSize'];
                if ( ! empty( $specific['subtitle'] ) ) $c_atts['subtitle'] = $specific['subtitle'];
                if ( ! empty( $specific['subtitleColor'] ) ) $c_atts['subtitle_color'] = $specific['subtitleColor'];
                if ( ! empty( $specific['subtitleSize'] ) ) $c_atts['subtitle_size'] = $specific['subtitleSize'];
                
                if ( ! empty( $specific['timer'] ) ) $c_atts['timer'] = $specific['timer'];
                if ( ! empty( $specific['timerLabel'] ) ) $c_atts['timer_label'] = $specific['timerLabel'];
                if ( ! empty( $specific['timerBgColor'] ) ) $c_atts['timer_bg_color'] = $specific['timerBgColor'];
                if ( ! empty( $specific['timerTextColor'] ) ) $c_atts['timer_text_color'] = $specific['timerTextColor'];
                if ( ! empty( $specific['timerSize'] ) ) $c_atts['timer_size'] = $specific['timerSize'];
                
                if ( ! empty( $specific['couponCode'] ) ) $c_atts['coupon_code'] = $specific['couponCode'];
                if ( ! empty( $specific['affiliateLink'] ) ) $c_atts['affiliate_link'] = $specific['affiliateLink'];
                if ( ! empty( $specific['logoUrl'] ) ) $c_atts['logo_url'] = $specific['logoUrl'];
                if ( ! empty( $specific['mascotUrl'] ) ) $c_atts['mascot_url'] = $specific['mascotUrl'];
                if ( ! empty( $specific['primaryColor'] ) ) $c_atts['primary_color'] = $specific['primaryColor'];
                
                if ( ! empty( $specific['features'] ) ) $c_atts['features'] = $specific['features'];
                if ( ! empty( $specific['featuresColor'] ) ) $c_atts['features_color'] = $specific['featuresColor'];
                if ( ! empty( $specific['featuresSize'] ) ) $c_atts['features_size'] = $specific['featuresSize'];
                
                if ( ! empty( $specific['exclusiveLabel'] ) ) $c_atts['exclusive_label'] = $specific['exclusiveLabel'];
                if ( ! empty( $specific['exclusiveBgColor'] ) ) $c_atts['exclusive_bg_color'] = $specific['exclusiveBgColor'];
                if ( ! empty( $specific['exclusiveTextColor'] ) ) $c_atts['exclusive_text_color'] = $specific['exclusiveTextColor'];
                
                if ( ! empty( $specific['verifiedLabel'] ) ) $c_atts['verified_label'] = $specific['verifiedLabel'];
                if ( ! empty( $specific['verifiedBgColor'] ) ) $c_atts['verified_bg_color'] = $specific['verifiedBgColor'];
                if ( ! empty( $specific['verifiedTextColor'] ) ) $c_atts['verified_text_color'] = $specific['verifiedTextColor'];
                
                if ( ! empty( $specific['buttonStyle'] ) ) $c_atts['button_style'] = $specific['buttonStyle'];
                if ( ! empty( $specific['clickAction'] ) ) $c_atts['click_action'] = $specific['clickAction'];
                if ( ! empty( $specific['buttonText'] ) ) $c_atts['button_text'] = $specific['buttonText'];
                if ( ! empty( $specific['maskText'] ) ) $c_atts['mask_text'] = $specific['maskText'];
                if ( ! empty( $specific['copiedText'] ) ) $c_atts['copied_text'] = $specific['copiedText'];
                if ( ! empty( $specific['btnSize'] ) ) $c_atts['btn_size'] = $specific['btnSize'];
                if ( ! empty( $specific['btnBgColor'] ) ) $c_atts['btn_bg_color'] = $specific['btnBgColor'];
                if ( ! empty( $specific['btnTextColor'] ) ) $c_atts['btn_text_color'] = $specific['btnTextColor'];
                if ( ! empty( $specific['btnHoverColor'] ) ) $c_atts['btn_hover_color'] = $specific['btnHoverColor'];
                if ( ! empty( $specific['copiedBgColor'] ) ) $c_atts['copied_bg_color'] = $specific['copiedBgColor'];
                if ( ! empty( $specific['copiedTextColor'] ) ) $c_atts['copied_text_color'] = $specific['copiedTextColor'];
                
                if ( ! empty( $specific['cardShadow'] ) ) $c_atts['card_shadow'] = $specific['cardShadow'];
                if ( ! empty( $specific['cardBorderStyle'] ) ) $c_atts['card_border_style'] = $specific['cardBorderStyle'];
                if ( ! empty( $specific['cardBorderColor'] ) ) $c_atts['card_border_color'] = $specific['cardBorderColor'];
                if ( ! empty( $specific['cardBorderWidth'] ) ) $c_atts['card_border_width'] = $specific['cardBorderWidth'];

                if ( ! empty( $specific['cardBgColor'] ) ) $c_atts['card_bg_color'] = $specific['cardBgColor'];
                if ( ! empty( $specific['cardPadding'] ) ) $c_atts['card_padding'] = $specific['cardPadding'];
                if ( ! empty( $specific['cardBorderRadius'] ) ) $c_atts['card_border_radius'] = $specific['cardBorderRadius'];
                if ( ! empty( $specific['leftWidth'] ) ) $c_atts['left_width'] = $specific['leftWidth'];
                if ( ! empty( $specific['leftBgColor'] ) ) $c_atts['left_bg_color'] = $specific['leftBgColor'];
                if ( ! empty( $specific['leftPadding'] ) ) $c_atts['left_padding'] = $specific['leftPadding'];
                if ( isset( $specific['dividerShow'] ) ) $c_atts['divider_show'] = $specific['dividerShow'];
                if ( ! empty( $specific['dividerStyle'] ) ) $c_atts['divider_style'] = $specific['dividerStyle'];
                if ( ! empty( $specific['dividerColor'] ) ) $c_atts['divider_color'] = $specific['dividerColor'];
                if ( ! empty( $specific['dividerWidth'] ) ) $c_atts['divider_width'] = $specific['dividerWidth'];
                if ( ! empty( $specific['mascotWidth'] ) ) $c_atts['mascot_width'] = $specific['mascotWidth'];
                if ( ! empty( $specific['mascotBottom'] ) ) $c_atts['mascot_bottom'] = $specific['mascotBottom'];
                if ( ! empty( $specific['mascotPosition'] ) ) $c_atts['mascot_position'] = $specific['mascotPosition'];
                if ( ! empty( $specific['mascotOffset'] ) ) $c_atts['mascot_offset'] = $specific['mascotOffset'];
                if ( isset( $specific['mascotBehind'] ) ) $c_atts['mascot_behind'] = $specific['mascotBehind'];
                if ( ! empty( $specific['mascotOpacity'] ) ) $c_atts['mascot_opacity'] = $specific['mascotOpacity'];
                if ( ! empty( $specific['timerBlockRadius'] ) ) $c_atts['timer_block_radius'] = $specific['timerBlockRadius'];
                if ( ! empty( $specific['timerBlockBorderWidth'] ) ) $c_atts['timer_block_border_width'] = $specific['timerBlockBorderWidth'];
                if ( ! empty( $specific['timerBlockBorderColor'] ) ) $c_atts['timer_block_border_color'] = $specific['timerBlockBorderColor'];
                if ( ! empty( $specific['timerBlockPadding'] ) ) $c_atts['timer_block_padding'] = $specific['timerBlockPadding'];
                if ( ! empty( $specific['timerBlockShadow'] ) ) $c_atts['timer_block_shadow'] = $specific['timerBlockShadow'];
                if ( ! empty( $specific['badgeRadius'] ) ) $c_atts['badge_radius'] = $specific['badgeRadius'];
                if ( ! empty( $specific['badgeBorderWidth'] ) ) $c_atts['badge_border_width'] = $specific['badgeBorderWidth'];
                if ( ! empty( $specific['badgePadding'] ) ) $c_atts['badge_padding'] = $specific['badgePadding'];
                if ( ! empty( $specific['closeBtnBg'] ) ) $c_atts['close_btn_bg'] = $specific['closeBtnBg'];
                if ( ! empty( $specific['closeBtnColor'] ) ) $c_atts['close_btn_color'] = $specific['closeBtnColor'];
                if ( ! empty( $specific['closeBtnHoverBg'] ) ) $c_atts['close_btn_hover_bg'] = $specific['closeBtnHoverBg'];
                if ( ! empty( $specific['closeBtnHoverColor'] ) ) $c_atts['close_btn_hover_color'] = $specific['closeBtnHoverColor'];
            }

            $c_name           = $c_item ? esc_html( $c_item['name'] ) : ( ! empty( $c_atts['title'] ) ? esc_html( $c_atts['title'] ) : 'Special Offer' );
            $c_logo           = ! empty( $c_atts['logo_url'] ) ? esc_url( $c_atts['logo_url'] ) : ( $c_item ? esc_url( $c_item['logo'] ) : '' );
            $c_coupon         = ! empty( $c_atts['coupon_code'] ) ? esc_html( $c_atts['coupon_code'] ) : ( $c_item ? esc_html( $c_item['coupon_code'] ) : '' );
            $c_link           = ! empty( $c_atts['affiliate_link'] ) ? esc_url( $c_atts['affiliate_link'] ) : ( $c_item ? esc_url( $c_item['direct_link'] ?: $c_item['details_link'] ) : '#' );
            $c_primary_color  = ! empty( $c_atts['primary_color'] ) ? esc_attr( $c_atts['primary_color'] ) : ( ( $c_item && isset($c_item['design_overrides']['primary']) ) ? $c_item['design_overrides']['primary'] : get_option( 'wpc_primary_color', '#0ea5e9' ) );
            $c_headline       = ! empty( $c_atts['title'] ) ? esc_html( $c_atts['title'] ) : sprintf( __( 'Get Exclusive %s Deal', 'wp-comparison-builder' ), $c_name );
            $c_subtitle       = ! empty( $c_atts['subtitle'] ) ? esc_html( $c_atts['subtitle'] ) : ( $c_item && ! empty( $c_item['description'] ) ? esc_html( wp_trim_words( $c_item['description'], 20, '...' ) ) : '' );
            $c_mascot         = ! empty( $c_atts['mascot_url'] ) ? esc_url( $c_atts['mascot_url'] ) : ( $c_item && isset($c_item['design_overrides']['mascot']) ? esc_url( $c_item['design_overrides']['mascot'] ) : '' );
            
            $c_feature_list = array();
            if ( ! empty( $c_atts['features'] ) ) {
                $c_feature_list = array_map( 'trim', explode( ',', $c_atts['features'] ) );
            } else {
                $c_feature_list = ( $c_item && ! empty( $c_item['pros'] ) ) ? array_slice( $c_item['pros'], 0, 3 ) : array(
                    __( '30-Day Money-back Guarantee', 'wp-comparison-builder' ),
                    __( 'Verified Premium Provider', 'wp-comparison-builder' ),
                    __( '24/7 Priority Support', 'wp-comparison-builder' )
                );
            }

            // Parse timer logic
            $c_timer_seconds = 900;
            $c_timer_val = trim( strtolower( $c_atts['timer'] ) );
            if ( $c_timer_val !== 'off' ) {
                $parsed_total = 0;
                preg_match_all('/(\d+)\s*(y|mo|d|h|m(?!o)|s)/i', $c_timer_val, $matches, PREG_SET_ORDER);
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
                    if ( $parsed_total > 0 ) $c_timer_seconds = $parsed_total;
                } elseif ( intval( $c_timer_val ) > 0 ) {
                    $c_timer_seconds = intval( $c_timer_val );
                }
            }

            $pool_data[] = array(
                'id' => $cid_int,
                'headline' => $c_headline,
                'subtitle' => $c_subtitle,
                'logo' => $c_logo,
                'coupon' => ! empty( $c_coupon ) ? $c_coupon : 'DEAL',
                'link' => $c_link,
                'primary_color' => $c_primary_color,
                'primary_rgb' => wpc_coupon_hex_to_rgb( $c_primary_color ),
                'mascot' => $c_mascot,
                'timer_val' => $c_timer_val,
                'timer_seconds' => $c_timer_seconds,
                'features' => $c_feature_list,
                
                // New Overrides
                'title_color' => $c_atts['title_color'],
                'title_size' => $c_atts['title_size'],
                'subtitle_color' => $c_atts['subtitle_color'],
                'subtitle_size' => $c_atts['subtitle_size'],
                
                'timer_label' => $c_atts['timer_label'],
                'timer_bg_color' => $c_atts['timer_bg_color'],
                'timer_text_color' => $c_atts['timer_text_color'],
                'timer_size' => $c_atts['timer_size'],
                
                'is_exclusive' => ( $c_item && ! empty( $c_item['is_exclusive'] ) ) ? true : false,
                'exclusive_label' => $c_atts['exclusive_label'],
                'exclusive_bg_color' => $c_atts['exclusive_bg_color'],
                'exclusive_text_color' => $c_atts['exclusive_text_color'],
                
                'is_verified' => ( $c_item && ! empty( $c_item['is_verified'] ) ) ? true : false,
                'verified_label' => $c_atts['verified_label'],
                'verified_bg_color' => $c_atts['verified_bg_color'],
                'verified_text_color' => $c_atts['verified_text_color'],
                
                'button_style' => $c_atts['button_style'],
                'click_action' => $c_atts['click_action'],
                'button_text' => $c_atts['button_text'],
                'mask_text' => $c_atts['mask_text'],
                'copied_text' => $c_atts['copied_text'],
                'btn_size' => $c_atts['btn_size'],
                'btn_bg_color' => $c_atts['btn_bg_color'],
                'btn_bg_rgb' => ! empty( $c_atts['btn_bg_color'] ) ? wpc_coupon_hex_to_rgb( $c_atts['btn_bg_color'] ) : '',
                'btn_text_color' => $c_atts['btn_text_color'],
                'btn_hover_color' => $c_atts['btn_hover_color'],
                'copied_bg_color' => $c_atts['copied_bg_color'],
                'copied_text_color' => $c_atts['copied_text_color'],
                
                'features_color' => $c_atts['features_color'],
                'features_size' => $c_atts['features_size'],
                
                'card_shadow' => $c_atts['card_shadow'],
                'card_border_style' => $c_atts['card_border_style'],
                'card_border_color' => $c_atts['card_border_color'],
                'card_border_width' => $c_atts['card_border_width'],

                'card_bg_color'      => $c_atts['card_bg_color'],
                'card_padding'      => $c_atts['card_padding'],
                'card_border_radius' => $c_atts['card_border_radius'],
                'left_width'        => $c_atts['left_width'],
                'left_bg_color'      => $c_atts['left_bg_color'],
                'left_padding'      => $c_atts['left_padding'],
                'divider_show'      => $c_atts['divider_show'],
                'divider_style'     => $c_atts['divider_style'],
                'divider_color'     => $c_atts['divider_color'],
                'divider_width'     => $c_atts['divider_width'],
                'mascot_width'      => $c_atts['mascot_width'],
                'mascot_bottom'     => $c_atts['mascot_bottom'],
                'mascot_position'   => $c_atts['mascot_position'],
                'mascot_offset'     => $c_atts['mascot_offset'],
                'mascot_behind'     => $c_atts['mascot_behind'],
                'mascot_opacity'    => $c_atts['mascot_opacity'] ?? '1',
                'timer_block_radius' => $c_atts['timer_block_radius'],
                'timer_block_border_width' => $c_atts['timer_block_border_width'],
                'timer_block_border_color' => $c_atts['timer_block_border_color'],
                'timer_block_padding'=> $c_atts['timer_block_padding'],
                'timer_block_shadow' => $c_atts['timer_block_shadow'],
                'badge_radius'      => $c_atts['badge_radius'],
                'badge_border_width' => $c_atts['badge_border_width'],
                'badge_padding'     => $c_atts['badge_padding'],
                'close_btn_bg'       => $c_atts['close_btn_bg'],
                'close_btn_color'    => $c_atts['close_btn_color'],
                'close_btn_hover_bg'  => $c_atts['close_btn_hover_bg'],
                'close_btn_hover_color'=> $c_atts['close_btn_hover_color']
            );
        }
        $pool_json = esc_attr( wp_json_encode( $pool_data ) );
    }

    ob_start();
    ?>
    <!-- WPC Coupon Instance Wrapper -->
    <div id="<?php echo esc_attr( $uid ); ?>" class="wpc-coupon-popup-wrapper" 
         data-timer-seconds="<?php echo esc_attr( $timer_seconds ); ?>" 
         data-timer-enabled="<?php echo esc_attr( $timer_val !== 'off' ? 'true' : 'false' ); ?>"
         data-auto-open="<?php echo esc_attr( $atts['auto_open'] ); ?>"
         data-exit-intent="<?php echo esc_attr( $atts['exit_intent'] ? 'true' : 'false' ); ?>"
         data-trigger-freq="<?php echo esc_attr( $atts['trigger_freq'] ); ?>"
         data-cookie-expiry="<?php echo esc_attr( $atts['cookie_expiry'] ); ?>"
         <?php if ( ! empty( $pool_json ) ) echo 'data-randomizer-pool="' . $pool_json . '"'; ?>>

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
                    <div class="wpc-coupon-logo-container" style="<?php echo empty( $logo ) ? 'display: none;' : ''; ?>">
                        <img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $name ); ?>" class="wpc-coupon-logo" />
                    </div>

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
                    
                    <p class="wpc-coupon-subtitle" style="<?php echo empty( $subtitle ) ? 'display: none;' : ''; ?>"><?php echo esc_html( $subtitle ); ?></p>

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
                <div class="wpc-coupon-mascot-container" style="<?php echo empty( $mascot ) ? 'display: none;' : ''; ?>">
                    <img src="<?php echo esc_url( $mascot ); ?>" alt="Mascot" class="wpc-coupon-mascot" />
                </div>
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

        /* 1b. DASHED TICKET STYLE — rectangular dashed border */
        .wpc-coupon-btn-container.preset-dashed_ticket {
            position: relative;
            width: 280px;
            max-width: 100%;
            height: 48px;
            border: 2px solid rgba(var(--wpc-coupon-primary-rgb), 0.16);
            background: #ffffff;
            border-radius: 6px;
            box-sizing: border-box;
            overflow: hidden;
            display: block;
        }
        .wpc-coupon-btn-container.preset-dashed_ticket .wpc-coupon-code-reveal {
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
        .wpc-coupon-showcode-btn.preset-dashed_ticket {
            position: absolute;
            top: 2px; left: 2px; bottom: 2px;
            z-index: 2;
            width: calc(100% - 50px);
            background-color: var(--wpc-btn-bg, var(--wpc-coupon-primary, #0ea5e9));
            color: var(--wpc-btn-text, #ffffff);
            font-size: var(--wpc-btn-size, 14px);
            border: 2px dashed rgba(255, 255, 255, 0.8) !important;
            font-weight: 700;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.2s ease;
            font-family: inherit;
            white-space: nowrap;
            padding: 0;
        }
        .wpc-coupon-btn-container.preset-dashed_ticket:hover .wpc-coupon-showcode-btn {
            width: calc(100% - 90px);
            background-color: var(--wpc-btn-hover, var(--wpc-coupon-primary, #0ea5e9));
            opacity: 0.95;
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

        /* Inline layout: perfectly centered, max-width preserved */
        .wpc-coupon-inline {
            margin: 40px auto;
            max-width: 820px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
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
                // Move overlay to body to prevent CSS transform/filter containing block issues from the theme
                if (overlay.parentNode !== document.body) {
                    document.body.appendChild(overlay);
                }

                const card = overlay.querySelector('.wpc-coupon-modal-card');
                
                // Randomize immediately before showing
                wpcInjectRandomCoupon(uid);
                
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
        window.wpcActiveTimers = window.wpcActiveTimers || {};

        window.wpcStartTimer = function(uid) {
            if (window.wpcActiveTimers[uid]) return; // Timer already running for this instance

            const wrapper = document.getElementById(uid);
            if (!wrapper) return;
            
            // If the overlay exists, the card (and timer) was moved to the body inside it
            let container = wrapper;
            const overlay = document.getElementById('overlay-' + uid);
            if (overlay) {
                container = overlay;
            }

            const timerEnabled = wrapper.getAttribute('data-timer-enabled') === 'true';
            if (!timerEnabled) return;

            let duration = parseInt(wrapper.getAttribute('data-timer-seconds'), 10) || 900;
            // If the user hasn't explicitly set a custom timer (or uses the default 15m), 
            // randomize it between 15m (900s) and 30m (1800s) to look organic.
            if (duration === 900) {
                duration = 900 + Math.floor(Math.random() * 900);
            }
            let timeRemaining = duration;

            const pairYM   = container.querySelector('.wpc-timer-pair-ym');
            const pairDH   = container.querySelector('.wpc-timer-pair-dh');
            const pairMS   = container.querySelector('.wpc-timer-pair-ms');
            const sepAB    = container.querySelector('.wpc-pair-sep-ab');
            const sepBC    = container.querySelector('.wpc-pair-sep-bc');
            const yearsBlock  = container.querySelector('.wpc-timer-block.years');
            const monthsBlock = container.querySelector('.wpc-timer-block.months');
            const daysBlock   = container.querySelector('.wpc-timer-block.days');
            const hoursBlock  = container.querySelector('.wpc-timer-block.hours');
            const minutesBlock = container.querySelector('.wpc-timer-block.minutes');
            const secondsBlock = container.querySelector('.wpc-timer-block.seconds');

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
            window.wpcActiveTimers[uid] = setInterval(updateClock, 1000);
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

        // Helper cookie setter
        function wpcSetCookie(name, value, days) {
            let expires = "";
            if (days) {
                let date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + (value || "")  + expires + "; path=/";
        }

        // JS Randomizer Engine
        window._wpc_last_shown = window._wpc_last_shown || {};
        
        window.wpcInjectRandomCoupon = function(uid) {
            const wrapper = document.getElementById(uid);
            if (!wrapper) return;
            const poolJson = wrapper.getAttribute('data-randomizer-pool');
            if (!poolJson) return; // Not multi-select, keep static PHP
            
            // The card may have been moved to the body via the overlay, so we must search there
            let container = wrapper;
            const overlay = document.getElementById('overlay-' + uid);
            if (overlay) {
                container = overlay;
            }
            
            try {
                const pool = JSON.parse(poolJson);
                if (!pool || pool.length <= 1) return; // Only 1 item, no randomization needed
                
                // Pick random item, ensuring it's different from the last shown item if possible
                let availableItems = pool;
                if (pool.length > 1 && window._wpc_last_shown[uid]) {
                    availableItems = pool.filter(function(item) { return item.id != window._wpc_last_shown[uid]; });
                    // Fallback just in case filter somehow empties the array (e.g. data mutation)
                    if (availableItems.length === 0) availableItems = pool;
                }
                
                const randomItem = availableItems[Math.floor(Math.random() * availableItems.length)];
                window._wpc_last_shown[uid] = randomItem.id;
                
                // 1. Logo
                const logoContainer = container.querySelector('.wpc-coupon-logo-container');
                if (logoContainer) {
                    if (randomItem.logo) {
                        const img = logoContainer.querySelector('img');
                        if (img) img.src = randomItem.logo;
                        logoContainer.style.display = 'block';
                    } else {
                        logoContainer.style.display = 'none';
                    }
                }
                
                // 2. Texts
                const titleEl = container.querySelector('.wpc-coupon-title');
                if (titleEl) titleEl.innerHTML = randomItem.headline || '';
                
                const subtitleEl = container.querySelector('.wpc-coupon-subtitle');
                if (subtitleEl) {
                    if (randomItem.subtitle) {
                        subtitleEl.innerHTML = randomItem.subtitle;
                        subtitleEl.style.display = 'block';
                    } else {
                        subtitleEl.style.display = 'none';
                    }
                }
                
                // 3. Mascot
                const mascotContainer = container.querySelector('.wpc-coupon-mascot-container');
                if (mascotContainer) {
                    if (randomItem.mascot) {
                        const img = mascotContainer.querySelector('img');
                        if (img) img.src = randomItem.mascot;
                        mascotContainer.style.display = 'block';
                    } else {
                        mascotContainer.style.display = 'none';
                    }
                }
                
                // 3b. Labels
                const exclusiveLabelEl = container.querySelector('.wpc-coupon-label-exclusive');
                if (exclusiveLabelEl) exclusiveLabelEl.style.display = randomItem.is_exclusive ? 'inline-flex' : 'none';
                
                const verifiedLabelEl = container.querySelector('.wpc-coupon-label-verified');
                if (verifiedLabelEl) verifiedLabelEl.style.display = randomItem.is_verified ? 'inline-flex' : 'none';
                
                // 4. Timer
                wrapper.setAttribute('data-timer-seconds', randomItem.timer_seconds);
                wrapper.setAttribute('data-timer-enabled', randomItem.timer_val !== 'off' ? 'true' : 'false');
                const timerContainer = container.querySelector('.wpc-coupon-timer-container');
                if (timerContainer) {
                    timerContainer.style.display = randomItem.timer_val !== 'off' ? 'block' : 'none';
                }
                
                // 5. Button and Reveal Box
                const btnContainer = container.querySelector('.wpc-coupon-btn-container');
                const btn = container.querySelector('.wpc-coupon-showcode-btn');
                
                // If the style changed, update classes
                if (btnContainer && btn && randomItem.button_style) {
                    btnContainer.className = 'wpc-coupon-btn-container preset-' + randomItem.button_style;
                    btn.className = 'wpc-coupon-showcode-btn preset-' + randomItem.button_style;
                }
                
                if (btn) {
                    btn.setAttribute('data-coupon', randomItem.coupon);
                    btn.setAttribute('data-link', randomItem.link);
                    if (randomItem.button_text) btn.setAttribute('data-button-text', randomItem.button_text);
                    if (randomItem.copied_text) btn.setAttribute('data-copied-text', randomItem.copied_text);
                    if (randomItem.click_action) btn.setAttribute('data-click-action', randomItem.click_action);
                    if (randomItem.copied_bg_color) btn.setAttribute('data-copied-bg', randomItem.copied_bg_color);
                    if (randomItem.copied_text_color) btn.setAttribute('data-copied-text-color', randomItem.copied_text_color);
                    
                    const btnLabel = btn.querySelector('.btn-text-label');
                    if (btnLabel && randomItem.button_text) btnLabel.innerText = randomItem.button_text;
                }
                
                const revealedCode = container.querySelector('.code-revealed');
                if (revealedCode) revealedCode.innerText = randomItem.coupon;
                
                const maskedCode = container.querySelector('.code-masked');
                if (maskedCode && randomItem.mask_text) maskedCode.innerText = randomItem.mask_text;
                
                // 6. Features
                const featList = container.querySelector('.wpc-coupon-features');
                if (featList && randomItem.features) {
                    let html = '';
                    randomItem.features.forEach(function(feat) {
                        html += '<li><svg class="wpc-feat-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg><span>' + feat + '</span></li>';
                    });
                    featList.innerHTML = html;
                }
                
                // 7. Colors & Overrides (Update CSS Variables)
                const overlayEl = document.getElementById('overlay-' + uid);
                const targets = [wrapper];
                if (overlayEl) targets.push(overlayEl);
                
                targets.forEach(function(t) {
                    if (randomItem.primary_color) t.style.setProperty('--wpc-coupon-primary', randomItem.primary_color);
                    if (randomItem.primary_rgb) t.style.setProperty('--wpc-coupon-primary-rgb', randomItem.primary_rgb);
                    
                    if (randomItem.title_color) t.style.setProperty('--wpc-coupon-title-color', randomItem.title_color);
                    if (randomItem.title_size) t.style.setProperty('--wpc-coupon-title-size', randomItem.title_size);
                    if (randomItem.subtitle_color) t.style.setProperty('--wpc-coupon-subtitle-color', randomItem.subtitle_color);
                    if (randomItem.subtitle_size) t.style.setProperty('--wpc-coupon-subtitle-size', randomItem.subtitle_size);
                    
                    if (randomItem.timer_bg_color) t.style.setProperty('--wpc-coupon-timer-bg', randomItem.timer_bg_color);
                    if (randomItem.timer_text_color) t.style.setProperty('--wpc-coupon-timer-color', randomItem.timer_text_color);
                    if (randomItem.timer_size) t.style.setProperty('--wpc-coupon-timer-size', randomItem.timer_size);
                    
                    if (randomItem.exclusive_bg_color) t.style.setProperty('--wpc-badge-exclusive-bg', randomItem.exclusive_bg_color);
                    if (randomItem.exclusive_text_color) t.style.setProperty('--wpc-badge-exclusive-text', randomItem.exclusive_text_color);
                    
                    if (randomItem.verified_bg_color) t.style.setProperty('--wpc-badge-verified-bg', randomItem.verified_bg_color);
                    if (randomItem.verified_text_color) t.style.setProperty('--wpc-badge-verified-text', randomItem.verified_text_color);
                    
                    if (randomItem.btn_bg_color) {
                        t.style.setProperty('--wpc-btn-bg', randomItem.btn_bg_color);
                        if (randomItem.btn_bg_rgb) t.style.setProperty('--wpc-btn-bg-rgb', randomItem.btn_bg_rgb);
                    }
                    if (randomItem.btn_text_color) t.style.setProperty('--wpc-btn-text', randomItem.btn_text_color);
                    if (randomItem.btn_hover_color) t.style.setProperty('--wpc-btn-hover', randomItem.btn_hover_color);
                    if (randomItem.btn_size) t.style.setProperty('--wpc-btn-size', randomItem.btn_size);
                    
                    if (randomItem.copied_bg_color) t.style.setProperty('--wpc-copied-bg', randomItem.copied_bg_color);
                    if (randomItem.copied_text_color) t.style.setProperty('--wpc-copied-text', randomItem.copied_text_color);
                    
                    if (randomItem.features_color) t.style.setProperty('--wpc-coupon-features-color', randomItem.features_color);
                    if (randomItem.features_size) t.style.setProperty('--wpc-coupon-features-size', randomItem.features_size);
                    
                    if (randomItem.card_shadow) {
                        if (randomItem.card_shadow === 'none') t.style.setProperty('--wpc-coupon-card-shadow', 'none');
                        else if (randomItem.card_shadow === 'soft') t.style.setProperty('--wpc-coupon-card-shadow', '0 10px 25px -5px rgba(15, 23, 42, 0.1)');
                        else t.style.setProperty('--wpc-coupon-card-shadow', '0 30px 60px -15px rgba(15, 23, 42, 0.25)');
                    }
                    if (randomItem.card_border_style) {
                        if (randomItem.card_border_style === 'none') {
                            t.style.setProperty('--wpc-coupon-card-border', 'none');
                        } else {
                            const width = randomItem.card_border_width || '2px';
                            const style = randomItem.card_border_style || 'solid';
                            const color = randomItem.card_border_color || randomItem.primary_color;
                            t.style.setProperty('--wpc-coupon-card-border', width + ' ' + style + ' ' + color);
                        }
                    }

                    if (randomItem.card_bg_color) {
                        const cardEl = t.querySelector('.wpc-coupon-card');
                        if (cardEl) cardEl.style.setProperty('background', randomItem.card_bg_color, 'important');
                    }
                    if (randomItem.card_padding) {
                        const cardEl = t.querySelector('.wpc-coupon-card');
                        if (cardEl) cardEl.style.setProperty('padding', randomItem.card_padding, 'important');
                    }
                    if (randomItem.card_border_radius) {
                        const cardEl = t.querySelector('.wpc-coupon-card');
                        if (cardEl) cardEl.style.setProperty('border-radius', randomItem.card_border_radius, 'important');
                    }
                    if (randomItem.left_width) {
                        const leftEl = t.querySelector('.wpc-coupon-left');
                        if (leftEl) {
                            leftEl.style.setProperty('width', randomItem.left_width, 'important');
                            leftEl.style.setProperty('max-width', randomItem.left_width, 'important');
                        }
                    }
                    if (randomItem.left_bg_color) {
                        const leftEl = t.querySelector('.wpc-coupon-left');
                        if (leftEl) leftEl.style.setProperty('background', randomItem.left_bg_color, 'important');
                    }
                    if (randomItem.left_padding) {
                        const leftEl = t.querySelector('.wpc-coupon-left');
                        if (leftEl) leftEl.style.setProperty('padding', randomItem.left_padding, 'important');
                    }
                    if (randomItem.divider_show !== undefined) {
                        const leftEl = t.querySelector('.wpc-coupon-left');
                        if (leftEl) {
                            const showDiv = randomItem.divider_show === true || randomItem.divider_show === 'true' || randomItem.divider_show === 1 || randomItem.divider_show === '1';
                            if (showDiv) {
                                const w = randomItem.divider_width || '1px';
                                const s = randomItem.divider_style || 'dashed';
                                const c = randomItem.divider_color || '#e2e8f0';
                                leftEl.style.setProperty('border-right', w + ' ' + s + ' ' + c, 'important');
                            } else {
                                leftEl.style.setProperty('border-right', 'none', 'important');
                            }
                        }
                    }
                    if (randomItem.mascot_width || randomItem.mascot_bottom || randomItem.mascot_position || randomItem.mascot_offset || randomItem.mascot_behind !== undefined || randomItem.mascot_opacity !== undefined) {
                        const mascEl = t.querySelector('.wpc-coupon-mascot-container');
                        if (mascEl) {
                            if (randomItem.mascot_width) mascEl.style.setProperty('width', randomItem.mascot_width, 'important');
                            if (randomItem.mascot_bottom) mascEl.style.setProperty('bottom', randomItem.mascot_bottom, 'important');
                            if (randomItem.mascot_opacity !== undefined) mascEl.style.setProperty('opacity', randomItem.mascot_opacity, 'important');
                            if (randomItem.mascot_behind !== undefined) {
                                const behind = randomItem.mascot_behind === true || randomItem.mascot_behind === 'true' || randomItem.mascot_behind === 1 || randomItem.mascot_behind === '1';
                                mascEl.style.setProperty('z-index', behind ? '1' : '10', 'important');
                            }
                            const pos = randomItem.mascot_position || 'right';
                            const offset = randomItem.mascot_offset || '25px';
                            if (pos === 'left') {
                                mascEl.style.setProperty('left', offset, 'important');
                                mascEl.style.setProperty('right', 'auto', 'important');
                            } else {
                                mascEl.style.setProperty('right', offset, 'important');
                                mascEl.style.setProperty('left', 'auto', 'important');
                            }
                        }
                    }
                    if (randomItem.timer_block_radius || randomItem.timer_block_border_width || randomItem.timer_block_border_color || randomItem.timer_block_padding || randomItem.timer_block_shadow) {
                        const blocks = t.querySelectorAll('.wpc-coupon-timer-clock .wpc-timer-block');
                        blocks.forEach(function(b) {
                            if (randomItem.timer_block_radius) b.style.setProperty('border-radius', randomItem.timer_block_radius, 'important');
                            if (randomItem.timer_block_border_width || randomItem.timer_block_border_color) {
                                const w = randomItem.timer_block_border_width || '1px';
                                const c = randomItem.timer_block_border_color || '#e5e7eb';
                                b.style.setProperty('border', w + ' solid ' + c, 'important');
                            }
                            if (randomItem.timer_block_padding) b.style.setProperty('padding', randomItem.timer_block_padding, 'important');
                            if (randomItem.timer_block_shadow) {
                                let sh = '0 2px 4px rgba(0,0,0,0.06)';
                                if (randomItem.timer_block_shadow === 'none') sh = 'none';
                                else if (randomItem.timer_block_shadow === 'medium') sh = '0 4px 10px rgba(0,0,0,0.1)';
                                else if (randomItem.timer_block_shadow === 'heavy') sh = '0 8px 20px rgba(0,0,0,0.15)';
                                b.style.setProperty('box-shadow', sh, 'important');
                            }
                        });
                    }
                    if (randomItem.badge_radius || randomItem.badge_border_width || randomItem.badge_padding) {
                        const badges = t.querySelectorAll('.wpc-coupon-badge');
                        badges.forEach(function(b) {
                            if (randomItem.badge_radius) b.style.setProperty('border-radius', randomItem.badge_radius, 'important');
                            if (randomItem.badge_border_width) b.style.setProperty('border-width', randomItem.badge_border_width, 'important');
                            if (randomItem.badge_padding) b.style.setProperty('padding', randomItem.badge_padding, 'important');
                        });
                    }
                    if (randomItem.close_btn_bg || randomItem.close_btn_color) {
                        const close = t.querySelector('.wpc-coupon-close-btn');
                        if (close) {
                            if (randomItem.close_btn_bg) close.style.setProperty('background', randomItem.close_btn_bg, 'important');
                            if (randomItem.close_btn_color) close.style.setProperty('color', randomItem.close_btn_color, 'important');
                        }
                    }
                });
                
                // Ensure timer resets completely if running
                if (window.wpcActiveTimers && window.wpcActiveTimers[uid]) {
                    clearInterval(window.wpcActiveTimers[uid]);
                    window.wpcActiveTimers[uid] = null;
                }
                
            } catch (e) {
                console.error('WPC Randomizer Error:', e);
            }
        };

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
                    wpcInjectRandomCoupon(uid);
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
