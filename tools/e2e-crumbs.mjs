// Breadcrumb + spacing checks. Needs temp QA pages: /about-us/ and /about-us/our-story/ (parent/child).
import puppeteer from 'puppeteer-core';

const B = 'http://rivermere-farmers-market.local';
const browser = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
await browser.setCookie({ name: 'rm_consent', value: encodeURIComponent(JSON.stringify({ v: 1, n: 1, a: 0, m: 0, t: new Date().toISOString() })), domain: 'rivermere-farmers-market.local', path: '/' });
const res = [];
const ok = (n, c, x = '') => res.push(`${c ? 'PASS' : 'FAIL'} ${n}${x ? ' — ' + x : ''}`);
const wait = (ms) => new Promise((r) => setTimeout(r, ms));
const errors = [];
const page = await browser.newPage();
page.on('pageerror', (e) => errors.push(e.message));
page.on('console', (m) => m.type() === 'error' && !/404/.test(m.text()) && errors.push(m.text()));
const go = async (path, w = 1280) => {
	await page.setViewport({ width: w, height: 900 });
	await page.goto(B + path, { waitUntil: 'networkidle0' });
};
const trail = () => page.$$eval('.breadcrumbs__item', (a) => a.map((x) => x.textContent.trim()).join(' / '));

// 1/2: presence + homepage absence
await go('/');
ok('2 homepage has no breadcrumbs', (await page.$$('.breadcrumbs, .breadcrumbs-bar')).length === 0);
const inner = { '/about-us/': 'Home / About Us', '/about-us/our-story/': 'Home / About Us / Our Story', '/events/': 'Home / Events', '/events/harvest-festival/': 'Home / Events / Harvest Festival', '/vendors/': 'Home / Vendors', '/vendors/greenfield-farm/': 'Home / Vendors / GreenField Farm', '/blog/': 'Home / Blog', '/qa-temp-post/': 'Home / Market News / QA Temp Post', '/category/market-news/': 'Home / Market News', '/?s=honey': 'Home / Search Results', '/nope-xyz/': 'Home / Page not found', '/privacy-policy/': 'Home / Privacy Policy', '/terms-and-conditions/': 'Home / Terms and Conditions', '/cookie-policy/': 'Home / Cookie Policy' };
for (const [path, expected] of Object.entries(inner)) {
	await go(path);
	const t = await trail();
	const navs = await page.$$eval('nav[aria-label]', (n) => n.filter((x) => /breadcrumb/i.test(x.getAttribute('aria-label'))).length);
	ok(`1 ${path} → ${expected}`, t === expected && navs === 1, `${t} (breadcrumb navs: ${navs})`);
}

// a11y structure on an event page
await go('/events/harvest-festival/');
const a11y = await page.evaluate(() => {
	const nav = document.querySelector('nav.breadcrumbs');
	return {
		label: nav.getAttribute('aria-label'),
		ol: nav.querySelector('ol') !== null,
		nestedNav: nav.querySelectorAll('nav').length,
		current: [...nav.querySelectorAll('[aria-current]')].map((x) => `${x.getAttribute('aria-current')}:${x.textContent.trim()}`),
		linksInCurrent: nav.querySelector('[aria-current] a') !== null,
		h1Count: document.querySelectorAll('h1').length,
		bar: Math.round(document.querySelector('.breadcrumbs-bar').getBoundingClientRect().height),
		barLeft: Math.round(document.querySelector('.breadcrumbs').getBoundingClientRect().left),
		brandLeft: Math.round(document.querySelector('.brand').getBoundingClientRect().left),
		size: getComputedStyle(document.querySelector('.breadcrumbs')).fontSize,
	};
});
ok('9 nav labelled "Breadcrumb", ordered list, no nested nav', a11y.label === 'Breadcrumb' && a11y.ol && a11y.nestedNav === 0);
ok('9b current page marked aria-current="page" (not a link)', a11y.current.length === 1 && a11y.current[0] === 'page:Harvest Festival' && !a11y.linksInCurrent, a11y.current.join());
ok('1b breadcrumb aligned with header container', a11y.barLeft === a11y.brandLeft, `${a11y.barLeft} vs ${a11y.brandLeft}`);
ok('1c bar height about 16–24px padding (≈ 60–80px total)', a11y.bar >= 56 && a11y.bar <= 84, `${a11y.bar}px, font ${a11y.size}`);

// keyboard + navigation
await page.focus('.breadcrumbs__link');
const ring = await page.evaluate(() => { const s = getComputedStyle(document.activeElement); return `${s.outlineStyle} ${s.outlineWidth}`; });
ok('9c visible keyboard focus on links', /solid 3px/.test(ring), ring);
await page.keyboard.press('Tab');
ok('9d Tab moves to next crumb link', await page.evaluate(() => document.activeElement.classList.contains('breadcrumbs__link') && document.activeElement.textContent.trim() === 'Events'));
await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.keyboard.press('Enter')]);
ok('6 "Events" crumb navigates to the events archive', page.url().endsWith('/events/') && (await trail()) === 'Home / Events');
await go('/about-us/our-story/');
await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('.breadcrumbs__link:nth-of-type(1)').catch(() => {}), page.evaluate(() => document.querySelectorAll('.breadcrumbs__link')[1].click())]).catch(() => {});
ok('6b parent crumb navigates to the parent page', page.url().endsWith('/about-us/'), page.url());
await go('/vendors/greenfield-farm/');
await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.evaluate(() => document.querySelector('.breadcrumbs__link').click())]);
ok('6c Home crumb navigates home', page.url() === B + '/');

// Schema: single BreadcrumbList
await go('/events/harvest-festival/');
const lds = await page.$$eval('script[type="application/ld+json"]', (s) => s.map((x) => x.textContent));
const lists = lds.join('').match(/"@type":"BreadcrumbList"/g) || [];
ok('8 exactly one BreadcrumbList in JSON-LD', lists.length === 1, String(lists.length));

// Responsive
for (const w of [1440, 1024, 768, 390, 320]) {
	await go('/about-us/our-story/', w);
	ok(`9e ${w}px: no horizontal overflow`, !(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)));
}
await go('/events/harvest-festival/', 320);
const wrap = await page.$eval('.breadcrumbs__list', (e) => ({ h: Math.round(e.getBoundingClientRect().height), w: e.scrollWidth <= e.clientWidth }));
ok('9f long trail wraps on 320px without overflow', wrap.w, JSON.stringify(wrap));

// Spacing
const metrics = async (path, w) => {
	await go(path, w);
	return page.evaluate(() => {
		const px = (sel, prop) => { const e = document.querySelector(sel); return e ? parseFloat(getComputedStyle(e)[prop]) : null; };
		return {
			cats: [px('.section--categories', 'paddingTop'), px('.section--categories', 'paddingBottom')],
			imageText: [px('.section--image-text', 'paddingTop'), px('.section--image-text', 'paddingBottom')],
			event: px('.section--event', 'paddingBottom'),
			vendors: [px('.section--vendors', 'paddingTop'), px('.section--vendors', 'paddingBottom')],
			newsletter: px('.newsletter', 'paddingTop'),
			footer: px('.footer-main', 'paddingTop'),
			tokens: ['sm', 'md', 'lg', 'xl', 'page'].map((k) => getComputedStyle(document.documentElement).getPropertyValue(`--section-space-${k}`).trim()),
		};
	});
};
const d1440 = await metrics('/', 1440);
const d1024 = await metrics('/', 1024);
const t768 = await metrics('/', 768);
const m390 = await metrics('/', 390);
console.log('desktop 1440', JSON.stringify(d1440));
console.log('desktop 1024', JSON.stringify(d1024));
console.log('tablet 768  ', JSON.stringify(t768));
console.log('mobile 390  ', JSON.stringify(m390));
ok('10 desktop standard sections 88–104px (md token)', d1440.cats[0] >= 88 && d1440.cats[0] <= 104 && d1024.cats[0] >= 88 && d1440.vendors[0] === 0 && d1440.vendors[1] >= 88 && d1440.vendors[1] <= 104);
ok('10b desktop supporting sections 64–80px', d1440.event >= 64 && d1440.event <= 80 && d1440.cats[1] >= 64 && d1440.cats[1] <= 80);
ok('10c tablet sections 64–80px', t768.cats[0] >= 64 && t768.cats[0] <= 80 && t768.event >= 48 && t768.event <= 64);
ok('11 mobile sections 40–56px', m390.cats[0] >= 40 && m390.cats[0] <= 56 && m390.event >= 40 && m390.event <= 56);
await go('/events/harvest-festival/', 1440);
const ev = await page.evaluate(() => ({ layout: parseFloat(getComputedStyle(document.querySelector('.event-layout')).paddingTop), gap: [...document.querySelectorAll('.event-main > section')].slice(1, 3).map((s) => parseFloat(getComputedStyle(s).marginTop)) }));
ok('10d event layout padding 72–96px and block gaps ≥ 64px', ev.layout >= 72 && ev.layout <= 96 && ev.gap.every((g) => g >= 64), JSON.stringify(ev));
await go('/privacy-policy/', 1440);
const pg = await page.evaluate(() => parseFloat(getComputedStyle(document.querySelector('main .section')).paddingTop));
ok('10e inner page content spacing 72–96px', pg >= 72 && pg <= 96, `${pg}px`);
await go('/', 1440);
ok('12 homepage: no horizontal overflow, hero unchanged width column', !(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) && (await page.$eval('.hero__content', (e) => Math.round(e.getBoundingClientRect().width))) === 560);

console.log(res.join('\n'));
console.log('Console/page errors:', errors.length ? errors.join(' | ') : 'none');
await browser.close();
