import React, { useEffect, useState } from 'react';
import PlatformHero from './PlatformHero';
import { ComparisonItem } from './PlatformCard';

interface PlatformHeroWrapperProps {
    itemId: string;
}

const PlatformHeroWrapper: React.FC<PlatformHeroWrapperProps> = ({ itemId }) => {
    const [item, setItem] = useState<ComparisonItem | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            // 1. Try Initial Data
            const settings = window.wpcSettings || window.ecommerceGuiderSettings || window.hostingGuiderSettings;
            if (settings?.initialData) {
                const list = settings.initialData.items || settings.initialData.providers;
                if (list) {
                    const found = list.find((p: any) => p.id == itemId);
                    if (found) {
                        setItem(found);
                        setLoading(false);
                        return;
                    }
                }
            }

            // 2. Fetch API if not found
            try {
                const apiUrl = settings?.apiUrl || '/wp-json/wpc/v1/items';
                const response = await fetch(apiUrl);
                const data = await response.json();
                const list = data.items || data.providers;

                const found = list?.find((p: any) => p.id == itemId);
                if (found) setItem(found);
            } catch (err) {
                console.error("Failed to load hero data", err);
            } finally {
                setLoading(false);
            }
        };

        fetchData();
    }, [itemId]);

    if (loading) return <div className="p-8 animate-pulse bg-muted/20 rounded-xl h-[400px]"></div>;
    if (!item) return <div className="text-red-500">Item not found</div>;

    // We don't need 'onBack' or 'scroll' in standalone embedded mode usually, 
    // unless user wants to scroll to another section on the same page.
    return (
        <PlatformHero
            item={item}
            onBack={() => window.history.back()}
            onScrollToCompare={() => {
                // Try to find ANY comparison table on page
                const root = document.querySelector('.wpc-root') || document.getElementById('ecommerce-guider-root');
                root?.scrollIntoView({ behavior: 'smooth' });
            }}
        />
    );
};

export default PlatformHeroWrapper;
