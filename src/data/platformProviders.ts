import { ComparisonItem } from "@/components/PlatformCard";

export const comparisonItems: ComparisonItem[] = [
    {
        id: "shopify",
        name: "Shopify",
        logo: "https://cdn.iconscout.com/icon/free/png-256/free-shopify-2-226578.png", // Using a generic placeholder or I should leave it empty if I don't have good URLs. I'll use placeholders.
        rating: 4.8,
        category: ["Ecommerce", "Hosted", "Dropshipping"],
        price: "$29",
        period: "month",
        features: {
            products: "Unlimited",
            fees: "2.0%",
            ssl: true,
            support: "24/7 Chat/Phone",
            channels: "Multi-channel",
        },
        pros: ["Best-in-class e-commerce features", "Huge app ecosystem", "Easy to use"],
        cons: ["Transaction fees", "Limited customization without code", "Content marketing weak"],
    },
    {
        id: "woocommerce",
        name: "WooCommerce",
        logo: "https://cdn.iconscout.com/icon/free/png-256/free-woocommerce-3629161-3030318.png",
        rating: 4.6,
        category: ["Ecommerce", "WordPress", "Open Source"],
        price: "Free",
        period: "forever",
        features: {
            products: "Unlimited",
            fees: "0%",
            ssl: false, // Usually requires host
            support: "Community",
            channels: "Unlimited",
        },
        pros: ["Complete control", "Runs on WordPress", "Thousands of plugins"],
        cons: ["Requires hosting", "Security is your responsibility", "Learning curve"],
    },
    {
        id: "bigcommerce",
        name: "BigCommerce",
        logo: "https://upload.wikimedia.org/wikipedia/commons/thumb/6/65/BigCommerce_icon.svg/1024px-BigCommerce_icon.svg.png",
        rating: 4.7,
        category: ["Ecommerce", "Hosted", "Enterprise"],
        price: "$29.95",
        period: "month",
        features: {
            products: "Unlimited",
            fees: "0%",
            ssl: true,
            support: "24/7 Expert",
            channels: "Multi-channel",
        },
        pros: ["Zero transaction fees", "Strong SEO features", "Scales well"],
        cons: ["Annual sales limits", "Theme customization hard", "Smaller app store"],
    },
    {
        id: "wix-ecommerce",
        name: "Wix Ecommerce",
        logo: "https://upload.wikimedia.org/wikipedia/commons/thumb/7/76/Wix.com_website_logo.svg/1200px-Wix.com_website_logo.svg.png",
        rating: 4.5,
        category: ["Ecommerce", "Builder"],
        price: "$27",
        period: "month",
        features: {
            products: "50,000",
            fees: "0%",
            ssl: true,
            support: "24/7",
            channels: "Social Media",
        },
        pros: ["Drag-and-drop builder", "Beginner friendly", "All-in-one solution"],
        cons: ["Storage limits", "Hard to migrate away", "Less powerful backend"],
    },
    {
        id: "squarespace",
        name: "Squarespace",
        logo: "https://upload.wikimedia.org/wikipedia/commons/thumb/4/4b/Squarespace_logo.svg/2560px-Squarespace_logo.svg.png",
        rating: 4.4,
        category: ["Ecommerce", "Builder", "Design"],
        price: "$23",
        period: "month",
        features: {
            products: "Unlimited",
            fees: "3%",
            ssl: true,
            support: "24/7 Email",
            channels: "Limited",
        },
        pros: ["Beautiful templates", "Easy inventory management", "Strong blogging"],
        cons: ["Transaction fees on low tier", "Limited extensions", "Payment gateways limited"],
    },
];

export const categories = [
    "Ecommerce",
    "WordPress",
    "Hosted",
    "Enterprise",
    "Dropshipping",
    "Open Source",
    "Builder",
];

export const filterableFeatures = [
    "Unlimited Products",
    "Zero Transaction Fees",
    "24/7 Support",
    "Multi-channel Selling",
    "Free SSL",
];
