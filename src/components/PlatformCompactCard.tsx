import React from 'react';
import { ComparisonItem } from './PlatformCard';
import { Check, Tag } from 'lucide-react';
import { cn } from '@/lib/utils';

interface PlatformCompactCardProps {
    item: ComparisonItem;
    onToggleCompare: (id: string, selected: boolean) => void;
    isSelected: boolean;
    onViewDetails?: (id: string) => void;
    enableComparison?: boolean;
    buttonText?: string;
    badgeText?: string;
    badgeColor?: string;
    badgeStyle?: string;
    showRating?: boolean;
    showPrice?: boolean;
    showCheckboxes?: boolean;
    viewAction?: 'popup' | 'link';
    activeCategories?: string[];
    labels?: {
        selectToCompare?: string;
        copied?: string;
        viewDetails?: string;
        visitSite?: string;
        getCoupon?: string;
        featureProducts?: string;
        featureFees?: string;
        featureSupport?: string;
    };
    config?: any;
}

const PlatformCompactCard: React.FC<PlatformCompactCardProps> = ({
    item,
    onToggleCompare,
    isSelected,
    onViewDetails,
    enableComparison = true,
    buttonText,
    badgeText,
    badgeColor,
    badgeStyle = 'floating',
    showRating = true,
    showPrice = true,
    showCheckboxes = true,
    viewAction = 'popup',
    activeCategories,
    labels,
    config,
}) => {

    const getBadgeColor = () => {
        return badgeColor || item.featured_badge_color || (window as any).wpcSettings?.colors?.primary || '#6366f1';
    };

    // Normalize comparison props to handle string inputs from PHP
    const isComparisonEnabled = enableComparison !== false && (enableComparison as any) !== '0';
    const isCheckboxesVisible = showCheckboxes !== false && (showCheckboxes as any) !== '0';

    const isFloating = badgeStyle !== 'flush';
    const finalBadgeText = badgeText || item.featured_badge_text;

    // Handle View Click
    const handleViewClick = (e: React.MouseEvent) => {
        e.stopPropagation();
        if (viewAction === 'popup' && onViewDetails) {
            onViewDetails(item.id);
        } else {
            const url = item.direct_link || item.details_link;
            if (url) {
                const target = config?.targetDirect || (window as any).wpcSettings?.target_direct || '_blank';
                window.open(url, target);
            }
        }
    };

    return (
        <div
            onClick={(e) => {
                if (window.getSelection()?.toString()) return;
                // Strict Interaction: Select if enabled, otherwise do nothing
                if (isComparisonEnabled) {
                    onToggleCompare(item.id, !isSelected); // Compact uses onToggleCompare
                }
            }}
            className={cn(
                "group relative flex flex-col p-4 rounded-xl border bg-card text-card-foreground shadow-sm transition-all hover:shadow-md h-full",
                isComparisonEnabled ? "cursor-pointer" : "cursor-default", // Pointer only if selectable
                isFloating ? '' : 'overflow-hidden'
            )}
            style={{
                borderColor: (window as any).wpcSettings?.colors?.border || undefined
            }}
        >
            {/* Badge */}
            {finalBadgeText && (
                isFloating ? (
                    <div className="absolute -top-3 left-4 z-10">
                        <span
                            className="bg-primary text-primary-foreground text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded shadow-sm"
                            style={{ backgroundColor: getBadgeColor() }}
                        >
                            {finalBadgeText}
                        </span>
                    </div>
                ) : (
                    <div className="absolute top-0 left-0">
                        <span
                            className="bg-primary text-primary-foreground text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-br-lg rounded-tl-xl shadow-sm"
                            style={{ backgroundColor: getBadgeColor() }}
                        >
                            {finalBadgeText}
                        </span>
                    </div>
                )
            )}

            {/* Selection Indicator */}
            {isComparisonEnabled && isCheckboxesVisible && (
                <div
                    className={`absolute top-2 right-2 w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors z-20 cursor-pointer ${isSelected ? 'bg-primary border-primary' : 'border-border bg-background group-hover:border-primary'}`}
                    style={{
                        backgroundColor: isSelected ? ((window as any).wpcSettings?.colors?.primary || undefined) : undefined,
                        borderColor: isSelected ? ((window as any).wpcSettings?.colors?.primary || undefined) : undefined
                    }}
                    onClick={(e) => {
                        e.stopPropagation();
                        if (enableComparison) {
                            onToggleCompare(item.id, !isSelected);
                        }
                    }}
                >
                    {isSelected && (
                        <svg className="w-3 h-3 text-primary-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                        </svg>
                    )}
                </div>
            )}

            <div className="flex items-center gap-3 mb-3 pt-2">
                {/* Small Logo */}
                <div className="w-10 h-10 flex-shrink-0 bg-muted/10 rounded-lg p-1 flex items-center justify-center">
                    {item.logo ? (
                        <img src={item.logo} alt={item.name} className="w-full h-full object-contain" />
                    ) : (
                        <div className="w-full h-full bg-muted/20 rounded"></div>
                    )}
                </div>

                <div className="flex-1 min-w-0">
                    <h3 className="font-bold text-sm leading-tight truncate">{item.name}</h3>
                    {showRating && (
                        <div className="flex items-center gap-1">
                            <span className="text-yellow-400 text-xs">★</span>
                            <span className="text-xs font-medium">{item.rating}</span>
                        </div>
                    )}
                </div>

                {showPrice && (
                    <div className="text-right">
                        <span
                            className="block font-bold text-lg leading-none text-primary"
                            style={{ color: (window as any).wpcSettings?.colors?.primary || undefined }}
                        >
                            {item.price}
                        </span>
                    </div>
                )}
            </div>

            {/* Use Coupon Button if exists and enabled */}
            {item.show_coupon && item.coupon_code && (
                <div className="mb-4">
                    <button
                        onClick={(e) => {
                            e.stopPropagation();
                            const couponCode = item.coupon_code;

                            // Try modern clipboard API first
                            if (navigator.clipboard && navigator.clipboard.writeText) {
                                navigator.clipboard.writeText(couponCode as string).then(() => {
                                    const target = e.currentTarget;
                                    const originalHTML = target.innerHTML;
                                    const successColor = (window as any).wpcSettings?.colors?.copied || '#10b981';
                                    const copiedText = item.copiedLabel || labels?.copied || "Copied!";

                                    target.innerHTML = '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z"/></svg> ' + copiedText;
                                    target.style.background = successColor;
                                    target.style.borderColor = successColor;
                                    target.style.color = '#ffffff';

                                    setTimeout(() => {
                                        target.innerHTML = originalHTML;
                                        target.style.background = '';
                                        target.style.borderColor = '';
                                        target.style.color = '';
                                    }, 1500);
                                });
                            } else {
                                // Fallback for older browsers
                                const textArea = document.createElement('textarea');
                                textArea.value = couponCode as string;
                                textArea.style.position = 'fixed';
                                textArea.style.left = '-999999px';
                                document.body.appendChild(textArea);
                                textArea.select();
                                try {
                                    document.execCommand('copy');
                                    const target = e.currentTarget;
                                    const originalHTML = target.innerHTML;
                                    const successColor = (window as any).wpcSettings?.colors?.copied || '#10b981';
                                    const copiedText = item.copiedLabel || labels?.copied || "Copied!";

                                    target.innerHTML = '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z"/></svg> ' + copiedText;
                                    target.style.background = successColor;
                                    target.style.borderColor = successColor;
                                    target.style.color = '#ffffff';

                                    setTimeout(() => {
                                        target.innerHTML = originalHTML;
                                        target.style.background = '';
                                        target.style.borderColor = '';
                                        target.style.color = '';
                                    }, 1500);
                                } catch (err) {
                                    console.error('Copy failed:', err);
                                }
                                document.body.removeChild(textArea);
                            }
                        }}
                        className="w-full py-1.5 text-xs font-bold border border-dashed rounded-lg transition-all duration-200 relative z-20 flex items-center justify-center gap-2 cursor-pointer"
                        style={{
                            backgroundColor: item.design_overrides?.coupon_bg || config?.colors?.couponBg || (window as any).wpcSettings?.colors?.couponBg || '#fef3c7',
                            color: item.design_overrides?.coupon_text || config?.colors?.couponText || (window as any).wpcSettings?.colors?.couponText || '#92400e',
                            borderColor: `${item.design_overrides?.coupon_text || config?.colors?.couponText || (window as any).wpcSettings?.colors?.couponText || '#92400e'}40`
                        }}
                        onMouseEnter={(e) => {
                            const hoverColor = config?.colors?.couponHover || (window as any).wpcSettings?.colors?.couponHover || '#fde68a';
                            e.currentTarget.style.backgroundColor = hoverColor;
                        }}
                        onMouseLeave={(e) => {
                            e.currentTarget.style.backgroundColor = item.design_overrides?.coupon_bg || config?.colors?.couponBg || (window as any).wpcSettings?.colors?.couponBg || '#fef3c7';
                        }}
                    >
                        <Tag className="w-3 h-3" /> {labels?.getCoupon || "Get Coupon:"} {item.coupon_code}
                    </button>
                </div>
            )}

            {/* Categories */}
            <div className="flex flex-wrap gap-2 mb-4">
                {(() => {
                    const limit = 2;
                    let toShow: string[] = [];

                    if (activeCategories && activeCategories.length > 0) {
                        const matches = item.category.filter(c => activeCategories.includes(c));
                        toShow.push(...matches);
                    }

                    if (item.primary_categories && item.primary_categories.length > 0) {
                        item.primary_categories.forEach(pc => {
                            if (!toShow.includes(pc) && item.category.includes(pc)) {
                                toShow.push(pc);
                            }
                        });
                    }

                    if (toShow.length < limit) {
                        item.category.forEach(c => {
                            if (!toShow.includes(c)) toShow.push(c);
                        });
                    }

                    return toShow.slice(0, limit).map((cat) => (
                        <span key={cat} className={cn(
                            "px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider",
                            activeCategories?.includes(cat)
                                ? "bg-primary text-primary-foreground"
                                : "bg-secondary text-secondary-foreground"
                        )}>
                            {cat}
                        </span>
                    ));
                })()}
            </div>

            {/* Key Features */}
            <ul className="space-y-2 mb-2">
                {item.raw_features && item.raw_features.length > 0 ? (
                    item.raw_features.slice(0, 3).map((feature, i) => (
                        <li key={i} className="flex items-center gap-2 text-sm text-foreground/80">
                            <Check className="w-4 h-4 text-primary flex-shrink-0" />
                            <span className="truncate">{feature}</span>
                        </li>
                    ))
                ) : (
                    <>
                        {item.features?.products && (
                            <li className="flex items-center gap-2 text-sm text-foreground/80">
                                <Tag className="w-4 h-4 text-primary flex-shrink-0" />
                                <span className="truncate">{item.features.products} {labels?.featureProducts || "Products"}</span>
                            </li>
                        )}
                        {item.features?.fees && (
                            <li className="flex items-center gap-2 text-sm text-foreground/80">
                                <Check className="w-4 h-4 text-primary flex-shrink-0" />
                                <span className="truncate">{item.features.fees} {labels?.featureFees || "Trans. Fees"}</span>
                            </li>
                        )}
                        <li className="flex items-center gap-2 text-sm text-foreground/80">
                            <Check className="w-4 h-4 text-primary flex-shrink-0" />
                            <span className="truncate">{item.features?.support || '24/7'} {labels?.featureSupport || "Support"}</span>
                        </li>
                    </>
                )}
            </ul>

            {/* Minimal Actions */}
            <div className="mt-auto grid grid-cols-2 gap-2">
                <button
                    type="button"
                    className="col-span-2 bg-primary text-primary-foreground h-8 px-3 rounded-md text-xs font-bold transition-colors hover:opacity-90"
                    style={{
                        backgroundColor: (window as any).wpcSettings?.colors?.primary || undefined
                    }}
                    onMouseEnter={(e) => {
                        const hoverColor = (window as any).wpcSettings?.colors?.hoverButton;
                        if (hoverColor) e.currentTarget.style.backgroundColor = hoverColor;
                    }}
                    onMouseLeave={(e) => {
                        e.currentTarget.style.backgroundColor = (window as any).wpcSettings?.colors?.primary || '';
                    }}
                    onClick={handleViewClick}
                >
                    {buttonText || (viewAction === 'link' ? (labels?.visitSite || "Visit") : (labels?.viewDetails || "View"))}
                </button>
            </div>
        </div>
    );
};

export default PlatformCompactCard;
