/**
 * Cookie consent manager (first-party cookie, no dependencies).
 *
 * Stored value (cookie `rm_consent`, URI-encoded JSON): { v: version, n: 1, a: 0|1, m: 0|1, t: ISO time }.
 * Optional scripts must NOT run before consent:
 *  - Analytics (GA4) is injected here only after analytics consent, using window.rivermereAnalytics.
 *  - Any other script can be gated with <script type="text/plain" data-consent="analytics|marketing" ...>.
 * Other code can listen for the `rivermere:consent` event (detail = { analytics, marketing }).
 */
const COOKIE = 'rm_consent';
const VERSION = 1; // Bump to ask every visitor again (e.g. when cookie categories change).
const MAX_AGE = 60 * 60 * 24 * 180;

const parse = () => {
	const match = document.cookie.split('; ').find((row) => row.startsWith(`${COOKIE}=`));
	if (!match) {
		return null;
	}
	try {
		const data = JSON.parse(decodeURIComponent(match.slice(COOKIE.length + 1)));
		return data && data.v === VERSION && data.t ? data : null;
	} catch {
		return null;
	}
};

const store = (analytics, marketing) => {
	const data = { v: VERSION, n: 1, a: analytics ? 1 : 0, m: marketing ? 1 : 0, t: new Date().toISOString() };
	const secure = window.location.protocol === 'https:' ? '; Secure' : '';
	document.cookie = `${COOKIE}=${encodeURIComponent(JSON.stringify(data))}; Max-Age=${MAX_AGE}; Path=/; SameSite=Lax${secure}`;
	return data;
};

/** Delete first-party Google Analytics cookies we can reach (current host and parent domains). */
const clearAnalyticsCookies = () => {
	const names = document.cookie
		.split('; ')
		.map((row) => row.split('=')[0])
		.filter((name) => /^(_ga|_gid|_gat)/.test(name));
	const parts = window.location.hostname.split('.');
	const domains = [''];
	for (let i = 0; i < parts.length - 1; i += 1) {
		domains.push(`; Domain=${parts.slice(i).join('.')}`);
	}
	names.forEach((name) => {
		domains.forEach((domain) => {
			document.cookie = `${name}=; Max-Age=0; Path=/${domain}`;
		});
	});
};

let gaLoaded = false;

const setAnalytics = (allowed) => {
	const config = window.rivermereAnalytics;
	if (!config || !config.id) {
		return; // Nothing configured: never inject tracking.
	}
	window[`ga-disable-${config.id}`] = !allowed;
	if (allowed) {
		if (!gaLoaded) {
			gaLoaded = true;
			window.dataLayer = window.dataLayer || [];
			window.gtag = function gtag() {
				window.dataLayer.push(arguments);
			};
			window.gtag('consent', 'default', { analytics_storage: 'granted', ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied' });
			window.gtag('js', new Date());
			window.gtag('config', config.id);
			const script = document.createElement('script');
			script.async = true;
			script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(config.id)}`;
			document.head.appendChild(script);
		} else if (window.gtag) {
			window.gtag('consent', 'update', { analytics_storage: 'granted' });
		}
	} else {
		if (window.gtag) {
			window.gtag('consent', 'update', { analytics_storage: 'denied', ad_storage: 'denied' });
		}
		clearAnalyticsCookies();
	}
};

/** Activate consent-gated placeholder scripts once their category is allowed. */
const activateScripts = (prefs) => {
	document.querySelectorAll('script[type="text/plain"][data-consent]').forEach((node) => {
		const category = node.dataset.consent;
		const allowed = (category === 'analytics' && prefs.analytics) || (category === 'marketing' && prefs.marketing);
		if (!allowed || node.dataset.consentActive) {
			return;
		}
		const script = document.createElement('script');
		[...node.attributes].forEach((attr) => {
			if (attr.name !== 'type' && attr.name !== 'data-consent') {
				script.setAttribute(attr.name, attr.value);
			}
		});
		script.text = node.text;
		node.dataset.consentActive = '1';
		node.after(script);
	});
};

const apply = (data) => {
	const prefs = { analytics: !!data.a, marketing: !!data.m };
	setAnalytics(prefs.analytics);
	activateScripts(prefs);
	window.rivermereConsent = prefs;
	document.dispatchEvent(new CustomEvent('rivermere:consent', { detail: prefs }));
};

export function initConsent() {
	const banner = document.querySelector('[data-cookie-banner]');
	const dialog = document.querySelector('[data-cookie-dialog]');
	const existing = parse();

	// Returning visitor with a valid choice: honour it silently.
	if (existing) {
		apply(existing);
	}
	if (!banner || !dialog) {
		return;
	}

	const form = dialog.querySelector('[data-cookie-form]');
	const analytics = dialog.querySelector('input[name="analytics"]');
	const marketing = dialog.querySelector('input[name="marketing"]');
	const reopen = document.querySelector('.footer-cookie-settings');

	const hideBanner = () => {
		banner.hidden = true;
	};

	const save = (a, m) => {
		const data = store(a, m);
		apply(data);
		hideBanner();
		if (dialog.open) {
			dialog.close();
		}
		// Keep focus somewhere visible once the banner has gone.
		(reopen || document.body).focus({ preventScroll: true });
	};

	const openSettings = (trigger) => {
		const current = parse();
		analytics.checked = !!(current && current.a);
		marketing.checked = !!(current && current.m);
		dialog.showModal();
		document.body.classList.add('is-locked');
		dialog.dataset.trigger = trigger && trigger.closest('[data-cookie-banner]') ? 'banner' : 'footer';
	};

	if (!existing) {
		banner.hidden = false;
	}

	document.querySelectorAll('[data-cookie-accept]').forEach((button) => button.addEventListener('click', () => save(true, true)));
	document.querySelectorAll('[data-cookie-reject]').forEach((button) => button.addEventListener('click', () => save(false, false)));
	document.querySelectorAll('[data-cookie-open]').forEach((button) => button.addEventListener('click', () => openSettings(button)));
	dialog.querySelector('[data-cookie-close]').addEventListener('click', () => dialog.close());

	form.addEventListener('submit', (event) => {
		event.preventDefault();
		save(analytics.checked, marketing.checked);
	});

	dialog.addEventListener('close', () => document.body.classList.remove('is-locked'));
	// Clicking the backdrop closes without saving.
	dialog.addEventListener('click', (event) => {
		if (event.target === dialog) {
			dialog.close();
		}
	});
}
