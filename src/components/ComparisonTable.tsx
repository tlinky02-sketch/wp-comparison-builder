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
  if (items.length === 0) {
    return (
      <div className="bg-card rounded-2xl border border-border p-12 text-center">
        <p className="text-muted-foreground">{getText('noItemsToCompare', 'Select up to 4 items to compare')}</p>
      </div>
    );
  }

  // TODO: Make this dynamic based on backend settings
  const features = [
    { key: "price", label: getText('priceLabel', "Price") },
    { key: "rating", label: getText('ratingLabel', "Rating") },
    { key: "products", label: getText('featureProducts', "Products") },
    { key: "fees", label: getText('featureFees', "Transaction Fees") },
    { key: "channels", label: getText('featureChannels', "Sales Channels") },
    { key: "ssl", label: getText('featureSsl', "Free SSL") },
    { key: "support", label: getText('featureSupport', "Support") },
  ];

  const renderCell = (key: string, item: ComparisonItem) => {
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
            {/* Cons */}
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
          </tbody>
        </table>
      </div>

      {/* Mobile Side-by-Side View (Grid) */}
      <div className="md:hidden">
        {/* Sticky Header: Item Names */}
        <div className="sticky top-0 z-40 bg-background/95 backdrop-blur border-b border-border shadow-sm">
          <div className="grid" style={{ gridTemplateColumns: `repeat(${items.length}, 1fr)` }}>
            {items.map((item) => (
              <div key={item.id} className="p-1.5 text-center border-r border-border last:border-r-0 relative group flex flex-col items-center justify-center min-h-[60px]">
                {!config?.hideRemoveButton && (
                  <button
                    onClick={() => onRemove(item.id)}
                    className="absolute top-0.5 right-0.5 text-muted-foreground/50 hover:text-destructive p-1 bg-white/80 rounded-full z-10"
                  >
                    <X className="w-3 h-3" />
                  </button>
                )}
                <div className="w-6 h-6 md:w-8 md:h-8 mx-auto mb-1 bg-white rounded p-0.5 border border-border/50 flex items-center justify-center overflow-hidden shrink-0">
                  {item.logo ? (
                    <img src={item.logo} alt={item.name} className="w-full h-full object-contain" />
                  ) : (
                    <ShoppingBag className="w-4 h-4 text-muted-foreground" />
                  )}
                </div>
                <h3 className="font-bold text-[10px] md:text-xs leading-none break-words w-full px-0.5 line-clamp-2">{item.name}</h3>
              </div>
            ))}
          </div>
        </div>

        {/* Content */}
        <div className="divide-y divide-border">
          {/* Price Row */}
          <div className="bg-muted/10">
            <div className="px-2 py-1 text-[10px] font-bold text-muted-foreground uppercase bg-muted/20">{getText('priceLabel', 'Price')}</div>
            <div className="grid" style={{ gridTemplateColumns: `repeat(${items.length}, 1fr)` }}>
              {items.map((item) => (
                <div key={item.id} className="p-2 text-center border-r border-border last:border-r-0">
                  <span className="font-bold text-primary text-sm">{item.price}</span>
                </div>
              ))}
            </div>
          </div>

          {/* Rating Row */}
          <div className="bg-muted/10">
            <div className="px-2 py-1 text-[10px] font-bold text-muted-foreground uppercase bg-muted/20">{getText('ratingLabel', 'Rating')}</div>
            <div className="grid" style={{ gridTemplateColumns: `repeat(${items.length}, 1fr)` }}>
              {items.map((item) => (
                <div key={item.id} className="p-2 text-center border-r border-border last:border-r-0 flex justify-center">
                  <div className="flex items-center gap-1 text-amber-500">
                    <Star className="w-3 h-3 fill-current" />
                    <span className="text-xs font-bold">{item.rating}</span>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Feature Rows */}
          {features.filter(f => !["price", "rating"].includes(f.key)).map((feature) => (
            <div key={feature.key}>
              <div className="px-2 py-1 text-[10px] font-bold text-red-600/80 uppercase bg-red-50/50 border-y border-red-100/50">{feature.label}</div>
              <div className="grid" style={{ gridTemplateColumns: `repeat(${items.length}, 1fr)` }}>
                {items.map((item) => (
                  <div key={item.id} className="p-2 text-center text-xs border-r border-border last:border-r-0 break-words">
                    {renderCell(feature.key, item)}
                  </div>
                ))}
              </div>
            </div>
          ))}

          {/* Pros Row */}
          <div>
            <div className="px-2 py-1 text-[10px] font-bold text-green-700 uppercase bg-green-50/50">{getText('prosLabel', 'Pros')}</div>
            <div className="grid" style={{ gridTemplateColumns: `repeat(${items.length}, 1fr)` }}>
              {items.map((item) => (
                <div key={item.id} className="p-2 border-r border-border last:border-r-0 min-w-0">
                  <ul className="space-y-1">
                    {item.pros.slice(0, 3).map((pro, i) => (
                      <li key={i} className="flex items-start gap-1 text-[10px] leading-tight text-left break-words">
                        <Check className="w-2.5 h-2.5 text-green-600 flex-shrink-0 mt-0.5" />
                        <span className="whitespace-normal">{pro}</span>
                      </li>
                    ))}
                  </ul>
                </div>
              ))}
            </div>
          </div>

          {/* Cons Row */}
          <div>
            <div className="px-2 py-1 text-[10px] font-bold text-red-700 uppercase bg-red-50/50">{getText('consLabel', 'Cons')}</div>
            <div className="grid" style={{ gridTemplateColumns: `repeat(${items.length}, 1fr)` }}>
              {items.map((item) => (
                <div key={item.id} className="p-2 border-r border-border last:border-r-0 min-w-0">
                  <ul className="space-y-1">
                    {item.cons.slice(0, 3).map((con, i) => (
                      <li key={i} className="flex items-start gap-1 text-[10px] leading-tight text-left break-words">
                        <X className="w-2.5 h-2.5 text-red-600 flex-shrink-0 mt-0.5" />
                        <span className="whitespace-normal">{con}</span>
                      </li>
                    ))}
                  </ul>
                </div>
              ))}
            </div>
          </div>

          {/* CTA Row */}
          <div className="bg-muted/5">
            <div className="grid" style={{ gridTemplateColumns: `repeat(${items.length}, 1fr)` }}>
              {items.map((item) => (
                <div key={item.id} className="p-2 text-center border-r border-border last:border-r-0">
                  {(item.design_overrides?.show_footer_table !== false) && (
                    <a
                      href={item.details_link || '#'}
                      target="_blank"
                      className="flex w-full items-center justify-center gap-1 text-white transition-all py-1.5 rounded-md font-bold text-[10px] shadow-sm"
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
                      {item.button_text || getText('visitSite', "Visit")} <ExternalLink className="w-3 h-3" />
                    </a>
                  )}
                </div>
              ))}
            </div>
          </div>
        </div>
      </div >
    </div >
  );
};

export default ComparisonTable;
