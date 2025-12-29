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
    
    // New Fields
    $product_category = get_post_meta( $post_id, '_wpc_product_category', true ) ?: 'SoftwareApplication';
    $brand = get_post_meta( $post_id, '_wpc_brand', true );
    $sku = get_post_meta( $post_id, '_wpc_sku', true );
    $gtin = get_post_meta( $post_id, '_wpc_gtin', true );
    $condition = get_post_meta( $post_id, '_wpc_condition', true );
    $availability = get_post_meta( $post_id, '_wpc_availability', true );
    $mfg_date = get_post_meta( $post_id, '_wpc_mfg_date', true );
    $exp_date = get_post_meta( $post_id, '_wpc_exp_date', true );
    $service_type = get_post_meta( $post_id, '_wpc_service_type', true );
    $area_served = get_post_meta( $post_id, '_wpc_area_served', true );
    $duration = get_post_meta( $post_id, '_wpc_duration', true );

    $website_url = get_post_meta( $post_id, '_wpc_website_url', true );
    $pros = get_post_meta( $post_id, '_wpc_pros', true );
    $cons = get_post_meta( $post_id, '_wpc_cons', true );
    $pricing_plans = get_post_meta( $post_id, '_wpc_pricing_plans', true );
    
    // Get logo
    $logo_url = get_the_post_thumbnail_url( $post_id, 'full' );
    if ( ! $logo_url ) {
        $logo_url = get_post_meta( $post_id, '_wpc_external_logo_url', true );
    }
    
    // Build schema base
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => $product_category, // Dynamic Type
        'name' => $title,
        'description' => $description ?: $title,
    );

    // Type-Specific Fields
    if ( $product_category === 'Product' ) {
        if ( $brand ) {
            $schema['brand'] = array( '@type' => 'Brand', 'name' => $brand );
        }
        if ( $sku ) $schema['sku'] = $sku;
        if ( $gtin ) $schema['gtin'] = $gtin; // Or gtin8/12/13/14 detection
        if ( $mfg_date ) $schema['productionDate'] = $mfg_date;
        // Exp date isn't standard on Product, but we can try (or rely on offers)
    } elseif ( $product_category === 'Service' ) {
        if ( $brand ) { // Provider
             $schema['provider'] = array( '@type' => 'Organization', 'name' => $brand );
        }
        if ( $service_type ) $schema['serviceType'] = $service_type;
        if ( $area_served ) $schema['areaServed'] = $area_served;
    } elseif ( $product_category === 'Course' ) {
         if ( $brand ) { // Provider
             $schema['provider'] = array( '@type' => 'Organization', 'name' => $brand );
         }
         if ( $duration ) $schema['timeRequired'] = $duration; // ISO 8601
    } elseif ( $product_category === 'SoftwareApplication' ) {
        $schema['applicationCategory'] = 'BusinessApplication'; // Default
        if ( $brand ) { // Author/Publisher
             $schema['author'] = array( '@type' => 'Organization', 'name' => $brand );
        }
    }
    
    // Add image
    if ( $logo_url ) {
        $schema['image'] = $logo_url;
    } else {
        $placeholder = isset( $settings['placeholder_image'] ) ? $settings['placeholder_image'] : '';
        if ( ! $placeholder ) {
            $custom_logo_id = get_theme_mod( 'custom_logo' );
            if ( $custom_logo_id ) $placeholder = wp_get_attachment_image_url( $custom_logo_id, 'full' );
        }
        if ( $placeholder ) $schema['image'] = $placeholder;
    }
    
    // Add URL
    if ( $website_url ) {
        $schema['url'] = $website_url;
    }
    
    // Add rating
    if ( $settings['include_rating'] === '1' && $rating ) {
        $rating_key = ($product_category === 'SoftwareApplication') ? 'aggregateRating' : 'aggregateRating'; 
        // Note: Service/Course can also use aggregateRating
        
        $schema[$rating_key] = array(
            '@type' => 'AggregateRating',
            'ratingValue' => round( floatval( $rating ), 1 ),
            'bestRating' => 5,
            'worstRating' => 1,
            'ratingCount' => 1,
        );
    }
    
    // Add offers
    if ( $settings['include_offers'] === '1' ) {
        $offers = array();
        
        // Common offer fields
        $offer_base = array(
            '@type' => 'Offer',
            'priceCurrency' => $settings['currency'],
        );
        
        if ( $product_category === 'Product' ) {
             if ( $condition ) $offer_base['itemCondition'] = "https://schema.org/{$condition}";
             if ( $availability ) $offer_base['availability'] = "https://schema.org/{$availability}";
             if ( $exp_date ) $offer_base['priceValidUntil'] = $exp_date; // Best fit
        }
        
        if ( ! empty( $pricing_plans ) && is_array( $pricing_plans ) ) {
            foreach ( $pricing_plans as $plan ) {
                $plan_price = isset( $plan['price'] ) ? preg_replace( '/[^0-9.]/', '', $plan['price'] ) : '';
                if ( $plan_price ) {
                    $offer = $offer_base;
                    $offer['name'] = isset( $plan['name'] ) ? $plan['name'] : '';
                    $offer['price'] = round( floatval( $plan_price ), 2 );
                    
                    if ( isset( $plan['link'] ) && $plan['link'] ) {
                        $offer['url'] = $plan['link'];
                    }
                    $offers[] = $offer;
                }
            }
        } elseif ( $price ) {
            $clean_price = preg_replace( '/[^0-9.]/', '', $price );
            if ( $clean_price ) {
                $offer = $offer_base;
                $offer['price'] = round( floatval( $clean_price ), 2 );
                $offers[] = $offer;
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
    
    // Get list items - try new meta key first
    $list_items = get_post_meta( $list_id, '_wpc_list_items', true );
    
    // Fallback to IDs-based approach
    if ( empty( $list_items ) || ! is_array( $list_items ) ) {
        $ids = get_post_meta( $list_id, '_wpc_list_ids', true );
        if ( empty( $ids ) ) {
            $ids = get_post_meta( $list_id, '_hg_list_ids', true );
        }
        
        if ( ! empty( $ids ) && is_array( $ids ) ) {
            $list_items = array_map( function( $id ) {
                return array( 'id' => $id );
            }, $ids );
        }
    }
    
    if ( empty( $list_items ) || ! is_array( $list_items ) ) {
        return '';
    }
    
    $item_list_elements = array();
    $position = 1;
    
    foreach ( $list_items as $list_item ) {
        $item_id = isset( $list_item['id'] ) ? intval( $list_item['id'] ) : ( is_numeric( $list_item ) ? intval( $list_item ) : 0 );
        if ( ! $item_id ) continue;
        
        $item_schema = wpc_generate_item_schema( $item_id, false );
        if ( ! empty( $item_schema ) && is_array( $item_schema ) ) {
            // Remove @context from nested items
            unset( $item_schema['@context'] );
            
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
 * Output schema on single comparison_item pages
 * 
 * Priority 99 ensures this runs after most SEO plugins (Yoast, Rank Math, All in One SEO etc.)
 * Use filter 'wpc_output_schema' to disable if needed for compatibility
 */
function wpc_output_single_schema() {
    // Allow other plugins to disable schema output
    if ( ! apply_filters( 'wpc_output_schema', true ) ) {
        return;
    }
    
    if ( ! is_singular( 'comparison_item' ) ) {
        return;
    }

    global $post;
    
    $settings = wpc_get_schema_settings();
    if ( $settings['enabled'] !== '1' ) {
        return;
    }
    
    echo wpc_generate_item_schema( $post->ID, true );
}
// Priority 99 ensures compatibility with other SEO plugins (they usually use 10 or lower)
add_action( 'wp_head', 'wpc_output_single_schema', 99 );

/**
 * Register schema settings
 */
function wpc_register_schema_settings() {
    register_setting( 'wpc_schema_group', 'wpc_schema_settings' );
}
add_action( 'admin_init', 'wpc_register_schema_settings' );
