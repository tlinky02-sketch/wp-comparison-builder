<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add Meta Box to Hosting List
 */
function hosting_guider_list_add_meta_box() {
    add_meta_box(
        'hosting_guider_list_config',
        __( 'List Configuration', 'hosting-guider' ),
        'hosting_guider_render_list_meta_box',
        'hosting_list',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'hosting_guider_list_add_meta_box' );

/**
 * Render List Config Meta Box
 */
function hosting_guider_render_list_meta_box( $post ) {
    wp_nonce_field( 'hosting_guider_save_list', 'hosting_guider_list_nonce' );

    $selected_ids = get_post_meta( $post->ID, '_hg_list_ids', true ) ?: [];
    $featured_ids = get_post_meta( $post->ID, '_hg_list_featured', true ) ?: [];
    $badge_texts = get_post_meta( $post->ID, '_hg_list_badge_texts', true ) ?: [];
    $badge_colors = get_post_meta( $post->ID, '_hg_list_badge_colors', true ) ?: [];
    $limit = get_post_meta( $post->ID, '_hg_list_limit', true );

    // Get all providers
    $providers = get_posts( array(
        'post_type' => 'hosting_provider',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC'
    ) );
    ?>
    <div class="hg-list-config">
        <p class="description"><?php _e( 'Select the providers you want to include in this list. Checks in the second column marks them as "Featured". Customize badge text and color for each featured provider.', 'hosting-guider' ); ?></p>
        
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><input type="checkbox" id="cb-select-all-1"></th>
                    <th><?php _e( 'Provider Name', 'hosting-guider' ); ?></th>
                    <th style="width: 100px; text-align: center;"><?php _e( 'Featured?', 'hosting-guider' ); ?></th>
                    <th style="width: 200px;"><?php _e( 'Featured Badge Text', 'hosting-guider' ); ?></th>
                    <th style="width: 120px;"><?php _e( 'Badge Color', 'hosting-guider' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $providers ) : ?>
                    <?php foreach ( $providers as $p ) : ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="hg_list_ids[]" value="<?php echo $p->ID; ?>" 
                                    <?php checked( in_array( $p->ID, $selected_ids ) ); ?> />
                            </td>
                            <td>
                                <strong><?php echo esc_html( $p->post_title ); ?></strong>
                                <span style="color: #999; margin-left: 5px;">ID: <?php echo $p->ID; ?></span>
                            </td>
                            <td style="text-align: center;">
                                <input type="checkbox" name="hg_list_featured[]" value="<?php echo $p->ID; ?>" 
                                    <?php checked( in_array( $p->ID, $featured_ids ) ); ?> />
                            </td>
                            <td>
                                <input type="text" 
                                    name="hg_badge_text[<?php echo $p->ID; ?>]" 
                                    value="<?php echo esc_attr( isset($badge_texts[$p->ID]) ? $badge_texts[$p->ID] : '' ); ?>" 
                                    placeholder="e.g., Top Choice" 
                                    style="width: 100%;" />
                                <small style="color: #666;"><?php _e( 'Only if Featured', 'hosting-guider' ); ?></small>
                            </td>
                            <td>
                                <input type="color" 
                                    name="hg_badge_color[<?php echo $p->ID; ?>]" 
                                    value="<?php echo esc_attr( isset($badge_colors[$p->ID]) ? $badge_colors[$p->ID] : '#f59e0b' ); ?>" 
                                    style="width: 100%; height: 40px;" />
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="5"><?php _e( 'No hosting providers found.', 'hosting-guider' ); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <p style="margin-top: 20px;">
            <label><strong><?php _e( 'Limit Number of Items:', 'hosting-guider' ); ?></strong></label>
            <input type="number" name="hg_list_limit" value="<?php echo esc_attr( $limit ); ?>" style="width: 80px;" />
            <span class="description"><?php _e( '(Leave empty for no limit)', 'hosting-guider' ); ?></span>
        </p>

        <div style="margin-top: 20px; padding: 15px; background: #f0f0f1; border: 1px solid #ccd0d4;">
            <strong><?php _e( 'Shortcode:', 'hosting-guider' ); ?></strong>
            <code style="font-size: 1.2em; display: inline-block; margin-left: 10px; user-select: all;">[hosting_guider_list id="<?php echo $post->ID; ?>"]</code>
            <p class="description" style="margin-top: 5px;"><?php _e( 'Copy and paste this shortcode to display this specific list.', 'hosting-guider' ); ?></p>
        </div>
    </div>
    <?php
}

/**
 * Save List Data
 */
function hosting_guider_save_list_meta( $post_id ) {
    if ( ! isset( $_POST['hosting_guider_list_nonce'] ) || ! wp_verify_nonce( $_POST['hosting_guider_list_nonce'], 'hosting_guider_save_list' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $ids = isset( $_POST['hg_list_ids'] ) ? array_map( 'intval', $_POST['hg_list_ids'] ) : [];
    $featured = isset( $_POST['hg_list_featured'] ) ? array_map( 'intval', $_POST['hg_list_featured'] ) : [];
    $limit = ( isset( $_POST['hg_list_limit'] ) && $_POST['hg_list_limit'] !== '' ) ? intval( $_POST['hg_list_limit'] ) : '';
    
    // Save badge customizations
    $badge_texts = [];
    $badge_colors = [];
    if ( isset( $_POST['hg_badge_text'] ) ) {
        foreach ( $_POST['hg_badge_text'] as $provider_id => $text ) {
            $badge_texts[intval($provider_id)] = sanitize_text_field( $text );
        }
    }
    if ( isset( $_POST['hg_badge_color'] ) ) {
        foreach ( $_POST['hg_badge_color'] as $provider_id => $color ) {
            $badge_colors[intval($provider_id)] = sanitize_hex_color( $color );
        }
    }

    update_post_meta( $post_id, '_hg_list_ids', $ids );
    update_post_meta( $post_id, '_hg_list_featured', $featured );
    update_post_meta( $post_id, '_hg_list_badge_texts', $badge_texts );
    update_post_meta( $post_id, '_hg_list_badge_colors', $badge_colors );
    update_post_meta( $post_id, '_hg_list_limit', $limit );
}
add_action( 'save_post_hosting_list', 'hosting_guider_save_list_meta' );

/**
 * Add Shortcode Column to Admin List
 */
function hosting_guider_list_columns( $columns ) {
    $columns['shortcode'] = __( 'Shortcode', 'hosting-guider' );
    return $columns;
}
add_filter( 'manage_hosting_list_posts_columns', 'hosting_guider_list_columns' );

function hosting_guider_list_custom_column( $column, $post_id ) {
    if ( $column === 'shortcode' ) {
        echo '<code style="user-select: all;">[hosting_guider_list id="' . $post_id . '"]</code>';
    }
}
add_action( 'manage_hosting_list_posts_custom_column', 'hosting_guider_list_custom_column', 10, 2 );
