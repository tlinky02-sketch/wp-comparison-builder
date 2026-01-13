<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Migration Admin Page
 */
function wpc_register_migration_page() {
    add_submenu_page(
        'tools.php',
        'WPC Migration',
        'WPC Migration',
        'manage_options',
        'wpc-migration',
        'wpc_render_migration_page'
    );
}
add_action( 'admin_menu', 'wpc_register_migration_page' );

/**
 * Render Page
 */
function wpc_render_migration_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $message = '';
    
    // Handle Migration Trigger
    if ( isset( $_POST['wpc_run_migration'] ) && check_admin_referer( 'wpc_migration_action' ) ) {
        require_once WPC_PLUGIN_DIR . 'includes/class-wpc-migrator.php';
        $migrator = new WPC_Migrator();
        $result = $migrator->run_migration();
        
        if ( $result['success'] ) {
            $message = '<div class="notice notice-success"><p>Migration Complete! Processed ' . $result['count'] . ' items.</p></div>';
            if ( ! empty( $result['errors'] ) ) {
                $message .= '<div class="notice notice-error"><p>Errors encountered:</p><ul>';
                foreach ( $result['errors'] as $err ) {
                    $message .= '<li>' . esc_html( $err ) . '</li>';
                }
                $message .= '</ul></div>';
            }
        }
    }
    
    ?>
    <div class="wrap">
        <h1>WPC Database Migration</h1>
        <?php echo $message; ?>
        
        <div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
            <h2>Migrate to Custom Table</h2>
            <p>This tool will migrate all your comparison items from standard WordPress Post Meta to the new optimized <code>wp_wpc_items</code> table.</p>
            <p><strong>Backup your database</strong> before running this tool.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field( 'wpc_migration_action' ); ?>
                <p>
                    <input type="submit" name="wpc_run_migration" class="button button-primary" value="Run Migration Now" onclick="return confirm('Are you sure you want to migrate data?');">
                </p>
            </form>
        </div>
    </div>
    <?php
}
