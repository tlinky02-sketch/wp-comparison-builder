import { Clock, ArrowRight, TrendingUp } from "lucide-react";

interface BlogCardProps {
  image: string;
  category: string;
  title: string;
  excerpt: string;
  readTime: string;
  featured?: boolean;
  trending?: boolean;
}

const BlogCard = ({ image, category, title, excerpt, readTime, featured = false, trending = false }: BlogCardProps) => {
  if (featured) {
    return (
      <article className="group relative overflow-hidden rounded-3xl bg-card cursor-pointer h-full min-h-[480px]">
        <img
          src={image}
          alt={title}
          className="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-secondary via-secondary/60 to-transparent" />
        <div className="absolute inset-0 bg-gradient-to-br from-primary/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500" />

        <div className="absolute top-6 left-6 flex items-center gap-2">
          <span className="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-primary text-primary-foreground text-xs font-bold uppercase tracking-wider">
            <TrendingUp className="w-3.5 h-3.5" />
            Featured
          </span>
        </div>

        <div className="absolute bottom-0 left-0 right-0 p-8">
          <span className="inline-block px-3 py-1 rounded-full bg-white/10 backdrop-blur-sm text-white/90 text-xs font-medium mb-4 border border-white/20">
            {category}
          </span>
          <h3 className="font-display text-2xl md:text-3xl font-bold text-white mb-3 group-hover:text-emerald-light transition-colors leading-tight">
            {title}
          </h3>
          <p className="text-white/70 text-base mb-6 line-clamp-2 max-w-xl">{excerpt}</p>
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2 text-white/60 text-sm">
              <Clock className="w-4 h-4" />
              {readTime}
            </div>
            <div className="flex items-center gap-2 text-emerald text-sm font-semibold group-hover:gap-3 transition-all bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full border border-white/20">
              Read Article <ArrowRight className="w-4 h-4" />
            </div>
          </div>
        </div>
      </article>
    );
  }

  return (
    <article className="group bg-card rounded-2xl overflow-hidden cursor-pointer border border-border/50 hover:border-primary/40 transition-all duration-300 hover:shadow-xl hover:shadow-primary/5 hover:-translate-y-1">
      <div className="relative aspect-[16/9] overflow-hidden">
        <img
          src={image}
          alt={title}
          className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-card/80 to-transparent opacity-0 group-hover:opacity-100 transition-opacity" />
        {trending && (
          <span className="absolute top-3 right-3 inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-accent text-accent-foreground text-xs font-semibold">
            <TrendingUp className="w-3 h-3" />
            Trending
          </span>
        )}
      </div>
      <div className="p-5">
        <div className="flex items-center gap-3 mb-3">
          <span className="inline-block px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-semibold">
            {category}
          </span>
          <div className="flex items-center gap-1.5 text-muted-foreground text-xs">
            <Clock className="w-3.5 h-3.5" />
            {readTime}
          </div>
        </div>
        <h3 className="font-display text-lg font-bold text-foreground mb-2 group-hover:text-primary transition-colors line-clamp-2 leading-snug">
          {title}
        </h3>
        <p className="text-muted-foreground text-sm line-clamp-2 mb-4">{excerpt}</p>
        <div className="flex items-center gap-1.5 text-primary text-sm font-medium">
          <span className="group-hover:underline">Read More</span>
          <ArrowRight className="w-4 h-4 transition-transform group-hover:translate-x-1" />
        </div>
      </div>
    </article>
  );
};

export default BlogCard;
