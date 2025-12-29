import { Link } from "react-router-dom";
import { Server, Twitter, Youtube, Linkedin } from "lucide-react";

const Footer = () => {
  const footerLinks = {
    hosting: [
      { name: "Web Hosting", href: "#" },
      { name: "VPS Hosting", href: "#" },
      { name: "Cloud Hosting", href: "#" },
      { name: "WordPress Hosting", href: "#" },
    ],
    resources: [
      { name: "Blog", href: "#" },
      { name: "Tutorials", href: "#" },
      { name: "Reviews", href: "#" },
      { name: "Comparisons", href: "#" },
    ],
    company: [
      { name: "About Us", href: "#" },
      { name: "Contact", href: "#" },
      { name: "Privacy Policy", href: "#" },
      { name: "Terms of Service", href: "#" },
    ],
  };

  return (
    <footer className="bg-secondary text-secondary-foreground">
      <div className="container mx-auto px-4 py-12 md:py-16">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 mb-12">
          {/* Brand */}
          <div className="lg:col-span-2">
            <Link to="/" className="flex items-center gap-2 mb-4">
              <div className="w-10 h-10 rounded-xl bg-primary flex items-center justify-center">
                <Server className="w-5 h-5 text-primary-foreground" />
              </div>
              <span className="font-display font-bold text-xl text-secondary-foreground">
                Hosting<span className="text-primary">Guider</span>
              </span>
            </Link>
            <p className="text-secondary-foreground/70 mb-6 max-w-sm">
              Your trusted source for hosting reviews, tutorials, and guides. Helping you make informed decisions since 2019.
            </p>
            <div className="flex gap-4">
              <a href="#" className="w-10 h-10 rounded-lg bg-secondary-foreground/10 flex items-center justify-center hover:bg-primary/20 transition-colors">
                <Twitter className="w-5 h-5 text-secondary-foreground/70" />
              </a>
              <a href="#" className="w-10 h-10 rounded-lg bg-secondary-foreground/10 flex items-center justify-center hover:bg-primary/20 transition-colors">
                <Youtube className="w-5 h-5 text-secondary-foreground/70" />
              </a>
              <a href="#" className="w-10 h-10 rounded-lg bg-secondary-foreground/10 flex items-center justify-center hover:bg-primary/20 transition-colors">
                <Linkedin className="w-5 h-5 text-secondary-foreground/70" />
              </a>
            </div>
          </div>

          {/* Hosting */}
          <div>
            <h4 className="font-display font-semibold text-secondary-foreground mb-4">Hosting</h4>
            <ul className="space-y-3">
              {footerLinks.hosting.map((link) => (
                <li key={link.name}>
                  <a href={link.href} className="text-secondary-foreground/70 hover:text-primary transition-colors text-sm">
                    {link.name}
                  </a>
                </li>
              ))}
            </ul>
          </div>

          {/* Resources */}
          <div>
            <h4 className="font-display font-semibold text-secondary-foreground mb-4">Resources</h4>
            <ul className="space-y-3">
              {footerLinks.resources.map((link) => (
                <li key={link.name}>
                  <a href={link.href} className="text-secondary-foreground/70 hover:text-primary transition-colors text-sm">
                    {link.name}
                  </a>
                </li>
              ))}
            </ul>
          </div>

          {/* Company */}
          <div>
            <h4 className="font-display font-semibold text-secondary-foreground mb-4">Company</h4>
            <ul className="space-y-3">
              {footerLinks.company.map((link) => (
                <li key={link.name}>
                  <a href={link.href} className="text-secondary-foreground/70 hover:text-primary transition-colors text-sm">
                    {link.name}
                  </a>
                </li>
              ))}
            </ul>
          </div>
        </div>

        {/* Bottom */}
        <div className="pt-8 border-t border-secondary-foreground/10">
          <div className="flex flex-col md:flex-row justify-between items-center gap-4">
            <p className="text-secondary-foreground/50 text-sm">
              © 2024 HostingGuider. All rights reserved.
            </p>
            <p className="text-secondary-foreground/50 text-sm">
              Made with ❤️ for the hosting community
            </p>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
