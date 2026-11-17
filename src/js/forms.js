/**
 * Newsletter form: client-side validation + REST submission with accessible status messages.
 */
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

export function initForms() {
	const data = window.rivermereData;
	document.querySelectorAll('[data-newsletter-form]').forEach((form) => {
		if (!data) {
			return;
		}
		const email = form.querySelector('input[name="email"]');
		const consent = form.querySelector('input[name="consent"]');
		const status = form.querySelector('[data-newsletter-status]');
		const button = form.querySelector('[data-newsletter-submit]');
		const field = form.querySelector('.newsletter__field');
		const label = button.querySelector('span');

		const say = (message, state) => {
			status.textContent = message;
			status.dataset.state = state || '';
		};

		const setInvalid = (invalid) => {
			email.setAttribute('aria-invalid', String(invalid));
			if (field) {
				field.dataset.invalid = String(invalid);
			}
		};

		form.addEventListener('submit', async (event) => {
			event.preventDefault();
			say('', '');
			setInvalid(false);

			if (!EMAIL_RE.test(email.value.trim())) {
				setInvalid(true);
				say(data.i18n.invalidEmail, 'error');
				email.focus();
				return;
			}
			if (consent && !consent.checked) {
				say(data.i18n.consent, 'error');
				consent.focus();
				return;
			}

			button.disabled = true;
			button.setAttribute('aria-busy', 'true');
			label.textContent = data.i18n.sending;
			say(data.i18n.sending, '');

			try {
				const response = await fetch(data.newsletterEndpoint, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': data.restNonce },
					credentials: 'same-origin',
					body: JSON.stringify({
						email: email.value.trim(),
						consent: consent ? consent.checked : false,
						website: form.querySelector('input[name="website"]').value,
						rm_ts: form.querySelector('input[name="rm_ts"]').value,
					}),
				});
				const json = await response.json().catch(() => ({}));
				if (response.ok && json.success) {
					say(json.message, 'success');
					form.reset();
				} else {
					if (json.code === 'invalid_email') {
						setInvalid(true);
					}
					say(json.message || data.i18n.genericError, 'error');
				}
			} catch {
				say(data.i18n.genericError, 'error');
			} finally {
				button.disabled = false;
				button.removeAttribute('aria-busy');
				label.textContent = data.i18n.subscribe;
			}
		});
	});
}
