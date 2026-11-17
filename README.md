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
- ACF field groups are Local JSON (see "ACF field groups" below).

## UI features (Theme Settings)
All are toggled in **Theme Settings** and default to on.
- **Header** tab: show social icons, language selector, search, header button. Social + language live in a slim utility bar above the header (keeps the main header identical to the design).
- **General** tab: telephone, email, WhatsApp number, Facebook / Instagram / LinkedIn URLs. An empty social URL renders a button that announces "coming soon" (no dead link); once set it becomes an external link (`target="_blank" rel="noopener noreferrer"`). The old `social_links` repeater was replaced by these three fields.
- **Floating Contact** tab: enable, show phone/email/WhatsApp, colour variant (green/yellow/dark), vertical position. A method with no data is hidden. WhatsApp uses `https://wa.me/<digits>` (8-15 digits required). The rail is 48px wide; labels expand left on hover/keyboard focus; below 768px it collapses to a 48px corner button.
- **UI Features** tab: custom 6px green scrollbar (Chromium/Safari pseudo-elements, Firefox `scrollbar-width`), 3px scroll progress bar, footer back-to-top (honours reduced motion), console welcome message (business text only).
- **Language selector is UI-only**: English remains the site language; other choices show a "coming soon" message. For real multilingual support later, use the `rivermere_languages` filter or replace `template-parts/components/language-switcher.php`.
- Tests: `node tools/e2e.mjs` and `node tools/e2e-ui.mjs` (Puppeteer + local Chrome); also `node tools/e2e-phase3.mjs` (needs GA test ID `G-TEST123456` in Rivermere SEO settings; requests to Google are aborted) and `tools/e2e-admin.mjs` (needs a temporary admin). The UI test expects a WhatsApp number and Facebook/LinkedIn URLs to be set in Theme Settings, and Instagram left empty.

## ACF field groups: how they are stored (Local JSON)
Field groups are **ACF Local JSON** files, imported into the database so they appear and can be edited under **ACF → Field Groups**:
- Theme groups (Page Builder, Theme Settings, Category Display): `acf-json/`
- Plugin groups (Vendor details, Event details): `companion-plugin/rivermere-core/acf-json/`
- Editing a group in the admin rewrites its JSON file (theme groups to the theme, plugin groups to the plugin). **Commit those files.** Field/group keys must never change; stored content is tied to them.
- New site or after `git pull` with changed JSON: ACF → Field Groups → "Sync available", or `wp rivermere acf-sync` (imports missing groups, updates DB copies older than the JSON).
- Why the groups were missing from the admin list before: they were registered in PHP with `acf_add_local_field_group()`. Such "local-only" groups render in editors but are never database records, and ACF's list shows only database/JSON-synced groups. They were converted with `tools/acf-export.php` (keys unchanged, no content touched).
- Select options for icons are refreshed from `rivermere_icons()` at runtime in the admin.

## Event pages
`single-rm_event.php` + `template-parts/event/*`: hero (label, status, intro, date/time/place, image), two-column layout (editor content + sticky information card), schedule timeline, accessibility info, FAQ (native `details`), gallery (with captions), participating vendors, related events and a closing CTA. Everything is edited in the event screen (tabs: Overview, Date & place, Visitor information, Schedule, FAQ, Gallery & vendors) plus the normal editor for the long-form text.
- **Add to calendar** serves a real `.ics` (`/events/<slug>/?rm-ics=1`, RFC 5545, UTC times, folded lines).
- **Directions** uses the Directions URL, otherwise a Google Maps search link built from the address. Share links use the public Facebook/LinkedIn share endpoints, `mailto:` and a copy-link button.
- Schema: one `Event` entity (Rank Math graph, no duplicates) from real fields: name, dates, location/address, image, organizer, status (scheduled/postponed/cancelled). No ratings or prices are invented; `offers` appears only when a registration URL is set.

## Images and media metadata
The importer applies title, alt text, caption and description to every placeholder image (only if the description is still empty, so edits are kept). Alt text describes what is visible. The Media Library alt text is used on the front end unless a page-builder block sets its own alt text. Vendor and event-banner thumbnails are decorative (empty alt) because the name is printed next to them. All images live in the Media Library with width/height attributes and `srcset` where WordPress generates sizes. **The placeholder photos are low-resolution crops of the design mock-up (not licensed photography) and are too small to generate larger sizes; replace them before launch.**

## Legal pages
The demo import creates (idempotently, versioned by `_rivermere_legal_v`) **Privacy Policy** (`/privacy-policy/`), **Terms and Conditions** (`/terms-and-conditions/`) and **Cookie Policy** (`/cookie-policy/`), sets the WordPress privacy page, builds a Legal menu for the footer and links the newsletter consent to the Privacy Policy. They describe how this software behaves. Items highlighted as `[placeholder]` (operator, address, jurisdiction, retention periods, contacts) must be supplied by the real operator, and all three pages must be reviewed by a qualified professional before production. Their existence does not make a site legally compliant.

## Cookie consent
Custom, dependency-free (`src/js/consent.js`, `template-parts/components/cookie-consent.php`). First visit shows a bottom-centred banner with equal-weight **Accept all / Reject optional / Cookie settings** and a Cookie Policy link. The settings dialog (native `<dialog>`, Escape closes, focus trapped while open) offers Strictly necessary (always on), Analytics and Marketing (both off by default). Choices are stored in the first-party cookie `rm_consent` (`{v: version, n, a, m, t}`, 180 days); the footer "Cookie settings" button reopens the dialog.
- Nothing optional loads before consent. GA4 (Rivermere SEO settings) is exposed to the page only as `window.rivermereAnalytics = {id}` and the Google tag is injected client-side after analytics consent. No ID configured = no tracking code at all. Site Kit users: the plugin skips its own output when Site Kit is active.
- Other scripts can be gated with `<script type="text/plain" data-consent="analytics|marketing" src="...">`; they are activated when the category is allowed. The event `rivermere:consent` is fired on every change.
- Revoking analytics sets `ga-disable-<ID>`, sends a Consent Mode `denied` update and deletes `_ga*`/`_gid*`/`_gat*` cookies on this domain and parent domains. **Limitation:** scripts already running stay loaded until the next page load, and cookies set by third-party domains cannot be removed from our site (visitors must clear them in the browser).
- To re-ask everyone (e.g. new categories) bump `VERSION` in `consent.js`. Toggle the whole feature under Theme Settings → UI Features.

## Newsletter
Footer band → REST endpoint `POST /wp-json/rivermere/v1/newsletter`. Protections: REST nonce, honeypot, minimum time, per-IP rate limit (hashed, transient), server-side email validation/normalisation, unique emails, prepared queries. Subscribers table `wp_rm_subscribers`. Admin: **Newsletter Subscribers** (search, filter, delete, bulk delete, CSV export — admin-only, nonce-protected, formula-injection safe). It is **single opt-in with an explicit consent checkbox**, not double opt-in; each confirmation email carries an unsubscribe link (confirmation page via POST). Subscribers are never added to external lists.
Note: the nonce expires after 12–24 h, so cached pages must have a cache lifetime shorter than that (or users see "session expired — reload").

## SEO
Rank Math owns titles, descriptions, canonicals, robots, OG/Twitter and sitemaps. **Settings → Rivermere SEO** stores market identity (address, hours, geo, phone, social) and enriches Rank Math's existing publisher entity (adds `LocalBusiness`, address, geo, opening hours, sameAs only when filled), adds an `Event` entity on event pages, and supplies a fallback social image. It outputs nothing if Rank Math is inactive. GA4 is optional: the tag loads only with a valid `G-` ID, once, skipped when Site Kit is active, and only after the visitor accepts analytics cookies (see Cookie consent).
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
