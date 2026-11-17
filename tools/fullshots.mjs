// Usage: node tools/fullshots.mjs <width> <name=path> ...   (consent pre-set, lazy images loaded)
import puppeteer from 'puppeteer-core';
const [w, ...pairs] = process.argv.slice(2);
const b = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
await b.setCookie({ name: 'rm_consent', value: encodeURIComponent(JSON.stringify({ v: 1, n: 1, a: 0, m: 0, t: new Date().toISOString() })), domain: 'rivermere-farmers-market.local', path: '/' });
const g = await b.newPage();
await g.setViewport({ width: +w, height: 900 });
for (const pair of pairs) {
	const [name, path] = pair.split('=');
	await g.goto('http://rivermere-farmers-market.local' + path, { waitUntil: 'networkidle0' });
	await g.evaluate(async () => { for (let y = 0; y < document.documentElement.scrollHeight; y += 600) { window.scrollTo({ top: y, behavior: 'instant' }); await new Promise((r) => setTimeout(r, 60)); } window.scrollTo({ top: 0, behavior: 'instant' }); });
	await new Promise((r) => setTimeout(r, 500));
	await g.screenshot({ path: `tools/out/${name}-full.png`, fullPage: true });
}
await b.close();
