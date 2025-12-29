import { Check, Star, ShoppingCart, Tag } from "lucide-react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";

export interface PricingPlan {
    name: string;
    price: string;
    period: string;
    features: string;
    link: string;
    show_button?: string;
    button_text?: string;
    show_banner?: string;
    banner_text?: string;
    banner_color?: string;
}

export interface ComparisonItem {
    id: string;
    name: string;
    logo: string;
    rating: number;
    category: string[];
    primary_categories?: string[];
    price: string;
    period: string;
    features: {
        [key: string]: any; // Generic features
        ssl: boolean;
        support: string;
    };
    pricing_plans?: PricingPlan[];
    hide_plan_features?: boolean;
    show_plan_links?: boolean;
    show_coupon?: boolean;
    pros: string[];
    cons: string[];
    raw_features?: string[];
    details_link?: string;
    button_text?: string;
    permalink?: string;
    description?: string;
    dashboard_image?: string;
    badge?: {
        text: string;
        color: string;
    };
    // Top-level visibility override (independent of design overrides)
    show_footer_popup?: boolean | string;
    show_footer_table?: boolean | string;
    footer_button_text?: string;
    coupon_code?: string;
    featured_badge_text?: string;
    featured_badge_color?: string;
    direct_link?: string;
    content?: string;
    design_overrides?: {
        enabled: boolean | string;
        primary?: string;
        accent?: string;
        border?: string;
        show_footer?: boolean | string;
        show_footer_popup?: boolean | string;
        show_footer_table?: boolean | string;
        footer_text?: string;
    };
}

interface PlatformCardProps {
    item: ComparisonItem;
    isSelected: boolean;
    onSelect: (id: string) => void;
    onViewDetails: (id: string) => void;
    disabled?: boolean;
    isFeatured?: boolean;
    activeCategories?: string[];
    enableComparison?: boolean;
    buttonText?: string;
}

const PlatformCard = ({ item, isSelected, onSelect, onViewDetails, disabled, isFeatured, activeCategories, enableComparison = true, buttonText }: PlatformCardProps) => {
    // Determine Badge Style
    const badgeColorMap: Record<string, string> = {
        red: "bg-red-500 text-white border-red-600",
        amber: "bg-amber-500 text-white border-amber-600",
        green: "bg-emerald-500 text-white border-emerald-600",
        blue: "bg-blue-500 text-white border-blue-600",
        purple: "bg-purple-500 text-white border-purple-600",
    };

    const hasCustomBadge = item.badge && item.badge.text;
    const badgeStyle = hasCustomBadge && item.badge?.color ? badgeColorMap[item.badge.color] || badgeColorMap.blue : "";

    // Handle Coupon Copy
    const handleCopyCoupon = (e: React.MouseEvent) => {
        e.stopPropagation();
        if (item.coupon_code) {
            navigator.clipboard.writeText(item.coupon_code);
            // The inline feedback is handled by onClick in the JSX
        }
    };

    // Handle Details View
    const handleDetailsClick = (e: React.MouseEvent) => {
        e.stopPropagation();
        onViewDetails(item.id);
    };

    const handleTrackClick = () => {
        // Fire and forget
        if ((window as any).wpcSettings?.apiUrl) {
            const baseUrl = (window as any).wpcSettings.apiUrl.replace('/items', '');
            const trackUrl = `${baseUrl}/track/click?id=${item.id}`;
            navigator.sendBeacon(trackUrl);
        } else if ((window as any).ecommerceGuiderSettings?.apiUrl) {
            // Legacy / Fallback
            const baseUrl = (window as any).ecommerceGuiderSettings.apiUrl.replace('/providers', '');
            const trackUrl = `${baseUrl}/track/click?id=${item.id}`;
            navigator.sendBeacon(trackUrl);
        }
    };

    return (
        <div
            className={cn(
                "relative bg-card rounded-2xl p-5 transition-all duration-300 cursor-pointer group",
                isSelected
                    ? "border-primary shadow-lg ring-2 ring-primary/20 scale-[1.02]"
                    : "border-border hover:border-primary/50 hover:shadow-xl hover:-translate-y-1",
                disabled && !isSelected && "opacity-50 cursor-not-allowed",
                isFeatured ? "border-4" : "border-2", // Apply border-4 if featured, else border-2
                isFeatured && !isSelected && "bg-amber-50/10" // Keep background for featured non-selected
            )}
            style={{
                borderColor: isFeatured
                    ? (item.featured_badge_color || (window as any).wpcSettings?.featured_color || (window as any).ecommerceGuiderSettings?.featured_color || '#6366f1')
                    : undefined
            }}
            onClick={() => {
                if (!disabled) {
                    onSelect(item.id);
                    handleTrackClick();
                }
            }}
        >
            {/* Custom Badge (Overrides Featured if present, or stacks) */}
            {hasCustomBadge ? (
                <div className={cn(
                    "absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider shadow-sm z-10 border",
                    badgeStyle
                )}>
                    {item.badge?.text}
                </div>
            ) : null}

            {/* Featured Badge */}
            {isFeatured && (
                <div
                    className="absolute -top-3 -right-3 px-3 py-1 bg-primary text-primary-foreground rounded-full text-xs font-bold shadow-lg z-10"
                    style={{
                        backgroundColor: item.featured_badge_color || (window as any).wpcSettings?.featured_color || (window as any).ecommerceGuiderSettings?.featured_color || '#6366f1',
                        color: 'white'
                    }}
                >
                    {item.featured_badge_text || 'Top Choice'}
                </div>
            )}

            {/* Selection indicator & Logo ... (unchanged) */}
            {enableComparison && (
                <div className={cn(
                    "absolute top-4 right-4 w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all",
                    isSelected
                        ? "bg-primary border-primary"
                        : "border-border bg-background group-hover:border-primary/50"
                )}>
                    {isSelected && <Check className="w-4 h-4 text-primary-foreground" />}
                </div>
            )}

            <div className="flex items-center gap-3 mb-4">
                <div className="w-12 h-12 rounded-xl bg-white p-1 shadow-sm border border-border/50 flex items-center justify-center overflow-hidden">
                    {item.logo ? (
                        <img src={item.logo} alt={item.name} className="w-full h-full object-contain" />
                    ) : (
                        <ShoppingCart className="w-6 h-6 text-muted-foreground" />
                    )}
                </div>
                <div>
                    <h3 className="font-display font-bold text-lg text-foreground leading-tight">{item.name}</h3>
                    <div className="flex items-center gap-1">
                        <div className="flex">
                            {[1, 2, 3, 4, 5].map((s) => (
                                <Star key={s} className={cn("w-3 h-3", s <= Math.round(item.rating) ? "fill-amber-400 text-amber-400" : "fill-muted text-muted")} />
                            ))}
                        </div>
                        <span className="text-xs font-medium text-muted-foreground ml-1">({item.rating})</span>
                    </div>
                </div>
            </div>

            {/* Price */}
            <div className="mb-6 p-3 bg-muted/30 rounded-lg text-center group-hover:bg-muted/50 transition-colors">
                <span className="text-3xl font-display font-bold text-primary">{item.price}</span>
                {item.period && <span className="text-sm text-muted-foreground">{item.period}</span>}
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
                                    target.innerHTML = '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z"/></svg> Copied!';
                                    target.style.background = 'rgb(16, 185, 129)';
                                    target.style.borderColor = 'rgb(16, 185, 129)';
                                    target.style.color = 'white';
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
                                    target.innerHTML = '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z"/></svg> Copied!';
                                    target.style.background = 'rgb(16, 185, 129)';
                                    target.style.borderColor = 'rgb(16, 185, 129)';
                                    target.style.color = 'white';
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
                        className="w-full py-1.5 text-xs font-bold text-primary border border-dashed border-primary/50 rounded-lg bg-primary/5 hover:bg-primary/10 transition-colors flex items-center justify-center gap-2"
                    >
                        <Tag className="w-3 h-3" /> Get Coupon: {item.coupon_code}
                    </button>
                </div>
            )}

            {/* Categories */}
            <div className="flex flex-wrap gap-2 mb-4">
                {(() => {
                    // Logic: Show 2 categories. 
                    // Priority 1: From active filters
                    // Priority 2: Primary Categories
                    // Priority 3: Others
                    const limit = 2;
                    let toShow: string[] = [];

                    // 1. Matched Filters
                    if (activeCategories && activeCategories.length > 0) {
                        const matches = item.category.filter(c => activeCategories.includes(c));
                        toShow.push(...matches);
                    }

                    // 2. Desired Primary (if not already shown)
                    if (item.primary_categories && item.primary_categories.length > 0) {
                        item.primary_categories.forEach(pc => {
                            if (!toShow.includes(pc) && item.category.includes(pc)) {
                                toShow.push(pc);
                            }
                        });
                    }

                    // 3. Fallback to any remaining (if not full)
                    if (toShow.length < limit) {
                        item.category.forEach(c => {
                            if (!toShow.includes(c)) toShow.push(c);
                        });
                    }

                    return toShow.slice(0, limit).map((cat) => (
                        <span key={cat} className={cn(
                            "px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider",
                            // Highlight if it matches active filter
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
                        {/* Coupon Code - REDUNDANT BLOCK REMOVED FOR CLEANUP */}

                        {/* Fallback Features if raw not available */}
                        {/* Since features object is now generic, we need to check keys safely */}
                        {item.features.products && (
                            <li className="flex items-center gap-2 text-sm text-foreground/80">
                                <Tag className="w-4 h-4 text-primary flex-shrink-0" />
                                <span className="truncate">{item.features.products} Products</span>
                            </li>
                        )}
                        {item.features.fees && (
                            <li className="flex items-center gap-2 text-sm text-foreground/80">
                                <Check className="w-4 h-4 text-primary flex-shrink-0" />
                                <span className="truncate">{item.features.fees} Trans. Fees</span>
                            </li>
                        )}
                        <li className="flex items-center gap-2 text-sm text-foreground/80">
                            <Check className="w-4 h-4 text-primary flex-shrink-0" />
                            <span className="truncate">{item.features.support} Support</span>
                        </li>
                    </>
                )}
            </ul>

            {/* Details Link / Button */}
            <div className="mt-4 pt-4 border-t border-border/50 text-center">
                {enableComparison ? (
                    onViewDetails ? (
                        <button
                            type="button"
                            className="text-xs font-semibold group-hover:underline block w-full h-full cursor-pointer bg-transparent border-0 transition-colors"
                            style={{
                                color: (window as any).wpcSettings?.colors?.primary || undefined,
                            }}
                            onMouseEnter={(e) => {
                                const hoverColor = (window as any).wpcSettings?.colors?.hoverButton || (window as any).wpcSettings?.colors?.primary;
                                if (hoverColor) e.currentTarget.style.color = hoverColor;
                            }}
                            onMouseLeave={(e) => {
                                e.currentTarget.style.color = (window as any).wpcSettings?.colors?.primary || '';
                            }}
                            onClick={(e) => {
                                e.stopPropagation();
                                onViewDetails(item.id);
                            }}
                        >
                            {buttonText || "View Details"}
                        </button>
                    ) : (
                        <span className="text-xs font-semibold text-muted-foreground block w-full">Select to Compare</span>
                    )
                ) : (
                    <button
                        type="button"
                        className="bg-primary text-primary-foreground px-6 py-2.5 rounded-lg font-bold text-sm transition-all duration-200 w-full shadow-md hover:shadow-lg transform active:scale-95"
                        onMouseEnter={(e) => {
                            const hoverColor = (window as any).wpcSettings?.colors?.hoverButton;
                            if (hoverColor) e.currentTarget.style.backgroundColor = hoverColor;
                            else e.currentTarget.style.filter = 'brightness(90%)';
                        }}
                        onMouseLeave={(e) => {
                            e.currentTarget.style.backgroundColor = '';
                            e.currentTarget.style.filter = '';
                        }}
                        onClick={(e) => {
                            e.stopPropagation();
                            const url = item.direct_link || item.details_link;
                            if (url) window.location.href = url;
                        }}
                    >
                        {buttonText || item.button_text || "View Details"}
                    </button>
                )}
            </div>
        </div>
    );
};

export default PlatformCard;
