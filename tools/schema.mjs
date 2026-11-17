// Schema audit: entity types per page, duplicate detection, FAQPage vs visible FAQ match, JSON validity.
const B = 'http://rivermere-farmers-market.local';
const paths = process.argv.slice(2).length ? process.argv.slice(2) : ['/', '/about-us/', '/become-a-vendor/', '/vendors/', '/vendors/greenfield-farm/', '/events/', '/events/harvest-festival/', '/privacy-policy/'];
let bad = 0;
const strip = (h) => h.replace(/<[^>]+>/g, '').replace(/&amp;/g, '&').replace(/&#8217;/g, '’').replace(/&#039;|&#8217;/g, "'").replace(/\s+/g, ' ').trim();
for (const path of paths) {
	const html = await (await fetch(B + path)).text();
	const blocks = [...html.matchAll(/<script type="application\/ld\+json"[^>]*>([\s\S]*?)<\/script>/g)].map((m) => m[1]);
	let graph = [];
	let valid = true;
	for (const b of blocks) {
		try {
			const j = JSON.parse(b);
			graph = graph.concat(j['@graph'] || [j]);
		} catch {
			valid = false;
		}
	}
	const types = {};
	const ids = {};
	for (const e of graph) {
		for (const t of [].concat(e['@type'])) types[t] = (types[t] || 0) + 1;
		if (e['@id']) ids[e['@id']] = (ids[e['@id']] || 0) + 1;
	}
	const dupTypes = ['Organization', 'WebSite', 'BreadcrumbList', 'FAQPage', 'Event'].filter((t) => types[t] > 1);
	const dupIds = Object.entries(ids).filter(([, n]) => n > 1).map(([id]) => id);
	const faq = graph.find((e) => [].concat(e['@type']).includes('FAQPage'));
	const visible = [...html.matchAll(/<summary class="faq__question">\s*<span>([\s\S]*?)<\/span>/g)].map((m) => strip(m[1]));
	let faqNote = 'no FAQ';
	if (faq || visible.length) {
		const qs = faq ? faq.mainEntity.map((q) => q.name) : [];
		const match = faq && qs.length === visible.length && qs.every((q, i) => q === visible[i]);
		faqNote = `FAQPage=${faq ? qs.length : 0} visible=${visible.length} match=${match}`;
		if (!match) bad += 1;
	}
	const web = graph.find((e) => /WebPage|AboutPage|CollectionPage/.test([].concat(e['@type']).join()));
	const org = graph.find((e) => [].concat(e['@type']).includes('Organization'));
	if (!valid || dupTypes.length || dupIds.length) bad += 1;
	console.log(`${path.padEnd(28)} jsonld=${blocks.length} valid=${valid} types=${Object.entries(types).map(([k, v]) => k + (v > 1 ? 'x' + v : '')).join(',')} dupTypes=[${dupTypes}] dupIds=[${dupIds.length}] web=${web ? [].concat(web['@type']).join('/') : '-'} org=${org ? [].concat(org['@type']).join('/') : '-'} | ${faqNote}`);
}
console.log(bad ? `PROBLEMS: ${bad}` : 'schema audit OK');
