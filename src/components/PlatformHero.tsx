
import React, { useState } from 'react';
import { Button } from "@/components/ui/button";
import { Star, ExternalLink, ArrowRight, X, Clock, Calendar, Check, Monitor, Shield, Zap } from "lucide-react";
import StarRating from './StarRating';
import { ComparisonItem } from "./PlatformCard";
import { cn } from "@/lib/utils";

interface PlatformHeroProps {
    item: ComparisonItem;
    onBack?: () => void;
    onScrollToCompare?: () => void;
    hoverColor?: string;
    primaryColor?: string;
}

const PlatformHero: React.FC<PlatformHeroProps> = ({ item, onBack, onScrollToCompare, hoverColor, primaryColor }) => {
    const [isHovered, setIsHovered] = useState(false);

    const handleVisit = () => {
        if (item.details_link) {
            const settings = (window as any).wpcSettings || (window as any).ecommerceGuiderSettings;
            const shouldOpenNewTab = settings?.openNewTab !== false; // Default to true
            window.open(item.details_link, shouldOpenNewTab ? '_blank' : '_self');
        }
    };

    return (
        <div className="mb-12">


            <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">

                {/* Left Column: Content */}
                <div>
                    {/* Logo & Name - Hide if show_hero_logo is false (default true) */}
                    {(item.show_hero_logo !== false) && (
                        <div className="flex items-center gap-4 mb-6">
                            <div className="w-16 h-16 rounded-2xl bg-white p-2 shadow-sm border border-border/50 flex items-center justify-center overflow-hidden">
                                {item.logo ? (
                                    <img src={item.logo} alt={item.name} className="w-full h-full object-contain" />
                                ) : (
                                    <div className="w-8 h-8 bg-primary/20 rounded-full" />
                                )}
                            </div>
                        </div>
                    )}

                    <h1 className="text-4xl md:text-5xl font-display font-bold text-foreground mb-4">
                        {item.name}
                    </h1>

                    {/* Subtitle (Dynamic) */}
                    {item.hero_subtitle && (
                        <p className="text-xl text-muted-foreground mb-6 leading-relaxed">
                            {item.hero_subtitle}
                        </p>
                    )}

                    <div className="prose prose-lg text-muted-foreground mb-8">
                        <p>{item.description}</p>
                    </div>

                    {/* Rating */}
                    <div className="flex items-center gap-2 mb-8 w-fit">
                        {(() => {
                            const starColor = (window as any).wpcSettings?.colors?.stars || '#fbbf24';
                            return <StarRating rating={item.rating || 0} color={starColor} size={20} />;
                        })()}
                        <span className="font-bold text-foreground">{item.rating}/5</span>
                        {item.analysis_label && <span className="text-sm text-muted-foreground">({item.analysis_label})</span>}
                    </div>

                    {/* Actions */}
                    <div className="flex flex-wrap gap-4">
                        <Button size="lg" className="h-12 px-8 text-base shadow-lg shadow-primary/20" onClick={handleVisit}>
                            {((window as any).wpcSettings?.texts?.visit || 'Visit')} {item.name} <ExternalLink className="w-4 h-4 ml-2" />
                        </Button>
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
                                {(window as any).wpcSettings?.texts?.preview || 'Preview'}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
};

export default PlatformHero;
