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
    var RangeControl = wp.components.RangeControl;
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
            cardBgColor: { type: 'string', default: '#ffffff' },
            cardPadding: { type: 'string', default: '36px 30px' },
            cardBorderRadius: { type: 'string', default: '24px' },
            leftWidth: { type: 'string', default: '40%' },
            leftBgColor: { type: 'string', default: 'transparent' },
            leftPadding: { type: 'string', default: '0 4px 0 0' },
            dividerShow: { type: 'boolean', default: false },
            dividerStyle: { type: 'string', default: 'dashed' },
            dividerColor: { type: 'string', default: '#e2e8f0' },
            dividerWidth: { type: 'string', default: '1px' },
            mascotWidth: { type: 'string', default: '160px' },
            mascotBottom: { type: 'string', default: '-5px' },
            mascotPosition: { type: 'string', default: 'right' },
            mascotOffset: { type: 'string', default: '25px' },
            mascotBehind: { type: 'boolean', default: true },
            mascotOpacity: { type: 'string', default: '1' },
            timerBlockRadius: { type: 'string', default: '6px' },
            timerBlockBorderWidth: { type: 'string', default: '1px' },
            timerBlockBorderColor: { type: 'string', default: '#e5e7eb' },
            timerBlockPadding: { type: 'string', default: '8px 10px' },
            timerBlockShadow: { type: 'string', default: 'light' },
            badgeRadius: { type: 'string', default: '9999px' },
            badgeBorderWidth: { type: 'string', default: '1px' },
            badgePadding: { type: 'string', default: '4px 10px' },
            closeBtnBg: { type: 'string', default: 'transparent' },
            closeBtnColor: { type: 'string', default: '#94a3b8' },
            closeBtnHoverColor: { type: 'string', default: '#0f172a' },
            itemOverrides: { type: 'string', default: '{}' },
            promoBannerText: { type: 'string', default: '' },
            promoBannerBg: { type: 'string', default: '#fee2e2' },
            promoBannerColor: { type: 'string', default: '#b91c1c' },
            groupMode: { type: 'string', default: 'independent' }
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

            var handleElementClick = function(panelTitle, fieldLabel) {
                var targetPanel = panelTitle;
                if (activeOverrideItemId) {
                    targetPanel = 'Per-Company Overrides';
                }

                // Find panel toggle button and click it if collapsed
                var buttons = document.querySelectorAll('.components-panel__body-title button, .components-panel__body h2 button');
                var foundBtn = null;
                for (var i = 0; i < buttons.length; i++) {
                    var btn = buttons[i];
                    var text = btn.textContent.trim().toLowerCase();
                    if (text.indexOf(targetPanel.toLowerCase()) !== -1) {
                        foundBtn = btn;
                        break;
                    }
                }

                if (foundBtn) {
                    if (foundBtn.getAttribute('aria-expanded') === 'false') {
                        foundBtn.click();
                    }
                    foundBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }

                // Focus/scroll to input matching fieldLabel
                if (fieldLabel) {
                    setTimeout(function() {
                        var labels = document.querySelectorAll('.components-base-control__label, .components-panel__body label, .components-panel__body h4');
                        var foundLabel = null;
                        for (var j = 0; j < labels.length; j++) {
                            var label = labels[j];
                            var labelText = label.textContent.trim().toLowerCase();
                            if (labelText.indexOf(fieldLabel.toLowerCase()) !== -1) {
                                foundLabel = label;
                                break;
                            }
                        }

                        if (foundLabel) {
                            var inputId = foundLabel.getAttribute('for');
                            if (inputId) {
                                var input = document.getElementById(inputId);
                                if (input) {
                                    input.focus();
                                    input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    return;
                                }
                            }
                            foundLabel.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }, 250);
                }
            };
            
            var activeOverride = overridesMap[activeOverrideItemId] || {};
            
            // Cascading lookup for active values (considering selected data source or overrides manually set)
            
            // Determine active preview item if any
            var previewItemId = activeOverrideItemId;
            if (!previewItemId && attributes.id) {
                var ids = String(attributes.id).split(',').map(function(s) { return s.trim(); }).filter(Boolean);
                if (ids.length > 0) {
                    previewItemId = ids[0];
                }
            }
            var previewItem = items.find(function(item) {
                return String(item.id) === String(previewItemId);
            });
            
            var getVal = function(attrKey, overrideKey, itemKey, defaultVal) {
                var ok = overrideKey || attrKey;
                var ik = itemKey || attrKey;
                // 1. Check manual override
                if (activeOverride[ok] !== undefined && activeOverride[ok] !== '') {
                    return activeOverride[ok];
                }
                // 2. Check previewItem design overrides or standard fields
                if (previewItem) {
                    var designOverrides = previewItem.design_overrides || {};
                    // Map camelCase to snake_case equivalent just in case
                    var snakeKey = ik.replace(/([A-Z])/g, "_$1").toLowerCase();
                    if (designOverrides[ik] !== undefined && designOverrides[ik] !== '') {
                        return designOverrides[ik];
                    }
                    if (designOverrides[snakeKey] !== undefined && designOverrides[snakeKey] !== '') {
                        return designOverrides[snakeKey];
                    }
                    if (previewItem[ik] !== undefined && previewItem[ik] !== '') {
                        return previewItem[ik];
                    }
                }
                // 3. Fallback to attributes
                return attributes[attrKey] !== undefined && attributes[attrKey] !== '' ? attributes[attrKey] : defaultVal;
            };

            var activeLogo = getVal('logoUrl', 'logoUrl', 'logo', '');
            var activeTitleName = previewItem ? previewItem.name : 'WooCommerce';
            var activeTitle = activeOverride.title || attributes.title || ('Get Exclusive ' + activeTitleName + ' Deal');
            
            // Subtitle / Description
            var rawDesc = (previewItem && previewItem.description) ? previewItem.description : '';
            var trimmedDesc = rawDesc ? (rawDesc.split(' ').slice(0, 20).join(' ') + (rawDesc.split(' ').length > 20 ? '...' : '')) : '';
            var activeSubtitle = activeOverride.description || trimmedDesc || attributes.subtitle || 'Copy this exclusive coupon and save big today on all plugin extensions.';
            
            // Mascot Options
            var activeMascotUrl = getVal('mascotUrl', 'mascotUrl', 'mascot', '');
            var activeMascotWidth = getVal('mascotWidth', 'mascotWidth', 'mascotWidth', '160px');
            var activeMascotBottom = getVal('mascotBottom', 'mascotBottom', 'mascotBottom', '-5px');
            var activeMascotPosition = getVal('mascotPosition', 'mascotPosition', 'mascotPosition', 'right');
            var activeMascotOffset = getVal('mascotOffset', 'mascotOffset', 'mascotOffset', '25px');
            var activeMascotBehind = (function() {
                var val = getVal('mascotBehind', 'mascotBehind', 'masc_behind', null);
                if (val === null) return true;
                return val === true || val === 'true' || val === 1 || val === '1';
            })();
            var activeMascotOpacity = getVal('mascotOpacity', 'mascotOpacity', 'mascotOpacity', '1');
            
            // Global Layout Styling Cascading Lookup
            var activeCardBgColor = getVal('cardBgColor', 'cardBgColor', 'cardBgColor', '#ffffff');
            var activeCardPadding = getVal('cardPadding', 'cardPadding', 'cardPadding', '36px 30px');
            var activeCardBorderRadius = getVal('cardBorderRadius', 'cardBorderRadius', 'cardBorderRadius', '24px');
            var activeLeftWidth = getVal('leftWidth', 'leftWidth', 'leftWidth', '40%');
            var activeLeftBgColor = getVal('leftBgColor', 'leftBgColor', 'leftBgColor', 'transparent');
            var activeLeftPadding = getVal('leftPadding', 'leftPadding', 'leftPadding', '0 4px 0 0');
            
            var activeDividerShow = (function() {
                var val = getVal('dividerShow', 'dividerShow', 'divider_show', null);
                if (val === null) return false;
                return val === true || val === 'true' || val === 1 || val === '1';
            })();
            var activeDividerStyle = getVal('dividerStyle', 'dividerStyle', 'dividerStyle', 'dashed');
            var activeDividerColor = getVal('dividerColor', 'dividerColor', 'dividerColor', '#e2e8f0');
            var activeDividerWidth = getVal('dividerWidth', 'dividerWidth', 'dividerWidth', '1px');
            
            var activeTimerBlockRadius = getVal('timerBlockRadius', 'timerBlockRadius', 'timerBlockRadius', '6px');
            var activeTimerBlockBorderWidth = getVal('timerBlockBorderWidth', 'timerBlockBorderWidth', 'timerBlockBorderWidth', '1px');
            var activeTimerBlockBorderColor = getVal('timerBlockBorderColor', 'timerBlockBorderColor', 'timerBlockBorderColor', '#e5e7eb');
            var activeTimerBlockPadding = getVal('timerBlockPadding', 'timerBlockPadding', 'timerBlockPadding', '8px 10px');
            var activeTimerBlockShadow = getVal('timerBlockShadow', 'timerBlockShadow', 'timerBlockShadow', 'light');
            var activeTimerBgColor = getVal('timerBgColor', 'timerBgColor', 'timerBgColor', '#f8fafc');
            var activeTimerTextColor = getVal('timerTextColor', 'timerTextColor', 'timerTextColor', '#0f172a');
            
            var activeBadgeRadius = getVal('badgeRadius', 'badgeRadius', 'badgeRadius', '9999px');
            var activeBadgeBorderWidth = getVal('badgeBorderWidth', 'badgeBorderWidth', 'badgeBorderWidth', '1px');
            var activeBadgePadding = getVal('badgePadding', 'badgePadding', 'badgePadding', '4px 10px');
            
            var activeCloseBtnBg = getVal('closeBtnBg', 'closeBtnBg', 'closeBtnBg', 'transparent');
            var activeCloseBtnColor = getVal('closeBtnColor', 'closeBtnColor', 'closeBtnColor', '#94a3b8');
            
            var activeExclusiveLabel = getVal('exclusiveLabel', 'exclusiveLabel', 'exclusiveLabel', 'Exclusive Deal');
            var activeExclusiveBgColor = getVal('exclusiveBgColor', 'exclusiveBgColor', 'exclusiveBgColor', '#fef3c7');
            var activeExclusiveTextColor = getVal('exclusiveTextColor', 'exclusiveTextColor', 'exclusiveTextColor', '#d97706');
            
            var activeVerifiedLabel = getVal('verifiedLabel', 'verifiedLabel', 'verifiedLabel', 'Verified');
            var activeVerifiedBgColor = getVal('verifiedBgColor', 'verifiedBgColor', 'verifiedBgColor', '#dcfce7');
            var activeVerifiedTextColor = getVal('verifiedTextColor', 'verifiedTextColor', 'verifiedTextColor', '#15803d');
            
            var activeButtonStyle = getVal('buttonStyle', 'buttonStyle', 'buttonStyle', 'ticket');
            var activeBtnBgColor = getVal('btnBgColor', 'btnBgColor', 'primary', '#2563eb');
            var activeBtnTextColor = getVal('btnTextColor', 'btnTextColor', 'btnTextColor', '#ffffff');
            var activeBtnSize = getVal('btnSize', 'btnSize', 'btnSize', '13px');
            var activeButtonText = getVal('buttonText', 'buttonText', 'buttonText', 'Show Code');
            
            var activePrimaryColor = getVal('primaryColor', 'primaryColor', 'primaryColor', '#0f172a');
            var activeCardShadow = getVal('cardShadow', 'cardShadow', 'cardShadow', 'heavy');
            var activeCardBorderStyle = getVal('cardBorderStyle', 'cardBorderStyle', 'cardBorderStyle', 'none');
            var activeCardBorderColor = getVal('cardBorderColor', 'cardBorderColor', 'cardBorderColor', '#e2e8f0');
            var activeCardBorderWidth = getVal('cardBorderWidth', 'cardBorderWidth', 'cardBorderWidth', '2px');

            // Features Checklist lookups
            var activeFeaturesColor = getVal('featuresColor', 'featuresColor', 'featuresColor', '');
            var activeFeaturesSize = getVal('featuresSize', 'featuresSize', 'featuresSize', '11px');
            var activeFeatures = (function() {
                if (activeOverride.features !== undefined && activeOverride.features !== '') {
                    return activeOverride.features;
                }
                if (previewItem && previewItem.pros) {
                    if (Array.isArray(previewItem.pros)) {
                        return previewItem.pros.join(', ');
                    }
                    if (typeof previewItem.pros === 'string') {
                        return previewItem.pros;
                    }
                }
                if (attributes.features !== undefined && attributes.features !== '') {
                    return attributes.features;
                }
                return '30-Day Money-back Guarantee, Verified Premium Provider, 24/7 Priority Support';
            })();
            var featuresArray = activeFeatures.split(',').map(function(s) { return s.trim(); }).filter(Boolean);

            // Timer display values parser
            var activeTimer = getVal('timer', 'timer', 'timer', '15m');
            var timerDisplay = (function() {
                var timerVal = String(activeTimer).trim().toLowerCase();
                if (timerVal === 'off') return null;
                
                var seconds = 900; // default 15m
                var regex = /(\d+)\s*(y|mo|d|h|m|s)/g;
                var matches = [];
                var match;
                while ((match = regex.exec(timerVal)) !== null) {
                    matches.push(match);
                }
                
                if (matches.length > 0) {
                    var total = 0;
                    matches.forEach(function(m) {
                        var val = parseInt(m[1], 10);
                        var unit = m[2];
                        if (unit === 'y') total += val * 31536000;
                        else if (unit === 'mo') total += val * 2592000;
                        else if (unit === 'd') total += val * 86400;
                        else if (unit === 'h') total += val * 3600;
                        else if (unit === 'm') total += val * 60;
                        else if (unit === 's') total += val;
                    });
                    if (total > 0) seconds = total;
                } else {
                    var valInt = parseInt(timerVal, 10);
                    if (!isNaN(valInt) && valInt > 0) {
                        seconds = valInt;
                    }
                }
                
                var days = Math.floor(seconds / 86400);
                var hours = Math.floor((seconds % 86400) / 3600);
                var minutes = Math.floor((seconds % 3600) / 60);
                var secs = seconds % 60;
                
                var blocks = [];
                if (days > 0) {
                    blocks.push({ label: 'd', val: String(days).padStart(2, '0') });
                }
                if (hours > 0 || days > 0) {
                    blocks.push({ label: 'h', val: String(hours).padStart(2, '0') });
                }
                blocks.push({ label: 'm', val: String(minutes).padStart(2, '0') });
                blocks.push({ label: 's', val: String(secs).padStart(2, '0') });
                
                return blocks;
            })();

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
                            el(PanelRow, null, el('span', null, 'Border Color')), el(ColorPalette, { value: activeOverrides.cardBorderColor || '', onChange: function(val) { updateOverride('cardBorderColor', val); } }),

                            el('h4', { style: { marginTop: '15px', marginBottom: '10px', borderBottom: '1px solid #ccc', paddingBottom: '5px' } }, 'Advanced Design Overrides'),
                            el(PanelRow, null, el('span', null, 'Card BG Color')), el(ColorPalette, { value: activeOverrides.cardBgColor || '', onChange: function(val) { updateOverride('cardBgColor', val); } }),
                            el(TextControl, { label: 'Card Padding', value: activeOverrides.cardPadding || '', onChange: function(val) { updateOverride('cardPadding', val); } }),
                            el(TextControl, { label: 'Card Border Radius', value: activeOverrides.cardBorderRadius || '', onChange: function(val) { updateOverride('cardBorderRadius', val); } }),
                            
                            el(TextControl, { label: 'Left Column Width (e.g. 40%)', value: activeOverrides.leftWidth || '', onChange: function(val) { updateOverride('leftWidth', val); } }),
                            el(PanelRow, null, el('span', null, 'Left Column BG')), el(ColorPalette, { value: activeOverrides.leftBgColor || '', onChange: function(val) { updateOverride('leftBgColor', val); } }),
                            el(TextControl, { label: 'Left Column Padding', value: activeOverrides.leftPadding || '', onChange: function(val) { updateOverride('leftPadding', val); } }),
                            
                            el(ToggleControl, { label: 'Show Divider Line', checked: activeOverrides.dividerShow || false, onChange: function(val) { updateOverride('dividerShow', val); } }),
                            el(SelectControl, { label: 'Divider Line Style', value: activeOverrides.dividerStyle || 'dashed', options: [ { label: 'Solid', value: 'solid' }, { label: 'Dashed', value: 'dashed' }, { label: 'Dotted', value: 'dotted' } ], onChange: function(val) { updateOverride('dividerStyle', val); } }),
                            el(PanelRow, null, el('span', null, 'Divider Color')), el(ColorPalette, { value: activeOverrides.dividerColor || '', onChange: function(val) { updateOverride('dividerColor', val); } }),
                            el(TextControl, { label: 'Divider Width', value: activeOverrides.dividerWidth || '', onChange: function(val) { updateOverride('dividerWidth', val); } }),
                            
                            el(TextControl, { label: 'Mascot Width', value: activeOverrides.mascotWidth || '', onChange: function(val) { updateOverride('mascotWidth', val); } }),
                            el(TextControl, { label: 'Mascot Bottom Offset', value: activeOverrides.mascotBottom || '', onChange: function(val) { updateOverride('mascotBottom', val); } }),
                            el(SelectControl, { label: 'Mascot Position', value: activeOverrides.mascotPosition || 'right', options: [ { label: 'Right Corner', value: 'right' }, { label: 'Left Corner', value: 'left' } ], onChange: function(val) { updateOverride('mascotPosition', val); } }),
                            el(TextControl, { label: 'Mascot Left/Right Offset', value: activeOverrides.mascotOffset || '', onChange: function(val) { updateOverride('mascotOffset', val); } }),
                            el(ToggleControl, { label: 'Mascot Behind Text', checked: activeOverrides.mascotBehind !== undefined ? activeOverrides.mascotBehind : true, onChange: function(val) { updateOverride('mascotBehind', val); } }),
                            el(RangeControl, {
                                label: 'Mascot Opacity Override',
                                value: activeOverrides.mascotOpacity !== undefined ? parseFloat(activeOverrides.mascotOpacity) : 1,
                                min: 0,
                                max: 1,
                                step: 0.1,
                                onChange: function(val) { updateOverride('mascotOpacity', String(val)); }
                            }),
                            
                            el(TextControl, { label: 'Timer Block Border Radius', value: activeOverrides.timerBlockRadius || '', onChange: function(val) { updateOverride('timerBlockRadius', val); } }),
                            el(TextControl, { label: 'Timer Block Border Width', value: activeOverrides.timerBlockBorderWidth || '', onChange: function(val) { updateOverride('timerBlockBorderWidth', val); } }),
                            el(PanelRow, null, el('span', null, 'Timer Block Border Color')), el(ColorPalette, { value: activeOverrides.timerBlockBorderColor || '', onChange: function(val) { updateOverride('timerBlockBorderColor', val); } }),
                            el(TextControl, { label: 'Timer Block Padding', value: activeOverrides.timerBlockPadding || '', onChange: function(val) { updateOverride('timerBlockPadding', val); } }),
                            el(SelectControl, { label: 'Timer Block Shadow', value: activeOverrides.timerBlockShadow || 'light', options: [ { label: 'None', value: 'none' }, { label: 'Light', value: 'light' }, { label: 'Medium', value: 'medium' }, { label: 'Heavy', value: 'heavy' } ], onChange: function(val) { updateOverride('timerBlockShadow', val); } }),
                            
                            el(TextControl, { label: 'Badge Border Radius', value: activeOverrides.badgeRadius || '', onChange: function(val) { updateOverride('badgeRadius', val); } }),
                            el(TextControl, { label: 'Badge Border Width', value: activeOverrides.badgeBorderWidth || '', onChange: function(val) { updateOverride('badgeBorderWidth', val); } }),
                            el(TextControl, { label: 'Badge Padding', value: activeOverrides.badgePadding || '', onChange: function(val) { updateOverride('badgePadding', val); } }),
                            
                            el(PanelRow, null, el('span', null, 'Close Button BG')), el(ColorPalette, { value: activeOverrides.closeBtnBg || '', onChange: function(val) { updateOverride('closeBtnBg', val); } }),
                            el(PanelRow, null, el('span', null, 'Close Button Color')), el(ColorPalette, { value: activeOverrides.closeBtnColor || '', onChange: function(val) { updateOverride('closeBtnColor', val); } })
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
                            label: 'Promo Banner Text',
                            value: attributes.promoBannerText,
                            help: 'Optional text box above the title (e.g. Claim 50% OFF Now). Leave empty to hide.',
                            onChange: function(val) { setAttributes({ promoBannerText: val }); }
                        }),
                        attributes.promoBannerText && el(PanelRow, null, el('span', null, 'Promo Banner Background')),
                        attributes.promoBannerText && el(ColorPalette, {
                            value: attributes.promoBannerBg,
                            onChange: function(val) { setAttributes({ promoBannerBg: val }); }
                        }),
                        attributes.promoBannerText && el(PanelRow, null, el('span', null, 'Promo Banner Text Color')),
                        attributes.promoBannerText && el(ColorPalette, {
                            value: attributes.promoBannerColor,
                            onChange: function(val) { setAttributes({ promoBannerColor: val }); }
                        }),
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

                    // 8b. Global Design Customizer (Figma-like controls)
                    el(
                        PanelBody,
                        { title: 'Global Design Customizer', initialOpen: false },
                        el('h4', { style: { marginBottom: '10px', fontWeight: 'bold' } }, 'Card Container'),
                        el(PanelRow, null, el('span', null, 'Card Background Color')),
                        el(ColorPalette, {
                            value: attributes.cardBgColor,
                            onChange: function(val) { setAttributes({ cardBgColor: val }); }
                        }),
                        el(TextControl, {
                            label: 'Card Padding',
                            value: attributes.cardPadding,
                            placeholder: 'e.g. 36px 30px',
                            onChange: function(val) { setAttributes({ cardPadding: val }); }
                        }),
                        el(TextControl, {
                            label: 'Card Border Radius',
                            value: attributes.cardBorderRadius,
                            placeholder: 'e.g. 24px',
                            onChange: function(val) { setAttributes({ cardBorderRadius: val }); }
                        }),

                        el('h4', { style: { marginTop: '15px', marginBottom: '10px', fontWeight: 'bold', borderTop: '1px solid #eee', paddingTop: '10px' } }, 'Left Column & Vertical Divider'),
                        el(TextControl, {
                            label: 'Left Column Width (e.g. 40%)',
                            value: attributes.leftWidth,
                            onChange: function(val) { setAttributes({ leftWidth: val }); }
                        }),
                        el(PanelRow, null, el('span', null, 'Left Column BG')),
                        el(ColorPalette, {
                            value: attributes.leftBgColor,
                            onChange: function(val) { setAttributes({ leftBgColor: val }); }
                        }),
                        el(TextControl, {
                            label: 'Left Column Padding',
                            value: attributes.leftPadding,
                            onChange: function(val) { setAttributes({ leftPadding: val }); }
                        }),
                        el(ToggleControl, {
                            label: 'Show Divider Line',
                            checked: attributes.dividerShow,
                            onChange: function(val) { setAttributes({ dividerShow: val }); }
                        }),
                        attributes.dividerShow && el(SelectControl, {
                            label: 'Divider Line Style',
                            value: attributes.dividerStyle,
                            options: [
                                { label: 'Solid', value: 'solid' },
                                { label: 'Dashed', value: 'dashed' },
                                { label: 'Dotted', value: 'dotted' }
                            ],
                            onChange: function(val) { setAttributes({ dividerStyle: val }); }
                        }),
                        attributes.dividerShow && el(PanelRow, null, el('span', null, 'Divider Color')),
                        attributes.dividerShow && el(ColorPalette, {
                            value: attributes.dividerColor,
                            onChange: function(val) { setAttributes({ dividerColor: val }); }
                        }),
                        attributes.dividerShow && el(TextControl, {
                            label: 'Divider Width',
                            value: attributes.dividerWidth,
                            onChange: function(val) { setAttributes({ dividerWidth: val }); }
                        }),

                        el('h4', { style: { marginTop: '15px', marginBottom: '10px', fontWeight: 'bold', borderTop: '1px solid #eee', paddingTop: '10px' } }, 'Mascot Layout'),
                        el(TextControl, {
                            label: 'Mascot Custom Width (e.g. 160px)',
                            value: attributes.mascotWidth,
                            onChange: function(val) { setAttributes({ mascotWidth: val }); }
                        }),
                        el(TextControl, {
                            label: 'Mascot Bottom Offset (e.g. -5px)',
                            value: attributes.mascotBottom,
                            onChange: function(val) { setAttributes({ mascotBottom: val }); }
                        }),
                        el(SelectControl, {
                            label: 'Mascot Side Position',
                            value: attributes.mascotPosition,
                            options: [
                                { label: 'Right Side', value: 'right' },
                                { label: 'Left Side', value: 'left' }
                            ],
                            onChange: function(val) { setAttributes({ mascotPosition: val }); }
                        }),
                        el(TextControl, {
                            label: 'Mascot Left/Right Offset',
                            value: attributes.mascotOffset,
                            onChange: function(val) { setAttributes({ mascotOffset: val }); }
                        }),
                        el(ToggleControl, {
                            label: 'Mascot Behind Text',
                            checked: attributes.mascotBehind,
                            onChange: function(val) { setAttributes({ mascotBehind: val }); }
                        }),
                        el(RangeControl, {
                            label: 'Mascot Opacity',
                            value: parseFloat(attributes.mascotOpacity || '1'),
                            min: 0,
                            max: 1,
                            step: 0.1,
                            onChange: function(val) { setAttributes({ mascotOpacity: String(val) }); }
                        }),

                        el('h4', { style: { marginTop: '15px', marginBottom: '10px', fontWeight: 'bold', borderTop: '1px solid #eee', paddingTop: '10px' } }, 'Timer Blocks'),
                        el(TextControl, {
                            label: 'Block Border Radius',
                            value: attributes.timerBlockRadius,
                            onChange: function(val) { setAttributes({ timerBlockRadius: val }); }
                        }),
                        el(TextControl, {
                            label: 'Block Border Width',
                            value: attributes.timerBlockBorderWidth,
                            onChange: function(val) { setAttributes({ timerBlockBorderWidth: val }); }
                        }),
                        el(PanelRow, null, el('span', null, 'Block Border Color')),
                        el(ColorPalette, {
                            value: attributes.timerBlockBorderColor,
                            onChange: function(val) { setAttributes({ timerBlockBorderColor: val }); }
                        }),
                        el(TextControl, {
                            label: 'Block Padding',
                            value: attributes.timerBlockPadding,
                            onChange: function(val) { setAttributes({ timerBlockPadding: val }); }
                        }),
                        el(SelectControl, {
                            label: 'Block Box Shadow',
                            value: attributes.timerBlockShadow,
                            options: [
                                { label: 'None', value: 'none' },
                                { label: 'Light', value: 'light' },
                                { label: 'Medium', value: 'medium' },
                                { label: 'Heavy', value: 'heavy' }
                            ],
                            onChange: function(val) { setAttributes({ timerBlockShadow: val }); }
                        }),

                        el('h4', { style: { marginTop: '15px', marginBottom: '10px', fontWeight: 'bold', borderTop: '1px solid #eee', paddingTop: '10px' } }, 'Badges styling'),
                        el(TextControl, {
                            label: 'Badge Border Radius',
                            value: attributes.badgeRadius,
                            onChange: function(val) { setAttributes({ badgeRadius: val }); }
                        }),
                        el(TextControl, {
                            label: 'Badge Border Width',
                            value: attributes.badgeBorderWidth,
                            onChange: function(val) { setAttributes({ badgeBorderWidth: val }); }
                        }),
                        el(TextControl, {
                            label: 'Badge Padding',
                            value: attributes.badgePadding,
                            onChange: function(val) { setAttributes({ badgePadding: val }); }
                        }),

                        el('h4', { style: { marginTop: '15px', marginBottom: '10px', fontWeight: 'bold', borderTop: '1px solid #eee', paddingTop: '10px' } }, 'Close Button'),
                        el(PanelRow, null, el('span', null, 'Close Button Background')),
                        el(ColorPalette, {
                            value: attributes.closeBtnBg,
                            onChange: function(val) { setAttributes({ closeBtnBg: val }); }
                        }),
                        el(PanelRow, null, el('span', null, 'Close Button Color')),
                        el(ColorPalette, {
                            value: attributes.closeBtnColor,
                            onChange: function(val) { setAttributes({ closeBtnColor: val }); }
                        }),
                        el(PanelRow, null, el('span', null, 'Close Button Hover Background')),
                        el(ColorPalette, {
                            value: attributes.closeBtnHoverBg,
                            onChange: function(val) { setAttributes({ closeBtnHoverBg: val }); }
                        }),
                        el(PanelRow, null, el('span', null, 'Close Button Hover Color')),
                        el(ColorPalette, {
                            value: attributes.closeBtnHoverColor,
                            onChange: function(val) { setAttributes({ closeBtnHoverColor: val }); }
                        })
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
                    ),

                    // 9b. Page-level Multi-Popup Behavior
                    attributes.layout === 'modal' && el(
                        PanelBody,
                        { title: '🔗 Page-level Multi-Popup Behavior', initialOpen: false },
                        el('p', { style: { fontSize: '12px', color: '#64748b', marginBottom: '12px', lineHeight: '1.6' } },
                            'If you add multiple popups to this page, how should they behave?'
                        ),
                        el(SelectControl, {
                            label: 'Behavior Mode',
                            value: attributes.groupMode,
                            options: [
                                { label: '🟢 Stacked (Independent triggers, close all together)', value: 'stacked' },
                                { label: '🟢 Fully Independent (Close one closes only itself)', value: 'independent' },
                                { label: '🎠 Carousel Queue – Show one at a time; closing opens the next', value: 'carousel' },
                                { label: '🎲 Random – Pick one randomly per page load, suppress the rest', value: 'one_random' }
                            ],
                            help: attributes.groupMode === 'carousel'
                                ? 'Popups set to Carousel will queue up. The first one triggers automatically, closing it shows the next.'
                                : attributes.groupMode === 'one_random'
                                ? 'Only one popup set to Random will auto-trigger per page load. The others are suppressed.'
                                : attributes.groupMode === 'stacked'
                                ? 'Popups trigger independently but stack visually. Closing one closes all visible popups.'
                                : 'Each popup fires independently. Closing one only closes itself.',
                            onChange: function(val) { setAttributes({ groupMode: val }); }
                        })
                    )
                ),

                // Canvas Editor Preview Box (Visual Card Customizer)
                el('div', { style: { marginTop: '20px', display: 'flex', flexDirection: 'column', gap: '10px' } },
                    el('div', { style: { fontWeight: '600', fontSize: '13px', color: '#475569', display: 'flex', alignItems: 'center', gap: '6px' } }, 
                        el('span', null, '🎨 Live Customizer Editor Preview')
                    ),
                    attributes.layout === 'modal' && el('div', { style: { fontSize: '11px', color: '#0369a1', background: '#e0f2fe', padding: '8px 12px', borderRadius: '6px', border: '1px solid #bae6fd', fontWeight: '500' } },
                        'ℹ️ Modal layout design preview. On the front-end page, this will appear as a popup overlay.'
                    ),
                    !attributes.showTriggerBtn && attributes.layout === 'modal' && el('div', { style: { fontSize: '11px', color: '#b91c1c', background: '#fee2e2', padding: '8px 12px', borderRadius: '6px', border: '1px solid #fca5a5', fontWeight: '500' } },
                        '⚠️ Trigger button is hidden on page. Modal will show via Auto-Open/Exit-Intent/Selector.'
                    ),
                    el('div', {
                        className: 'wpc-interactive-element',
                        onClick: function(e) {
                            e.stopPropagation();
                            handleElementClick('Global Design Customizer', 'Card');
                        },
                        style: {
                            position: 'relative',
                            display: 'flex',
                            flexDirection: 'row',
                            alignItems: 'stretch',
                            background: activeCardBgColor,
                            padding: activeCardPadding,
                            borderRadius: activeCardBorderRadius,
                            boxShadow: activeCardShadow === 'none' ? 'none' : (activeCardShadow === 'soft' ? '0 4px 20px rgba(0,0,0,0.05)' : (activeCardShadow === 'heavy' ? '0 20px 40px rgba(0,0,0,0.15)' : '0 10px 30px rgba(0,0,0,0.08)')),
                            border: activeCardBorderStyle && activeCardBorderStyle !== 'none' ? (activeCardBorderWidth || '1px') + ' ' + activeCardBorderStyle + ' ' + (activeCardBorderColor || '#e2e8f0') : 'none',
                            minHeight: '220px',
                            overflow: 'hidden',
                            width: '100%',
                            boxSizing: 'border-box',
                            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
                        }
                    },
                        // Style Injector for Customizer Highlights
                        el('style', null,
                            '.wpc-interactive-element { cursor: pointer; transition: all 0.2s ease; border: 1.5px dashed transparent !important; position: relative; }\n' +
                            '.wpc-interactive-element:hover { border: 1.5px dashed #3b82f6 !important; background: rgba(59, 130, 246, 0.04) !important; outline: 3px solid rgba(59, 130, 246, 0.15); }\n' +
                            '.wpc-interactive-element::after { content: "✏️"; position: absolute; top: -8px; right: -8px; font-size: 10px; background: #3b82f6; color: white; padding: 2px; border-radius: 50%; opacity: 0; transition: opacity 0.2s ease; line-height: 1; z-index: 100; }\n' +
                            '.wpc-interactive-element:hover::after { opacity: 1; }\n'
                        ),

                        // Close Button Preview
                        el('button', {
                            style: {
                                position: 'absolute',
                                top: '15px',
                                right: '15px',
                                width: '32px',
                                height: '32px',
                                borderRadius: '50%',
                                border: 'none',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                background: activeCloseBtnBg,
                                color: activeCloseBtnColor,
                                fontSize: '16px',
                                cursor: 'default',
                                zIndex: '10'
                            }
                        }, '×'),

                        // Left Column
                        el('div', {
                            className: 'wpc-interactive-element',
                            onClick: function(e) {
                                e.stopPropagation();
                                handleElementClick('Brand Logo & Overrides', 'Logo');
                            },
                            style: {
                                width: activeLeftWidth,
                                backgroundColor: activeLeftBgColor,
                                padding: activeLeftPadding,
                                display: 'flex',
                                flexDirection: 'column',
                                alignItems: 'center',
                                justifyContent: 'center',
                                textAlign: 'center',
                                position: 'relative',
                                zIndex: '2'
                            }
                        },
                            activeLogo ? el('img', {
                                src: activeLogo,
                                style: { maxWidth: '100%', maxHeight: '60px', objectFit: 'contain', margin: '0 auto', display: 'block' }
                            }) : el('div', {
                                style: { width: '80px', height: '80px', borderRadius: '50%', background: '#f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '11px', color: '#64748b', margin: '0 auto' }
                            }, 'Woo Logo'),
                            el('div', { style: { marginTop: '8px', fontSize: '13px', fontWeight: 'bold', color: activePrimaryColor } }, activeTitleName)
                        ),

                        // Divider Line (if dividerShow is true)
                        activeDividerShow && el('div', {
                            style: {
                                borderLeftWidth: activeDividerWidth,
                                borderLeftStyle: activeDividerStyle,
                                borderLeftColor: activeDividerColor,
                                margin: '0 15px',
                                zIndex: '2'
                            }
                        }),

                        // Right Column
                        el('div', {
                            style: {
                                flex: '1',
                                display: 'flex',
                                flexDirection: 'column',
                                justifyContent: 'center',
                                paddingLeft: '15px',
                                position: 'relative',
                                zIndex: '2'
                            }
                        },
                            // Badge
                            el('div', {
                                className: 'wpc-interactive-element',
                                onClick: function(e) {
                                    e.stopPropagation();
                                    handleElementClick('Badges', 'Exclusive');
                                },
                                style: { display: 'flex', gap: '6px', marginBottom: '8px', padding: '4px', borderRadius: '4px', width: 'fit-content' }
                            },
                                el('span', {
                                    style: {
                                        background: activeExclusiveBgColor,
                                        color: activeExclusiveTextColor,
                                        fontSize: '10px',
                                        fontWeight: '700',
                                        textTransform: 'uppercase',
                                        padding: activeBadgePadding,
                                        border: activeBadgeBorderWidth ? activeBadgeBorderWidth + ' solid ' + activeExclusiveTextColor : 'none',
                                        borderRadius: activeBadgeRadius,
                                        letterSpacing: '0.05em'
                                    }
                                }, activeExclusiveLabel),
                                el('span', {
                                    style: {
                                        background: activeVerifiedBgColor,
                                        color: activeVerifiedTextColor,
                                        fontSize: '10px',
                                        fontWeight: '700',
                                        textTransform: 'uppercase',
                                        padding: activeBadgePadding,
                                        border: activeBadgeBorderWidth ? activeBadgeBorderWidth + ' solid ' + activeVerifiedTextColor : 'none',
                                        borderRadius: activeBadgeRadius,
                                        letterSpacing: '0.05em'
                                    }
                                }, activeVerifiedLabel)
                            ),

                            // Heading & Description
                            el('div', {
                                className: 'wpc-interactive-element',
                                onClick: function(e) {
                                    e.stopPropagation();
                                    handleElementClick('Titles & Colors', 'Title');
                                },
                                style: { padding: '4px', borderRadius: '4px', marginBottom: '8px' }
                            },
                                attributes.promoBannerText ? el('div', {
                                    style: {
                                        display: 'inline-block',
                                        padding: '4px 12px',
                                        borderRadius: '4px',
                                        fontSize: '12px',
                                        fontWeight: '700',
                                        textTransform: 'uppercase',
                                        letterSpacing: '0.5px',
                                        marginBottom: '12px',
                                        backgroundColor: attributes.promoBannerBg,
                                        color: attributes.promoBannerColor
                                    }
                                }, attributes.promoBannerText) : null,
                                el('div', { style: { fontSize: '18px', fontWeight: 'bold', color: activePrimaryColor, marginBottom: '4px' } }, activeTitle),
                                el('div', { style: { fontSize: '12px', color: '#475569' } }, activeSubtitle)
                            ),

                            // Features Checklist Preview
                            featuresArray.length > 0 && el('div', {
                                className: 'wpc-interactive-element',
                                onClick: function(e) {
                                    e.stopPropagation();
                                    handleElementClick('Titles & Colors', 'Features');
                                },
                                style: { padding: '4px', borderRadius: '4px', marginBottom: '12px' }
                            },
                                el('ul', {
                                    className: 'wpc-coupon-features',
                                    style: {
                                        listStyle: 'none',
                                        padding: '0',
                                        margin: '0',
                                        display: 'flex',
                                        flexDirection: 'column',
                                        gap: '6px'
                                    }
                                },
                                    featuresArray.map(function(feature, idx) {
                                        return el('li', {
                                            key: idx,
                                            style: {
                                                display: 'flex',
                                                alignItems: 'center',
                                                gap: '6px',
                                                fontSize: activeFeaturesSize,
                                                color: activeFeaturesColor || '#475569'
                                            }
                                        },
                                            el('svg', {
                                                width: '12',
                                                height: '12',
                                                viewBox: '0 0 24 24',
                                                fill: 'none',
                                                stroke: activePrimaryColor,
                                                strokeWidth: '3',
                                                style: { flexShrink: 0 }
                                            },
                                                el('polyline', { points: '20 6 9 17 4 12' })
                                            ),
                                            el('span', null, feature)
                                        );
                                    })
                                )
                            ),

                            // Timer Blocks Preview
                            timerDisplay && el('div', {
                                className: 'wpc-interactive-element',
                                onClick: function(e) {
                                    e.stopPropagation();
                                    handleElementClick('Timer Settings', 'Timer');
                                },
                                style: { display: 'flex', gap: '8px', marginBottom: '15px', padding: '4px', borderRadius: '4px', width: 'fit-content' }
                            },
                                timerDisplay.map(function(block, idx) {
                                    var shadowValue = 'none';
                                    if (activeTimerBlockShadow === 'light') shadowValue = '0 1px 2px rgba(0,0,0,0.05)';
                                    else if (activeTimerBlockShadow === 'medium') shadowValue = '0 4px 6px rgba(0,0,0,0.08)';
                                    else if (activeTimerBlockShadow === 'heavy') shadowValue = '0 10px 15px rgba(0,0,0,0.12)';
                                    
                                    return el('div', {
                                        key: idx,
                                        style: {
                                            background: activeTimerBgColor,
                                            color: activeTimerTextColor,
                                            padding: activeTimerBlockPadding,
                                            borderRadius: activeTimerBlockRadius,
                                            border: (activeTimerBlockBorderWidth || '1px') + ' solid ' + (activeTimerBlockBorderColor || '#e5e7eb'),
                                            boxShadow: shadowValue,
                                            display: 'flex',
                                            flexDirection: 'column',
                                            alignItems: 'center',
                                            minWidth: '36px'
                                        }
                                    },
                                        el('span', { style: { fontSize: '13px', fontWeight: 'bold' } }, block.val),
                                        el('span', { style: { fontSize: '8px', color: '#64748b', textTransform: 'uppercase', marginTop: '2px' } }, block.label)
                                    );
                                })
                            ),

                            // Claim Button Preview
                            el('div', {
                                className: 'wpc-interactive-element',
                                onClick: function(e) {
                                    e.stopPropagation();
                                    handleElementClick('Button Styles & Action', 'Button');
                                },
                                style: { display: 'flex', alignItems: 'center', marginTop: '10px', width: 'fit-content' }
                            },
                                el('div', {
                                    style: {
                                        position: 'relative',
                                        width: '280px',
                                        maxWidth: '100%',
                                        height: '42px',
                                        border: '1px solid rgba(15, 23, 42, 0.12)',
                                        background: '#f8fafc',
                                        borderRadius: activeButtonStyle === 'dashed_ticket' ? '6px' : '9999px',
                                        boxSizing: 'border-box',
                                        overflow: 'hidden',
                                        display: 'block'
                                    }
                                },
                                    el('div', {
                                        style: {
                                            position: 'absolute',
                                            top: '0', right: '0', bottom: '0', left: '0',
                                            zIndex: '1',
                                            color: '#64748b',
                                            fontWeight: '700',
                                            fontSize: '13px',
                                            letterSpacing: '0.05em',
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'flex-end',
                                            paddingRight: '15px'
                                        }
                                    }, attributes.maskText || 'SPECIAL'),
                                    
                                    el('div', {
                                        style: {
                                            position: 'absolute',
                                            top: '2px', left: '2px', bottom: '2px',
                                            zIndex: '2',
                                            width: 'calc(100% - 50px)',
                                            backgroundColor: activeBtnBgColor || activePrimaryColor,
                                            color: activeBtnTextColor || '#ffffff',
                                            fontSize: activeBtnSize || '13px',
                                            border: activeButtonStyle === 'dashed_ticket' ? '2px dashed rgba(255, 255, 255, 0.8)' : 'none',
                                            fontWeight: '700',
                                            borderRadius: activeButtonStyle === 'dashed_ticket' ? '4px' : (activeButtonStyle === 'ticket' ? '9999px 0 0 9999px' : '9999px'),
                                            clipPath: activeButtonStyle === 'ticket' ? 'polygon(0 0, calc(100% - 12px) 0, 100% 12px, 100% 100%, 0 100%)' : 'none',
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            gap: '6px',
                                            fontFamily: 'inherit',
                                            whiteSpace: 'nowrap'
                                        }
                                    },
                                        el('svg', {
                                            width: '12',
                                            height: '12',
                                            viewBox: '0 0 24 24',
                                            fill: 'none',
                                            stroke: 'currentColor',
                                            strokeWidth: '2.5'
                                        },
                                            el('path', { d: 'M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16' }),
                                            el('path', { d: 'M12 10a2 2 0 0 0 0-4 2 2 0 0 0 0 4z' }),
                                            el('path', { d: 'M12 12v3' })
                                        ),
                                        el('span', null, activeButtonText)
                                    )
                                )
                            )
                        ),

                        // Mascot Preview
                        activeMascotUrl && el('div', {
                            className: 'wpc-interactive-element',
                            onClick: function(e) {
                                e.stopPropagation();
                                handleElementClick('Mascot Options', 'Mascot');
                            },
                            style: (function() {
                                var base = {
                                    position: 'absolute',
                                    bottom: activeMascotBottom,
                                    width: activeMascotWidth,
                                    zIndex: activeMascotBehind ? '1' : '3',
                                    opacity: activeMascotOpacity
                                };
                                if (activeMascotPosition === 'left') {
                                    base.left = activeMascotOffset;
                                } else {
                                    base.right = activeMascotOffset;
                                }
                                return base;
                            })()
                        },
                            el('img', {
                                src: activeMascotUrl,
                                style: { width: '100%', height: 'auto', display: 'block', pointerEvents: 'none' }
                            })
                        )
                    )
                )
            );
        },

        save: function() {
            return null; // dynamic rendering in PHP
        }
    });
})(window.wp);
