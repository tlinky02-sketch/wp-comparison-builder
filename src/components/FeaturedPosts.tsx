import BlogCard from "./BlogCard";
import { Button } from "@/components/ui/button";
import { ArrowRight, BookOpen } from "lucide-react";

const posts = [
  {
    image: "https://images.unsplash.com/photo-1556742049-0cfed4f7a07d?w=800&q=80",
    category: "Store Building",
    title: "Best Ecommerce Platforms in 2024: Complete Comparison Guide",
    excerpt: "We tested 20+ platforms to bring you this comprehensive comparison. Find the perfect solution for your online store.",
    readTime: "12 min read",
    featured: true,
  },
  {
    image: "https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&q=80",
    category: "Enterprise",
    title: "Shopify Plus vs BigCommerce Enterprise: Which is Best?",
    excerpt: "Understanding the key differences between enterprise solutions for high-volume merchants.",
    readTime: "8 min read",
    trending: true,
  },
  {
    image: "https://images.unsplash.com/photo-1556740758-90de374c12ad?w=800&q=80",
    category: "Dropshipping",
    title: "Getting Started with Dropshipping: A Beginner's Guide",
    excerpt: "Everything you need to know about setting up your first dropshipping business without inventory.",
    readTime: "10 min read",
  },
  {
    image: "https://images.unsplash.com/photo-1563986768494-4dee2763ff3f?w=800&q=80",
    category: "Security",
    title: "Essential Security Tips for Your One Store",
    excerpt: "Protect your customer data and payments with these proven security practices.",
    readTime: "7 min read",
    trending: true,
  },
  {
    image: "https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=800&q=80",
    category: "Marketing",
    title: "Optimizing Conversion Rates for Your Product Pages",
    excerpt: "Increase sales with these design and copy optimizations for your product listings.",
    readTime: "9 min read",
  },
  {
    image: "https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=800&q=80",
    category: "Analytics",
    title: "How to Track Key Ecommerce Metrics",
    excerpt: "Understanding LTV, CAC, and AOV to grow your business profitably.",
    readTime: "6 min read",
  },
];

const FeaturedPosts = () => {
  return (
    <section className="py-20 md:py-28 bg-gradient-to-b from-background to-muted/50">
      <div className="container mx-auto px-4">
        {/* Section Header */}
        <div className="flex flex-col md:flex-row md:items-end justify-between mb-14 gap-6">
          <div className="space-y-4">
            <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary/10 text-primary text-sm font-medium">
              <BookOpen className="w-4 h-4" />
              Latest from the Blog
            </div>
            <h2 className="font-display text-3xl md:text-5xl font-bold text-foreground leading-tight">
              Seller Guides &<br />
              <span className="text-primary">Expert Insights</span>
            </h2>
            <p className="text-muted-foreground max-w-lg text-lg">
              In-depth reviews, tutorials, and comparisons to help you make informed business decisions.
            </p>
          </div>
          <Button variant="outline" className="group self-start md:self-auto">
            Browse All Articles
            <ArrowRight className="w-4 h-4 ml-2 transition-transform group-hover:translate-x-1" />
          </Button>
        </div>

        {/* Featured + Grid Layout */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
          {/* Featured Post - Large */}
          <div className="lg:col-span-7 animate-fade-in">
            <BlogCard {...posts[0]} featured />
          </div>

          {/* Side Stack */}
          <div className="lg:col-span-5 grid gap-6">
            {posts.slice(1, 3).map((post, index) => (
              <div
                key={post.title}
                className="animate-fade-in"
                style={{ animationDelay: `${(index + 1) * 0.1}s` }}
              >
                <BlogCard {...post} />
              </div>
            ))}
          </div>
        </div>

        {/* Bottom Grid */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
          {posts.slice(3).map((post, index) => (
            <div
              key={post.title}
              className="animate-fade-in"
              style={{ animationDelay: `${(index + 3) * 0.1}s` }}
            >
              <BlogCard {...post} />
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

export default FeaturedPosts;
