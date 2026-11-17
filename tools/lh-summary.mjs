// Usage: node tools/lh-summary.mjs <file.json> ... : prints category scores + vitals from Lighthouse JSON reports.
import fs from 'node:fs';
for (const f of process.argv.slice(2)) {
	const r = JSON.parse(fs.readFileSync(f, 'utf8'));
	const sc = Object.fromEntries(Object.entries(r.categories).map(([k, v]) => [k, Math.round(v.score * 100)]));
	const a = r.audits;
	console.log(`${r.finalDisplayedUrl.replace('http://rivermere-farmers-market.local', '') || '/'} perf=${sc.performance} a11y=${sc.accessibility} bp=${sc['best-practices']} seo=${sc.seo} | LCP ${a['largest-contentful-paint'].displayValue} CLS ${a['cumulative-layout-shift'].displayValue} TBT ${a['total-blocking-time'].displayValue}`);
	const fails = Object.entries(a).filter(([k, v]) => v.score === 0 && ['accessibility', 'seo'].some((c) => r.categories[c].auditRefs.some((x) => x.id === k))).map(([k]) => k);
	if (fails.length) console.log('   a11y/seo failures:', fails.join(', '));
}
