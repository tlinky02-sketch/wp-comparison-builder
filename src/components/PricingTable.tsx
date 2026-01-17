import { useState } from "react";
import { Check, ExternalLink } from "lucide-react";
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

    // Product Variants: Filter Plans by Category (with fallback to all plans)
    if (config?.category && item.variants?.enabled && item.variants?.plans_by_category) {
        const allowedIndices = item.variants.plans_by_category[config.category];
        if (allowedIndices && Array.isArray(allowedIndices)) {
            const indices = allowedIndices.map(Number);
            const filteredPlans = plans.filter((_, idx) => indices.includes(idx));
            // Fallback: If no plans match the category, show ALL plans instead of empty
            if (filteredPlans.length > 0) {
                plans = filteredPlans;
            }
            // else: keep original plans array (fallback to all)
        }
    }
    const showFeatures = !item.hide_plan_features;

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

        // If amount is empty or '0', use the configurable "Free" text
        if (!result.amount || result.amount === '0') {
            result.amount = emptyPriceText;
            result.period = ''; // No period for "Free"
        }

        return result;
    };

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

    const hasAnyButtons = plans.some(plan => shouldShowPlanButton(plan));

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
        btnText: settings?.wpc_pt_btn_text || '#ffffff',
    };

    const overrides = item.design_overrides || { enabled: false } as NonNullable<ComparisonItem['design_overrides']>;
    const useOverrides = overrides.enabled === true || overrides.enabled === '1';
    const primaryColor = useOverrides && overrides.primary ? overrides.primary : (settings?.primary_color || '#6366f1');

    const headerBg = defaultStyles.headerBg;
    const headerText = defaultStyles.headerText;
    const btnBg = (useOverrides && overrides.primary) ? overrides.primary : (defaultStyles.btnBg || primaryColor);
    const btnText = defaultStyles.btnText;
    const borderColor = useOverrides && overrides.border ? overrides.border : 'hsl(var(--border))';

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
                    <h2 className="text-3xl font-bold mb-2">{((window as any).wpcSettings?.texts?.pricingHeader || 'Pricing Plans: {name}').replace('{name}', item.name)}</h2>
                    <p className="text-muted-foreground">{(window as any).wpcSettings?.texts?.pricingSub || 'Compare available plans explicitly'}</p>
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
                                    className={`px-4 py-3 text-sm font-medium border-b-2 transition-all ${selectedCycle === cycle.slug
                                        ? 'text-primary'
                                        : 'border-transparent text-muted-foreground hover:text-foreground'
                                        }`}
                                    style={selectedCycle === cycle.slug ? {
                                        borderColor: useOverrides && overrides.primary ? overrides.primary : (settings?.colors?.primary || '#6366f1'),
                                        color: useOverrides && overrides.primary ? overrides.primary : (settings?.colors?.primary || '#6366f1'),
                                    } : { borderBottomColor: 'transparent' }}
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
                                    className={`px-4 py-2 text-sm font-medium rounded-md transition-all ${selectedCycle === cycle.slug
                                        ? 'shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground'
                                        }`}
                                    style={selectedCycle === cycle.slug ? {
                                        backgroundColor: useOverrides && overrides.primary ? overrides.primary : (settings?.colors?.primary || '#6366f1'),
                                        color: 'white'
                                    } : {}}
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
                <div className="hidden md:block w-full">
                    <table className="w-full table-fixed border-collapse text-sm">
                        <thead>
                            <tr className="border-b border-border" style={{ backgroundColor: 'var(--pt-header-bg)', borderColor: 'var(--pt-border)' }}>
                                {plans.map((plan, idx) => (
                                    <th key={idx} className={`p-4 text-center font-bold align-top relative ${idx !== plans.length - 1 ? 'border-r border-border' : ''}`} style={{ color: 'var(--pt-header-text)', borderColor: 'var(--pt-border)' }}>
                                        {plan.show_banner === '1' && plan.banner_text && (
                                            <div
                                                className="absolute top-0 right-0 text-[10px] font-bold px-2 py-0.5 rounded-bl-md text-white shadow-sm z-10"
                                                style={{ backgroundColor: plan.banner_color || settings?.colors?.banner || '#10b981' }}
                                            >
                                                {plan.banner_text}
                                            </div>
                                        )}
                                        <span className="text-lg block truncate mt-2" title={plan.name}>{plan.name}</span>
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border" style={{ borderColor: 'var(--pt-border)' }}>
                            {/* Price Row */}
                            <tr className="bg-card">
                                {plans.map((plan, idx) => {
                                    const { amount, period } = getPriceInfo(plan, selectedCycle);
                                    return (
                                        <td key={idx} className={`p-4 text-center align-top ${idx !== plans.length - 1 ? 'border-r border-border' : ''}`} style={{ borderColor: 'var(--pt-border)' }}>
                                            <div className="flex flex-wrap items-baseline justify-center gap-1">
                                                <span className="text-2xl font-bold truncate" style={{ color: useOverrides && overrides.primary ? overrides.primary : undefined }}>
                                                    {amount}
                                                </span>
                                                {period && <span className="text-xs text-muted-foreground truncate">
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
                                    {plans.map((plan, idx) => (
                                        <td key={idx} className={`p-4 text-center align-middle ${idx !== plans.length - 1 ? 'border-r border-border' : ''}`} style={{ borderColor: 'var(--pt-border)' }}>
                                            {shouldShowPlanButton(plan) && plan.link && (
                                                <a
                                                    href={plan.link}
                                                    target={config?.targetPricing || settings?.target_pricing || '_blank'}
                                                    rel="noreferrer"
                                                    className="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-9 px-3 w-full shadow-sm"
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
                                    {plans.map((plan, idx) => (
                                        <td key={idx} className={`p-4 align-top ${idx !== plans.length - 1 ? 'border-r border-border' : ''} break-words whitespace-normal`} style={{ borderColor: 'var(--pt-border)' }}>
                                            <ul className="space-y-2 text-left inline-block w-full min-w-0">
                                                {plan.features.split('\n').map((feature, i) => (
                                                    feature.trim() && (
                                                        <li key={i} className="flex items-start gap-2 text-sm break-words whitespace-normal">
                                                            <Check className="w-4 h-4 flex-shrink-0 mt-0.5" style={{ color: useOverrides && overrides.primary ? overrides.primary : undefined }} />
                                                            <span className="text-muted-foreground break-words whitespace-normal min-w-0">{feature.trim()}</span>
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
                                    {plans.map((plan, idx) => (
                                        <td key={idx} className={`p-4 text-center align-middle ${idx !== plans.length - 1 ? 'border-r border-border' : ''}`} style={{ borderColor: 'var(--pt-border)' }}>
                                            {shouldShowPlanButton(plan) && plan.link && (
                                                <a
                                                    href={plan.link}
                                                    target={config?.targetPricing || settings?.target_pricing || '_blank'}
                                                    rel="noreferrer"
                                                    className="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-9 px-3 w-full shadow-sm"
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

                {/* Mobile Card View - Each Plan as Individual Card */}
                <div className="md:hidden w-full space-y-4 p-4">
                    {plans.map((plan, idx) => {
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
                                            className="absolute top-0 right-0 text-xs font-bold px-2 py-1 rounded-bl-md text-white shadow-sm z-10"
                                            style={{ backgroundColor: plan.banner_color || settings?.colors?.banner || '#10b981' }}
                                        >
                                            {plan.banner_text}
                                        </div>
                                    )}
                                    <h3 className="text-xl font-bold">{plan.name}</h3>
                                </div>

                                {/* Price */}
                                <div className="p-6 text-center border-b border-border" style={{ borderColor: 'var(--pt-border)' }}>
                                    <div className="flex flex-wrap items-baseline justify-center gap-1">
                                        <span className="text-3xl font-bold" style={{ color: useOverrides && overrides.primary ? overrides.primary : undefined }}>
                                            {amount}
                                        </span>
                                        {period && <span className="text-sm text-muted-foreground">
                                            {period}
                                        </span>}
                                    </div>
                                </div>
                                {/* Features */}
                                {plan.features && showFeatures && (
                                    <div className="p-4 border-b border-border" style={{ borderColor: 'var(--pt-border)' }}>
                                        <ul className="space-y-2 text-sm">
                                            {plan.features.split('\n').filter(f => f.trim()).map((feature, i) => (
                                                <li key={i} className="flex items-start gap-2">
                                                    <Check className="w-4 h-4 flex-shrink-0 mt-0.5" style={{ color: useOverrides && overrides.primary ? overrides.primary : undefined }} />
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
                                            onClick={() => {
                                                const shouldOpenNewTab = settings?.openNewTab !== false;
                                                window.open(plan.link, shouldOpenNewTab ? '_blank' : '_self');
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
                                            <span className="text-sm font-medium">Coupon: <code className="font-mono font-bold">{plan.coupon}</code></span>
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
