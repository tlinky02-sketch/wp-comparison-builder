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

<div class="wpc-landing-page">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 60px 20px;">
        
        <!-- ========== AUTO-GENERATED HERO SECTION (FROM META FIELDS) ========== -->
        
        <!-- Breadcrumbs -->
        <div class="breadcrumbs" style="margin-bottom: 40px; color: #666; font-size: 14px;">
            <a href="<?php echo home_url(); ?>" style="color: #666;"><?php _e('Home', 'wp-comparison-builder'); ?></a>
            <span> / </span>
            <a href="<?php echo get_post_type_archive_link( 'comparison_item' ); ?>" style="color: #666;"><?php _e('Review', 'wp-comparison-builder'); ?></a>
            <span> / </span>
            <span style="color: #333; font-weight: 600;"><?php the_title(); ?></span>
        </div>

        <!-- Hero Grid -->
        <div class="hero-section" style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-bottom: 60px; align-items: start;">
            
            <!-- Left: Info -->
            <div class="hero-content">
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px;">
                    <div style="width: 80px; height: 80px; background: white; border-radius: 16px; padding: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center;">
                        <?php if ( $logo ) : ?>
                            <img src="<?php echo esc_url( $logo ); ?>" alt="<?php the_title(); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                        <?php endif; ?>
                    </div>
                </div>

                <h1 style="font-size: 48px; font-weight: 700; margin-bottom: 20px; line-height: 1.2;">
                    <?php the_title(); ?>
                </h1>

                <p style="font-size: 20px; color: #666; margin-bottom: 30px; line-height: 1.6;">
                    <?php echo has_excerpt() ? get_the_excerpt() : __('In-depth review and details', 'wp-comparison-builder'); ?>
                </p>

                <?php if ( $rating ) : ?>
                <div style="display: inline-flex; align-items: center; gap: 10px; padding: 12px 20px; background: #FEF3C7; border: 1px solid #FDE68A; border-radius: 8px; margin-bottom: 30px;">
                    <div style="display: flex; gap: 2px;">
                        <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                            <span style="color: <?php echo $i <= round($rating) ? '#FBBF24' : '#D1D5DB'; ?>; font-size: 20px;">★</span>
                        <?php endfor; ?>
                    </div>
                    <span style="font-weight: 700; color: #333;"><?php echo esc_html( $rating ); ?>/5</span>
                    <span style="font-size: 14px; color: #666;"><?php _e('(Based on our analysis)', 'wp-comparison-builder'); ?></span>
                </div>
                <?php endif; ?>

                <!-- Buttons -->
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <?php if ( $details_link ) : ?>
                    <a href="<?php echo esc_url( $details_link ); ?>" target="_blank" 
                       style="display: inline-flex; align-items: center; gap: 8px; padding: 14px 32px; background: #0d9488; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);">
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
                <div style="border-radius: 16px; overflow: hidden; border: 1px solid #e5e7eb; box-shadow: 0 20px 60px rgba(0,0,0,0.15); background: white;">
                    <div style="background: #f3f4f6; padding: 12px 16px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 8px;">
                        <div style="display: flex; gap: 6px;">
                            <div style="width: 10px; height: 10px; border-radius: 50%; background: #EF4444;"></div>
                            <div style="width: 10px; height: 10px; border-radius: 50%; background: #FBBF24;"></div>
                            <div style="width: 10px; height: 10px; border-radius: 50%; background: #10B981;"></div>
                        </div>
                    </div>
                    <?php if ( $dashboard_image ) : ?>
                        <img src="<?php echo esc_url( $dashboard_image ); ?>" alt="Dashboard" style="width: 100%; height: auto;">
                    <?php else : ?>
                        <div style="aspect-ratio: 16/10; background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #9ca3af;">
                            <p style="font-weight: 600;"><?php _e('Dashboard Preview', 'wp-comparison-builder'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ========== EDITABLE WORDPRESS CONTENT (FROM EDITOR) ========== -->
        
        <?php if ( get_the_content() ) : ?>
        <div class="provider-custom-content" style="max-width: 1000px; margin: 0 auto;">
            <div class="entry-content prose" style="font-size: 16px; line-height: 1.8; color: #333;">
                <?php the_content(); ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<style>
@media (max-width: 768px) {
    .hero-section { grid-template-columns: 1fr !important; gap: 40px !important; }
    h1 { font-size: 32px !important; }
}

/* WordPress Content Styling */
.prose h2 { font-size: 28px; font-weight: 700; margin: 2em 0 1em; }
.prose h3 { font-size: 22px; font-weight: 600; margin: 1.5em 0 0.75em; }
.prose p { margin-bottom: 1.5em; line-height: 1.8; }
.prose ul, .prose ol { margin: 1.5em 0; padding-left: 2em; }
.prose li { margin-bottom: 0.5em; }
.prose img { max-width: 100%; height: auto; border-radius: 8px; margin: 2em 0; }
.prose a { color: #0d9488; text-decoration: underline; }
.prose blockquote { border-left: 4px solid #e5e7eb; padding-left: 1.5em; margin: 2em 0; font-style: italic; color: #666; }
</style>

<?php
endwhile;

get_footer();
