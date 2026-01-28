import { X } from "lucide-react";
import { useEffect, useState } from "react";
import { ComparisonItem } from "./PlatformCard";
import PricingTable from "./PricingTable";

interface PricingPopupProps {
    item: ComparisonItem;
    onClose: () => void;
    showPlanButtons?: boolean;
    config?: any;
    onHydrate?: (ids: string[]) => Promise<void>;
}

const PricingPopup = ({ item, onClose, showPlanButtons, config, onHydrate }: PricingPopupProps) => {
    const [isLoading, setIsLoading] = useState(false);

    // Hydration Logic
    useEffect(() => {
        // Check if we have the "light" version (stripped by PHP optimization)
        // We know we stripped 'content' and 'product_details' and detailed 'pricing_plans'
        const isLightVersion = !item.content && (!item.pricing_plans?.[0]?.features && !item.pricing_plans?.[0]?.link);

        if (isLightVersion && onHydrate) {
            if (!isLoading) {
                console.log('Hydrating item:', item.id);
                setIsLoading(true);
                onHydrate([item.id]).catch(() => setIsLoading(false));
            }
        }
    }, [item.id]); // Run when item ID changes (or mount)

    // Watch for item updates to clear loading
    useEffect(() => {
        const isLightVersion = !item.content && (!item.pricing_plans?.[0]?.features);
        if (!isLightVersion && isLoading) {
            setIsLoading(false);
        }
    }, [item, isLoading]);

    // Lock body scroll when popup is open
    useEffect(() => {
        document.body.style.overflow = 'hidden';
        return () => {
            document.body.style.overflow = 'unset';
        };
    }, []);

    // Color Priority Logic:
    // ... (rest of color logic)
    const isListContext = !!config;
    const listHasColors = !!(config?.colors?.primary || config?.colorsOverride?.primary);
    const isItemOverrideEnabled = (item.design_overrides?.enabled === true || item.design_overrides?.enabled === '1');

    const getPrimaryColor = () => {
        if (isListContext) {
            if (listHasColors) {
                return config?.colors?.primary || config?.colorsOverride?.primary;
            }
            return (window as any).wpcSettings?.colors?.primary || '#6366f1';
        }
        if (isItemOverrideEnabled && item.design_overrides?.primary) {
            return item.design_overrides.primary;
        }
        return (window as any).wpcSettings?.colors?.primary || '#6366f1';
    };

    const primaryColor = getPrimaryColor();

    return (
        <div className="wpc-root fixed inset-0 z-[10000] bg-background/95 backdrop-blur-sm overflow-y-auto p-4 md:p-8 flex items-start justify-center pt-8 md:pt-16">
            <div className="relative bg-card w-full max-w-6xl rounded-2xl shadow-2xl border border-border p-6 md:p-10 mb-8 flex flex-col min-h-[400px]">
                <button
                    onClick={onClose}
                    aria-label={(window as any).wpcSettings?.texts?.close || 'Close'}
                    className="absolute top-4 right-4 p-2 rounded-full transition-colors z-10 border"
                    style={{
                        backgroundColor: primaryColor,
                        color: 'var(--wpc-btn-text, #ffffff) !important',
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

                {isLoading ? (
                    <div className="flex-1 flex flex-col items-center justify-center min-h-[300px]">
                        <div className="w-12 h-12 border-4 border-muted border-t-primary rounded-full animate-spin mb-4" style={{ borderTopColor: primaryColor }}></div>
                        <p className="text-muted-foreground animate-pulse">Loading details...</p>
                    </div>
                ) : (
                    <PricingTable
                        item={item}
                        showPlanButtons={showPlanButtons}
                        showHeaders={true}
                        displayContext="popup"
                        config={config}
                    />
                )}
            </div>
        </div>
    );
};

export default PricingPopup;

