import { useState, useMemo } from "react";
import Header from "@/components/Header";
import Footer from "@/components/Footer";
import HostingCard, { HostingProvider } from "@/components/HostingCard";
import ComparisonTable from "@/components/ComparisonTable";
import ComparisonFilters from "@/components/ComparisonFilters";
import { hostingProviders, categories, filterableFeatures } from "@/data/hostingProviders";
import { Button } from "@/components/ui/button";
import { ArrowDown, X } from "lucide-react";
import { toast } from "@/hooks/use-toast";

const Compare = () => {
  const [selectedProviders, setSelectedProviders] = useState<string[]>([]);
  const [selectedCategories, setSelectedCategories] = useState<string[]>([]);
  const [selectedFeatures, setSelectedFeatures] = useState<string[]>([]);
  const [showComparison, setShowComparison] = useState(false);

  const MAX_COMPARE = 4;

  const filteredProviders = useMemo(() => {
    return hostingProviders.filter((provider) => {
      // Category filter
      if (selectedCategories.length > 0) {
        const hasCategory = selectedCategories.some((cat) => provider.category.includes(cat));
        if (!hasCategory) return false;
      }

      // Feature filter
      if (selectedFeatures.length > 0) {
        for (const feature of selectedFeatures) {
          switch (feature) {
            case "Free SSL":
              if (!provider.features.ssl) return false;
              break;
            case "Free Domain":
              if (provider.features.domains === "0") return false;
              break;
            case "Daily Backups":
              if (!provider.features.backups) return false;
              break;
            case "Unlimited Bandwidth":
              if (!provider.features.bandwidth.toLowerCase().includes("unlimit") &&
                !provider.features.bandwidth.toLowerCase().includes("unmeter")) return false;
              break;
            case "24/7 Support":
              if (!provider.features.support.includes("24/7")) return false;
              break;
            case "Email Hosting":
              if (provider.features.email === "0") return false;
              break;
          }
        }
      }

      return true;
    });
  }, [selectedCategories, selectedFeatures]);

  const handleSelectProvider = (id: string) => {
    if (selectedProviders.includes(id)) {
      setSelectedProviders((prev) => prev.filter((p) => p !== id));
    } else {
      if (selectedProviders.length >= MAX_COMPARE) {
        toast({
          title: "Maximum reached",
          description: `You can compare up to ${MAX_COMPARE} providers at once.`,
          variant: "destructive",
        });
        return;
      }
      setSelectedProviders((prev) => [...prev, id]);
    }
  };

  const handleCategoryChange = (category: string) => {
    setSelectedCategories((prev) =>
      prev.includes(category) ? prev.filter((c) => c !== category) : [...prev, category]
    );
  };

  const handleFeatureChange = (feature: string) => {
    setSelectedFeatures((prev) =>
      prev.includes(feature) ? prev.filter((f) => f !== feature) : [...prev, feature]
    );
  };

  const handleClearFilters = () => {
    setSelectedCategories([]);
    setSelectedFeatures([]);
  };

  const handleRemoveFromComparison = (id: string) => {
    setSelectedProviders((prev) => prev.filter((p) => p !== id));
  };

  const selectedProviderObjects = hostingProviders.filter((p) =>
    selectedProviders.includes(p.id)
  );

  return (
    <div className="min-h-screen bg-background">
      <Header />

      <main>
        {/* Hero */}
        <section className="bg-hero py-16 md:py-20">
          <div className="container mx-auto px-4 text-center">
            <h1 className="font-display text-3xl md:text-5xl font-extrabold text-primary-foreground mb-4">
              Compare Hosting Providers
            </h1>
            <p className="text-lg text-primary-foreground/70 max-w-2xl mx-auto">
              Select up to 4 hosting providers to compare features, pricing, and performance side by side
            </p>
          </div>
        </section>

        {/* Selected Providers Bar */}
        {selectedProviders.length > 0 && (
          <div className="sticky top-16 z-40 bg-card border-b border-border shadow-sm">
            <div className="container mx-auto px-4 py-4">
              <div className="flex items-center justify-between flex-wrap gap-4">
                <div className="flex items-center gap-2 flex-wrap">
                  <span className="text-sm font-medium text-muted-foreground">
                    Selected ({selectedProviders.length}/{MAX_COMPARE}):
                  </span>
                  {selectedProviderObjects.map((provider) => (
                    <div
                      key={provider.id}
                      className="flex items-center gap-2 px-3 py-1.5 bg-primary/10 rounded-full"
                    >
                      <img src={provider.logo} alt={provider.name} className="w-5 h-5 rounded-full" />
                      <span className="text-sm font-medium text-foreground">{provider.name}</span>
                      <button
                        onClick={() => handleRemoveFromComparison(provider.id)}
                        className="hover:text-destructive transition-colors"
                      >
                        <X className="w-4 h-4" />
                      </button>
                    </div>
                  ))}
                </div>
                <Button
                  onClick={() => setShowComparison(true)}
                  className="gap-2"
                  disabled={selectedProviders.length < 2}
                >
                  Compare Now <ArrowDown className="w-4 h-4" />
                </Button>
              </div>
            </div>
          </div>
        )}

        {/* Main Content */}
        <section className="py-12 md:py-16">
          <div className="container mx-auto px-4">
            {/* Filters - Top Aligned */}
            <div className="mb-8 p-4 bg-card rounded-xl border border-border shadow-sm">
              <ComparisonFilters
                categories={categories}
                features={filterableFeatures}
                selectedCategories={selectedCategories}
                selectedFeatures={selectedFeatures}
                onCategoryChange={handleCategoryChange}
                onFeatureChange={handleFeatureChange}
                onClearFilters={handleClearFilters}
              />
            </div>

            <div className="flex flex-col lg:flex-row gap-8">
              {/* Providers Grid - Full Width now */}
              <div className="flex-1">
                <div className="mb-6 flex items-center justify-between">
                  <p className="text-muted-foreground">
                    Showing {filteredProviders.length} of {hostingProviders.length} platforms
                  </p>
                </div>

                {filteredProviders.length === 0 ? (
                  <div className="bg-card rounded-2xl border border-border p-12 text-center">
                    <p className="text-muted-foreground mb-4">No platforms match your filters</p>
                    <Button variant="outline" onClick={handleClearFilters}>
                      Clear Filters
                    </Button>
                  </div>
                ) : (
                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    {filteredProviders.map((provider, index) => (
                      <div
                        key={provider.id}
                        className="animate-fade-in"
                        style={{ animationDelay: `${index * 0.05}s` }}
                      >
                        <HostingCard
                          provider={provider}
                          isSelected={selectedProviders.includes(provider.id)}
                          onSelect={handleSelectProvider}
                          disabled={
                            selectedProviders.length >= MAX_COMPARE &&
                            !selectedProviders.includes(provider.id)
                          }
                        />
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>
          </div>
        </section>

        {/* Comparison Table Section */}
        {showComparison && selectedProviders.length >= 2 && (
          <section id="comparison" className="py-12 md:py-16 bg-muted">
            <div className="container mx-auto px-4">
              <div className="flex items-center justify-between mb-8">
                <h2 className="font-display text-2xl md:text-3xl font-bold text-foreground">
                  Head-to-Head Comparison
                </h2>
                <Button variant="outline" onClick={() => setShowComparison(false)}>
                  Hide Comparison
                </Button>
              </div>
              <ComparisonTable
                providers={selectedProviderObjects}
                onRemove={handleRemoveFromComparison}
              />
            </div>
          </section>
        )}
      </main>

      <Footer />
    </div>
  );
};

export default Compare;
