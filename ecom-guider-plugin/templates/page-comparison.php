<?php
/**
 * Template for Comparison Page
 * 
 * This template handles comparison URLs with item IDs
 */

get_header();
?>

<div class="comparison-page-wrapper" style="padding: 40px 20px;">
    <div class="container" style="max-width: 1400px; margin: 0 auto;">
        
        <?php
        // Get item IDs from URL
        $compare_ids = isset($_GET['compare_ids']) ? sanitize_text_field($_GET['compare_ids']) : '';
        
        if (!empty($compare_ids)) {
            // Generate shortcode with specific item IDs
            $ids_array = explode(',', $compare_ids);
            $ids_array = array_map('intval', $ids_array);
            $ids_string = implode(',', $ids_array);
            
            echo '<div class="comparison-header" style="text-align: center; margin-bottom: 30px;">';
            echo '<h1 style="font-size: 32px; font-weight: bold; margin-bottom: 10px;">' . __('Comparison', 'wp-comparison-builder') . '</h1>';
            echo '<p style="color: #666;">' . __('Compare features, pricing, and capabilities side-by-side', 'wp-comparison-builder') . '</p>';
            echo '</div>';
            
            // Output the comparison shortcode
            echo do_shortcode('[wpc_compare ids="' . esc_attr($ids_string) . '" featured="' . esc_attr($ids_string) . '"]');
        } else {
            // No IDs provided - show all items
            echo '<div class="comparison-header" style="text-align: center; margin-bottom: 30px;">';
            echo '<h1 style="font-size: 32px; font-weight: bold; margin-bottom: 10px;">' . __('Compare Items', 'wp-comparison-builder') . '</h1>';
            echo '<p style="color: #666;">' . __('Select items to compare their features and pricing', 'wp-comparison-builder') . '</p>';
            echo '</div>';
            
            echo do_shortcode('[wpc_compare]');
        }
        ?>
        
    </div>
</div>

<?php
get_footer();
