// ACF admin editing checks (temporary admin). Run: WP_PS1=<wp.ps1> node tools/e2e-acf4.mjs
import puppeteer from 'puppeteer-core';
import { wpEval, wpCli } from './lib/wp.mjs';

const B = 'http://rivermere-farmers-market.local';
const res = [];
const ok = (n, c, x = '') => res.push(`${c ? 'PASS' : 'FAIL'} ${n}${x ? ' — ' + x : ''}`);
const U = ['qa_acf_tmp', 'Qa-' + Math.random().toString(36).slice(2) + Math.random().toString(36).slice(2) + '!9A'];
wpCli('user', 'create', U[0], 'qa-acf@example.invalid', '--role=administrator', `--user_pass=${U[1]}`);
const browser = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
const page = await browser.newPage();
await page.setViewport({ width: 1400, height: 1000 });
page.on('dialog', (d) => d.accept());
try {
	await page.goto(`${B}/wp-login.php`, { waitUntil: 'networkidle0' });
	await page.type('#user_login', U[0]);
	await page.type('#user_pass', U[1]);
	await Promise.all([page.waitForNavigation(), page.click('#wp-submit')]);

	const ids = JSON.parse(wpEval(`echo wp_json_encode(array('about'=>rivermere_demo_find('page-about','page'),'vp'=>rivermere_demo_find('page-become-a-vendor','page'),'vendor'=>get_posts(array('post_type'=>'rm_vendor','name'=>'greenfield-farm','fields'=>'ids'))[0],'event'=>get_posts(array('post_type'=>'rm_event','name'=>'harvest-festival','fields'=>'ids'))[0],'home'=>(int) get_option('page_on_front')));`));
	const tabs = async (url) => { await page.goto(url, { waitUntil: 'networkidle0' }); return page.$$eval('.acf-tab-wrap .acf-tab-button', (a) => a.map((x) => x.textContent.trim())); };

	let t = await tabs(`${B}/wp-admin/post.php?post=${ids.about}&action=edit`);
	ok('G13 About Us editor shows its field tabs (Hero … FAQ)', ['Hero', 'Our story', 'What we do', 'Mission', 'Values', 'Featured vendors', 'Seasons', 'Plan your visit', 'Community CTA', 'FAQ'].every((x) => t.includes(x)), t.join(', '));
	ok('G13/C3 About FAQ repeater has the 6 demo rows with add/remove/reorder controls', (await page.$$eval('.acf-field[data-name="ap_faq_items"] .acf-row:not(.acf-clone)', (r) => r.length)) === 6 && (await page.$$('.acf-field[data-name="ap_faq_items"] .acf-row-handle.order')).length >= 6 && !!(await page.$('.acf-field[data-name="ap_faq_items"] [data-event="add-row"]')));
	ok('G13 the generic Page Builder is hidden on the About template (no duplicate editors)', (await page.$('.acf-field[data-name="page_sections"]')) === null);
	t = await tabs(`${B}/wp-admin/post.php?post=${ids.vp}&action=edit`);
	ok('E12 Become a Vendor editor shows Hero, Why join, How it works, Requirements, Form, FAQ, Final CTA tabs', ['Hero', 'Why join', 'How it works', 'Requirements', 'Application form', 'FAQ', 'Final CTA'].every((x) => t.includes(x)), t.join(', '));
	ok('E12 benefits (4), steps (4) and FAQ (6) repeaters load', (await page.$$eval('.acf-field[data-name="vp_benefits"] .acf-row:not(.acf-clone)', (r) => r.length)) === 4 && (await page.$$eval('.acf-field[data-name="vp_steps"] .acf-row:not(.acf-clone)', (r) => r.length)) === 4 && (await page.$$eval('.acf-field[data-name="vp_faq_items"] .acf-row:not(.acf-clone)', (r) => r.length)) === 6);
	t = await tabs(`${B}/wp-admin/post.php?post=${ids.home}&action=edit`);
	ok('regression: Home keeps its Page Builder (flexible content) with existing layouts', (await page.$$eval('.acf-flexible-content .layout:not(.acf-clone)', (l) => l.length)) >= 5);
	await page.goto(`${B}/wp-admin/post.php?post=${ids.vendor}&action=edit`, { waitUntil: 'networkidle0' });
	ok('C3 single vendor has an optional FAQ repeater', !!(await page.$('.acf-field[data-name="vendor_faqs"]')));
	t = await tabs(`${B}/wp-admin/post.php?post=${ids.event}&action=edit`);
	ok('C3 event edit screen keeps its FAQ tab with 4 rows', t.includes('FAQ') && (await page.$$eval('.acf-field[data-name="event_faqs"] .acf-row:not(.acf-clone)', (r) => r.length)) === 4);

	// Theme Settings: new tabs + save
	t = await tabs(`${B}/wp-admin/admin.php?page=rivermere-theme-settings`);
	ok('C3/E12 Theme Settings has FAQs and Vendor Applications tabs', t.includes('FAQs') && t.includes('Vendor Applications'), t.join(', '));
	const names = ['faq_vendors_items', 'faq_events_items', 'apps_enabled', 'apps_notify_email', 'apps_ack_applicant', 'apps_deadline', 'apps_fee_enabled', 'apps_booth', 'apps_instructions', 'apps_success', 'apps_terms'];
	const present = await page.evaluate((n) => n.filter((x) => document.querySelector(`.acf-field[data-name="${x}"]`)), names);
	ok('E12 application settings fields present', present.length === names.length, `${present.length}/${names.length}`);
	const original = wpEval(`echo get_field('apps_instructions','option');`);
	await page.evaluate(() => { const i = document.querySelector('.acf-field[data-name="apps_instructions"] textarea'); i.value = 'QA edited instructions'; });
	await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('.acf-button.button-primary, input[type=submit].button-primary')]);
	const saved = wpEval(`echo get_field('apps_instructions','option');`);
	ok('K2 editing a setting in the admin saves and changes the page', saved === 'QA edited instructions' && (await (await fetch(B + '/become-a-vendor/')).text()).includes('QA edited instructions'), saved);
	wpEval(`update_field('apps_instructions', ${JSON.stringify(original)}, 'option'); echo 1;`);
	// ACF list
	await page.goto(`${B}/wp-admin/edit.php?post_type=acf-field-group`, { waitUntil: 'networkidle0' });
	const groups = await page.$$eval('#the-list tr .row-title', (a) => a.map((x) => x.textContent.trim()));
	ok('C3 ACF → Field Groups lists all 7 groups once (no duplicates)', groups.length === 7 && new Set(groups).size === 7, groups.join(' | '));
} finally {
	try { wpCli('user', 'delete', U[0], '--yes'); } catch { /* ignore */ }
	console.log(res.join('\n'));
	await browser.close();
}
