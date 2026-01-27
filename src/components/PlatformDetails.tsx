import React, { useState, forwardRef } from "react";
import { ComparisonItem } from "./PlatformCard";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { X, Check, Star, ExternalLink, ArrowLeft, BarChart, LayoutDashboard, Search } from "lucide-react";
import { toast } from "sonner";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from "@/components/ui/dropdown-menu";
import { Input } from "@/components/ui/input";

interface PlatformDetailsProps {
    item: ComparisonItem;
    allItems: ComparisonItem[];
    onBack?: () => void;
    hoverColor?: string;
    primaryColor?: string;
    labels?: any;
}

const PlatformDetails = ({ item, allItems, onBack, hoverColor, primaryColor, labels }: PlatformDetailsProps) => {
    const [searchQuery, setSearchQuery] = useState("");
    const [selectedAlts, setSelectedAlts] = useState<ComparisonItem[]>([]);
    const [showWarning, setShowWarning] = useState(false);

    // Generate unique ID for scoping styles
    const uniqueId = "pd-" + Math.random().toString(36).substr(2, 9);

    const filteredItems = Array.isArray(allItems) ? allItems.filter(p =>
        p.id !== item.id &&
        p.name.toLowerCase().includes(searchQuery.toLowerCase())
    ) : [];

    const toggleAlt = (p: ComparisonItem) => {
        if (selectedAlts.some(a => a.id === p.id)) {
            setSelectedAlts(selectedAlts.filter(a => a.id !== p.id));
        } else {
            if (selectedAlts.length >= 3) {
                setShowWarning(true);
                setTimeout(() => setShowWarning(false), 4000);
                return;
            }
            setSelectedAlts([...selectedAlts, p]);
            setShowWarning(false);
        }
    };

    const handleCompare = () => {
        if (selectedAlts.length === 0) return;
        const allIds = [item.id, ...selectedAlts.map(a => a.id)];
        // Use current URL architecture or update later to generic URL
        window.location.href = `${window.location.origin}/hosting-reviews?compare_ids=${allIds.join(',')}`;
    };

    return (
        <div className="min-h-screen bg-background">
            <div className="container mx-auto px-4 py-8">
                {/* Breadcrumb / Back */}
                <div className="mb-8 flex items-center gap-2 text-sm text-muted-foreground">
                    {onBack ? (
                        <button onClick={onBack} className="hover:text-primary flex items-center gap-1">
                            <ArrowLeft className="w-4 h-4" /> Back to Reviews
                        </button>
                    ) : (
                        <a href="/hosting-reviews" className="hover:text-primary flex items-center gap-1">
                            <ArrowLeft className="w-4 h-4" /> Home
                        </a>
                    )}
                    <span>/</span>
                    <span>Items</span>
                    <span>/</span>
                    <span className="text-foreground font-medium">{item.name}</span>
                </div>

                {/* Selection Bar (Multi-select) */}
                {selectedAlts.length > 0 && (
                    <div className="mb-8 p-4 bg-card border border-border rounded-2xl shadow-sm flex flex-col md:flex-row items-center justify-between gap-4 animate-in fade-in slide-in-from-top-4">
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="text-sm font-semibold text-muted-foreground mr-2">{labels?.selected || "Selected:"}</span>
                            <Badge variant="secondary" className="px-3 py-1 bg-primary/10 text-primary border-primary/20">
                                {item.name}
                            </Badge>
                            {selectedAlts.map(alt => (
                                <Badge
                                    key={alt.id}
                                    variant="secondary"
                                    className="px-3 py-1 bg-primary/10 text-primary border-primary/20 flex items-center gap-1"
                                >
                                    {alt.name}
                                    <button onClick={() => toggleAlt(alt)} className="hover:text-destructive">
                                        <X className="w-3 h-3" />
                                    </button>
                                </Badge>
                            ))}
                        </div>
                        <DynamicButton
                            hoverColor={hoverColor}
                            primaryColor={primaryColor}
                            onClick={handleCompare}
                            className="w-full md:w-auto font-bold shadow-lg"
                        >
                            {(labels?.compareNow || "Compare Now")} <BarChart className="w-4 h-4 ml-2" />
                        </DynamicButton>
                    </div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
                    {/* Left Column: Info */}
                    <div className="space-y-8">
                        <div className="flex items-center gap-4">
                            <div className="w-20 h-20 bg-white rounded-xl shadow-sm border p-3 flex items-center justify-center">
                                {item.logo ? (
                                    <img src={item.logo} alt={item.name} className="w-full h-full object-contain" />
                                ) : (
                                    <span className="text-2xl font-bold text-primary">{item.name.charAt(0)}</span>
                                )}
                            </div>
                            <div>
                                <h1 className="text-4xl font-bold mb-2">{item.name}</h1>
                                <div className="flex items-center gap-2">
                                    <div className="flex items-center" style={{ color: (window as any).wpcSettings?.colors?.stars || '#fbbf24' }}>
                                        <Star className="w-5 h-5 fill-current" />
                                        <span className="ml-1 text-foreground font-bold">{item.rating}</span>
                                    </div>
                                    <span className="text-muted-foreground text-sm">{labels?.analysisBase || "(Based on our analysis)"}</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h2 className="text-xl font-semibold mb-3">{(labels?.about || "About {name}").replace('{name}', item.name)}</h2>
                            <p className="text-muted-foreground text-lg leading-relaxed">
                                {item.description || `${item.name} is a leading platform that empowers you to succeed. Designed with powerful features to help you grow.`}
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-4">
                            <Button size="lg" className="px-8" onClick={() => {
                                const settings = (window as any).wpcSettings || (window as any).ecommerceGuiderSettings;
                                const shouldOpenNewTab = settings?.openNewTab !== false;
                                window.open(item.details_link, shouldOpenNewTab ? '_blank' : '_self');
                            }}>
                                {(labels?.visitPlat ? labels.visitPlat.replace('%s', item.name) : `Visit ${item.name}`)} <ExternalLink className="w-4 h-4 ml-2" />
                            </Button>

                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <DynamicButton
                                        hoverColor={hoverColor}
                                        primaryColor={primaryColor}
                                        variant="outline"
                                        size="lg"
                                        className="transition-colors border-primary text-primary"
                                    >
                                        {labels?.compareAlternatives || "Compare Alternatives"}
                                    </DynamicButton>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent className={`w-72 ${uniqueId}`} align="start">
                                    {/* Dynamic Hover Styles */}
                                    {hoverColor && (
                                        <style>
                                            {`
                                            .${uniqueId} [role="menuitem"]:hover, 
                                            .${uniqueId} [role="menuitem"][data-highlighted] {
                                                background-color: ${hoverColor}15 !important;
                                                color: ${hoverColor} !important;
                                            }
                                            .${uniqueId} [role="menuitem"]:hover svg,
                                            .${uniqueId} [role="menuitem"][data-highlighted] svg {
                                                color: ${hoverColor} !important;
                                            }
                                            `}
                                        </style>
                                    )}
                                    <div className="p-3 border-b border-border bg-muted/30">
                                        <DropdownMenuLabel className="px-0 pb-2">Compare {item.name} with...</DropdownMenuLabel>
                                        <div className="relative">
                                            <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                                            <Input
                                                placeholder="Search platform..."
                                                className="pl-8 h-9"
                                                value={searchQuery}
                                                onChange={(e) => setSearchQuery(e.target.value)}
                                            />
                                        </div>
                                    </div>
                                    {showWarning && (
                                        <div className="px-3 py-2 bg-destructive/10 text-destructive text-xs font-semibold border-b border-destructive/20 animate-in fade-in zoom-in-95">
                                            You can select up to 3 alternatives to compare.
                                        </div>
                                    )}
                                    <div className="max-h-[300px] overflow-y-auto p-1">
                                        {filteredItems.length > 0 ? filteredItems.map(p => {
                                            const isSelected = selectedAlts.some(a => a.id === p.id);
                                            return (
                                                <DropdownMenuItem
                                                    key={p.id}
                                                    onClick={(e) => {
                                                        e.preventDefault();
                                                        toggleAlt(p);
                                                    }}
                                                    className={`cursor-pointer flex items-center justify-between py-2.5 px-3 rounded-md transition-colors ${isSelected ? 'bg-primary/10 text-primary' : ''}`}
                                                    style={isSelected && primaryColor ? {
                                                        backgroundColor: `${primaryColor}15`, // ~8% opacity
                                                        color: primaryColor,
                                                    } : undefined}
                                                >
                                                    <span className="font-medium">{p.name}</span>
                                                    {isSelected ? (
                                                        <Check className="w-4 h-4 text-primary font-bold" style={{ color: primaryColor }} />
                                                    ) : (
                                                        <ArrowLeft className="w-4 h-4 opacity-30 rotate-180" />
                                                    )}
                                                </DropdownMenuItem>
                                            );
                                        }) : (
                                            <div className="p-4 text-center text-sm text-muted-foreground">
                                                No matches found
                                            </div>
                                        )}
                                    </div>
                                    {selectedAlts.length > 0 && (
                                        <>
                                            <DropdownMenuSeparator />
                                            <div className="p-2">
                                                <DynamicButton
                                                    hoverColor={hoverColor}
                                                    primaryColor={primaryColor}
                                                    onClick={handleCompare}
                                                    size="sm"
                                                    className="w-full"
                                                >
                                                    {(labels?.compareNow || "Compare Now")} ({selectedAlts.length + 1})
                                                </DynamicButton>
                                            </div>
                                        </>
                                    )}
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>

                        {/* Quick Stats Grid */}
                        <div className="grid grid-cols-2 gap-4 pt-4">
                            <div className="p-4 bg-secondary/10 rounded-xl">
                                <div className="text-sm text-muted-foreground mb-1">{labels?.startingPrice || "Starting Price"}</div>
                                <div className="text-2xl font-bold text-primary">{item.price}</div>
                            </div>
                            <div className="p-4 bg-secondary/10 rounded-xl">
                                <div className="text-sm text-muted-foreground mb-1">{labels?.featureFees || "Transaction Fees"}</div>
                                <div className="text-2xl font-bold text-primary">{item.features?.fees || 'Varies'}</div>
                            </div>
                            <div className="p-4 bg-secondary/10 rounded-xl">
                                <div className="text-sm text-muted-foreground mb-1">{labels?.featureProducts || "Products"}</div>
                                <div className="text-2xl font-bold text-primary">{item.features?.products || 'Unlimited'}</div>
                            </div>
                            <div className="p-4 bg-secondary/10 rounded-xl">
                                <div className="text-sm text-muted-foreground mb-1">{labels?.featureSupport || "Support"}</div>
                                <div className="text-xl font-bold text-primary">{item.features?.support || '24/7'}</div>
                            </div>
                        </div>
                    </div>

                    {/* Right Column: Dashboard Image */}
                    <div className="relative mt-8 lg:mt-0">
                        <div className="absolute -inset-1 bg-gradient-to-r from-primary to-emerald-500 rounded-2xl blur opacity-20"></div>
                        <div className="relative bg-card border border-border rounded-xl overflow-hidden shadow-2xl">
                            {/* Browser Header */}
                            <div className="bg-muted px-4 py-3 border-b border-border flex items-center gap-2">
                                <div className="flex gap-1.5">
                                    <div className="w-3 h-3 rounded-full bg-red-400" />
                                    <div className="w-3 h-3 rounded-full bg-yellow-400" />
                                    <div className="w-3 h-3 rounded-full bg-green-400" />
                                </div>
                                <div className="ml-4 bg-background/50 rounded-md px-3 py-1 text-xs text-muted-foreground flex-1 text-center font-mono">
                                    {item.name.toLowerCase()}.com/dashboard
                                </div>
                            </div>

                            {/* Dashboard Preview (Mock or Real) */}
                            <div className="aspect-[4/3] bg-secondary/5 relative flex items-center justify-center">
                                {item.dashboard_image ? (
                                    <img src={item.dashboard_image} alt="Dashboard" className="w-full h-full object-cover" />
                                ) : (
                                    <div className="text-center p-8">
                                        <LayoutDashboard className="w-16 h-16 text-muted-foreground/30 mx-auto mb-4" />
                                        <p className="text-muted-foreground font-medium">{labels?.dashboardPreview || "Dashboard Preview"}</p>

                                        {/* Mock UI Elements for visuals */}
                                        <div className="mt-8 grid grid-cols-3 gap-4 opacity-30">
                                            <div className="h-24 bg-primary/20 rounded-lg"></div>
                                            <div className="h-24 bg-primary/20 rounded-lg"></div>
                                            <div className="h-24 bg-primary/20 rounded-lg"></div>
                                            <div className="col-span-2 h-32 bg-primary/20 rounded-lg"></div>
                                            <div className="h-32 bg-primary/20 rounded-lg"></div>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

interface DynamicButtonProps extends React.ComponentProps<typeof Button> {
    hoverColor?: string;
    primaryColor?: string;
}

const DynamicButton = forwardRef<HTMLButtonElement, DynamicButtonProps>(
    ({ hoverColor, primaryColor, className, style, variant, onMouseEnter, onMouseLeave, ...props }, ref) => {
        const [isHovered, setIsHovered] = useState(false);
        const isOutline = variant === "outline";

        return (
            <Button
                ref={ref}
                variant={variant}
                className={`transition-colors duration-200 ${className}`}
                onMouseEnter={(e) => {
                    setIsHovered(true);
                    onMouseEnter?.(e);
                }}
                onMouseLeave={(e) => {
                    setIsHovered(false);
                    onMouseLeave?.(e);
                }}
                style={{
                    ...style,
                    ...(isOutline ? {
                        borderColor: isHovered && hoverColor ? hoverColor : (primaryColor || undefined),
                        color: isHovered && hoverColor ? hoverColor : (primaryColor || undefined),
                        backgroundColor: isHovered && hoverColor ? `${hoverColor}10` : undefined
                    } : {
                        backgroundColor: isHovered && hoverColor ? hoverColor : (primaryColor || undefined),
                        borderColor: isHovered && hoverColor ? hoverColor : (primaryColor || undefined),
                        color: 'white'
                    })
                }}
                {...props}
            />
        );
    }
);
DynamicButton.displayName = "DynamicButton";

export default PlatformDetails;
