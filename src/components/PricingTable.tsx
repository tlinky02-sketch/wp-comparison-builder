import { useState, useRef, useEffect } from "react";
import { Check, ExternalLink, ChevronRight, ChevronLeft } from "lucide-react";
import { ComparisonItem } from "./PlatformCard";
import { Button } from "@/components/ui/button";

declare global {
    interface Window {
        wpcSettings?: any;
        ecommerceGuiderSettings?: any; // Fallback
    }
}

interface PricingTableProps {
    item: ComparisonItem;
    showPlanButtons?: boolean;
    showFooterButton?: boolean;
    footerButtonText?: string;
    showHeaders?: boolean;
    displayContext?: 'popup' | 'inline'; // New context prop
    config?: any;
}

const PricingTable = ({
    item,
    showPlanButtons,
    showFooterButton = true,
    footerButtonText,
    showHeaders = false,
    displayContext = 'inline', // Default likely inline if not specified
    config
}: PricingTableProps) => {
    // 1. Legacy Data Retrieval (for Fallback)
    const itemAny = item as any;
    const billingMode = config?.billingMode || itemAny.billing_mode || 'monthly_only';
    const monthlyLabel = config?.monthlyLabel || itemAny.monthly_label || 'Pay monthly';
    const yearlyLabel = config?.yearlyLabel || itemAny.yearly_label || 'Pay yearly (save 25%)*';
    const defaultBilling = config?.defaultBilling || itemAny.default_billing || 'monthly';

    // 2. Billing Mode Logic (Dynamic)
    const billingCycles: any[] = config?.billingCycles || (item as any).billing_cycles || [];
    const billingStyle = config?.billingDisplay || (item as any).billing_display_style || 'toggle';
    // Fallback if cycles missing but legacy props exist (safety net)
    if (billingCycles.length === 0) {
        if (billingMode !== 'yearly_only') billingCycles.push({ slug: 'monthly', label: monthlyLabel });
        if (billingMode !== 'monthly_only') billingCycles.push({ slug: 'yearly', label: yearlyLabel });
    }

    // Determine default cycle
    const initialCycle = billingCycles.find((c: any) => c.slug === defaultBilling) ? defaultBilling : (billingCycles[0]?.slug || 'monthly');
    const [selectedCycle, setSelectedCycle] = useState<string>(initialCycle);

    // 3. Plans Data Preparation
    let plans = item.pricing_plans || [];


    // Product Variants: Local Category State (defaults to config category, item default, or null)
    const [localCategory, setLocalCategory] = useState<string | null>(config?.category || item.variants?.default_category || null);

    // Product Variants: Filter Plans by Category (with fallback to all plans)
    if (item.variants?.enabled && item.variants?.plans_by_category) {
        // Use local state if set, otherwise fall back to config (if any)
        const activeCat = localCategory;

        if (activeCat) {
            const allowedIndices = item.variants.plans_by_category[activeCat];
            if (allowedIndices && Array.isArray(allowedIndices)) {
                const indices = allowedIndices.map(Number);
                const filteredPlans = plans.filter((_, idx) => indices.includes(idx));
                // Fallback: If no plans match the category, show ALL plans instead of empty
                if (filteredPlans.length > 0) {
                    plans = filteredPlans;
                }
            }
        }
    }
    const showFeatures = !item.hide_plan_features;


    // Helper to get price availability (strictly checks if price exists for cycle)
    const hasPriceForCycle = (plan: any, cycleSlug: string) => {
        // New structure check
        if (plan.prices && plan.prices[cycleSlug]) {
            return plan.prices[cycleSlug].amount !== undefined && plan.prices[cycleSlug].amount !== '';
        }
        // Legacy fallback
        if (cycleSlug === 'monthly') return !!plan.price;
        if (cycleSlug === 'yearly') return !!(plan.yearly_price || plan.price);

        return false;
    };

    // Helper to get price info
    const getPriceInfo = (plan: any, cycleSlug: string) => {
        const emptyPriceText = (window as any).wpcSettings?.texts?.emptyPrice || 'Free';
        let result = { amount: '', period: '' };

        // New structure: plan.prices[slug]
        if (plan.prices && plan.prices[cycleSlug]) {
            result = { amount: plan.prices[cycleSlug].amount, period: plan.prices[cycleSlug].period };
        }
        // Legacy fallback
        else if (cycleSlug === 'monthly') {
            result = { amount: plan.price, period: plan.period || '/mo' };
        }
        else if (cycleSlug === 'yearly') {
            result = { amount: plan.yearly_price || plan.price, period: (plan as any).yearly_period || '/yr' };
        }

        // Fix logic: Only show "Free" if amount is "0", NOT if it's empty/missing (which should be hidden)
        if (result.amount === '0') {
            result.amount = emptyPriceText;
            result.period = '';
        }

        return result;
    };

    // Filter plans based on cycle availability
    // If a plan does NOT have a price for the selected cycle, it should be hidden from view.
    const visiblePlans = plans.filter(plan => hasPriceForCycle(plan, selectedCycle));

    // 4. Settings & Visibility Logic
    const settings = window.wpcSettings || window.ecommerceGuiderSettings;
    const resolvedShowButtons = typeof showPlanButtons === 'boolean'
        ? showPlanButtons
        : (settings?.showPlanButtons !== '0' && settings?.showPlanButtons !== false); // Default to true if undefined

    // Helper to check if a specific plan should show its button in current context
    const shouldShowPlanButton = (plan: any) => {
        // --- LIST-LEVEL OVERRIDE (Highest Priority) ---
        if (displayContext === 'popup') {
            if (config?.showSelectPopup === false) return false;
        } else {
            if (config?.showSelectTable === false) return false;
        }

        // Backward compatibility fallback to show_button
        const showMeta = plan.show_button === '1';

        // 1. Check strict context overrides first (per-plan settings)
        if (displayContext === 'popup') {
            if (plan.show_popup !== undefined) return plan.show_popup === '1';
            return showMeta || resolvedShowButtons;
        } else {
            if (plan.show_table !== undefined) return plan.show_table === '1';
            return showMeta || resolvedShowButtons;
        }
    };

    const hasAnyButtons = visiblePlans.some(plan => shouldShowPlanButton(plan));

    // 5. Style Logic
    const visuals = settings?.visuals || {};
    const positionSetting = displayContext === 'popup'
        ? (config?.ptBtnPosPopup || item.popup_btn_pos || visuals.wpc_pt_btn_pos_popup)
        : (config?.ptBtnPosTable || item.table_btn_pos || visuals.wpc_pt_btn_pos_table);
    const buttonPosition = positionSetting || 'after_price';

    const defaultStyles = {
        headerBg: settings?.wpc_pt_header_bg || '#f8fafc',
        headerText: settings?.wpc_pt_header_text || '#0f172a',
        btnBg: settings?.wpc_pt_btn_bg || '',
        btnText: settings?.colors?.btnText || settings?.wpc_button_text_color || settings?.wpc_pt_btn_text || '#ffffff',
    };

    // Color Priority Logic:
    // - List context (config exists): List colors (if set) > Global (item overrides IGNORED)
    // - Item context (no config): item.design_overrides (if enabled) > Global
    const isListContext = !!config;
    const listHasColors = !!(config?.colors?.primary || config?.colorsOverride?.primary);
    const overrides = item.design_overrides || { enabled: false } as NonNullable<ComparisonItem['design_overrides']>;

    // Scroll Logic for "More than 3 columns"
    const scrollContainerRef = useRef<HTMLDivElement>(null);
    const [showLeftArrow, setShowLeftArrow] = useState(false);
    const [showRightArrow, setShowRightArrow] = useState(true);

    const showScroll = visiblePlans.length > 3;
    const minTableWidth = showScroll ? `${visiblePlans.length * 280}px` : '100%';

    const handleScroll = () => {
        if (scrollContainerRef.current) {
            const { scrollLeft, scrollWidth, clientWidth } = scrollContainerRef.current;
            setShowLeftArrow(scrollLeft > 0);
            // Tolerance of 5px for floating point issues
            setShowRightArrow(scrollLeft < scrollWidth - clientWidth - 5);
        }
    };

    useEffect(() => {
        handleScroll(); // Check initial state
        window.addEventListener('resize', handleScroll);
        return () => window.removeEventListener('resize', handleScroll);
    }, [plans]);

    const scrollTable = (direction: 'left' | 'right') => {
        if (scrollContainerRef.current) {
            const scrollAmount = 300;
            scrollContainerRef.current.scrollBy({
                left: direction === 'right' ? scrollAmount : -scrollAmount,
                behavior: 'smooth'
            });
        }
    };

    // Only allow item overrides when NOT in a list context
    const useOverrides = !isListContext && (overrides.enabled === true || overrides.enabled === '1');

    // Primary color: List colors > Item overrides (if applicable) > Global
    const getPrimaryColor = () => {
        if (isListContext && listHasColors) {
            return config?.colors?.primary || config?.colorsOverride?.primary;
        }
        if (useOverrides && overrides.primary) {
            return overrides.primary;
        }
        return settings?.colors?.primary || '#6366f1';
    };
    const primaryColor = getPrimaryColor();

    const headerBg = defaultStyles.headerBg;
    const headerText = defaultStyles.headerText;
    const btnBg = (useOverrides && overrides.primary) ? overrides.primary : (defaultStyles.btnBg || primaryColor);
    const btnText = (useOverrides && overrides.primary && !overrides.btn_text_color) ? '#ffffff' : ((useOverrides && overrides.btn_text_color) ? overrides.btn_text_color : defaultStyles.btnText);
    const borderColor = useOverrides && overrides.border ? overrides.border : 'hsl(var(--border))';

    const resolvedTickColor = (useOverrides && overrides.primary) ? overrides.primary : (settings?.colors?.tick || settings?.colors?.primary || '#10b981');
    // Force Primary Color for Price (ignoring global text color)
    const resolvedPriceColor = (useOverrides && overrides.primary) ? overrides.primary : (settings?.colors?.primary || primaryColor);

    // Footer Visibility Logic
    const resolvedShowFooter = (() => {
        if (displayContext === 'popup') {
            if (config?.showFooterPopup === false) return false;
        } else {
            if (config?.showFooterTable === false) return false;
        }

        if (displayContext === 'popup') {
            if (item.show_footer_popup !== undefined && item.show_footer_popup !== '') {
                return item.show_footer_popup === true || item.show_footer_popup === '1';
            }
        } else {
            if (item.show_footer_table !== undefined && item.show_footer_table !== '') {
                return item.show_footer_table === true || item.show_footer_table === '1';
            }
        }

        if (displayContext === 'popup') {
            const val = overrides.show_footer_popup ?? overrides.show_footer;
            if (val !== undefined && val !== null && val !== '') return val !== false && val !== '0';
        } else {
            if (overrides.show_footer_table !== undefined && overrides.show_footer_table !== null && overrides.show_footer_table !== '') {
                return overrides.show_footer_table !== false && overrides.show_footer_table !== '0';
            }
        }

        if (settings?.showFooterButtonGlobal !== undefined) {
            return settings.showFooterButtonGlobal === '1' || settings.showFooterButtonGlobal === true;
        }

        return showFooterButton !== false;
    })();

    const containerStyle = {
        '--pt-header-bg': headerBg,
        '--pt-header-text': headerText,
        '--pt-btn-bg': btnBg,
        '--pt-btn-text': btnText,
        '--pt-border': borderColor,
        '--primary': useOverrides && overrides.primary ? overrides.primary : undefined,
    } as React.CSSProperties;

    if (plans.length === 0) {
        return (
            <div className="text-center py-12 bg-secondary/20 rounded-xl mb-8">
                <p className="text-muted-foreground">{(window as any).wpcSettings?.texts?.noPlans || 'No specific pricing plans available for display.'}</p>
            </div>
        );
    }

    return (
        <div className="w-full" style={containerStyle}>
            {showHeaders && (
                <div className="text-center mb-8 flex-shrink-0">
                    <div className="w-16 h-16 mx-auto bg-white rounded-xl shadow-sm border p-2 mb-4 flex items-center justify-center">
                        {item.logo ? (
                            <img src={item.logo} alt={item.name} className="w-full h-full object-contain" />
                        ) : (
                            <div className="w-full h-full bg-primary/10 rounded-lg flex items-center justify-center font-bold text-primary">
                                {item.name.charAt(0)}
                            </div>
                        )}
                    </div>
                    <h2 className="font-bold mb-2" style={{ fontSize: 'var(--wpc-font-size-h2)' }}>{((window as any).wpcSettings?.texts?.pricingHeader || 'Pricing Plans: {name}').replace('{name}', item.name)}</h2>
                    <p className="text-muted-foreground">{(window as any).wpcSettings?.texts?.pricingSub || 'Compare available plans explicitly'}</p>
                </div>
            )}

            {/* Product Variants: Category Selector (Pricing Table) */}
            {/* HIDE TABS if a category is hardcoded in the shortcode (config.category) */}
            {!config?.category && item.variants?.enabled && item.variants.plans_by_category && Object.keys(item.variants.plans_by_category).length > 0 && (
                <div className="flex flex-wrap gap-2 mb-6 justify-center">
                    <div
                        onClick={() => setLocalCategory(null)}
                        className={`px-4 py-2 rounded-full text-sm font-bold transition-all border cursor-pointer ${!localCategory
                            ? "text-white shadow-md scale-105"
                            : "bg-transparent text-muted-foreground border-border hover:bg-muted"
                            }`}
                        style={!localCategory ? {
                            backgroundColor: primaryColor,
                            borderColor: primaryColor,
                            color: 'var(--wpc-btn-text, #ffffff) !important',
                        } : {
                            color: 'var(--muted-foreground)'
                        }}
                    >
                        {(window as any).wpcSettings?.texts?.allPlans || 'All Plans'}
                    </div>
                    {Object.keys(item.variants.plans_by_category).map((catSlug) => {
                        const prettyName = catSlug
                            .split('-')
                            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                            .join(' ');

                        const isActive = localCategory === catSlug;

                        return (
                            <div
                                key={catSlug}
                                onClick={() => setLocalCategory(catSlug)}
                                className={`px-4 py-2 rounded-full text-sm font-bold transition-all border cursor-pointer ${isActive
                                    ? "shadow-md scale-105"
                                    : "bg-transparent text-muted-foreground border-border hover:bg-muted"
                                    }`}
                                style={isActive ? {
                                    backgroundColor: primaryColor,
                                    borderColor: primaryColor,
                                    color: 'var(--wpc-btn-text, #ffffff) !important',
                                } : {
                                    color: 'var(--muted-foreground)'
                                }}
                            >
                                {prettyName}
                            </div>
                        );
                    })}
                </div>
            )}

            {/* Billing Cycle Toggle/Tabs (Dynamic for N items) */}
            {billingCycles.length > 1 && billingStyle !== 'none' && (
                <div className="flex justify-center mb-4 w-full">
                    {billingStyle === 'tabs' ? (
                        <div className="flex border-b border-border w-full justify-center gap-x-8" style={{ borderColor: 'var(--pt-border)' }}>
                            {billingCycles.map((cycle: any) => (
                                <button
                                    key={cycle.slug}
                                    onClick={() => setSelectedCycle(cycle.slug)}
                                    className={`px-4 py-3 font-medium border-b-2 transition-all ${selectedCycle === cycle.slug
                                        ? 'text-primary'
                                        : 'border-transparent text-muted-foreground hover:text-foreground'
                                        }`}
                                    style={selectedCycle === cycle.slug ? {
                                        borderColor: useOverrides && overrides.primary ? overrides.primary : (settings?.colors?.primary || '#6366f1'),
                                        color: useOverrides && overrides.primary ? overrides.primary : (settings?.colors?.primary || '#6366f1'),
                                        fontSize: 'var(--wpc-font-size-base)'
                                    } : {
                                        borderBottomColor: 'transparent',
                                        fontSize: 'var(--wpc-font-size-base)'
                                    }}
                                    ref={(el) => {
                                        if (!el) return;
                                        if (selectedCycle === cycle.slug) {
                                            const color = useOverrides && overrides.primary ? overrides.primary : (settings?.colors?.primary || '#6366f1');
                                            el.style.setProperty('color', color, 'important');
                                            el.style.setProperty('border-color', color, 'important');
                                        } else {
                                            el.style.setProperty('color', 'var(--muted-foreground)', 'important');
                                            el.style.setProperty('border-bottom-color', 'transparent', 'important');
                                        }
                                    }}
                                >
                                    {cycle.label}
                                </button>
                            ))}
                        </div>
                    ) : (
                        <div className="inline-flex rounded-lg border border-border bg-muted/30 p-1 flex-wrap justify-center gap-1">
                            {billingCycles.map((cycle: any) => (
                                <button
                                    key={cycle.slug}
                                    onClick={() => setSelectedCycle(cycle.slug)}
                                    className={`px-4 py-2 font-medium rounded-md transition-all ${selectedCycle === cycle.slug
                                        ? 'shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground'
                                        }`}
                                    style={selectedCycle === cycle.slug ? {
                                        backgroundColor: useOverrides && overrides.primary ? overrides.primary : (settings?.colors?.primary || '#6366f1'),
                                        color: (window as any).wpcSettings?.colors?.btnText || settings?.colors?.btnText || '#ffffff',
                                        fontSize: 'var(--wpc-font-size-base)'
                                    } : {
                                        fontSize: 'var(--wpc-font-size-base)'
                                    }}
                                    ref={(el) => {
                                        if (!el) return;
                                        if (selectedCycle === cycle.slug) {
                                            // Selected: Background uses Primary, Text uses Button Text Color
                                            const bg = useOverrides && overrides.primary ? overrides.primary : (settings?.colors?.primary || '#6366f1');
                                            const btnTextColor = (window as any).wpcSettings?.colors?.btnText || settings?.colors?.btnText || '#ffffff';
                                            el.style.setProperty('background-color', bg, 'important');
                                            el.style.setProperty('color', btnTextColor, 'important');
                                        } else {
                                            // Unselected: Transparent BG, Muted Text (Important)
                                            el.style.setProperty('background-color', 'transparent', 'important');
                                            el.style.setProperty('color', 'var(--muted-foreground)', 'important');
                                        }
                                    }}
                                >
                                    {cycle.label}
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            )}

            {/* Main Unified Card Container */}
            <div
                className="w-full border border-border rounded-xl bg-card overflow-hidden flex flex-col"
                style={{ borderColor: 'var(--pt-border)' }}
            >
                {/* Desktop Table View */}
                <div className="hidden md:block w-full relative group/table">
                    {/* Scroll Arrows */}
                    {showScroll && (
                        <>
                            {showLeftArrow && (
                                <button
                                    onClick={() => scrollTable('left')}
                                    className="absolute left-2 top-1/2 -translate-y-1/2 z-20 p-2 rounded-full shadow-lg border transition-all hover:scale-110"
                                    style={{
                                        backgroundColor: 'var(--pt-header-bg)',
                                        color: 'var(--pt-header-text)',
                                        borderColor: 'var(--pt-border)',
                                        marginLeft: '0.5rem'
                                    }}
                                >
                                    <ChevronLeft className="w-6 h-6" />
                                </button>
                            )}
                            {showRightArrow && (
                                <button
                                    onClick={() => scrollTable('right')}
                                    className="absolute right-2 top-1/2 -translate-y-1/2 z-20 p-2 rounded-full shadow-lg border transition-all hover:scale-110"
                                    style={{
                                        backgroundColor: btnBg,
                                        color: btnText,
                                        borderColor: 'transparent',
                                        marginRight: '0.5rem'
                                    }}
                                >
                                    <ChevronRight className="w-6 h-6" />
                                </button>
                            )}
                        </>
                    )}

                    <div
                        ref={scrollContainerRef}
                        onScroll={handleScroll}
                        className={`w-full ${showScroll ? 'overflow-x-auto pb-4 scrollbar-hide' : ''}`}
                    >
                        <table className="w-full table-fixed border-collapse" style={{ minWidth: minTableWidth }}>
                            <thead>
                                <tr className="border-b border-border" style={{ backgroundColor: 'var(--pt-header-bg)', borderColor: 'var(--pt-border)' }}>
                                    {visiblePlans.map((plan, idx) => (
                                        <th key={idx} className={`p-4 text-center font-bold align-top relative ${idx !== visiblePlans.length - 1 ? 'border-r border-border' : ''}`} style={{ color: 'var(--pt-header-text)', borderColor: 'var(--pt-border)' }}>
                                            {plan.show_banner === '1' && plan.banner_text && (
                                                <div
                                                    className="absolute top-0 right-0 font-bold px-2 py-0.5 rounded-bl-md text-white shadow-sm z-10"
                                                    style={{ backgroundColor: plan.banner_color || settings?.colors?.banner || '#10b981', fontSize: 'var(--wpc-font-size-small, 0.75rem)' }}
                                                >
                                                    {plan.banner_text}
                                                </div>
                                            )}
                                            <span className="block truncate mt-2" title={plan.name} style={{ fontSize: 'var(--wpc-font-size-h2)' }}>{plan.name}</span>
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border" style={{ borderColor: 'var(--pt-border)' }}>
                                {/* Price Row */}
                                <tr className="bg-card">
                                    {visiblePlans.map((plan, idx) => {
                                        const { amount, period } = getPriceInfo(plan, selectedCycle);
                                        return (
                                            <td key={idx} className={`p-4 text-center align-top ${idx !== visiblePlans.length - 1 ? 'border-r border-border' : ''}`} style={{ borderColor: 'var(--pt-border)' }}>
                                                <div className="flex flex-wrap items-baseline justify-center gap-1">
                                                    <span className="font-bold truncate" style={{ color: resolvedPriceColor, fontSize: 'var(--wpc-font-size-price, var(--wpc-font-size-h3, 1.5rem))' }} ref={(el) => { if (el) el.style.setProperty('color', resolvedPriceColor, 'important'); }}>
                                                        {amount}
                                                    </span>
                                                    {period && <span className="text-muted-foreground truncate" style={{ fontSize: 'calc(var(--wpc-font-size-price) * 0.5)', lineHeight: '1.2' }}>
                                                        {period}
                                                    </span>}
                                                </div>
                                            </td>
                                        );
                                    })}
                                </tr>

                                {/* Action Row (Top) */}
                                {hasAnyButtons && buttonPosition === 'after_price' && (
                                    <tr className="bg-muted/5">
                                        {visiblePlans.map((plan, idx) => (
                                            <td key={idx} className={`p-4 text-center align-middle ${idx !== visiblePlans.length - 1 ? 'border-r border-border' : ''}`} style={{ borderColor: 'var(--pt-border)' }}>
                                                {shouldShowPlanButton(plan) && plan.link && (
                                                    <a
                                                        href={plan.link}
                                                        target={config?.targetPricing || settings?.target_pricing || '_blank'}
                                                        rel="noreferrer"
                                                        className="inline-flex items-center justify-center rounded-md font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-9 px-3 w-full shadow-sm"
                                                        style={{
                                                            backgroundColor: 'var(--pt-btn-bg)',
                                                            color: 'var(--pt-btn-text, #ffffff)',
                                                            fontSize: 'var(--wpc-font-size-btn, 1rem)',
                                                        }}
                                                        onMouseEnter={(e) => {
                                                            if (!(useOverrides && overrides.primary) && settings?.colors?.hoverButton) {
                                                                e.currentTarget.style.backgroundColor = settings.colors.hoverButton;
                                                                e.currentTarget.style.filter = 'none';
                                                            } else {
                                                                e.currentTarget.style.filter = 'brightness(90%)';
                                                            }
                                                        }}
                                                        onMouseLeave={(e) => {
                                                            e.currentTarget.style.backgroundColor = 'var(--pt-btn-bg)';
                                                            e.currentTarget.style.filter = 'brightness(100%)';
                                                        }}
                                                        ref={(el) => {
                                                            if (!el) return;
                                                            const btnColor = (window as any).wpcSettings?.colors?.btnText || '#ffffff';
                                                            el.style.setProperty('color', btnColor, 'important');
                                                        }}
                                                    >
                                                        {plan.button_text || (window as any).wpcSettings?.texts?.selectPlan || 'Select'}
                                                    </a>
                                                )}
                                            </td>
                                        ))}
                                    </tr>
                                )}

                                {/* Features Row */}
                                {showFeatures && (
                                    <tr className="bg-card">
                                        {visiblePlans.map((plan, idx) => (
                                            <td key={idx} className={`p-4 align-top ${idx !== visiblePlans.length - 1 ? 'border-r border-border' : ''} break-words whitespace-normal`} style={{ borderColor: 'var(--pt-border)' }}>
                                                <ul className="space-y-2 text-left inline-block w-full min-w-0">
                                                    {(plan.features || '').split('\n').map((feature, i) => (
                                                        feature.trim() && (
                                                            <li key={i} className="flex items-start gap-2 break-words whitespace-normal">
                                                                <Check className="w-4 h-4 flex-shrink-0 mt-0.5" style={{ color: resolvedTickColor }} />
                                                                <span className="text-muted-foreground break-words whitespace-normal min-w-0" style={{ fontSize: 'var(--wpc-font-size-body, inherit)' }}>{feature.trim()}</span>
                                                            </li>
                                                        )
                                                    ))}
                                                </ul>
                                            </td>
                                        ))}
                                    </tr>
                                )}

                                {/* Action Row (Bottom) */}
                                {hasAnyButtons && buttonPosition === 'bottom' && (
                                    <tr className="bg-muted/5">
                                        {visiblePlans.map((plan, idx) => (
                                            <td key={idx} className={`p-4 text-center align-middle ${idx !== visiblePlans.length - 1 ? 'border-r border-border' : ''}`} style={{ borderColor: 'var(--pt-border)' }}>
                                                {shouldShowPlanButton(plan) && plan.link && (
                                                    <a
                                                        href={plan.link}
                                                        target={config?.targetPricing || settings?.target_pricing || '_blank'}
                                                        rel="noreferrer"
                                                        className="inline-flex items-center justify-center rounded-md font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-9 px-3 w-full shadow-sm"
                                                        style={{
                                                            backgroundColor: 'var(--pt-btn-bg)',
                                                            color: 'var(--pt-btn-text)',
                                                            fontSize: 'var(--wpc-font-size-btn)',
                                                        }}
                                                        onMouseEnter={(e) => {
                                                            if (!(useOverrides && overrides.primary) && settings?.colors?.hoverButton) {
                                                                e.currentTarget.style.backgroundColor = settings.colors.hoverButton;
                                                                e.currentTarget.style.filter = 'none';
                                                            } else {
                                                                e.currentTarget.style.filter = 'brightness(90%)';
                                                            }
                                                        }}
                                                        onMouseLeave={(e) => {
                                                            e.currentTarget.style.backgroundColor = 'var(--pt-btn-bg)';
                                                            e.currentTarget.style.filter = 'brightness(100%)';
                                                        }}
                                                        ref={(el) => {
                                                            if (!el) return;
                                                            const btnColor = (window as any).wpcSettings?.colors?.btnText || '#ffffff';
                                                            el.style.setProperty('color', btnColor, 'important');
                                                        }}
                                                    >
                                                        {plan.button_text || 'Select'}
                                                    </a>
                                                )}
                                            </td>
                                        ))}
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Mobile Card View - Each Plan as Individual Card */}
                <div className="md:hidden w-full space-y-4 p-4">
                    {visiblePlans.map((plan, idx) => {
                        const { amount, period } = getPriceInfo(plan, selectedCycle);
                        return (
                            <div
                                key={idx}
                                className="border border-border rounded-xl overflow-hidden bg-card shadow-sm"
                                style={{ borderColor: 'var(--pt-border)' }}
                            >
                                {/* Plan Header */}
                                <div
                                    className="p-4 text-center relative"
                                    style={{ backgroundColor: 'var(--pt-header-bg)', color: 'var(--pt-header-text)' }}
                                >
                                    {/* Discount Banner */}
                                    {plan.show_banner === '1' && plan.banner_text && (
                                        <div
                                            className="inline-block px-2 py-0.5 rounded-md text-white font-bold mb-2"
                                            style={{ backgroundColor: plan.banner_color || settings?.colors?.banner || '#10b981', fontSize: 'var(--wpc-font-size-small, 0.75rem)' }}
                                        >
                                            {plan.banner_text}
                                        </div>
                                    )}
                                    <h3 className="font-bold" style={{ fontSize: 'var(--wpc-font-size-h2)' }}>{plan.name}</h3>
                                </div>

                                {/* Price */}
                                <div className="p-6 text-center border-b border-border" style={{ borderColor: 'var(--pt-border)' }}>
                                    <div className="flex flex-wrap items-baseline justify-center gap-1">
                                        <span className="font-bold" style={{ color: resolvedPriceColor, fontSize: 'var(--wpc-font-size-price, var(--wpc-font-size-h3, 1.5rem))' }} ref={(el) => { if (el) el.style.setProperty('color', resolvedPriceColor, 'important'); }}>
                                            {amount}
                                        </span>
                                        {period && <span className="text-muted-foreground" style={{ fontSize: 'calc(var(--wpc-font-size-price) * 0.5)', lineHeight: '1.2' }}>
                                            {period}
                                        </span>}
                                    </div>
                                </div>
                                {/* Features */}
                                {plan.features && showFeatures && (
                                    <div className="p-4 border-b border-border" style={{ borderColor: 'var(--pt-border)' }}>
                                        <ul className="space-y-2">
                                            {(plan.features || '').split('\n').filter(f => f.trim()).map((feature, i) => (
                                                <li key={i} className="flex items-start gap-2">
                                                    <Check className="w-4 h-4 flex-shrink-0 mt-0.5" style={{ color: resolvedTickColor }} />
                                                    <span>{feature}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}

                                {/* Button */}
                                {shouldShowPlanButton(plan) && plan.link && (
                                    <div className="p-4">
                                        <Button
                                            className="w-full shadow-sm transition-all"
                                            style={{
                                                backgroundColor: useOverrides && overrides.primary ? overrides.primary : 'var(--pt-btn-bg)',
                                                color: 'var(--pt-btn-text)',
                                                height: 'auto',
                                                padding: '0.75rem 1.5rem',
                                                fontSize: 'var(--wpc-font-size-btn)',
                                            }}
                                            onMouseEnter={(e) => {
                                                if (!(useOverrides && overrides.primary) && settings?.colors?.hoverButton) {
                                                    e.currentTarget.style.backgroundColor = settings.colors.hoverButton;
                                                    e.currentTarget.style.filter = 'none';
                                                } else {
                                                    e.currentTarget.style.filter = 'brightness(90%)';
                                                }
                                            }}
                                            onMouseLeave={(e) => {
                                                e.currentTarget.style.backgroundColor = useOverrides && overrides.primary ? overrides.primary : 'var(--pt-btn-bg)';
                                                e.currentTarget.style.filter = 'brightness(100%)';
                                            }}
                                            ref={(el) => {
                                                if (!el) return;
                                                const btnColor = (window as any).wpcSettings?.colors?.btnText || settings?.colors?.btnText || '#ffffff';
                                                el.style.setProperty('color', btnColor, 'important');
                                            }}
                                            onClick={() => {
                                                if (plan.link) window.open(plan.link, (window as any).wpcSettings?.openNewTab === '1' ? '_blank' : '_self');
                                            }}
                                        >
                                            {plan.button_text || (window as any).wpcSettings?.texts?.selectPlan || 'Select'}
                                        </Button>
                                    </div>
                                )}

                                {/* Coupon */}
                                {item.show_coupon && plan.coupon && (
                                    <div className="px-4 pb-4">
                                        <div className="flex items-center justify-between bg-secondary/20 p-3 rounded-lg">
                                            <span className="font-medium" style={{ fontSize: 'var(--wpc-font-size-base)' }}>Coupon: <code className="font-mono font-bold" style={{ fontSize: 'var(--wpc-font-size-code)' }}>{plan.coupon}</code></span>
                                        </div>
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>

                {/* Footer Link Button */}
                {resolvedShowFooter && (item.details_link || item.direct_link) && (
                    <div className="text-center border-t border-border p-6 bg-muted/5 mt-auto flex-shrink-0" style={{ borderColor: 'var(--pt-border)' }}>
                        <Button
                            className="w-full md:w-auto px-8 shadow-sm"
                            size="lg"
                            style={{
                                backgroundColor: 'var(--pt-btn-bg)',
                                color: 'var(--pt-btn-text)',
                            }}
                            onMouseEnter={(e) => {
                                if (!(useOverrides && overrides.primary) && settings?.colors?.hoverButton) {
                                    e.currentTarget.style.backgroundColor = settings.colors.hoverButton;
                                    e.currentTarget.style.filter = 'none';
                                } else {
                                    e.currentTarget.style.filter = 'brightness(90%)';
                                }
                            }}
                            onMouseLeave={(e) => {
                                e.currentTarget.style.backgroundColor = 'var(--pt-btn-bg)';
                                e.currentTarget.style.filter = 'brightness(100%)';
                            }}
                            onClick={() => window.open(item.details_link || item.direct_link, config?.targetDetails || settings?.target_details || '_blank')}
                        >
                            {footerButtonText || item.button_text || "Visit Website"} <ExternalLink className="w-4 h-4 ml-2" />
                        </Button>
                    </div>
                )}
            </div>
        </div >
    );
};

export default PricingTable;
