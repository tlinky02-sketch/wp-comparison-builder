import React from 'react';
import { ComparisonItem } from './PlatformCard';
import { Check, Tag } from 'lucide-react';
import { cn } from '@/lib/utils';

interface PlatformDetailedRowProps {
    item: ComparisonItem;
    index: number;
    onToggleCompare: (id: string, selected: boolean) => void;
    isSelected: boolean;
    onViewDetails?: (id: string) => void;
    enableComparison?: boolean;
    buttonText?: string;
    showRank?: boolean;
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
        featureSupport?: string;
    };
    config?: any;
}

const PlatformDetailedRow: React.FC<PlatformDetailedRowProps> = ({
    item,
    index,
    onToggleCompare,
    isSelected,
    onViewDetails,
    enableComparison = true,
    buttonText,
    showRank = true,
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

    const isFloating = badgeStyle === 'floating';
    const finalBadgeText = badgeText || item.featured_badge_text;

    // Normalize comparison props to handle string inputs from PHP
    const isComparisonEnabled = enableComparison !== false && (enableComparison as any) !== '0';
    const isCheckboxesVisible = showCheckboxes !== false && (showCheckboxes as any) !== '0';

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
                // Allow text selection
                if (window.getSelection()?.toString()) return;
                // Strict Interaction: Select if comparison enabled, otherwise do nothing
                if (isComparisonEnabled) {
                    onToggleCompare(item.id, !isSelected);
                }
            }}
            className={cn(
                `group relative grid grid-cols-1 md:grid-cols-12 gap-6 p-6 rounded-xl border bg-card text-card-foreground shadow-sm transition-all hover:shadow-md items-center`,
                isComparisonEnabled ? 'cursor-pointer' : 'cursor-default',
                isFloating ? '' : 'overflow-hidden'
            )}
            style={{
                borderColor: (window as any).wpcSettings?.colors?.border || undefined
            }}
        >
            {/* Rank / Badge for Mobile/Desktop */}
            {(showRank || finalBadgeText) && (
                isFloating ? (
                    // Floating Style
                    finalBadgeText && (
                        <div className="absolute -top-3 left-4 z-10">
                            <span
                                className="bg-primary text-primary-foreground text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-full shadow-lg"
                                style={{ backgroundColor: getBadgeColor() }}
                            >
                                {finalBadgeText}
                            </span>
                        </div>
                    )
                ) : (
                    // Flush Style
                    <div className="absolute top-0 left-0">
                        {finalBadgeText ? (
                            <span
                                className="bg-primary text-primary-foreground text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-br-lg rounded-tl-xl shadow-sm"
                                style={{ backgroundColor: getBadgeColor() }}
                            >
                                {finalBadgeText}
                            </span>
                        ) : (
                            showRank && (
                                <span className="flex items-center justify-center w-6 h-6 bg-muted text-muted-foreground font-bold text-xs rounded-br-lg rounded-tl-xl">
                                    {index + 1}
                                </span>
                            )
                        )}
                    </div>
                )
            )}

            {/* Selection Indicator */}
            {enableComparison && showCheckboxes && (
                <div
                    className={`absolute top-2 right-2 w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors z-20 cursor-pointer ${isSelected ? 'bg-primary border-primary' : 'border-border bg-background group-hover:border-primary'}`}
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
                        <svg className="w-4 h-4 text-primary-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                        </svg>
                    )}
                </div>
            )}

            {/* Logo Column (Span 2) */}
            <div className="md:col-span-2 flex justify-center md:justify-start pt-6 md:pt-0">
                <div className="w-16 h-16 md:w-20 md:h-20 p-2 rounded-lg bg-muted/10 flex items-center justify-center">
                    {item.logo ? (
                        <img
                            src={item.logo}
                            alt={item.name}
                            className="w-full h-full object-contain"
                        />
                    ) : (
                        <div className="w-full h-full text-[10px] flex items-center justify-center text-muted-foreground">{config?.labels?.noLogo || 'No Logo'}</div>
                    )}
                </div>
            </div>

            {/* Info Column (Span 4) */}
            <div className="md:col-span-4 text-center md:text-left space-y-1">
                <h3 className="font-bold text-lg leading-tight">{item.name}</h3>
                {showRating && (
                    <div className="flex items-center justify-center md:justify-start text-yellow-400 text-sm">
                        {[...Array(5)].map((_, i) => (
                            <span key={i}>{i < Math.floor(item.rating) ? '★' : '☆'}</span>
                        ))}
                        <span className="ml-1 text-xs text-muted-foreground font-medium">({item.rating})</span>
                    </div>
                )}

                {/* Categories */}
                <div className="flex flex-wrap justify-center md:justify-start gap-2 mt-2">
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

                {/* Product Details & Custom Fields */}
                {(item.product_details || (item.custom_fields && item.custom_fields.length > 0)) && (
                    <div className="mt-3 space-y-1 text-xs text-muted-foreground hidden md:block">
                        {item.product_details?.brand && <div><strong className="font-medium text-foreground/80">Brand:</strong> {item.product_details.brand}</div>}
                        {item.product_details?.sku && <div><strong className="font-medium text-foreground/80">SKU:</strong> {item.product_details.sku}</div>}
                        {item.product_details?.condition && item.product_details.condition !== 'NewCondition' && (
                            <div><strong className="font-medium text-foreground/80">Condition:</strong> {item.product_details.condition.replace(/Condition$/, '')}</div>
                        )}
                        {item.product_details?.availability && item.product_details.availability !== 'InStock' && (
                            <div><strong className="font-medium text-foreground/80">Status:</strong> {item.product_details.availability.replace(/([A-Z])/g, ' $1').trim()}</div>
                        )}
                        {item.product_details?.duration && (
                            <div><strong className="font-medium text-foreground/80">Duration:</strong> {item.product_details.duration.replace('PT', '').replace('H', 'h ').replace('M', 'm')}</div>
                        )}
                        {item.product_details?.service_type && (
                            <div><strong className="font-medium text-foreground/80">Type:</strong> {item.product_details.service_type}</div>
                        )}

                        {item.custom_fields?.map((cf, idx) => (
                            <div key={idx}><strong className="font-medium text-foreground/80">{cf.name}:</strong> {cf.value}</div>
                        ))}
                    </div>
                )}
            </div>

            {/* Feature Highlights (Span 3) - Only on Desktop */}
            <div className="hidden md:block md:col-span-3 text-sm text-muted-foreground border-l border-gray-100 pl-4 h-full flex flex-col justify-center">
                <ul className="space-y-1">
                    {item.raw_features && item.raw_features.length > 0 ? (
                        item.raw_features.slice(0, 3).map((feature, i) => (
                            <li key={i} className="flex items-center gap-2 text-xs">
                                <Check className="w-3 h-3 text-green-600 mr-2" />
                                <span className="truncate">{feature}</span>
                            </li>
                        ))
                    ) : (
                        <>
                            {item.features.products && (
                                <li className="flex items-center gap-2 text-xs">
                                    <Tag className="w-3 h-3 text-primary flex-shrink-0" />
                                    <span className="truncate">{item.features.products} Products</span>
                                </li>
                            )}
                            {item.features.fees && (
                                <li className="flex items-center gap-2 text-xs">
                                    <Check className="w-3 h-3 text-primary flex-shrink-0" />
                                    <span className="truncate">{item.features.fees} Trans. Fees</span>
                                </li>
                            )}
                            <li className="flex items-center gap-2 text-xs">
                                <Check className="w-3 h-3 text-primary flex-shrink-0" />
                                <span className="truncate">{item.features.support} Support</span>
                            </li>
                        </>
                    )}
                </ul>
            </div>

            {/* Actions Column (Span 3) */}
            <div className="md:col-span-3 flex flex-col items-center md:items-end justify-center gap-3 md:pl-4">
                {showPrice && (
                    <div className="flex items-baseline gap-1">
                        <span
                            className="text-xl font-bold text-primary"
                            style={{ color: (window as any).wpcSettings?.colors?.primary || undefined }}
                        >
                            {item.price}
                        </span>
                        {item.period && <span className="text-xs text-muted-foreground">{item.period}</span>}
                    </div>
                )}

                {/* Use Coupon Button if exists and enabled */}
                {item.show_coupon && item.coupon_code && (
                    <div className="w-full">
                        <button
                            onClick={(e) => {
                                e.stopPropagation();
                                const couponCode = item.coupon_code;

                                if (navigator.clipboard && navigator.clipboard.writeText) {
                                    navigator.clipboard.writeText(couponCode as string).then(() => {
                                        const target = e.currentTarget;
                                        const originalHTML = target.innerHTML;
                                        const successColor = (window as any).wpcSettings?.colors?.copied || '#10b981';
                                        const copiedText = labels?.copied || "Copied!";

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
                                    } catch (err) { console.error(err); }
                                    document.body.removeChild(textArea);
                                }
                            }}
                            className="w-full py-1 text-xs font-bold border border-dashed rounded-lg transition-all duration-200 relative z-20 flex items-center justify-center gap-2 cursor-pointer"
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
                            <Tag className="w-3 h-3" /> Coupon: {item.coupon_code}
                        </button>
                    </div>
                )}


                <div className="w-full space-y-2">
                    {/* Button - ALWAYS A BUTTON NOW */}
                    <button
                        type="button"
                        className="w-full bg-primary text-primary-foreground h-9 px-3 hover:bg-primary/90 inline-flex items-center justify-center whitespace-nowrap rounded-md text-xs font-bold uppercase tracking-wide transition-colors"
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
                        {buttonText || (viewAction === 'link' ? (labels?.visitSite || "Visit Site") : (labels?.viewDetails || "View Details"))}
                    </button>
                </div>
            </div>
        </div>
    );
};

export default PlatformDetailedRow;
