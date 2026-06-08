<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Migrate Legacy Data to New Schema
 */
function wpc_migrate_legacy_data() {
    $force = isset( $_GET['wpc_migrate'] ) && $_GET['wpc_migrate'] === 'true';

    // Check if migration already ran (unless forced)
    if ( get_option( 'wpc_migration_done_v1' ) && ! $force ) {
        return;
    }
    
    // Check if user has permissions
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    global $wpdb;

    // 1. Migrate Post Types
    // Check if any legacy exist before running
    $has_legacy = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ecommerce_provider'" );
    if ( $has_legacy > 0 || $force ) {
        $wpdb->query( "UPDATE {$wpdb->posts} SET post_type = 'comparison_item' WHERE post_type = 'ecommerce_provider'" );
        $wpdb->query( "UPDATE {$wpdb->posts} SET post_type = 'comparison_list' WHERE post_type = 'ecommerce_list'" );
        $wpdb->query( "UPDATE {$wpdb->posts} SET post_type = 'comparison_review' WHERE post_type = 'ecommerce_review'" );

        // 2. Migrate Meta Keys
        $wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_key = REPLACE(meta_key, '_ecommerce_', '_wpc_') WHERE meta_key LIKE '_ecommerce_%'" );
        $wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_key = REPLACE(meta_key, '_hg_', '_wpc_') WHERE meta_key LIKE '_hg_%'" );

        // 3. Migrate Taxonomies
        $wpdb->query( "UPDATE {$wpdb->term_taxonomy} SET taxonomy = 'comparison_category' WHERE taxonomy = 'ecommerce_type'" );
        $wpdb->query( "UPDATE {$wpdb->term_taxonomy} SET taxonomy = 'comparison_feature' WHERE taxonomy = 'ecommerce_feature'" );

        // 4. Mark Migration as Done
        update_option( 'wpc_migration_done_v1', '1' );
        
        // 5. Build Success Notice
        add_action( 'admin_notices', function() {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>WP Comparison Builder:</strong> Data migration successful! Legacy data has been updated to the new format.</p>
            </div>
            <?php
        });
    }
}

// Run on admin init
add_action( 'admin_init', 'wpc_migrate_legacy_data' );
