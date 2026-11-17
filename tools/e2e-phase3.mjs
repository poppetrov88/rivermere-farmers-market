// Phase 3 browser tests. Expects: GA4 test ID "G-TEST123456" configured (requests to Google are aborted, never sent).
import puppeteer from 'puppeteer-core';

const B = 'http://rivermere-farmers-market.local';
const browser = await puppeteer.launch({
	executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
	headless: true,
	ignoreDefaultArgs: ['--hide-scrollbars'],
});
const res = [];
const ok = (n, c, x = '') => res.push(`${c ? 'PASS' : 'FAIL'} ${n}${x ? ' — ' + x : ''}`);
const wait = (ms) => new Promise((r) => setTimeout(r, ms));
const errors = [];
const gaRequests = [];

const open = async (url, w = 1280, h = 800) => {
	const ctx = await browser.createBrowserContext(); // fresh cookies = first visit
	const p = await ctx.newPage();
	p.on('pageerror', (e) => errors.push(`${url}: ${e.message}`));
	p.on('console', (m) => m.type() === 'error' && !/404|ERR_FAILED/.test(m.text()) && errors.push(`${url}: ${m.text()}`));
	p.on('dialog', (d) => d.dismiss());
	await p.setRequestInterception(true);
	p.on('request', (r) => {
		if (/googletagmanager|google-analytics/.test(r.url())) {
			gaRequests.push(r.url());
			r.abort();
		} else {
			r.continue();
		}
	});
	await p.setViewport({ width: w, height: h });
	await p.goto(url, { waitUntil: 'networkidle0' });
	return { p, ctx };
};
const consentCookie = async (p) => {
	const c = (await p.cookies()).find((x) => x.name === 'rm_consent');
	return c ? JSON.parse(decodeURIComponent(c.value)) : null;
};
const visible = (p, sel) => p.$eval(sel, (e) => !e.hidden && getComputedStyle(e).display !== 'none');

// ---- 1. Hero alignment
{
	const { p } = await open(B + '/', 1440, 900);
	const r = await p.evaluate(() => {
		const q = (s) => document.querySelector(s).getBoundingClientRect();
		return { brand: q('.brand').left, hero: q('.hero__content').left, w: q('.hero__content').width, title: q('.hero__title').width, btn: q('.hero__actions .btn').left };
	});
	ok('1 hero text left edge equals header logo edge (1440px)', Math.abs(r.brand - r.hero) < 1, `${r.brand} vs ${r.hero}`);
	ok('1b hero text column 540–650px', r.w >= 540 && r.w <= 650, `${r.w}px`);
	ok('1c hero buttons align with text', Math.abs(r.btn - r.hero) < 1);
	const sec = await p.evaluate(() => [...document.querySelectorAll('.section--categories .container, .image-text__grid, .section--event .container, .section--vendors .container, .footer-main .container')].map((e) => Math.round(e.getBoundingClientRect().left + parseFloat(getComputedStyle(e).paddingLeft))));
	ok('1d other sections share the header container edge', sec.every((l) => l === Math.round(r.brand)), sec.join(','));
	for (const w of [768, 390]) {
		await p.setViewport({ width: w, height: 800 });
		ok(`1e hero no horizontal overflow at ${w}px`, !(await p.evaluate(() => document.documentElement.scrollWidth > innerWidth)));
	}
}

// ---- 2. Newsletter checkbox
{
	const { p } = await open(B + '/');
	await p.evaluate(() => document.querySelector('#newsletter-consent').scrollIntoView({ block: 'center', behavior: 'instant' }));
	ok('2 checkbox is native and NOT pre-selected', await p.$eval('#newsletter-consent', (e) => e.type === 'checkbox' && !e.checked && e.required));
	ok('2b privacy policy link in consent text', await p.$eval('.newsletter__consent a', (a) => /privacy-policy/.test(a.href)));
	ok('2c consent text names the market', (await p.$eval('.check__text', (e) => e.textContent)).includes('Rivermere Farmers Market. I can unsubscribe at any time'));
	await p.type('#newsletter-email', 'phase3@example.com');
	await wait(2200);
	await p.click('[data-newsletter-submit]');
	await wait(400);
	ok('2d submit blocked without consent + inline error', (await visible(p, '[data-consent-error]')) && (await p.$eval('#newsletter-consent', (e) => e.getAttribute('aria-invalid'))) === 'true');
	ok('2e error is announced (role=alert) and focus moves to checkbox', (await p.$eval('[data-consent-error]', (e) => e.getAttribute('role'))) === 'alert' && (await p.evaluate(() => document.activeElement.id)) === 'newsletter-consent');
	{ const r = await p.$eval('.check__text', (e) => { const b = e.getBoundingClientRect(); return { x: b.left + 24, y: b.top + 10 }; }); await p.mouse.click(r.x, r.y); }
	ok('2f clicking the label text toggles the checkbox', await p.$eval('#newsletter-consent', (e) => e.checked));
	ok('2g error clears after ticking', !(await visible(p, '[data-consent-error]')));
	await p.keyboard.press('Space');
	ok('2h Space key toggles (keyboard)', !(await p.$eval('#newsletter-consent', (e) => e.checked)));
	await p.keyboard.press('Space');
	await wait(500);
	const boxBg = await p.$eval('.check__box', (e) => getComputedStyle(e).backgroundColor);
	ok('2i checked state is green', /rgb\(29, 104, 6\)/.test(boxBg), boxBg);
	const focusRing = await p.$eval('.check__box', (e) => getComputedStyle(e).boxShadow);
	ok('2j keyboard focus ring visible', focusRing !== 'none', focusRing.slice(0, 40));
	const touch = await p.$eval('.check', (e) => e.getBoundingClientRect().height);
	ok('2k touch target >= 44px', touch >= 44, touch + 'px');
	// server side: no consent
	const server = await p.evaluate(async () => {
		const r = await fetch(window.rivermereData.newsletterEndpoint, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.rivermereData.restNonce }, body: JSON.stringify({ email: 'server-check@example.com', consent: false }) });
		return { status: r.status, body: await r.json() };
	});
	ok('3 server rejects missing consent', server.status === 400 && server.body.code === 'no_consent', JSON.stringify(server.body));
}

// ---- 4. Event page
{
	const { p } = await open(B + '/events/harvest-festival/');
	ok('4 event page at existing URL, single H1', (await p.$$('h1')).length === 1 && (await p.$eval('h1', (e) => e.textContent.trim())) === 'Harvest Festival');
	const info = await p.$eval('.event-card', (e) => e.textContent.replace(/\s+/g, ' '));
	ok('4b sidebar shows date, times, location, address, admission, organizer, contact', ['Sunday, December 13, 2026', '9:00 am', '3:00 pm', 'Rivermere Town Square', '12 Market Street', 'Free entry', 'Rivermere Farmers Market', 'events@rivermere-market.example'].every((t) => info.includes(t)));
	ok('4c schedule has 6 items', (await p.$$('.timeline__item')).length === 6);
	ok('4d FAQ has 4 native details', (await p.$$('details.faq__item')).length === 4);
	ok('4e gallery, vendors, related events render', (await p.$$('.gallery-grid li')).length >= 2 && (await p.$$('.event-main .vendor-card')).length === 4 && (await p.$$('.event-card-sm')).length >= 1);
	const dir = await p.$eval('.event-card__actions a[href*="maps"]', (a) => a.href);
	ok('4f directions link built from real address', dir.includes('google.com/maps/search') && dir.includes('Market'), dir.slice(0, 90));
	const ics = await p.evaluate(async () => {
		const href = document.querySelector('a[href*="rm-ics"]').href;
		const r = await fetch(href);
		return { type: r.headers.get('content-type'), body: await r.text(), href };
	});
	ok('4g .ics downloads as text/calendar with valid VEVENT', /text\/calendar/.test(ics.type) && ics.body.startsWith('BEGIN:VCALENDAR') && ics.body.includes('DTSTART:20261213T090000Z') && ics.body.includes('SUMMARY:Harvest Festival') && ics.body.trim().endsWith('END:VCALENDAR'));
	const ld = await p.$$eval('script[type="application/ld+json"]', (s) => s.map((x) => x.textContent));
	const graph = JSON.parse(ld[0])['@graph'];
	const events = graph.filter((x) => x['@type'] === 'Event');
	ok('4h exactly one JSON-LD block with one Event entity (no duplicates)', ld.length === 1 && events.length === 1);
	ok('4i Event has name, start/end, location, image, organizer, status', ['name', 'startDate', 'endDate', 'location', 'image', 'organizer', 'eventStatus'].every((k) => events[0][k]));
	ok('4j no invented ratings/offers', !JSON.stringify(graph).includes('aggregateRating') && !events[0].offers);
	const sticky = await p.$eval('.event-sidebar', (e) => getComputedStyle(e).position);
	ok('4k sidebar sticky on desktop', sticky === 'sticky');
	await p.setViewport({ width: 390, height: 800 });
	await wait(300);
	ok('4l mobile: single column, no horizontal overflow', !(await p.evaluate(() => document.documentElement.scrollWidth > innerWidth)) && (await p.$eval('.event-layout', (e) => getComputedStyle(e).gridTemplateColumns.split(' ').length)) === 1);
	await p.setViewport({ width: 1280, height: 800 });
	await p.goto(B + '/events/harvest-festival/', { waitUntil: 'networkidle0' });
	await p.evaluate(() => document.querySelector('[data-copy-link]').scrollIntoView({ block: 'center', behavior: 'instant' }));
	await wait(300);
	await p.focus('[data-copy-link]');
	await p.keyboard.press('Enter');
	await wait(300);
	ok('4m copy-link announces success (or falls back)', /copied/i.test(await p.$eval('[data-toast]', (t) => t.textContent)));
}

// ---- 5. Legal pages + footer links
{
	for (const slug of ['privacy-policy', 'terms-and-conditions', 'cookie-policy']) {
		const { p } = await open(`${B}/${slug}/`);
		const words = await p.$eval('.entry-content', (e) => e.textContent.trim().split(/\s+/).length);
		const h2 = (await p.$$('.entry-content h2')).length;
		ok(`5 /${slug}/ loads with substantial content`, words > 400 && h2 >= 8 && (await p.$$('h1')).length === 1, `${words} words, ${h2} sections`);
	}
	const { p } = await open(B + '/');
	const links = await p.$$eval('.footer-legal a', (a) => a.map((x) => x.getAttribute('href')));
	ok('5b footer legal links (3)', links.length === 3 && ['privacy-policy', 'terms-and-conditions', 'cookie-policy'].every((s) => links.some((l) => l.includes(s))), links.join(' '));
	ok('5c footer has persistent Cookie settings control', (await p.$$('.footer-cookie-settings')).length === 1);
}

// ---- 6. Cookie consent
{
	let { p } = await open(B + '/');
	await wait(300);
	ok('6 banner visible on first visit', await visible(p, '[data-cookie-banner]'));
	const pos = await p.$eval('[data-cookie-banner]', (e) => { const r = e.getBoundingClientRect(); return { center: Math.round(r.left + r.width / 2), vw: document.documentElement.clientWidth, bottomGap: Math.round(innerHeight - r.bottom), w: Math.round(r.width) }; });
	ok('6b banner bottom-centered, max-width ok', Math.abs(pos.center - pos.vw / 2) <= 1 && pos.bottomGap < 40 && pos.w <= 760, JSON.stringify(pos));
	ok('6c three actions + policy link', (await p.$$('[data-cookie-banner] button')).length === 3 && (await p.$eval('[data-cookie-banner] a', (a) => /cookie-policy/.test(a.href))));
	ok('7 no analytics request before consent', gaRequests.length === 0, `${gaRequests.length} requests`);
	ok('7b no consent cookie stored before choice', (await consentCookie(p)) === null);
	// Reject optional
	await p.click('[data-cookie-banner] [data-cookie-reject]');
	let c = await consentCookie(p);
	ok('8 Reject optional saves a=0 m=0 with version + timestamp', c && c.a === 0 && c.m === 0 && c.v === 1 && c.n === 1 && !!c.t, JSON.stringify(c));
	ok('8b banner hidden after choice', !(await visible(p, '[data-cookie-banner]')));
	ok('8c still no analytics request after reject', gaRequests.length === 0);
	await p.reload({ waitUntil: 'networkidle0' });
	await wait(300);
	ok('9 banner stays hidden after refresh', !(await visible(p, '[data-cookie-banner]')));
	await p.goto(B + '/privacy-policy/', { waitUntil: 'networkidle0' });
	ok('9b and on another page', !(await visible(p, '[data-cookie-banner]')) && (await consentCookie(p)).a === 0);
	// Footer reopen + save with analytics
	await p.click('.footer-cookie-settings');
	await wait(200);
	ok('10 footer link reopens settings dialog', await p.$eval('[data-cookie-dialog]', (e) => e.open));
	ok('10b necessary is checked+disabled; analytics/marketing reflect saved (off)', await p.evaluate(() => { const g = (n) => document.querySelector(`#cookie-${n}`); return g('necessary').checked && g('necessary').disabled && !g('analytics').checked && !g('marketing').checked; }));
	ok('10c focus moved inside dialog', await p.evaluate(() => !!document.activeElement.closest('dialog')));
	await p.keyboard.press('Escape');
	await wait(200);
	ok('10d Escape closes dialog without changing choice', !(await p.$eval('[data-cookie-dialog]', (e) => e.open)) && (await consentCookie(p)).a === 0);
	ok('10e focus returns to footer control', await p.evaluate(() => document.activeElement.classList.contains('footer-cookie-settings')));
	await p.click('.footer-cookie-settings');
	await wait(200);
	await p.click('div.cookie-option:nth-of-type(2) .check'); // analytics
	await p.click('[data-cookie-form] button[type="submit"]');
	await wait(600);
	c = await consentCookie(p);
	ok('11 Save preferences stores analytics on, marketing off', c && c.a === 1 && c.m === 0, JSON.stringify(c));
	ok('11b analytics tag requested only now (after consent)', gaRequests.length >= 1 && gaRequests.some((u) => u.includes('G-TEST123456')), gaRequests[0]);
	// revoke: fake first-party GA cookie must be removed
	await p.evaluate(() => { document.cookie = '_ga=GA1.1.123.456; Path=/'; document.cookie = '_ga_TEST123456=GS1.1.x; Path=/'; });
	await p.click('.footer-cookie-settings');
	await wait(200);
	ok('12 settings show analytics currently on', await p.$eval('#cookie-analytics', (e) => e.checked));
	await p.click('div.cookie-option:nth-of-type(2) .check');
	await p.click('[data-cookie-form] button[type="submit"]');
	await wait(400);
	c = await consentCookie(p);
	const left = (await p.cookies()).filter((x) => /^_ga/.test(x.name));
	ok('12b revoking analytics: stored a=0, GA disabled flag set, _ga cookies removed', c.a === 0 && (await p.evaluate(() => window['ga-disable-G-TEST123456'] === true)) && left.length === 0, `remaining GA cookies: ${left.length}`);
	// Accept all
	const ctx2 = await open(B + '/');
	await ctx2.p.click('[data-cookie-banner] [data-cookie-accept]');
	const c2 = await consentCookie(ctx2.p);
	ok('13 Accept all stores a=1 m=1', c2.a === 1 && c2.m === 1);
	// consent-gated script placeholder
	const gated = await ctx2.p.evaluate(() => {
		const s = document.createElement('script');
		s.type = 'text/plain';
		s.dataset.consent = 'marketing';
		s.text = 'window.__gatedRan = true;';
		document.body.appendChild(s);
		return window.__gatedRan === true;
	});
	ok('13b gated script does not run on its own', gated === false);
	// Keyboard: tab order reaches banner buttons; banner doesn't trap
	const kb = await open(B + '/');
	await kb.p.focus('[data-back-to-top]');
	await kb.p.keyboard.press('Tab');
	await kb.p.keyboard.press('Tab');
	await kb.p.keyboard.press('Tab');
	const hits = new Set();
	for (let i = 0; i < 25; i += 1) {
		await kb.p.keyboard.press('Tab');
		hits.add(await kb.p.evaluate(() => (document.activeElement.closest('[data-cookie-banner]') ? 'banner' : 'other')));
	}
	ok('14 banner controls are keyboard reachable and not a trap', hits.has('banner') && hits.has('other'));
	await kb.p.click('[data-cookie-banner] [data-cookie-open]');
	await wait(200);
	let escaped = 0;
	for (let i = 0; i < 12; i += 1) {
		await kb.p.keyboard.press('Tab');
		if (await kb.p.evaluate(() => document.activeElement !== document.body && !document.activeElement.closest('dialog'))) escaped += 1;
	}
	ok('14b modal dialog keeps focus inside while open', escaped === 0);
	await kb.p.keyboard.press('Escape');
	// Mobile layout
	const mob = await open(B + '/', 390, 800);
	await wait(300);
	const m = await mob.p.$eval('[data-cookie-banner]', (e) => { const r = e.getBoundingClientRect(); return { l: r.left, r: innerWidth - r.right, h: r.height }; });
	ok('15 mobile banner fits viewport (no overflow), leaves content visible', m.l >= 0 && m.r >= 0 && m.h < 420 && !(await mob.p.evaluate(() => document.documentElement.scrollWidth > innerWidth)), JSON.stringify(m));
}

console.log(res.join('\n'));
console.log('Console/page errors:', errors.length ? errors.join(' | ') : 'none');
await browser.close();
