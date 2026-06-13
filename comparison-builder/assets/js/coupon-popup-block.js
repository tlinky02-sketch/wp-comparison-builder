(function(wp) {
    if (!wp) return;

    var registerBlockType = wp.blocks.registerBlockType;
    var el = wp.element.createElement;
    var useState = wp.element.useState;
    var useEffect = wp.element.useEffect;
    
    var InspectorControls = wp.blockEditor.InspectorControls;
    var MediaUpload = wp.blockEditor.MediaUpload;
    var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;
    
    var PanelBody = wp.components.PanelBody;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var ToggleControl = wp.components.ToggleControl;
    var ColorPalette = wp.components.ColorPalette;
    var PanelRow = wp.components.PanelRow;
    var Button = wp.components.Button;
    var TextareaControl = wp.components.TextareaControl;
    registerBlockType('wp-comparison-builder/coupon-popup', {
        title: 'WPC Coupon Popup Box',
        icon: 'tickets',
        category: 'widgets',
        description: 'Create a customizable coupon box or popup modal with a countdown timer, custom colors, and mascot.',
        attributes: {
            id: { type: 'string', default: '' },
            logoUrl: { type: 'string', default: '' },
            title: { type: 'string', default: '' },
            titleColor: { type: 'string', default: '' },
            titleSize: { type: 'string', default: '' },
            subtitle: { type: 'string', default: '' },
            subtitleColor: { type: 'string', default: '' },
            subtitleSize: { type: 'string', default: '' },
            timer: { type: 'string', default: '15m' },
            timerLabel: { type: 'string', default: 'Expires in:' },
            timerBgColor: { type: 'string', default: '' },
            timerTextColor: { type: 'string', default: '' },
            timerSize: { type: 'string', default: '' },
            mascotUrl: { type: 'string', default: '' },
            exclusiveLabel: { type: 'string', default: 'Exclusive Deal' },
            exclusiveBgColor: { type: 'string', default: '' },
            exclusiveTextColor: { type: 'string', default: '' },
            verifiedLabel: { type: 'string', default: 'Verified' },
            verifiedBgColor: { type: 'string', default: '' },
            verifiedTextColor: { type: 'string', default: '' },
            buttonText: { type: 'string', default: 'Show Code' },
            copiedText: { type: 'string', default: 'Copied!' },
            maskText: { type: 'string', default: 'SPECIAL' },
            cardShadow: { type: 'string', default: 'heavy' },
            cardBorderStyle: { type: 'string', default: 'none' },
            cardBorderColor: { type: 'string', default: '' },
            cardBorderWidth: { type: 'string', default: '2px' },
            btnBgColor: { type: 'string', default: '' },
            btnTextColor: { type: 'string', default: '' },
            btnHoverColor: { type: 'string', default: '' },
            btnSize: { type: 'string', default: '' },
            features: { type: 'string', default: '' },
            featuresColor: { type: 'string', default: '' },
            featuresSize: { type: 'string', default: '' },
            couponCode: { type: 'string', default: '' },
            affiliateLink: { type: 'string', default: '' },
            layout: { type: 'string', default: 'modal' },
            copiedBgColor: { type: 'string', default: '' },
            copiedTextColor: { type: 'string', default: '' },
            buttonStyle: { type: 'string', default: 'ticket' },
            clickAction: { type: 'string', default: 'copy_reveal_redirect' },
            showTriggerBtn: { type: 'boolean', default: true },
            triggerText: { type: 'string', default: 'Claim Deal' },
            triggerClass: { type: 'string', default: '' },
            triggerSelector: { type: 'string', default: '' },
            autoOpen: { type: 'string', default: '' },
            exitIntent: { type: 'boolean', default: false },
            triggerFrequency: { type: 'string', default: 'cookie' },
            cookieExpiry: { type: 'string', default: '1' },
            primaryColor: { type: 'string', default: '' },
            itemOverrides: { type: 'string', default: '{}' }
        },

        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            // React States for fetching & searching items
            var [items, setItems] = useState([]);
            var [searchTerm, setSearchTerm] = useState('');
            var [selectedItemName, setSelectedItemName] = useState('');
            var [activeOverrideItemId, setActiveOverrideItemId] = useState('');
            
            // Safe JSON parse for item overrides
            var overridesMap = {};
            try {
                overridesMap = JSON.parse(attributes.itemOverrides || '{}');
            } catch (e) {
                overridesMap = {};
            }

            // Fetch comparison items from REST API
            useEffect(function() {
                wp.apiFetch({ path: '/wpc/v1/items' }).then(function(res) {
                    if (res && res.items) {
                        setItems(res.items);
                        // Resolve selected item name
                        var found = res.items.find(function(item) {
                            return String(item.id) === String(attributes.id);
                        });
                        if (found) {
                            setSelectedItemName(found.name);
                        }
                    }
                }).catch(function(err) {
                    console.error("Error loading comparison items", err);
                });
            }, []);

            // Keep selected item name updated when attributes.id changes
            useEffect(function() {
                if (!attributes.id) {
                    setSelectedItemName('Manual Entry (Custom Data)');
                    return;
                }
                var currentIds = String(attributes.id).split(',').map(function(s) { return s.trim(); }).filter(Boolean);
                var foundNames = [];
                currentIds.forEach(function(cid) {
                    var found = items.find(function(item) {
                        return String(item.id) === cid;
                    });
                    if (found) foundNames.push(found.name);
                });
                
                if (foundNames.length > 0) {
                    setSelectedItemName(foundNames.join(', '));
                } else {
                    setSelectedItemName('Item ID(s): ' + attributes.id);
                }
            }, [attributes.id, items]);

            // Filter items based on search term
            var filteredItems = items.filter(function(item) {
                return item.name.toLowerCase().includes(searchTerm.toLowerCase());
            });

            // Helpers for Per-Item Overrides
            var currentSelectedIds = attributes.id ? String(attributes.id).split(',').map(function(s) { return s.trim(); }).filter(Boolean) : [];
            var overrideOptions = [{ label: '-- Select a company to override --', value: '' }];
            currentSelectedIds.forEach(function(cid) {
                var found = items.find(function(i) { return String(i.id) === cid; });
                if (found) overrideOptions.push({ label: found.name, value: cid });
                else overrideOptions.push({ label: 'ID: ' + cid, value: cid });
            });
            
            var updateOverride = function(key, val) {
                if (!activeOverrideItemId) return;
                var newMap = Object.assign({}, overridesMap);
                if (!newMap[activeOverrideItemId]) newMap[activeOverrideItemId] = {};
                newMap[activeOverrideItemId][key] = val;
                setAttributes({ itemOverrides: JSON.stringify(newMap) });
            };
            
            var activeOverrides = overridesMap[activeOverrideItemId] || {};

            return el(
                'div',
                { className: 'wpc-coupon-block-editor-preview', style: { border: '2px dashed #cbd5e1', padding: '20px', borderRadius: '12px', background: '#f8fafc', position: 'relative' } },
                
                // Sidebar controls
                el(
                    InspectorControls,
                    null,
                    
                    // 1. Item Selection & Search
                    el(
                        PanelBody,
                        { title: 'Data Source (Search & Select)', initialOpen: true },
                        el('div', { style: { marginBottom: '10px', fontWeight: 'bold' } }, 
                            'Current Selection: ' + selectedItemName
                        ),
                        
                        // Search Input
                        el(TextControl, {
                            label: 'Search Comparison Items',
                            placeholder: 'Type to search...',
                            value: searchTerm,
                            onChange: function(val) { setSearchTerm(val); }
                        }),

                        // Scrollable List of matching items
                        el('div', { 
                            style: { 
                                border: '1px solid #e2e8f0', 
                                borderRadius: '6px', 
                                maxHeight: '180px', 
                                overflowY: 'auto', 
                                marginBottom: '10px', 
                                background: '#ffffff' 
                            } 
                        },
                            filteredItems.length === 0 ? el('div', { style: { padding: '8px', fontSize: '12px', color: '#64748b' } }, 'No items found') :
                            filteredItems.map(function(item) {
                                var currentIds = attributes.id ? String(attributes.id).split(',').map(function(s) { return s.trim(); }).filter(Boolean) : [];
                                var isSelected = currentIds.indexOf(String(item.id)) !== -1;
                                return el('div', {
                                    key: item.id,
                                    onClick: function() {
                                        var newIds = currentIds.slice();
                                        var itemIdStr = String(item.id);
                                        var index = newIds.indexOf(itemIdStr);
                                        if (index !== -1) {
                                            newIds.splice(index, 1);
                                        } else {
                                            newIds.push(itemIdStr);
                                        }
                                        setAttributes({ id: newIds.join(',') });
                                    },
                                    style: {
                                        padding: '8px 12px',
                                        fontSize: '13px',
                                        cursor: 'pointer',
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: '8px',
                                        borderBottom: '1px solid #f1f5f9',
                                        background: isSelected ? '#eff6ff' : 'transparent',
                                        fontWeight: isSelected ? 'bold' : 'normal',
                                        color: isSelected ? '#1d4ed8' : '#1e293b'
                                    }
                                },
                                    item.logo ? el('img', { src: item.logo, style: { width: '20px', height: '20px', objectFit: 'contain' } }) : null,
                                    el('span', null, item.name)
                                );
                            })
                        ),

                        // Button to switch to Manual mode
                        el(Button, {
                            isSecondary: true,
                            isSmall: true,
                            onClick: function() {
                                setAttributes({ id: '' });
                            },
                            style: { width: '100%', justifyContent: 'center' }
                        }, 'Clear Selection (Use Manual Entry)'),

                        el(SelectControl, {
                            label: 'Layout Mode',
                            value: attributes.layout,
                            options: [
                                { label: 'Modal Popup', value: 'modal' },
                                { label: 'Inline Box (Embedded in Page)', value: 'inline' }
                            ],
                            onChange: function(val) { setAttributes({ layout: val }); }
                        }),
                        el(PanelRow, null, el('span', null, 'Primary Color Override')),
                        el(ColorPalette, {
                            value: attributes.primaryColor,
                            onChange: function(val) { setAttributes({ primaryColor: val }); }
                        })
                    ),

                    // 1b. Per-Company Overrides
                    el(
                        PanelBody,
                        { title: 'Per-Company Overrides', initialOpen: false },
                        el(SelectControl, {
                            label: 'Select Company to Override',
                            value: activeOverrideItemId,
                            options: overrideOptions,
                            onChange: function(val) { setActiveOverrideItemId(val); }
                        }),
                        activeOverrideItemId ? el(
                            'div',
                            { style: { padding: '10px', background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '4px', marginTop: '10px' } },
                            el('p', { style: { fontSize: '12px', color: '#64748b', marginBottom: '15px' } }, 'These settings apply ONLY to the selected company.'),
                            
                            el('h4', { style: { marginTop: '15px', marginBottom: '10px', borderBottom: '1px solid #ccc', paddingBottom: '5px' } }, 'Core Overrides'),
                            el(TextControl, { label: 'Custom Title', value: activeOverrides.title || '', onChange: function(val) { updateOverride('title', val); } }),
                            el(TextControl, { label: 'Custom Subtitle', value: activeOverrides.subtitle || '', onChange: function(val) { updateOverride('subtitle', val); } }),
                            el(TextControl, { label: 'Custom Coupon Code', value: activeOverrides.couponCode || '', onChange: function(val) { updateOverride('couponCode', val); } }),
                            el(TextControl, { label: 'Custom Affiliate Link', value: activeOverrides.affiliateLink || '', onChange: function(val) { updateOverride('affiliateLink', val); } }),
                            el(TextControl, { label: 'Custom Logo URL', value: activeOverrides.logoUrl || '', onChange: function(val) { updateOverride('logoUrl', val); } }),
                            el(TextControl, { label: 'Custom Mascot URL', value: activeOverrides.mascotUrl || '', onChange: function(val) { updateOverride('mascotUrl', val); } }),
                            
                            el('h4', { style: { marginTop: '15px', marginBottom: '10px', borderBottom: '1px solid #ccc', paddingBottom: '5px' } }, 'Titles & Colors'),
                            el(TextControl, { label: 'Title Font Size', value: activeOverrides.titleSize || '', onChange: function(val) { updateOverride('titleSize', val); } }),
                            el(PanelRow, null, el('span', null, 'Title Color')), el(ColorPalette, { value: activeOverrides.titleColor || '', onChange: function(val) { updateOverride('titleColor', val); } }),
                            el(TextControl, { label: 'Subtitle Font Size', value: activeOverrides.subtitleSize || '', onChange: function(val) { updateOverride('subtitleSize', val); } }),
                            el(PanelRow, null, el('span', null, 'Subtitle Color')), el(ColorPalette, { value: activeOverrides.subtitleColor || '', onChange: function(val) { updateOverride('subtitleColor', val); } }),
                            el(PanelRow, null, el('span', null, 'Primary Color')), el(ColorPalette, { value: activeOverrides.primaryColor || '', onChange: function(val) { updateOverride('primaryColor', val); } }),

                            el('h4', { style: { marginTop: '15px', marginBottom: '10px', borderBottom: '1px solid #ccc', paddingBottom: '5px' } }, 'Features Checklist'),
                            el(TextareaControl, { label: 'Features (Comma separated)', value: activeOverrides.features || '', onChange: function(val) { updateOverride('features', val); } }),
                            el(TextControl, { label: 'Features Font Size', value: activeOverrides.featuresSize || '', onChange: function(val) { updateOverride('featuresSize', val); } }),
                            el(PanelRow, null, el('span', null, 'Features Text Color')), el(ColorPalette, { value: activeOverrides.featuresColor || '', onChange: function(val) { updateOverride('featuresColor', val); } }),

                            el('h4', { style: { marginTop: '15px', marginBottom: '10px', borderBottom: '1px solid #ccc', paddingBottom: '5px' } }, 'Timer Settings'),
                            el(TextControl, { label: 'Custom Timer (e.g. 15m, 2h)', value: activeOverrides.timer || '', onChange: function(val) { updateOverride('timer', val); } }),
                            el(TextControl, { label: 'Timer Label (e.g. Expires in:)', value: activeOverrides.timerLabel || '', onChange: function(val) { updateOverride('timerLabel', val); } }),
                            el(TextControl, { label: 'Timer Font Size', value: activeOverrides.timerSize || '', onChange: function(val) { updateOverride('timerSize', val); } }),
                            el(PanelRow, null, el('span', null, 'Timer Background')), el(ColorPalette, { value: activeOverrides.timerBgColor || '', onChange: function(val) { updateOverride('timerBgColor', val); } }),
                            el(PanelRow, null, el('span', null, 'Timer Text')), el(ColorPalette, { value: activeOverrides.timerTextColor || '', onChange: function(val) { updateOverride('timerTextColor', val); } }),

                            el('h4', { style: { marginTop: '15px', marginBottom: '10px', borderBottom: '1px solid #ccc', paddingBottom: '5px' } }, 'Badges'),
                            el(TextControl, { label: 'Exclusive Label Text', value: activeOverrides.exclusiveLabel || '', onChange: function(val) { updateOverride('exclusiveLabel', val); } }),
                            el(PanelRow, null, el('span', null, 'Exclusive Badge BG')), el(ColorPalette, { value: activeOverrides.exclusiveBgColor || '', onChange: function(val) { updateOverride('exclusiveBgColor', val); } }),
                            el(PanelRow, null, el('span', null, 'Exclusive Badge Text')), el(ColorPalette, { value: activeOverrides.exclusiveTextColor || '', onChange: function(val) { updateOverride('exclusiveTextColor', val); } }),
                            el(TextControl, { label: 'Verified Label Text', value: activeOverrides.verifiedLabel || '', onChange: function(val) { updateOverride('verifiedLabel', val); } }),
                            el(PanelRow, null, el('span', null, 'Verified Badge BG')), el(ColorPalette, { value: activeOverrides.verifiedBgColor || '', onChange: function(val) { updateOverride('verifiedBgColor', val); } }),
                            el(PanelRow, null, el('span', null, 'Verified Badge Text')), el(ColorPalette, { value: activeOverrides.verifiedTextColor || '', onChange: function(val) { updateOverride('verifiedTextColor', val); } }),

                            el('h4', { style: { marginTop: '15px', marginBottom: '10px', borderBottom: '1px solid #ccc', paddingBottom: '5px' } }, 'Button Styles & Action'),
                            el(SelectControl, { label: 'Button Style', value: activeOverrides.buttonStyle || '', options: [ { label: 'Default', value: '' }, { label: 'Ticket Cutout (Dashed)', value: 'ticket' }, { label: 'Solid Block', value: 'solid' }, { label: 'Rounded Pill', value: 'pill' } ], onChange: function(val) { updateOverride('buttonStyle', val); } }),
                            el(SelectControl, { label: 'Click Action', value: activeOverrides.clickAction || '', options: [ { label: 'Default', value: '' }, { label: 'Copy Code, Reveal, and Redirect', value: 'copy_reveal_redirect' }, { label: 'Copy Code and Reveal (No Redirect)', value: 'copy_reveal' }, { label: 'Redirect Only (No Code)', value: 'redirect_only' } ], onChange: function(val) { updateOverride('clickAction', val); } }),
                            el(TextControl, { label: 'Button Text', value: activeOverrides.buttonText || '', onChange: function(val) { updateOverride('buttonText', val); } }),
                            el(TextControl, { label: 'Masked Text (e.g. SPECIAL)', value: activeOverrides.maskText || '', onChange: function(val) { updateOverride('maskText', val); } }),
                            el(TextControl, { label: 'Copied Text', value: activeOverrides.copiedText || '', onChange: function(val) { updateOverride('copiedText', val); } }),
                            el(TextControl, { label: 'Button Font Size', value: activeOverrides.btnSize || '', onChange: function(val) { updateOverride('btnSize', val); } }),
                            el(PanelRow, null, el('span', null, 'Button Background')), el(ColorPalette, { value: activeOverrides.btnBgColor || '', onChange: function(val) { updateOverride('btnBgColor', val); } }),
                            el(PanelRow, null, el('span', null, 'Button Text Color')), el(ColorPalette, { value: activeOverrides.btnTextColor || '', onChange: function(val) { updateOverride('btnTextColor', val); } }),
                            el(PanelRow, null, el('span', null, 'Button Hover BG')), el(ColorPalette, { value: activeOverrides.btnHoverColor || '', onChange: function(val) { updateOverride('btnHoverColor', val); } }),
                            el(PanelRow, null, el('span', null, 'Copied BG')), el(ColorPalette, { value: activeOverrides.copiedBgColor || '', onChange: function(val) { updateOverride('copiedBgColor', val); } }),
                            el(PanelRow, null, el('span', null, 'Copied Text')), el(ColorPalette, { value: activeOverrides.copiedTextColor || '', onChange: function(val) { updateOverride('copiedTextColor', val); } }),

                            el('h4', { style: { marginTop: '15px', marginBottom: '10px', borderBottom: '1px solid #ccc', paddingBottom: '5px' } }, 'Card Styling'),
                            el(SelectControl, { label: 'Card Shadow', value: activeOverrides.cardShadow || '', options: [ { label: 'Default', value: '' }, { label: 'Heavy Default', value: 'heavy' }, { label: 'Soft Shadow', value: 'soft' }, { label: 'No Shadow', value: 'none' } ], onChange: function(val) { updateOverride('cardShadow', val); } }),
                            el(SelectControl, { label: 'Border Style', value: activeOverrides.cardBorderStyle || '', options: [ { label: 'Default', value: '' }, { label: 'None', value: 'none' }, { label: 'Solid', value: 'solid' }, { label: 'Dashed', value: 'dashed' }, { label: 'Dotted', value: 'dotted' } ], onChange: function(val) { updateOverride('cardBorderStyle', val); } }),
                            el(TextControl, { label: 'Border Width', value: activeOverrides.cardBorderWidth || '', onChange: function(val) { updateOverride('cardBorderWidth', val); } }),
                            el(PanelRow, null, el('span', null, 'Border Color')), el(ColorPalette, { value: activeOverrides.cardBorderColor || '', onChange: function(val) { updateOverride('cardBorderColor', val); } })
                        ) : null
                    ),

                    // 2. Custom Brand & logo (For Manual Entry / Global Fallbacks)
                    el(
                        PanelBody,
                        { title: 'Brand Logo & Overrides', initialOpen: false },
                        el(TextControl, {
                            label: 'Custom Logo Image URL',
                            value: attributes.logoUrl,
                            onChange: function(val) { setAttributes({ logoUrl: val }); }
                        }),
                        // Media Library Upload for custom logo
                        el(MediaUploadCheck, null,
                            el(MediaUpload, {
                                onSelect: function(media) {
                                    setAttributes({ logoUrl: media.url });
                                },
                                allowedTypes: ['image'],
                                value: attributes.logoUrl,
                                render: function(obj) {
                                    return el(Button, {
                                        isSecondary: true,
                                        onClick: obj.open,
                                        style: { marginBottom: '15px', width: '100%', justifyContent: 'center' }
                                    }, attributes.logoUrl ? 'Change Logo Image' : 'Select Logo Image');
                                }
                            })
                        ),
                        el(TextControl, {
                            label: 'Coupon Code Override/Manual',
                            value: attributes.couponCode,
                            onChange: function(val) { setAttributes({ couponCode: val }); }
                        }),
                        el(TextControl, {
                            label: 'Affiliate URL Override/Manual',
                            value: attributes.affiliateLink,
                            onChange: function(val) { setAttributes({ affiliateLink: val }); }
                        })
                    ),

                    // 3. Headings & Typography
                    el(
                        PanelBody,
                        { title: 'Titles & Colors', initialOpen: false },
                        el(TextControl, {
                            label: 'Headline Title Override/Manual',
                            value: attributes.title,
                            onChange: function(val) { setAttributes({ title: val }); }
                        }),
                        el(TextControl, {
                            label: 'Title Font Size (e.g. 20px, 1.5rem)',
                            value: attributes.titleSize,
                            onChange: function(val) { setAttributes({ titleSize: val }); }
                        }),
                        el(PanelRow, null, el('span', null, 'Title Color')),
                        el(ColorPalette, {
                            value: attributes.titleColor,
                            onChange: function(val) { setAttributes({ titleColor: val }); }
                        }),
                        el(TextControl, {
                            label: 'Subtitle Override',
                            value: attributes.subtitle,
                            onChange: function(val) { setAttributes({ subtitle: val }); }
                        }),
                        el(TextControl, {
                            label: 'Subtitle Font Size',
                            value: attributes.subtitleSize,
                            onChange: function(val) { setAttributes({ subtitleSize: val }); }
                        }),
                        el(PanelRow, null, el('span', null, 'Subtitle Color')),
                        el(ColorPalette, {
                            value: attributes.subtitleColor,
                            onChange: function(val) { setAttributes({ subtitleColor: val }); }
                        })
                    ),

                    // 4. Features Checklist
                    el(
                        PanelBody,
                        { title: 'Features Checklist', initialOpen: false },
                        el(TextControl, {
                            label: 'Custom Features List',
                            value: attributes.features,
                            help: 'Comma-separated features list (e.g., 30-Day Guarantee, 24/7 Support).',
                            onChange: function(val) { setAttributes({ features: val }); }
                        }),
                        el(TextControl, {
                            label: 'Features Font Size',
                            value: attributes.featuresSize,
                            onChange: function(val) { setAttributes({ featuresSize: val }); }
                        }),
                        el(PanelRow, null, el('span', null, 'Features Text Color')),
                        el(ColorPalette, {
                            value: attributes.featuresColor,
                            onChange: function(val) { setAttributes({ featuresColor: val }); }
                        }),
                        el(SelectControl, {
                            label: 'Card Shadow',
                            value: attributes.cardShadow,
                            options: [
                                { label: 'None', value: 'none' },
                                { label: 'Light', value: 'light' },
                                { label: 'Medium', value: 'medium' },
                                { label: 'Heavy (Default)', value: 'heavy' }
                            ],
                            onChange: function(val) { setAttributes({ cardShadow: val }); }
                        }),
                        el(SelectControl, {
                            label: 'Card Border Style',
                            value: attributes.cardBorderStyle,
                            options: [
                                { label: 'None', value: 'none' },
                                { label: 'Solid', value: 'solid' },
                                { label: 'Dashed', value: 'dashed' },
                                { label: 'Dotted', value: 'dotted' }
                            ],
                            onChange: function(val) { setAttributes({ cardBorderStyle: val }); }
                        }),
                        attributes.cardBorderStyle !== 'none' && el(SelectControl, {
                            label: 'Card Border Width',
                            value: attributes.cardBorderWidth,
                            options: [
                                { label: '1px', value: '1px' },
                                { label: '2px', value: '2px' },
                                { label: '3px', value: '3px' },
                                { label: '4px', value: '4px' },
                                { label: '5px', value: '5px' }
                            ],
                            onChange: function(val) { setAttributes({ cardBorderWidth: val }); }
                        }),
                        attributes.cardBorderStyle !== 'none' && el(PanelRow, null, el('span', null, 'Card Border Color')),
                        attributes.cardBorderStyle !== 'none' && el(ColorPalette, {
                            value: attributes.cardBorderColor,
                            onChange: function(val) { setAttributes({ cardBorderColor: val }); }
                        })
                    ),

                    // 5. Timer Settings
                    el(
                        PanelBody,
                        { title: 'Timer Settings', initialOpen: false },
                        el(TextControl, {
                            label: 'Timer Duration',
                            value: attributes.timer,
                            placeholder: 'e.g. 15m, 2h, 30s, 1h30m',
                            help: 'Format examples: 30s = 30 seconds · 15m = 15 minutes · 2h = 2 hours · off = hide timer. The timer loops when it reaches zero.',
                            onChange: function(val) { setAttributes({ timer: val }); }
                        }),
                        el(TextControl, {
                            label: 'Timer Label',
                            value: attributes.timerLabel,
                            onChange: function(val) { setAttributes({ timerLabel: val }); }
                        }),
                        el(TextControl, {
                            label: 'Timer Font Size',
                            value: attributes.timerSize,
                            onChange: function(val) { setAttributes({ timerSize: val }); }
                        }),
                        el(PanelRow, null, el('span', null, 'Timer Block Background')),
                        el(ColorPalette, {
                            value: attributes.timerBgColor,
                            onChange: function(val) { setAttributes({ timerBgColor: val }); }
                        }),
                        el(PanelRow, null, el('span', null, 'Timer Text Color')),
                        el(ColorPalette, {
                            value: attributes.timerTextColor,
                            onChange: function(val) { setAttributes({ timerTextColor: val }); }
                        })
                    ),

                    // 6. Badges (Exclusive & Verified)
                    el(
                        PanelBody,
                        { title: 'Badges', initialOpen: false },
                        el(TextControl, {
                            label: 'Exclusive Deal Text',
                            value: attributes.exclusiveLabel,
                            onChange: function(val) { setAttributes({ exclusiveLabel: val }); }
                        }),
                        el(PanelRow, null, el('span', null, 'Exclusive Badge BG')),
                        el(ColorPalette, {
                            value: attributes.exclusiveBgColor,
                            onChange: function(val) { setAttributes({ exclusiveBgColor: val }); }
                        }),
                        el(PanelRow, null, el('span', null, 'Exclusive Badge Text')),
                        el(ColorPalette, {
                            value: attributes.exclusiveTextColor,
                            onChange: function(val) { setAttributes({ exclusiveTextColor: val }); }
                        }),
                        el(TextControl, {
                            label: 'Verified Text',
                            value: attributes.verifiedLabel,
                            onChange: function(val) { setAttributes({ verifiedLabel: val }); }
                        }),
                        el(PanelRow, null, el('span', null, 'Verified Badge BG')),
                        el(ColorPalette, {
                            value: attributes.verifiedBgColor,
                            onChange: function(val) { setAttributes({ verifiedBgColor: val }); }
                        }),
                        el(PanelRow, null, el('span', null, 'Verified Badge Text')),
                        el(ColorPalette, {
                            value: attributes.verifiedTextColor,
                            onChange: function(val) { setAttributes({ verifiedTextColor: val }); }
                        })
                    ),

                    // 7. Action Button Styles & Behavior
                    el(
                        PanelBody,
                        { title: 'Button Styles & Action', initialOpen: false },
                        el(SelectControl, {
                            label: 'Button Design Preset',
                            value: attributes.buttonStyle,
                            options: [
                                { label: '🎫 Premium Ticket (with Cutouts)', value: 'ticket' },
                                { label: '💊 Solid Rounded Pill', value: 'solid' },
                                { label: '⭕ Outline Pill', value: 'outline' },
                                { label: '✨ Glowing Pulse Pill', value: 'glow' }
                            ],
                            onChange: function(val) { setAttributes({ buttonStyle: val }); }
                        }),
                        el(SelectControl, {
                            label: 'On Click Behavior',
                            value: attributes.clickAction,
                            options: [
                                { label: 'Copy to Clipboard, Reveal Code & Redirect', value: 'copy_reveal_redirect' },
                                { label: 'Copy to Clipboard & Reveal Code Only', value: 'copy_reveal_only' },
                                { label: 'Redirect to Affiliate Link Only', value: 'redirect_only' }
                            ],
                            onChange: function(val) { setAttributes({ clickAction: val }); }
                        }),
                        el(TextControl, {
                            label: 'Show Code Button Text',
                            value: attributes.buttonText,
                            onChange: function(val) { setAttributes({ buttonText: val }); }
                        }),
                        el(TextControl, {
                            label: 'Copied Success Text',
                            value: attributes.copiedText,
                            onChange: function(val) { setAttributes({ copiedText: val }); }
                        }),
                        el(TextControl, {
                            label: 'Placeholder Code Mask',
                            value: attributes.maskText,
                            help: 'Fake placeholder word for the hidden code peek (e.g. SPECIAL becomes •••IAL)',
                            onChange: function(val) { setAttributes({ maskText: val }); }
                        }),
                        el(PanelRow, null, el('span', null, 'Copied Button Background')),
                        el(ColorPalette, {
                            value: attributes.copiedBgColor,
                            onChange: function(val) { setAttributes({ copiedBgColor: val }); }
                        }),
                        el(PanelRow, null, el('span', null, 'Copied Button Text Color')),
                        el(ColorPalette, {
                            value: attributes.copiedTextColor,
                            onChange: function(val) { setAttributes({ copiedTextColor: val }); }
                        }),
                        el(TextControl, {
                            label: 'Button Font Size',
                            value: attributes.btnSize,
                            onChange: function(val) { setAttributes({ btnSize: val }); }
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
                        }),
                        el(PanelRow, null, el('span', null, 'Button Hover Background')),
                        el(ColorPalette, {
                            value: attributes.btnHoverColor,
                            onChange: function(val) { setAttributes({ btnHoverColor: val }); }
                        })
                    ),

                    // 8. Mascot Options
                    el(
                        PanelBody,
                        { title: 'Mascot Options', initialOpen: false },
                        el(TextControl, {
                            label: 'Mascot Image URL',
                            value: attributes.mascotUrl,
                            onChange: function(val) { setAttributes({ mascotUrl: val }); }
                        }),
                        // Media Library Upload for custom mascot
                        el(MediaUploadCheck, null,
                            el(MediaUpload, {
                                onSelect: function(media) {
                                    setAttributes({ mascotUrl: media.url });
                                },
                                allowedTypes: ['image'],
                                value: attributes.mascotUrl,
                                render: function(obj) {
                                    return el(Button, {
                                        isSecondary: true,
                                        onClick: obj.open,
                                        style: { width: '100%', justifyContent: 'center' }
                                    }, attributes.mascotUrl ? 'Change Mascot Image' : 'Select Mascot Image');
                                }
                            })
                        )
                    ),

                    // 9. Popup Trigger Settings (Only for Modal Layout)
                    attributes.layout === 'modal' && el(
                        PanelBody,
                        { title: 'Popup Trigger Settings', initialOpen: false },
                        el(ToggleControl, {
                            label: 'Show Trigger Button on Page',
                            checked: attributes.showTriggerBtn,
                            help: 'If disabled, the Claim Deal trigger button is hidden. The popup will only launch automatically or via exit-intent.',
                            onChange: function(val) { setAttributes({ showTriggerBtn: val }); }
                        }),
                        attributes.showTriggerBtn && el(TextControl, {
                            label: 'Trigger Button Text',
                            value: attributes.triggerText,
                            onChange: function(val) { setAttributes({ triggerText: val }); }
                        }),
                        attributes.showTriggerBtn && el(TextControl, {
                            label: 'Trigger Button Custom CSS Class',
                            value: attributes.triggerClass,
                            onChange: function(val) { setAttributes({ triggerClass: val }); }
                        }),
                        el(TextControl, {
                            label: 'Custom Trigger Element Selector',
                            value: attributes.triggerSelector,
                            help: 'e.g. .claim-deal-btn. Bind click event to custom elements created with page builders.',
                            onChange: function(val) { setAttributes({ triggerSelector: val }); }
                        }),
                        el(TextControl, {
                            label: 'Auto Open Delay',
                            value: attributes.autoOpen,
                            placeholder: 'e.g. 5 (5 seconds), 30, 120',
                            help: 'Enter number of SECONDS before popup shows automatically. Examples: 5 = after 5 sec · 30 = after 30 sec · 120 = after 2 min. Leave empty to disable auto-open.',
                            onChange: function(val) { setAttributes({ autoOpen: val }); }
                        }),
                        el(ToggleControl, {
                            label: 'Trigger on Exit Intent',
                            checked: attributes.exitIntent,
                            help: 'Trigger popup when cursor leaves browser window.',
                            onChange: function(val) { setAttributes({ exitIntent: val }); }
                        }),
                        el(SelectControl, {
                            label: 'Auto-Trigger Frequency',
                            value: attributes.triggerFrequency,
                            options: [
                                { label: 'Once Every X Days (Use Cookie)', value: 'cookie' },
                                { label: 'Once Per Page Load (Local Variable)', value: 'page' },
                                { label: 'Once Per Browser Session', value: 'session' },
                                { label: 'Every Time (Aggressive)', value: 'always' }
                            ],
                            help: 'How often should Auto-Open or Exit Intent trigger if the user previously closed the popup?',
                            onChange: function(val) { setAttributes({ triggerFrequency: val }); }
                        }),
                        attributes.triggerFrequency === 'cookie' && el(TextControl, {
                            label: 'Cookie Expiry (days)',
                            value: attributes.cookieExpiry,
                            help: 'Prevent popup from auto-opening again to the same user for X days.',
                            onChange: function(val) { setAttributes({ cookieExpiry: val }); }
                        })
                    )
                ),

                // Canvas Editor Preview Box
                el('div', { style: { fontWeight: 'bold', fontSize: '14px', marginBottom: '8px', color: '#1e3a8a', display: 'flex', alignItems: 'center', gap: '6px' } }, 
                    el('span', { style: { fontSize: '18px' } }, '🎟️'),
                    el('span', null, 'WPC Coupon Popup Box Settings')
                ),
                el('div', { style: { padding: '12px', background: '#ffffff', borderRadius: '8px', border: '1px solid #e2e8f0' } }, 
                    el('div', { style: { fontSize: '13px', fontWeight: 'bold', color: '#1e293b' } }, 
                        'Source: ' + selectedItemName
                    ),
                    el('div', { style: { fontSize: '12px', color: '#64748b', marginTop: '4px' } }, 
                        'Layout: ' + attributes.layout + 
                        ' | Button Style: ' + attributes.buttonStyle + 
                        ' | Click Action: ' + attributes.clickAction
                    ),
                    !attributes.showTriggerBtn && attributes.layout === 'modal' && el('div', { style: { fontSize: '11px', color: '#b91c1c', marginTop: '4px', fontWeight: '500' } },
                        '⚠️ Trigger button is hidden on page. Modal will show via Auto-Open/Exit-Intent/Selector.'
                    )
                )
            );
        },

        save: function() {
            return null; // dynamic rendering in PHP
        }
    });
})(window.wp);
