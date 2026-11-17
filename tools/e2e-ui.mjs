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
const logs = [];
const open = async (w, h = 800, opts = {}) => {
	const p = await browser.newPage();
	p.on('pageerror', (e) => errors.push(e.message));
	p.on('console', (m) => {
		if (m.type() === 'error') errors.push(m.text());
		if (m.type() === 'log') logs.push(m.text());
	});
	if (opts.reduced) await p.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
	await p.setViewport({ width: w, height: h });
	await p.goto(opts.url || B, { waitUntil: 'networkidle0' });
	return p;
};
const attr = (p, sel, name) => p.$eval(sel, (e, n) => e.getAttribute(n), name);

let p = await open(1280);
ok('1 console welcome logged once', logs.filter((l) => l.includes('RIVERMERE FARMERS MARKET')).length === 1);
ok('1b console message has no technical/secret content', !/password|token|nonce|debug/i.test(logs.join(' ')));
const sb = await p.evaluate(() => innerWidth - document.documentElement.clientWidth);
ok('3 scrollbar is 6px wide', sb === 6, String(sb));
ok('4 progress is 3px high and starts at 0%', await p.evaluate(() => {
	const b = document.querySelector('[data-scroll-progress]');
	return b.getBoundingClientRect().height === 3 && b.style.width === '0%';
}));
ok('4b progress fixed at top, above header', await p.evaluate(() => {
	const w = document.querySelector('.scroll-progress');
	const s = getComputedStyle(w);
	return s.position === 'fixed' && w.getBoundingClientRect().top === 0 && +s.zIndex > +getComputedStyle(document.querySelector('.site-header')).zIndex;
}));
await p.evaluate(() => scrollTo(0, document.documentElement.scrollHeight));
await wait(500);
ok('4c progress reaches 100%', (await p.$eval('[data-scroll-progress]', (b) => b.style.width)) === '100%');
await p.evaluate(() => scrollTo(0, (document.documentElement.scrollHeight - innerHeight) / 2));
await wait(400);
const mid = await p.$eval('[data-scroll-progress]', (b) => parseFloat(b.style.width));
ok('4d progress mid-scroll about 50%', mid > 45 && mid < 55, mid.toFixed(1));

await p.evaluate(() => scrollTo(0, 0));
await wait(200);
const t0 = await p.$eval('.contact-widget', (e) => Math.round(e.getBoundingClientRect().top));
await p.evaluate(() => scrollTo(0, 1500));
await wait(300);
const t1 = await p.$eval('.contact-widget', (e) => Math.round(e.getBoundingClientRect().top));
ok('14 widget fixed while scrolling', t0 === t1, `${t0}/${t1}`);
const w = await p.$eval('.contact-widget', (e) => e.getBoundingClientRect().width);
ok('15 widget collapsed width <= 50px', w <= 50, w + 'px');
ok('15b no horizontal overflow (desktop)', !(await p.evaluate(() => document.documentElement.scrollWidth > innerWidth)));
const links = await p.$$eval('.contact-widget__link', (a) => a.map((x) => x.getAttribute('href')));
ok('17 tel and mailto schemes', links.some((h) => /^tel:\+?\d+$/.test(h)) && links.some((h) => /^mailto:[^@]+@[^@]+$/.test(h)), links.join(' '));
ok('18 whatsapp wa.me digits only', links.includes('https://wa.me/447700900123'));
const waAttrs = await p.$eval('.contact-widget__link--whatsapp', (a) => a.target + '|' + a.rel);
ok('18b whatsapp opens in new tab safely', waAttrs === '_blank|noopener noreferrer', waAttrs);

const labelOpacity = (sel) => p.$eval(sel, (e) => getComputedStyle(e).opacity);
ok('16 label hidden by default', (await labelOpacity('.contact-widget__link--phone .contact-widget__label')) === '0');
await p.hover('.contact-widget__link--phone');
await wait(400);
ok('16b label visible on hover', (await labelOpacity('.contact-widget__link--phone .contact-widget__label')) === '1');
await p.mouse.move(10, 10);
await wait(300);
// Keyboard focus: tab into the widget from the last footer control.
await p.focus('[data-back-to-top]');
await p.keyboard.press('Tab');
await p.keyboard.press('Tab');
await p.keyboard.press('Tab');
await wait(400);
const focused = await p.evaluate(() => document.activeElement.className);
ok('16c label visible on keyboard focus', (await p.evaluate(() => getComputedStyle(document.activeElement.querySelector('.contact-widget__label') || document.body).opacity)) === '1', focused);
ok('16d width unchanged while label shown', (await p.$eval('.contact-widget', (e) => e.getBoundingClientRect().width)) <= 50);

await p.evaluate(() => scrollTo(0, document.documentElement.scrollHeight));
await wait(300);
await p.click('[data-back-to-top]');
await wait(1800);
ok('6 back to top scrolls to 0', (await p.evaluate(() => scrollY)) === 0);

ok('11 header has 3 social controls', (await p.$$('.utility-bar .social-list__link')).length === 3);
ok('12 footer has 3 social controls', (await p.$$('.footer-contact .social-list__link')).length === 3);
const fb = await p.$eval('.footer-contact a.social-list__link[href*="facebook"]', (a) => `${a.target}|${a.rel}`);
ok('13 configured social opens in new tab safely', fb === '_blank|noopener noreferrer', fb);
const insta = await p.$('.utility-bar button.social-list__link.is-soon');
await insta.click();
await wait(300);
const toast = await p.$eval('[data-toast]', (t) => t.textContent);
ok('13b unconfigured social shows coming soon, no navigation', /coming soon/i.test(toast) && p.url().startsWith(B), toast);

const urlBefore = p.url();
await p.focus('[data-lang-toggle]');
await p.keyboard.press('Enter');
await wait(150);
ok('9 dropdown opens by keyboard (aria-expanded)', (await attr(p, '[data-lang-toggle]', 'aria-expanded')) === 'true');
const opts = await p.$$eval('[data-lang-option]', (o) => o.map((x) => x.textContent.replace(/\s+/g, ' ').trim()));
ok('8 six languages listed', opts.length === 6, opts.join(' | '));
ok('8b default is EN', (await p.$eval('[data-lang-current]', (e) => e.textContent)) === 'EN');
await p.keyboard.press('ArrowDown');
await wait(50);
ok('9b arrow key moves focus', (await p.evaluate(() => document.activeElement.dataset.langOption)) === 'fr');
await p.keyboard.press('Escape');
await wait(100);
ok('9c Escape closes and returns focus', (await attr(p, '[data-lang-toggle]', 'aria-expanded')) === 'false' && (await p.evaluate(() => document.activeElement.hasAttribute('data-lang-toggle'))));
await p.click('[data-lang-toggle]');
await wait(100);
await p.mouse.click(600, 400);
await wait(100);
ok('9d outside click closes', (await attr(p, '[data-lang-toggle]', 'aria-expanded')) === 'false');
await p.click('[data-lang-toggle]');
await p.click('[data-lang-option="fr"]');
await wait(300);
const soon = /coming soon/i.test(await p.$eval('[data-toast]', (t) => t.textContent));
const still = (await p.$eval('html', (h) => h.lang)).startsWith('en') && (await p.$eval('[data-lang-current]', (e) => e.textContent)) === 'EN' && p.url() === urlBefore;
ok('10 French shows coming soon; page stays English, no navigation', soon && still);

const r = await open(1280, 800, { reduced: true });
await r.evaluate(() => scrollTo(0, document.documentElement.scrollHeight));
await wait(300);
await r.click('[data-back-to-top]');
await wait(80);
ok('7 reduced motion: instant scroll to top', (await r.evaluate(() => scrollY)) === 0);

const m = await open(390, 800);
ok('19 mobile: rail collapsed behind toggle', (await m.$eval('.contact-widget__list', (e) => getComputedStyle(e).display)) === 'none' && (await m.$eval('[data-contact-toggle]', (e) => e.getBoundingClientRect().width)) <= 50);
const box = await m.$eval('.contact-widget', (e) => {
	const rc = e.getBoundingClientRect();
	return [Math.round(rc.width), Math.round(rc.height)];
});
ok('19b mobile widget is a small corner button', box[0] <= 50 && box[1] <= 50, box.join('x'));
await m.click('[data-contact-toggle]');
await wait(300);
ok('19c mobile toggle reveals actions', (await m.$eval('.contact-widget__list', (e) => getComputedStyle(e).display)) === 'block');
ok('19d no horizontal overflow on mobile', !(await m.evaluate(() => document.documentElement.scrollWidth > innerWidth)));
await m.keyboard.press('Escape');
await wait(100);
ok('19e Escape closes mobile widget', (await m.$eval('.contact-widget__list', (e) => getComputedStyle(e).display)) === 'none');

const s = await open(1280, 3000, { url: B + '/nope-xyz/' });
ok('4e short page: progress stays 0%', (await s.$eval('[data-scroll-progress]', (b) => b.style.width)) === '0%');

console.log(res.join('\n'));
console.log('JS errors:', errors.length ? errors.join(' | ') : 'none');
await browser.close();
