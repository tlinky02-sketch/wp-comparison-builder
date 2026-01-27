import React, { useState } from "react";
import { Check, X, Star, ExternalLink, ShoppingBag, Tag } from "lucide-react";
import { ComparisonItem } from "./PlatformCard";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

interface ComparisonTableProps {
  items: ComparisonItem[];
  onRemove: (id: string) => void;
  labels?: any;
  config?: any;
}

const ComparisonTable = ({ items, onRemove, labels, config }: ComparisonTableProps) => {
  const getText = (key: string, def: string) => labels?.[key] || def;
  const target = config?.targetDetails || (window as any).wpcSettings?.target_details || '_blank';

  // Merge global colors with config-specific overrides (config wins for specific keys)
  const globalColors = (window as any).wpcSettings?.colors || {};
  const colors = { ...globalColors, ...(config?.colors || {}) };

  // Centralized Color Definitions (User Setting > Default)
  const primaryColor = colors.primary || '#6366f1';
  const btnTextColor = colors.btnText || '#ffffff';
  const hoverColor = colors.hoverButton; // Allow undefined to fallback to brightness filter
  const mutedTextColor = colors.textMuted || '#64748b';

  const couponBg = colors.couponBg || '#fef3c7';
  const couponText = colors.couponText || '#92400e';
  const couponHover = colors.couponHover || '#fde68a';
  const copiedColor = colors.copied || '#10b981';
  const prosBg = colors.prosBg || '#f0fdf4';
  const prosText = colors.prosText || '#166534';
  const consBg = colors.consBg || '#fef2f2';
  const consText = colors.consText || '#991b1b';
  const tickColor = colors.tick || '#10b981';
  const crossColor = colors.cross || '#94a3b8';

  // Billing Cycle Sync: Collect all unique billing cycles from items
  const allCycles = React.useMemo(() => {
    const cyclesMap = new Map<string, string>();
    items.forEach(item => {
      const cycles = (item as any).billing_cycles || [];
      cycles.forEach((c: { slug: string, label: string }) => {
        if (c.slug && !cyclesMap.has(c.slug)) {
          cyclesMap.set(c.slug, c.label);
        }
      });
    });
    if (cyclesMap.size === 0) {
      cyclesMap.set('monthly', 'Monthly');
    }
    return Array.from(cyclesMap.entries()).map(([slug, label]) => ({ slug, label }));
  }, [items]);

  // Category / Variant Sync
  // Collect all unique variant categories
  const allCategories = React.useMemo(() => {
    const cats = new Set<string>();
    items.forEach(item => {
      if (item.variants?.enabled && item.variants.plans_by_category) {
        Object.keys(item.variants.plans_by_category).forEach(c => cats.add(c));
      }
    });

    // Check config for pre-selected category
    const preselected = config?.category || null;
    return {
      list: Array.from(cats),
      preselected
    };
  }, [items, config]);

  const [selectedCategory, setSelectedCategory] = useState<string | null>(() => {
    // Priority: Config Shortcode > First Item Default > Null
    if (allCategories.preselected) return allCategories.preselected;
    return items[0]?.variants?.default_category || null;
  });

  const [selectedCycle, setSelectedCycle] = useState<string>(() => {
    // Default to first item's default_cycle or 'monthly'
    return (items[0] as any)?.default_cycle || 'monthly';
  });

  // Helper to get price for a specific cycle AND category
  const getPriceForCycle = (item: ComparisonItem, cycle: string): { amount: string, period: string, unavailable?: boolean } => {
    const emptyPriceText = (window as any).wpcSettings?.texts?.emptyPrice || 'Free';
    let plans = (item as any).pricing_plans || [];

    // Filter plans by category if selected
    if (selectedCategory && item.variants?.enabled && item.variants.plans_by_category) {
      const allowedIndices = item.variants.plans_by_category[selectedCategory];
      if (allowedIndices && Array.isArray(allowedIndices)) {
        const indices = allowedIndices.map(Number);
        const filtered = plans.filter((_: any, idx: number) => indices.includes(idx));
        if (filtered.length > 0) {
          plans = filtered;
        }
      }
    }

    // Try to find price in any plan for this cycle
    for (const plan of plans) {
      // Strict check: if plan has price for cycle (and it's not empty)
      if (plan.prices && plan.prices[cycle]) {
        const p = plan.prices[cycle];
        if (p.amount !== undefined && p.amount !== '') {
          return { amount: p.amount, period: p.period || '' };
        }
      }
      // Legacy fallback check (if accessing plan.price/yearly_price directly)
      // Note: PricingTable has stricter "hasPriceForCycle". We should match that logic if possible.
      // But typically "prices" object is the new standard.
    }

    // Fallback to item.price (the pre-computed default) IF no category is selected OR if fallback is desired
    // However, if category is selected, we might want to be stricter? 
    // User said "Should only show that plan". 
    // If filtered plans have no valid price for this cycle, we return "Free" (or empty).
    // Original fallback logic:
    if (plans.length > 0 && !selectedCategory) {
      if (item.price && item.price !== '0') {
        return { amount: item.price, period: item.moSuffix || getText('moSuffix', '/mo') };
      }
    }

    // If strict category selected but no plans match cycle, or just no price found:
    // User Requirement: Check if it's "Unavailable" (filtered out by category) vs just "No Price" (Free)
    // If strict filtering was applied (selectedCategory is set) and we found NO valid price logic above:
    if (selectedCategory) {
      // If plans were filtered, and we didn't find a price in the filtered subset.
      // It implies the plan(s) for this category don't exist or don't have a price.
      // We should show X.
      return { amount: '', period: '', unavailable: true };
    }

    // Check if we should show Free
    return { amount: emptyPriceText, period: '' };
  };

  const copyCoupon = (code: string, btn: HTMLButtonElement) => {
    const originalText = btn.innerHTML;
    const originalStyle = btn.getAttribute('style') || '';

    // Apply Copied Style
    const applyCopiedStyle = () => {
      btn.innerHTML = `<svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> ${getText('copied', 'Copied!')}`;
      btn.style.backgroundColor = copiedColor + '15'; // Very light bg
      btn.style.borderColor = copiedColor;
      btn.style.color = copiedColor;
    };

    const restoreOriginal = () => {
      btn.innerHTML = originalText;
      if (originalStyle) btn.setAttribute('style', originalStyle);
      else btn.removeAttribute('style');
    };

    if (navigator.clipboard) {
      navigator.clipboard.writeText(code).then(() => {
        applyCopiedStyle();
        setTimeout(restoreOriginal, 2000);
      });
    } else {
      // Fallback
      const ta = document.createElement('textarea');
      ta.value = code;
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      document.body.removeChild(ta);
      applyCopiedStyle();
      setTimeout(restoreOriginal, 2000);
    }
  };

  // Mobile: Active item index for tab switching
  const [activeItemIndex, setActiveItemIndex] = useState(0);
  const activeItem = items[activeItemIndex] || items[0];

  if (items.length === 0) {
    return (
      <div className="bg-card rounded-2xl border border-border p-12 text-center">
        <p className="text-muted-foreground">{getText('noItemsToCompare', 'Select up to 4 items to compare')}</p>
      </div>
    );
  }

  // Dynamic features based on backend settings
  const compareFeatures = config?.compareFeatures || (window as any).wpcSettings?.compareFeatures || {};
  const compareTagTerms = config?.compareTagTerms || (window as any).wpcSettings?.compareTagTerms || [];

  // Built-in features (price, rating)
  const builtinFeatures = [
    { key: "price", label: getText('priceLabel', "Price") },
    { key: "rating", label: getText('ratingLabel', "Rating") },
  ];

  // Dynamic tag features from taxonomy
  const tagFeatures = compareTagTerms.map((term: any) => ({
    key: term.key, // e.g. "tag_123"
    label: term.name,
    slug: term.slug,
  }));

  // 1. Construct the Mapping of Key -> Feature Definition
  const featureDefinitions: Record<string, any> = {};
  builtinFeatures.forEach(f => featureDefinitions[f.key] = f);
  tagFeatures.forEach(f => featureDefinitions[f.key] = f);

  // 2. Determine Ordered List of Enabled Feature Keys
  let enabledKeys: string[] = [];
  let showPros = false;
  let showCons = false;

  if (Array.isArray(compareFeatures)) {
    // NEW format: compareFeatures IS the ordered list of keys
    enabledKeys = compareFeatures;
    showPros = compareFeatures.includes('pros');
    showCons = compareFeatures.includes('cons');
  } else {
    // OLD format: Object {'key': '1'}
    // Use default order from allFeatures (Builtins then Tags)
    const allFeatures = [...builtinFeatures, ...tagFeatures];
    const isConfigEmpty = Object.keys(compareFeatures).length === 0;

    enabledKeys = allFeatures
      .filter(f => {
        if (compareFeatures[f.key] === '1') return true;
        if (isConfigEmpty && ['price', 'rating'].includes(f.key)) return true;
        return false;
      })
      .map(f => f.key);

    showPros = compareFeatures.pros === '1' || isConfigEmpty;
    showCons = compareFeatures.cons === '1' || isConfigEmpty;
  }

  // 3. Construct Final Features Array (preserving order)
  const features = enabledKeys
    .filter(key => featureDefinitions[key]) // Remove invalid/deleted keys
    .map(key => featureDefinitions[key]);

  const renderCell = (key: string, item: ComparisonItem) => {
    // 1. Dynamic Tag Features
    if (key.startsWith('tag_')) {
      const term = compareTagTerms.find((t: any) => t.key === key);
      if (!term) return <span className="text-muted-foreground/30">—</span>;

      // Smart Legacy fallback: If tag name matches legacy field, show text value
      const lowerName = term.name.toLowerCase();
      const itemFeatures = item.features;

      if (lowerName === 'products' && itemFeatures.products) return itemFeatures.products;
      if ((lowerName === 'fees' || lowerName === 'transaction fees') && itemFeatures.fees) return itemFeatures.fees;
      if ((lowerName === 'channels' || lowerName === 'sales channels') && itemFeatures.channels) return itemFeatures.channels;
      if (lowerName === 'support' && itemFeatures.support) return itemFeatures.support;

      // Boolean Tag Check
      const raw = item.raw_features || [];
      const hasTag =
        raw.includes(term.name) ||
        raw.includes(term.slug) ||
        raw.some((f: string) => f.toLowerCase() === term.name.toLowerCase());

      if (hasTag) {
        return <Check
          className="wpc-tick w-4 h-4 md:w-5 md:h-5 mx-auto"
          style={{ color: tickColor }}
          ref={(el) => { if (el) { el.style.setProperty('color', tickColor, 'important'); el.style.setProperty('stroke', tickColor, 'important'); } }}
        />;
      }
      return <X
        className="wpc-cross w-4 h-4 md:w-5 md:h-5 mx-auto"
        style={{ color: crossColor }}
        ref={(el) => { if (el) { el.style.setProperty('color', crossColor, 'important'); el.style.setProperty('stroke', crossColor, 'important'); } }}
      />;
    }

    switch (key) {
      case "price":
        const priceInfo = getPriceForCycle(item, selectedCycle);

        if (priceInfo.unavailable) {
          return (
            <X
              className="wpc-cross w-4 h-4 md:w-5 md:h-5 mx-auto"
              style={{ color: crossColor }}
              ref={(el) => { if (el) { el.style.setProperty('color', crossColor, 'important'); el.style.setProperty('stroke', crossColor, 'important'); } }}
            />
          );
        }

        return (
          <span
            className="font-bold"
            ref={(el) => {
              if (el) {
                el.style.setProperty('color', primaryColor, 'important');
              }
            }}
            style={{ color: primaryColor }}
          >
            {priceInfo.amount}
            {priceInfo.period && (
              <span className="text-xs font-normal ml-0.5" style={{ color: mutedTextColor }}>{priceInfo.period}</span>
            )}
          </span>
        );
      case "rating":
        const starColor = colors.stars || 'var(--wpc-star-color, #fbbf24)';
        return (
          <div className="flex items-center gap-1 justify-center">
            <Star className="w-3 h-3 md:w-4 md:h-4" style={{ fill: starColor, color: starColor }} />
            <span className="text-xs md:text-base">{item.rating}</span>
          </div>
        );
      case "ssl":
        return item.features.ssl ? (
          <Check
            className="wpc-tick w-4 h-4 md:w-5 md:h-5 mx-auto"
            style={{ color: tickColor }}
            ref={(el) => { if (el) { el.style.setProperty('color', tickColor, 'important'); el.style.setProperty('stroke', tickColor, 'important'); } }}
          />
        ) : (
          <X
            className="wpc-cross w-4 h-4 md:w-5 md:h-5 mx-auto"
            style={{ color: crossColor }}
            ref={(el) => { if (el) { el.style.setProperty('color', crossColor, 'important'); el.style.setProperty('stroke', crossColor, 'important'); } }}
          />
        );
      case "products":
        return item.features.products || "—";
      case "fees":
        return item.features.fees || "—";
      case "channels":
        return item.features.channels || "—";
      case "support":
        return item.features.support || "—";
      default:
        // Fallback for any other keys
        return (item.features as any)[key] || "—";
    }
  };

  return (
    <div className="bg-card rounded-2xl border border-border shadow-2xl overflow-hidden">
      {/* Category Tabs (Only if categories exist AND not pre-selected via shortcode) */}
      {!config?.category && allCategories.list.length > 0 && (
        <div className="flex flex-wrap items-center justify-center gap-2 p-4 border-b border-border bg-muted/20 pb-2">
          <div
            onClick={() => setSelectedCategory(null)}
            className={`px-4 py-1.5 rounded-full text-sm font-bold transition-all border cursor-pointer ${!selectedCategory
              ? "shadow-sm"
              : "bg-transparent text-muted-foreground border-border hover:bg-muted"
              }`}
            style={!selectedCategory ? {
              backgroundColor: primaryColor,
              color: 'var(--wpc-btn-text, #ffffff) !important',
              borderColor: primaryColor
            } : {}}
          >
            {(window as any).wpcSettings?.texts?.allPlans || 'All Plans'}
          </div>
          {allCategories.list.map(catSlug => {
            const pretty = catSlug.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
            const isActive = selectedCategory === catSlug;
            return (
              <div
                key={catSlug}
                onClick={() => setSelectedCategory(catSlug)}
                className={`px-4 py-1.5 rounded-full text-sm font-bold transition-all border cursor-pointer ${isActive
                  ? "shadow-sm"
                  : "bg-transparent text-muted-foreground border-border hover:bg-muted"
                  }`}
                style={isActive ? {
                  backgroundColor: primaryColor,
                  color: 'var(--wpc-btn-text, #ffffff) !important',
                  borderColor: primaryColor
                } : {}}
              >
                {pretty}
              </div>
            );
          })}
        </div>
      )}

      {/* Billing Cycle Toggle (only if multiple cycles available) */}
      {allCycles.length > 1 && (
        <div className="flex items-center justify-center gap-2 p-4 border-b border-border bg-muted/20">
          <span className="text-sm text-muted-foreground mr-2">{getText('billingCycle', 'Billing:')}</span>
          {allCycles.map((cycle) => (
            <button
              key={cycle.slug}
              onClick={() => setSelectedCycle(cycle.slug)}
              className={cn(
                "wpc-text-link px-4 py-1.5 rounded-full text-sm font-medium transition-all border",
                selectedCycle === cycle.slug
                  ? "shadow-sm"
                  : "bg-transparent border-border hover:bg-muted"
              )}
              style={selectedCycle === cycle.slug ? {
                backgroundColor: primaryColor,
                color: btnTextColor,
                borderColor: primaryColor
              } : {
                color: mutedTextColor
              }}
            >
              {cycle.label}
            </button>
          ))}
        </div>
      )}

      {/* Desktop Table View */}
      <div className="hidden md:block w-full pb-4">
        <table className="w-full relative table-fixed">
          <thead>
            <tr className="border-b border-border bg-muted/40 backdrop-blur">
              <th className="p-2 md:p-6 text-left font-display font-bold text-foreground text-xs md:text-[length:var(--wpc-font-size-body,1rem)] sticky left-0 bg-background/95 z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] w-[20%]">
                {getText('featureHeader', "Feature")}
              </th>
              {items.map((item) => {
                const itemCouponBg = item.design_overrides?.coupon_bg || couponBg;
                const itemCouponText = item.design_overrides?.coupon_text || couponText;

                return (
                  <th key={item.id} className="p-2 md:p-6 text-center">
                    <div className="flex flex-col items-center gap-2 md:gap-4">
                      <div className="w-10 h-10 md:w-16 md:h-16 rounded-lg md:rounded-xl bg-white p-1 md:p-2 shadow-sm border border-border/50 flex items-center justify-center overflow-hidden flex-shrink-0">
                        {item.logo ? (
                          <img src={item.logo} alt={item.name} className="w-full h-full object-contain" />
                        ) : (
                          <ShoppingBag className="w-6 h-6 text-muted-foreground" />
                        )}
                      </div>
                      <div className="w-full">
                        <h3 className="font-bold text-foreground text-sm md:text-lg mb-1 min-h-[1.75rem] flex items-center justify-center">{item.name}</h3>
                        <div className="flex items-center justify-center gap-1 mb-1" style={{ color: colors.stars || 'var(--wpc-star-color, #fbbf24)' }}>
                          <Star className="w-3 h-3 md:w-4 md:h-4 fill-current" />
                          <span className="font-medium text-xs md:text-base">{item.rating}</span>
                        </div>
                        {(() => {
                          const priceInfo = getPriceForCycle(item, selectedCycle);
                          return (
                            <div
                              className="text-lg md:text-2xl font-bold mb-2 md:mb-4"
                              ref={(el) => {
                                if (el) {
                                  el.style.setProperty('color', primaryColor, 'important');
                                }
                              }}
                              style={{ color: primaryColor }}
                            >
                              {priceInfo.amount}
                              {priceInfo.period && (
                                <span className="text-xs md:text-sm font-normal ml-1" style={{ color: mutedTextColor }}>
                                  {priceInfo.period}
                                </span>
                              )}
                            </div>
                          );
                        })()}

                        {/* Coupon in Header if Main Item has one */}
                        {item.coupon_code && (
                          <div className="w-full mb-2">
                            {(() => {
                              const bg = itemCouponBg;
                              const text = itemCouponText;
                              const hover = item.design_overrides?.coupon_hover || colors.couponHover || '#fde68a';

                              // Copied state colors
                              const copiedColor = colors.copied || '#10b981';
                              const copiedTextLabel = item.copiedLabel || labels?.copied || "Copied!";

                              return (
                                <button
                                  className="px-2 py-1 rounded w-full flex items-center justify-center gap-1 transition-colors text-[10px]"
                                  style={{
                                    backgroundColor: bg,
                                    color: text,
                                    border: `1px solid ${text}40`
                                  }}
                                  onMouseEnter={(e) => { e.currentTarget.style.backgroundColor = hover; }}
                                  onMouseLeave={(e) => { e.currentTarget.style.backgroundColor = bg; }}
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    const btn = e.currentTarget;
                                    const originalHTML = btn.innerHTML;

                                    // Copy Action
                                    const performCopy = () => {
                                      btn.innerHTML = `<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z"/></svg> ${copiedTextLabel}`;
                                      btn.style.backgroundColor = copiedColor;
                                      btn.style.borderColor = copiedColor;
                                      btn.style.color = '#ffffff';

                                      setTimeout(() => {
                                        btn.innerHTML = originalHTML;
                                        btn.style.backgroundColor = bg;
                                        btn.style.borderColor = `${text}40`;
                                        btn.style.color = text;
                                      }, 2000);
                                    };

                                    if (navigator.clipboard) {
                                      navigator.clipboard.writeText(item.coupon_code || '').then(performCopy);
                                    } else {
                                      // Fallback
                                      const ta = document.createElement('textarea');
                                      ta.value = item.coupon_code || '';
                                      document.body.appendChild(ta);
                                      ta.select();
                                      document.execCommand('copy');
                                      document.body.removeChild(ta);
                                      performCopy();
                                    }
                                  }}
                                >
                                  <Tag className="w-3 h-3" /> {item.couponLabel || getText('getCoupon', 'Code')}: {item.coupon_code}
                                </button>
                              );
                            })()}
                          </div>
                        )}
                        {/* Footer / Button Visibility Check */}
                        {(item.design_overrides?.show_footer_table !== false) && (
                          (() => {
                            return (
                              <a
                                href={item.details_link || '#'}
                                target={target}
                                className="wpc-cta-btn inline-flex items-center justify-center w-full px-3 md:h-10 rounded-lg text-xs md:text-sm font-medium transition-all whitespace-nowrap"
                                rel="noreferrer"
                                style={{
                                  backgroundColor: primaryColor,
                                  color: btnTextColor
                                }}
                                onMouseEnter={(e) => {
                                  if (hoverColor) e.currentTarget.style.backgroundColor = hoverColor;
                                  else e.currentTarget.style.filter = 'brightness(90%)';
                                }}
                                onMouseLeave={(e) => {
                                  e.currentTarget.style.backgroundColor = primaryColor;
                                  e.currentTarget.style.filter = '';
                                }}
                              >
                                {item.button_text || item.visitSiteLabel || getText('visitSite', "Visit Site")} <ExternalLink className="w-3 h-3 md:w-4 md:h-4 ml-1 md:ml-2 flex-shrink-0" style={{ stroke: btnTextColor }} />
                              </a>
                            );
                          })()
                        )}
                        {!config?.hideRemoveButton && (
                          <button
                            onClick={() => onRemove(item.id)}
                            className="wpc-text-link mt-2 text-xs flex items-center justify-center gap-1 w-full opacity-70 hover:opacity-100 transition-opacity"
                            style={{ color: mutedTextColor }}
                          >
                            <X className="w-3 h-3" /> {getText('remove', 'Remove')}
                          </button>
                        )}
                      </div>
                    </div>
                  </th>
                );
              })}
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {features.filter(f => !["price", "rating"].includes(f.key)).map((feature) => (
              // Price/Rating are displayed in header, so we filter them from body rows
              <tr key={feature.key} className="group hover:bg-muted/30 transition-colors">
                <td className="p-2 md:p-6 text-left font-medium text-muted-foreground text-xs md:text-[length:var(--wpc-font-size-body,1rem)] sticky left-0 bg-background/95 backdrop-blur z-20 group-hover:bg-background/95 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                  {feature.label}
                </td>
                {items.map((item) => (
                  <td key={item.id} className="p-2 md:p-6 text-center text-xs md:text-[length:var(--wpc-font-size-body,1rem)] break-words whitespace-normal min-w-0">
                    {renderCell(feature.key, item)}
                  </td>
                ))}
              </tr>
            ))}
            {/* Pros */}
            {showPros && (
              <tr className="transition-colors" style={{ backgroundColor: prosBg }}>
                <td className="p-5 font-bold text-foreground sticky left-0 bg-inherit shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] z-10 w-[20%]">{getText('prosLabel', "Pros")}</td>
                {items.map((item) => (
                  <td key={item.id} className="p-5 align-top break-words whitespace-normal min-w-0">
                    <ul className="space-y-2 text-left p-4 rounded-xl border w-full min-w-0"
                      style={{ backgroundColor: `${prosBg}80`, borderColor: `${prosText}20` }}>
                      {item.pros.slice(0, 3).map((pro, i) => (
                        <li key={i} className="flex items-start gap-2 text-sm text-foreground break-words whitespace-normal">
                          <Check className="w-4 h-4 flex-shrink-0 mt-0.5" style={{ color: prosText }} />
                          <span className="min-w-0">{pro}</span>
                        </li>
                      ))}
                    </ul>
                  </td>
                ))}
              </tr>
            )}
            {/* Cons */}
            {showCons && (
              <tr className="transition-colors" style={{ backgroundColor: consBg }}>
                <td className="p-5 font-bold text-foreground sticky left-0 bg-inherit shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] z-10 w-[20%]">{getText('consLabel', "Cons")}</td>
                {items.map((item) => (
                  <td key={item.id} className="p-5 align-top break-words whitespace-normal min-w-0">
                    <ul className="space-y-2 text-left p-4 rounded-xl border w-full min-w-0"
                      style={{ backgroundColor: `${consBg}80`, borderColor: `${consText}20` }}>
                      {item.cons.slice(0, 3).map((con, i) => (
                        <li key={i} className="flex items-start gap-2 text-sm text-foreground break-words whitespace-normal">
                          <X className="w-4 h-4 flex-shrink-0 mt-0.5" style={{ color: consText }} />
                          <span className="min-w-0">{con}</span>
                        </li>
                      ))}
                    </ul>
                  </td>
                ))}
              </tr>
            )}
          </tbody>
        </table>
      </div>

      {/* Mobile: Tabbed Single-Item View */}
      <div className="md:hidden">
        {/* Tab Bar: Logo + Name for each item */}
        <div className="sticky top-0 z-40 bg-background/95 backdrop-blur border-b border-border">
          <div className="flex overflow-x-auto">
            {items.map((item, idx) => (
              <button
                key={item.id}
                onClick={() => setActiveItemIndex(idx)}
                className={cn(
                  "flex-1 min-w-0 flex flex-col items-center gap-1 px-3 py-3 transition-all border-b-2",
                  activeItemIndex === idx
                    ? "border-primary bg-primary/5"
                    : "border-transparent hover:bg-muted/50"
                )}
              >
                <div className="w-8 h-8 rounded-lg bg-white p-1 border border-border/50 flex items-center justify-center overflow-hidden shrink-0">
                  {item.logo ? (
                    <img src={item.logo} alt={item.name} className="w-full h-full object-contain" />
                  ) : (
                    <ShoppingBag className="w-4 h-4 text-muted-foreground" />
                  )}
                </div>
                <span className={cn(
                  "text-xs font-medium truncate max-w-full",
                  activeItemIndex === idx ? "text-primary" : "text-muted-foreground"
                )}>
                  {item.name}
                </span>
              </button>
            ))}
          </div>
        </div>

        {/* Active Item Content */}
        <div className="p-4 space-y-4">
          {/* Header: Logo, Name, Rating, Price */}
          <div className="flex items-start gap-4 pb-4 border-b border-border">
            <div className="w-16 h-16 rounded-xl bg-white p-2 border border-border/50 flex items-center justify-center overflow-hidden shrink-0 shadow-sm">
              {activeItem.logo ? (
                <img src={activeItem.logo} alt={activeItem.name} className="w-full h-full object-contain" />
              ) : (
                <ShoppingBag className="w-8 h-8 text-muted-foreground" />
              )}
            </div>
            <div className="flex-1 min-w-0">
              <h3 className="font-bold text-lg mb-1">{activeItem.name}</h3>
              <div className="flex items-center gap-2 mb-2">
                <div className="flex items-center gap-1" style={{ color: colors.stars || 'var(--wpc-star-color, #fbbf24)' }}>
                  <Star className="w-4 h-4 fill-current" />
                  <span className="text-sm font-medium">{activeItem.rating}</span>
                </div>
                {(() => {
                  const priceInfo = getPriceForCycle(activeItem, selectedCycle);
                  return (
                    <span
                      className="text-2xl font-bold text-primary"
                      ref={(el) => {
                        if (el) {
                          el.style.setProperty('color', primaryColor, 'important');
                        }
                      }}
                      style={{ color: primaryColor }}
                    >
                      {priceInfo.amount}
                      {priceInfo.period && (
                        <span className="text-sm text-muted-foreground font-normal">{priceInfo.period}</span>
                      )}
                    </span>
                  );
                })()}
              </div>
              {/* Coupon */}
              {activeItem.coupon_code && (
                <button
                  className="px-3 py-1.5 rounded-lg text-xs flex items-center gap-1.5 w-full justify-center"
                  style={{
                    backgroundColor: activeItem.design_overrides?.coupon_bg || couponBg,
                    color: activeItem.design_overrides?.coupon_text || couponText,
                    border: `1px solid ${couponText}40`
                  }}
                  onClick={(e) => { e.stopPropagation(); copyCoupon(activeItem.coupon_code || '', e.currentTarget); }}
                >
                  <Tag className="w-3 h-3" /> {getText('getCoupon', 'Code')}: {activeItem.coupon_code}
                </button>
              )}
            </div>
            {!config?.hideRemoveButton && (
              <button
                onClick={() => onRemove(activeItem.id)}
                className="p-1 opacity-70 hover:opacity-100 transition-opacity"
                style={{ color: mutedTextColor }}
              >
                <X className="w-5 h-5" />
              </button>
            )}
          </div>

          {/* Features */}
          <div className="space-y-3">
            {features.filter(f => !["price", "rating"].includes(f.key)).map((feature) => (
              <div key={feature.key} className="flex items-center justify-between py-2 border-b border-border/50 last:border-0">
                <span className="text-sm text-muted-foreground">{feature.label}</span>
                <span className="text-sm font-medium">{renderCell(feature.key, activeItem)}</span>
              </div>
            ))}
          </div>

          {/* Pros */}
          {showPros && (
            <div className="rounded-xl p-4" style={{ backgroundColor: prosBg }}>
              <h4 className="font-bold text-sm mb-2" style={{ color: prosText }}>{getText('prosLabel', 'Pros')}</h4>
              <ul className="space-y-2">
                {activeItem.pros.slice(0, 4).map((pro, i) => (
                  <li key={i} className="flex items-start gap-2 text-sm">
                    <Check className="w-4 h-4 flex-shrink-0 mt-0.5" style={{ color: prosText }} />
                    <span>{pro}</span>
                  </li>
                ))}
              </ul>
            </div>
          )}

          {/* Cons */}
          {showCons && (
            <div className="rounded-xl p-4" style={{ backgroundColor: consBg }}>
              <h4 className="font-bold text-sm mb-2" style={{ color: consText }}>{getText('consLabel', 'Cons')}</h4>
              <ul className="space-y-2">
                {activeItem.cons.slice(0, 4).map((con, i) => (
                  <li key={i} className="flex items-start gap-2 text-sm">
                    <X className="w-4 h-4 flex-shrink-0 mt-0.5" style={{ color: consText }} />
                    <span>{con}</span>
                  </li>
                ))}
              </ul>
            </div>
          )}

          {/* CTA Button */}
          {(activeItem.design_overrides?.show_footer_table !== false) && (
            <a
              href={activeItem.details_link || '#'}
              target={target}
              className="wpc-cta-btn flex w-full items-center justify-center gap-2 transition-all py-3 rounded-xl font-bold text-sm shadow-lg"
              rel="noreferrer"
              style={{
                backgroundColor: primaryColor,
                color: btnTextColor,
              }}
              onMouseEnter={(e) => {
                if (hoverColor) e.currentTarget.style.backgroundColor = hoverColor;
                else e.currentTarget.style.filter = 'brightness(90%)';
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.backgroundColor = primaryColor;
                e.currentTarget.style.filter = '';
              }}
            >
              {activeItem.button_text || getText('visitSite', "Visit Site")} <ExternalLink className="w-4 h-4" style={{ stroke: btnTextColor }} />
            </a>
          )}
        </div>
      </div>
    </div>
  );
};

export default ComparisonTable;

