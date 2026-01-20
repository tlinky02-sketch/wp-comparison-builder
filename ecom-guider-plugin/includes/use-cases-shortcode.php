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

    // Product Variants Context
    $category_slug = ! empty( $atts['category'] ) ? sanitize_text_field( $atts['category'] ) : '';
    $variants_enabled = get_post_meta( $post_id, '_wpc_variants_enabled', true ) === '1';

    $use_cases = get_post_meta( $post_id, '_wpc_use_cases', true );
    if ( empty( $use_cases ) || ! is_array( $use_cases ) ) {
        return '';
    }
    
    // Filter Use Cases by Category
    if ( $variants_enabled && ! empty( $category_slug ) ) {
        $uc_by_cat = get_post_meta( $post_id, '_wpc_use_cases_by_category', true );
        if ( ! empty( $uc_by_cat ) && isset( $uc_by_cat[ $category_slug ] ) ) {
            $allowed_indices = $uc_by_cat[ $category_slug ];
            $filtered_uc = array();
            foreach ( $use_cases as $idx => $uc ) {
                if ( in_array( $idx, $allowed_indices ) ) {
                    $filtered_uc[] = $uc; // Re-index for display
                }
            }
            $use_cases = $filtered_uc;
        }
    }

    // Enqueue only styles (no React!)
    wp_enqueue_style( 'wpc-styles' );
    wp_enqueue_style( 'fontawesome' ); // FontAwesome for icons

    // Get global colors for fallback
    $global_primary = get_option( 'wpc_primary_color', '#6366f1' );
    $global_icon_color = get_option( 'wpc_usecase_icon_color', $global_primary );
    $border_color = get_option( 'wpc_border_color', '#e2e8f0' );

    $columns = intval( $atts['columns'] );
    $count = count( $use_cases );
    $title = esc_html( $atts['title'] );

    // Determine responsive grid classes
    $grid_cols = 'grid-cols-1';
    if ( $count >= 4 ) {
        $grid_cols .= ' sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4';
    } elseif ( $count === 3 ) {
        $grid_cols .= ' sm:grid-cols-2 md:grid-cols-3';
    } elseif ( $count === 2 ) {
        $grid_cols .= ' sm:grid-cols-2';
    }

    ob_start();
    ?>
    <div class="wpc-use-cases" style="width: 100%;">
        <?php if ( ! empty( $title ) ) : ?>
            <h2 class="wpc-heading" style="font-weight: 700; margin-bottom: 1.5rem; text-align: center;"><?php echo $title; ?></h2>
        <?php endif; ?>

        <div class="wpc-use-cases-grid" style="display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: 1.5rem;">
            <style>
                @media (min-width: 640px) {
                    .wpc-use-cases-grid { grid-template-columns: repeat(<?php echo min($count, 2); ?>, minmax(0, 1fr)) !important; }
                }
                @media (min-width: 768px) {
                    .wpc-use-cases-grid { grid-template-columns: repeat(<?php echo min($count, 3); ?>, minmax(0, 1fr)) !important; }
                }
                @media (min-width: 1024px) {
                    .wpc-use-cases-grid { grid-template-columns: repeat(<?php echo min($count, $columns); ?>, minmax(0, 1fr)) !important; }
                }
                .wpc-use-case-card { transition: box-shadow 0.2s ease; }
                .wpc-use-case-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
            </style>

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
                ">
                    <!-- Icon or Image -->
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
                    <h3 class="wpc-heading" style="font-weight: 700; margin: 0 0 0.5rem 0;"><?php echo $name; ?></h3>
                    <p class="wpc-text-muted" style="line-height: 1.5; margin: 0;"><?php echo $desc; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'wpc_use_cases', 'wpc_use_cases_shortcode' );
