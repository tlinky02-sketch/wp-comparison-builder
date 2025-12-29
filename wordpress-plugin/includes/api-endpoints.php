<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register API Routes
 */
function hosting_guider_register_api() {
    register_rest_route( 'hosting-guider/v1', '/providers', array(
        'methods'  => 'GET',
        'callback' => 'hosting_guider_get_providers',
        'permission_callback' => '__return_true', // Public endpoint
    ));
}
add_action( 'rest_api_init', 'hosting_guider_register_api' );

/**
 * Get Providers and Filters
 */
function hosting_guider_get_providers() {
    $providers = array();

    // Query all providers
    $args = array(
        'post_type'      => 'hosting_provider',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    );
    $query = new WP_Query( $args );

    $all_types = array();
    $all_features = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $id = get_the_ID();

            // Meta
            $price = get_post_meta( $id, '_hosting_price', true ) ?: '0';
            $rating = get_post_meta( $id, '_hosting_rating', true ) ?: 4.0;
            $pros_raw = get_post_meta( $id, '_hosting_pros', true );
            $cons_raw = get_post_meta( $id, '_hosting_cons', true );
            
            // Taxonomies
            $types = get_the_terms( $id, 'hosting_type' );
            $type_names = array();
            if ( $types && ! is_wp_error( $types ) ) {
                foreach ( $types as $t ) {
                    $type_names[] = $t->name;
                    if (!in_array($t->name, $all_types)) $all_types[] = $t->name;
                }
            }

            $features = get_the_terms( $id, 'hosting_feature' );
            $feature_map = array(
                'storage' => 'N/A', // Defaults
                'bandwidth' => 'N/A',
                'ssl' => false,
                'support' => 'N/A',
                'uptime' => 'N/A',
                'domains' => '0',
                'email' => '0',
                'backups' => false
            );
            
            // For the purpose of "Comparison", we map taxonomy terms to the 'features' object if possible,
            // OR we just use them for filtering.
            // The existing React app expects a specific 'features' object structure.
            // To make this dynamic, we might need to adjust the React app OR match terms to these keys.
            // For simplicity in this "Initial" version:
            // We will map SPECIFIC terms to keys if they exist, otherwise pass generic features.
            
            // But wait, the React app filters based on `provider.features.ssl` boolean etc.
            // Let's iterate terms and fill the object.
            
            $feature_names = array();
            if ( $features && ! is_wp_error( $features ) ) {
                foreach ( $features as $f ) {
                    $name = $f->name;
                    $feature_names[] = $name; // Collect raw names
                    $name_lower = strtolower($name);
                    
                    if (!in_array($name, $all_features)) $all_features[] = $name;

                    // Naive mapping for demo purposes
                    if ( strpos( $name_lower, 'ssl' ) !== false ) $feature_map['ssl'] = true;
                    if ( strpos( $name_lower, 'backup' ) !== false ) $feature_map['backups'] = true;
                    if ( strpos( $name_lower, 'support' ) !== false ) $feature_map['support'] = $name;
                    
                    // Strip redundant words for Card Display
                    if ( strpos( $name_lower, 'storage' ) !== false ) {
                        $feature_map['storage'] = trim(str_ireplace('storage', '', $name));
                    }
                    if ( strpos( $name_lower, 'band' ) !== false ) {
                        $feature_map['bandwidth'] = trim(str_ireplace(array('bandwidth', 'transfer'), '', $name));
                    }
                    if ( strpos( $name_lower, 'uptime' ) !== false ) {
                        $feature_map['uptime'] = trim(str_ireplace(array('uptime', 'guarantee'), '', $name));
                    }
                    
                    // Domain
                    if ( strpos( $name_lower, 'domain' ) !== false && strpos( $name_lower, 'free' ) !== false ) {
                        $feature_map['domains'] = 'Free';
                    }
                    
                    // Email
                    if ( strpos( $name_lower, 'email' ) !== false ) {
                        $feature_map['email'] = $name;
                    }
                }
            }

            // Image
            $logo_url = get_the_post_thumbnail_url( $id, 'full' );
            if ( ! $logo_url ) {
                $logo_url = get_post_meta( $id, '_hosting_external_logo_url', true ) ?: '';
            }

            // Details Link 
            $details_link = get_post_meta( $id, '_hosting_details_link', true ) ?: '#';
            
            // Pricing Plans
            $pricing_plans = get_post_meta( $id, '_hosting_pricing_plans', true ) ?: array();
            $hide_plan_features = get_post_meta( $id, '_hosting_hide_plan_features', true ) === '1';
            $hide_plan_action = get_post_meta( $id, '_hosting_hide_plan_action', true ) === '1';

            $providers[] = array(
                'id'       => (string) $id,
                'name'     => get_the_title(),
                'logo'     => $logo_url,
                'rating'   => (float) $rating,
                'category' => $type_names,
                'price'    => $price,
                'features' => $feature_map,
                'pricing_plans' => $pricing_plans,
                'hide_plan_features' => $hide_plan_features, // New field
                'hide_plan_action' => $hide_plan_action,     // New field
                'pros'     => $pros_raw ? explode( "\n", $pros_raw ) : array(),
                'cons'     => $cons_raw ? explode( "\n", $cons_raw ) : array(),
                'raw_features' => $feature_names,
                'details_link' => $details_link,
                'button_text' => get_post_meta( $id, '_hosting_button_text', true ) ?: 'Visit Website',
                'featured_badge_text' => get_post_meta( $id, '_hosting_featured_badge_text', true ) ?: '',
                'featured_badge_color' => get_post_meta( $id, '_hosting_featured_badge_color', true ) ?: '#f59e0b'
            );
        }
        wp_reset_postdata();
    }

    // Get ALL available terms for the filter list, not just used ones
    $all_types_terms = get_terms( array( 'taxonomy' => 'hosting_type', 'hide_empty' => false ) );
    $all_features_terms = get_terms( array( 'taxonomy' => 'hosting_feature', 'hide_empty' => false ) );

    $all_types_names = ! is_wp_error($all_types_terms) ? wp_list_pluck($all_types_terms, 'name') : [];
    $all_features_names = ! is_wp_error($all_features_terms) ? wp_list_pluck($all_features_terms, 'name') : [];

    return array(
        'providers' => $providers,
        'categories' => $all_types_names,
        'filterableFeatures' => $all_features_names 
    );
}
