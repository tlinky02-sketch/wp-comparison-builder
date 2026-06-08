<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create Sample Data for Demo
 */
function wpc_create_sample_data() {
    // Check if sample data already exists
    $existing = get_posts( array(
        'post_type' => 'comparison_item',
        'posts_per_page' => 1,
        'meta_query' => array(
            array(
                'key' => '_is_sample_data',
                'value' => '1'
            )
        )
    ));
    
    if ( ! empty( $existing ) ) {
        return; // Sample data already exists
    }

    // Create taxonomies first
    $categories = array(
        'Category A' => 'Sample category description',
        'Category B' => 'Another sample category',
        'Category C' => 'Third sample category'
    );
    
    foreach ( $categories as $name => $desc ) {
        if ( ! term_exists( $name, 'comparison_category' ) ) {
            wp_insert_term( $name, 'comparison_category', array( 'description' => $desc ) );
        }
    }
    
    $features = array(
        'Feature One', 'Feature Two', 'Feature Three', 'Analytics', 
        'Mobile App', '24/7 Support', 'API Access', 'Custom Domain'
    );
    
    foreach ( $features as $feature ) {
        if ( ! term_exists( $feature, 'comparison_feature' ) ) {
            wp_insert_term( $feature, 'comparison_feature' );
        }
    }

    // Define generic sample items
    $items = array(
        array(
            'title' => 'Sample Item Alpha',
            'excerpt' => 'This is a sample item description. You can replace this with your own product, service, or software details.',
            'price' => '$29/mo',
            'rating' => 4.8,
            'category' => 'Category A',
            'features' => array('Feature One', 'Feature Two', 'Analytics', 'Mobile App'),
            'logo' => '', // No external logo by default
            'details_link' => 'https://example.com',
            'button_text' => 'Get Started',
            'coupon_code' => 'SAVE20',
            'pros' => "Easy to use\nGreat features\nExcellent support\nReliable performance",
            'cons' => "Monthly fees\nLimited customization\nLearning curve",
            'plans' => array(
                array(
                    'name' => 'Basic',
                    'price' => '$29',
                    'period' => '/mo',
                    'features' => "Basic features\n1 User\nStandard support",
                    'link' => '#'
                ),
                array(
                    'name' => 'Pro',
                    'price' => '$79',
                    'period' => '/mo',
                    'features' => "Everything in Basic\n5 Users\nPriority support",
                    'link' => '#'
                )
            )
        ),
        array(
            'title' => 'Sample Item Beta',
            'excerpt' => 'Another sample item for comparison. Great for demonstrating differences in features and pricing.',
            'price' => 'Free',
            'rating' => 4.5,
            'category' => 'Category B',
            'features' => array('Feature One', 'API Access', 'Custom Domain'),
            'logo' => '',
            'details_link' => 'https://example.com',
            'button_text' => 'Download Free',
            'pros' => "Completely free\nOpen source\nFlexible\nCommunity support",
            'cons' => "Requires setup\nManual updates\nNo official support",
            'plans' => array(
                array(
                    'name' => 'Free Plan',
                    'price' => 'Free',
                    'period' => '',
                    'features' => "Core features\nCommunity forum",
                    'link' => '#'
                ),
                array(
                    'name' => 'Premium',
                    'price' => '$49',
                    'period' => '/year',
                    'features' => "Advanced features\nEmail support",
                    'link' => '#'
                )
            )
        ),
        array(
            'title' => 'Sample Item Gamma',
            'excerpt' => 'A premium solution for enterprise needs. Demonstrates higher pricing and advanced feature sets.',
            'price' => '$99/mo',
            'rating' => 4.7,
            'category' => 'Category A',
            'features' => array('Feature One', 'Feature Two', 'Feature Three', 'Analytics', '24/7 Support', 'API Access'),
            'logo' => '',
            'details_link' => 'https://example.com',
            'button_text' => 'Request Demo',
            'coupon_code' => '',
            'pros' => "Enterprise grade\nUnlimited scale\nDedicated account manager\nSLA Guarantee",
            'cons' => "Expensive\nContract required\nComplex setup",
            'plans' => array(
                array(
                    'name' => 'Business',
                    'price' => '$99',
                    'period' => '/mo',
                    'features' => "All features\nUnlimited users\n24/7 Phone support",
                    'link' => '#'
                )
            )
        )
    );

    // Create items
    foreach ( $items as $item_data ) {
        $post_id = wp_insert_post( array(
            'post_title' => $item_data['title'],
            'post_excerpt' => $item_data['excerpt'],
            'post_status' => 'publish',
            'post_type' => 'comparison_item'
        ));
        
        if ( $post_id ) {
            // Mark as sample data
            update_post_meta( $post_id, '_is_sample_data', '1' );
            
            // Basic meta
            update_post_meta( $post_id, '_wpc_price', $item_data['price'] );
            update_post_meta( $post_id, '_wpc_rating', $item_data['rating'] );
            update_post_meta( $post_id, '_wpc_external_logo_url', $item_data['logo'] );
            update_post_meta( $post_id, '_wpc_details_link', $item_data['details_link'] );
            update_post_meta( $post_id, '_wpc_button_text', $item_data['button_text'] );
            
            if ( ! empty( $item_data['coupon_code'] ) ) {
                update_post_meta( $post_id, '_wpc_coupon_code', $item_data['coupon_code'] );
            }
            
            update_post_meta( $post_id, '_wpc_pros', $item_data['pros'] );
            update_post_meta( $post_id, '_wpc_cons', $item_data['cons'] );
            
            // Pricing plans
            if ( ! empty( $item_data['plans'] ) ) {
                update_post_meta( $post_id, '_wpc_pricing_plans', $item_data['plans'] );
            }
            
            // Assign category
            $cat_term = get_term_by( 'name', $item_data['category'], 'comparison_category' );
            if ( $cat_term ) {
                wp_set_post_terms( $post_id, array( $cat_term->term_id ), 'comparison_category' );
            }
            
            // Assign features
            $feature_ids = array();
            foreach ( $item_data['features'] as $feature_name ) {
                $term = get_term_by( 'name', $feature_name, 'comparison_feature' );
                if ( $term ) {
                    $feature_ids[] = $term->term_id;
                }
            }
            if ( ! empty( $feature_ids ) ) {
                wp_set_post_terms( $post_id, $feature_ids, 'comparison_feature' );
            }
        }
    }
}

/**
 * Create Demo Pages with Shortcodes
 */
function wpc_create_demo_pages() {
    // Check if demo pages already exist
    $existing = get_posts( array(
        'post_type' => 'comparison_review',
        'posts_per_page' => 1,
        'meta_query' => array(
            array(
                'key' => '_wpc_demo_page',
                'value' => '1'
            )
        )
    ));
    
    if ( ! empty( $existing ) ) {
        return; // Demo pages already exist
    }

    // Get IDs of created items
    $items = get_posts( array(
        'post_type' => 'comparison_item',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'orderby' => 'title',
        'order' => 'ASC'
    ));
    
    if ( count( $items ) < 3 ) {
        return; // Not enough items
    }

    // Page 1: Full Comparison Table
    $page1_id = wp_insert_post( array(
        'post_title' => 'Comparison Demo',
        'post_content' => '<h2>Compare Top Items</h2>

<p>Find the perfect solution for your needs. Compare features, pricing, and capabilities side-by-side.</p>

[wpc_compare]

<h3>Why Compare?</h3>
<p>Making an informed decision requires looking at all the specs. Our comparison table makes it easy.</p>',
        'post_status' => 'publish',
        'post_type' => 'comparison_review'
    ));
    update_post_meta( $page1_id, '_wpc_demo_page', '1' );

    // Page 2: Featured Comparison
    $featured_ids = implode( ',', array_slice( $items, 0, 3 ) );
    $page2_id = wp_insert_post( array(
        'post_title' => 'Featured Selection',
        'post_content' => '<h2>Our Top Recommendations</h2>

<p>These are our top picks based on features and value.</p>

[wpc_compare ids="' . $featured_ids . '" featured="' . $featured_ids . '"]

<h3>What Makes These Stand Out?</h3>
<p>We selected these items based on comprehensive analysis.</p>',
        'post_status' => 'publish',
        'post_type' => 'comparison_review'
    ));
    update_post_meta( $page2_id, '_wpc_demo_page', '1' );

    // Page 3: Individual Item Review (Hero Example)
    if ( ! empty( $items[0] ) ) {
        $page3_id = wp_insert_post( array(
            'post_title' => 'Single Item Review',
            'post_content' => '[wpc_hero id="' . $items[0] . '"]

<h2>Deep Dive Review</h2>

<p>In this comprehensive review, we\'ll explore all the features and specifications.</p>

<h3>Key Highlights</h3>
<ul>
<li>Easy to use</li>
<li>Great performance</li>
<li>Excellent support</li>
</ul>

<h3>Compare With Alternatives</h3>
<p>See how this item compares to others:</p>

[wpc_compare limit="4"]',
            'post_status' => 'publish',
            'post_type' => 'comparison_review'
        ));
        update_post_meta( $page3_id, '_wpc_demo_page', '1' );
    }
}

// Hook to admin notice to show activation button
add_action( 'admin_notices', 'wpc_sample_data_notice' );
function wpc_sample_data_notice() {
    // Only show on plugin pages
    $screen = get_current_screen();
    if ( ! $screen || strpos( $screen->id, 'wpc' ) === false && strpos( $screen->id, 'comparison' ) === false ) {
        return;
    }

    // Check if sample data exists
    $has_data = get_posts( array(
        'post_type' => 'comparison_item',
        'posts_per_page' => 1,
        'meta_query' => array(
            array(
                'key' => '_is_sample_data',
                'value' => '1'
            )
        )
    ));

    if ( ! empty( $has_data ) ) {
        return; // Already has sample data
    }

    // Check if dismissed
    if ( get_option( 'wpc_dismiss_sample_notice' ) ) {
        return;
    }

    ?>
    <div class="notice notice-info is-dismissible" id="wpc-sample-notice">
        <p><strong>WP Comparison Builder:</strong> Want to see how it works? <a href="<?php echo admin_url( 'admin.php?page=wpc-settings&create_sample=1' ); ?>" class="button button-primary" style="margin-left: 10px;">Create Sample Data & Demo Pages</a></p>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('#wpc-sample-notice').on('click', '.notice-dismiss', function() {
            $.post(ajaxurl, { action: 'wpc_dismiss_sample_notice' });
        });
    });
    </script>
    <?php
}

// Handle dismiss
add_action( 'wp_ajax_wpc_dismiss_sample_notice', function() {
    update_option( 'wpc_dismiss_sample_notice', '1' );
    wp_die();
});

// Handle sample data creation from URL parameter
add_action( 'admin_init', 'wpc_handle_sample_creation' );
function wpc_handle_sample_creation() {
    if ( ! isset( $_GET['create_sample'] ) || $_GET['create_sample'] != '1' ) {
        return;
    }
    
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    wpc_create_sample_data();
    wpc_create_demo_pages();
    
    wp_redirect( admin_url( 'edit.php?post_type=comparison_item&sample_created=1' ) );
    exit;
}

// Show success notice after creation
add_action( 'admin_notices', function() {
    if ( isset( $_GET['sample_created'] ) && $_GET['sample_created'] == '1' ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Success!</strong> Sample items and demo pages have been created. <a href="<?php echo admin_url( 'edit.php?post_type=comparison_review' ); ?>">View Demo Pages</a></p>
        </div>
        <?php
    }
});
