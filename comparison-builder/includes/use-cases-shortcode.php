<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode: [wpc_use_cases]
 * Displays "Best Use Cases" grid for an item.
 * Pure PHP SSR - No React dependency.
 */
function wpc_use_cases_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'id' => '',
        'columns' => '4',
        'title' => '',
        'category' => '', // Product Variants Module
    ), $atts, 'wpc_use_cases' );

    $post_id = ! empty( $atts['id'] ) ? intval( $atts['id'] ) : get_the_ID();
    if ( ! $post_id ) return '';

    // 1. Determine Scope
    $variants_enabled = get_post_meta( $post_id, '_wpc_variants_enabled', true ) === '1';
    $requested_cat = ! empty( $atts['category'] ) ? sanitize_text_field( $atts['category'] ) : '';
    
    error_log( "WPC Debug [UseCases]: Post ID $post_id, Variants: " . ($variants_enabled ? 'Yes' : 'No') . ", ReqCat: '$requested_cat'" );

    $target_categories = array(); 
    $render_selector = false;
    $selector_style = 'tabs';
    $active_cat = '';

    if ( $variants_enabled ) {
        if ( ! empty( $requested_cat ) ) {
            $target_categories = array( $requested_cat );
        } else {
            $cat_ids = get_post_meta( $post_id, '_wpc_variant_categories', true );
            error_log( "WPC Debug [UseCases]: Variant Cat IDs: " . print_r($cat_ids, true) );
            
            $terms = array();
            if ( ! empty( $cat_ids ) && is_array( $cat_ids ) ) {
                $terms = get_terms( array( 'taxonomy' => 'comparison_category', 'include' => $cat_ids, 'hide_empty' => false ) );
            } else {
                // Fallback: If no variant categories selected, use ALL assigned categories (matching Admin UI)
                $terms = wp_get_post_terms( $post_id, 'comparison_category' );
            }

            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                foreach( $terms as $term ) {
                    $target_categories[] = $term->slug;
                }
                $selector_style = get_post_meta( $post_id, '_wpc_category_selector_style', true ) ?: 'tabs';
                if ( $selector_style !== 'hidden' && count($target_categories) > 1 ) {
                    $render_selector = true;
                }
                $default_cat = get_post_meta( $post_id, '_wpc_default_category', true );
                $active_cat = ( ! empty( $default_cat ) && in_array( $default_cat, $target_categories ) ) ? $default_cat : $target_categories[0];
            }
        }
    }
    
    error_log( "WPC Debug [UseCases]: Target Cats: " . print_r($target_categories, true) );
    error_log( "WPC Debug [UseCases]: Render Selector: " . ($render_selector ? 'Yes' : 'No') );
    
    if ( empty( $target_categories ) ) {
        $target_categories = array( '' );
    } else {
        if ( empty( $active_cat ) ) $active_cat = $target_categories[0];
    }

    $all_use_cases = get_post_meta( $post_id, '_wpc_use_cases', true );
    $uc_by_cat_map = get_post_meta( $post_id, '_wpc_use_cases_by_category', true );
    
    // Check if we have ANY data (Global OR Category-specific)
    $has_global = ! empty( $all_use_cases ) && is_array( $all_use_cases );
    $has_cats   = ! empty( $uc_by_cat_map ) && is_array( $uc_by_cat_map );
    
    if ( ! $has_global && ! $has_cats ) {
        return '<!-- WPC DEBUG: No use cases found (Global or Category-specific). Post ID: ' . $post_id . ' -->';
    }
    
    // Ensure array for safe iteration later
    if ( ! $has_global ) $all_use_cases = array();

    // Enqueue
    wp_enqueue_style( 'wpc-styles' );
    wp_enqueue_style( 'fontawesome' );

    $global_primary = get_option( 'wpc_primary_color', '#6366f1' );
    $global_icon_color = get_option( 'wpc_usecase_icon_color', $global_primary );
    $columns = intval( $atts['columns'] );
    $title_base = esc_html( $atts['title'] );

    $wrapper_id = 'wpc-uc-' . $post_id . '-' . mt_rand(1000,9999);

    // Make sure JS is Enqueued for Tabs
    if ( $render_selector ) {
        wp_enqueue_script( 'wpc-frontend' );
    }

    ob_start();
    echo '<!-- WPC DEBUG: 
        Variants Enabled: ' . ($variants_enabled ? 'Yes' : 'No') . '
        Target Cats: ' . implode(', ', $target_categories) . '
        Active Cat: ' . $active_cat . '
        Render Selector: ' . ($render_selector ? 'Yes' : 'No') . '
    -->';
    ?>
    <style>
        /* Hide scrollbar for tabs to prevent ugly arrows */
        .wpc-cat-tabs::-webkit-scrollbar { display: none; }
        .wpc-cat-tabs { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
    <div id="<?php echo esc_attr($wrapper_id); ?>" class="wpc-use-cases-container" style="width: 100%;">
        <?php if ( ! empty( $title_base ) ) : ?>
            <h2 class="wpc-heading" style="font-weight: 700; margin-bottom: 1.5rem; text-align: center;"><?php echo $title_base; ?></h2>
        <?php endif; ?>

        <?php 
        // Render Selector (if applicable)
        if ( $render_selector ) {
            // Need term objects for helper
            $cat_objects = array();
            foreach ($target_categories as $slug) {
                $term = get_term_by( 'slug', $slug, 'comparison_category' );
                if ( $term ) $cat_objects[] = $term;
            }
            if ( function_exists('wpc_render_category_selector') ) {
                wpc_render_category_selector( $active_cat, $cat_objects, $selector_style, $wrapper_id );
            }
        }
        ?>

        <?php foreach ( $target_categories as $loop_cat_slug ) : 
            $is_visible = ( $loop_cat_slug === $active_cat );
            $use_cases = $all_use_cases;
            
            // Filter Use Cases per Loop Category
            if ( $variants_enabled && ! empty( $loop_cat_slug ) ) {
                if ( ! empty( $uc_by_cat_map ) && isset( $uc_by_cat_map[ $loop_cat_slug ] ) ) {
                    $cat_data = $uc_by_cat_map[ $loop_cat_slug ];
                    
                    if ( ! empty( $cat_data ) ) {
                         $first_item = reset( $cat_data );
                         
                         if ( is_array( $first_item ) ) {
                             // DATA TYPE: OBJECTS (From Enhanced Tab)
                             // Use them directly as they are full definitions
                             $use_cases = $cat_data;
                         } else {
                             // DATA TYPE: SCALARS (Indices from Checkboxes)
                             // Filter global list by these indices
                             $allowed_indices = $cat_data;
                             $filtered_uc = array();
                             foreach ( $all_use_cases as $idx => $uc ) {
                                 if ( in_array( $idx, $allowed_indices ) ) {
                                     $filtered_uc[] = $uc; 
                                 }
                             }
                             $use_cases = $filtered_uc;
                         }
                    } else {
                        // Empty array = No use cases for this category
                        $use_cases = array();
                    }
                } else {
                     // No mapping found? Fallback to none, or all? 
                     // Typically means "None Selected" for this specific category if mapping exists but is null
                     // But if mapping key doesn't exist? 
                     // Let's assume empty.
                     $use_cases = array();
                }
            }
            
            $count = count( $use_cases );
            
            // Grid Classes
            $grid_cols = 'grid-cols-1'; // used in native Tailwind, but here we use inline styles below
        ?>
            <div 
                class="wpc-tab-content" 
                data-tab="<?php echo esc_attr( $loop_cat_slug ); ?>" 
                style="display: <?php echo $is_visible ? 'block' : 'none'; ?>;"
            >
                <?php if ( $count === 0 ) : ?>
                    <p style="text-align: center; color: hsl(var(--muted-foreground)); font-style: italic; padding: 20px;">
                        <?php _e( 'No use cases defined for this category.', 'wp-comparison-builder' ); ?>
                    </p>
                <?php else : ?>
                    <!-- Inline Responsive Styles per Grid (Scoped) -->
                    <style>
                        #<?php echo $wrapper_id; ?> .wpc-use-cases-grid.<?php echo esc_attr($loop_cat_slug); ?> { grid-template-columns: repeat(1, minmax(0, 1fr)); }
                        @media (min-width: 640px) {
                            #<?php echo $wrapper_id; ?> .wpc-use-cases-grid.<?php echo esc_attr($loop_cat_slug); ?> { grid-template-columns: repeat(<?php echo min($count, 2); ?>, minmax(0, 1fr)); }
                        }
                        @media (min-width: 768px) {
                            #<?php echo $wrapper_id; ?> .wpc-use-cases-grid.<?php echo esc_attr($loop_cat_slug); ?> { grid-template-columns: repeat(<?php echo min($count, 3); ?>, minmax(0, 1fr)); }
                        }
                        @media (min-width: 1024px) {
                            #<?php echo $wrapper_id; ?> .wpc-use-cases-grid.<?php echo esc_attr($loop_cat_slug); ?> { grid-template-columns: repeat(<?php echo min($count, $columns); ?>, minmax(0, 1fr)); }
                        }
                    </style>

                    <div class="wpc-use-cases-grid <?php echo esc_attr($loop_cat_slug); ?>" style="display: grid; gap: 1.5rem;">
            
                        <?php foreach ( $use_cases as $case ) : 
                            $name = isset( $case['name'] ) ? esc_html( $case['name'] ) : '';
                            $desc = isset( $case['desc'] ) ? esc_html( $case['desc'] ) : '';
                            $icon = isset( $case['icon'] ) ? esc_attr( $case['icon'] ) : '';
                            $image = isset( $case['image'] ) ? esc_url( $case['image'] ) : '';
                            $icon_color = ! empty( $case['icon_color'] ) ? esc_attr( $case['icon_color'] ) : $global_icon_color;
                        ?>
                            <div class="wpc-use-case-card" style="
                                background: hsl(var(--card));
                                border-radius: 0.75rem;
                                border: 1px solid hsl(var(--border));
                                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                                padding: 1.5rem;
                                display: flex;
                                flex-direction: column;
                                align-items: center;
                                text-align: center;
                                transition: box-shadow 0.2s ease;
                            "
                            onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'"
                            onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.05)'"
                            >
                                <!-- Icon/Image -->
                                <div style="
                                    margin-bottom: 1rem;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    width: 4rem;
                                    height: 4rem;
                                    border-radius: 50%;
                                    background: <?php echo esc_attr( $icon_color ); ?>15;
                                ">
                                    <?php if ( ! empty( $image ) ) : ?>
                                        <img src="<?php echo $image; ?>" alt="<?php echo $name; ?>" style="width: 2.5rem; height: 2.5rem; object-fit: contain;" />
                                    <?php elseif ( ! empty( $icon ) ) : ?>
                                        <i class="<?php echo $icon; ?>" style="font-size: 1.5rem; color: <?php echo $icon_color; ?>;"></i>
                                    <?php else : ?>
                                        <span style="font-size: 1.5rem;">✨</span>
                                    <?php endif; ?>
                                </div>
            
                                <!-- Content -->
                                <h3 class="wpc-heading" style="font-weight: 700; margin: 0 0 0.5rem 0; font-size: 1.1em; color: hsl(var(--foreground));"><?php echo $name; ?></h3>
                                <p class="wpc-text-muted" style="line-height: 1.5; margin: 0; color: hsl(var(--muted-foreground));"><?php echo $desc; ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'wpc_use_cases', 'wpc_use_cases_shortcode' );
