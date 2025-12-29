<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add Shortcode Helper Meta Box to Items
 * Shows the hero shortcode for easy copying
 */
function wpc_add_shortcode_metabox() {
    add_meta_box(
        'wpc_shortcode_helper',
        __( 'Shortcode', 'wp-comparison-builder' ),
        'wpc_render_shortcode_metabox',
        'comparison_item',
        'side',
        'high'
    );
    
    // Also add to review pages
    add_meta_box(
        'wpc_review_shortcode',
        __( 'Available Shortcodes', 'wp-comparison-builder' ),
        'wpc_render_review_shortcodes',
        'comparison_review',
        'side',
        'high'
    );
}
add_action( 'add_meta_boxes', 'wpc_add_shortcode_metabox' );

/**
 * Render Item Shortcode Meta Box
 */
function wpc_render_shortcode_metabox( $post ) {
    ?>
    <div style="background: #f0f6ff; border: 2px solid #0d9488; border-radius: 8px; padding: 15px; margin-bottom: 10px;">
        <p style="margin: 0 0 10px; font-weight: 600; color: #333;">Hero Section Shortcode:</p>
        <div style="position: relative;">
            <input 
                type="text" 
                value='[wpc_hero id="<?php echo $post->ID; ?>"]' 
                readonly 
                id="hero-shortcode-<?php echo $post->ID; ?>"
                style="width: 100%; padding: 8px; font-family: monospace; font-size: 12px; background: white; border: 1px solid #ddd; border-radius: 4px;"
                onclick="this.select(); document.execCommand('copy'); alert('Shortcode copied!');"
            />
        </div>
        <p style="margin: 10px 0 0; font-size: 11px; color: #666;">
            Click to copy. Use this shortcode to display this item's hero section on any page.
        </p>
    </div>
    
    <div style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 15px;">
        <p style="margin: 0 0 10px; font-weight: 600; color: #333;">Single Item URL:</p>
        <div style="position: relative;">
            <input 
                type="text" 
                value='<?php echo get_permalink( $post->ID ); ?>' 
                readonly 
                style="width: 100%; padding: 8px; font-family: monospace; font-size: 11px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;"
                onclick="this.select(); document.execCommand('copy'); alert('URL copied!');"
            />
        </div>
        <p style="margin: 10px 0 0; font-size: 11px; color: #666;">
            Direct link to this item's landing page.
        </p>
    </div>
    <?php
}

/**
 * Render Review Page Shortcodes Meta Box
 */
function wpc_render_review_shortcodes( $post ) {
    // Get all items for selection
    $items = get_posts( array(
        'post_type' => 'comparison_item',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ));
    ?>
    <div style="margin-bottom: 15px;">
        <p style="margin: 0 0 10px; font-weight: 600;">Hero Shortcode Generator:</p>
        <select id="item-selector" style="width: 100%; padding: 6px; margin-bottom: 8px;">
            <option value="">-- Select an Item --</option>
            <?php foreach ( $items as $item ) : ?>
                <option value="<?php echo $item->ID; ?>"><?php echo esc_html( $item->post_title ); ?></option>
            <?php endforeach; ?>
        </select>
        <input 
            type="text" 
            id="generated-hero-shortcode" 
            readonly 
            placeholder="[shortcode will appear here]"
            style="width: 100%; padding: 8px; font-family: monospace; font-size: 11px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 5px;"
            onclick="this.select(); document.execCommand('copy'); alert('Shortcode copied!');"
        />
        <p style="font-size: 11px; color: #666; margin: 0;">Select an item above to generate hero shortcode</p>
    </div>
    
    <hr style="margin: 15px 0; border: none; border-top: 1px solid #ddd;">
    
    <div style="margin-bottom: 15px;">
        <p style="margin: 0 0 10px; font-weight: 600;">Comparison Table:</p>
        <input 
            type="text" 
            value='[wpc_compare]' 
            readonly 
            style="width: 100%; padding: 8px; font-family: monospace; font-size: 11px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;"
            onclick="this.select(); document.execCommand('copy'); alert('Shortcode copied!');"
        />
        <p style="font-size: 11px; color: #666; margin: 5px 0 0;">Shows all items with filters</p>
    </div>
    
    <div style="margin-bottom: 15px;">
        <p style="margin: 0 0 10px; font-weight: 600;">Compare Button (Use Item Selector Above):</p>
        <input 
            type="text" 
            id="generated-compare-button-shortcode" 
            readonly 
            placeholder="Select an item first"
            style="width: 100%; padding: 8px; font-family: monospace; font-size: 11px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;"
            onclick="this.select(); document.execCommand('copy'); alert('Shortcode copied!');"
        />
        <p style="font-size: 11px; color: #666; margin: 5px 0 0;">Button with dropdown to compare with other items</p>
    </div>
    
    <div>
        <p style="margin: 0 0 10px; font-weight: 600;">Featured Items:</p>
        <input 
            type="text" 
            value='[wpc_compare ids="1,2,3" featured="1,2"]' 
            readonly 
            style="width: 100%; padding: 8px; font-family: monospace; font-size: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;"
            onclick="this.select(); document.execCommand('copy'); alert('Shortcode copied!');"
        />
        <p style="font-size: 11px; color: #666; margin: 5px 0 0;">Replace with actual item IDs</p>
    </div>
    
    <script>
    document.getElementById('item-selector').addEventListener('change', function() {
        var itemId = this.value;
        var heroInput = document.getElementById('generated-hero-shortcode');
        var buttonInput = document.getElementById('generated-compare-button-shortcode');
        
        if (itemId) {
            heroInput.value = '[wpc_hero id="' + itemId + '"]';
            buttonInput.value = '[wpc_compare_button id="' + itemId + '"]';
        } else {
            heroInput.value = '';
            buttonInput.value = '';
        }
    });
    </script>
    <?php
}
