<?php
/**
 * Schema Builder for Comparison Items
 * Generates JSON-LD structured data for SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get schema settings
 */
function wpc_get_schema_settings() {
    $defaults = array(
        'enabled' => '1',
        'product_type' => 'Product',
        'currency' => 'USD',
        'include_rating' => '1',
        'include_offers' => '1',
        'include_pros_cons' => '1',
    );
    
    $settings = get_option( 'wpc_schema_settings', array() );
    return wp_parse_args( $settings, $defaults );
}

/**
 * Generate schema for a single comparison item
 */
function wpc_generate_item_schema( $post_id, $include_wrapper = true ) {
    $settings = wpc_get_schema_settings();
    
    if ( $settings['enabled'] !== '1' ) {
        return '';
    }
    
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'comparison_item' ) {
        return '';
    }
    
    // Get item data
    $title = $post->post_title;
    $description = get_post_meta( $post_id, '_wpc_short_description', true );
    $rating = get_post_meta( $post_id, '_wpc_rating', true );
    $price = get_post_meta( $post_id, '_wpc_price', true );
    $price_period = get_post_meta( $post_id, '_wpc_price_period', true );
    $website_url = get_post_meta( $post_id, '_wpc_website_url', true );
    $pros = get_post_meta( $post_id, '_wpc_pros', true );
    $cons = get_post_meta( $post_id, '_wpc_cons', true );
    $pricing_plans = get_post_meta( $post_id, '_wpc_pricing_plans', true );
    
    // Get logo
    $logo_url = get_the_post_thumbnail_url( $post_id, 'full' );
    if ( ! $logo_url ) {
        $logo_url = get_post_meta( $post_id, '_wpc_external_logo_url', true );
    }
    
    // Build schema
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => $settings['product_type'],
        'name' => $title,
        'description' => $description ?: $title,
    );
    
    // Add image
    if ( $logo_url ) {
        $schema['image'] = $logo_url;
    }
    
    // Add URL
    if ( $website_url ) {
        $schema['url'] = $website_url;
    }
    
    // Add rating
    if ( $settings['include_rating'] === '1' && $rating ) {
        $schema['aggregateRating'] = array(
            '@type' => 'AggregateRating',
            'ratingValue' => floatval( $rating ),
            'bestRating' => 5,
            'worstRating' => 1,
            'ratingCount' => 1,
        );
    }
    
    // Add offers (pricing plans or single price)
    if ( $settings['include_offers'] === '1' ) {
        $offers = array();
        
        if ( ! empty( $pricing_plans ) && is_array( $pricing_plans ) ) {
            foreach ( $pricing_plans as $plan ) {
                $plan_price = isset( $plan['price'] ) ? preg_replace( '/[^0-9.]/', '', $plan['price'] ) : '';
                if ( $plan_price ) {
                    $offer = array(
                        '@type' => 'Offer',
                        'name' => isset( $plan['name'] ) ? $plan['name'] : '',
                        'price' => floatval( $plan_price ),
                        'priceCurrency' => $settings['currency'],
                    );
                    
                    if ( isset( $plan['link'] ) && $plan['link'] ) {
                        $offer['url'] = $plan['link'];
                    }
                    
                    $offers[] = $offer;
                }
            }
        } elseif ( $price ) {
            $clean_price = preg_replace( '/[^0-9.]/', '', $price );
            if ( $clean_price ) {
                $offers[] = array(
                    '@type' => 'Offer',
                    'price' => floatval( $clean_price ),
                    'priceCurrency' => $settings['currency'],
                );
            }
        }
        
        if ( ! empty( $offers ) ) {
            if ( count( $offers ) === 1 ) {
                $schema['offers'] = $offers[0];
            } else {
                $schema['offers'] = $offers;
            }
        }
    }
    
    // Add pros/cons as review
    if ( $settings['include_pros_cons'] === '1' && ( $pros || $cons ) ) {
        $review = array(
            '@type' => 'Review',
            'author' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo( 'name' ),
            ),
        );
        
        if ( $pros ) {
            $pros_array = is_array( $pros ) ? $pros : array_filter( explode( "\n", $pros ) );
            if ( ! empty( $pros_array ) ) {
                $review['positiveNotes'] = array(
                    '@type' => 'ItemList',
                    'itemListElement' => array_map( function( $item, $index ) {
                        return array(
                            '@type' => 'ListItem',
                            'position' => $index + 1,
                            'name' => trim( $item ),
                        );
                    }, $pros_array, array_keys( $pros_array ) ),
                );
            }
        }
        
        if ( $cons ) {
            $cons_array = is_array( $cons ) ? $cons : array_filter( explode( "\n", $cons ) );
            if ( ! empty( $cons_array ) ) {
                $review['negativeNotes'] = array(
                    '@type' => 'ItemList',
                    'itemListElement' => array_map( function( $item, $index ) {
                        return array(
                            '@type' => 'ListItem',
                            'position' => $index + 1,
                            'name' => trim( $item ),
                        );
                    }, $cons_array, array_keys( $cons_array ) ),
                );
            }
        }
        
        $schema['review'] = $review;
    }
    
    // Add categories as additionalType
    $categories = wp_get_post_terms( $post_id, 'comparison_category', array( 'fields' => 'names' ) );
    if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
        $schema['category'] = implode( ', ', $categories );
    }
    
    if ( $include_wrapper ) {
        return '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>';
    }
    
    return $schema;
}

/**
 * Generate schema for a custom list (ItemList with multiple products)
 */
function wpc_generate_list_schema( $list_id ) {
    $settings = wpc_get_schema_settings();
    
    if ( $settings['enabled'] !== '1' ) {
        return '';
    }
    
    $list = get_post( $list_id );
    if ( ! $list || $list->post_type !== 'comparison_list' ) {
        return '';
    }
    
    $list_items = get_post_meta( $list_id, '_wpc_list_items', true );
    if ( empty( $list_items ) || ! is_array( $list_items ) ) {
        return '';
    }
    
    $item_list_elements = array();
    $position = 1;
    
    foreach ( $list_items as $list_item ) {
        $item_id = isset( $list_item['id'] ) ? intval( $list_item['id'] ) : 0;
        if ( ! $item_id ) continue;
        
        $item_schema = wpc_generate_item_schema( $item_id, false );
        if ( ! empty( $item_schema ) ) {
            $item_list_elements[] = array(
                '@type' => 'ListItem',
                'position' => $position,
                'item' => $item_schema,
            );
            $position++;
        }
    }
    
    if ( empty( $item_list_elements ) ) {
        return '';
    }
    
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => $list->post_title,
        'description' => get_post_meta( $list_id, '_wpc_list_description', true ) ?: $list->post_title,
        'numberOfItems' => count( $item_list_elements ),
        'itemListElement' => $item_list_elements,
    );
    
    return '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>';
}

/**
 * Generate schema for pricing table (single product with multiple offers)
 */
function wpc_generate_pricing_schema( $post_id ) {
    return wpc_generate_item_schema( $post_id, true );
}

/**
 * Get schema as formatted JSON string (for preview)
 */
function wpc_get_schema_preview( $post_id ) {
    $schema = wpc_generate_item_schema( $post_id, false );
    if ( empty( $schema ) ) {
        return '// Schema generation disabled or no valid data';
    }
    return wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
}

/**
 * Register schema settings
 */
function wpc_register_schema_settings() {
    register_setting( 'wpc_schema_group', 'wpc_schema_settings' );
}
add_action( 'admin_init', 'wpc_register_schema_settings' );
