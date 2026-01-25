import React from "react";
import { Check, Star, ShoppingCart, Tag } from "lucide-react";
import StarRating from "./StarRating";
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
    hero_button_text?: string;
    show_banner?: string;
    banner_text?: string;
    banner_color?: string;
    coupon?: string; // Coupon for the specific plan
}

export interface ComparisonItem {
    id: string;
    name: string;
    logo: string;
    rating: number;
    category: string[];
    primary_categories?: string[];
    primary_features?: string[];
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
    coupon_code?: string;
    pros: string[];
    cons: string[];
    raw_features?: string[];
    details_link?: string;
    button_text?: string;
    hero_button_text?: string;
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
    featured_badge_text?: string;
    featured_badge_color?: string;
    direct_link?: string;
    content?: string;
    hero_subtitle?: string; // New field for Hero
    analysis_label?: string; // New field for Hero
    table_btn_pos?: string;
    popup_btn_pos?: string;
    show_hero_logo?: boolean; // New field for Hero
    product_details?: {
        category?: string;
        brand?: string;
        sku?: string;
        gtin?: string;
        condition?: string;
        availability?: string;
        mfg_date?: string;
        exp_date?: string;
        service_type?: string;
        area_served?: string;
        duration?: string;
    };
    custom_fields?: Array<{ name: string; value: string; }>;
    design_overrides?: {
        enabled: boolean | string;
        primary?: string;
        accent?: string;
        border?: string;
        coupon_bg?: string;
        coupon_text?: string;
        coupon_hover?: string; // Added field
        copied_text?: string; // Added field
        show_footer?: boolean | string;
        show_footer_popup?: boolean | string;
        show_footer_table?: boolean | string;
        footer_text?: string;
        btn_text_color?: string;
    };
    // Per-item text label overrides
    prosLabel?: string;
    consLabel?: string;
    priceLabel?: string;
    ratingLabel?: string;
    moSuffix?: string;
    visitSiteLabel?: string;
    couponLabel?: string;
    copiedLabel?: string;
    featureHeader?: string;
    variants?: {
        enabled: boolean;
        plans_by_category?: { [key: string]: number[] };
        features_by_category?: { [key: string]: string[] };
        use_cases_by_category?: { [key: string]: number[] };
        default_category?: string;
        selector_style?: string;
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
    badgeText?: string;
    badgeColor?: string;
    badgeStyle?: string;
    showRating?: boolean;
    showPrice?: boolean;
    showCheckboxes?: boolean;
    viewAction?: 'popup' | 'link';
    index?: number;
    labels?: {
        selectToCompare?: string;
        copied?: string;
        viewDetails?: string;
        visitSite?: string;
        getCoupon?: string;
        featureProducts?: string;
        featureFees?: string;
        featureSupport?: string;
        featuredBadge?: string;
        logoLabel?: string;
    };
    config?: any;
}

const PlatformCard = ({
    item,
    isSelected,
    onSelect,
    onViewDetails,
    disabled,
    isFeatured,
    activeCategories,
    enableComparison = true,
    buttonText,
    badgeText,
    badgeColor,
    badgeStyle = 'floating',
    showRating = true,
    showPrice = true,
    showCheckboxes = true,
    viewAction = 'popup',
    index,
    labels,
    config,
}: PlatformCardProps) => {

    const handleTrackClick = () => {
        // Analytics tracking if needed
    };

    const handleDetailsClick = (e: React.MouseEvent) => {
        e.stopPropagation();
        if (viewAction === 'popup') {
            onViewDetails(item.id);
        } else {
            const url = item.direct_link || item.details_link;
            if (url) {
                const target = config?.targetDirect || (window as any).wpcSettings?.target_direct || '_blank';
                window.open(url, target);
            }
        }
        handleTrackClick();
    };

    // Determine badge styling
    const hasCustomBadge = !!item.badge?.text;
    const featuredText = badgeText || (isFeatured ? (item.featured_badge_text || labels?.featuredBadge || "Featured") : null);
    const featuredColor = badgeColor || item.featured_badge_color || ((window as any).wpcSettings?.colors?.primary) || "#6366f1";

    // Hierarchy: Item Override > Global Default
    const isDesignOverrideEnabled = item.design_overrides?.enabled === true || item.design_overrides?.enabled === '1';

    // Logic for Badge Style
    const badgeStyleInfo = (window as any).wpcSettings?.design_overrides?.badge_style || badgeStyle;
    const isFlush = badgeStyle === 'flush';

    // Normalize comparison props to handle string inputs from PHP
    const isComparisonEnabled = enableComparison !== false && (enableComparison as any) !== '0';
    const isCheckboxesVisible = showCheckboxes !== false && (showCheckboxes as any) !== '0';

    // Primary Color
    const globalPrimary = (window as any).wpcSettings?.colors?.primary || "#6366f1";
    const primaryColor = (isDesignOverrideEnabled && item.design_overrides?.primary)
        ? item.design_overrides.primary
        : globalPrimary;

    // Border Color
    const globalBorder = (window as any).wpcSettings?.colors?.border;
    const borderColor = (isDesignOverrideEnabled && item.design_overrides?.border)
        ? item.design_overrides.border
        : globalBorder;

    return (
        <div
            onClick={(e) => {
                // Allow text selection
                if (window.getSelection()?.toString()) return;

                // STRICT INTERACTION: Only clickable for SELECTION when comparison is ON.
                // When OFF, card body does NOTHING.
                if (isComparisonEnabled) {
                    if (!disabled) onSelect(item.id);
                }
            }}
            className={cn(
                "relative bg-card rounded-2xl p-5 transition-all duration-300 group flex flex-col h-full",
                // Pointer only if selectable
                isComparisonEnabled ? "cursor-pointer" : "cursor-default",

                // Active State (Selected) vs Default State (Normal + Hover)
                // Hover effects should applied ALWAYS for visual feedback, even if selection is disabled via master switch
                (isComparisonEnabled && isSelected)
                    ? "shadow-lg ring-2 scale-[1.02]" // Colors handled via style
                    : "hover:shadow-xl hover:-translate-y-1", // Border color handled via style

                disabled && !isSelected && "opacity-50 cursor-not-allowed",
                isFeatured ? "border-4" : "border-2",
                isFeatured && !isSelected && "bg-amber-50/10",
                isFlush && featuredText ? "overflow-hidden" : ""
            )}
            style={{
                // DYNAMIC BORDER COLOR HIERARCHY
                // 1. Featured & Not Selected -> Featured Badge Color
                // 2. Selected -> Primary Color
                // 3. Default -> Border Color (Global or Override)
                borderColor: (isFeatured && !isSelected)
                    ? featuredColor
                    : ((isComparisonEnabled && isSelected) ? primaryColor : borderColor),

                // DYNAMIC RING COLOR (For Selected State)
                // We use CSS variable for ring opacity but need javascript for the custom color
                '--tw-ring-color': isComparisonEnabled && isSelected ? `${primaryColor}33` : undefined, // 20% opacity approx
            } as React.CSSProperties}
        >
            {/* Custom Badge (Overrides Featured if present, or stacks) */}
            {hasCustomBadge ? (
                <div className={cn(
                    "absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider shadow-sm z-10 border",
                    badgeStyleInfo
                )}>
                    {item.badge?.text}
                </div>
            ) : (
                // Featured Badge logic
                featuredText && (
                    isFlush ? (
                        <div className="absolute top-0 left-0">
                            <span
                                className="inline-block px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-white rounded-br-lg"
                                style={{ backgroundColor: featuredColor }}
                            >
                                {featuredText}
                            </span>
                        </div>
                    ) : (
                        <div
                            className="absolute -top-3 -right-3 px-3 py-1 bg-primary text-primary-foreground rounded-full text-xs font-bold shadow-lg z-10"
                            style={{ backgroundColor: featuredColor }}
                        >
                            {featuredText}
                        </div>
                    )
                )
            )}

            {/* Selection Indicator - Conditional on Comparison Enabled */}
            {isComparisonEnabled && isCheckboxesVisible && (
                <div
                    onClick={(e) => {
                        e.stopPropagation();
                        if (!disabled) onSelect(item.id);
                    }}
                    className={cn(
                        "absolute top-4 right-4 w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors z-20 cursor-pointer",
                        isSelected
                            ? "bg-primary border-primary"
                            : "border-border bg-background group-hover:border-primary hover:border-primary"
                    )}
                >
                    {isSelected && <Check className="w-4 h-4 text-primary-foreground" />}
                </div>
            )}

            {/* Header: Logo + Name + Rating */}
            <div className="flex items-center gap-3 mb-4 pt-2">
                <div className="w-12 h-12 rounded-xl bg-white p-1 shadow-sm border border-border/50 flex items-center justify-center overflow-hidden shrink-0">
                    {item.logo ? (
                        <img src={item.logo} alt={item.name} className="w-full h-full object-contain" />
                    ) : (
                        <div className="w-full h-full bg-muted/10 flex items-center justify-center text-xs text-muted-foreground">{labels?.logoLabel || "Logo"}</div>
                    )}
                </div>
                <div className="min-w-0">
                    <h3 className="font-display font-bold text-lg text-foreground leading-tight truncate">{item.name}</h3>
                    {showRating && (
                        <StarRating
                            rating={item.rating || 0}
                            size={14}
                            color={(window as any).wpcSettings?.colors?.stars || 'var(--wpc-star-color, #fbbf24)'}
                            showLabel={true}
                        />
                    )}
                </div>
            </div>

            {/* Price Block */}
            {showPrice && (
                <div className="mb-4 p-3 bg-muted/30 rounded-lg text-center backdrop-blur-sm">
                    <span
                        className="text-3xl font-display font-bold text-primary"
                        ref={(el) => {
                            if (el) {
                                // Force priority over global text override
                                el.style.setProperty('color', primaryColor, 'important');
                            }
                        }}
                        style={{ color: primaryColor }}
                    >
                        {item.price}
                    </span>
                    {item.period && <span className="text-sm text-muted-foreground ml-1">{item.period}</span>}
                </div>
            )}

            {/* Coupon Button (If enabled) */}
            {item.show_coupon && item.coupon_code && (
                <div className="mb-4 w-full">
                    {(() => {
                        // COUPON COLOR HIERARCHY: List (Config) > Item (Meta) > Global
                        const globalColors = (window as any).wpcSettings?.colors || {};
                        const listColors = config?.colors || {};
                        // Use optional chaining directly on item.design_overrides

                        const bg = listColors.couponBg || item.design_overrides?.coupon_bg || globalColors.couponBg || '#fef3c7';
                        const text = listColors.couponText || item.design_overrides?.coupon_text || globalColors.couponText || '#92400e';
                        const hover = listColors.couponHover || item.design_overrides?.coupon_hover || globalColors.couponHover || '#fde68a';

                        // Copied state colors
                        const copiedColor = globalColors.copied || '#10b981'; // Usually global is fine for success state, but let's check hierarchy if needed
                        const copiedTextLabel = item.copiedLabel || labels?.copied || "Copied!";

                        return (
                            <button
                                onClick={(e) => {
                                    e.stopPropagation();
                                    const couponCode = item.coupon_code;

                                    // Try modern clipboard API first
                                    const copyAction = () => {
                                        const target = e.currentTarget;
                                        const originalHTML = target.innerHTML;

                                        target.innerHTML = '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z"/></svg> ' + copiedTextLabel;
                                        target.style.background = copiedColor;
                                        target.style.borderColor = copiedColor;
                                        target.style.color = '#ffffff'; // Always white for success contrast

                                        setTimeout(() => {
                                            target.innerHTML = originalHTML;
                                            target.style.background = bg;
                                            target.style.borderColor = `${text}40`;
                                            target.style.color = text;
                                        }, 1500);
                                    };

                                    if (navigator.clipboard && navigator.clipboard.writeText) {
                                        navigator.clipboard.writeText(couponCode as string).then(copyAction);
                                    } else {
                                        // Fallback
                                        const textArea = document.createElement('textarea');
                                        textArea.value = couponCode as string;
                                        textArea.style.position = 'fixed';
                                        textArea.style.left = '-999999px';
                                        document.body.appendChild(textArea);
                                        textArea.select();
                                        try {
                                            document.execCommand('copy');
                                            copyAction();
                                        } catch (err) {
                                            console.error('Copy failed:', err);
                                        }
                                        document.body.removeChild(textArea);
                                    }
                                }}
                                className="w-full py-1.5 text-xs font-bold border border-dashed rounded-lg transition-all duration-200 relative z-20 flex items-center justify-center gap-2 cursor-pointer"
                                style={{
                                    backgroundColor: bg,
                                    color: text,
                                    borderColor: `${text}40`
                                }}
                                onMouseEnter={(e) => {
                                    e.currentTarget.style.backgroundColor = hover;
                                }}
                                onMouseLeave={(e) => {
                                    e.currentTarget.style.backgroundColor = bg;
                                }}
                            >
                                <Tag className="w-3 h-3" /> {labels?.getCoupon || "Get Coupon:"} {item.coupon_code}
                            </button>
                        );
                    })()}
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

            {/* Features Preview */}
            <ul className="space-y-2 mb-6 flex-1">
                {(() => {
                    const limit = 3;
                    let toShow: string[] = [];

                    // 1. Primary Features (Prioritized)
                    if (item.primary_features && item.primary_features.length > 0) {
                        toShow.push(...item.primary_features.slice(0, limit));
                    }

                    // 2. Regular Features (Fill remaining slots)
                    if (toShow.length < limit && item.raw_features && item.raw_features.length > 0) {
                        item.raw_features.forEach(f => {
                            if (toShow.length < limit && !toShow.includes(f)) {
                                toShow.push(f);
                            }
                        });
                    }

                    if (toShow.length > 0) {
                        return toShow.map((feature, i) => (
                            <li key={i} className="flex items-center gap-2 text-sm text-foreground/80">
                                <Check className="w-4 h-4 text-primary shrink-0"
                                    style={{ color: (window as any).wpcSettings?.colors?.primary || undefined }}
                                />
                                <span className="truncate">{feature}</span>
                            </li>
                        ));
                    }

                    // 3. Fallback to Legacy/Specific Features (if no list features found)
                    return (
                        <>
                            {item.features.products && (
                                <li className="flex items-center gap-2 text-sm text-foreground/80">
                                    <Tag className="w-4 h-4 text-primary shrink-0"
                                        style={{ color: (window as any).wpcSettings?.colors?.primary || undefined }} />
                                    <span className="truncate">{item.features.products} {labels?.featureProducts || "Products"}</span>
                                </li>
                            )}
                            {item.features.fees && (
                                <li className="flex items-center gap-2 text-sm text-foreground/80">
                                    <Check className="w-4 h-4 text-primary shrink-0"
                                        style={{ color: (window as any).wpcSettings?.colors?.primary || undefined }} />
                                    <span className="truncate">{item.features.fees} {labels?.featureFees || "Trans. Fees"}</span>
                                </li>
                            )}
                            {item.features.support && (
                                <li className="flex items-center gap-2 text-sm text-foreground/80">
                                    <Check className="w-4 h-4 text-primary shrink-0"
                                        style={{ color: (window as any).wpcSettings?.colors?.primary || undefined }} />
                                    <span className="truncate">{item.features.support} {labels?.featureSupport || "Support"}</span>
                                </li>
                            )}
                        </>
                    );
                })()}
            </ul>

            {/* Footer Actions */}
            {/* Footer Actions */}
            <div className="mt-auto space-y-3 pt-4 border-t border-border/50 text-center">
                <Button
                    onClick={(e) => {
                        e.stopPropagation(); // Prevent card click
                        handleDetailsClick(e);
                    }}
                    className="w-full gap-2 font-display font-bold shadow-sm hover:shadow-md transition-all relative z-20 cursor-pointer"
                    style={{
                        backgroundColor: (window as any).wpcSettings?.colors?.primary || undefined,
                        color: (window as any).wpcSettings?.colors?.btnText || '#ffffff'
                    }}
                    onMouseEnter={(e) => {
                        const hoverColor = (window as any).wpcSettings?.colors?.hoverButton;
                        if (hoverColor) e.currentTarget.style.backgroundColor = hoverColor;
                    }}
                    onMouseLeave={(e) => {
                        e.currentTarget.style.backgroundColor = (window as any).wpcSettings?.colors?.primary || '';
                    }}
                >
                    {buttonText || (viewAction === 'link' ? (labels?.visitSite || "Visit Site") : (labels?.viewDetails || "View Details"))}
                </Button>
            </div>
        </div >
    );
};

export default PlatformCard;
