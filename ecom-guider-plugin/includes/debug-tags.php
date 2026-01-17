<?php
/**
 * Debug Shortcode: [wpc_debug_tags]
 * Displays raw feature configuration and tag terms
 */
function wpc_debug_tags_shortcode() {
    if ( ! current_user_can( 'manage_options' ) ) return '';

    $compare_features = get_option( 'wpc_compare_features', array() );
    $tag_terms = wpc_get_compare_tag_terms();
    
    ob_start();
    ?>
    <div style="background:#fff; border:2px solid red; padding:20px; color:#000; z-index:99999; position:relative;">
        <h3>WPC Debug Tags</h3>
        
        <h4>1. Saved Compare Features (Global)</h4>
        <pre><?php print_r( $compare_features ); ?></pre>
        
        <h4>2. Available Tag Terms via wpc_get_compare_tag_terms()</h4>
        <pre><?php print_r( $tag_terms ); ?></pre>
        
        <h4>3. Raw Comparison Feature Taxonomy Terms</h4>
        <pre><?php 
            $raw_terms = get_terms( array( 'taxonomy' => 'comparison_feature', 'hide_empty' => false ) );
            print_r( $raw_terms ); 
        ?></pre>

        <h4>4. Legacy 'ecommerce_feature' Terms (If any)</h4>
        <pre><?php 
            if ( taxonomy_exists( 'ecommerce_feature' ) ) {
                $legacy_terms = get_terms( array( 'taxonomy' => 'ecommerce_feature', 'hide_empty' => false ) );
                print_r( $legacy_terms );
            } else {
                echo "Taxonomy 'ecommerce_feature' does not exist.";
            }
        ?></pre>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'wpc_debug_tags', 'wpc_debug_tags_shortcode' );
