# Rivermere Market — WordPress theme for farmers markets

A reusable classic WordPress theme (PHP + SCSS + vanilla JS) with an ACF Pro page builder, a companion plugin (vendors, events, newsletter, SEO integration) and Rank Math compatibility. First site: **Rivermere Farmers Market** (all demo content is fictional). Design reference: `home-page.png`.

## Requirements
- WordPress 6.4+, PHP 8.0+
- **ACF Pro** 6.x (required) and **Rank Math SEO** (recommended; the SEO module only extends it)
- Node 18+ to build assets (compiled files are committed, so Node is not needed to deploy)

## Local setup (Local WP)
- Site: `C:\Users\Vane\Local Sites\rivermere-farmers-market`, URL `http://rivermere-farmers-market.local`
- The theme folder `wp-content/themes/rivermere-market` is a **Windows Junction** to this repo (`D:\Projects\rivermere-farmers-market-wp`). Do not recreate or copy it.
- The companion plugin lives in `companion-plugin/rivermere-core/`. WordPress only loads plugins from `wp-content/plugins`, so link or copy it there:
  `New-Item -ItemType Junction -Path "<site>\app\public\wp-content\plugins\rivermere-core" -Target "<repo>\companion-plugin\rivermere-core"`
  (on production, upload/zip the `rivermere-core` folder). Activate it, then activate the theme.
- Demo content: `wp rivermere import-demo` or **Tools → Rivermere Demo**. It is idempotent (tagged `_rivermere_demo`; never duplicates or overwrites edited content).

## Build
```
npm install
npm run dev        # sass + esbuild watchers (use SCRIPT_DEBUG=true to load unminified assets)
npm run build      # production: assets/css/main.min.css, assets/js/main.min.js
npm run lint       # stylelint + eslint
npm run images     # regenerate placeholder images from home-page.png
```
Sources: `src/scss` (abstracts → base → layout → components → sections → main.scss), `src/js`. Never edit `assets/*.min.*`.

## Structure
`inc/` setup, assets, navigation, ACF fields/options, template functions · `template-parts/{header,footer,components,sections,content}` · `companion-plugin/rivermere-core/includes` (post types, ACF groups for vendors/events, newsletter, newsletter admin, SEO integration, demo import).

## Editing content
- **Theme Settings** (admin menu): General (logos, contact, address, maps, hours, social), Header (CTA, sticky, search, announcement bar), Footer (description, newsletter text/consent, privacy page, copyright), Appearance (3 brand colours — validated hex only, output as CSS variables — and button style).
- **Page builder** (`page_sections` Flexible Content on pages): Hero, Product Categories, Image & Text, Upcoming Event Banner, Featured Vendors, Call to Action. Add/reorder/duplicate in the editor. Layouts render only through an allow-list (`rivermere_section_map()`); add a new layout by adding ACF fields, a template part and a map entry.
- **Vendors** (Vendors menu): featured image, short description, "Featured" toggle (shows on homepage), stall, days, contacts. **Product categories** are a taxonomy with an icon per term.
- **Events**: start/end date-time, location, tagline, registration URL. The homepage banner shows the next upcoming event automatically (or a chosen one).
- ACF field groups are registered in PHP with stable keys (theme: page builder, options; plugin: vendor/event). `acf-json/` is intentionally empty to avoid duplicate registration.

## Newsletter
Footer band → REST endpoint `POST /wp-json/rivermere/v1/newsletter`. Protections: REST nonce, honeypot, minimum time, per-IP rate limit (hashed, transient), server-side email validation/normalisation, unique emails, prepared queries. Subscribers table `wp_rm_subscribers`. Admin: **Newsletter Subscribers** (search, filter, delete, bulk delete, CSV export — admin-only, nonce-protected, formula-injection safe). It is **single opt-in with an explicit consent checkbox**, not double opt-in; each confirmation email carries an unsubscribe link (confirmation page via POST). Subscribers are never added to external lists.
Note: the nonce expires after 12–24 h, so cached pages must have a cache lifetime shorter than that (or users see "session expired — reload").

## SEO
Rank Math owns titles, descriptions, canonicals, robots, OG/Twitter and sitemaps. **Settings → Rivermere SEO** stores market identity (address, hours, geo, phone, social) and enriches Rank Math's existing publisher entity (adds `LocalBusiness`, address, geo, opening hours, sameAs only when filled), adds an `Event` entity on event pages, and supplies a fallback social image. It outputs nothing if Rank Math is inactive. GA4 is optional: loads only with a valid `G-` ID, once, skipped when Site Kit is active, and by default only after consent (`rm_consent=analytics` cookie or `rivermere_analytics_consent` filter).
Set a per-page meta description in Rank Math (the demo import sets one for Home).

## Performance
Single minified CSS (~15 KB) + 3 KB JS (deferred), one preloaded variable WOFF2 (Nunito, OFL), eager+`fetchpriority=high` hero image, lazy images elsewhere, responsive `srcset`, no jQuery, emoji/block CSS removed on the front end.

## Create another market's site
1. Install theme + plugin, activate ACF Pro. 2. Theme Settings: logo, name, colours, contact, hours, social. 3. Replace vendors/events/categories (or import demo then edit). 4. Build the homepage from page-builder sections. 5. Set menus. 6. Fill Rivermere SEO settings. No PHP edits required.

## Deployment
Run `npm run build`, commit, deploy theme + `rivermere-core` plugin, activate, flush permalinks (Settings → Permalinks → Save), configure Theme Settings, Rank Math and SEO settings, serve over HTTPS.

## Troubleshooting
- Unstyled site: run `npm run build` (min files are required).
- `/vendors/` 404: re-save permalinks.
- Fields missing: ACF Pro inactive or plugin not activated.
- WP-CLI under Local needs the site's PHP with `mysqli.default_port` set to the site's MySQL port.

## Known limitations
- Images in `assets/images/placeholder-*.webp` are **low-resolution crops of the design mock-up, for development only** — replace with licensed photography (hero ≥1200 px wide).
- The footer's detail section (menus/contact/social) is an addition below the newsletter band, which is what the reference shows as the page end.
- Reference fidelity was compared visually at 1024 px; it is close, not pixel-identical (font is Nunito, a close match, and illustration/leaf art is simplified SVG).
