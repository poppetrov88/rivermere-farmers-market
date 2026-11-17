// Admin tests with TEMPORARY users created/removed by this script.
// Run: WP_PS1=<wp.ps1> node tools/e2e-admin4.mjs
import puppeteer from 'puppeteer-core';
import { probe, wpEval, wpCli, clearThrottles } from './lib/wp.mjs';

const B = 'http://rivermere-farmers-market.local';
const res = [];
const ok = (n, c, x = '') => res.push(`${c ? 'PASS' : 'FAIL'} ${n}${x ? ' — ' + x : ''}`);
const wait = (ms) => new Promise((r) => setTimeout(r, ms));
const errors = [];
const ADMIN = ['qa_admin_tmp', 'Qa-' + Math.random().toString(36).slice(2) + Math.random().toString(36).slice(2) + '!9A']; // throwaway user, deleted at the end
const SUB = ['qa_sub_tmp', 'Qa-' + Math.random().toString(36).slice(2) + Math.random().toString(36).slice(2) + '!9A'];

wpCli('user', 'create', ADMIN[0], 'qa-admin@example.invalid', '--role=administrator', `--user_pass=${ADMIN[1]}`);
wpCli('user', 'create', SUB[0], 'qa-sub@example.invalid', '--role=subscriber', `--user_pass=${SUB[1]}`);
const browser = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
const login = async ([u, p]) => {
	const ctx = await browser.createBrowserContext();
	const page = await ctx.newPage();
	page.on('pageerror', (e) => errors.push(e.message));
	page.on('dialog', (d) => d.accept());
	await page.setViewport({ width: 1400, height: 900 });
	await page.goto(`${B}/wp-login.php`, { waitUntil: 'networkidle0' });
	await page.type('#user_login', u);
	await page.type('#user_pass', p);
	await Promise.all([page.waitForNavigation(), page.click('#wp-submit')]);
	return { page, ctx };
};
const text = (page) => page.$eval('body', (b) => b.innerText);

try {
	// ---- seed deterministic analytics rows across several days
	wpEval(`global $wpdb; $t = ri_table(); $wpdb->query("TRUNCATE TABLE $t");
$rows = array(
 array(0,'pageview','page','/about-us/',24,'page',30), array(0,'pageview','home','/',20,'home',50), array(3,'pageview','home','/',20,'home',12),
 array(40,'pageview','home','/',20,'home',7),
 array(0,'apply_cta','apply-now','/about-us/',24,'page',9), array(0,'tel','phone','/',20,'home',4), array(1,'social','facebook','/',20,'home',3),
 array(0,'application_submitted','vendor-application','',0,'server',2), array(2,'newsletter_subscribed','newsletter-new','',0,'server',5), array(0,'external','example.org','/events/',0,'archive',1));
foreach ($rows as $r) { $day = gmdate('Y-m-d', time() - $r[0] * DAY_IN_SECONDS); $wpdb->insert($t, array('row_hash'=>md5(wp_json_encode($r)),'day'=>$day,'event_type'=>$r[1],'event_key'=>$r[2],'page_path'=>$r[3],'page_id'=>$r[4],'page_type'=>$r[5],'hits'=>$r[6])); }
echo 'seeded';`);

	// ---- Administrator
	const { page, ctx } = await login(ADMIN);
	ok('login as administrator', !page.url().includes('wp-login'));
	const menu = await page.$$eval('#adminmenu a.menu-top', (a) => a.map((x) => x.textContent.trim().replace(/\s+/g, ' ')));
	ok('F2 top-level "Insights" and "Vendor Applications" menus exist', menu.some((m) => m.startsWith('Insights')) && menu.some((m) => m.startsWith('Vendor Applications')), menu.filter((m) => /Insights|Vendor/.test(m)).join(' | '));
	await page.goto(`${B}/wp-admin/admin.php?page=rivermere-insights`, { waitUntil: 'networkidle0' });
	const subs = await page.$$eval('#adminmenu a[href*="page=ri-"]', (a) => a.map((x) => x.textContent.trim()));
	ok('F2 submenus: Click Tracking, Pages, Conversions, Reports, Settings', ['Click Tracking', 'Pages', 'Conversions', 'Reports', 'Settings'].every((s) => subs.includes(s)), subs.join());
	let cards = await page.$$eval('.ri-card', (c) => Object.fromEntries(c.map((x) => [x.querySelector('.ri-card__label').textContent, x.querySelector('.ri-card__value').textContent])));
	ok('F3 default (30 days) totals come from stored data: views 92, clicks 17, applications 2, newsletter 5', cards['Total page views'] === '92' && cards['Tracked clicks'] === '17' && cards['Vendor applications submitted'] === '2' && cards['Newsletter subscriptions'] === '5', JSON.stringify(cards));
	ok('F3 contact clicks = 4, social = 3', cards['Contact clicks'] === '4' && cards['Social media clicks'] === '3');
	ok('F3 daily trend chart rendered (svg) with accessible title and data table', (await page.$$('.ri-chart svg[role="img"] polyline')).length === 3 && (await page.$$('.ri-table-details table')).length === 1);
	const top = await page.$$eval('.ri-two table:first-of-type tbody tr td:first-child', (t) => t.map((x) => x.textContent.trim().split('\n')[0]));
	ok('F3 most popular pages lists real pages (Home first)', top[0] && /Home/.test(top[0]), top.join(' | '));
	ok('F3 definitions distinguish views, clicks and confirmed conversions', /not unique visitors/i.test(await text(page)) && /not conversions/i.test(await text(page)));
	// filters
	await page.goto(`${B}/wp-admin/admin.php?page=rivermere-insights&range=today`, { waitUntil: 'networkidle0' });
	cards = await page.$$eval('.ri-card', (c) => Object.fromEntries(c.map((x) => [x.querySelector('.ri-card__label').textContent, x.querySelector('.ri-card__value').textContent])));
	ok('F3 filter "Today": views 80, applications 2', cards['Total page views'] === '80' && cards['Vendor applications submitted'] === '2', JSON.stringify(cards));
	await page.goto(`${B}/wp-admin/admin.php?page=rivermere-insights&range=7`, { waitUntil: 'networkidle0' });
	cards = await page.$$eval('.ri-card', (c) => Object.fromEntries(c.map((x) => [x.querySelector('.ri-card__label').textContent, x.querySelector('.ri-card__value').textContent])));
	ok('F3 filter "Last 7 days": views 92 (the 40-day-old row excluded)', cards['Total page views'] === '92', cards['Total page views']);
	await page.goto(`${B}/wp-admin/admin.php?page=rivermere-insights&range=90`, { waitUntil: 'networkidle0' });
	cards = await page.$$eval('.ri-card', (c) => Object.fromEntries(c.map((x) => [x.querySelector('.ri-card__label').textContent, x.querySelector('.ri-card__value').textContent])));
	ok('F3 filter "Last 90 days": includes the 40-day-old row (views 99)', cards['Total page views'] === '99', cards['Total page views']);
	const d = (n) => new Date(Date.now() - n * 864e5).toISOString().slice(0, 10);
	await page.goto(`${B}/wp-admin/admin.php?page=rivermere-insights&range=custom&from=${d(45)}&to=${d(35)}`, { waitUntil: 'networkidle0' });
	cards = await page.$$eval('.ri-card', (c) => Object.fromEntries(c.map((x) => [x.querySelector('.ri-card__label').textContent, x.querySelector('.ri-card__value').textContent])));
	ok('F3 custom range selects only that window (views 7)', cards['Total page views'] === '7', cards['Total page views']);
	await page.goto(`${B}/wp-admin/admin.php?page=rivermere-insights&range=custom&from=garbage&to=%3Cscript%3E`, { waitUntil: 'networkidle0' });
	ok('F3 invalid custom dates are ignored safely (page still renders)', (await page.$$('.ri-card')).length === 6);
	// sub pages
	for (const [slug, needle] of [['ri-clicks', 'apply-now'], ['ri-pages', '/about-us/'], ['ri-conversions', 'Vendor application']]) {
		await page.goto(`${B}/wp-admin/admin.php?page=${slug}`, { waitUntil: 'networkidle0' });
		ok(`F10 ${slug} shows stored data`, (await text(page)).includes(needle));
	}
	ok('F10 conversions page shows no invented conversion rate', !/%|rate/i.test((await text(page)).replace(/Conversions made without that consent[^.]*\./, '').replace(/no conversion rates are shown/i, '')));
	// export
	await page.goto(`${B}/wp-admin/admin.php?page=ri-reports&range=30`, { waitUntil: 'networkidle0' });
	const exportUrl = await page.$eval('a.button-primary', (a) => a.href);
	const csv = await page.evaluate(async (u) => { const r = await fetch(u, { credentials: 'same-origin' }); return { type: r.headers.get('content-type'), disp: r.headers.get('content-disposition'), body: await r.text(), status: r.status }; }, exportUrl);
	ok('F10 CSV export (nonce + capability): text/csv attachment with header + rows', csv.status === 200 && /text\/csv/.test(csv.type) && /attachment/.test(csv.disp) && csv.body.startsWith('day_utc,event_type') && csv.body.split('\n').length > 8, `${csv.body.split('\n').length} lines`);
	ok('F10 CSV respects the date range (40-day-old row excluded from last-30)', !/,7\s*$/m.test(csv.body.split('\n').filter((l) => /pageview/.test(l) && /\/,/.test(l) && l.startsWith(d(40))).join('')) && !csv.body.includes(d(40)));
	ok('F12 CSV contains no personal data columns', !/email|phone|name/i.test(csv.body.split('\n')[0]));
	const noNonce = await page.evaluate(async () => (await fetch('/wp-admin/admin-post.php?action=ri_export', { credentials: 'same-origin' })).status);
	ok('F10 export without nonce is rejected', noNonce === 403 || noNonce === 400, String(noNonce));
	// settings + reset
	await page.goto(`${B}/wp-admin/admin.php?page=ri-settings`, { waitUntil: 'networkidle0' });
	const labels = await page.$$eval('.form-table th', (t) => t.map((x) => x.textContent.trim()));
	ok('F11 settings: tracking, page views, CTA, contact, social, external, retention, exclusions, consent status', ['Tracking', 'Page views', 'CTA / button tracking', 'Contact tracking', 'Social tracking', 'External link and download tracking', 'Data retention', 'Exclude pages', 'Consent integration', 'Exclude administrators'].every((l) => labels.includes(l)), labels.join(' | '));
	ok('F11 consent integration status reports the cookie banner connection', /Connected to the Rivermere cookie banner/.test(await text(page)));
	await page.type('#ri-confirm', 'nope');
	await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('input.delete, button.delete, #submit[value="Delete all data"]')]).catch(() => {});
	ok('F11 reset needs explicit confirmation: wrong text deletes nothing', probe().events.pageview > 0);
	await page.goto(`${B}/wp-admin/admin.php?page=ri-settings`, { waitUntil: 'networkidle0' });
	await page.type('#ri-confirm', 'RESET');
	await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('input.delete, button.delete, #submit[value="Delete all data"]')]);
	ok('F11 reset with "RESET" deletes all analytics data', Object.keys(probe().events).length === 0);

	// ---- Vendor applications admin (E8)
	clearThrottles();
	const html = await (await fetch(B + '/become-a-vendor/')).text();
	const nonce = html.match(/"restNonce":"([a-z0-9]+)"/)[1];
	const mk = async (n, biz, email) => (await fetch(`${B}/wp-json/rivermere/v1/vendor-application`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce, Origin: B }, body: JSON.stringify({ business_name: biz, first_name: 'Pat', last_name: 'Q' + n, email, phone: '+1 555 010 02' + n + n, products: `Products ${n} <b>x</b>`, category: 'Fresh Produce', location: 'Rivermere', website: 'https://example.com', consent: true, company_website: '', rm_ts: String(Math.floor(Date.now() / 1000) - 20), submission_id: 'adm' + n + Date.now() }) })).status;
	const sts = [await mk(11, 'Alder Orchard', 'alder@example.com'), await mk(22, 'Birch Bakery', 'birch@example.com'), await mk(33, 'Cedar Honey', 'cedar@example.com')];
	ok('E8 three applications created through the public form', sts.every((s) => s === 200), sts.join());
	await page.goto(`${B}/wp-admin/edit.php?post_type=vendor_application`, { waitUntil: 'networkidle0' });
	const rows = await page.$$eval('#the-list tr', (r) => r.map((x) => x.textContent.replace(/\s+/g, ' ').trim()));
	ok('E8 admin list shows applications with applicant, products, status and submission date', rows.length === 3 && rows.every((r) => /New/.test(r)) && rows.some((r) => /alder@example.com/.test(r)), `${rows.length} rows`);
	ok('E8 products are escaped in the list (no raw HTML)', !(await page.$('#the-list b')));
	await page.goto(`${B}/wp-admin/edit.php?post_type=vendor_application&s=birch%40example.com`, { waitUntil: 'networkidle0' });
	ok('E8 search by applicant email finds exactly that application', (await page.$$eval('#the-list tr', (r) => r.length)) === 1 && /Birch Bakery/.test(await text(page)));
	// edit + status
	await page.goto(`${B}/wp-admin/edit.php?post_type=vendor_application&s=alder`, { waitUntil: 'networkidle0' });
	const editHref = await page.$eval('#the-list .row-title', (a) => a.href);
	await page.goto(editHref, { waitUntil: 'networkidle0' });
	const detail = await page.$eval('#rm_app_details', (e) => e.innerText);
	ok('E8 detail screen shows full application incl. consent record', /Alder Orchard/.test(detail) && /Privacy consent: yes/.test(detail) && /Rivermere|privacy/i.test(detail));
	ok('E7 publishing is not automatic: no vendor profile exists yet', Number(wpEval(`echo count(get_posts(array('post_type'=>'rm_vendor','post_status'=>array('publish','draft','private','pending','trash'),'title'=>'Alder Orchard','numberposts'=>-1)));`)) === 0);
	await page.select('#rm_app_status_field', 'approved');
	await page.type('#rm_app_note', 'QA note');
	await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('#publish')]);
	const status = wpEval(`$p = get_posts(array('post_type'=>'vendor_application','title'=>'Alder Orchard','numberposts'=>1,'post_status'=>array('publish','draft','private','pending','trash')))[0]; echo get_post_meta($p->ID,'_app_status',true);`);
	ok('E7 status update saved (Approved)', status === 'approved', status);
	ok('E7 explicit "Create DRAFT vendor profile" action offered only after approval', /Create DRAFT vendor profile/.test(await text(page)));
	await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('a.button[href*="rivermere_app_to_vendor"]')]);
	const vendor = wpEval(`$v = get_posts(array('post_type'=>'rm_vendor','post_status'=>array('publish','draft','private','pending','trash'),'title'=>'Alder Orchard','numberposts'=>1)); echo $v ? $v[0]->post_status : 'none';`);
	ok('E7 conversion creates a DRAFT vendor (never published automatically)', vendor === 'draft', vendor);
	// status filter
	await page.goto(`${B}/wp-admin/edit.php?post_type=vendor_application&app_status=approved`, { waitUntil: 'networkidle0' });
	ok('E8 filter by status "Approved" shows only that application', (await page.$$eval('#the-list tr', (r) => r.length)) === 1);
	await page.goto(`${B}/wp-admin/edit.php?post_type=vendor_application`, { waitUntil: 'networkidle0' });
	const exp = await page.$eval('a.button[href*="rivermere_export_applications"]', (a) => a.href);
	const appsCsv = await page.evaluate(async (u) => { const r = await fetch(u, { credentials: 'same-origin' }); return { type: r.headers.get('content-type'), body: await r.text(), status: r.status }; }, exp);
	ok('E8 CSV export of applications (3 rows + header, status column)', appsCsv.status === 200 && /text\/csv/.test(appsCsv.type) && appsCsv.body.trim().split('\n').length === 4 && /approved/.test(appsCsv.body));
	// delete
	await page.goto(`${B}/wp-admin/edit.php?post_type=vendor_application&s=cedar`, { waitUntil: 'networkidle0' });
	const trashed = await page.$eval('#the-list .submitdelete', (a) => a.href).catch(() => null);
	ok('E8 delete (trash) action available to administrators', !!trashed);
	if (trashed) { await page.goto(trashed, { waitUntil: 'networkidle0' }); }
	ok('E8 deleted application is removed from the list', Number(wpEval(`echo count(get_posts(array('post_type'=>'vendor_application','title'=>'Cedar Honey','numberposts'=>-1,'post_status'=>'publish')));`)) === 0);
	await ctx.close();

	// ---- Non-admin cannot reach anything (F2, E8)
	const sub = await login(SUB);
	for (const path of ['/wp-admin/admin.php?page=rivermere-insights', '/wp-admin/admin.php?page=ri-reports', '/wp-admin/admin.php?page=ri-settings', '/wp-admin/edit.php?post_type=vendor_application']) {
		const r = await sub.page.goto(B + path, { waitUntil: 'networkidle0' });
		ok(`F2/E8 subscriber is denied ${path.replace('/wp-admin/', '')}`, r.status() === 403 || /not allowed|do not have permission|Sorry, you are not allowed/i.test(await text(sub.page)));
	}
	const subExport = await sub.page.evaluate(async () => (await fetch('/wp-admin/admin-post.php?action=ri_export&_wpnonce=x', { credentials: 'same-origin' })).status);
	const subApps = await sub.page.evaluate(async () => (await fetch('/wp-admin/admin-post.php?action=rivermere_export_applications&_wpnonce=x', { credentials: 'same-origin' })).status);
	ok('F10/E8 subscriber cannot export analytics or applications', [400, 403].includes(subExport) && [400, 403].includes(subApps), `${subExport}/${subApps}`);
	await sub.ctx.close();
} finally {
	wpEval(`foreach (get_posts(array('post_type'=>array('vendor_application','rm_vendor'),'title'=>'Alder Orchard','numberposts'=>-1,'post_status'=>array('publish','draft','private','pending','trash'))) as $p) { wp_delete_post($p->ID, true); } foreach (get_posts(array('post_type'=>'vendor_application','numberposts'=>-1,'post_status'=>array('publish','draft','private','pending','trash'))) as $p) { wp_delete_post($p->ID, true); } global $wpdb; $wpdb->query('TRUNCATE TABLE ' . ri_table()); echo 1;`);
	for (const u of [ADMIN[0], SUB[0]]) {
		try { wpCli('user', 'delete', u, '--yes'); } catch { /* already gone */ }
	}
	console.log(res.join('\n'));
	console.log('page errors:', errors.length ? errors.join(' | ') : 'none');
	await browser.close();
}
