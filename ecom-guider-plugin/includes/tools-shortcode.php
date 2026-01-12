<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register [wpc_tools] Shortcode
 */
add_shortcode( 'wpc_tools', 'wpc_tools_shortcode' );

function wpc_tools_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'collection' => '',      // Collection slug from item
        'ids'        => '',      // Manual tool IDs (comma-separated)
        'item_id'    => '',      // Item ID to pull collections from
        'style'      => 'grid',  // Display style: grid, list, detailed, compact
    ), $atts );

    $tools = array();

    // Case 1: Manual IDs provided
    if ( ! empty( $atts['ids'] ) ) {
        $ids = array_map( 'intval', explode( ',', $atts['ids'] ) );
        foreach ( $ids as $id ) {
            $tool = wpc_get_tool_data( $id );
            if ( $tool ) {
                $tools[] = $tool;
            }
        }
    }
    // Case 2: Collection from current post or specified item
    elseif ( ! empty( $atts['collection'] ) ) {
        $item_id = $atts['item_id'] ?: get_the_ID();
        $collections = get_post_meta( $item_id, '_wpc_tool_collections', true );
        
        if ( ! empty( $collections ) && is_array( $collections ) ) {
            foreach ( $collections as $coll ) {
                if ( sanitize_title( $coll['name'] ) === $atts['collection'] ) {
                    if ( ! empty( $coll['tools'] ) && is_array( $coll['tools'] ) ) {
                        foreach ( $coll['tools'] as $tool_id ) {
                            $tool = wpc_get_tool_data( intval( $tool_id ) );
                            if ( $tool ) {
                                $tools[] = $tool;
                            }
                        }
                    }
                    break;
                }
            }
        }
    }
    // Case 3: Default - all tools from current item's first collection
    else {
        $item_id = get_the_ID();
        $collections = get_post_meta( $item_id, '_wpc_tool_collections', true );
        
        if ( ! empty( $collections ) && is_array( $collections ) && isset( $collections[0] ) ) {
            if ( ! empty( $collections[0]['tools'] ) && is_array( $collections[0]['tools'] ) ) {
                foreach ( $collections[0]['tools'] as $tool_id ) {
                    $tool = wpc_get_tool_data( intval( $tool_id ) );
                    if ( $tool ) {
                        $tools[] = $tool;
                    }
                }
            }
        }
    }

    if ( empty( $tools ) ) {
        return '';
    }

    // Get display style (default to grid)
    $style = ! empty( $atts['style'] ) ? $atts['style'] : 'grid';

    // Prepare config (EXACT format as comparison items)
    $config = array(
        'items' => $tools, // Changed from 'tools' to 'items'
        'displayStyle' => $style, // grid, list, detailed, compact
        'showCompare' => false,
    );

    // Render root div (use existing comparison root class instead of custom)
    $props = esc_attr( wp_json_encode( $config ) );
    return sprintf(
        '<div class="wpc-comparison-root" data-props="%s"></div>',
        $props
    );
}

/**
 * Helper: Get Tool Data (Item-Compatible Format)
 */
function wpc_get_tool_data( $tool_id ) {
    $post = get_post( $tool_id );
    
    if ( ! $post || $post->post_type !== 'comparison_tool' || $post->post_status !== 'publish' ) {
        return null;
    }

    $logo = get_the_post_thumbnail_url( $tool_id, 'medium' );
    
    // Default / Check for Custom Table Data
    $data = null;
    if ( class_exists('WPC_Tools_Database') ) {
        $db = new WPC_Tools_Database();
        $tool = $db->get_tool($tool_id);
        if ($tool) {
            $data = [
                'badge' => $tool->badge_text,
                'short_description' => $tool->short_description,
                'rating' => $tool->rating,
                'link' => $tool->link,
                'button_text' => $tool->button_text,
                'pricing_plans' => $tool->pricing_plans,
                'features' => $tool->features
            ];
        }
    }

    // Fallbacks (Meta)
    $badge = $data['badge'] ?? get_post_meta( $tool_id, '_tool_badge', true );
    $short_desc = $data['short_description'] ?? get_post_meta( $tool_id, '_wpc_tool_short_description', true );
    $rating = $data['rating'] ?? floatval( get_post_meta( $tool_id, '_wpc_tool_rating', true ) ?: 4.5 );
    $link = $data['link'] ?? get_post_meta( $tool_id, '_tool_link', true );
    $button_text = $data['button_text'] ?? get_post_meta( $tool_id, '_tool_button_text', true ) ?: 'View Details';
    $pricing = $data['pricing_plans'] ?? get_post_meta( $tool_id, '_wpc_tool_pricing_plans', true );
    
    $features = $data['features'] ?? [];
    if ( empty($features) ) {
        $features_text = get_post_meta( $tool_id, '_wpc_tool_features', true );
        $features = ! empty( $features_text ) ? array_filter( array_map( 'trim', explode( "\n", $features_text ) ) ) : array();
    }

    $description = ! empty( $short_desc ) ? $short_desc : wp_strip_all_tags( get_the_content( null, false, $post ) );
    
    // Return in EXACT item format (matches comparison items)
    return array(
        'id'                => $tool_id,
        'name'             => $post->post_title,
        'logo'             => $logo ?: '',
        'badge'            => $badge,
        'short_description' => $description,
        'rating'           => floatval($rating),
        'link'             => $link,
        'button_text'      => $button_text,
        'pricing_plans'    => ! empty( $pricing ) && is_array( $pricing ) ? $pricing : array(),
        'features'         => $features,
        // Add empty fields that items have (for compatibility)
        'pros'             => array(),
        'cons'             => array(),
        'price'            => '',
        'price_period'     => '',
    );
}
