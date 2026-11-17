/**
 * Rivermere Insights tracker. Dependency-free, cookie-free, consent-gated.
 *
 * Contract with the Rivermere cookie manager:
 *   - cookie `rm_consent` (URI-encoded JSON, a = 1 means analytics allowed)
 *   - window.rivermereConsent and the `rivermere:consent` event (detail.analytics)
 * Nothing is sent before consent; after withdrawal nothing more is sent.
 * Only stable labels are recorded: never input values, emails, phone numbers or full URLs.
 */
(function () {
	'use strict';

	var cfg = window.rivermereInsights;
	if (!cfg || !cfg.endpoint) {
		return;
	}

	var SOCIAL = { 'facebook.com': 'facebook', 'instagram.com': 'instagram', 'linkedin.com': 'linkedin', 'x.com': 'x', 'twitter.com': 'x', 'youtube.com': 'youtube', 'tiktok.com': 'tiktok' };
	var DOWNLOAD = /\.(pdf|docx?|xlsx?|zip|csv|pptx?)$/i;
	var active = false;
	var sentPageview = false;
	var last = { el: null, at: 0 };

	function hasConsent() {
		if (window.rivermereConsent && typeof window.rivermereConsent.analytics === 'boolean') {
			return window.rivermereConsent.analytics;
		}
		var row = document.cookie.split('; ').filter(function (c) { return c.indexOf('rm_consent=') === 0; })[0];
		if (!row) {
			return false;
		}
		try {
			var data = JSON.parse(decodeURIComponent(row.slice(11)));
			return !!(data && data.v && data.a === 1);
		} catch (e) {
			return false;
		}
	}

	function send(events) {
		if (!active || !events.length) {
			return;
		}
		var body = JSON.stringify({ events: events.slice(0, 5) });
		try {
			if (navigator.sendBeacon && navigator.sendBeacon(cfg.endpoint, new Blob([body], { type: 'application/json' }))) {
				return;
			}
		} catch (e) { /* fall through to fetch */ }
		if (window.fetch) {
			window.fetch(cfg.endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body, keepalive: true, credentials: 'same-origin' }).catch(function () {});
		}
	}

	function base(type, label) {
		return { t: type, k: label, id: cfg.page.id || 0, p: cfg.page.path, pt: cfg.page.pt };
	}

	function slug(text) {
		return String(text || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 50);
	}

	function enable() {
		if (active) {
			return;
		}
		active = true;
		if (cfg.track.pageviews && !sentPageview) {
			sentPageview = true;
			send([base('pageview', cfg.page.pt)]);
		}
	}

	function disable() {
		active = false;
	}

	/** Decide what (if anything) a click should record. Returns {t,k} or null. */
	function classify(el) {
		var type = el.getAttribute('data-insights-event');
		var label = el.getAttribute('data-insights-label');
		if (type) {
			return { t: type, k: slug(label) || slug(el.textContent) };
		}
		if (el.tagName !== 'A') {
			return null; // Plain buttons are only tracked when explicitly marked with data-insights-event.
		}
		var href = el.getAttribute('href') || '';
		if (/^tel:/i.test(href)) {
			return cfg.track.contact ? { t: 'tel', k: 'phone' } : null;
		}
		if (/^mailto:/i.test(href)) {
			return cfg.track.contact ? { t: 'email', k: 'email' } : null;
		}
		var url;
		try {
			url = new URL(el.href, window.location.href);
		} catch (e) {
			return null;
		}
		var host = url.hostname.replace(/^www\./, '');
		if (host === 'wa.me' || host === 'api.whatsapp.com') {
			return cfg.track.contact ? { t: 'whatsapp', k: 'whatsapp' } : null;
		}
		if (SOCIAL[host]) {
			return cfg.track.social ? { t: 'social', k: SOCIAL[host] } : null;
		}
		if (url.hostname !== cfg.host) {
			return cfg.track.external ? { t: 'external', k: host.slice(0, 60) } : null; // Hostname only.
		}
		if (DOWNLOAD.test(url.pathname)) {
			return cfg.track.external ? { t: 'download', k: slug(url.pathname.split('/').pop()) } : null;
		}
		if (cfg.vendorPath && url.pathname === cfg.vendorPath) {
			return cfg.track.cta ? { t: 'apply_cta', k: slug(el.textContent) || 'apply' } : null;
		}
		if (el.classList.contains('btn')) {
			return cfg.track.cta ? { t: 'cta', k: slug(el.textContent) } : null;
		}
		return null; // Ordinary internal links are not tracked.
	}

	document.addEventListener('click', function (event) {
		if (!active || event.__riHandled) {
			return;
		}
		var el = event.target && event.target.closest ? event.target.closest('[data-insights-event], a[href]') : null;
		if (!el) {
			return;
		}
		event.__riHandled = true; // One record per click, even through nested elements.
		var now = Date.now();
		if (last.el === el && now - last.at < 600) {
			return;
		}
		last = { el: el, at: now };
		var hit = classify(el);
		if (hit && hit.k !== undefined) {
			send([base(hit.t, hit.k)]);
		}
	});

	document.addEventListener('rivermere:consent', function (event) {
		if (event.detail && event.detail.analytics) {
			enable();
		} else {
			disable();
		}
	});

	if (hasConsent()) {
		enable();
	}
})();
