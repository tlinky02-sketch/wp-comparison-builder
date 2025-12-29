import { Globe, Server, Cloud, Shield, Zap, HardDrive } from "lucide-react";
import CategoryCard from "./CategoryCard";

const categories = [
  {
    icon: Globe,
    title: "Store Building",
    description: "Launch your online shop with easy-to-use builders",
    count: 45,
  },
  {
    icon: Server,
    title: "Dropshipping",
    description: "Start selling without managing inventory",
    count: 32,
  },
  {
    icon: Cloud,
    title: "Enterprise",
    description: "Scalable solutions for high-volume merchants",
    count: 28,
  },
  {
    icon: Shield,
    title: "Security",
    description: "Payment protection and fraud prevention",
    count: 24,
  },
  {
    icon: Zap,
    title: "Marketing",
    description: "SEO, email campaigns, and social media growth",
    count: 38,
  },
  {
    icon: HardDrive,
    title: "Analytics",
    description: "Track sales, inventory, and customer behavior",
    count: 56,
  },
];

const Categories = () => {
  return (
    <section className="py-16 md:py-24 bg-background">
      <div className="container mx-auto px-4">
        <div className="text-center mb-12">
          <h2 className="font-display text-3xl md:text-4xl font-bold text-foreground mb-4">
            Explore by Category
          </h2>
          <p className="text-muted-foreground max-w-2xl mx-auto">
            Find the perfect hosting solution with our comprehensive guides organized by topic
          </p>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {categories.map((category, index) => (
            <div
              key={category.title}
              className="animate-fade-in"
              style={{ animationDelay: `${index * 0.1}s` }}
            >
              <CategoryCard {...category} />
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

export default Categories;
