        <!-- TAB: USE CASES -->
        <div id="wpc-tab-use_cases" class="wpc-tab-content">
            <?php
            // Check if variants are enabled
            $variants_enabled = get_post_meta( $post->ID, '_wpc_variants_enabled', true ) === '1';
            $all_assigned_cats = wp_get_post_terms( $post->ID, 'comparison_category', array( 'fields' => 'all' ) );
            
            // Get selected variant categories (if set)
            $variant_cat_ids = get_post_meta( $post->ID, '_wpc_variant_categories', true );
            if ( ! is_array( $variant_cat_ids ) ) $variant_cat_ids = array();
            $variant_cat_ids = array_map( 'intval', $variant_cat_ids ); // Ensure integers
            
            // Filter to only selected categories, or use all if none selected
            if ( $variants_enabled && !empty($variant_cat_ids) ) {
                $assigned_cats = array_filter( $all_assigned_cats, function($cat) use ($variant_cat_ids) {
                    return in_array( (int) $cat->term_id, $variant_cat_ids, true );
                });
                $assigned_cats = array_values($assigned_cats); // Re-index
            } else {
                $assigned_cats = $all_assigned_cats;
            }
            
            $has_variants = $variants_enabled && !empty($assigned_cats);
            
            // Get legacy use cases
            $use_cases = get_post_meta( $post->ID, '_wpc_use_cases', true );
            if ( ! is_array( $use_cases ) ) $use_cases = [];
            
            // Get category-specific use cases
            $use_cases_by_category = get_post_meta( $post->ID, '_wpc_use_cases_by_category', true );
            if ( ! is_array( $use_cases_by_category ) ) $use_cases_by_category = [];
            ?>
            
            <h3 class="wpc-section-title">Best Use Cases Highlights</h3>
            <p class="description" style="margin-bottom: 20px;">Add highlights for "Best For..." scenarios. These will be displayed in a responsive grid using the <code>[wpc_use_cases]</code> shortcode.</p>

            <!-- Shortcode Display -->
            <div style="background:#f5f3ff; border:1px solid #8b5cf6; padding:15px; border-radius:6px; margin-bottom:20px;">
                <?php if ( $has_variants ) : ?>
                    <strong style="color:#7c3aed; display:block; margin-bottom:8px;">Category-Specific Shortcodes:</strong>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <!-- All Use Cases -->
                        <div style="display:flex; align-items:center; gap:10px;">
                            <code style="flex:1; background:#fff; padding:8px 12px; border:1px solid #ddd; border-radius:4px; color:#7c3aed;">[wpc_use_cases id="<?php echo $post->ID; ?>"]</code>
                            <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('[wpc_use_cases id=<?php echo $post->ID; ?>]'); this.innerText='Copied!'; setTimeout(()=>this.innerText='Copy', 1500);">Copy</button>
                            <span style="font-size: 11px; color: #64748b;">(All)</span>
                        </div>
                        
                        <!-- Per Category -->
                        <?php foreach ( $assigned_cats as $cat ) : ?>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <code style="flex:1; background:#fff; padding:8px 12px; border:1px solid #ddd; border-radius:4px; color:#7c3aed;">[wpc_use_cases id="<?php echo $post->ID; ?>" category="<?php echo esc_attr($cat->slug); ?>"]</code>
                            <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('[wpc_use_cases id=<?php echo $post->ID; ?> category=<?php echo esc_js($cat->slug); ?>]'); this.innerText='Copied!'; setTimeout(()=>this.innerText='Copy', 1500);">Copy</button>
                            <span style="padding: 2px 8px; background: #e0e7ff; color: #4f46e5; border-radius: 9999px; font-size: 11px; font-weight: 600;"><?php echo esc_html($cat->name); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <strong style="color:#7c3aed; display:block; margin-bottom:4px;">Shortcode for this Item:</strong>
                    <div style="display:flex; gap:10px;">
                        <code style="background:#fff; padding:6px 10px; border:1px solid #ddd; border-radius:4px; color:#7c3aed; flex:1;">[wpc_use_cases id="<?php echo $post->ID; ?>"]</code>
                        <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('[wpc_use_cases id=<?php echo $post->ID; ?>]'); this.innerText='Copied!'; setTimeout(()=>this.innerText='Copy', 1500);">Copy</button>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ( $has_variants ) : ?>
                <!-- CATEGORY-AWARE MODE -->
                <div id="wpc-cat-usecase-editor">
                    <!-- Category Selector -->
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <?php foreach ( $assigned_cats as $cat_idx => $cat ) : ?>
                                <button 
                                    type="button" 
                                    class="wpc-cat-uc-tab <?php echo $cat_idx === 0 ? 'active' : ''; ?>" 
                                    data-category="<?php echo esc_attr($cat->slug); ?>"
                                    onclick="wpcSwitchUseCaseCategory('<?php echo esc_js($cat->slug); ?>')"
                                    style="padding: 8px 16px; cursor: pointer; border: 1px solid #e5e7eb; background: <?php echo $cat_idx === 0 ? '#8b5cf6' : '#f9fafb'; ?>; color: <?php echo $cat_idx === 0 ? '#fff' : '#6b7280'; ?>; border-radius: 6px; font-weight: 600; font-size: 13px; transition: all 0.2s;"
                                >
                                    <?php echo esc_html($cat->name); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="button button-primary" onclick="wpcAddCatUseCase()">+ Add Use Case</button>
                    </div>
                    
                    <!-- Use Case Lists (One per Category) -->
                    <?php foreach ( $assigned_cats as $cat ) : 
                        $cat_use_cases = isset( $use_cases_by_category[$cat->slug] ) ? $use_cases_by_category[$cat->slug] : [];
                    ?>
                    <div class="wpc-cat-usecase-list" data-category="<?php echo esc_attr($cat->slug); ?>" style="display: <?php echo $cat === reset($assigned_cats) ? 'block' : 'none'; ?>;">
                        <div id="wpc-use-cases-list-<?php echo esc_attr($cat->slug); ?>">
                            <?php foreach ( $cat_use_cases as $index => $case ) :
                                $name = isset( $case['name'] ) ? esc_attr( $case['name'] ) : '';
                                $desc = isset( $case['desc'] ) ? esc_textarea( $case['desc'] ) : '';
                                $icon = isset( $case['icon'] ) ? esc_attr( $case['icon'] ) : '';
                                $image = isset( $case['image'] ) ? esc_url( $case['image'] ) : '';
                                $has_custom_color = isset($case['icon_color']) && !empty($case['icon_color']);
                                $color_value = $has_custom_color ? esc_attr($case['icon_color']) : '#6366f1';
                            ?>
                            <div class="wpc-use-case-item" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 6px; margin-bottom: 10px; position: relative;">
                                <button type="button" class="button-link-delete" style="position: absolute; top: 10px; right: 10px; color: #ef4444; text-decoration: none;" onclick="this.closest('.wpc-use-case-item').remove()">Remove</button>
                                
                                <div class="wpc-row" style="margin-bottom: 10px;">
                                    <div class="wpc-col">
                                        <label class="wpc-label">Name / Title</label>
                                        <input type="text" name="wpc_use_cases_by_category[<?php echo esc_attr($cat->slug); ?>][<?php echo $index; ?>][name]" value="<?php echo $name; ?>" class="wpc-input" placeholder="e.g. Best for Dropshipping" />
                                    </div>
                                    <div class="wpc-col">
                                        <label class="wpc-label">Icon Class (FontAwesome/Lucide)</label>
                                        <input type="text" name="wpc_use_cases_by_category[<?php echo esc_attr($cat->slug); ?>][<?php echo $index; ?>][icon]" value="<?php echo $icon; ?>" class="wpc-input" placeholder="e.g. fa-solid fa-rocket" />
                                    </div>
                                </div>
                                
                                <div class="wpc-row" style="margin-bottom: 10px;">
                                    <div class="wpc-col">
                                         <label class="wpc-label">Description</label>
                                         <textarea name="wpc_use_cases_by_category[<?php echo esc_attr($cat->slug); ?>][<?php echo $index; ?>][desc]" class="wpc-input" style="height: 60px;"><?php echo $desc; ?></textarea>
                                    </div>
                                    <div class="wpc-col">
                                        <label class="wpc-label">Icon Color</label>
                                        <div style="display: flex; gap: 10px; align-items: center;">
                                            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                                <input 
                                                    type="checkbox" 
                                                    class="wpc-uc-color-toggle"
                                                    <?php echo $has_custom_color ? 'checked' : ''; ?>
                                                    onchange="var col = this.closest('.wpc-col'); col.querySelector('.wpc-uc-color-picker').style.display = this.checked ? 'flex' : 'none'; col.querySelector('.wpc-uc-color-value').value = this.checked ? col.querySelector('input[type=color]').value : '';"
                                                />
                                                <span style="font-size: 13px;">Use custom color</span>
                                            </label>
                                            <div class="wpc-uc-color-picker" style="display: <?php echo $has_custom_color ? 'flex' : 'none'; ?>; gap: 5px; align-items: center;">
                                                <input 
                                                    type="color" 
                                                    value="<?php echo $color_value; ?>"
                                                    onchange="this.nextElementSibling.value = this.value"
                                                    style="width: 40px; height: 30px; border: 1px solid #ddd; padding: 0; cursor: pointer;"
                                                />
                                                <input 
                                                    type="hidden" 
                                                    name="wpc_use_cases_by_category[<?php echo esc_attr($cat->slug); ?>][<?php echo $index; ?>][icon_color]"
                                                    class="wpc-uc-color-value"
                                                    value="<?php echo $has_custom_color ? $color_value : ''; ?>"
                                                />
                                            </div>
                                        </div>
                                        <small style="color: #888; font-size: 11px; margin-top: 3px; display: block;">Unchecked = uses global primary color</small>
                                    </div>
                                </div>

                                <div class="wpc-row" style="margin-bottom: 0;">
                                    <div class="wpc-col">
                                        <label class="wpc-label">Custom Image (Optional)</label>
                                        <div style="display: flex; gap: 10px;">
                                            <input type="text" name="wpc_use_cases_by_category[<?php echo esc_attr($cat->slug); ?>][<?php echo $index; ?>][image]" value="<?php echo $image; ?>" class="wpc-input wpc-uc-image-input" placeholder="https://..." />
                                            <button type="button" class="button wpc-uc-upload-btn">Upload</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <script>
                var wpcCurrentUseCaseCategory = '<?php echo $has_variants ? esc_js($assigned_cats[0]->slug) : ''; ?>';
                
                function wpcSwitchUseCaseCategory(catSlug) {
                    wpcCurrentUseCaseCategory = catSlug;
                    
                    // Update active tab
                    document.querySelectorAll('.wpc-cat-uc-tab').forEach(function(btn) {
                        if (btn.dataset.category === catSlug) {
                            btn.style.background = '#8b5cf6';
                            btn.style.color = '#fff';
                        } else {
                            btn.style.background = '#f9fafb';
                            btn.style.color = '#6b7280';
                        }
                    });
                    
                    // Show/hide use case lists
                    document.querySelectorAll('.wpc-cat-usecase-list').forEach(function(list) {
                        list.style.display = list.dataset.category === catSlug ? 'block' : 'none';
                    });
                }
                
                function wpcAddCatUseCase() {
                    const listContainer = document.querySelector('.wpc-cat-usecase-list[data-category="' + wpcCurrentUseCaseCategory + '"] > div');
                    if (!listContainer) return;
                    
                    const index = listContainer.children.length;
                    const catSlug = wpcCurrentUseCaseCategory;
                    const item = document.createElement('div');
                    item.className = 'wpc-use-case-item';
                    item.style.cssText = 'background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 6px; margin-bottom: 10px; position: relative; animation: wpcSlideDown 0.3s ease-out;';
                    item.innerHTML = `
                        <button type="button" class="button-link-delete" style="position: absolute; top: 10px; right: 10px; color: #ef4444; text-decoration: none;" onclick="this.closest('.wpc-use-case-item').remove()">Remove</button>
                        
                        <div class="wpc-row" style="margin-bottom: 10px;">
                            <div class="wpc-col">
                                <label class="wpc-label">Name / Title</label>
                                <input type="text" name="wpc_use_cases_by_category[${catSlug}][${index}][name]" class="wpc-input" placeholder="e.g. Best for Dropshipping" />
                            </div>
                            <div class="wpc-col">
                                <label class="wpc-label">Icon Class (FontAwesome/Lucide)</label>
                                <input type="text" name="wpc_use_cases_by_category[${catSlug}][${index}][icon]" class="wpc-input" placeholder="e.g. fa-solid fa-rocket" />
                            </div>
                        </div>
                        
                        <div class="wpc-row" style="margin-bottom: 10px;">
                            <div class="wpc-col">
                                 <label class="wpc-label">Description</label>
                                 <textarea name="wpc_use_cases_by_category[${catSlug}][${index}][desc]" class="wpc-input" style="height: 60px;"></textarea>
                            </div>
                            <div class="wpc-col">
                                <label class="wpc-label">Icon Color</label>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                        <input 
                                            type="checkbox" 
                                            class="wpc-uc-color-toggle"
                                            onchange="var col = this.closest('.wpc-col'); col.querySelector('.wpc-uc-color-picker').style.display = this.checked ? 'flex' : 'none'; col.querySelector('.wpc-uc-color-value').value = this.checked ? col.querySelector('input[type=color]').value : '';"
                                        />
                                        <span style="font-size: 13px;">Use custom color</span>
                                    </label>
                                    <div class="wpc-uc-color-picker" style="display: none; gap: 5px; align-items: center;">
                                        <input 
                                            type="color" 
                                            value="#6366f1"
                                            onchange="this.nextElementSibling.value = this.value"
                                            style="width: 40px; height: 30px; border: 1px solid #ddd; padding: 0; cursor: pointer;"
                                        />
                                        <input 
                                            type="hidden" 
                                            name="wpc_use_cases_by_category[${catSlug}][${index}][icon_color]"
                                            class="wpc-uc-color-value"
                                            value=""
                                        />
                                    </div>
                                </div>
                                <small style="color: #888; font-size: 11px; margin-top: 3px; display: block;">Unchecked = uses global primary color</small>
                            </div>
                        </div>

                        <div class="wpc-row" style="margin-bottom: 0;">
                            <div class="wpc-col">
                                <label class="wpc-label">Custom Image (Optional)</label>
                                <div style="display: flex; gap: 10px;">
                                    <input type="text" name="wpc_use_cases_by_category[${catSlug}][${index}][image]" class="wpc-input wpc-uc-image-input" placeholder="https://..." />
                                    <button type="button" class="button wpc-uc-upload-btn">Upload</button>
                                </div>
                            </div>
                        </div>
                    `;
                    listContainer.appendChild(item);
                    wpcBindUseCaseUploaders();
                }
                
                jQuery(document).ready(function() {
                    wpcBindUseCaseUploaders();
                });
                </script>
                
                <style>
                .wpc-cat-uc-tab:hover {
                    transform: translateY(-1px);
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                @keyframes wpcSlideDown {
                    from { opacity: 0; transform: translateY(-10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                </style>
                
            <?php else : ?>
                <!-- LEGACY MODE (No Variants) -->
                <div id="wpc-use-cases-list">
                    <?php foreach ( $use_cases as $index => $case ) :
                        $name = isset( $case['name'] ) ? esc_attr( $case['name'] ) : '';
                        $desc = isset( $case['desc'] ) ? esc_textarea( $case['desc'] ) : '';
                        $icon = isset( $case['icon'] ) ? esc_attr( $case['icon'] ) : '';
                        $image = isset( $case['image'] ) ? esc_url( $case['image'] ) : '';
                        $has_custom_color = isset($case['icon_color']) && !empty($case['icon_color']);
                        $color_value = $has_custom_color ? esc_attr($case['icon_color']) : '#6366f1';
                    ?>
                    <div class="wpc-use-case-item" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 6px; margin-bottom: 10px; position: relative;">
                        <button type="button" class="button-link-delete" style="position: absolute; top: 10px; right: 10px; color: #ef4444; text-decoration: none;" onclick="this.closest('.wpc-use-case-item').remove()">Remove</button>
                        
                        <div class="wpc-row" style="margin-bottom: 10px;">
                            <div class="wpc-col">
                                <label class="wpc-label">Name / Title</label>
                                <input type="text" name="wpc_use_cases[<?php echo $index; ?>][name]" value="<?php echo $name; ?>" class="wpc-input" placeholder="e.g. Best for Dropshipping" />
                            </div>
                            <div class="wpc-col">
                                <label class="wpc-label">Icon Class (FontAwesome/Lucide)</label>
                                <input type="text" name="wpc_use_cases[<?php echo $index; ?>][icon]" value="<?php echo $icon; ?>" class="wpc-input" placeholder="e.g. fa-solid fa-rocket" />
                            </div>
                        </div>
                        
                        <div class="wpc-row" style="margin-bottom: 10px;">
                            <div class="wpc-col">
                                 <label class="wpc-label">Description</label>
                                 <textarea name="wpc_use_cases[<?php echo $index; ?>][desc]" class="wpc-input" style="height: 60px;"><?php echo $desc; ?></textarea>
                            </div>
                            <div class="wpc-col">
                                <label class="wpc-label">Icon Color</label>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                        <input 
                                            type="checkbox" 
                                            class="wpc-uc-color-toggle"
                                            <?php echo $has_custom_color ? 'checked' : ''; ?>
                                            onchange="var col = this.closest('.wpc-col'); col.querySelector('.wpc-uc-color-picker').style.display = this.checked ? 'flex' : 'none'; col.querySelector('.wpc-uc-color-value').value = this.checked ? col.querySelector('input[type=color]').value : '';"
                                        />
                                        <span style="font-size: 13px;">Use custom color</span>
                                    </label>
                                    <div class="wpc-uc-color-picker" style="display: <?php echo $has_custom_color ? 'flex' : 'none'; ?>; gap: 5px; align-items: center;">
                                        <input 
                                            type="color" 
                                            value="<?php echo $color_value; ?>"
                                            onchange="this.nextElementSibling.value = this.value"
                                            style="width: 40px; height: 30px; border: 1px solid #ddd; padding: 0; cursor: pointer;"
                                        />
                                        <input 
                                            type="hidden" 
                                            name="wpc_use_cases[<?php echo $index; ?>][icon_color]"
                                            class="wpc-uc-color-value"
                                            value="<?php echo $has_custom_color ? $color_value : ''; ?>"
                                        />
                                    </div>
                                </div>
                                <small style="color: #888; font-size: 11px; margin-top: 3px; display: block;">Unchecked = uses global primary color</small>
                            </div>
                        </div>

                        <div class="wpc-row" style="margin-bottom: 0;">
                            <div class="wpc-col">
                                <label class="wpc-label">Custom Image (Optional)</label>
                                <div style="display: flex; gap: 10px;">
                                    <input type="text" name="wpc_use_cases[<?php echo $index; ?>][image]" value="<?php echo $image; ?>" class="wpc-input wpc-uc-image-input" placeholder="https://..." />
                                    <button type="button" class="button wpc-uc-upload-btn">Upload</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <button type="button" class="button button-primary" onclick="wpcAddUseCase()">+ Add Use Case</button>

                <script>
                function wpcAddUseCase() {
                    const list = document.getElementById('wpc-use-cases-list');
                    const index = list.children.length;
                    const item = document.createElement('div');
                    item.className = 'wpc-use-case-item';
                    item.style.cssText = 'background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 6px; margin-bottom: 10px; position: relative; animation: wpcSlideDown 0.3s ease-out;';
                    item.innerHTML = `
                        <button type="button" class="button-link-delete" style="position: absolute; top: 10px; right: 10px; color: #ef4444; text-decoration: none;" onclick="this.closest('.wpc-use-case-item').remove()">Remove</button>
                        
                        <div class="wpc-row" style="margin-bottom: 10px;">
                            <div class="wpc-col">
                                <label class="wpc-label">Name / Title</label>
                                <input type="text" name="wpc_use_cases[${index}][name]" class="wpc-input" placeholder="e.g. Best for Dropshipping" />
                            </div>
                            <div class="wpc-col">
                                <label class="wpc-label">Icon Class (FontAwesome/Lucide)</label>
                                <input type="text" name="wpc_use_cases[${index}][icon]" class="wpc-input" placeholder="e.g. fa-solid fa-rocket" />
                            </div>
                        </div>
                        
                        <div class="wpc-row" style="margin-bottom: 10px;">
                            <div class="wpc-col">
                                 <label class="wpc-label">Description</label>
                                 <textarea name="wpc_use_cases[${index}][desc]" class="wpc-input" style="height: 60px;"></textarea>
                            </div>
                            <div class="wpc-col">
                                <label class="wpc-label">Icon Color</label>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                        <input 
                                            type="checkbox" 
                                            class="wpc-uc-color-toggle"
                                            onchange="var col = this.closest('.wpc-col'); col.querySelector('.wpc-uc-color-picker').style.display = this.checked ? 'flex' : 'none'; col.querySelector('.wpc-uc-color-value').value = this.checked ? col.querySelector('input[type=color]').value : '';"
                                        />
                                        <span style="font-size: 13px;">Use custom color</span>
                                    </label>
                                    <div class="wpc-uc-color-picker" style="display: none; gap: 5px; align-items: center;">
                                        <input 
                                            type="color" 
                                            value="#6366f1"
                                            onchange="this.nextElementSibling.value = this.value"
                                            style="width: 40px; height: 30px; border: 1px solid #ddd; padding: 0; cursor: pointer;"
                                        />
                                        <input 
                                            type="hidden" 
                                            name="wpc_use_cases[${index}][icon_color]"
                                            class="wpc-uc-color-value"
                                            value=""
                                        />
                                    </div>
                                </div>
                                <small style="color: #888; font-size: 11px; margin-top: 3px; display: block;">Unchecked = uses global primary color</small>
                            </div>
                        </div>

                        <div class="wpc-row" style="margin-bottom: 0;">
                            <div class="wpc-col">
                                <label class="wpc-label">Custom Image (Optional)</label>
                                <div style="display: flex; gap: 10px;">
                                    <input type="text" name="wpc_use_cases[${index}][image]" class="wpc-input wpc-uc-image-input" placeholder="https://..." />
                                    <button type="button" class="button wpc-uc-upload-btn">Upload</button>
                                </div>
                            </div>
                        </div>
                    `;
                    list.appendChild(item);
                    wpcBindUseCaseUploaders();
                }
                </script>
                
                <style>
                @keyframes wpcSlideDown {
                    from { opacity: 0; transform: translateY(-10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                </style>
            <?php endif; ?>
            
            <script>
            function wpcBindUseCaseUploaders() {
                jQuery(document).off('click', '.wpc-uc-upload-btn').on('click', '.wpc-uc-upload-btn', function(e) {
                    e.preventDefault();
                    var button = jQuery(this);
                    var input = button.prev('.wpc-uc-image-input');
                    
                    var mediaUploader = wp.media({
                        title: 'Choose Image',
                        button: {
                            text: 'Choose Image'
                        },
                        multiple: false
                    });
                    
                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        input.val(attachment.url);
                    });
                    
                    mediaUploader.open();
                });
            }
            
            jQuery(document).ready(function() {
                wpcBindUseCaseUploaders();
            });
            </script>
        </div>
