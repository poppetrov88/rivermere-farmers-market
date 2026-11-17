/**
 * Mobile navigation: disclosure button toggling the primary nav.
 * Escape closes it and returns focus to the toggle; resizing to desktop resets state.
 */
export function initNavigation() {
	const toggle = document.querySelector('[data-nav-toggle]');
	const nav = document.getElementById('primary-nav');
	if (!toggle || !nav) {
		return;
	}

	const desktop = window.matchMedia('(min-width: 1024px)');

	const setOpen = (open) => {
		toggle.setAttribute('aria-expanded', String(open));
		nav.classList.toggle('is-open', open);
		document.body.classList.toggle('is-locked', open && !desktop.matches);
	};

	toggle.addEventListener('click', () => {
		setOpen(toggle.getAttribute('aria-expanded') !== 'true');
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
			setOpen(false);
			toggle.focus();
		}
	});

	// Close after choosing an in-page link.
	nav.addEventListener('click', (event) => {
		if (event.target.closest('a')) {
			setOpen(false);
		}
	});

	desktop.addEventListener('change', () => setOpen(false));
}
