// Generates/extends ACF Local JSON for the Phase 4-7 field groups. Idempotent: re-running rewrites the same keys.
// Run: node tools/acf-build.mjs   then: wp rivermere acf-sync
import fs from 'node:fs';

const NOW = Math.floor(Date.now() / 1000);
const base = { 'aria-label': '', required: 0, conditional_logic: 0, wrapper: { width: '', class: '', id: '' } };
// Field NAMES carry the scope prefix for page/FAQ/application fields (vp_title, ap_story_title, faq_vendors_items, apps_enabled);
// Theme Settings (scope 'opt') and sub-fields keep short names. Keys always stay field_rm_{scope}_{name}.
const PREFIXED = new Set(['vp', 'ap', 'faq', 'apps']);
const field = (type, scope, name, label, extra = {}) => ({ ...base, key: `field_rm_${scope}_${name}`, label, name: PREFIXED.has(scope) ? `${scope}_${name}` : name, type, ...extra });
const half = (w) => ({ wrapper: { width: String(w), class: '', id: '' } });
const text = (s, n, l, e = {}) => field('text', s, n, l, e);
const area = (s, n, l, e = {}) => field('textarea', s, n, l, { rows: 3, new_lines: '', ...e });
const img = (s, n, l, e = {}) => field('image', s, n, l, { return_format: 'array', preview_size: 'medium', library: 'all', ...e });
const tab = (s, n, l) => ({ ...base, key: `field_rm_tab_${s}_${n}`, label: l, name: '', type: 'tab', placement: 'top' });
const toggle = (s, n, l, def = 1, e = {}) => field('true_false', s, n, l, { ui: 1, default_value: def, message: '', ...e });
const repeater = (s, n, l, subs, e = {}) => field('repeater', s, n, l, { layout: 'block', button_label: 'Add row', min: 0, max: 0, sub_fields: subs, ...e });
const icon = (s, n, l, e = {}) => field('select', s, n, l, { choices: { leaf: 'Leaf' }, default_value: 'leaf', return_format: 'value', allow_null: 0, ...e });

// Shared FAQ repeater definition (one architecture for every FAQ location).
const faqItems = (scope, name, label, extra = {}) =>
	repeater(scope, name, label, [
		{ ...text(`${scope}_${name}`, 'question', 'Question', { required: 0 }), key: `field_rm_${scope}_${name}_question` },
		{ ...area(`${scope}_${name}`, 'answer', 'Answer', { rows: 4, new_lines: 'wpautop' }), key: `field_rm_${scope}_${name}_answer` },
	], { button_label: 'Add question', instructions: 'Add, remove or drag to reorder. Rows with an empty question or answer are ignored. Only questions you actually answer on the page are published (and added to structured data).', ...extra });

const group = (key, title, fields, location, order = 0) => ({
	key,
	title,
	fields,
	location,
	menu_order: order,
	position: 'normal',
	style: 'default',
	label_placement: 'top',
	instruction_placement: 'label',
	hide_on_screen: '',
	active: true,
	description: '',
	show_in_rest: 0,
	modified: NOW,
});
const write = (dir, g) => {
	fs.writeFileSync(`${dir}/${g.key}.json`, JSON.stringify(g, null, 4) + '\n');
	console.log('wrote', dir, g.key, g.fields.length, 'fields');
};
const readGroup = (file) => JSON.parse(fs.readFileSync(file, 'utf8'));

// ---------- 1. Theme Settings: FAQ + Vendor Applications tabs ----------
{
	const file = 'acf-json/group_rm_theme_settings.json';
	const g = readGroup(file);
	g.fields = g.fields.filter((f) => !/^field_rm_(tab_faq|tab_apps|faq_|apps_)/.test(f.key));
	const faqs = [
		tab('opt', 'faq', 'FAQs'),
		field('message', 'faq', 'note', 'About global FAQs', { message: 'Used as the default FAQ on the Vendors and Events pages. A single vendor or event can override it with its own FAQ. The About Us and Become a Vendor FAQs are edited on those pages.', new_lines: 'wpautop' }),
		text('faq', 'vendors_title', 'Vendors FAQ heading', { default_value: 'Vendor questions answered' }),
		area('faq', 'vendors_intro', 'Vendors FAQ introduction', { rows: 2 }),
		faqItems('faq', 'vendors_items', 'Global Vendors FAQ'),
		text('faq', 'events_title', 'Events FAQ heading', { default_value: 'Event questions answered' }),
		area('faq', 'events_intro', 'Events FAQ introduction', { rows: 2 }),
		faqItems('faq', 'events_items', 'Global Events FAQ'),
	];
	const apps = [
		tab('opt', 'apps', 'Vendor Applications'),
		toggle('apps', 'enabled', 'Applications are open', 1, { instructions: 'Turn off to close the form (the page stays visible).' }),
		field('email', 'apps', 'notify_email', 'Notification email', { instructions: 'Receives a short alert for each new application (no applicant details). Defaults to the site admin email.', ...half(50) }),
		toggle('apps', 'ack_applicant', 'Email an acknowledgement to the applicant', 0, { instructions: 'Sends a generic "we received your application" message.', ...half(50) }),
		field('date_picker', 'apps', 'deadline', 'Application deadline (optional)', { display_format: 'd/m/Y', return_format: 'Y-m-d', first_day: 1, instructions: 'After this date the form closes. Leave empty for year-round applications.' }),
		toggle('apps', 'fee_enabled', 'Charge an application fee', 0, { instructions: 'Only enable if a fee really applies. No payment is collected on the site: applicants just acknowledge the fee.' }),
		text('apps', 'fee_amount', 'Fee amount', { ...half(50), instructions: 'e.g. "25"', conditional_logic: [[{ field: 'field_rm_apps_fee_enabled', operator: '==', value: '1' }]] }),
		text('apps', 'fee_description', 'Fee description', { ...half(50), instructions: 'e.g. "one-time administration fee, payable if approved"', conditional_logic: [[{ field: 'field_rm_apps_fee_enabled', operator: '==', value: '1' }]] }),
		area('apps', 'booth', 'Booth / stall requirements', { instructions: 'Shown on the Become a Vendor page. Only state real requirements.' }),
		area('apps', 'instructions', 'Application instructions', { instructions: 'Short text above the form.' }),
		text('apps', 'success', 'Success message', { default_value: 'Thank you for applying! Your application has been received. Our team will review your information and contact you about the next steps.' }),
		area('apps', 'terms', 'Extra terms (optional)', { instructions: 'Shown above the consent checkbox.' }),
	];
	const at = g.fields.findIndex((f) => f.key === 'field_rm_tab_appearance');
	g.fields.splice(at < 0 ? g.fields.length : at, 0, ...faqs, ...apps);
	g.modified = NOW;
	fs.writeFileSync(file, JSON.stringify(g, null, 4) + '\n');
	console.log('updated Theme Settings:', g.fields.length, 'fields');
}

// ---------- 2. Vendor group (plugin): per-vendor FAQ ----------
{
	const file = 'companion-plugin/rivermere-core/acf-json/group_rmc_vendor.json';
	const g = readGroup(file);
	g.fields = g.fields.filter((f) => !/^field_rm_(tab_vfaq|vfaq_)/.test(f.key));
	g.fields.push(tab('vfaq', 'faq', 'FAQ'));
	g.fields.push(faqItems('vfaq', 'vendor_faqs', 'Vendor FAQ (optional)', { instructions: 'Optional. If empty, the global Vendors FAQ (Theme Settings → FAQs) is shown.', name: 'vendor_faqs' }));
	g.modified = NOW;
	fs.writeFileSync(file, JSON.stringify(g, null, 4) + '\n');
	console.log('updated Vendor details:', g.fields.length, 'fields');
}

// ---------- 3. Become a Vendor page group ----------
write('acf-json', group('group_rm_page_vendor_application', 'Become a Vendor page', [
	tab('vp', 'hero', 'Hero'),
	text('vp', 'eyebrow', 'Eyebrow', { default_value: 'Grow with our community' }),
	text('vp', 'title', 'Heading (H1)', { default_value: 'Become a Vendor at Rivermere Farmers Market' }),
	area('vp', 'description', 'Description', { default_value: 'Share your products, connect with local shoppers and become part of a community celebrating fresh food, local makers and independent businesses.' }),
	img('vp', 'image', 'Hero image'),
	text('vp', 'cta_apply', 'Primary button label', { default_value: 'Apply Now', ...half(50) }),
	text('vp', 'cta_requirements', 'Secondary button label', { default_value: 'Learn About Requirements', ...half(50) }),
	tab('vp', 'benefits', 'Why join'),
	text('vp', 'benefits_title', 'Heading', { default_value: 'Why Join Our Market?' }),
	repeater('vp', 'benefits', 'Benefit cards', [
		{ ...icon('vp_b', 'icon', 'Icon', { ...half(25) }), key: 'field_rm_vp_b_icon' },
		{ ...text('vp_b', 'title', 'Title', { ...half(75) }), key: 'field_rm_vp_b_title' },
		{ ...area('vp_b', 'text', 'Text'), key: 'field_rm_vp_b_text' },
	], { max: 6, button_label: 'Add benefit' }),
	tab('vp', 'steps', 'How it works'),
	text('vp', 'steps_title', 'Heading', { default_value: 'How It Works' }),
	repeater('vp', 'steps', 'Steps', [
		{ ...text('vp_s', 'title', 'Title'), key: 'field_rm_vp_s_title' },
		{ ...area('vp_s', 'text', 'Text'), key: 'field_rm_vp_s_text' },
	], { max: 6, button_label: 'Add step', instructions: 'Do not promise acceptance or an unverified review time.' }),
	tab('vp', 'req', 'Requirements'),
	text('vp', 'req_title', 'Heading', { default_value: 'Vendor Requirements' }),
	area('vp', 'req_intro', 'Introduction'),
	repeater('vp', 'requirements', 'Requirements', [
		{ ...text('vp_r', 'title', 'Title'), key: 'field_rm_vp_r_title' },
		{ ...area('vp_r', 'text', 'Details'), key: 'field_rm_vp_r_text' },
	], { max: 12, button_label: 'Add requirement', instructions: 'Only list requirements that really apply. Booth requirements and fees come from Theme Settings → Vendor Applications.' }),
	tab('vp', 'form', 'Application form'),
	text('vp', 'form_title', 'Form heading', { default_value: 'Apply to Become a Vendor' }),
	area('vp', 'form_intro', 'Form introduction'),
	tab('vp', 'faqtab', 'FAQ'),
	text('vp', 'faq_title', 'FAQ heading', { default_value: 'Vendor application questions' }),
	area('vp', 'faq_intro', 'FAQ introduction', { rows: 2 }),
	faqItems('vp', 'faq_items', 'Vendor application FAQ'),
	tab('vp', 'cta', 'Final CTA'),
	text('vp', 'cta_title', 'Heading', { default_value: 'Questions before you apply?' }),
	area('vp', 'cta_text', 'Text', { rows: 2 }),
], [[{ param: 'page_template', operator: '==', value: 'page-become-a-vendor.php' }]]));

// ---------- 4. About Us page group ----------
const cardRepeater = (s, n, l, extra = {}) => repeater(s, n, l, [
	{ ...icon(`${s}_${n}`, 'icon', 'Icon', { ...half(25) }), key: `field_rm_${s}_${n}_icon` },
	{ ...text(`${s}_${n}`, 'title', 'Title', { ...half(75) }), key: `field_rm_${s}_${n}_title` },
	{ ...area(`${s}_${n}`, 'text', 'Text'), key: `field_rm_${s}_${n}_text` },
], { button_label: 'Add card', ...extra });
write('acf-json', group('group_rm_page_about', 'About Us page', [
	tab('ap', 'hero', 'Hero'),
	text('ap', 'eyebrow', 'Eyebrow', { default_value: 'Our story' }),
	text('ap', 'title', 'Heading (H1)', { default_value: 'More Than a Market. A Community Growing Together.' }),
	area('ap', 'intro', 'Introduction'),
	img('ap', 'image', 'Hero image'),
	text('ap', 'cta1', 'Primary button label', { default_value: 'Explore Our Vendors', ...half(50) }),
	text('ap', 'cta2', 'Secondary button label', { default_value: 'Visit the Market', ...half(50) }),
	tab('ap', 'story', 'Our story'),
	text('ap', 'story_eyebrow', 'Eyebrow'),
	text('ap', 'story_title', 'Heading', { default_value: 'Rooted in Local. Growing Together.' }),
	field('wysiwyg', 'ap', 'story_body', 'Story text', { tabs: 'all', toolbar: 'basic', media_upload: 0, delay: 0 }),
	img('ap', 'story_image', 'Story image'),
	tab('ap', 'what', 'What we do'),
	text('ap', 'what_title', 'Heading', { default_value: 'What You’ll Find at Rivermere' }),
	repeater('ap', 'what_cards', 'Feature cards', [
		{ ...icon('ap_wc', 'icon', 'Icon', { ...half(25) }), key: 'field_rm_ap_wc_icon' },
		{ ...text('ap_wc', 'title', 'Title', { ...half(75) }), key: 'field_rm_ap_wc_title' },
		{ ...area('ap_wc', 'text', 'Text'), key: 'field_rm_ap_wc_text' },
		{ ...field('select', 'ap_wc', 'link', 'Links to', { choices: { vendors: 'Vendors', events: 'Events', market: 'Market info page', none: 'No link' }, default_value: 'vendors', return_format: 'value', allow_null: 0 }), key: 'field_rm_ap_wc_link' },
		{ ...text('ap_wc', 'link_label', 'Link label'), key: 'field_rm_ap_wc_link_label' },
	], { max: 6, button_label: 'Add card' }),
	tab('ap', 'mission', 'Mission'),
	text('ap', 'mission_eyebrow', 'Eyebrow', { default_value: 'What drives us' }),
	text('ap', 'mission_title', 'Heading', { default_value: 'Good Food. Strong Connections. Thriving Communities.' }),
	area('ap', 'mission_text', 'Text'),
	repeater('ap', 'mission_points', 'Mission points', [{ ...text('ap_mp', 'text', 'Point'), key: 'field_rm_ap_mp_text' }], { max: 8, button_label: 'Add point' }),
	tab('ap', 'values', 'Values'),
	text('ap', 'values_title', 'Heading', { default_value: 'What We Value' }),
	cardRepeater('ap', 'values', 'Value cards', { max: 9 }),
	tab('ap', 'vendors', 'Featured vendors'),
	text('ap', 'vendors_title', 'Heading', { default_value: 'Meet the People Behind the Market' }),
	area('ap', 'vendors_intro', 'Introduction', { rows: 2 }),
	text('ap', 'vendors_cta', 'Button label', { default_value: 'Explore All Vendors' }),
	tab('ap', 'season', 'Seasons'),
	text('ap', 'season_title', 'Heading', { default_value: 'Something Fresh Every Season' }),
	area('ap', 'season_text', 'Text'),
	img('ap', 'season_image', 'Image'),
	text('ap', 'season_cta', 'Button label', { default_value: 'See Upcoming Events' }),
	tab('ap', 'visit', 'Plan your visit'),
	text('ap', 'visit_title', 'Heading', { default_value: 'Plan Your Visit' }),
	area('ap', 'visit_intro', 'Introduction', { rows: 2, instructions: 'Address, market days, hours and contact details come from Theme Settings.' }),
	tab('ap', 'cta', 'Community CTA'),
	text('ap', 'cta_title', 'Heading', { default_value: 'There’s a Place for Everyone at Rivermere' }),
	text('ap', 'cta_visitor_title', 'Visitor card title', { ...half(50) }),
	text('ap', 'cta_vendor_title', 'Vendor card title', { ...half(50) }),
	area('ap', 'cta_visitor_text', 'Visitor card text', { rows: 2, ...half(50) }),
	area('ap', 'cta_vendor_text', 'Vendor card text', { rows: 2, ...half(50) }),
	text('ap', 'cta_visitor_label', 'Visitor button label', { default_value: 'Explore the Market', ...half(50) }),
	text('ap', 'cta_vendor_label', 'Vendor button label', { default_value: 'Become a Vendor', ...half(50) }),
	tab('ap', 'faqtab', 'FAQ'),
	text('ap', 'faq_title', 'FAQ heading', { default_value: 'About the market: your questions' }),
	area('ap', 'faq_intro', 'FAQ introduction', { rows: 2 }),
	faqItems('ap', 'faq_items', 'About Us FAQ'),
], [[{ param: 'page_template', operator: '==', value: 'page-about.php' }]]));
