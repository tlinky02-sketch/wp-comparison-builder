=== Comparison Builder ===
Contributors: comparisonbuilder
Tags: comparison table, pricing table, product comparison, review table, comparison widget
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.0.5
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build beautiful comparison tables, pricing tables, pros/cons sections, and hero sections for anything — software, products, services, hosting, and more.

== Description ==

**Comparison Builder** is a full-featured WordPress plugin for creating professional comparison pages — for any niche, any product type, any service.

Whether you're comparing software tools, hosting providers, physical products, SaaS plans, financial services, or anything else, Comparison Builder gives you everything you need — no coding required.

**Key Features:**

* **Comparison Tables** — Display items in a clean, filterable grid with ratings, pricing, categories, and feature tags. Works for any type of item.
* **Pricing Tables** — Show structured pricing plans with billing cycle toggles (monthly/yearly/custom), feature lists, and CTA buttons.
* **Hero Sections** — Full-width hero cards with logo, rating, pricing, pros/cons, and direct links.
* **Pros & Cons** — Icon-based pros and cons lists with customizable colors and icons.
* **Feature Tables** — Compare features across items in a structured table format with checkmarks.
* **Use Cases** — Visual "Best For..." use-case cards with icons and descriptions.
* **Custom Lists** — Curated comparison lists with category filter buttons.
* **SEO Schema** — Automatically generates Product, SoftwareApplication, and Review structured data (schema.org).
* **AI Content Generation** — Optional AI integration to auto-generate descriptions, pros, cons, and use cases.
* **Server-Side Rendering** — All shortcodes output full HTML server-side for SEO and fast First Contentful Paint.
* **Responsive Design** — Mobile-friendly layouts out of the box.
* **Per-Item Design Overrides** — Set custom primary, accent, and border colors per item.
* **Billing Variants** — Supports multiple billing cycles (monthly, yearly, custom) with a switcher toggle.

**Shortcodes:**

* `[wpc_compare]` — Full comparison table with filters and search
* `[wpc_compare ids="1,2,3"]` — Comparison table limited to specific items
* `[wpc_pricing_table id="123"]` — Pricing plans table for a specific item
* `[wpc_hero id="123"]` — Hero section for a specific item
* `[wpc_pros_cons id="123"]` — Pros & cons table
* `[wpc_feature_table id="123"]` — Feature comparison table
* `[wpc_use_cases id="123"]` — Use case highlight cards
* `[wpc_list id="456"]` — Custom curated comparison list
* `[wpc_compare_button id="123"]` — Comparison popup trigger button

**Use It For:**

* Software & SaaS comparisons
* Web hosting reviews
* Product comparison pages
* Financial services comparisons
* Course & education platform reviews
* Agency service comparisons
* Any "best of" or "vs" content

== Installation ==

1. Upload the `comparison-builder` folder to the `/wp-content/plugins/` directory, or install directly through the WordPress Plugins screen.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **Comparison Items** in the WordPress admin to add your first item.
4. Fill in the item details (name, logo, price, rating, features, pros/cons, etc.).
5. Use the available shortcodes on any page or post to display your comparison content.

== Frequently Asked Questions ==

= Does this only work for ecommerce? =

No. Comparison Builder is completely generic. You can use it to compare software tools, hosting plans, physical products, services, financial products, or anything else that needs a side-by-side comparison.

= Do I need to know how to code? =

No. Everything is managed through the WordPress admin interface. Add items, fill in their data, and paste a shortcode into any page or post.

= Is this compatible with page builders? =

Yes. The shortcodes work in any context that renders standard WordPress shortcodes, including Gutenberg (via the Shortcode block), Elementor, Divi, Bricks, and WPBakery.

= Does it affect page load speed? =

All shortcodes use Server-Side Rendering (SSR) to deliver ready-to-display HTML on the first request. JavaScript-powered interactions (like billing toggles) activate progressively after page load, so there's no blocking render delay.

= Is my data secure? =

Yes. All database queries use `$wpdb->prepare()` and WordPress-standard insert/update/delete functions. All AJAX endpoints include nonce verification and capability checks (`manage_options`).

= Can I override styles per item? =

Yes. Each comparison item supports design overrides for primary color, accent color, border color, and coupon colors. These are applied via CSS custom properties scoped to a unique instance ID.

== Screenshots ==

1. Comparison table with category filters and search
2. Pricing table with billing cycle toggle
3. Hero section with logo, rating, pros/cons, and CTA button
4. Pros & Cons section with customizable colors
5. Use case highlight cards grid
6. Admin item editor with pricing plans

== Changelog ==

= 1.0.5 =
* Fixed: Resolved layout regression where `[wpc_pricing_table]` shortcode caused sidebar displacement due to a mismatched HTML closing tag.
* Improved: Strengthened output buffering consistency across all SSR shortcode templates.
* Improved: Full WordPress.org compliance — proper plugin headers, readme.txt, and security hardening.

= 1.0.4 =
* Added: Billing cycle toggle with monthly/yearly/custom cycle configuration per item.
* Added: Product Variants module for category-aware pricing plan switching.
* Improved: API endpoints now support bulk item fetching with filter parameters.

= 1.0.3 =
* Added: AI content generation for items and tools.
* Added: Use cases tab with per-category variant support.
* Fixed: Various SSR rendering issues across shortcodes.

= 1.0.2 =
* Added: SEO schema builder (Product, SoftwareApplication, Review).
* Added: Custom comparison lists shortcode with filter buttons.
* Added: Recommended Tools module (custom post type + shortcode).

= 1.0.1 =
* Added: Pros/Cons shortcode with global and per-item color settings.
* Added: Feature table shortcode.
* Added: Hero section SSR shortcode.
* Fixed: Taxonomy and custom post type registration.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.5 =
Fixes a critical layout issue with the pricing table shortcode that could push sidebars out of position. Upgrade recommended for all users displaying pricing tables.

== External Services ==

This plugin includes an **optional** AI content generation feature. When enabled and configured by the site administrator, it may send data to third-party AI API services. No data is sent unless the site admin explicitly enables the feature and provides their own API key.

Supported third-party services (all optional, admin-configured):

* **OpenAI API** — Used to generate item descriptions, pros, cons, and use cases.
  * Service URL: https://api.openai.com/
  * Privacy Policy: https://openai.com/privacy/
  * Terms of Use: https://openai.com/terms/

* **Other OpenAI-compatible endpoints** — Site admins may configure a custom base URL pointing to any OpenAI-compatible API (e.g., local or self-hosted models). No data is sent to custom endpoints unless configured by the admin.

Data sent to AI services may include: item names, short descriptions, and category labels entered by the admin. No personal visitor data is ever transmitted.

== Privacy Policy ==

This plugin does not collect, transmit, or store any personal data from site visitors. All data entered through the plugin admin is stored locally in the WordPress database.

The optional AI content generation feature transmits admin-entered item data (such as product name and category) to third-party AI APIs only when explicitly enabled and configured by the site administrator. No visitor data is ever sent.

== Third-Party Libraries & Build Process ==

The plugin's interactive frontend components (billing toggles, comparison popups) are built with React and compiled using Vite. The compiled output is included in the `dist/assets/` directory.

Source code for the compiled JavaScript is available in the plugin's development repository. The full, unminified source files can be inspected at the same location where this plugin was originally developed. Compiled assets are generated from the `src/` directory using `npm run build`.

Key dependencies used in the compiled bundle include:
* React (MIT License) — https://reactjs.org/
* Radix UI (MIT License) — https://www.radix-ui.com/
* Lucide Icons (ISC License) — https://lucide.dev/
* TanStack Query (MIT License) — https://tanstack.com/query/

All bundled libraries are compatible with the GPLv2 or later license.
