(function(wp) {
    if (!wp) return;

    var registerBlockType = wp.blocks.registerBlockType;
    var el = wp.element.createElement;
    var useState = wp.element.useState;
    var useEffect = wp.element.useEffect;
    
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var SelectControl = wp.components.SelectControl;
    var TextControl = wp.components.TextControl;
    var CheckboxControl = wp.components.CheckboxControl;
    var Spinner = wp.components.Spinner;
    var ColorPalette = wp.components.ColorPalette;
    var PanelRow = wp.components.PanelRow;
    var apiFetch = wp.apiFetch;

    registerBlockType('wp-comparison-builder/promotion-block', {
        title: 'WPC Promotion Block',
        icon: 'grid-view',
        category: 'widgets',
        description: 'Display comparison items in beautiful responsive promotion cards.',
        attributes: {
            itemIds: { type: 'array', default: [] },
            itemOverrides: { type: 'object', default: {} },
            layout: { type: 'string', default: 'horizontal' },
            // Typography
            titleSize: { type: 'string', default: '' },
            ratingSize: { type: 'string', default: '' },
            btnSize: { type: 'string', default: '' },
            // Colors
            cardBgColor: { type: 'string', default: '' },
            textColor: { type: 'string', default: '' },
            starColor: { type: 'string', default: '' },
            btnBgColor: { type: 'string', default: '' },
            btnTextColor: { type: 'string', default: '' },
            // Borders & Shadow
            cardBorderColor: { type: 'string', default: '' },
            cardBorderWidth: { type: 'string', default: '' },
            cardShadow: { type: 'string', default: 'default' },
        },
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            
            var [availableItems, setAvailableItems] = useState([]);
            var [searchQuery, setSearchQuery] = useState('');
            var [isLoading, setIsLoading] = useState(true);

            // Fetch available comparison items
            useEffect(function() {
                apiFetch({ path: '/wp/v2/comparison_item?per_page=100' }).then(function(items) {
                    var formattedItems = items.map(function(item) {
                        return { value: item.id, label: item.title.rendered || 'Untitled' };
                    });
                    setAvailableItems(formattedItems);
                    setIsLoading(false);
                }).catch(function(error) {
                    console.error('Error fetching comparison items:', error);
                    setIsLoading(false);
                });
            }, []);

            var handleCheckboxChange = function(itemId, isChecked) {
                var newIds = [...attributes.itemIds];
                var newOverrides = Object.assign({}, attributes.itemOverrides);

                if (isChecked) {
                    if (newIds.indexOf(itemId) === -1) {
                        newIds.push(itemId);
                        if (!newOverrides[itemId]) {
                            newOverrides[itemId] = { rating: '', buttonText: '' };
                        }
                    }
                } else {
                    newIds = newIds.filter(function(id) { return id !== itemId; });
                    delete newOverrides[itemId];
                }
                setAttributes({ itemIds: newIds, itemOverrides: newOverrides });
            };

            var updateOverride = function(itemId, field, value) {
                var newOverrides = Object.assign({}, attributes.itemOverrides);
                if (!newOverrides[itemId]) {
                    newOverrides[itemId] = { rating: '', buttonText: '' };
                }
                newOverrides[itemId][field] = value;
                setAttributes({ itemOverrides: newOverrides });
            };

            var filteredItems = availableItems.filter(function(item) {
                return item.label.toLowerCase().indexOf(searchQuery.toLowerCase()) !== -1;
            });

            return el(
                wp.element.Fragment,
                null,
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: 'Card Settings', initialOpen: true },
                        el(SelectControl, {
                            label: 'Layout Style',
                            value: attributes.layout,
                            options: [
                                { label: 'Horizontal (List)', value: 'horizontal' },
                                { label: 'Vertical (Grid)', value: 'grid' },
                                { label: 'Compact', value: 'compact' }
                            ],
                            onChange: function(val) { setAttributes({ layout: val }); }
                        }),
                        isLoading ? el(Spinner, null) : el(
                            'div',
                            null,
                            el(TextControl, {
                                label: 'Search Items',
                                value: searchQuery,
                                onChange: function(val) { setSearchQuery(val); },
                                placeholder: 'Search by name...'
                            }),
                            el(
                                'div',
                                { style: { maxHeight: '250px', overflowY: 'auto', border: '1px solid #ddd', padding: '10px', borderRadius: '4px', background: '#fff' } },
                                filteredItems.map(function(item) {
                                    return el(CheckboxControl, {
                                        key: item.value,
                                        label: item.label,
                                        checked: attributes.itemIds.indexOf(item.value) !== -1,
                                        onChange: function(val) { handleCheckboxChange(item.value, val); }
                                    });
                                }),
                                filteredItems.length === 0 ? el('p', { style: { color: '#666', fontStyle: 'italic', margin: 0 } }, 'No items found.') : null
                            )
                        )
                    ),
                    attributes.itemIds.length > 0 && el(
                        PanelBody,
                        { title: 'Item Overrides', initialOpen: false },
                        attributes.itemIds.map(function(id) {
                            var found = availableItems.find(function(item) { return item.value === id; });
                            var label = found ? found.label : 'Item ' + id;
                            var over = attributes.itemOverrides[id] || { rating: '', buttonText: '' };

                            return el(
                                'div',
                                { key: id, style: { marginBottom: '15px', borderBottom: '1px solid #eee', paddingBottom: '10px' } },
                                el('strong', { style: { display: 'block', marginBottom: '8px' } }, label),
                                el(TextControl, {
                                    label: 'Custom Rating (e.g. 4.8)',
                                    value: over.rating,
                                    onChange: function(val) { updateOverride(id, 'rating', val); }
                                }),
                                el(TextControl, {
                                    label: 'Custom Button Text',
                                    value: over.buttonText,
                                    placeholder: 'e.g. Claim Offer',
                                    onChange: function(val) { updateOverride(id, 'buttonText', val); }
                                })
                            );
                        })
                    ),
                    el(
                        PanelBody,
                        { title: 'Typography', initialOpen: false },
                        el(TextControl, {
                            label: 'Title Font Size (e.g. 20px, 1.5rem)',
                            value: attributes.titleSize,
                            onChange: function(val) { setAttributes({ titleSize: val }); }
                        }),
                        el(TextControl, {
                            label: 'Rating Text Size (e.g. 14px)',
                            value: attributes.ratingSize,
                            onChange: function(val) { setAttributes({ ratingSize: val }); }
                        }),
                        el(TextControl, {
                            label: 'Button Font Size (e.g. 15px)',
                            value: attributes.btnSize,
                            onChange: function(val) { setAttributes({ btnSize: val }); }
                        })
                    ),
                    el(
                        PanelBody,
                        { title: 'Colors', initialOpen: false },
                        el(PanelRow, null, el('span', null, 'Card Background')),
                        el(ColorPalette, {
                            value: attributes.cardBgColor,
                            onChange: function(val) { setAttributes({ cardBgColor: val }); }
                        }),
                        el(PanelRow, null, el('span', null, 'Text Color')),
                        el(ColorPalette, {
                            value: attributes.textColor,
                            onChange: function(val) { setAttributes({ textColor: val }); }
                        }),
                        el(PanelRow, null, el('span', null, 'Star Rating Color')),
                        el(ColorPalette, {
                            value: attributes.starColor,
                            onChange: function(val) { setAttributes({ starColor: val }); }
                        }),
                        el(PanelRow, null, el('span', null, 'Button Background')),
                        el(ColorPalette, {
                            value: attributes.btnBgColor,
                            onChange: function(val) { setAttributes({ btnBgColor: val }); }
                        }),
                        el(PanelRow, null, el('span', null, 'Button Text Color')),
                        el(ColorPalette, {
                            value: attributes.btnTextColor,
                            onChange: function(val) { setAttributes({ btnTextColor: val }); }
                        })
                    ),
                    el(
                        PanelBody,
                        { title: 'Borders & Shadow', initialOpen: false },
                        el(SelectControl, {
                            label: 'Border Width',
                            value: attributes.cardBorderWidth,
                            options: [
                                { label: 'Default', value: '' },
                                { label: 'None (0px)', value: '0px' },
                                { label: '1px', value: '1px' },
                                { label: '2px', value: '2px' },
                                { label: '3px', value: '3px' },
                                { label: '4px', value: '4px' }
                            ],
                            onChange: function(val) { setAttributes({ cardBorderWidth: val }); }
                        }),
                        el(PanelRow, null, el('span', null, 'Border Color')),
                        el(ColorPalette, {
                            value: attributes.cardBorderColor,
                            onChange: function(val) { setAttributes({ cardBorderColor: val }); }
                        }),
                        el(SelectControl, {
                            label: 'Card Shadow',
                            value: attributes.cardShadow,
                            options: [
                                { label: 'Default', value: 'default' },
                                { label: 'None', value: 'none' },
                                { label: 'Soft', value: 'soft' },
                                { label: 'Heavy', value: 'heavy' }
                            ],
                            onChange: function(val) { setAttributes({ cardShadow: val }); }
                        })
                    )
                ),
                // Canvas Editor Preview Box
                el(
                    'div',
                    { 
                        style: { 
                            padding: '16px', 
                            border: '1px solid #e2e8f0', 
                            borderRadius: '8px',
                            background: '#ffffff',
                            fontFamily: 'sans-serif'
                        } 
                    },
                    el('div', { style: { fontWeight: 'bold', fontSize: '14px', marginBottom: '12px', color: '#1e3a8a', display: 'flex', alignItems: 'center', gap: '6px' } }, 
                        el('span', { style: { fontSize: '18px' } }, '📢'),
                        el('span', null, 'WPC Promotion Block')
                    ),
                    el('div', { style: { fontSize: '13px', color: '#64748b', marginBottom: '8px' } }, 
                        'Layout: ' + attributes.layout 
                    ),
                    attributes.itemIds.length === 0 ? 
                        el('p', { style: { color: '#b91c1c', fontStyle: 'italic', fontSize: '13px', margin: 0 } }, '⚠️ No items selected. Please select items from the sidebar.') :
                        el('div', null,
                            el('div', { style: { fontSize: '13px', fontWeight: 'bold', color: '#1e293b', marginBottom: '6px' } }, 'Selected Items (' + attributes.itemIds.length + '):'),
                            el('ul', { style: { margin: 0, paddingLeft: '20px', fontSize: '13px', color: '#334155' } },
                                attributes.itemIds.map(function(id) {
                                    var found = availableItems.find(function(item) { return item.value === id; });
                                    var label = found ? found.label : 'Loading...';
                                    return el('li', { key: id, style: { marginBottom: '4px' } }, label);
                                })
                            )
                        )
                )
            );
        },
        save: function() {
            // Server-side render block
            return null;
        }
    });
})(window.wp);
