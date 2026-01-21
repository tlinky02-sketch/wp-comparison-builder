import { X } from "lucide-react";
import { useEffect, useState } from "react";
import { ComparisonItem } from "./PlatformCard";
import PricingTable from "./PricingTable";

interface PricingPopupProps {
    item: ComparisonItem;
    onClose: () => void;
    showPlanButtons?: boolean;
    config?: any;
}

const PricingPopup = ({ item, onClose, showPlanButtons, config }: PricingPopupProps) => {
    // Product Variants: Local Category State (defaults to active context or item default)
    const [localCategory, setLocalCategory] = useState<string | null>(config?.category || item.variants?.default_category || null);

    // Lock body scroll when popup is open
    useEffect(() => {
        document.body.style.overflow = 'hidden';
        return () => {
            document.body.style.overflow = 'unset';
        };
    }, []);

    // Color Priority Logic:
    // - Custom List context (config exists): List colors (if set) > Global (item overrides ALWAYS IGNORED)
    // - Item context (no config): item.design_overrides (if enabled) > Global

    // Detect if we're in a list context (config passed means we're in a list shortcode)
    const isListContext = !!config;
    const listHasColors = !!(config?.colors?.primary || config?.colorsOverride?.primary);
    const isItemOverrideEnabled = (item.design_overrides?.enabled === true || item.design_overrides?.enabled === '1');

    const getPrimaryColor = () => {
        if (isListContext) {
            // Custom List context: Use list colors if set, otherwise use global (IGNORE item overrides)
            if (listHasColors) {
                return config?.colors?.primary || config?.colorsOverride?.primary;
            }
            // List has no colors, use global
            return (window as any).wpcSettings?.colors?.primary || '#6366f1';
        }
        // Item context (not in a list): Item overrides (if enabled) > Global
        if (isItemOverrideEnabled && item.design_overrides?.primary) {
            return item.design_overrides.primary;
        }
        return (window as any).wpcSettings?.colors?.primary || '#6366f1';
    };

    const primaryColor = getPrimaryColor();

    return (
        <div className="fixed inset-0 z-[10000] bg-background/95 backdrop-blur-sm overflow-y-auto p-4 md:p-8 flex items-start justify-center pt-8 md:pt-16">
            <div className="relative bg-card w-full max-w-6xl rounded-2xl shadow-2xl border border-border p-6 md:p-10 mb-8 flex flex-col">
                <button
                    onClick={onClose}
                    aria-label={(window as any).wpcSettings?.texts?.close || 'Close'}
                    className="absolute top-4 right-4 p-2 rounded-full transition-colors z-10 border"
                    style={{
                        backgroundColor: primaryColor,
                        color: 'white',
                        borderColor: primaryColor,
                    }}
                    onMouseEnter={(e) => {
                        const hoverColor = (window as any).wpcSettings?.colors?.hoverButton;
                        if (hoverColor) e.currentTarget.style.backgroundColor = hoverColor;
                        else e.currentTarget.style.filter = 'brightness(85%)';
                    }}
                    onMouseLeave={(e) => {
                        e.currentTarget.style.backgroundColor = primaryColor;
                        e.currentTarget.style.filter = '';
                    }}
                >
                    <X className="w-6 h-6" />
                </button>

                {/* Product Variants: Category Selector */}
                {item.variants?.enabled && item.variants.plans_by_category && Object.keys(item.variants.plans_by_category).length > 0 && (
                    <div className="flex flex-wrap gap-2 mb-6 justify-center">
                        <button
                            onClick={() => setLocalCategory(null)}
                            className={`px-4 py-2 rounded-full text-sm font-bold transition-all border ${!localCategory
                                ? "text-white shadow-md scale-105"
                                : "bg-transparent text-muted-foreground border-border hover:bg-muted"
                                }`}
                            style={!localCategory ? {
                                backgroundColor: primaryColor,
                                borderColor: primaryColor,
                            } : {}}
                        >
                            {(window as any).wpcSettings?.texts?.allPlans || 'All Plans'}
                        </button>
                        {Object.keys(item.variants.plans_by_category).map((catSlug) => {
                            // Try to match slug to a pretty name from item.category if possible, else prettify slug
                            const prettyName = catSlug
                                .split('-')
                                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                                .join(' ');

                            const isActive = localCategory === catSlug;

                            return (
                                <button
                                    key={catSlug}
                                    onClick={() => setLocalCategory(catSlug)}
                                    className={`px-4 py-2 rounded-full text-sm font-bold transition-all border ${isActive
                                        ? "text-white shadow-md scale-105"
                                        : "bg-transparent text-muted-foreground border-border hover:bg-muted"
                                        }`}
                                    style={isActive ? {
                                        backgroundColor: primaryColor,
                                        borderColor: primaryColor,
                                    } : {}}
                                >
                                    {prettyName}
                                </button>
                            );
                        })}
                    </div>
                )}

                <PricingTable
                    item={item}
                    showPlanButtons={showPlanButtons}
                    showHeaders={true}
                    displayContext="popup"
                    config={{ ...config, category: localCategory }}
                />
            </div>
        </div>
    );
};

export default PricingPopup;

