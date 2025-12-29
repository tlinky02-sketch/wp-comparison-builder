import React from 'react';
import { Button } from "@/components/ui/button";
import { ArrowLeft, Check, ExternalLink, Star } from "lucide-react";
import { ComparisonItem } from "./PlatformCard";
import { cn } from "@/lib/utils";

interface PlatformHeroProps {
    item: ComparisonItem;
    onBack?: () => void;
    onScrollToCompare?: () => void;
}

const PlatformHero: React.FC<PlatformHeroProps> = ({ item, onBack, onScrollToCompare }) => {

    const handleVisit = () => {
        if (item.details_link) {
            window.open(item.details_link, '_blank');
        }
    };

    return (
        <div className="mb-12">
            {/* Breadcrumbs / Back */}
            <div className="flex items-center gap-2 text-sm text-muted-foreground mb-8">
                <button onClick={onBack} className="hover:text-primary flex items-center gap-1 transition-colors">
                    Home
                </button>
                <span>/</span>
                <button onClick={onBack} className="hover:text-primary transition-colors">
                    Reviews
                </button>
                <span>/</span>
                <span className="text-foreground font-medium">{item.name}</span>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">

                {/* Left Column: Content */}
                <div>
                    {/* Logo & Name */}
                    <div className="flex items-center gap-4 mb-6">
                        <div className="w-16 h-16 rounded-2xl bg-white p-2 shadow-sm border border-border/50 flex items-center justify-center overflow-hidden">
                            {item.logo ? (
                                <img src={item.logo} alt={item.name} className="w-full h-full object-contain" />
                            ) : (
                                <div className="w-8 h-8 bg-primary/20 rounded-full" />
                            )}
                        </div>
                    </div>

                    <h1 className="text-4xl md:text-5xl font-display font-bold text-foreground mb-4">
                        {item.name}
                    </h1>

                    <p className="text-xl text-muted-foreground mb-6 leading-relaxed">
                        In-depth review and details
                    </p>

                    <div className="prose prose-lg text-muted-foreground mb-8">
                        <p>{item.description}</p>
                    </div>

                    {/* Rating */}
                    <div className="flex items-center gap-2 mb-8 p-3 bg-amber-50/50 border border-amber-100 rounded-lg w-fit">
                        <div className="flex">
                            {[1, 2, 3, 4, 5].map((s) => (
                                <Star key={s} className={cn("w-5 h-5", s <= Math.round(item.rating) ? "fill-amber-400 text-amber-400" : "fill-muted text-muted/30")} />
                            ))}
                        </div>
                        <span className="font-bold text-foreground">{item.rating}/5</span>
                        <span className="text-sm text-muted-foreground">(Based on our analysis)</span>
                    </div>

                    {/* Actions */}
                    <div className="flex flex-wrap gap-4">
                        <Button size="lg" className="h-12 px-8 text-base shadow-lg shadow-primary/20" onClick={handleVisit}>
                            Visit {item.name} <ExternalLink className="w-4 h-4 ml-2" />
                        </Button>

                        {onScrollToCompare && (
                            <Button variant="outline" size="lg" className="h-12 px-8 text-base bg-background" onClick={onScrollToCompare}>
                                Compare Alternatives
                            </Button>
                        )}
                    </div>
                </div>

                {/* Right Column: Dashboard Image (Optional) */}
                <div className="relative mt-8 lg:mt-0 hidden lg:block">
                    <div className="rounded-xl overflow-hidden border border-border shadow-2xl bg-card rotate-1 hover:rotate-0 transition-transform duration-500">
                        <div className="bg-muted px-4 py-2 border-b border-border flex items-center gap-2">
                            <div className="flex gap-1.5">
                                <div className="w-2.5 h-2.5 rounded-full bg-red-400/50" />
                                <div className="w-2.5 h-2.5 rounded-full bg-amber-400/50" />
                                <div className="w-2.5 h-2.5 rounded-full bg-green-400/50" />
                            </div>
                        </div>
                        {item.dashboard_image ? (
                            <img
                                src={item.dashboard_image}
                                alt={`${item.name} Preview`}
                                className="w-full h-auto object-cover"
                            />
                        ) : (
                            <div className="aspect-[16/10] bg-muted/20 flex items-center justify-center text-muted-foreground">
                                Preview
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
};

export default PlatformHero;
