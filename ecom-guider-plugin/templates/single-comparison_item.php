<?php
/**
 * Template for Single Item Landing Page
 * Shows hero section (auto-generated) + WordPress content editor
 */

get_header();

while ( have_posts() ) : the_post();
    $item_id = get_the_ID();
    
    // Get item meta data
    $price = get_post_meta( $item_id, '_wpc_price', true );
    $rating = get_post_meta( $item_id, '_wpc_rating', true );
    $logo = get_the_post_thumbnail_url( $item_id, 'full' );
    if ( ! $logo ) {
        $logo = get_post_meta( $item_id, '_wpc_external_logo_url', true );
    }
    
    $details_link = get_post_meta( $item_id, '_wpc_details_link', true );
    $dashboard_image = get_post_meta( $item_id, '_wpc_dashboard_image', true );
?>

<div class="wpc-landing-page wpc-typography-inherit">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 60px 20px;">
        
        <!-- ========== AUTO-GENERATED HERO SECTION (FROM META FIELDS) ========== -->
        
        <!-- Breadcrumbs -->
        <div class="breadcrumbs wpc-text-muted" style="margin-bottom: 40px;">
            <a href="<?php echo home_url(); ?>" class="wpc-link"><?php _e('Home', 'wp-comparison-builder'); ?></a>
            <span> / </span>
            <a href="<?php echo get_post_type_archive_link( 'comparison_item' ); ?>" class="wpc-link"><?php _e('Review', 'wp-comparison-builder'); ?></a>
            <span> / </span>
            <span class="wpc-text-body" style="font-weight: 600;"><?php the_title(); ?></span>
        </div>

        <!-- Hero Grid -->
        <div class="hero-section" style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-bottom: 60px; align-items: start;">
            
            <!-- Left: Info -->
            <div class="hero-content">
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px;">
                    <div style="width: 80px; height: 80px; background: hsl(var(--card)); border-radius: 16px; padding: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center; border: 1px solid hsl(var(--border));">
                        <?php if ( $logo ) : ?>
                            <img src="<?php echo esc_url( $logo ); ?>" alt="<?php the_title(); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                        <?php endif; ?>
                    </div>
                </div>

                <h1 class="wpc-heading text-foreground" style="margin-bottom: 20px; line-height: 1.2; font-size: var(--wpc-font-size-h1, 2.5rem);">
                    <?php the_title(); ?>
                </h1>

                <p class="wpc-text-muted" style="margin-bottom: 30px; line-height: 1.6; font-size: var(--wpc-font-size-body, 1.125rem);">
                    <?php echo has_excerpt() ? get_the_excerpt() : __('In-depth review and details', 'wp-comparison-builder'); ?>
                </p>

                <?php if ( $rating ) : ?>
                <!-- Rating Badge - Using theme variables with fallback -->
                <div class="wpc-rating-badge" style="display: inline-flex; align-items: center; gap: 10px; padding: 12px 20px; background: hsl(var(--muted)); border: 1px solid hsl(var(--border)); border-radius: 8px; margin-bottom: 30px;">
                    <div style="display: flex; gap: 2px;">
                        <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                            <span class="wpc-star" style="color: <?php echo $i <= round($rating) ? 'var(--wpc-star-color, #FBBF24)' : 'hsl(var(--muted-foreground))'; ?>;">&#9733;</span>
                        <?php endfor; ?>
                    </div>
                    <span class="wpc-text-body" style="font-weight: 700; font-size: var(--wpc-font-size-h3, 1.2rem);"><?php echo esc_html( $rating ); ?>/5</span>
                    <span class="wpc-text-muted" style="font-size: var(--wpc-font-size-small, 0.875rem);"><?php _e('(Based on our analysis)', 'wp-comparison-builder'); ?></span>
                </div>
                <?php endif; ?>

                <!-- Buttons -->
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <?php if ( $details_link ) : ?>
                    <a href="<?php echo esc_url( $details_link ); ?>" target="_blank" 
                       style="display: inline-flex; align-items: center; gap: 8px; padding: 14px 32px; background: hsl(var(--primary)); color: var(--wpc-btn-text, #fff); text-decoration: none; border-radius: 8px; font-weight: 600; font-size: var(--wpc-font-size-btn, 16px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);">
                        <?php printf( __('Visit %s', 'wp-comparison-builder'), get_the_title() ); ?>
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/>
                        </svg>
                    </a>
                    <?php endif; ?>

                    <!-- Compare Dropdown via Shortcode -->
                    <?php echo do_shortcode('[wpc_compare_button id="' . $item_id . '"]'); ?>
                </div>
            </div>

            <!-- Right: Dashboard -->
            <div class="hero-image">
                <div style="border-radius: 16px; overflow: hidden; border: 1px solid hsl(var(--border)); box-shadow: 0 20px 60px rgba(0,0,0,0.15); background: hsl(var(--card));">
                    <div style="background: hsl(var(--muted)); padding: 12px 16px; border-bottom: 1px solid hsl(var(--border)); display: flex; align-items: center; gap: 8px;">
                        <div style="display: flex; gap: 6px;">
                            <div style="width: 10px; height: 10px; border-radius: 50%; background: #EF4444;"></div>
                            <div style="width: 10px; height: 10px; border-radius: 50%; background: #FBBF24;"></div>
                            <div style="width: 10px; height: 10px; border-radius: 50%; background: #10B981;"></div>
                        </div>
                    </div>
                    <?php if ( $dashboard_image ) : ?>
                        <img src="<?php echo esc_url( $dashboard_image ); ?>" alt="Dashboard" style="width: 100%; height: auto;">
                    <?php else : ?>
                        <div style="aspect-ratio: 16/10; background: hsl(var(--muted)); display: flex; align-items: center; justify-content: center; color: hsl(var(--muted-foreground));">
                            <p style="font-weight: 600;"><?php _e('Dashboard Preview', 'wp-comparison-builder'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ========== EDITABLE WORDPRESS CONTENT (FROM EDITOR) ========== -->
        
        <?php if ( get_the_content() ) : ?>
        <div class="provider-custom-content" style="max-width: 1000px; margin: 0 auto;">
            <div class="entry-content prose wpc-text-body" style="line-height: 1.8;">
                <?php the_content(); ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<style>
@media (max-width: 768px) {
    .hero-section { grid-template-columns: 1fr !important; gap: 40px !important; }
    h1 { font-size: 2rem !important; } /* Use relative units */
}

/* WordPress Content Styling - inheriting from theme */
.prose h2 { font-size: 1.75rem; font-weight: 700; margin: 2em 0 1em; color: var(--foreground); }
.prose h3 { font-size: 1.5rem; font-weight: 600; margin: 1.5em 0 0.75em; color: var(--foreground); }
.prose p { margin-bottom: 1.5em; line-height: 1.8; color: var(--muted-foreground); }
.prose ul, .prose ol { margin: 1.5em 0; padding-left: 2em; color: var(--muted-foreground); }
.prose li { margin-bottom: 0.5em; }
.prose img { max-width: 100%; height: auto; border-radius: 8px; margin: 2em 0; }
.prose a { color: var(--primary, #0d9488); text-decoration: underline; }
.prose blockquote { border-left: 4px solid var(--border, #e5e7eb); padding-left: 1.5em; margin: 2em 0; font-style: italic; color: var(--muted-foreground, #666); }
</style>

<?php
endwhile;

get_footer();
