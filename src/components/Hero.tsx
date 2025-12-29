import { Search } from "lucide-react";
import { Button } from "@/components/ui/button";

const Hero = () => {
  return (
    <section className="relative bg-hero overflow-hidden">
      {/* Background Pattern */}
      <div className="absolute inset-0 opacity-10">
        <div className="absolute top-20 left-10 w-72 h-72 bg-emerald rounded-full blur-3xl" />
        <div className="absolute bottom-10 right-10 w-96 h-96 bg-emerald-light rounded-full blur-3xl" />
      </div>

      <div className="container mx-auto px-4 py-20 md:py-28 relative z-10">
        <div className="max-w-3xl mx-auto text-center">
          {/* Badge */}
          <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary/20 text-primary-foreground/90 text-sm font-medium mb-6 animate-fade-in">
            <span className="w-2 h-2 rounded-full bg-emerald animate-pulse" />
            Your Trusted Ecommerce Resource
          </div>

          {/* Headline */}
          <h1 className="font-display text-4xl md:text-5xl lg:text-6xl font-extrabold text-primary-foreground mb-6 leading-tight animate-fade-in" style={{ animationDelay: '0.1s' }}>
            Find the Perfect{" "}
            <span className="text-gradient">Ecommerce Platform</span>{" "}
            for Your Business
          </h1>

          {/* Subheadline */}
          <p className="text-lg md:text-xl text-primary-foreground/70 mb-10 max-w-2xl mx-auto animate-fade-in" style={{ animationDelay: '0.2s' }}>
            Expert reviews, comparisons, and guides to help you choose and grow. From startups to enterprise.
          </p>

          {/* Search Bar */}
          <div className="max-w-xl mx-auto animate-fade-in" style={{ animationDelay: '0.3s' }}>
            <div className="relative flex items-center">
              <Search className="absolute left-4 w-5 h-5 text-muted-foreground" />
              <input
                type="text"
                placeholder="Search platforms, reviews, features..."
                className="w-full h-14 pl-12 pr-32 rounded-xl bg-card text-foreground placeholder:text-muted-foreground border-0 shadow-lg focus:outline-none focus:ring-2 focus:ring-primary"
              />
              <Button className="absolute right-2" size="sm">
                Search
              </Button>
            </div>
          </div>

          {/* Stats */}
          <div className="flex flex-wrap justify-center gap-8 md:gap-12 mt-12 animate-fade-in" style={{ animationDelay: '0.4s' }}>
            <div className="text-center">
              <div className="text-3xl font-display font-bold text-primary-foreground">50+</div>
              <div className="text-sm text-primary-foreground/60">Platform Reviews</div>
            </div>
            <div className="text-center">
              <div className="text-3xl font-display font-bold text-primary-foreground">20K+</div>
              <div className="text-sm text-primary-foreground/60">Monthly Readers</div>
            </div>
            <div className="text-center">
              <div className="text-3xl font-display font-bold text-primary-foreground">Expert</div>
              <div className="text-sm text-primary-foreground/60">Analysis</div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default Hero;
