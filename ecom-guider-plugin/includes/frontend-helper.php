<?php
/**
 * WPC Frontend Helper Functions
 * Shared logic for frontend rendering
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render Category Selector (Tabs or Dropdown)
 * 
 * @param string $current_cat Slug of the currently active category
 * @param array $assigned_cats Array of WP_Term objects
 * @param string $style 'tabs' or 'dropdown'
 * @param string $unique_id Unique ID for this instance (to scope JS)
 */
function wpc_render_category_selector( $current_cat, $assigned_cats, $style, $unique_id ) {
    if ( empty( $assigned_cats ) || count( $assigned_cats ) <= 1 ) {
        return;
    }

    if ( $style === 'dropdown' ) {
        ?>
        <div class="wpc-cat-selector wpc-cat-dropdown" style="margin-bottom: 20px;">
            <select 
                onchange="wpcSwitchTab('<?php echo esc_attr($unique_id); ?>', this.value)"
                style="padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-weight: 500; color: #4b5563; min-width: 200px;"
            >
                <?php foreach ( $assigned_cats as $cat ) : ?>
                    <option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( $current_cat, $cat->slug ); ?>>
                        <?php echo esc_html( $cat->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    } else {
        // Default to Tabs
        ?>
        <div class="wpc-cat-selector wpc-cat-tabs" style="display: flex; gap: 8px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 5px;">
            <?php foreach ( $assigned_cats as $cat ) : 
                $is_active = ( $current_cat === $cat->slug );
                // User requested: "Text-muted" when inactive, "Button Hover (BG) + Button Text" when active.
                // User requested: "Text-muted" when inactive, "Accent Color" for active underline.
                $active_style = "background: transparent; color: hsl(var(--foreground)); border-bottom: 2px solid hsl(var(--accent)); opacity: 1;";
                $inactive_style = "background: transparent; color: hsl(var(--muted-foreground)); border-bottom: 2px solid transparent; opacity: 0.8;";
            ?>
                <div 
                    role="button"
                    tabindex="0"
                    class="wpc-tab-btn"
                    data-tab="<?php echo esc_attr( $cat->slug ); ?>"
                    onclick="wpcSwitchTab('<?php echo esc_attr($unique_id); ?>', '<?php echo esc_attr( $cat->slug ); ?>')"
                    onkeypress="if(event.key==='Enter'||event.key===' ') wpcSwitchTab('<?php echo esc_attr($unique_id); ?>', '<?php echo esc_attr( $cat->slug ); ?>')"
                    style="
                        padding: 10px 16px; 
                        font-weight: 600; 
                        cursor: pointer; 
                        white-space: nowrap; 
                        transition: all 0.2s;
                        font-size: 0.95em;
                        outline: none;
                        user-select: none;
                        border-radius: 0;
                        <?php echo $is_active ? $active_style : $inactive_style; ?>
                    "
                    onmouseover="if(!this.dataset.active) { this.style.color='hsl(var(--foreground))'; }"
                    onmouseout="if(!this.dataset.active) { this.style.color='hsl(var(--muted-foreground))'; }"
                >
                    <?php echo esc_html( $cat->name ); ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <script>
        // Simple inline init to set active state data attribute for hover logic
        (function() {
            var activeBtn = document.querySelector('#<?php echo esc_js($unique_id); ?> .wpc-tab-btn[data-tab="<?php echo esc_js($current_cat); ?>"]');
            if(activeBtn) activeBtn.dataset.active = "true";
        })();
        </script>
        <?php
    }
}
