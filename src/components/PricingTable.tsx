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
}

const PricingTable = ({
    item,
    showPlanButtons,
    showFooterButton = true,
    footerButtonText,
    showHeaders = false,
    displayContext = 'inline' // Default likely inline if not specified
}: PricingTableProps) => {
    const plans = item.pricing_plans || [];
    const showFeatures = !item.hide_plan_features;

    // 1. Settings & Visibility Logic
    const settings = window.wpcSettings || window.ecommerceGuiderSettings;
    const resolvedShowButtons = typeof showPlanButtons === 'boolean'
        ? showPlanButtons
        : (settings?.showPlanButtons !== '0' && settings?.showPlanButtons !== false); // Default to true if undefined

    // Helper to check if a specific plan should show its button in current context
    const shouldShowPlanButton = (plan: any) => {
        // Backward compatibility fallback to show_button
        const showMeta = plan.show_button === '1';

        // 1. Check strict context overrides first
        if (displayContext === 'popup') {
            if (plan.show_popup !== undefined) {
                return plan.show_popup === '1';
            }
            // If data missing (legacy), use showMeta or fallback to Global
            return showMeta || resolvedShowButtons;
        } else {
            // inline / table
            if (plan.show_table !== undefined) {
                return plan.show_table === '1';
            }
            return showMeta || resolvedShowButtons;
        }
    };

    const hasAnyButtons = plans.some(plan => shouldShowPlanButton(plan));

    // 2. Style Logic
    // Global Visual Settings
    const visuals = settings?.visuals || {};
    // Button Position Logic
    const positionSetting = displayContext === 'popup'
        ? visuals.wpc_pt_btn_pos_popup
        : visuals.wpc_pt_btn_pos_table;
    const buttonPosition = positionSetting || 'after_price'; // Default to 'after_price'

    const defaultStyles = {
        headerBg: settings?.wpc_pt_header_bg || '#f8fafc',
        headerText: settings?.wpc_pt_header_text || '#0f172a',
        // Change: Default to empty string so we can detect if it wasn't set and fallback to Primary
        btnBg: settings?.wpc_pt_btn_bg || '',
        btnText: settings?.wpc_pt_btn_text || '#ffffff',
    };

    // Overrides (from Item)
    // Cast fallback to ensure TS knows it matches the shape
    const overrides = item.design_overrides || { enabled: false } as NonNullable<ComparisonItem['design_overrides']>;
    const useOverrides = overrides.enabled === true || overrides.enabled === '1';

    // Resolve Final Colors
    const primaryColor = useOverrides && overrides.primary ? overrides.primary : (settings?.primary_color || '#6366f1');

    // Header
    const headerBg = defaultStyles.headerBg;
    const headerText = defaultStyles.headerText;

    // Buttons (Plan Select)
    // Logic: 1. Item Override 2. Global PT Setting 3. Global Primary Color (Fallback)
    const btnBg = (useOverrides && overrides.primary)
        ? overrides.primary
        : (defaultStyles.btnBg || primaryColor);

    const btnText = defaultStyles.btnText;

    // Border
    const borderColor = useOverrides && overrides.border ? overrides.border : 'hsl(var(--border))';

    // Footer Visibility Logic
    const resolvedShowFooter = (() => {
        // 1. Top-Level Item Override (Specific context - Independent of Design Overrides)
        if (displayContext === 'popup') {
            if (item.show_footer_popup !== undefined && item.show_footer_popup !== '') {
                return item.show_footer_popup === true || item.show_footer_popup === '1';
            }
        } else {
            if (item.show_footer_table !== undefined && item.show_footer_table !== '') {
                return item.show_footer_table === true || item.show_footer_table === '1';
            }
        }

        // 2. Legacy Design Overrides (Backward Compatibility)
        if (useOverrides) {
            if (displayContext === 'popup') {
                const val = overrides.show_footer_popup ?? overrides.show_footer;
                if (val !== undefined && val !== '') return val !== false && val !== '0';
            } else {
                if (overrides.show_footer_table !== undefined && overrides.show_footer_table !== '') {
                    return overrides.show_footer_table !== false && overrides.show_footer_table !== '0';
                }
            }
        }

        // 3. Global Setting from WP Options
        if (settings?.showFooterButtonGlobal !== undefined) {
            return settings.showFooterButtonGlobal === '1' || settings.showFooterButtonGlobal === true;
        }

        // 4. Default / Prop Fallback
        return showFooterButton !== false;
    })();

    // Inline CSS Variables for this component instance
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
                <p className="text-muted-foreground">No specific pricing plans available for display.</p>
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
                    <h2 className="text-3xl font-bold mb-2">Pricing Plans: {item.name}</h2>
                    <p className="text-muted-foreground">Compare available plans explicitly</p>
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
                                {/* Header Row: Plan Names */}
                                {plans.map((plan, idx) => (
                                    <th key={idx} className={`p-4 text-center font-bold align-top relative ${idx !== plans.length - 1 ? 'border-r border-border' : ''}`} style={{ color: 'var(--pt-header-text)', borderColor: 'var(--pt-border)' }}>
                                        {/* Discount Banner */}
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
                                {plans.map((plan, idx) => (
                                    <td key={idx} className={`p-4 text-center align-top ${idx !== plans.length - 1 ? 'border-r border-border' : ''}`} style={{ borderColor: 'var(--pt-border)' }}>
                                        <div className="text-2xl font-bold truncate" style={{ color: useOverrides && overrides.primary ? overrides.primary : undefined }}>{plan.price}</div>
                                        {plan.period && <div className="text-xs text-muted-foreground truncate">{plan.period}</div>}
                                    </td>
                                ))}
                            </tr>

                            {/* Action Row (Top) */}
                            {hasAnyButtons && buttonPosition === 'after_price' && (
                                <tr className="bg-muted/5">
                                    {plans.map((plan, idx) => (
                                        <td key={idx} className={`p-4 text-center align-middle ${idx !== plans.length - 1 ? 'border-r border-border' : ''}`} style={{ borderColor: 'var(--pt-border)' }}>
                                            {shouldShowPlanButton(plan) && plan.link && (
                                                <a
                                                    href={plan.link}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-9 px-3 w-full shadow-sm"
                                                    style={{
                                                        backgroundColor: 'var(--pt-btn-bg)',
                                                        color: 'var(--pt-btn-text)',
                                                        // Fallback hover handling via CSS helper or just use filter here?
                                                        // Inline hover is impossible. We rely on class but override BG.
                                                        // Use a data attribute for styled-wrapper or just simple brightness filter on hover?
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
                                                    target="_blank"
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

                {/* Mobile Grid View */}
                <div className="md:hidden w-full">
                    {/* Sticky Header: Plan Names */}
                    <div className="sticky top-0 z-30 bg-background/95 backdrop-blur border-b border-border shadow-sm" style={{ borderColor: 'var(--pt-border)' }}>
                        <div className="grid divide-x divide-border" style={{ gridTemplateColumns: `repeat(${plans.length}, 1fr)`, borderColor: 'var(--pt-border)', backgroundColor: 'var(--pt-header-bg)' }}>
                            {plans.map((plan, idx) => (
                                <div key={idx} className="p-2 text-center relative group pt-5">
                                    {/* Discount Banner */}
                                    {plan.show_banner === '1' && plan.banner_text && (
                                        <div
                                            className="absolute top-0 right-0 text-[10px] font-bold px-1.5 py-0.5 rounded-bl-md text-white shadow-sm z-10 leading-none"
                                            style={{ backgroundColor: plan.banner_color || settings?.colors?.banner || '#10b981' }}
                                        >
                                            {plan.banner_text}
                                        </div>
                                    )}
                                    <h4 className="font-bold text-xs truncate leading-tight" style={{ color: 'var(--pt-header-text)' }}>{plan.name}</h4>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="divide-y divide-border" style={{ borderColor: 'var(--pt-border)' }}>
                        {/* Price Row */}
                        <div className="bg-muted/10">
                            <div className="px-2 py-1 text-[10px] font-bold text-muted-foreground uppercase bg-muted/20">Price</div>
                            <div className="grid divide-x divide-border" style={{ gridTemplateColumns: `repeat(${plans.length}, 1fr)`, borderColor: 'var(--pt-border)' }}>
                                {plans.map((plan, idx) => (
                                    <div key={idx} className="p-2 text-center">
                                        <span className="font-bold text-sm block truncate" style={{ color: useOverrides && overrides.primary ? overrides.primary : undefined }}>{plan.price}</span>
                                        {plan.period && <span className="text-[10px] text-muted-foreground block truncate">{plan.period}</span>}
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Action Buttons (Top/After Price) */}
                        {hasAnyButtons && buttonPosition === 'after_price' && (
                            <div className="bg-muted/5">
                                <div className="grid divide-x divide-border" style={{ gridTemplateColumns: `repeat(${plans.length}, 1fr)`, borderColor: 'var(--pt-border)' }}>
                                    {plans.map((plan, idx) => (
                                        <div key={idx} className="p-2 text-center">
                                            {shouldShowPlanButton(plan) && plan.link && (
                                                <a
                                                    href={plan.link}
                                                    target="_blank"
                                                    className="flex w-full items-center justify-center gap-1 py-1.5 rounded-md font-bold text-[10px] shadow-sm transition-all"
                                                    rel="noreferrer"
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
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Features Row */}
                        {showFeatures && (
                            <div>
                                <div className="px-2 py-1 text-[10px] font-bold text-muted-foreground uppercase bg-muted/20">Features</div>
                                <div className="grid divide-x divide-border" style={{ gridTemplateColumns: `repeat(${plans.length}, 1fr)`, borderColor: 'var(--pt-border)' }}>
                                    {plans.map((plan, idx) => (
                                        <div key={idx} className="p-2 align-top text-xs">
                                            <ul className="space-y-1">
                                                {plan.features.split('\n').map((feature, i) => (
                                                    feature.trim() && (
                                                        <li key={i} className="flex items-start gap-1 text-[10px] leading-tight text-left break-words">
                                                            <Check className="w-2.5 h-2.5 flex-shrink-0 mt-0.5" style={{ color: useOverrides && overrides.primary ? overrides.primary : undefined }} />
                                                            <span className="text-muted-foreground">{feature.trim()}</span>
                                                        </li>
                                                    )
                                                ))}
                                            </ul>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Action Buttons (Bottom) */}
                        {hasAnyButtons && buttonPosition === 'bottom' && (
                            <div className="bg-muted/5">
                                <div className="grid divide-x divide-border" style={{ gridTemplateColumns: `repeat(${plans.length}, 1fr)`, borderColor: 'var(--pt-border)' }}>
                                    {plans.map((plan, idx) => (
                                        <div key={idx} className="p-2 text-center">
                                            {shouldShowPlanButton(plan) && plan.link && (
                                                <a
                                                    href={plan.link}
                                                    target="_blank"
                                                    className="flex w-full items-center justify-center gap-1 py-1.5 rounded-md font-bold text-[10px] shadow-sm transition-all"
                                                    rel="noreferrer"
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
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Footer Link Button */}
                {/* Footer Link Button */}
                {resolvedShowFooter && item.details_link && (
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
                            onClick={() => window.open(item.details_link, '_blank')}
                        >
                            {footerButtonText || item.button_text || "Visit Website"} <ExternalLink className="w-4 h-4 ml-2" />
                        </Button>
                    </div>
                )}
            </div>
        </div>
    );
};

export default PricingTable;
