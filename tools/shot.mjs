// Usage: node tools/shot.mjs <url> <width> <out.png> [fullPage=1]
import puppeteer from 'puppeteer-core';
const [url, width = '1024', out = 'tools/out/shot.png', full = '1'] = process.argv.slice(2);
const browser = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true, args: ['--ignore-certificate-errors'] });
const page = await browser.newPage();
const logs = [];
page.on('console', (m) => logs.push(`${m.type()}: ${m.text()}`));
page.on('pageerror', (e) => logs.push('pageerror: ' + e.message));
page.on('requestfailed', (r) => logs.push('requestfailed: ' + r.url()));
await page.setViewport({ width: +width, height: 900, deviceScaleFactor: 1 });
await page.goto(url, { waitUntil: 'networkidle0' });
await page.screenshot({ path: out, fullPage: full === '1' });
console.log('overflow:', await page.evaluate(() => document.documentElement.scrollWidth > innerWidth));
console.log('height:', await page.evaluate(() => document.documentElement.scrollHeight));
console.log(logs.join('\n') || 'no console output');
await browser.close();
