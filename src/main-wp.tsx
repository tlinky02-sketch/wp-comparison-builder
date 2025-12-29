import * as React from 'react';
import { useEffect, useState, useRef, useMemo } from 'react';
import * as ReactDOM from 'react-dom/client';
import ComparisonTable from "@/components/ComparisonTable";
import ComparisonFilters from "@/components/ComparisonFilters";
import PlatformDetails from "@/components/PlatformDetails";
import PlatformCard, { ComparisonItem } from "@/components/PlatformCard";
import PricingPopup from "@/components/PricingPopup";
import { Button } from "@/components/ui/button";
import { ArrowDown, X, Search, ChevronDown } from "lucide-react";
import { toast, Toaster } from 'sonner';
import { cn } from "@/lib/utils";
import '@/index.css';
import PricingTable from "@/components/PricingTable";

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

    const [items, setItems] = useState<ComparisonItem[]>([]);
    const [categories, setCategories] = useState<string[]>([]);
    const [filterableFeatures, setFilterableFeatures] = useState<string[]>([]);
    const [loading, setLoading] = useState(true);

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
            const settings = window.wpcSettings || window.ecommerceGuiderSettings || window.hostingGuiderSettings;
            const initialData = settings?.initialData;

            if (initialData) {
                if (initialData.items) setItems(initialData.items);
                else if (initialData.providers) setItems(initialData.providers); // Legacy support

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
            const { providerIds, itemIds, autoShow } = event.detail;
            const ids = itemIds || providerIds;

            if (ids && Array.isArray(ids)) {
                // Auto-select the items
                setSelectedItems(ids.map(String));
                // console.log('Selected items set to:', ids);

                if (autoShow) {
                    // Only show comparison table automatically if we are in usage mode for it (e.g. Button)
                    // Regular lists should NOT switch to table view automatically via remote event.
                    // UPDATE: We now filter by 'source' at the top, so if we reached here, it is a valid trigger.
                    setShowComparison(true);
                    console.log('Showing comparison table');

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

        // 7. Search Filter (by name) - case insensitive
        if (searchQuery.trim()) {
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
    }, [items, selectedCategories, selectedFeatures, searchQuery, sortOption]);

    const displayedItems = filteredItems.slice(0, visibleCount);
    const hasMore = filteredItems.length > visibleCount;

    // Handlers
    const handleSelectItem = (id: string) => {
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

    // Check if in compare button mode (from config in ANY .wpc-root element)
    const getCurrentConfig = () => {
        // Check all possible root elements, including compare button roots
        const roots = document.querySelectorAll('.wpc-root, .ecommerce-guider-root, #ecommerce-guider-root, #hosting-guider-root');
        for (let i = 0; i < roots.length; i++) {
            const root = roots[i] as HTMLElement;
            if (root && root.dataset.config) {
                try {
                    const config = JSON.parse(root.dataset.config);
                    // If we find a root with compareButtonMode, return it
                    if (config.compareButtonMode === true) {
                        return config;
                    }
                } catch (e) {
                    // Continue checking other roots
                }
            }
        }
        // Fallback: check the original IDs
        const root = document.querySelector('.wpc-root') || document.getElementById('hosting-guider-root') || document.getElementById('ecommerce-guider-root');
        if (root && (root as HTMLElement).dataset.config) {
            try {
                return JSON.parse((root as HTMLElement).dataset.config || "{}");
            } catch (e) {
                return {};
            }
        }
        return {};
    };
    const isCompareButtonMode = getCurrentConfig().compareButtonMode === true;

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
                />
            </div>
        );
    }

    // RENDER: Loading State - Show skeleton placeholder for SEO (prevents CLS)
    if (loading) {
        // Show a skeleton that matches the filter bar to prevent layout shift
        const shouldShowFilterSkeleton = !isCompareButtonMode;

        if (!shouldShowFilterSkeleton) {
            return <div className="wpc-comparison-wrapper"><Toaster /></div>;
        }

        return (
            <div className="wpc-comparison-wrapper bg-background text-foreground min-h-[100px] py-4">
                <Toaster />

                {/* Skeleton Filter Bar */}
                {filterStyle === 'top' ? (
                    <div className="mb-8 p-4 bg-card rounded-xl border border-border shadow-sm animate-pulse">
                        <div className="flex flex-wrap items-center gap-2">
                            <div className="flex items-center gap-2 mr-2">
                                <div className="w-5 h-5 bg-muted rounded"></div>
                                <div className="w-16 h-5 bg-muted rounded"></div>
                            </div>
                            <div className="w-28 h-9 bg-muted rounded border border-dashed border-border"></div>
                            <div className="w-36 h-9 bg-muted rounded border border-dashed border-border"></div>
                        </div>
                    </div>
                ) : (
                    <div className="flex flex-col lg:grid lg:grid-cols-4 lg:gap-8">
                        {/* Sidebar Skeleton */}
                        <div className="lg:col-span-1 border border-border rounded-xl p-6 bg-card mb-8 lg:mb-0 h-fit animate-pulse">
                            <div className="flex items-center gap-2 mb-4 pb-2 border-b border-border">
                                <div className="w-5 h-5 bg-muted rounded"></div>
                                <div className="w-16 h-5 bg-muted rounded"></div>
                            </div>
                            <div className="space-y-3">
                                <div className="w-24 h-4 bg-muted rounded"></div>
                                <div className="space-y-2">
                                    {[1, 2, 3].map(i => (
                                        <div key={i} className="flex items-center gap-3">
                                            <div className="w-4 h-4 bg-muted rounded"></div>
                                            <div className="w-20 h-4 bg-muted rounded"></div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* Cards Skeleton */}
                        <div className="lg:col-span-3">
                            <div className="w-48 h-4 bg-muted rounded mb-4 animate-pulse"></div>
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                                {[1, 2, 3, 4, 5, 6].map(i => (
                                    <div key={i} className="bg-card rounded-2xl border border-border p-6 animate-pulse">
                                        <div className="w-16 h-16 bg-muted rounded-lg mb-4"></div>
                                        <div className="w-32 h-5 bg-muted rounded mb-2"></div>
                                        <div className="w-full h-4 bg-muted rounded mb-1"></div>
                                        <div className="w-3/4 h-4 bg-muted rounded"></div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                )}
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
                            <span className="text-sm font-semibold text-foreground">Selected:</span>
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
                            Compare Now <ArrowDown className="w-4 h-4 ml-2" />
                        </Button>
                    </div>
                </div>
            )}

            {/* Filters Section - Top Layout Only */}
            {filterStyle === 'top' && (showComparison || !shouldHideFilters) && (
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
                        labels={{ categories: config.categoriesLabel, features: config.featuresLabel }}
                    />
                </div>
            )}

            {/* Main Content Area */}
            <div className={cn(
                "w-full",
                !isCompareButtonMode && filterStyle === 'sidebar' && !shouldHideFilters ? "flex flex-col lg:grid lg:grid-cols-4 lg:gap-8" : "flex flex-col lg:flex-row gap-8"
            )}>
                {/* Sidebar Filters - Keep visible even when comparison is shown */}
                {!isCompareButtonMode && filterStyle === 'sidebar' && !shouldHideFilters && (
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
                            labels={{ categories: config.categoriesLabel, features: config.featuresLabel }}
                        />
                    </div>
                )}

                {/* Main List Area */}
                <div className={cn(
                    "flex-1",
                    !isCompareButtonMode && filterStyle === 'sidebar' && !shouldHideFilters ? "lg:col-span-3" : ""
                )}>
                    {/* Platform Grid Container - Only show if not in compare button mode */}
                    {!isCompareButtonMode && (
                        <div className="w-full">
                            {/* Active Filters Display */}
                            {(selectedCategories.length > 0 || selectedFeatures.length > 0) && (
                                <div className="mb-6 flex flex-wrap items-center gap-2">
                                    <span className="text-sm font-medium text-muted-foreground mr-2">Active filters:</span>
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
                                        Clear all
                                    </button>
                                </div>
                            )}
                            {/* Search & Sort Bar */}
                            <div className="mb-6 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                {/* Search Input */}
                                <div className="relative flex-1 max-w-md">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                    <input
                                        type="text"
                                        placeholder="Search by name..."
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
                                        <option value="default">Sort: Default</option>
                                        <option value="name-asc">Name: A to Z</option>
                                        <option value="name-desc">Name: Z to A</option>
                                        <option value="rating-desc">Rating: Highest</option>
                                        <option value="price-asc">Price: Lowest</option>
                                    </select>
                                    <ChevronDown className="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
                                </div>

                                {/* Results Count */}
                                <span className="text-sm text-muted-foreground whitespace-nowrap">
                                    {filteredItems.length} {filteredItems.length === 1 ? 'item' : 'items'}
                                </span>
                            </div>

                            <div className={cn(
                                "grid grid-cols-1 md:grid-cols-2 gap-6",
                                filterStyle === 'sidebar'
                                    ? "lg:grid-cols-2 xl:grid-cols-3"
                                    : "lg:grid-cols-3 xl:grid-cols-4"
                            )}>
                                {/* Show cards - display ALL visibleCount cards, Show All card is extra */}
                                {displayedItems.map(item => {
                                    // Check if featured
                                    // Check if featured
                                    // Config is hoisted, use it directly
                                    const isFeatured = config.featured && config.featured.includes(item.id);

                                    return (
                                        <PlatformCard
                                            key={item.id}
                                            item={item}
                                            isSelected={selectedItems.includes(item.id)}
                                            onSelect={handleSelectItem}
                                            onViewDetails={handleViewDetails}
                                            disabled={selectedItems.length >= MAX_COMPARE && !selectedItems.includes(item.id)}
                                            isFeatured={isFeatured}
                                            activeCategories={selectedCategories} // Pass filters for dynamic badge display
                                            enableComparison={config.enableComparison !== false}
                                            buttonText={config.buttonText}
                                        />
                                    );
                                })}

                                {/* Show More Card - only if showAllEnabled */}
                                {hasMore && config.showAllEnabled !== false && (
                                    <div
                                        onClick={() => setVisibleCount(filteredItems.length)}
                                        className="bg-card rounded-2xl border-2 border-dashed border-border p-6 flex flex-col items-center justify-center text-center cursor-pointer hover:border-primary hover:bg-primary/5 transition-all group"
                                    >
                                        <div className="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                                            <ArrowDown className="w-6 h-6 text-primary" />
                                        </div>
                                        <h3 className="font-bold text-lg mb-1">Show All Items</h3>
                                        <p className="text-sm text-muted-foreground">
                                            Click to reveal {filteredItems.length - visibleCount} more
                                        </p>
                                    </div>
                                )}
                            </div>

                            {filteredItems.length === 0 && (
                                <div className="text-center p-12 bg-muted/20 rounded-xl">
                                    <p>No items match your filters.</p>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>

            {/* Inline Comparison Section */}
            {selectedItems.length > 0 && showComparison && (
                <div ref={comparisonRef} className="mt-16 pt-8 border-t border-border w-full">
                    <h2 className="text-2xl font-bold mb-6 px-2">Detailed Comparison</h2>
                    <ComparisonTable items={selectedItemObjects} onRemove={handleRemoveFromComparison} />
                </div>
            )}

            {/* Pricing Popup */}
            {detailsItem && (
                <PricingPopup item={detailsItem} onClose={handleCloseDetails} showPlanButtons={config.showPlanButtons} />
            )}
        </div>
    );
};

// Mount
// Helper to get React props from data attributes
const getPropsFromRoot = (rootId: string | HTMLElement) => {
    const root = typeof rootId === 'string' ? document.getElementById(rootId) : rootId;
    if (root && root.dataset.config) {
        try {
            return JSON.parse(root.dataset.config);
        } catch (e) {
            console.error("Error parsing config", e);
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

// Mount Hero Instances (potentially multiple or single)
// Currently only supporting one ID based on typical shortcode pattern implies one per page usually, 
// but let's query selector all if possible? No, React roots need unique management. 
// For now, let's grab the first one matching the ID we output in PHP.
// For now, let's grab the first one matching the ID or Class
const heroRoot = document.querySelector('.wpc-hero-root') || document.getElementById('wpc-hero-root') || document.getElementById('ecommerce-guider-hero-root');
if (heroRoot) {
    const config = getPropsFromRoot(heroRoot as HTMLElement);
    // We need a wrapper component to fetch the specific provider data
    import('./components/PlatformHeroWrapper').then(({ default: PlatformHeroWrapper }) => {
        ReactDOM.createRoot(heroRoot as HTMLElement).render(
            <React.StrictMode>
                <PlatformHeroWrapper itemId={config.itemId || config.providerId} />
            </React.StrictMode>
        );
    });
}

