import { LucideIcon } from "lucide-react";

interface CategoryCardProps {
  icon: LucideIcon;
  title: string;
  description: string;
  count: number;
}

const CategoryCard = ({ icon: Icon, title, description, count }: CategoryCardProps) => {
  return (
    <div className="group p-6 bg-card rounded-2xl shadow-card hover:shadow-card-hover transition-all duration-300 cursor-pointer border border-border hover:border-primary/30">
      <div className="w-14 h-14 rounded-xl bg-primary/10 flex items-center justify-center mb-4 group-hover:bg-primary/20 transition-colors">
        <Icon className="w-7 h-7 text-primary" />
      </div>
      <h3 className="font-display font-bold text-lg text-foreground mb-2">{title}</h3>
      <p className="text-sm text-muted-foreground mb-4">{description}</p>
      <span className="text-xs font-medium text-primary">{count} articles</span>
    </div>
  );
};

export default CategoryCard;
