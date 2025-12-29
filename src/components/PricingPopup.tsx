import { X } from "lucide-react";
import { useEffect } from "react";
import { ComparisonItem } from "./PlatformCard";
import PricingTable from "./PricingTable";

interface PricingPopupProps {
    item: ComparisonItem;
    onClose: () => void;
    showPlanButtons?: boolean;
}

const PricingPopup = ({ item, onClose, showPlanButtons }: PricingPopupProps) => {
    // Lock body scroll when popup is open
    useEffect(() => {
        document.body.style.overflow = 'hidden';
        return () => {
            document.body.style.overflow = 'unset';
        };
    }, []);

    return (
        <div className="fixed inset-0 z-[10000] bg-background/95 backdrop-blur-sm overflow-y-auto p-4 md:p-8 flex items-start justify-center pt-8 md:pt-16">
            <div className="relative bg-card w-full max-w-6xl rounded-2xl shadow-2xl border border-border p-6 md:p-10 mb-8 flex flex-col">
                <button
                    onClick={onClose}
                    className="absolute top-4 right-4 p-2 rounded-full transition-colors z-10 border"
                    style={{
                        backgroundColor: (window as any).wpcSettings?.colors?.primary || '#6366f1',
                        color: 'white',
                        borderColor: (window as any).wpcSettings?.colors?.primary || '#6366f1',
                    }}
                    onMouseEnter={(e) => {
                        const hoverColor = (window as any).wpcSettings?.colors?.hoverButton;
                        const primaryColor = (window as any).wpcSettings?.colors?.primary || '#6366f1';
                        if (hoverColor) e.currentTarget.style.backgroundColor = hoverColor;
                        else e.currentTarget.style.filter = 'brightness(85%)';
                    }}
                    onMouseLeave={(e) => {
                        e.currentTarget.style.backgroundColor = (window as any).wpcSettings?.colors?.primary || '#6366f1';
                        e.currentTarget.style.filter = '';
                    }}
                >
                    <X className="w-6 h-6" />
                </button>

                <PricingTable
                    item={item}
                    showPlanButtons={showPlanButtons}
                    showHeaders={true}
                    displayContext="popup"
                />
            </div>
        </div>
    );
};

export default PricingPopup;
