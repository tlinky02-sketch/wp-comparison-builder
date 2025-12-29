<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Insert Sample Data on Activation
 */

/**
 * Insert Sample Data on Activation
 */
function hosting_guider_insert_sample_data() {
    // We want to FORCE update data to ensure filters/terms are correct
    // even if the user installed an older version.

    $providers = array(
        array(
            'title' => 'Hostinger',
            'price' => '$2.99',
            'rating' => '4.8',
            'types' => array('Web Hosting', 'WordPress', 'Cloud'),
            'features' => array('100GB SSD Storage', 'Unlimited Bandwidth', 'Free SSL', '24/7 Support', '99.9% Uptime', 'Free Domain', 'Daily Backups', 'Email Hosting'),
            'pros' => "Very affordable pricing\nFast loading speeds\nEasy to use hPanel",
            'cons' => "No phone support\nRenewal prices higher\nLimited for enterprise",
            'logo' => 'https://images.unsplash.com/photo-1560472355-536de3962603?w=100&q=80'
        ),
        array(
            'title' => 'Bluehost',
            'price' => '$3.95',
            'rating' => '4.5',
            'types' => array('Web Hosting', 'WordPress', 'VPS'),
            'features' => array('50GB SSD Storage', 'Unlimited Bandwidth', 'Free SSL', '24/7 Support', '99.98% Uptime', 'Free Domain', 'Email Hosting'),
            'pros' => "Official WordPress recommended\nFree domain first year\nExcellent uptime",
            'cons' => "Backups cost extra\nHigher renewal rates\nUpsells during checkout",
            'logo' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=100&q=80'
        ),
        array(
            'title' => 'SiteGround',
            'price' => '$3.99',
            'rating' => '4.7',
            'types' => array('Web Hosting', 'WordPress', 'Cloud'),
            'features' => array('10GB SSD Storage', 'Unlimited Bandwidth', 'Free SSL', '24/7 Support', '99.99% Uptime', 'Daily Backups', 'Email Hosting'),
            'pros' => "Exceptional support\nDaily backups included\nGreat for WordPress",
            'cons' => "Limited storage on basic\nNo free domain\nPrice jumps on renewal",
            'logo' => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?w=100&q=80'
        ),
        array(
            'title' => 'DreamHost',
            'price' => '$2.59',
            'rating' => '4.4',
            'types' => array('Web Hosting', 'VPS', 'Cloud'),
            'features' => array('50GB SSD Storage', 'Unlimited Bandwidth', 'Free SSL', '24/7 Support', '100% Uptime', 'Free Domain', 'Daily Backups'),
            'pros' => "97-day money back guarantee\n100% uptime guarantee\nPrivacy focused",
            'cons' => "No email on basic plan\nNo phone support\nSlower response times",
            'logo' => 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=100&q=80'
        ),
        array(
            'title' => 'A2 Hosting',
            'price' => '$2.99',
            'rating' => '4.6',
            'types' => array('Web Hosting', 'VPS', 'WordPress'),
            'features' => array('100GB SSD Storage', 'Unlimited Bandwidth', 'Free SSL', '24/7 Support', '99.9% Uptime', 'Daily Backups', 'Email Hosting'),
            'pros' => "Turbo servers 20x faster\nFree site migration\nDeveloper friendly",
            'cons' => "No free domain\nTurbo costs more\nComplex pricing tiers",
            'logo' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=100&q=80'
        ),
        array(
            'title' => 'Cloudways',
            'price' => '$14.00',
            'rating' => '4.8',
            'types' => array('Cloud', 'VPS', 'WordPress'),
            'features' => array('25GB SSD Storage', '1TB Bandwidth', 'Free SSL', '24/7 Support', '99.99% Uptime', 'Unlimited Domains', 'Daily Backups'),
            'pros' => "Managed cloud hosting\nMultiple cloud providers\nExcellent performance",
            'cons' => "Higher price point\nNo email hosting\nLearning curve for beginners",
            'logo' => 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=100&q=80'
        ),
        array(
            'title' => 'Kinsta',
            'price' => '$35.00',
            'rating' => '4.9',
            'types' => array('WordPress', 'Cloud'),
            'features' => array('10GB SSD Storage', '50GB Bandwidth', 'Free SSL', '24/7 Support', '99.99% Uptime', 'Free Domain', 'Daily Backups'),
            'pros' => "Premium WordPress hosting\nGoogle Cloud powered\nStaging environments",
            'cons' => "Expensive for beginners\nWordPress only\nLimited bandwidth on basic",
            'logo' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=100&q=80'
        ),
        array(
            'title' => 'WP Engine',
            'price' => '$25.00',
            'rating' => '4.7',
            'types' => array('WordPress', 'Cloud'),
            'features' => array('10GB SSD Storage', '50GB Bandwidth', 'Free SSL', '24/7 Support', '99.99% Uptime', 'Free Domain', 'Daily Backups'),
            'pros' => "Enterprise-grade security\nAutomatic updates\nGreat for agencies",
            'cons' => "Premium pricing\nWordPress only\nNo email included",
            'logo' => 'https://images.unsplash.com/photo-1563986768494-4dee2763ff3f?w=100&q=80'
        ),
        // New Providers for Pagination Test
        array(
            'title' => 'Liquid Web',
            'price' => '$19.00',
            'rating' => '4.8',
            'types' => array('VPS', 'Cloud'),
            'features' => array('40GB SSD Storage', '10TB Bandwidth', 'Free SSL', '24/7 Support', '100% Uptime', 'Daily Backups'),
            'pros' => "High performance VPS\n100% Uptime Guarantee\nHeroic Support",
            'cons' => "No shared hosting\nExpensive for starters\nGeared towards devs",
            'logo' => 'https://images.unsplash.com/photo-1481487484168-9b930d5b019b?w=100&q=80'
        ),
        array(
            'title' => 'InMotion',
            'price' => '$2.29',
            'rating' => '4.5',
            'types' => array('Web Hosting', 'VPS'),
            'features' => array('100GB SSD Storage', 'Unlimited Bandwidth', 'Free SSL', '24/7 Support', '99.9% Uptime', 'Free Domain'),
            'pros' => "90-day money back\nGreat US support\nFast SSDs",
            'cons' => "Verification process slow\nHigh renewal rates\nNo Windows hosting",
            'logo' => 'https://images.unsplash.com/photo-1562577309-4932fdd64cd1?w=100&q=80'
        ),
        array(
            'title' => 'HostGator',
            'price' => '$2.75',
            'rating' => '4.2',
            'types' => array('Web Hosting', 'WordPress'),
            'features' => array('Unmetered Storage', 'Unmetered Bandwidth', 'Free SSL', '24/7 Support', '99.9% Uptime', 'Free Domain'),
            'pros' => "Very cheap starting price\nUnmetered resources\n45-day money back",
            'cons' => "Aggressive upsells\nSlow support sometimes\nRestore fees",
            'logo' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=100&q=80'
        ),
        array(
            'title' => 'GreenGeeks',
            'price' => '$2.95',
            'rating' => '4.6',
            'types' => array('Web Hosting', 'WordPress', 'Green'),
            'features' => array('50GB SSD Storage', 'Unmetered Bandwidth', 'Free SSL', '24/7 Support', '99.9% Uptime', 'Free Domain', 'Daily Backups'),
            'pros' => "Eco-friendly hosting\nLitespeed servers\nFree nightly backups",
            'cons' => "High renewal price\nSetup fee on monthly\nLimited data centers",
            'logo' => 'https://images.unsplash.com/photo-1466611653911-95081537e5b7?w=100&q=80'
        )
    );

    $created_ids = array();

    foreach ( $providers as $p ) {
        // Try to find existing by title
        $existing_post = get_page_by_title( $p['title'], OBJECT, 'hosting_provider' );
        
        if ( $existing_post ) {
            $provider_id = $existing_post->ID;
            // Update existing
             $update_args = array(
                'ID' => $provider_id,
                'post_status' => 'publish'
            );
            wp_update_post( $update_args );
        } else {
            // Create new
            $provider_id = wp_insert_post( array(
                'post_title'  => $p['title'],
                'post_type'   => 'hosting_provider',
                'post_status' => 'publish'
            ));
        }

        if ( $provider_id ) {
            $created_ids[] = $provider_id;
            // Meta
            update_post_meta( $provider_id, '_hosting_price', $p['price'] );
            update_post_meta( $provider_id, '_hosting_rating', $p['rating'] );
            update_post_meta( $provider_id, '_hosting_pros', $p['pros'] );
            update_post_meta( $provider_id, '_hosting_cons', $p['cons'] );
            update_post_meta( $provider_id, '_hosting_details_link', 'https://example.com' );
            update_post_meta( $provider_id, '_hosting_button_text', 'Visit Site' );
            
            // Sample Pricing Plans
            $sample_plans = array(
                array(
                    'name' => 'Starter',
                    'price' => '$2.99',
                    'period' => '/mo',
                    'features' => "1 Website\n10 GB Storage\nFree SSL",
                    'link' => '#'
                ),
                array(
                    'name' => 'Pro',
                    'price' => '$5.99',
                    'period' => '/mo',
                    'features' => "Unlimited Websites\n20 GB Storage\nFree SSL\nFree Domain",
                    'link' => '#'
                ),
                array(
                    'name' => 'Business',
                    'price' => '$9.99',
                    'period' => '/mo',
                    'features' => "Unlimited Everything\nFree SSL\nDedicated IP\nPriority Support",
                    'link' => '#'
                )
            );
            update_post_meta( $provider_id, '_hosting_pricing_plans', $sample_plans );

            // Terms
            wp_set_object_terms( $provider_id, $p['types'], 'hosting_type' );
            wp_set_object_terms( $provider_id, $p['features'], 'hosting_feature' );
            
            // Image
            // We can't upload images programmatically easily without physical files,
            // so we set the external logo URL meta we created earlier
            update_post_meta( $provider_id, '_hosting_external_logo_url', $p['logo'] );
        }
    }

    // Update List
    $list = get_page_by_title( 'Best Hosting Providers 2024', OBJECT, 'hosting_list' );
    if ( $list ) {
        // Update to include ALL ids
        update_post_meta( $list->ID, '_hg_list_ids', $created_ids );
        update_post_meta( $list->ID, '_hg_list_featured', array_slice($created_ids, 0, 3) );
    } else {
        if ( ! empty( $created_ids ) ) {
             $list_id = wp_insert_post( array(
                'post_title'  => 'Best Hosting Providers 2024',
                'post_type'   => 'hosting_list',
                'post_status' => 'publish'
            ));
            if ($list_id) {
                update_post_meta( $list_id, '_hg_list_ids', $created_ids );
                update_post_meta( $list_id, '_hg_list_featured', array_slice($created_ids, 0, 3) );
            }
        }
    }
}

// Hook into admin_init to force check once (or typically activation)
function hosting_guider_check_data() {
    // If we have providers but NO featured terms, something is wrong. Run repair.
    // OR if user manually requests seed
    if ( is_admin() ) {
        if ( isset($_GET['hg_seed']) ) {
             hosting_guider_insert_sample_data();
             add_action('admin_notices', function(){ echo '<div class="notice notice-success"><p>Hosting Data Seeded!</p></div>'; });
        }
        
        $count = wp_count_terms( array( 'taxonomy' => 'hosting_feature', 'hide_empty' => false ) );
        if ( is_wp_error($count) || $count == 0 ) {
            hosting_guider_insert_sample_data();
        }
    }
}
add_action( 'admin_init', 'hosting_guider_check_data' );

register_activation_hook( dirname( dirname( __FILE__ ) ) . '/hosting-guider.php', 'hosting_guider_insert_sample_data' );
