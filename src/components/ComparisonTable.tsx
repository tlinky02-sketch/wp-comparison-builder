import { useState } from "react";
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

  const colors = config?.colors || (window as any).wpcSettings?.colors || {};
  const couponBg = colors.couponBg || '#fef3c7';
  const couponText = colors.couponText || '#92400e';
  const couponHover = colors.couponHover || '#fde68a';
  const copiedColor = colors.copied || '#10b981';
  const prosBg = colors.prosBg || '#f0fdf4';
  const prosText = colors.prosText || '#166534';
  const consBg = colors.consBg || '#fef2f2';
  const consText = colors.consText || '#991b1b';

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
      // Restore default colors logic if handled by render
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

  // Combine all features
  const allFeatures = [...builtinFeatures, ...tagFeatures];

  // Filter features based on settings
  const isConfigEmpty = Object.keys(compareFeatures).length === 0;

  const features = allFeatures.filter(f => {
    if (compareFeatures[f.key] === '1') return true;
    // Default fallback for built-ins only if config is completely empty
    if (isConfigEmpty && ['price', 'rating'].includes(f.key)) return true;
    return false;
  });

  // Check if pros/cons should be shown
  const showPros = compareFeatures.pros === '1' || isConfigEmpty;
  const showCons = compareFeatures.cons === '1' || isConfigEmpty;

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
      if (item.raw_features?.includes(term.name)) {
        return <Check className="w-4 h-4 md:w-5 md:h-5 text-primary mx-auto" />;
      }
      return <span className="text-muted-foreground/30">—</span>;
    }

    switch (key) {
      case "price":
        return <span className="font-bold text-primary">{item.price}</span>;
      case "rating":
        return (
          <div className="flex items-center gap-1 justify-center">
            <Star className="w-3 h-3 md:w-4 md:h-4 fill-amber-400 text-amber-400" />
            <span className="text-xs md:text-base">{item.rating}</span>
          </div>
        );
      case "ssl":
        return item.features.ssl ? (
          <Check className="w-4 h-4 md:w-5 md:h-5 text-primary mx-auto" />
        ) : (
          <X className="w-4 h-4 md:w-5 md:h-5 text-destructive mx-auto" />
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
      {/* Desktop Table View */}
      <div className="hidden md:block w-full pb-4">
        <table className="w-full relative table-fixed">
          <thead>
            <tr className="border-b border-border bg-muted/40 backdrop-blur">
              <th className="p-2 md:p-6 text-left font-display font-bold text-foreground text-xs md:text-base sticky left-0 bg-background/95 z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] w-[20%]">
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
                        <div className="flex items-center justify-center gap-1 text-amber-500 mb-1">
                          <Star className="w-3 h-3 md:w-4 md:h-4 fill-current" />
                          <span className="font-medium text-xs md:text-base">{item.rating}</span>
                        </div>
                        <div className="text-lg md:text-2xl font-bold text-primary mb-2 md:mb-4">
                          {item.price}<span className="text-xs md:text-sm text-muted-foreground font-normal">{item.moSuffix || getText('moSuffix', "/mo")}</span>
                        </div>

                        {/* Coupon in Header if Main Item has one */}
                        {item.coupon_code && (
                          <button
                            className="px-2 py-1 rounded mb-2 w-full flex items-center justify-center gap-1 transition-colors text-[10px]"
                            style={{
                              backgroundColor: itemCouponBg,
                              color: itemCouponText,
                              border: `1px solid ${itemCouponText}40`
                            }}
                            onMouseEnter={(e) => { e.currentTarget.style.backgroundColor = couponHover; }}
                            onMouseLeave={(e) => { e.currentTarget.style.backgroundColor = itemCouponBg; }}
                            onClick={(e) => { e.stopPropagation(); copyCoupon(item.coupon_code || '', e.currentTarget); }}
                          >
                            <Tag className="w-3 h-3" /> {item.couponLabel || getText('getCoupon', 'Code')}: {item.coupon_code}
                          </button>
                        )}
                        {/* Footer / Button Visibility Check */}
                        {(item.design_overrides?.show_footer_table !== false) && (
                          <a
                            href={item.details_link || '#'}
                            target={target}
                            className="inline-flex items-center justify-center w-full text-white px-3 md:h-10 rounded-lg text-xs md:text-sm font-medium transition-all whitespace-nowrap"
                            rel="noreferrer"
                            style={{
                              backgroundColor: (window as any).wpcSettings?.colors?.primary || '#6366f1',
                            }}
                            onMouseEnter={(e) => {
                              const hoverColor = (window as any).wpcSettings?.colors?.hoverButton;
                              if (hoverColor) e.currentTarget.style.backgroundColor = hoverColor;
                              else e.currentTarget.style.filter = 'brightness(90%)';
                            }}
                            onMouseLeave={(e) => {
                              e.currentTarget.style.backgroundColor = (window as any).wpcSettings?.colors?.primary || '#6366f1';
                              e.currentTarget.style.filter = '';
                            }}
                          >
                            {item.button_text || item.visitSiteLabel || getText('visitSite', "Visit Site")} <ExternalLink className="w-3 h-3 md:w-4 md:h-4 ml-1 md:ml-2 flex-shrink-0" />
                          </a>
                        )}
                        {!config?.hideRemoveButton && (
                          <button
                            onClick={() => onRemove(item.id)}
                            className="mt-2 text-xs text-muted-foreground hover:text-destructive flex items-center justify-center gap-1 w-full"
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
            {features.slice(2).map((feature) => ( // Skip price/rating as they are in header or handled differently?
              // Actually price/rating are in header, so we can skip them or show them again? 
              // In the original code, Price and Rating were in logic but renderCell handled them. 
              // BUT in the Table Body loop, map logic: `features.map((feature) => ... ` 
              // If I include Price/Rating in `features` array, they will appear as rows. 
              // Usually Price/Rating is good to reinforce or just keep in header.
              // Original code had them in features array array.
              // I'll keep them but usually skip price/rating row if it's in header. 
              // Let's filter out price/rating from the body rows.
              <tr key={feature.key} className="group hover:bg-muted/30 transition-colors">
                <td className="p-2 md:p-6 text-left font-medium text-muted-foreground text-xs md:text-base sticky left-0 bg-background/95 backdrop-blur z-20 group-hover:bg-background/95 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                  {feature.label}
                </td>
                {items.map((item) => (
                  <td key={item.id} className="p-2 md:p-6 text-center text-xs md:text-base break-words whitespace-normal min-w-0">
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
                <div className="flex items-center gap-1 text-amber-500">
                  <Star className="w-4 h-4 fill-current" />
                  <span className="text-sm font-medium">{activeItem.rating}</span>
                </div>
                <span className="text-2xl font-bold text-primary">{activeItem.price}<span className="text-sm text-muted-foreground font-normal">{activeItem.moSuffix || getText('moSuffix', '/mo')}</span></span>
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
                className="text-muted-foreground hover:text-destructive p-1"
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
              className="flex w-full items-center justify-center gap-2 text-white transition-all py-3 rounded-xl font-bold text-sm shadow-lg"
              rel="noreferrer"
              style={{
                backgroundColor: (window as any).wpcSettings?.colors?.primary || '#6366f1',
              }}
              onMouseEnter={(e) => {
                const hoverColor = (window as any).wpcSettings?.colors?.hoverButton;
                if (hoverColor) e.currentTarget.style.backgroundColor = hoverColor;
                else e.currentTarget.style.filter = 'brightness(90%)';
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.backgroundColor = (window as any).wpcSettings?.colors?.primary || '#6366f1';
                e.currentTarget.style.filter = '';
              }}
            >
              {activeItem.button_text || getText('visitSite', "Visit Site")} <ExternalLink className="w-4 h-4" />
            </a>
          )}
        </div>
      </div>
    </div>
  );
};

export default ComparisonTable;

