/**
 * Floating contact widget: on small screens the rail collapses behind a toggle button.
 * On desktop the labels are pure CSS (:hover / :focus-visible).
 */
export function initContactWidget() {
	const widget = document.querySelector('[data-contact-widget]');
	const toggle = widget?.querySelector('[data-contact-toggle]');
	if (!widget || !toggle) {
		return;
	}
	const setOpen = (open) => {
		toggle.setAttribute('aria-expanded', String(open));
		widget.classList.toggle('is-open', open);
	};
	toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && widget.classList.contains('is-open')) {
			setOpen(false);
			toggle.focus();
		}
	});
	document.addEventListener('click', (event) => {
		if (widget.classList.contains('is-open') && !widget.contains(event.target)) {
			setOpen(false);
		}
	});
}
