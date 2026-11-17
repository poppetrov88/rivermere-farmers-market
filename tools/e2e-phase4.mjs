// Browser tests for FAQ, Become a Vendor, About Us, CTA links and consent-aware analytics.
// Run: WP_PS1=<wp.ps1> node tools/e2e-phase4.mjs
import puppeteer from 'puppeteer-core';
import { probe, wpEval, clearThrottles } from './lib/wp.mjs';

const B = 'http://rivermere-farmers-market.local';
const browser = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
const res = [];
const ok = (n, c, x = '') => res.push(`${c ? 'PASS' : 'FAIL'} ${n}${x ? ' — ' + x : ''}`);
const wait = (ms) => new Promise((r) => setTimeout(r, ms));
const errors = [];
const consentVal = (a) => encodeURIComponent(JSON.stringify({ v: 1, n: 1, a, m: 0, t: new Date().toISOString() }));

/** New isolated browsing context; optional consent cookie; collects /collect beacons. */
const open = async (path, { w = 1280, consent = null, onRequest = null } = {}) => {
	const ctx = await browser.createBrowserContext();
	const page = await ctx.newPage();
	const beacons = [];
	page.on('pageerror', (e) => errors.push(`${path}: ${e.message}`));
	page.on('console', (m) => m.type() === 'error' && !/40[0-9]|ERR_FAILED|429|409|422/.test(m.text()) && errors.push(`${path}: ${m.text()}`));
	// sendBeacon bodies are not visible to request interception, so capture them inside the page.
	await page.evaluateOnNewDocument(() => {
		window.__beacons = [];
		const orig = navigator.sendBeacon.bind(navigator);
		navigator.sendBeacon = (url, data) => {
			if (/collect/.test(url) && data && data.text) {
				data.text().then((txt) => {
					try { window.__beacons.push(...JSON.parse(txt).events); } catch (e) { /* ignore */ }
				});
			}
			return orig(url, data);
		};
	});
	await page.setRequestInterception(true);
	page.on('request', (req) => {
		if (onRequest && onRequest(req)) return;
		req.continue();
	});
	if (consent !== null) await ctx.setCookie({ name: 'rm_consent', value: consentVal(consent), domain: 'rivermere-farmers-market.local', path: '/' });
	await page.setViewport({ width: w, height: 900 });
	await page.goto(B + path, { waitUntil: 'networkidle0' });
	return { page, ctx, beacons };
};
const bc = (p) => p.evaluate(() => window.__beacons || []);
const visible = (p, sel) => p.$eval(sel, (e) => !e.hidden && getComputedStyle(e).display !== 'none');

// ---------- Pages load (K1)
for (const path of ['/', '/vendors/', '/vendors/greenfield-farm/', '/events/', '/events/harvest-festival/', '/become-a-vendor/', '/about-us/', '/privacy-policy/', '/terms-and-conditions/', '/cookie-policy/', '/?s=honey', '/no-such-page-xyz/']) {
	const { page, ctx } = await open(path, { consent: 0 });
	const info = await page.evaluate(() => ({ h1: document.querySelectorAll('h1').length, main: document.querySelectorAll('main').length, crumbs: document.querySelectorAll('.breadcrumbs').length, h1t: (document.querySelector('h1') || {}).textContent }));
	const isHome = path === '/';
	ok(`K1 ${path} loads: one H1, one <main>${isHome ? ', no breadcrumbs' : ', breadcrumbs'}`, info.h1 === 1 && info.main === 1 && (isHome ? info.crumbs === 0 : info.crumbs === 1), `${info.h1t && info.h1t.trim().slice(0, 50)}`);
	await ctx.close();
}

// ---------- FAQ (K2)
{
	const { page, ctx } = await open('/vendors/', { consent: 0 });
	ok('K2 vendors archive shows the global Vendors FAQ (6 questions) with heading', (await page.$$('.faq__item')).length === 6 && (await page.$eval('.faq-section__title', (e) => e.textContent.trim())).length > 5);
	const first = await page.$('.faq__item summary');
	await first.click();
	ok('K2 click opens an answer', await page.$eval('.faq__item', (d) => d.open));
	await first.click();
	ok('K2 click closes it again', !(await page.$eval('.faq__item', (d) => d.open)));
	await page.focus('.faq__item:nth-child(2) summary');
	await page.keyboard.press('Enter');
	ok('K2 keyboard: Enter toggles', await page.$eval('.faq__item:nth-child(2)', (d) => d.open));
	await page.keyboard.press('Space');
	ok('K2 keyboard: Space toggles', !(await page.$eval('.faq__item:nth-child(2)', (d) => d.open)));
	const ring = await page.evaluate(() => { document.querySelector('.faq__item:nth-child(3) summary').focus(); return getComputedStyle(document.activeElement).outlineStyle; });
	ok('K2 summary has a visible focus style', ring === 'solid');
	await ctx.close();
	const v = await open('/vendors/greenfield-farm/', { consent: 0 });
	ok('C4 single vendor without own FAQ falls back to the global Vendors FAQ', (await v.page.$$('.faq__item')).length === 6 && (await v.page.$eval('.faq-section', (e) => e.dataset.faqContext)) === 'vendor');
	await v.ctx.close();
	const e1 = await open('/events/', { consent: 0 });
	ok('C4 events archive shows the global Events FAQ (7)', (await e1.page.$$('.faq__item')).length === 7);
	await e1.ctx.close();
	const e2 = await open('/events/harvest-festival/', { consent: 0 });
	ok('C4 event with its own FAQ shows exactly that FAQ (4), not the global one', (await e2.page.$$('.faq__item')).length === 4);
	await e2.ctx.close();
	// empty FAQ -> no section: temporarily clear the global Events FAQ & the event's FAQ
	wpEval(`$id = get_posts(array('post_type'=>'rm_event','name'=>'seed-seedling-swap','fields'=>'ids','numberposts'=>1)); update_option('rm_qa_faq_backup', get_field('faq_events_items','option')); update_field('faq_events_items', array(), 'option'); echo 'ok';`);
	const e3 = await open('/events/', { consent: 0 });
	ok('C4 no valid FAQ content -> no empty FAQ section rendered', (await e3.page.$$('.faq-section')).length === 0);
	const ld = await e3.page.$$eval('script[type="application/ld+json"]', (s) => s.map((x) => x.textContent).join(''));
	ok('C6 no FAQPage schema without FAQ content', !/FAQPage/.test(ld));
	await e3.ctx.close();
	wpEval(`update_field('faq_events_items', get_option('rm_qa_faq_backup'), 'option'); delete_option('rm_qa_faq_backup'); echo 'restored';`);
	const e4 = await open('/events/', { consent: 0 });
	ok('C4 FAQ restored after test', (await e4.page.$$('.faq__item')).length === 7);
	await e4.ctx.close();
}

// ---------- CTA links (E2/J5)
{
	const vendorUrl = wpEval('echo rivermere_vendor_page_url();');
	const aboutUrl = wpEval('echo rivermere_about_page_url();');
	for (const path of ['/', '/vendors/', '/vendors/greenfield-farm/', '/about-us/']) {
		const { page, ctx } = await open(path, { consent: 0 });
		const hrefs = await page.$$eval('a', (a) => a.filter((x) => /become a vendor|apply as a vendor/i.test(x.textContent)).map((x) => x.href));
		ok(`E2 ${path}: every "Become a Vendor" CTA links to ${vendorUrl.replace(B, '')}`, hrefs.length > 0 && hrefs.every((h) => h === vendorUrl), `${hrefs.length} links`);
		await ctx.close();
	}
	const { page, ctx } = await open('/', { consent: 0 });
	const nav = await page.$$eval('.primary-nav .menu a', (a) => a.map((x) => x.href));
	ok('J5 primary menu "About" resolves to the About Us page', nav.includes(aboutUrl) && !nav.some((h) => /\/about\/?$/.test(h)), nav.join(' '));
	const footerCta = await page.$$eval('.footer-brand a.btn', (a) => a.map((x) => x.href));
	ok('E2 footer CTA links to Become a Vendor', footerCta.includes(vendorUrl));
	await ctx.close();
}

// ---------- About Us (G)
{
	const { page, ctx } = await open('/about-us/', { consent: 0 });
	const info = await page.evaluate(() => ({
		h2: [...document.querySelectorAll('main h2')].map((h) => h.textContent.trim()),
		vendors: document.querySelectorAll('.about-vendors .vendor-card').length,
		values: document.querySelectorAll('#values .feature-card').length,
		what: document.querySelectorAll('#what-we-do .feature-card').length,
		visit: !!document.querySelector('#visit .visit__card'),
		vendorCta: [...document.querySelectorAll('.community-card--vendor a.btn')].map((a) => a.href),
		heroEager: (document.querySelector('.landing-hero__img') || {}).loading,
		heroAlt: (document.querySelector('.landing-hero__img') || {}).alt,
		orderedHeadings: [...document.querySelectorAll('h1,h2,h3')].map((h) => +h.tagName[1]),
	}));
	ok('G13 About sections present (what 4, values 6, vendors 4, visit card)', info.what === 4 && info.values === 6 && info.vendors === 4 && info.visit);
	ok('G3 hero image is eager with descriptive alt', info.heroEager === 'eager' && info.heroAlt.length > 20, info.heroAlt.slice(0, 60));
	let skipped = false;
	let prev = 1;
	for (const lv of info.orderedHeadings) { if (lv > prev + 1) skipped = true; prev = lv; }
	ok('B3 heading hierarchy has no skipped levels', !skipped, info.orderedHeadings.join(''));
	ok('G11 vendor CTA card links to /become-a-vendor/', info.vendorCta.length === 1 && /\/become-a-vendor\/$/.test(info.vendorCta[0]));
	ok('G12 About FAQ rendered (6)', (await page.$$('.faq__item')).length === 6);
	const visitTxt = await page.$eval('#visit', (e) => e.textContent.replace(/\s+/g, ' '));
	ok('G10 visit info comes from Theme Settings (market days, address, email, phone)', ['Every Sunday', 'Market Street', 'hello@rivermere-market.example', '010-0199'].every((t) => visitTxt.includes(t)));
	await ctx.close();
}

// ---------- Become a Vendor page + form (E)
{
	clearThrottles();
	wpEval(`foreach (get_posts(array('post_type'=>'vendor_application','numberposts'=>-1,'post_status'=>array('publish','draft','private','pending','trash'))) as $p) { wp_delete_post($p->ID, true); } echo 1;`);
	const { page, ctx } = await open('/become-a-vendor/', { consent: 0 });
	const struct = await page.evaluate(() => ({
		h1: document.querySelector('h1').textContent.trim(),
		sections: ['benefits', 'how-it-works', 'requirements', 'apply'].map((id) => !!document.getElementById(id)),
		cards: document.querySelectorAll('#benefits .feature-card').length,
		steps: document.querySelectorAll('.steps__item').length,
		legends: document.querySelectorAll('.apply-form legend').length,
		consentChecked: document.getElementById('va-consent').checked,
		unlabeled: [...document.querySelectorAll('.apply-form input:not([type=hidden]):not([tabindex="-1"]), .apply-form textarea, .apply-form select')].filter((c) => !document.querySelector(`label[for="${c.id}"]`)).length,
		heroLinks: [...document.querySelectorAll('.landing-hero__actions a')].map((a) => a.getAttribute('href')),
		types: [...document.querySelectorAll('.apply-form input')].map((i) => i.type).filter((t) => ['email', 'tel', 'url'].includes(t)),
	}));
	ok('E3 hero H1 text', struct.h1 === 'Become a Vendor at Rivermere Farmers Market', struct.h1);
	ok('E3 benefits (4), steps (4), requirements and form sections present', struct.cards === 4 && struct.steps === 4 && struct.sections.every(Boolean));
	ok('E3 hero buttons anchor to #apply and #requirements', struct.heroLinks.join() === '#apply,#requirements');
	ok('E4 grouped into 5 fieldsets with legends; every control has a label', struct.legends === 5 && struct.unlabeled === 0, `legends=${struct.legends} unlabeled=${struct.unlabeled}`);
	ok('E4 uses email / tel / url input types', ['email', 'tel', 'url'].every((t) => struct.types.includes(t)));
	ok('E5 consent checkbox unchecked by default', struct.consentChecked === false);
	ok('E5 consent text links to the real Privacy Policy', await page.$eval('#va-consent + label a', (a) => /privacy-policy/.test(a.href)));
	ok('E5 no payment fields and newsletter is not pre-enrolled', (await page.$$('.apply-form input[name*="card" i], .apply-form input[name*="newsletter" i]')).length === 0);

	// Empty submit
	await page.evaluate(() => document.querySelector('[data-apply-submit]').scrollIntoView({ block: 'center', behavior: 'instant' }));
	await page.click('[data-apply-submit]');
	await wait(300);
	const empty = await page.evaluate(() => ({ summary: !document.querySelector('[data-apply-summary]').hidden, focus: document.activeElement.hasAttribute('data-apply-summary'), links: document.querySelectorAll('[data-apply-summary-list] a').length, invalid: document.querySelectorAll('[aria-invalid="true"]').length, errs: [...document.querySelectorAll('.field__error:not([hidden])')].length }));
	ok('E10 empty submit: accessible error summary shown and focused', empty.summary && empty.focus && empty.links === 7, `links=${empty.links}`);
	ok('E10 inline errors + aria-invalid on each failing field', empty.invalid === 7 && empty.errs === 7);
	await page.click('[data-apply-summary-list] a');
	ok('E10 summary links move focus to the field', await page.evaluate(() => document.activeElement.matches('input,textarea,select')));
	const reqs = [];
	// Bad email / URL / phone
	await page.type('#va-business', 'Bad Data Farm');
	await page.type('#va-first', 'Ann');
	await page.type('#va-last', 'Lee');
	await page.type('#va-email', 'nope');
	await page.type('#va-phone', 'abc');
	await page.type('#va-website', 'not a url');
	await page.type('#va-products', 'Eggs');
	await page.click('[data-apply-submit]');
	await wait(200);
	const bad = await page.evaluate(() => ({ email: document.getElementById('va-email-error').textContent, phone: document.getElementById('va-phone-error').textContent, web: document.getElementById('va-website-error').textContent, consent: document.getElementById('va-consent-error').textContent }));
	ok('E10 client validation: invalid email, phone, URL and missing consent explained', bad.email && bad.phone && bad.web && bad.consent, JSON.stringify(bad).slice(0, 120));
	ok('E10 nothing was submitted while invalid', probe().apps === 0);

	// Fix and submit with a slow, observed request
	await page.$eval('#va-email', (e) => { e.value = ''; });
	await page.type('#va-email', 'ui-test@example.com');
	await page.$eval('#va-phone', (e) => { e.value = ''; });
	await page.type('#va-phone', '+1 555 010 0188');
	await page.$eval('#va-website', (e) => { e.value = ''; });
	await page.type('#va-website', 'https://example.com');
	await page.click('#va-consent + label .check__box');
	await wait(2500); // honour the minimum time-on-form check
	await page.setRequestInterception(true);
	let posts = 0;
	page.removeAllListeners('request');
	page.on('request', async (req) => {
		if (req.url().includes('/vendor-application')) {
			posts += 1;
			await wait(900); // make the loading state observable
		}
		req.continue();
	});
	await page.evaluate(() => document.querySelector('[data-apply-submit]').click());
	await page.evaluate(() => document.querySelector('[data-apply-submit]').click()); // double click
	await wait(250);
	const loading = await page.evaluate(() => ({ disabled: document.querySelector('[data-apply-submit]').disabled, busy: document.querySelector('[data-apply-submit]').getAttribute('aria-busy'), text: document.querySelector('[data-apply-submit] span').textContent }));
	ok('E10 loading state: button disabled + aria-busy while sending', loading.disabled && loading.busy === 'true', loading.text);
	await page.waitForSelector('[data-apply-success]:not([hidden])', { timeout: 8000 });
	const done = await page.evaluate(() => ({ text: document.querySelector('[data-apply-success-text]').textContent, formHidden: document.querySelector('[data-apply-form]').hidden, focused: document.activeElement.hasAttribute('data-apply-success') }));
	ok('E10 success message shown only after the server confirmed storage', done.formHidden && done.text.startsWith('Thank you for applying') && done.focused, done.text.slice(0, 40));
	ok('E9/E10 double click produced exactly ONE request and ONE stored application', posts === 1 && probe().apps === 1, `requests=${posts} stored=${probe().apps}`);
	await ctx.close();

	// failure handling: server error keeps the form usable and says so
	const f = await open('/become-a-vendor/', { consent: 0, onRequest: (req) => { if (req.url().includes('/vendor-application')) { req.respond({ status: 500, contentType: 'application/json', body: JSON.stringify({ success: false, message: 'Sorry, we could not save your application. Please try again later.' }) }); return true; } return false; } });
	await f.page.type('#va-business', 'Fail Farm'); await f.page.type('#va-first', 'F'); await f.page.type('#va-last', 'L'); await f.page.type('#va-email', 'fail@example.com'); await f.page.type('#va-phone', '+1 555 010 0177'); await f.page.type('#va-products', 'Jam');
	await f.page.click('#va-consent + label .check__box');
	await f.page.click('[data-apply-submit]');
	await wait(600);
	const fail = await f.page.evaluate(() => ({ status: document.querySelector('[data-apply-status]').textContent, enabled: !document.querySelector('[data-apply-submit]').disabled, formVisible: !document.querySelector('[data-apply-form]').hidden, successHidden: document.querySelector('[data-apply-success]').hidden }));
	ok('E10 server failure: meaningful message, form stays, button re-enabled, no false success', /could not save/.test(fail.status) && fail.enabled && fail.formVisible && fail.successHidden);
	await f.ctx.close();

	// mobile layout
	for (const w of [320, 390, 768]) {
		const m = await open('/become-a-vendor/', { w, consent: 0 });
		const cols = await m.page.$eval('.field-row', (e) => getComputedStyle(e).gridTemplateColumns.split(' ').length);
		ok(`B5 ${w}px: form is ${w < 768 ? 'single' : 'two'}-column, no horizontal overflow`, (w < 768 ? cols === 1 : cols === 2) && !(await m.page.evaluate(() => document.documentElement.scrollWidth > innerWidth)), `${cols} col`);
		await m.ctx.close();
	}
	wpEval(`foreach (get_posts(array('post_type'=>'vendor_application','numberposts'=>-1,'post_status'=>array('publish','draft','private','pending','trash'))) as $p) { wp_delete_post($p->ID, true); } global $wpdb; $wpdb->query('TRUNCATE TABLE ' . ri_table()); echo 1;`);
}

// ---------- Analytics + consent in a real browser (F7, K4)
{
	clearThrottles();
	wpEval(`global $wpdb; $wpdb->query('TRUNCATE TABLE ' . ri_table()); update_field('whatsapp_number', '+44 7700 900123', 'option'); update_field('facebook_url', 'https://www.facebook.com/example-test', 'option'); echo 1;`);
	const noNav = (p) => p.evaluate(() => document.addEventListener('click', (e) => { if (e.target.closest('a[href]')) e.preventDefault(); }, true));

	// first visit, no consent
	let s = await open('/', { consent: null });
	await noNav(s.page);
	await s.page.click('.site-header__cta .btn');
	await wait(400);
	ok('F7 first visit, banner undecided: NO beacon for views or clicks', (await bc(s.page)).length === 0, `${(await bc(s.page)).length} beacons`);
	ok('F5 tracker sets no cookie and no storage before consent', (await s.page.evaluate(() => document.cookie)) === '' && (await s.page.evaluate(() => localStorage.length)) === 0);
	await s.ctx.close();

	// reject optional
	s = await open('/', { consent: null });
	await noNav(s.page);
	await s.page.click('[data-cookie-banner] [data-cookie-reject]');
	await wait(300);
	await s.page.click('.site-header__cta .btn');
	await s.page.evaluate(() => document.querySelector('.contact-widget__link--phone').click());
	await wait(500);
	ok('F7 Reject Optional: nothing is sent (views, CTA, contact)', (await bc(s.page)).length === 0, `${(await bc(s.page)).length}`);
	await s.ctx.close();

	// accept all
	s = await open('/', { consent: null });
	await noNav(s.page);
	await s.page.click('[data-cookie-banner] [data-cookie-accept]');
	await wait(700);
	ok('F7 Accept: page view recorded once consent is given', (await bc(s.page)).filter((b) => b.t === 'pageview').length === 1, JSON.stringify((await bc(s.page))[0]));
	await s.page.click('.site-header__cta .btn'); // header "Become a Vendor" (internal, vendor page)
	await s.page.evaluate(() => document.querySelector('.contact-widget__link--phone').click());
	await s.page.evaluate(() => document.querySelector('.contact-widget__link--email').click());
	await s.page.evaluate(() => document.querySelector('.contact-widget__link--whatsapp').click());
	await s.page.evaluate(() => document.querySelector('.footer-contact a.social-list__link[href*="facebook"]').click());
	await s.page.evaluate(() => document.querySelector('[data-newsletter-submit]').click());
	await wait(900);
	const types = (await bc(s.page)).map((b) => b.t);
	ok('F4 CTA to vendor page tracked as apply_cta', types.includes('apply_cta'), types.join());
	ok('F4 telephone, email and WhatsApp clicks tracked', ['tel', 'email', 'whatsapp'].every((t) => types.includes(t)));
	ok('F4 social link tracked', types.includes('social') && (await bc(s.page)).find((b) => b.t === 'social').k === 'facebook');
	ok('F4 newsletter button interaction tracked (not a conversion)', types.includes('newsletter_cta') && !types.includes('newsletter_subscribed'));
	const dump = JSON.stringify(await bc(s.page));
	ok('F5/F12 payloads contain no phone number, email address or URL', !/\d{6,}/.test(dump) && !/@/.test(dump) && !/https?:/.test(dump), dump.slice(0, 140));
	// duplicate listeners / double counting
	const before = (await bc(s.page)).length;
	await s.page.click('.hero__actions .btn');
	await wait(400);
	ok('F4 one click = one beacon (no double counting)', (await bc(s.page)).length - before === 1, `${(await bc(s.page)).length - before}`);
	const ck = await s.page.evaluate(() => document.cookie.split('; ').map((c) => c.split('=')[0]));
	ok('F7 Insights adds no cookie (only the consent cookie exists)', ck.length === 1 && ck[0] === 'rm_consent' && (await s.page.evaluate(() => localStorage.length)) === 0, ck.join());

	// withdrawal
	await s.page.click('.footer-cookie-settings');
	await wait(200);
	await s.page.click('div.cookie-option:nth-of-type(2) .check');
	await s.page.click('[data-cookie-form] button[type="submit"]');
	await wait(400);
	const mark = (await bc(s.page)).length;
	await s.page.click('.hero__actions .btn');
	await s.page.evaluate(() => document.querySelector('.contact-widget__link--phone').click());
	await wait(500);
	ok('F7 consent withdrawal: tracking stops immediately (no further beacons)', (await bc(s.page)).length === mark, `${(await bc(s.page)).length - mark} new`);
	await s.ctx.close();

	// stored data matches
	const p = probe();
	ok('F8 aggregated rows stored from the consented session', (p.events.pageview || 0) >= 1 && (p.events.tel || 0) >= 1 && (p.events.social || 0) >= 1, JSON.stringify(p.events));
	const rowShape = wpEval(`global $wpdb; echo $wpdb->get_var("SELECT COUNT(*) FROM " . ri_table() . " WHERE event_key REGEXP '[0-9]{6,}|@'");`);
	ok('F12 no row contains a number-like or email-like label', rowShape === '0');
	// perf: size of tracker + no external requests
	const size = (await (await fetch(B + '/wp-content/plugins/rivermere-insights/assets/insights.js')).text()).length;
	ok('F13 tracker is tiny (< 6 KB, no dependencies)', size < 6000, `${size} bytes`);
	wpEval(`global $wpdb; $wpdb->query('TRUNCATE TABLE ' . ri_table()); delete_field('whatsapp_number','option'); delete_field('facebook_url','option'); echo 1;`);
}

console.log(res.join('\n'));
console.log('Console/page errors:', errors.length ? errors.join(' | ') : 'none');
await browser.close();
