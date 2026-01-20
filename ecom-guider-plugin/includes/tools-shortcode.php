<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register [wpc_tools] Shortcode - Pure PHP SSR
 * No React dependency - Fast initial load
 */
add_shortcode( 'wpc_tools', 'wpc_tools_shortcode' );

function wpc_tools_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'collection' => '',      // Collection slug from item
        'ids'        => '',      // Manual tool IDs (comma-separated)
        'item_id'    => '',      // Item ID to pull collections from
        'style'      => 'grid',  // Display style: grid, list, detailed, compact
        'columns'    => '3',     // Number of columns
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

    // Enqueue only styles (no React!)
    wp_enqueue_style( 'wpc-styles' );

    // Get colors
    $primary_color = get_option( 'wpc_primary_color', '#6366f1' );
    $hover_color = get_option( 'wpc_button_hover_color', '' ) ?: '#4f46e5';
    $star_color = get_option( 'wpc_star_rating_color', '#fbbf24' );
    $border_color = get_option( 'wpc_border_color', '#e2e8f0' );

    $columns = intval( $atts['columns'] );
    $count = count( $tools );

    ob_start();
    ?>
    <div class="wpc-tools-grid" style="width: 100%;">
        <style>
            .wpc-tools-grid-inner { 
                display: grid; 
                grid-template-columns: repeat(1, minmax(0, 1fr)); 
                gap: 1.5rem; 
            }
            @media (min-width: 640px) {
                .wpc-tools-grid-inner { grid-template-columns: repeat(<?php echo min($count, 2); ?>, minmax(0, 1fr)); }
            }
            @media (min-width: 768px) {
                .wpc-tools-grid-inner { grid-template-columns: repeat(<?php echo min($count, $columns); ?>, minmax(0, 1fr)); }
            }
            .wpc-tool-card { transition: box-shadow 0.2s, transform 0.2s; }
            .wpc-tool-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.1); transform: translateY(-2px); }
        </style>

        <div class="wpc-tools-grid-inner">
            <?php foreach ( $tools as $tool ) : 
                $logo = esc_url( $tool['logo'] );
                $name = esc_html( $tool['name'] );
                $desc = esc_html( $tool['short_description'] );
                $badge = esc_html( $tool['badge'] );
                $rating = floatval( $tool['rating'] );
                $link = esc_url( $tool['link'] );
                $button_text = esc_html( $tool['button_text'] ?: 'View Details' );

                // Star rating
                $full_stars = floor( $rating );
                $has_half = ( $rating - $full_stars ) >= 0.5;
            ?>
                <div class="wpc-tool-card" style="
                    background: #fff;
                    border-radius: 0.75rem;
                    border: 1px solid <?php echo esc_attr( $border_color ); ?>;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                    padding: 1.5rem;
                    display: flex;
                    flex-direction: column;
                ">
                    <!-- Header -->
                    <div style="display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1rem;">
                        <?php if ( ! empty( $logo ) ) : ?>
                        <div style="
                            width: 3rem;
                            height: 3rem;
                            border-radius: 0.5rem;
                            background: #f9fafb;
                            border: 1px solid <?php echo esc_attr( $border_color ); ?>;
                            padding: 0.375rem;
                            flex-shrink: 0;
                        ">
                            <img src="<?php echo $logo; ?>" alt="<?php echo $name; ?>" style="width: 100%; height: 100%; object-fit: contain;" loading="lazy" />
                        </div>
                        <?php endif; ?>
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <h3 class="wpc-heading" style="font-weight: 600; margin: 0;"><?php echo $name; ?></h3>
                                <?php if ( ! empty( $badge ) ) : ?>
                                    <span style="
                                        display: inline-block;
                                        padding: 0.125rem 0.5rem;
                                        background: <?php echo esc_attr( $primary_color ); ?>20;
                                        color: <?php echo esc_attr( $primary_color ); ?>;
                                        font-weight: 500;
                                        border-radius: 9999px;
                                    "><?php echo $badge; ?></span>
                                <?php endif; ?>
                            </div>
                            <!-- Rating -->
                            <div style="display: flex; align-items: center; gap: 0.25rem; margin-top: 0.25rem;">
                                <?php for ( $i = 0; $i < $full_stars; $i++ ) : ?>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="<?php echo esc_attr( $star_color ); ?>" stroke="<?php echo esc_attr( $star_color ); ?>" stroke-width="1"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                <?php endfor; ?>
                                <?php if ( $has_half ) : ?>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="<?php echo esc_attr( $star_color ); ?>" stroke-width="1">
                                        <defs><linearGradient id="half-tool"><stop offset="50%" stop-color="<?php echo esc_attr( $star_color ); ?>"/><stop offset="50%" stop-color="transparent"/></linearGradient></defs>
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" fill="url(#half-tool)"></polygon>
                                    </svg>
                                <?php endif; ?>
                                <span class="wpc-text-muted" style="margin-left: 0.25rem;"><?php echo number_format( $rating, 1 ); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <?php if ( ! empty( $desc ) ) : ?>
                    <p class="wpc-text-muted" style="
                        line-height: 1.5;
                        margin: 0 0 1rem 0;
                        flex: 1;
                        display: -webkit-box;
                        -webkit-line-clamp: 3;
                        -webkit-box-orient: vertical;
                        overflow: hidden;
                    "><?php echo $desc; ?></p>
                    <?php endif; ?>

                    <!-- CTA (onclick to hide affiliate URL in status bar) -->
                    <?php if ( ! empty( $link ) ) : ?>
                    <button 
                        type="button"
                        onclick="window.open('<?php echo esc_js( $link ); ?>', '_blank');"
                        class="wpc-text-body"
                        style="
                            display: block;
                            width: 100%;
                            text-align: center;
                            padding: 0.625rem 1rem;
                            background: <?php echo esc_attr( $primary_color ); ?>;
                            color: #fff;
                            font-weight: 500;
                            border-radius: 0.5rem;
                            border: none;
                            cursor: pointer;
                            margin-top: auto;
                            transition: background 0.2s;
                        "
                        onmouseover="this.style.background='<?php echo esc_attr( $hover_color ); ?>';"
                        onmouseout="this.style.background='<?php echo esc_attr( $primary_color ); ?>';"
                    ><?php echo $button_text; ?></button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
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
