import React from 'react';
import { cn } from '@/lib/utils'; // Assuming global utils exist
// We might need to handle FontAwesome icons if they are passed as strings like "fa-solid fa-rocket"
// or Lucide icons if passed as names. User said "Icon (FontAwesome Icon)".
// The plugin seems to use FontAwesome globally or Lucide.
// Since the input is a text field class string, we render <i> with that class.

interface UseCaseItem {
    name: string;
    desc: string;
    icon: string;
    image: string;
    icon_color?: string;
}

interface BestUseCasesProps {
    items: UseCaseItem[];
    config?: {
        columns?: number;
    };
    title?: string;
}

const BestUseCases: React.FC<BestUseCasesProps> = ({ items, config, title }) => {
    // Determine grid columns dynamically based on item count, up to the config limit (default 4)
    const maxCols = config?.columns || 4;
    const count = items.length;

    // Responsive logic: 
    // 1 item: grid-cols-1 (always centered/full)
    // 2 items: sm:grid-cols-2
    // 3 items: sm:grid-cols-2 md:grid-cols-3
    // 4+ items: sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4

    let gridClass = "grid-cols-1";
    if (count >= 4) {
        gridClass += " sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4";
    } else if (count === 3) {
        gridClass += " sm:grid-cols-2 md:grid-cols-3";
    } else if (count === 2) {
        gridClass += " sm:grid-cols-2";
    }

    // Get global icon color: custom usecase color > primary color
    const globalIconColor = (window as any).wpcSettings?.colors?.usecaseIcon;

    return (
        <div className="wpc-use-cases w-full">
            {title && <h2 className="text-2xl font-bold mb-6 text-center">{title}</h2>}

            <div className={`grid grid-cols-1 ${gridClass} gap-6`}>
                {Array.isArray(items) && items.map((item, idx) => (
                    <div
                        key={idx}
                        className="bg-card text-card-foreground rounded-xl border border-border bg-white shadow-sm p-6 flex flex-col items-center text-center transition-all hover:shadow-md"
                        // Match PlatformCard style tokens roughly
                        style={{
                            borderColor: (window as any).wpcSettings?.colors?.border || undefined
                        }}
                    >
                        {/* Icon or Image */}
                        <div className="mb-4 flex items-center justify-center w-16 h-16 rounded-full bg-primary/10">
                            {item.image ? (
                                <img src={item.image} alt={item.name} className="w-10 h-10 object-contain" />
                            ) : (
                                item.icon ? (
                                    <i
                                        className={`${item.icon} text-2xl`}
                                        style={{ color: item.icon_color || globalIconColor || `hsl(var(--primary))` }}
                                    ></i>
                                ) : (
                                    // Fallback icon
                                    <span className="text-2xl">✨</span>
                                )
                            )}
                        </div>

                        {/* Content */}
                        <h3 className="text-lg font-bold mb-2 text-foreground">{item.name}</h3>
                        <p className="text-sm text-muted-foreground leading-relaxed">{item.desc}</p>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default BestUseCases;
