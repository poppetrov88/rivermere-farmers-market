import puppeteer from 'puppeteer-core';
const [url, width = '1024', sel = ''] = process.argv.slice(2);
const browser = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
const page = await browser.newPage();
await page.setViewport({ width: +width, height: 900 });
await page.goto(url, { waitUntil: 'networkidle0' });
const res = await page.evaluate((sel) => {
  const out = { vw: innerWidth, docW: document.documentElement.scrollWidth, wide: [] };
  document.querySelectorAll('body *').forEach((el) => {
    const r = el.getBoundingClientRect();
    if (r.right > innerWidth + 1 && r.width > 0) out.wide.push(`${el.tagName}.${(el.className && el.className.baseVal === undefined ? el.className : '')} right=${Math.round(r.right)}`);
  });
  out.wide = out.wide.slice(0, 12);
  if (sel) out.sel = [...document.querySelectorAll(sel)].map((e) => { const r = e.getBoundingClientRect(); return `${sel}: x=${Math.round(r.x)} y=${Math.round(r.y)} w=${Math.round(r.width)} h=${Math.round(r.height)}`; });
  return out;
}, sel);
console.log(JSON.stringify(res, null, 1));
await browser.close();
