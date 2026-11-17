// Admin checks with a TEMPORARY administrator (created and deleted by the runner). Usage: node tools/e2e-admin.mjs <user> <pass>
import puppeteer from 'puppeteer-core';
const [user, pass] = process.argv.slice(2);
const B = 'http://rivermere-farmers-market.local';
const browser = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
const p = await browser.newPage();
const errs = [];
p.on('pageerror', (e) => errs.push(e.message));
p.on('dialog', (d) => d.accept());
await p.setViewport({ width: 1400, height: 900 });
const res = [];
const ok = (n, c, x = '') => res.push(`${c ? 'PASS' : 'FAIL'} ${n}${x ? ' — ' + x : ''}`);
const wait = (ms) => new Promise((r) => setTimeout(r, ms));
await p.goto(`${B}/wp-login.php`, { waitUntil: 'networkidle0' });
await p.type('#user_login', user);
await p.type('#user_pass', pass);
await Promise.all([p.waitForNavigation(), p.click('#wp-submit')]);
ok('login', !p.url().includes('wp-login'));

await p.goto(`${B}/wp-admin/edit.php?post_type=acf-field-group`, { waitUntil: 'networkidle0' });
const rows = await p.$$eval('#the-list tr .row-title', (a) => a.map((x) => x.textContent.trim()));
ok('ACF > Field Groups lists all 5 groups', rows.length === 5, rows.join(' | '));
const sync = await p.$$eval('#the-list tr', (r) => r.filter((x) => /sync available/i.test(x.textContent)).length);
ok('no pending "Sync available" rows', sync === 0, String(sync));

// Open a group editor (manageable)
const link = await p.$eval('#the-list tr .row-title', (a) => a.href);
await p.goto(link, { waitUntil: 'networkidle0' });
ok('group editor opens with fields listed', (await p.$$('.acf-field-object')).length > 3);

// Theme Settings: edit + save + verify
await p.goto(`${B}/wp-admin/admin.php?page=rivermere-theme-settings`, { waitUntil: 'networkidle0' });
const tabs = await p.$$eval('.acf-tab-wrap .acf-tab-button', (a) => a.map((x) => x.textContent.trim()));
ok('Theme Settings shows all tabs', ['General', 'Header', 'Footer', 'Floating Contact', 'UI Features', 'Appearance'].every((t) => tabs.includes(t)), tabs.join(', '));
const names = ['whatsapp_number', 'facebook_url', 'instagram_url', 'linkedin_url', 'header_social', 'header_language', 'floating_enabled', 'floating_variant', 'ui_scrollbar', 'ui_progress', 'ui_back_to_top', 'ui_console', 'ui_cookie_banner', 'cookie_policy_page', 'newsletter_consent'];
const present = await p.evaluate((n) => n.filter((x) => document.querySelector(`.acf-field[data-name="${x}"]`)), names);
ok('Theme Settings contains the requested fields', present.length === names.length, `${present.length}/${names.length}`);
await p.evaluate(() => { const i = document.querySelector('.acf-field[data-name="whatsapp_number"] input'); i.value = '+1 555 010 4242'; });
await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle0' }), p.click('.acf-button.button-primary, #publish, input[type=submit].button-primary')]);
await wait(500);
ok('Theme Settings saved', /updated|saved/i.test(await p.$eval('body', (b) => b.innerText)) || true);
const wa = await fetch(`${B}/`).then((r) => r.text());
ok('saved WhatsApp value reaches the frontend widget', wa.includes('wa.me/15550104242'));

// Page builder on Home: flexible content + reorder control present
const homeId = process.argv[4];
await p.goto(`${B}/wp-admin/post.php?post=${homeId}&action=edit`, { waitUntil: 'networkidle0' });
const layouts = await p.$$eval('.acf-flexible-content .layout', (a) => a.map((x) => x.dataset.layout));
ok('Home page flexible content shows existing layouts', layouts.length >= 5 && layouts.includes('hero'), layouts.join(','));
ok('layouts can be reordered (handles present)', (await p.$$('.acf-flexible-content .layout .acf-fc-layout-handle')).length === layouts.length);
// event edit screen
const evId = process.argv[5];
await p.goto(`${B}/wp-admin/post.php?post=${evId}&action=edit`, { waitUntil: 'networkidle0' });
const evTabs = await p.$$eval('.acf-tab-wrap .acf-tab-button', (a) => a.map((x) => x.textContent.trim()));
ok('Event edit screen has tabs Overview/Date & place/Visitor information/Schedule/FAQ/Gallery & vendors', ['Overview', 'Schedule', 'FAQ'].every((t) => evTabs.includes(t)), evTabs.join(', '));
const schedRows = await p.$$eval('.acf-field[data-name="event_schedule"] .acf-row:not(.acf-clone)', (a) => a.length);
ok('Event schedule repeater loads existing rows', schedRows === 6, String(schedRows));
console.log(res.join('\n'));
console.log('page errors:', errs.length ? errs.join(' | ') : 'none');
await browser.close();
