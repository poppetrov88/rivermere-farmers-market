// Horizontal-overflow + overlap sanity check at the required widths. Run: node tools/responsive.mjs
import puppeteer from 'puppeteer-core';

const B = 'http://rivermere-farmers-market.local';
const widths = [320, 360, 375, 390, 430, 768, 1024, 1280, 1440, 1920];
const paths = process.argv.slice(2).length ? process.argv.slice(2) : ['/', '/vendors/', '/vendors/greenfield-farm/', '/events/', '/events/harvest-festival/', '/become-a-vendor/', '/about-us/', '/privacy-policy/'];
const browser = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
await browser.setCookie({ name: 'rm_consent', value: encodeURIComponent(JSON.stringify({ v: 1, n: 1, a: 0, m: 0, t: new Date().toISOString() })), domain: 'rivermere-farmers-market.local', path: '/' });
const page = await browser.newPage();
let bad = 0;
let checks = 0;
for (const path of paths) {
	const row = [];
	for (const w of widths) {
		await page.setViewport({ width: w, height: 900 });
		await page.goto(B + path, { waitUntil: 'networkidle0' });
		const r = await page.evaluate(() => {
			const overflow = document.documentElement.scrollWidth - innerWidth;
			const wide = [...document.querySelectorAll('main *, footer *, header *')].filter((e) => { const b = e.getBoundingClientRect(); return b.width > 0 && b.right > innerWidth + 1 && !e.closest('.hero, .image-text, .newsletter, .site-main--front, .contact-widget, .screen-reader-text, svg') && !e.classList.contains('check__input') && getComputedStyle(e).position !== 'fixed'; }).slice(0, 2).map((e) => e.className || e.tagName);
			const tiny = [...document.querySelectorAll('a, button, input:not([type=hidden]), select, textarea, summary')].filter((e) => { const b = e.getBoundingClientRect(); const s = getComputedStyle(e); return b.width > 0 && s.visibility !== 'hidden' && (b.height < 24 || b.width < 24) && !e.classList.contains('screen-reader-text') && !e.closest('.screen-reader-text') && !e.classList.contains('check__input') && e.getAttribute('tabindex') !== '-1' && !e.closest('[aria-hidden="true"]') && !(e.tagName === 'A' && getComputedStyle(e).display === 'inline'); }).length;
			return { overflow, wide, tiny };
		});
		checks += 1;
		if (r.overflow > 0 || r.wide.length) {
			bad += 1;
			row.push(`${w}px overflow=${r.overflow} ${r.wide.join('|')}`);
		}
	}
	console.log(`${path.padEnd(28)} ${row.length ? 'PROBLEMS: ' + row.join('; ') : 'ok at all ' + widths.length + ' widths'}`);
}
console.log(bad ? `${bad} of ${checks} page/width combinations have overflow` : `no horizontal overflow in ${checks} page/width combinations`);
await browser.close();
