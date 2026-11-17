// Prints the breadcrumb trail, nav count and BreadcrumbList count for a set of URLs.
const B = 'http://rivermere-farmers-market.local';
const paths = process.argv.slice(2).length ? process.argv.slice(2) : ['/', '/about/', '/events/', '/events/harvest-festival/', '/vendors/', '/vendors/greenfield-farm/', '/products/fresh-produce/', '/?s=honey', '/nope-xyz/', '/privacy-policy/', '/terms-and-conditions/', '/cookie-policy/'];
for (const path of paths) {
	const res = await fetch(B + path);
	const html = await res.text();
	const navs = [...html.matchAll(/<nav class="breadcrumbs"[\s\S]*?<\/nav>/g)].map((m) => m[0]);
	const trail = navs[0]
		? [...navs[0].matchAll(/<(?:a|span)[^>]*class="breadcrumbs__(?:link|current)"[^>]*>([^<]*)</g)].map((m) => m[1]).join(' / ')
		: '(none)';
	const schema = (html.match(/"@type":"BreadcrumbList"/g) || []).length;
	const anyBreadcrumbNav = (html.match(/aria-label="[Bb]readcrumbs?"/g) || []).length;
	console.log(`${res.status} ${path.padEnd(30)} navs=${anyBreadcrumbNav} schema=${schema}  ${trail}`);
}
