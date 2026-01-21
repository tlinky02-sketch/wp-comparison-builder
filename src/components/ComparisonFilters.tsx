import { Check, Filter, PlusCircle } from "lucide-react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";

interface ComparisonFiltersProps {
  categories: string[];
  features: string[];
  selectedCategories: string[];
  selectedFeatures: string[];
  onCategoryChange: (category: string) => void;
  onFeatureChange: (feature: string) => void;
  onClearFilters: () => void;
  layout?: 'top' | 'sidebar';
  labels?: {
    categories?: string;
    features?: string;
    filters?: string;
    resetFilters?: string;
    select?: string;
    clear?: string;
  };
}

const ComparisonFilters = ({
  categories,
  features,
  selectedCategories,
  selectedFeatures,
  onCategoryChange,
  onFeatureChange,
  onClearFilters,
  layout = 'top',
  labels,
}: ComparisonFiltersProps) => {
  const hasFilters = selectedCategories.length > 0 || selectedFeatures.length > 0;

  const catLabel = labels?.categories || (layout === 'top' ? 'Category' : 'Categories');
  const featLabel = labels?.features || (layout === 'top' ? 'Platform Features' : 'Features');

  // Dynamic Text Colors from Global Settings
  const globalColors = (window as any).wpcSettings?.colors || {};
  const headingColor = globalColors.textHeading;
  const bodyColor = globalColors.textBody;
  const mutedColor = globalColors.textMuted;

  if (layout === 'sidebar') {
    return (
      <div className="space-y-2 py-2">
        <div>
          <div className="flex items-center gap-2 mb-2 pb-2 border-b border-border">
            <Filter className="w-5 h-5 text-muted-foreground" style={{ color: mutedColor }} />
            <span className="font-display font-bold text-lg text-foreground" style={{ color: headingColor }}>{labels?.filters || "Filters"}</span>
          </div>
          {hasFilters && (
            <Button
              variant="link"
              onClick={onClearFilters}
              className="px-0 text-xs text-muted-foreground hover:text-primary h-auto mt-1 mb-1 block"
              style={{ color: mutedColor }}
            >
              {labels?.resetFilters || "Reset Filters"}
            </Button>
          )}
        </div>

        {/* Category Filter */}
        <div className="space-y-3 pt-2">
          <h4 className="text-sm font-bold text-foreground uppercase tracking-wider" style={{ color: headingColor }}>{labels?.categories || 'Categories'}</h4>
          <div className="space-y-2 max-h-[35vh] overflow-y-auto pr-2 custom-scrollbar">
            {categories.map((category) => {
              const isSelected = selectedCategories.includes(category);
              return (
                <div
                  key={category}
                  onClick={() => onCategoryChange(category)}
                  className="flex items-center gap-3 cursor-pointer group"
                >
                  <div className={cn(
                    "flex h-4 w-4 shrink-0 items-center justify-center rounded border border-primary transition-colors",
                    isSelected ? "bg-primary text-primary-foreground" : "bg-background border-border group-hover:border-primary/50"
                  )}
                    style={isSelected ? { backgroundColor: globalColors.primary, borderColor: globalColors.primary } : {}}
                  >
                    <Check className={cn("h-3 w-3", isSelected ? "opacity-100" : "opacity-0")} />
                  </div>
                  <span className={cn(
                    "text-sm transition-colors",
                    isSelected ? "text-foreground font-medium" : "text-muted-foreground group-hover:text-foreground"
                  )}
                    style={{ color: isSelected ? bodyColor : mutedColor }}
                  >
                    {category}
                  </span>
                </div>
              );
            })}
          </div>
        </div>

        {/* Features - Sidebar */}
        <div className="space-y-3 pt-2">
          <h4 className="text-sm font-bold text-foreground uppercase tracking-wider" style={{ color: headingColor }}>{labels?.features || 'Features'}</h4>
          <div className="space-y-2 max-h-[35vh] overflow-y-auto pr-2 custom-scrollbar">
            {features.map((feature) => {
              const isSelected = selectedFeatures.includes(feature);
              return (
                <div
                  key={feature}
                  onClick={() => onFeatureChange(feature)}
                  className="flex items-center gap-3 cursor-pointer group"
                >
                  <div className={cn(
                    "flex h-4 w-4 shrink-0 items-center justify-center rounded border border-primary transition-colors",
                    isSelected ? "bg-primary text-primary-foreground" : "bg-background border-border group-hover:border-primary/50"
                  )}
                    style={isSelected ? { backgroundColor: globalColors.primary, borderColor: globalColors.primary } : {}}
                  >
                    <Check className={cn("h-3 w-3", isSelected ? "opacity-100" : "opacity-0")} />
                  </div>
                  <span className={cn(
                    "text-sm transition-colors",
                    isSelected ? "text-foreground font-medium" : "text-muted-foreground group-hover:text-foreground"
                  )}
                    style={{ color: isSelected ? bodyColor : mutedColor }}
                  >
                    {feature}
                  </span>
                </div>
              );
            })}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="flex flex-wrap items-center gap-2">
      <div className="flex items-center gap-2 mr-2">
        <Filter className="w-5 h-5 text-muted-foreground" style={{ color: mutedColor }} />
        <span className="font-display font-bold text-lg text-foreground" style={{ color: headingColor }}>{labels?.filters || "Filters"}</span>
      </div>

      {/* Category Filter */}
      <Popover>
        <PopoverTrigger asChild>
          <Button variant="outline" size="sm" className="h-9 border-dashed bg-transparent hover:bg-muted/50" style={{ color: bodyColor || mutedColor }}>
            <PlusCircle className="mr-2 h-4 w-4" />
            {catLabel}
            {selectedCategories.length > 0 && (
              <>
                <Separator orientation="vertical" className="mx-2 h-4" />
                <Badge variant="secondary" className="rounded-sm px-1 font-normal lg:hidden">
                  {selectedCategories.length}
                </Badge>
                <div className="hidden space-x-1 lg:flex">
                  {selectedCategories.length > 2 ? (
                    <Badge variant="secondary" className="rounded-sm px-1 font-normal">
                      {selectedCategories.length} selected
                    </Badge>
                  ) : (
                    selectedCategories.map((option) => (
                      <Badge
                        variant="secondary"
                        key={option}
                        className="rounded-sm px-1 font-normal"
                      >
                        {option}
                      </Badge>
                    ))
                  )}
                </div>
              </>
            )}
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-[240px] p-0" align="start">
          <div className="p-2 space-y-1">
            <h4 className="px-2 py-1.5 text-sm font-semibold text-muted-foreground">{(labels?.select || "Select %s").replace('%s', catLabel)}</h4>
            <Separator className="my-1" />
            <div className="max-h-[300px] overflow-y-auto pr-1">
              {categories.map((category) => {
                const isSelected = selectedCategories.includes(category);
                return (
                  <div
                    key={category}
                    onClick={() => onCategoryChange(category)}
                    className={cn(
                      "relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none transition-colors hover:bg-accent hover:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50",
                      isSelected ? "bg-accent/10" : ""
                    )}
                  >
                    <div className={cn(
                      "mr-2 flex h-4 w-4 items-center justify-center rounded-sm border border-primary",
                      isSelected ? "bg-primary text-primary-foreground" : "opacity-50 [&_svg]:invisible"
                    )}
                      style={isSelected ? { backgroundColor: globalColors.primary, borderColor: globalColors.primary } : {}}
                    >
                      <Check className={cn("h-3 w-3")} />
                    </div>
                    <span style={{ color: isSelected ? bodyColor : mutedColor }}>{category}</span>
                  </div>
                );
              })}
            </div>
          </div>
          {selectedCategories.length > 0 && (
            <>
              <Separator />
              <div className="p-2">
                <Button
                  variant="ghost"
                  className="w-full justify-center text-xs h-8"
                  onClick={() => {
                    // We need a clear ONLY categories logic?
                    // For now user can click items to toggle. Use Clear All main button for global clear.
                    categories.forEach(c => {
                      if (selectedCategories.includes(c)) onCategoryChange(c); // Toggle off
                    });
                  }}
                  style={{ color: mutedColor }}
                >
                  {labels?.clear || "Clear"} {catLabel}
                </Button>
              </div>
            </>
          )}
        </PopoverContent>
      </Popover>

      {/* Features Filter */}
      <Popover>
        <PopoverTrigger asChild>
          <Button variant="outline" size="sm" className="h-9 border-dashed bg-transparent hover:bg-muted/50" style={{ color: bodyColor || mutedColor }}>
            <PlusCircle className="mr-2 h-4 w-4" />
            {featLabel}
            {selectedFeatures.length > 0 && (
              <>
                <Separator orientation="vertical" className="mx-2 h-4" />
                <Badge variant="secondary" className="rounded-sm px-1 font-normal lg:hidden">
                  {selectedFeatures.length}
                </Badge>
                <div className="hidden space-x-1 lg:flex">
                  {selectedFeatures.length > 2 ? (
                    <Badge variant="secondary" className="rounded-sm px-1 font-normal">
                      {selectedFeatures.length} selected
                    </Badge>
                  ) : (
                    selectedFeatures.map((option) => (
                      <Badge
                        variant="secondary"
                        key={option}
                        className="rounded-sm px-1 font-normal"
                      >
                        {option}
                      </Badge>
                    ))
                  )}
                </div>
              </>
            )}
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-[280px] p-0" align="start">
          <div className="p-2 space-y-1">
            <h4 className="px-2 py-1.5 text-sm font-semibold text-muted-foreground">{(labels?.select || "Select %s").replace('%s', featLabel)}</h4>
            <Separator className="my-1" />
            <div className="max-h-[300px] overflow-y-auto pr-1">
              {features.map((feature) => {
                const isSelected = selectedFeatures.includes(feature);
                return (
                  <div
                    key={feature}
                    onClick={() => onFeatureChange(feature)}
                    className={cn(
                      "relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none transition-colors hover:bg-accent hover:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50",
                      isSelected ? "bg-accent/10" : ""
                    )}
                  >
                    <div className={cn(
                      "mr-2 flex h-4 w-4 items-center justify-center rounded-sm border border-primary",
                      isSelected ? "bg-primary text-primary-foreground" : "opacity-50 [&_svg]:invisible"
                    )}
                      style={isSelected ? { backgroundColor: globalColors.primary, borderColor: globalColors.primary } : {}}
                    >
                      <Check className={cn("h-3 w-3")} />
                    </div>
                    <span style={{ color: isSelected ? bodyColor : mutedColor }}>{feature}</span>
                  </div>
                );
              })}
            </div>
          </div>
          {selectedFeatures.length > 0 && (
            <>
              <Separator />
              <div className="p-2">
                <Button
                  variant="ghost"
                  className="w-full justify-center text-xs h-8"
                  onClick={() => {
                    features.forEach(f => {
                      if (selectedFeatures.includes(f)) onFeatureChange(f);
                    });
                  }}
                >
                  {labels?.clear || "Clear"} {featLabel}
                </Button>
              </div>
            </>
          )}
        </PopoverContent>
      </Popover>

      {/* Clear All Button */}
      {hasFilters && (
        <Button
          variant="ghost"
          onClick={onClearFilters}
          className="h-9 px-2 lg:px-3 text-muted-foreground hover:text-foreground"
        >
          {labels?.resetFilters || "Reset Filters"}
        </Button>
      )}
    </div>
  );
};

export default ComparisonFilters;
