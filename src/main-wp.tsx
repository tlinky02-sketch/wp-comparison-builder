import * as React from 'react';
import { useEffect, useState, useRef, useMemo } from 'react';
import * as ReactDOM from 'react-dom/client';
import ComparisonTable from "@/components/ComparisonTable";
import ComparisonFilters from "@/components/ComparisonFilters";
import PlatformDetails from "@/components/PlatformDetails";
import PlatformCard, { ComparisonItem } from "@/components/PlatformCard";
import PlatformListRow from "@/components/PlatformListRow";
import PlatformDetailedRow from "@/components/PlatformDetailedRow";
import PlatformCompactCard from "@/components/PlatformCompactCard";
import PricingPopup from "@/components/PricingPopup";
import { Button } from "@/components/ui/button";
import { SearchCombobox } from "@/components/SearchCombobox";
import { ArrowDown, X, Search, ChevronDown } from "lucide-react";
import { toast, Toaster } from 'sonner';
import { cn } from "@/lib/utils";
import '@/index.css';
import PricingTable from "@/components/PricingTable";
import PlatformHeroWrapper from "@/components/PlatformHeroWrapper"; // Static Import

// Define window interface for WP settings
declare global {
    interface Window {
        wpcSettings?: any; // Generic Name
        // Fallback for transition
        ecommerceGuiderSettings?: any;
        hostingGuiderSettings?: any;
        ecommerceGuiderRender?: (rootId: string) => void;
    }
}

const ComparisonBuilderApp = ({ initialConfig = {} }: { initialConfig?: any }) => {
    const config = initialConfig;
    console.log('AG_DEBUG: Config:', config);
    console.log('AG_DEBUG: EnableComparison:', config.enableComparison, typeof config.enableComparison);

    // Helper to get text with global setting override
    const getText = (key: string, defaultText: string) => {
        // 1. Check Config Labels (List Specific)
        if (config.labels && config.labels[key]) return config.labels[key];

        // 2. Check Global Settings (Legacy/Global)
        const settings = (window as any).wpcSettings || (window as any).ecommerceGuiderSettings;
        return settings?.texts?.[key] || defaultText;
    };

    // Helper to get color with override priority: config.colorsOverride > wpcSettings.colors
    const getColor = (colorKey: 'primary' | 'accent' | 'hoverButton' | 'secondary') => {
        // First check list-specific override from config (passed via PHP)
        if (config.colorsOverride && config.colorsOverride[colorKey]) {
            return config.colorsOverride[colorKey];
        }
        // Then check global settings
        const globalColors = (window as any).wpcSettings?.colors;
        if (globalColors && globalColors[colorKey]) {
            return globalColors[colorKey];
        }
        // Default fallbacks
        const defaults: Record<string, string> = {
            primary: '#6366f1',
            accent: '#0d9488',
            hoverButton: '',
            secondary: '#1e293b',
        };
        return defaults[colorKey] || '';
    };

    // Pre-load data synchronously for instant render
    const preloadedSettings = typeof window !== 'undefined' ?
        ((window as any).wpcSettings || (window as any).ecommerceGuiderSettings || (window as any).hostingGuiderSettings) : null;
    const preloadedData = preloadedSettings?.initialData;

    const [items, setItems] = useState<ComparisonItem[]>(() => {
        // Priority 1: List-specific items passed via data-config (Shortcode)
        if (config.initialItems && Array.isArray(config.initialItems) && config.initialItems.length > 0) {
            return config.initialItems;
        }

        // Priority 2: Global preloaded data (WP Localization)
        if (preloadedData?.items) return preloadedData.items;
        if (preloadedData?.providers) return preloadedData.providers; // Legacy

        return [];
    });
    const [categories, setCategories] = useState<string[]>(() => preloadedData?.categories || []);
    const [filterableFeatures, setFilterableFeatures] = useState<string[]>(() => preloadedData?.filterableFeatures || []);
    const [loading, setLoading] = useState(() => !preloadedData);

    // Derived Display Lists (for Custom Filters)
    const displayedCategories = useMemo(() => {
        if (config.filterCats && config.filterCats.length > 0) {
            return categories.filter(c => config.filterCats.includes(c));
        }
        return categories;
    }, [categories, config]);

    const displayedFeatures = useMemo(() => {
        if (config.filterFeats && config.filterFeats.length > 0) {
            return filterableFeatures.filter(f => config.filterFeats.includes(f));
        }
        return filterableFeatures;
    }, [filterableFeatures, config]);

    // Filter State
    const [selectedItems, setSelectedItems] = useState<string[]>([]);
    const [selectedCategories, setSelectedCategories] = useState<string[]>([]);
    const [selectedFeatures, setSelectedFeatures] = useState<string[]>([]);

    // Search & Sort State
    const [searchQuery, setSearchQuery] = useState('');
    const [searchSelectedItems, setSearchSelectedItems] = useState<string[]>([]);
    const [sortOption, setSortOption] = useState<'default' | 'name-asc' | 'name-desc' | 'rating-desc' | 'price-asc'>('default');

    // UI State - use initialConfig directly for initial visible count
    const [visibleCount, setVisibleCount] = useState(
        (initialConfig?.initialVisible && initialConfig.initialVisible >= 3)
            ? initialConfig.initialVisible
            : 8
    );
    const [selectedItemForDetails, setSelectedItemForDetails] = useState<string | null>(null);
    const [showComparison, setShowComparison] = useState(false);

    // Single Page Mode State
    const [viewMode, setViewMode] = useState<'list' | 'details'>('list');
    const [singleItemId, setSingleItemId] = useState<string | null>(null);

    // Product Variants: Active Category Context
    const [activeCategory, setActiveCategory] = useState<string | null>(config.category || null);

    const comparisonRef = useRef<HTMLDivElement>(null);

    const MAX_COMPARE = 4;

    // Get filter style early for loading state
    const globalFilterStyle = (window as any).wpcSettings?.filterStyle || 'top';
    const filterStyle = config.filterLayout && config.filterLayout !== 'default' ? config.filterLayout : globalFilterStyle;

    // Fetch Data on Load
    useEffect(() => {
        // Debug Config
        if ((window as any).wpcSettings?.debug || process.env.NODE_ENV === 'development') {
            console.log('WPC Final Config:', config);
        }

        const fetchData = async () => {
            // Check for preloaded data (try new name first, then old)
            // Check for preloaded data (try new name first, then old)
            const settings = window.wpcSettings || window.ecommerceGuiderSettings || window.hostingGuiderSettings;
            const initialData = settings?.initialData;

            if (initialData) {
                // IMPORTANT: If we already have items from config.initialItems (passed via shortcode),
                // DO NOT overwrite them with global initialData.items (which contains ALL items).
                // Only use global data if we have no local items.
                if (!config.initialItems || config.initialItems.length === 0) {
                    if (initialData.items) setItems(initialData.items);
                    else if (initialData.providers) setItems(initialData.providers); // Legacy support
                }

                if (initialData.categories) setCategories(initialData.categories);
                if (initialData.filterableFeatures) setFilterableFeatures(initialData.filterableFeatures);
                setLoading(false);
                return;
            }

            // Fallback to API
            try {
                // Should match the new PHP endpoint
                const apiUrl = settings?.apiUrl || '/wp-json/wpc/v1/items';
                const response = await fetch(apiUrl);
                const data = await response.json();

                if (data.items) setItems(data.items);
                else if (data.providers) setItems(data.providers); // Legacy API support

                if (data.categories) setCategories(data.categories);
                if (data.filterableFeatures) setFilterableFeatures(data.filterableFeatures);
            } catch (error) {
                console.error("Failed to fetch comparison data", error);
            } finally {
                setLoading(false);
            }
        };
        fetchData();
    }, []);

    // Track if this is initial mount (to avoid resetting on mount)
    const isInitialMount = useRef(true);

    // Reset pagination when filters change (but NOT on initial mount)
    useEffect(() => {
        if (isInitialMount.current) {
            isInitialMount.current = false;
            return; // Skip reset on initial mount
        }
        // Reset to initial config value or 8
        const resetValue = (config?.initialVisible && config.initialVisible >= 3) ? config.initialVisible : 8;
        setVisibleCount(resetValue);
    }, [selectedCategories, selectedFeatures, searchQuery, sortOption]);

    // Listen for in-page comparison trigger from compare button
    useEffect(() => {
        const handleCompareSelect = (event: any) => {
            // SOURCE FILTERING:
            // Check if the event comes from the External Compare Button (Shortcode).
            // If so, Standard Lists should IGNORE it to prevent "Double Table" or hijacking.
            // But Standard Lists MUST still listen to other events (e.g. internal triggers from Sticky Bar).
            const isExternalTrigger = event.detail?.source === 'external-button';

            if (isExternalTrigger && !isCompareButtonMode) {
                return; // Ignored.
            }

            if (config.enableComparison === false) return;

            // console.log('Compare event received:', event.detail);
            const { providerIds, itemIds, autoShow, category } = event.detail;
            const ids = providerIds || itemIds || [];

            if (category) {
                setActiveCategory(category);
            }

            if (ids.length > 0) {
                setSelectedItems(ids.map(String));
                if (autoShow !== false) {
                    setShowComparison(true);
                    // Scroll to comparison after a short delay
                    setTimeout(() => {
                        if (comparisonRef.current) {
                            comparisonRef.current.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }, 300);
                }
            }
        };

        window.addEventListener('ecommerceCompareSelect', handleCompareSelect);
        window.addEventListener('wpcCompareSelect', handleCompareSelect);

        return () => {
            window.removeEventListener('ecommerceCompareSelect', handleCompareSelect);
            window.removeEventListener('wpcCompareSelect', handleCompareSelect);
        };
    }, [config.enableComparison]);

    // Check URL parameters on mount for compare_ids
    useEffect(() => {
        if (config.enableComparison === false) return; // Master Switch block

        const urlParams = new URLSearchParams(window.location.search);
        const compareIds = urlParams.get('compare_ids');

        if (compareIds) {
            const ids = compareIds.split(',').map(id => id.trim());
            setSelectedItems(ids);
            setShowComparison(true);

            // Scroll to comparison section
            setTimeout(() => {
                if (comparisonRef.current) {
                    comparisonRef.current.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 500);
        }
    }, []);

    // Derived State (Filtering & Sorting)
    const filteredItems = useMemo(() => {
        let result = items;

        // Product Variants: Resolve Context (Price/Period)
        // Update item price/period to match the first plan in the active category
        if (activeCategory) {
            result = result.map(item => {
                if (item.variants?.enabled && item.variants?.plans_by_category) {
                    const allowed = item.variants.plans_by_category[activeCategory];
                    if (allowed && Array.isArray(allowed) && allowed.length > 0 && item.pricing_plans) {
                        const firstIndex = Number(allowed[0]);
                        if (item.pricing_plans[firstIndex]) {
                            // Override features if available for this category
                            let resolvedFeatures = item.raw_features;
                            if (item.variants?.features_by_category && item.variants.features_by_category[activeCategory]) {
                                const catFeatures = item.variants.features_by_category[activeCategory];
                                if (Array.isArray(catFeatures) && catFeatures.length > 0) {
                                    resolvedFeatures = catFeatures;
                                }
                            }

                            return {
                                ...item,
                                price: item.pricing_plans[firstIndex].price,
                                period: item.pricing_plans[firstIndex].period,
                                raw_features: resolvedFeatures
                            };
                        }
                    }
                }
                return item;
            });
        }

        // Config is now hoisted

        // Override featured_badge_text if custom list has badge_texts
        if (config.badge_texts && typeof config.badge_texts === 'object') {
            result = result.map(item => {
                const badgeText = config.badge_texts[item.id];
                if (badgeText && badgeText.trim()) {
                    return {
                        ...item,
                        featured_badge_text: badgeText
                    };
                }
                return item;
            });
        }

        // Override featured_badge_color if custom list has badge_colors
        if (config.badge_colors && typeof config.badge_colors === 'object') {
            result = result.map(item => {
                const badgeColor = config.badge_colors[item.id];
                if (badgeColor && badgeColor.trim()) {
                    return {
                        ...item,
                        featured_badge_color: badgeColor
                    };
                }
                return item;
            });
        }

        // Check if this is compare button mode
        const isCompareButtonMode = config.compareButtonMode === true;

        // 1. Filter by specific IDs if provided
        if (config.ids && config.ids.length > 0) {
            // Normalize IDs to strings for reliable filtering/sorting
            // This ensures we match '123' with 123 and preserve the backend sort order
            const configIds = config.ids.map(String);
            result = result.filter(p => configIds.includes(String(p.id)));

            // Enforce explicit sort order from config (Featured items are already sorted first by backend)
            result.sort((a, b) => {
                return configIds.indexOf(String(a.id)) - configIds.indexOf(String(b.id));
            });
        }

        // 2. Filter by Category from Config
        if (config.category) {
            result = result.filter(p => p.category.some(c => c.toLowerCase() === config.category.toLowerCase()));
        }

        // 3. Apply User Filters
        // Category filter
        if (selectedCategories.length > 0) {
            result = result.filter(item => {
                return selectedCategories.some((cat) => item.category.includes(cat));
            });
        }

        // Feature filter
        if (selectedFeatures.length > 0) {
            result = result.filter(item => {
                return selectedFeatures.every(feature => {
                    const featureNameLower = feature.toLowerCase();

                    // 1. Check Exact Taxonomy Match (from raw tags)
                    if (item.raw_features) {
                        if (item.raw_features.some(f => f.toLowerCase() === featureNameLower)) return true;
                    }

                    // 2. Fallback: Check mapped values (updated for ecommerce)
                    const features = item.features;
                    if (!features) return false;

                    // Products check
                    if (featureNameLower.includes('product') && featureNameLower.includes('unlimited') && features.products?.toLowerCase().includes('unlimited')) return true;

                    // Fees check
                    if (featureNameLower.includes('zero') && featureNameLower.includes('fee') && features.fees === '0%') return true;

                    // SSL check
                    if (featureNameLower.includes('ssl') && features.ssl) return true;

                    // Support check
                    if (featureNameLower.includes('support') && features.support?.toLowerCase().includes('24/7')) return true;

                    return false;
                });
            });
        }


        // 5. Final Consolidated Sorting (Relevance > Featured)
        result.sort((a, b) => {
            // Priority 0: Weighted Score based on User Request
            // (Has Filtered Category AND is Primary) > (Has Filtered Category) > Others

            let aScore = 0;
            let bScore = 0;

            if (selectedCategories.length > 0) {
                const aMatches = a.category.filter(c => selectedCategories.includes(c)).length;
                const bMatches = b.category.filter(c => selectedCategories.includes(c)).length;

                // Calculate Primary Weight (if matched category is also a primary category for that item)
                if (a.primary_categories) {
                    const aPrimaryMatches = a.primary_categories.filter(c => selectedCategories.includes(c)).length;
                    aScore += (aMatches * 1) + (aPrimaryMatches * 2); // Increased weight for primary match
                } else {
                    aScore += aMatches;
                }

                if (b.primary_categories) {
                    const bPrimaryMatches = b.primary_categories.filter(c => selectedCategories.includes(c)).length;
                    bScore += (bMatches * 1) + (bPrimaryMatches * 2);
                } else {
                    bScore += bMatches;
                }
            }

            if (bScore !== aScore) return bScore - aScore;

            // Priority 2: Featured Status (Featured comes first)
            if (config.featured && config.featured.length > 0) {
                const aFeatured = config.featured.includes(a.id);
                const bFeatured = config.featured.includes(b.id);
                if (aFeatured && !bFeatured) return -1;
                if (!aFeatured && bFeatured) return 1;
            }

            return 0;
        });

        // 6. Limit (Global limit from shortcode)
        if (config.limit && config.limit > 0) {
            result = result.slice(0, config.limit);
        }

        // 7. Search Filter (by name) - case insensitive OR Multi-select
        if (config.searchType === 'combobox' && searchSelectedItems.length > 0) {
            result = result.filter(item => searchSelectedItems.includes(item.name));
        } else if (searchQuery.trim()) {
            const query = searchQuery.toLowerCase().trim();
            result = result.filter(item =>
                item.name.toLowerCase().includes(query) ||
                ((item as any).short_description && (item as any).short_description.toLowerCase().includes(query))
            );
        }

        // 8. User Sort (if not default, override the relevance sort)
        if (sortOption !== 'default') {
            result = [...result].sort((a, b) => {
                switch (sortOption) {
                    case 'name-asc':
                        return a.name.localeCompare(b.name);
                    case 'name-desc':
                        return b.name.localeCompare(a.name);
                    case 'rating-desc':
                        const aRating = parseFloat(String(a.rating || '0')) || 0;
                        const bRating = parseFloat(String(b.rating || '0')) || 0;
                        return bRating - aRating;
                    case 'price-asc':
                        const aPrice = parseFloat(String(a.price || '0').replace(/[^0-9.]/g, '')) || 0;
                        const bPrice = parseFloat(String(b.price || '0').replace(/[^0-9.]/g, '')) || 0;
                        return aPrice - bPrice;
                    default:
                        return 0;
                }
            });
        }

        return result;
    }, [items, selectedCategories, selectedFeatures, searchQuery, searchSelectedItems, sortOption]);

    const displayedItems = filteredItems.slice(0, visibleCount);
    const hasMore = filteredItems.length > visibleCount;

    // Handle item selection/deselection
    const handleSelectItem = (id: string) => {
        // STRICT MASTER SWITCH: Block selection if comparison is disabled
        // Handle both boolean and string "0" from PHP
        if (config.enableComparison === false || config.enableComparison === '0') return;

        // ALLOW selection even if showCheckboxes is false (User Request: Clean UI but selectable)
        // Checks removed.

        if (selectedItems.includes(id)) {
            const newSelection = selectedItems.filter((p) => p !== id);
            setSelectedItems(newSelection);
            if (newSelection.length === 0) setShowComparison(false);
        } else {
            if (selectedItems.length >= MAX_COMPARE) {
                toast.error(`You can compare up to ${MAX_COMPARE} items at once.`);
                return;
            }
            setSelectedItems((prev) => [...prev, id]);
        }
    };

    // Auto-clear selection if master switch is turned off
    useEffect(() => {
        if ((config.enableComparison === false || (config.enableComparison as any) === '0') && selectedItems.length > 0) {
            setSelectedItems([]);
            setShowComparison(false);
        }
    }, [config.enableComparison, selectedItems.length]);

    const handleCategoryChange = (category: string) => {
        setSelectedCategories((prev) =>
            prev.includes(category) ? prev.filter((c) => c !== category) : [...prev, category]
        );
    };

    const handleFeatureChange = (feature: string) => {
        setSelectedFeatures((prev) =>
            prev.includes(feature) ? prev.filter((f) => f !== feature) : [...prev, feature]
        );
    };

    const handleEnsureComparison = () => {
        setShowComparison(true);
        // Small timeout to allow render
        setTimeout(() => {
            comparisonRef.current?.scrollIntoView({ behavior: 'smooth' });
        }, 100);
    };

    const handleRemoveFromComparison = (id: string) => {
        const newSelection = selectedItems.filter(pid => pid !== id);
        setSelectedItems(newSelection);
        if (newSelection.length === 0) setShowComparison(false);
    };

    const handleViewDetails = (id: string) => setSelectedItemForDetails(id);
    const handleCloseDetails = () => setSelectedItemForDetails(null);



    // ... (imports remain)

    // Inside component ...

    const selectedItemObjects = items.filter(p => selectedItems.includes(p.id));
    const detailsItem = items.find(p => p.id === selectedItemForDetails);

    // Check if compare button shortcode is present on the page
    const shouldHideFilters = !!(window as any).wpcCompareButtonPresent || !!(window as any).ecommerceCompareButtonPresent;

    // Helper: Check if ANY root on the page has compareButtonMode (Fallback for legacy/caching)
    const getCurrentConfigFallback = () => {
        const roots = document.querySelectorAll('.wpc-root, .ecommerce-guider-root, #ecommerce-guider-root, #hosting-guider-root');
        for (let i = 0; i < roots.length; i++) {
            const root = roots[i] as HTMLElement;
            if (root && root.dataset.config) {
                try {
                    const cfg = JSON.parse(root.dataset.config);
                    if (cfg.compareButtonMode === true) return true;
                } catch (e) { }
            }
        }
        return false;
    };

    // Check if in compare button mode (Priority: Local Config > Global Fallback)
    const isCompareButtonMode = config.compareButtonMode === true || config.viewMode === 'button' || getCurrentConfigFallback();

    // RENDER: Single Pros/Cons Table Mode (New Shortcode)
    if (config.viewMode === 'pros-cons-table' && config.item) {
        const item = config.item;
        const pros = item.pros || [];
        const cons = item.cons || [];

        // Get colors from config (per-item overrides) with global fallbacks
        const prosBg = config.prosBg || (window as any).wpcSettings?.colors?.prosBg || '#f0fdf4';
        const prosText = config.prosText || (window as any).wpcSettings?.colors?.prosText || '#166534';
        const consBg = config.consBg || (window as any).wpcSettings?.colors?.consBg || '#fef2f2';
        const consText = config.consText || (window as any).wpcSettings?.colors?.consText || '#991b1b';

        // Get labels from config (per-item overrides)
        const prosLabel = config.prosLabel || (window as any).wpcSettings?.texts?.prosLabel || 'Pros';
        const consLabel = config.consLabel || (window as any).wpcSettings?.texts?.consLabel || 'Cons';

        // Get icons from config
        const prosIcon = config.prosIcon || '✓';
        const consIcon = config.consIcon || '✗';

        return (
            <div className="wpc-comparison-wrapper">
                <Toaster />
                <div className="w-full">
                    <div className="grid md:grid-cols-2 gap-6">
                        {/* Pros Section */}
                        <div
                            className="rounded-xl border p-6"
                            style={{
                                backgroundColor: prosBg,
                                borderColor: prosText + '40'
                            }}
                        >
                            <h3 className="text-xl font-bold mb-4 flex items-center gap-3" style={{ color: prosText }}>
                                <span className="text-3xl">{prosIcon}</span>
                                {prosLabel}
                            </h3>
                            {pros.length > 0 ? (
                                <ul className="space-y-3">
                                    {pros.map((pro: string, idx: number) => (
                                        <li key={idx} className="flex items-start gap-3 text-sm">
                                            <span className="mt-0.5 font-bold" style={{ color: prosText }}>{prosIcon}</span>
                                            <span style={{ color: prosText }}>{pro}</span>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-muted-foreground italic text-sm">No {prosLabel.toLowerCase()} listed</p>
                            )}
                        </div>

                        {/* Cons Section */}
                        <div
                            className="rounded-xl border p-6"
                            style={{
                                backgroundColor: consBg,
                                borderColor: consText + '40'
                            }}
                        >
                            <h3 className="text-xl font-bold mb-4 flex items-center gap-3" style={{ color: consText }}>
                                <span className="text-3xl">{consIcon}</span>
                                {consLabel}
                            </h3>
                            {cons.length > 0 ? (
                                <ul className="space-y-3">
                                    {cons.map((con: string, idx: number) => (
                                        <li key={idx} className="flex items-start gap-3 text-sm">
                                            <span className="mt-0.5 font-bold" style={{ color: consText }}>{consIcon}</span>
                                            <span style={{ color: consText }}>{con}</span>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-muted-foreground italic text-sm">No {consLabel.toLowerCase()} listed</p>
                            )}
                        </div>
                    </div>
                </div >
            </div >
        );
    }

    // RENDER: Single Pricing Table Mode (New Shortcode)
    // Render BEFORE loading check because it uses config.item (passed from shortcode)
    if (config.viewMode === 'pricing-table' && config.item) {
        return (
            <div className="wpc-comparison-wrapper">
                <Toaster />
                <PricingTable
                    item={config.item}
                    showPlanButtons={config.showPlanButtons}
                    showFooterButton={config.showFooterButton}
                    footerButtonText={config.footerButtonText}
                    showHeaders={false}
                    displayContext={config.displayContext || 'inline'}
                    config={{ ...config, category: activeCategory }}
                />
            </div>
        );
    }

    // RENDER: Comparison Table Mode (Pure Table View)
    if (config.viewMode === 'comparison-table') {
        if (loading) {
            return null;
        }

        return (
            <div className="wpc-comparison-wrapper">
                <Toaster />
                <div className="w-full">
                    <ComparisonTable items={filteredItems} onRemove={() => { }} config={{ ...config, category: activeCategory }} />
                </div>
            </div>
        );
    }

    // RENDER: Loading State - Return nothing (data is preloaded via PHP, so loading is instant)
    if (loading) {
        return null;
    }

    // RENDER: Hide items initially if showItemsInitially is false (for compare button mode)
    // BUT: Allow rendering if user has selected items OR if showComparison is true
    if (config.showItemsInitially === false && selectedItems.length === 0) {
        return (
            <div className="wpc-comparison-wrapper">
                <Toaster />
            </div>
        );
    }

    // RENDER: Single Platform Details Mode
    if (viewMode === 'details' && singleItemId) {
        const singleItem = items.find(p => p.id === singleItemId);
        if (singleItem) {
            return (
                <div className="wpc-comparison-wrapper">
                    <Toaster />
                    <PlatformDetails
                        item={singleItem}
                        allItems={items}
                        onBack={() => {
                            // If user navigates back, we could switch mode, but likely they want to go to the main page
                            window.location.href = '/hosting-reviews'; // or dynamic root
                        }}
                        hoverColor={getColor('hoverButton')}
                        primaryColor={getColor('primary')}
                        labels={config.labels}
                    />
                </div>
            );
        }
        // Fallback if ID not found (404-ish)
        return <div className="p-12 text-center text-muted-foreground">Item not found.</div>;
    }


    // RENDER: Default List/Compare Mode
    // In compare button mode, don't render anything until comparison is triggered (Comparison Table)
    // We do NOT want to show the list grid or the sticky selection bar in this mode.
    if (isCompareButtonMode && !showComparison) {
        return null;
    }

    return (
        <div className="wpc-comparison-wrapper bg-background text-foreground min-h-[100px] py-4">
            <Toaster />

            {/* Selection Bar - Show when providers selected (Only if Enabled) */}
            {config.enableComparison !== false && selectedItems.length > 0 && (
                <div className="mb-6 p-4 bg-card rounded-xl border border-border shadow-sm sticky top-4 z-50">
                    <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="text-sm font-semibold text-foreground">{getText('selected', 'Selected:')}</span>
                            {selectedItemObjects.map(p => {
                                const accentColor = getColor('accent');
                                return (
                                    <div
                                        key={p.id}
                                        className="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium"
                                        style={{
                                            backgroundColor: `${accentColor}15`,
                                            color: accentColor,
                                        }}
                                    >
                                        {p.name}
                                        <button onClick={() => handleRemoveFromComparison(p.id)} aria-label="Remove from comparison">
                                            <X className="w-4 h-4" />
                                        </button>
                                    </div>
                                );
                            })}
                        </div>
                        <Button
                            onClick={handleEnsureComparison}
                            disabled={selectedItems.length < 2}
                            className="font-bold shadow-lg"
                            style={{
                                backgroundColor: getColor('primary') || undefined,
                                color: 'white',
                            }}
                            onMouseEnter={(e) => {
                                const hoverColor = getColor('hoverButton');
                                const primaryColor = getColor('primary');
                                if (hoverColor) e.currentTarget.style.backgroundColor = hoverColor;
                                else if (primaryColor) e.currentTarget.style.filter = 'brightness(90%)';
                            }}
                            onMouseLeave={(e) => {
                                e.currentTarget.style.backgroundColor = getColor('primary') || '';
                                e.currentTarget.style.filter = '';
                            }}
                        >
                            {getText('compareNow', 'Compare Now')} <ArrowDown className="w-4 h-4 ml-2" />
                        </Button>
                    </div>
                </div>
            )}

            {/* Filters Section - Top Layout Only */}
            {config.showFilters !== false && filterStyle === 'top' && (showComparison || !shouldHideFilters) && (
                <div className="mb-8 p-4 bg-card rounded-xl border border-border shadow-sm">
                    <ComparisonFilters
                        categories={displayedCategories}
                        features={displayedFeatures}
                        selectedCategories={selectedCategories}
                        selectedFeatures={selectedFeatures}
                        onCategoryChange={handleCategoryChange}
                        onFeatureChange={handleFeatureChange}
                        onClearFilters={() => { setSelectedCategories([]); setSelectedFeatures([]); }}
                        layout="top"
                        labels={{
                            categories: config.categoriesLabel || getText('categoryLabel', 'Category'),
                            features: config.featuresLabel || getText('featuresLabel', 'Platform Features'),
                            filters: getText('filters', 'Filters'),
                            resetFilters: getText('resetFilters', 'Reset Filters'),
                            select: getText('select', 'Select %s'),
                            clear: getText('clear', 'Clear')
                        }}
                    />
                </div>
            )}

            {/* Main Content Area */}
            <div className={cn(
                "w-full",
                config.showFilters !== false && !isCompareButtonMode && filterStyle === 'sidebar' && !shouldHideFilters ? "flex flex-col lg:grid lg:grid-cols-4 lg:gap-8" : "flex flex-col lg:flex-row gap-8"
            )}>
                {/* Sidebar Filters - Keep visible even when comparison is shown */}
                {config.showFilters !== false && !isCompareButtonMode && filterStyle === 'sidebar' && !shouldHideFilters && (
                    <div className="lg:col-span-1 border border-border rounded-xl p-6 bg-card mb-8 lg:mb-0 h-fit lg:sticky lg:top-24">
                        <ComparisonFilters
                            categories={displayedCategories}
                            features={displayedFeatures}
                            selectedCategories={selectedCategories}
                            selectedFeatures={selectedFeatures}
                            onCategoryChange={handleCategoryChange}
                            onFeatureChange={handleFeatureChange}
                            onClearFilters={() => { setSelectedCategories([]); setSelectedFeatures([]); }}
                            layout="sidebar"
                            labels={{
                                categories: config.categoriesLabel || getText('categoryLabel', 'Categories'),
                                features: config.featuresLabel || getText('featuresLabel', 'Features'),
                                filters: getText('filters', 'Filters'),
                                resetFilters: getText('resetFilters', 'Reset Filters'),
                                select: getText('select', 'Select %s'),
                                clear: getText('clear', 'Clear')
                            }}
                        />
                    </div>
                )}

                {/* Main List Area */}
                <div className={cn(
                    "flex-1",
                    config.showFilters !== false && !isCompareButtonMode && filterStyle === 'sidebar' && !shouldHideFilters ? "lg:col-span-3" : ""
                )}>
                    {/* Platform Grid Container - Only show if not in compare button mode */}
                    {!isCompareButtonMode && (
                        <div className="w-full">
                            {/* Active Filters Display */}
                            {config.showFilters !== false && (selectedCategories.length > 0 || selectedFeatures.length > 0) && (
                                <div className="mb-6 flex flex-wrap items-center gap-2">
                                    <span className="text-sm font-medium text-muted-foreground mr-2">{getText('activeFilters', 'Active filters:')}</span>
                                    {selectedCategories.map(cat => (
                                        <button
                                            key={cat}
                                            onClick={() => handleCategoryChange(cat)}
                                            className="flex items-center gap-1.5 px-3 py-1 bg-accent/10 text-accent rounded-full text-sm font-medium hover:bg-accent/20 transition-colors"
                                        >
                                            {cat} <X className="w-3 h-3" />
                                        </button>
                                    ))}
                                    {selectedFeatures.map(feat => (
                                        <button
                                            key={feat}
                                            onClick={() => handleFeatureChange(feat)}
                                            className="flex items-center gap-1.5 px-3 py-1 bg-primary/10 text-primary rounded-full text-sm font-medium hover:bg-primary/20 transition-colors"
                                        >
                                            {feat} <X className="w-3 h-3" />
                                        </button>
                                    ))}
                                    <button
                                        onClick={() => { setSelectedCategories([]); setSelectedFeatures([]); }}
                                        className="text-sm text-muted-foreground hover:text-accent underline ml-2"
                                    >
                                        {getText('clearAll', 'Clear all')}
                                    </button>
                                </div>
                            )}

                            {/* Search & Sort Bar */}
                            {config.showSearchBar !== false && (
                                <div className="mb-6 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                    {/* Search Input */}
                                    <div className="relative flex-1">

                                        {config.searchType === 'combobox' ? (
                                            <SearchCombobox
                                                items={items}
                                                selectedValues={searchSelectedItems}
                                                onSelect={setSearchSelectedItems}
                                                placeholder="Search & Select..."
                                                hoverColor={getColor('hoverButton')}
                                                primaryColor={getColor('primary')}
                                                compareLabel={config.labels?.compareBtn}
                                                labels={{
                                                    selectProvider: getText('selectProvider', 'Select provider...'),
                                                    search: getText('searchPlaceholder', 'Search...'),
                                                    noItemFound: getText('noItemFound', 'No item found.'),
                                                    more: getText('more', 'more')
                                                }}
                                                onCompare={(config.enableComparison !== false && config.showCheckboxes !== false) ? () => {
                                                    // Convert selected item names to IDs
                                                    const idsToCompare = items
                                                        .filter(item => searchSelectedItems.includes(item.name))
                                                        .map(item => item.id);

                                                    if (idsToCompare.length >= 2) {
                                                        setSelectedItems(idsToCompare);
                                                        setShowComparison(true);
                                                        // Scroll to comparison
                                                        setTimeout(() => {
                                                            comparisonRef.current?.scrollIntoView({ behavior: 'smooth' });
                                                        }, 100);
                                                    }
                                                } : undefined}
                                            />
                                        ) : (
                                            <>
                                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                                <input
                                                    type="text"
                                                    placeholder={getText('searchPlaceholder', 'Search by name...')}
                                                    value={searchQuery}
                                                    onChange={(e) => setSearchQuery(e.target.value)}
                                                    className="w-full pl-10 pr-4 py-2.5 bg-card border border-border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                                                    style={{
                                                        borderColor: (window as any).wpcSettings?.colors?.cardBorder || undefined
                                                    }}
                                                />
                                                {searchQuery && (
                                                    <button
                                                        onClick={() => setSearchQuery('')}
                                                        className="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground hover:text-foreground"
                                                    >
                                                        <X className="w-4 h-4" />
                                                    </button>
                                                )}
                                            </>
                                        )}
                                    </div>

                                    {/* Sort Dropdown */}
                                    <div className="relative min-w-[160px]">
                                        <select
                                            value={sortOption}
                                            onChange={(e) => setSortOption(e.target.value as typeof sortOption)}
                                            className="w-full appearance-none pl-4 pr-10 py-2.5 bg-card border border-border rounded-xl text-sm cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                                            style={{
                                                borderColor: (window as any).wpcSettings?.colors?.cardBorder || undefined
                                            }}
                                        >
                                            <option value="default">{getText('sortDefault', 'Sort: Default')}</option>
                                            <option value="name-asc">{getText('sortNameAsc', 'Name (A-Z)')}</option>
                                            <option value="name-desc">{getText('sortNameDesc', 'Name (Z-A)')}</option>
                                            <option value="rating-desc">{getText('sortRating', 'Highest Rated')}</option>
                                            <option value="price-asc">{getText('sortPrice', 'Lowest Price')}</option>
                                        </select>
                                        <ChevronDown className="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
                                    </div>

                                    {/* Results Count */}
                                    <span className="text-sm text-muted-foreground whitespace-nowrap">
                                        {filteredItems.length} {filteredItems.length === 1 ? 'item' : 'items'}
                                    </span>
                                </div>
                            )}

                            {(() => {
                                // Grid Class Logic
                                let containerClass = "grid gap-6";
                                if (config.style === 'list' || config.style === 'detailed') {
                                    containerClass += " grid-cols-1";
                                } else {
                                    containerClass += " grid-cols-1 md:grid-cols-2";
                                    if (filterStyle === 'sidebar') containerClass += " lg:grid-cols-2 xl:grid-cols-3";
                                    else containerClass += " lg:grid-cols-3 xl:grid-cols-4";
                                }

                                const hasNextPage = hasMore && config.showAllEnabled !== false;

                                return (
                                    <div className={containerClass}>
                                        {displayedItems.map((item, index) => {
                                            const isFeatured = config.featured && config.featured.includes(item.id);
                                            // Props for PlatformCard (Legacy/Grid)
                                            const gridProps = {
                                                key: item.id,
                                                item,
                                                index,
                                                isSelected: selectedItems.includes(item.id),
                                                onSelect: handleSelectItem, // PlatformCard uses onSelect
                                                onViewDetails: handleViewDetails,
                                                disabled: selectedItems.length >= MAX_COMPARE && !selectedItems.includes(item.id),
                                                isFeatured,
                                                activeCategories: selectedCategories,
                                                enableComparison: config.enableComparison !== false,
                                                buttonText: config.buttonText,
                                                badgeText: config.badgeTexts?.[item.id],
                                                badgeColor: config.badgeColors?.[item.id],
                                                badgeStyle: config.badgeStyle,
                                                showRating: config.showRating,
                                                showPrice: config.showPrice,
                                                showCheckboxes: config.showCheckboxes !== false,
                                                viewAction: config.viewAction || 'popup',
                                                labels: config.labels, // Pass configurable labels
                                                config // Pass full config for targets/positioning
                                            };

                                            // Props for New Styles
                                            const listProps = {
                                                key: item.id,
                                                item,
                                                index,
                                                isSelected: selectedItems.includes(item.id),
                                                onToggleCompare: handleSelectItem, // New components use onToggleCompare
                                                onViewDetails: handleViewDetails,
                                                enableComparison: config.enableComparison !== false,
                                                buttonText: config.buttonText,
                                                showRank: true,
                                                badgeText: config.badgeTexts?.[item.id],
                                                badgeColor: config.badgeColors?.[item.id],
                                                badgeStyle: config.badgeStyle,
                                                showRating: config.showRating,
                                                showPrice: config.showPrice,
                                                showCheckboxes: config.showCheckboxes !== false,
                                                viewAction: config.viewAction || 'popup',
                                                activeCategories: selectedCategories,
                                                labels: config.labels, // Pass configurable labels
                                                config // Pass full config for targets/positioning
                                            };

                                            switch (config.style) {
                                                case 'list': return <PlatformListRow {...listProps} />;
                                                case 'detailed': return <PlatformDetailedRow {...listProps} />;
                                                case 'compact': return <PlatformCompactCard {...listProps} />;
                                                case 'grid': default: return <PlatformCard {...gridProps} />;
                                            }
                                        })}

                                        {/* Show More Card - Inside Grid Flow */}
                                        {hasNextPage && (
                                            <div
                                                onClick={() => setVisibleCount(filteredItems.length)}
                                                className={cn(
                                                    "bg-card rounded-2xl border-2 border-dashed border-border flex flex-col items-center justify-center text-center cursor-pointer hover:border-primary hover:bg-primary/5 transition-all group",
                                                    (config.style === 'list' || config.style === 'detailed') ? "p-4 min-h-[100px]" : "p-6 aspect-square md:aspect-auto min-h-[200px]"
                                                )}
                                            >
                                                <div className="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                                                    <ArrowDown className="w-6 h-6 text-primary" />
                                                </div>
                                                <h3 className="font-bold text-lg mb-1">{config.labels?.showAllItems || 'Show All Items'}</h3>
                                                <p className="text-sm text-muted-foreground">
                                                    {config.labels?.revealMore || 'Click to reveal'} {filteredItems.length - visibleCount} more
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                );
                            })()}

                            {filteredItems.length === 0 && (
                                <div className="text-center p-12 bg-muted/20 rounded-xl">
                                    <p>{getText('noResults', 'No items match your filters.')}</p>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>

            {/* Inline Comparison Section */}
            {
                selectedItems.length > 0 && showComparison && (
                    <div ref={comparisonRef} className="mt-16 pt-8 border-t border-border w-full">
                        <h2 className="text-2xl font-bold mb-6 px-2">Detailed Comparison</h2>
                        <ComparisonTable items={selectedItemObjects} onRemove={handleRemoveFromComparison} labels={config.labels} config={{ ...config, category: activeCategory }} />
                    </div>
                )
            }

            {/* Pricing Popup */}
            {
                detailsItem && (
                    <PricingPopup item={detailsItem} onClose={handleCloseDetails} showPlanButtons={config.showPlanButtons} config={{ ...config, category: activeCategory }} />
                )
            }
        </div >
    );
};

// Mount
// Helper to get React props from data attributes
const getPropsFromRoot = (rootId: string | HTMLElement) => {
    const root = typeof rootId === 'string' ? document.getElementById(rootId) : rootId;
    if (root) {
        if (root.dataset.config) {
            try {
                return JSON.parse(root.dataset.config);
            } catch (e) {
                console.error("Error parsing config", e);
            }
        } else if (root.dataset.props) {
            try {
                return JSON.parse(root.dataset.props);
            } catch (e) {
                console.error("Error parsing props", e);
            }
        }
    }
    return {};
};

// Mount Main App on all comparison root elements
// This handles both regular shortcode and compare button shortcode
const roots = document.querySelectorAll('.wpc-root, .ecommerce-guider-root, #ecommerce-guider-root, #hosting-guider-root');
roots.forEach((el) => {
    const root = el as HTMLElement;
    if (root && !root.hasAttribute('data-react-mounted')) {
        root.setAttribute('data-react-mounted', 'true');
        const config = root.dataset.config ? JSON.parse(root.dataset.config) : {};
        ReactDOM.createRoot(root).render(
            <React.StrictMode>
                <ComparisonBuilderApp initialConfig={config} />
            </React.StrictMode>
        );
    }
});

// Mount Hero Instances (potentially multiple)
try {
    const heroRoots = document.querySelectorAll('.wpc-hero-root');
    if (heroRoots.length > 0) {
        for (let i = 0; i < heroRoots.length; i++) {
            const root = heroRoots[i] as HTMLElement;
            if (root && !root.hasAttribute('data-react-mounted')) {
                root.setAttribute('data-react-mounted', 'true');
                const config = getPropsFromRoot(root);

                // Static Render
                ReactDOM.createRoot(root).render(
                    <React.StrictMode>
                        <PlatformHeroWrapper itemId={config.itemId || config.providerId} />
                    </React.StrictMode>
                );
            }
        }
    }
} catch (e) {
    console.error('WPC: Error mounting hero instances', e);
}
// Mount Best Use Cases
const useCasesRoots = document.querySelectorAll('.wpc-use-cases-root');
useCasesRoots.forEach((el) => {
    const root = el as HTMLElement;
    if (root && !root.hasAttribute('data-react-mounted')) {
        root.setAttribute('data-react-mounted', 'true');
        const config = getPropsFromRoot(root);

        import('./components/BestUseCases').then(({ default: BestUseCases }) => {
            ReactDOM.createRoot(root).render(
                <React.StrictMode>
                    <BestUseCases {...config} />
                </React.StrictMode>
            );
        });
    }
});
