/**
 * Small UI features: console welcome, scroll progress, back-to-top, "coming soon" toasts.
 */
const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/** Business welcome message in the browser console (once per page load). */
export function initConsoleWelcome() {
	const data = window.rivermereData;
	if (!data || !data.consoleMessage || window.__rivermereWelcome) {
		return;
	}
	window.__rivermereWelcome = true;

	const title = 'color:#fff;background:#053120;font:800 20px/1.6 system-ui,sans-serif;padding:8px 16px;border-radius:8px 8px 0 0';
	const tag = 'color:#053120;background:#fdd13f;font:700 13px/1.6 system-ui,sans-serif;padding:4px 16px;border-radius:0 0 8px 8px';
	const body = 'color:#1d6806;font:600 13px/1.7 system-ui,sans-serif';
	const thanks = 'color:#053120;font:800 14px/2 system-ui,sans-serif';

	console.log(
		`%c🌿 ${String(data.siteName).toUpperCase()}%c\nFresh food. Local farmers. Stronger communities.\n%c\nDiscover:\n✓ Fresh seasonal produce\n✓ Local farmers and artisan vendors\n✓ Weekly community markets\n✓ Special events and seasonal celebrations\n✓ Sustainable and locally sourced products\n\n%cThank you for supporting local agriculture!\n%c${data.siteUrl}`,
		title,
		tag,
		body,
		thanks,
		'color:#4a5c4f;font:12px system-ui,sans-serif'
	);
}

/** 3px reading progress at the very top of the viewport. */
export function initScrollProgress() {
	const bar = document.querySelector('[data-scroll-progress]');
	if (!bar) {
		return;
	}
	const root = document.documentElement;
	let ticking = false;

	const update = () => {
		ticking = false;
		const max = root.scrollHeight - root.clientHeight;
		const pct = max > 0 ? Math.min(100, Math.max(0, (root.scrollTop / max) * 100)) : 0;
		bar.style.width = `${pct}%`;
	};
	const request = () => {
		if (!ticking) {
			ticking = true;
			window.requestAnimationFrame(update);
		}
	};

	window.addEventListener('scroll', request, { passive: true });
	window.addEventListener('resize', request, { passive: true });
	update();
}

/** Back to top. */
export function initBackToTop() {
	document.querySelectorAll('[data-back-to-top]').forEach((button) => {
		button.addEventListener('click', () => {
			window.scrollTo({ top: 0, behavior: reducedMotion() ? 'auto' : 'smooth' });
			// Return keyboard users to the start of the page.
			document.querySelector('.brand')?.focus({ preventScroll: true });
		});
	});
}

let toastTimer;
/**
 * Show a message in the shared live region.
 *
 * @param {string} message Text to announce.
 */
export function showToast(message) {
	const toast = document.querySelector('[data-toast]');
	if (!toast) {
		return;
	}
	toast.textContent = '';
	// Re-set on next frame so repeated identical messages are announced again.
	window.requestAnimationFrame(() => {
		toast.textContent = message;
		toast.classList.add('is-visible');
	});
	window.clearTimeout(toastTimer);
	toastTimer = window.setTimeout(() => toast.classList.remove('is-visible'), 3500);
}

/** Social buttons without a configured URL. */
export function initComingSoon() {
	document.querySelectorAll('[data-coming-soon]').forEach((button) => {
		button.addEventListener('click', () => showToast(button.dataset.comingSoon));
	});
}
