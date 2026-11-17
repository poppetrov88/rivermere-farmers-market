// API / security / consent / conversion tests (no browser). Run: WP_PS1=<path to wp.ps1> node tools/e2e-api.mjs
import { probe, wpEval, clearThrottles } from './lib/wp.mjs';

const B = 'http://rivermere-farmers-market.local';
const res = [];
const ok = (n, c, x = '') => res.push(`${c ? 'PASS' : 'FAIL'} ${n}${x ? ' — ' + x : ''}`);
const consent = (a) => `rm_consent=${encodeURIComponent(JSON.stringify({ v: 1, n: 1, a, m: 0, t: new Date().toISOString() }))}`;

const html = await (await fetch(B + '/become-a-vendor/')).text();
const nonce = html.match(/"restNonce":"([a-z0-9]+)"/)[1];
const VA = `${B}/wp-json/rivermere/v1/vendor-application`;
const NL = `${B}/wp-json/rivermere/v1/newsletter`;
const COLLECT = `${B}/wp-json/rivermere-insights/v1/collect`;
const post = async (url, body, headers = {}, raw = false) => {
	let r;
	try {
		r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', Origin: B, Referer: B + '/', ...headers }, body: raw ? body : JSON.stringify(body) });
	} catch (e) {
		return { status: 0, json: { code: 'fetch-error: ' + (e.cause && e.cause.code) } };
	}
	let json = null;
	try { json = await r.json(); } catch { /* 204 */ }
	return { status: r.status, json };
};
const stamp = () => String(Math.floor(Date.now() / 1000) - 30);
const valid = (n, extra = {}) => ({
	business_name: `QA Farm ${n}`, first_name: 'Quinn', last_name: 'Tester', email: `qa-${n}@example.com`, phone: '+1 (555) 010-0100', products: 'Seasonal vegetables and herbs',
	website: '', social: '', description: '', category: '', location: '', experience: '', notes: '', consent: true, fee_ack: false, company_website: '', rm_ts: stamp(), submission_id: `qa-${n}-${Date.now()}`, ...extra,
});

// clean slate
wpEval(`global $wpdb; $wpdb->query('TRUNCATE TABLE ' . ri_table()); foreach (get_posts(array('post_type'=>'vendor_application','numberposts'=>-1,'post_status'=>array('publish','draft','private','pending','trash'))) as $p) { wp_delete_post($p->ID, true); } $wpdb->query("DELETE FROM {$wpdb->prefix}rm_subscribers WHERE email LIKE '%@example.com'"); echo 'ok';`);
clearThrottles();
const H = { 'X-WP-Nonce': nonce };

// ---- Capability regression (a bad CPT capability map once removed manage_options from every administrator)
{
	const caps = JSON.parse(wpEval(`$a = new WP_User(1); $s = wp_insert_user(array('user_login'=>'qa_cap_sub','user_pass'=>'x-Y9!zzzzzzzz','user_email'=>'qa-cap@example.invalid','role'=>'subscriber')); $u = new WP_User($s);
echo wp_json_encode(array('admin_manage'=>$a->has_cap('manage_options'),'admin_apps'=>$a->has_cap('edit_vendor_applications'),'sub_manage'=>$u->has_cap('manage_options'),'sub_apps'=>$u->has_cap('edit_vendor_applications'),'create'=>$a->has_cap('create_vendor_applications')));
wp_delete_user($s);`));
	ok('SEC administrators keep manage_options; only they can manage applications', caps.admin_manage === true && caps.admin_apps === true && caps.sub_manage === false && caps.sub_apps === false, JSON.stringify(caps));
}

// ---- Vendor application security + validation
let r = await post(VA, valid(1));
ok('E9 request without nonce rejected (403)', r.status === 403, r.json && r.json.code);
r = await post(VA, JSON.stringify({ a: 'x'.repeat(25000) }), H, true);
ok('E9 oversized payload rejected (413)', r.status === 413);
r = await post(VA, { consent: false }, H);
ok('K3 empty submission -> 422 with per-field errors', r.status === 422 && ['business_name', 'first_name', 'last_name', 'email', 'phone', 'products', 'consent'].every((k) => r.json.errors[k]), Object.keys(r.json.errors || {}).join());
r = await post(VA, valid(2, { email: 'not-an-email', website: 'javascript:alert(1)', social: 'ftp://x', phone: 'abc' }), H);
ok('K3 invalid email / URL / phone rejected', r.status === 422 && r.json.errors.email && r.json.errors.website && r.json.errors.social && r.json.errors.phone, Object.keys(r.json.errors || {}).join());
r = await post(VA, valid(3, { consent: false }), H);
ok('K3 privacy consent required server-side', r.status === 422 && !!r.json.errors.consent);
const before = probe();
r = await post(VA, valid(4, { company_website: 'http://spam.example' }), H);
ok('E9 honeypot: fake success, nothing stored', r.status === 200 && probe().apps === before.apps);
r = await post(VA, valid(5, { rm_ts: String(Math.floor(Date.now() / 1000)) }), H);
ok('E9 too-fast submission rejected (429)', r.status === 429 && r.json.code === 'too_fast');

// ---- Successful storage + conversion consent rules
clearThrottles(); // fresh rate-limit window for the happy-path checks
let p0 = probe();
const good = valid(10);
r = await post(VA, good, { ...H, Cookie: consent(1) });
let p1 = probe();
ok('K3 valid application stored (success only after storage)', r.status === 200 && r.json.success === true && p1.apps === p0.apps + 1, r.json && r.json.message && r.json.message.slice(0, 40));
ok('J3/K4 confirmed conversion recorded WITH analytics consent', (p1.events.application_submitted || 0) === (p0.events.application_submitted || 0) + 1);
r = await post(VA, good, { ...H, Cookie: consent(1) });
let p2 = probe();
ok('E9 replayed submission id: no second record, no second conversion', r.status === 200 && r.json.duplicate === true && p2.apps === p1.apps && (p2.events.application_submitted || 0) === (p1.events.application_submitted || 0));
r = await post(VA, valid(10, { submission_id: 'other-id-' + Date.now() }), { ...H, Cookie: consent(1) });
ok('K3 duplicate applicant (same email + business) -> 409, nothing stored', r.status === 409 && probe().apps === p2.apps);
clearThrottles();
r = await post(VA, valid(11), H);
let p3 = probe();
ok('K4 application stored but NO analytics conversion without a consent cookie', r.status === 200 && p3.apps === p2.apps + 1 && (p3.events.application_submitted || 0) === (p2.events.application_submitted || 0));
r = await post(VA, valid(12), { ...H, Cookie: consent(0) });
let p4 = probe();
ok('K4 Reject Optional (a=0): stored, NO conversion', r.status === 200 && p4.apps === p3.apps + 1 && (p4.events.application_submitted || 0) === (p3.events.application_submitted || 0));
r = await post(VA, valid(13), { ...H, Cookie: 'rm_consent=garbage' });
ok('K4 invalid consent cookie: stored, NO conversion', r.status === 200 && (probe().events.application_submitted || 0) === (p4.events.application_submitted || 0));
let throttled = false;
for (let i = 0; i < 8 && !throttled; i += 1) {
	r = await post(VA, valid(20 + i, { phone: 'bad' }), H);
	throttled = r.status === 429 && r.json.code === 'rate_limited';
}
ok('E9 rate limit: repeated attempts are blocked (429)', throttled);

// ---- stored data + privacy of storage
const stored = wpEval(`$p = get_posts(array('post_type'=>'vendor_application','numberposts'=>1,'meta_key'=>'_app_email','meta_value'=>'qa-10@example.com','post_status'=>array('publish','draft','private','pending','trash')))[0]; echo wp_json_encode(array('title'=>$p->post_title,'status'=>get_post_meta($p->ID,'_app_status',true),'consent'=>json_decode(get_post_meta($p->ID,'_app_consent',true),true),'public'=>is_post_type_viewable('vendor_application'),'mail'=>get_post_meta($p->ID,'_app_mail_status',true)));`);
const sj = JSON.parse(stored);
ok('E6/E7 stored with status "new" and a consent record', sj.status === 'new' && sj.consent.privacy === true && !!sj.consent.at_utc && /privacy/i.test(sj.consent.text));
ok('E6 post type is not public/viewable', sj.public === false);
ok('E11 notification attempted and result recorded', /admin: (sent|FAILED)/.test(sj.mail), sj.mail);

// ---- not exposed publicly
const types = await (await fetch(B + '/wp-json/wp/v2/types')).json();
ok('E8 vendor_application absent from public REST types', !('vendor_application' in types));
ok('E8 no public REST collection', (await fetch(B + '/wp-json/wp/v2/vendor_application')).status === 404);
const search = await (await fetch(B + '/?s=QA+Farm')).text();
ok('E6 applications not in public search', !/QA Farm 10/.test(search));
const sm = await fetch(B + '/sitemap_index.xml');
const smTxt = sm.ok ? await sm.text() : '';
ok('E6 applications not in XML sitemap index', !/vendor_application|vendor-application/.test(smTxt), sm.status + ' ' + (smTxt.match(/<loc>[^<]*<\/loc>/g) || []).map((l) => l.replace(/<\/?loc>/g, '').split('/').pop()).join(' '));
const anon = await fetch(B + '/wp-admin/admin-post.php?action=rivermere_export_applications', { redirect: 'manual' });
ok('E8 anonymous CSV export denied', anon.status !== 200, String(anon.status));

// ---- Newsletter conversions
clearThrottles();
let n0 = probe();
r = await post(NL, { email: 'qa-nl-1@example.com', consent: true, website: '', rm_ts: stamp() }, { ...H, Cookie: consent(1) });
let n1 = probe();
ok('J4 new subscription: stored + conversion (consent given)', r.status === 200 && n1.subs === n0.subs + 1 && (n1.events.newsletter_subscribed || 0) === (n0.events.newsletter_subscribed || 0) + 1);
r = await post(NL, { email: 'QA-NL-1@example.com', consent: true, website: '', rm_ts: stamp() }, { ...H, Cookie: consent(1) });
let n2 = probe();
ok('J4 duplicate subscription: NOT counted as a new conversion', r.status === 200 && n2.subs === n1.subs && (n2.events.newsletter_subscribed || 0) === (n1.events.newsletter_subscribed || 0));
r = await post(NL, { email: 'qa-nl-2@example.com', consent: true, website: '', rm_ts: stamp() }, H);
let n3 = probe();
ok('K4 subscription without analytics consent: stored, NO conversion', r.status === 200 && n3.subs === n2.subs + 1 && (n3.events.newsletter_subscribed || 0) === (n2.events.newsletter_subscribed || 0));
r = await post(NL, { email: 'qa-nl-3@example.com', consent: false, website: '', rm_ts: stamp() }, { ...H, Cookie: consent(1) });
ok('K4 failed subscription (no newsletter consent): no record, no conversion', r.status === 400 && probe().subs === n3.subs && (probe().events.newsletter_subscribed || 0) === (n3.events.newsletter_subscribed || 0));

// ---- Insights collect endpoint
wpEval(`global $wpdb; $wpdb->query('TRUNCATE TABLE ' . ri_table()); echo 'ok';`);
clearThrottles();
const ev = (t, k, extra = {}) => ({ events: [{ t, k, p: '/about-us/', pt: 'page', ...extra }] });
r = await post(COLLECT, ev('cta', 'apply-now'));
ok('F9 no consent cookie: accepted (204) but NOTHING stored', r.status === 204 && Object.keys(probe().events).length === 0);
r = await post(COLLECT, ev('cta', 'apply-now'), { Cookie: consent(0) });
ok('F7 Reject Optional (a=0): nothing stored', Object.keys(probe().events).length === 0);
r = await post(COLLECT, ev('cta', 'apply-now'), { Cookie: consent(1) });
ok('F9 consent given: event stored', r.status === 204 && probe().events.cta === 1);
r = await post(COLLECT, ev('application_submitted', 'fake'), { Cookie: consent(1) });
ok('F6 browsers cannot claim conversions', !probe().events.application_submitted);
r = await post(COLLECT, ev('bogus_type', 'x'), { Cookie: consent(1) });
ok('F9 unknown event types ignored', !probe().events.bogus_type);
await post(COLLECT, ev('tel', '+1 555 010 0199 <script>'), { Cookie: consent(1) });
const lbl = wpEval(`global $wpdb; echo $wpdb->get_var("SELECT event_key FROM " . ri_table() . " WHERE event_type='tel' LIMIT 1");`);
ok('F5/F12 labels are sanitised to a safe slug (no raw input stored)', /^[a-z0-9\-_.:]*$/.test(lbl) && !/script/.test(lbl), lbl);
r = await fetch(COLLECT, { method: 'POST', headers: { 'Content-Type': 'application/json', Origin: 'https://evil.example', Cookie: consent(1) }, body: JSON.stringify(ev('cta', 'x')) });
ok('F9 cross-origin request rejected (403)', r.status === 403);
r = await fetch(COLLECT, { method: 'POST', headers: { 'Content-Type': 'application/json', Cookie: consent(1) }, body: JSON.stringify(ev('cta', 'x')) });
ok('F9 request without Origin/Referer rejected', r.status === 403);
r = await post(COLLECT, JSON.stringify({ a: 'x'.repeat(3000) }), { Cookie: consent(1) }, true);
ok('F9 payload limit (413)', r.status === 413);
await post(COLLECT, { events: [{ t: 'pageview', k: 'page', id: 99999999, p: '/x?email=a@b.com&token=1#frag', pt: 'page' }] }, { Cookie: consent(1) });
const path = wpEval(`global $wpdb; echo $wpdb->get_var("SELECT page_path FROM " . ri_table() . " WHERE event_type='pageview' LIMIT 1");`);
ok('F5 query strings are never stored (sensitive parameters dropped)', path === '/x', path);
let limited = 0;
for (let i = 0; i < 70; i += 1) {
	const rr = await post(COLLECT, ev('cta', `label-${i % 3}`), { Cookie: consent(1) });
	if (rr.status === 429) limited += 1;
}
ok('F9 rate limit engages (429) under flood', limited > 0, `${limited} of 70 limited`);

// ---- cardinality cap
clearThrottles();
wpEval(`global $wpdb; $wpdb->query('TRUNCATE TABLE ' . ri_table()); for ($i = 0; $i < 450; $i++) { ri_record('cta', 'k' . $i, '/p' . $i . '/', 0, 'page'); } echo 'ok';`);
const rows = Number(wpEval(`global $wpdb; echo $wpdb->get_var("SELECT COUNT(*) FROM " . ri_table() . " WHERE event_type='cta'");`));
ok('F8/F9 row cap bounds cardinality (450 distinct labels -> <= 401 rows)', rows <= 401, `${rows} rows`);

// ---- retention
wpEval(`global $wpdb; $wpdb->query('TRUNCATE TABLE ' . ri_table()); $t = ri_table();
$old = gmdate('Y-m-d', time() - 500 * DAY_IN_SECONDS); $new = gmdate('Y-m-d');
$wpdb->insert($t, array('row_hash'=>md5('old'),'day'=>$old,'event_type'=>'pageview','event_key'=>'page','page_path'=>'/','hits'=>5));
$wpdb->insert($t, array('row_hash'=>md5('new'),'day'=>$new,'event_type'=>'pageview','event_key'=>'page','page_path'=>'/','hits'=>5));
echo 'ok';`);
const deleted = wpEval(`echo ri_cleanup();`);
const left = wpEval(`global $wpdb; echo $wpdb->get_var("SELECT COUNT(*) FROM " . ri_table());`);
ok('F8/K4 retention cleanup deletes only data older than the retention period', deleted === '1' && left === '1', `deleted=${deleted} left=${left}`);

// cleanup of test data
wpEval(`global $wpdb; $wpdb->query('TRUNCATE TABLE ' . ri_table()); foreach (get_posts(array('post_type'=>'vendor_application','numberposts'=>-1,'post_status'=>array('publish','draft','private','pending','trash'))) as $p) { wp_delete_post($p->ID, true); } $wpdb->query("DELETE FROM {$wpdb->prefix}rm_subscribers WHERE email LIKE '%@example.com'"); echo 'ok';`);
clearThrottles();
console.log(res.join('\n'));
console.log(res.filter((l) => l.startsWith('FAIL')).length ? 'FAILURES PRESENT' : 'all API checks passed');
