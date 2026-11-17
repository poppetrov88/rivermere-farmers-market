/**
 * Full-screen search overlay built on the native <dialog> element:
 * showModal() provides the focus trap, inert background and focus restoration;
 * we add the animated close, scroll lock and input focus.
 */
export function initSearch() {
	const dialog = document.getElementById('search-overlay');
	const openers = document.querySelectorAll('[data-search-open]');
	if (!dialog || typeof dialog.showModal !== 'function' || !openers.length) {
		return;
	}

	const input = dialog.querySelector('input[type="search"]');
	const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
	let lastTrigger = null;

	const open = (trigger) => {
		lastTrigger = trigger;
		dialog.classList.remove('is-closing');
		dialog.showModal();
		document.body.classList.add('is-locked');
		input?.focus();
	};

	const close = () => {
		if (!dialog.open) {
			return;
		}
		const finish = () => {
			dialog.close();
			dialog.classList.remove('is-closing');
		};
		if (reduced.matches) {
			finish();
			return;
		}
		dialog.classList.add('is-closing');
		dialog.addEventListener('transitionend', finish, { once: true });
		// Safety net if no transition fires.
		window.setTimeout(() => dialog.open && finish(), 400);
	};

	openers.forEach((button) => {
		button.addEventListener('click', () => open(button));
	});

	dialog.querySelector('[data-search-close]')?.addEventListener('click', close);

	// Escape: animate out instead of the instant native close.
	dialog.addEventListener('cancel', (event) => {
		event.preventDefault();
		close();
	});

	// Clicking the dim area outside the content closes too.
	dialog.addEventListener('click', (event) => {
		if (event.target === dialog) {
			close();
		}
	});

	dialog.addEventListener('close', () => {
		document.body.classList.remove('is-locked');
		(lastTrigger || openers[0]).focus();
	});
}
