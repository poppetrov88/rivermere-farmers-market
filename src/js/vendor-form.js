/**
 * Vendor application form: inline validation, accessible error summary, one-shot submission.
 * Success is shown ONLY after the server confirms the application was stored.
 */
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
const PHONE_RE = /^\+?[0-9][0-9 ().-]{5,28}[0-9]$/;

const uuid = () => {
	if (window.crypto && window.crypto.randomUUID) {
		return window.crypto.randomUUID();
	}
	return `${Date.now().toString(16)}-${Math.random().toString(16).slice(2)}-${Math.random().toString(16).slice(2)}`;
};

const isUrl = (value) => {
	try {
		const url = new URL(value);
		return url.protocol === 'http:' || url.protocol === 'https:';
	} catch {
		return false;
	}
};

export function initVendorForm() {
	const form = document.querySelector('[data-apply-form]');
	const data = window.rivermereData;
	if (!form || !data) {
		return;
	}
	const summary = form.querySelector('[data-apply-summary]');
	const summaryList = form.querySelector('[data-apply-summary-list]');
	const button = form.querySelector('[data-apply-submit]');
	const label = button.querySelector('span');
	const status = form.querySelector('[data-apply-status]');
	const success = document.querySelector('[data-apply-success]');
	const idField = form.querySelector('input[name="submission_id"]');
	idField.value = uuid(); // One id per page load: a replayed submit can never create a second record.
	let busy = false;

	const controlOf = (name) => form.querySelector(`[name="${name}"]`);
	const slot = (name) => {
		const control = controlOf(name);
		const wrap = control ? control.closest('[data-field]') : null;
		return { control, error: wrap ? wrap.querySelector('.field__error') : null, wrap };
	};

	const setError = (name, message) => {
		const { control, error, wrap } = slot(name);
		if (!control || !error) {
			return;
		}
		error.textContent = message || '';
		error.hidden = !message;
		control.setAttribute('aria-invalid', message ? 'true' : 'false');
		wrap.classList.toggle('has-error', !!message);
	};

	const clearAll = () => {
		form.querySelectorAll('[data-field]').forEach((wrap) => {
			const control = wrap.querySelector('input, textarea, select');
			if (control) {
				setError(control.name, '');
			}
		});
		summary.hidden = true;
		summaryList.textContent = '';
	};

	const validate = () => {
		const errors = {};
		const v = (n) => (controlOf(n) ? controlOf(n).value.trim() : '');
		const req = {
			business_name: data.i18n.vBusiness,
			first_name: data.i18n.vFirst,
			last_name: data.i18n.vLast,
			products: data.i18n.vProducts,
		};
		Object.entries(req).forEach(([name, message]) => {
			if (!v(name)) {
				errors[name] = message;
			}
		});
		if (!v('email')) {
			errors.email = data.i18n.vEmailRequired;
		} else if (!EMAIL_RE.test(v('email'))) {
			errors.email = data.i18n.invalidEmail;
		}
		if (!v('phone')) {
			errors.phone = data.i18n.vPhoneRequired;
		} else if (!PHONE_RE.test(v('phone'))) {
			errors.phone = data.i18n.vPhone;
		}
		['website', 'social'].forEach((name) => {
			if (v(name) && !isUrl(v(name))) {
				errors[name] = data.i18n.vUrl;
			}
		});
		if (controlOf('fee_ack') && !controlOf('fee_ack').checked) {
			errors.fee_ack = data.i18n.vFee;
		}
		if (!controlOf('consent').checked) {
			errors.consent = data.i18n.vConsent;
		}
		return errors;
	};

	const showErrors = (errors) => {
		summaryList.textContent = '';
		Object.entries(errors).forEach(([name, message]) => {
			setError(name, message);
			const { control } = slot(name);
			const item = document.createElement('li');
			const link = document.createElement('a');
			link.href = `#${control ? control.id : ''}`;
			link.textContent = message;
			link.addEventListener('click', (event) => {
				event.preventDefault();
				control.focus();
			});
			item.appendChild(link);
			summaryList.appendChild(item);
		});
		summary.hidden = false;
		summary.focus();
	};

	// Clear a field's error as soon as it is edited.
	form.addEventListener('input', (event) => {
		if (event.target.name) {
			setError(event.target.name, '');
		}
	});
	form.addEventListener('change', (event) => {
		if (event.target.name) {
			setError(event.target.name, '');
		}
	});

	form.addEventListener('submit', async (event) => {
		event.preventDefault();
		if (busy) {
			return;
		}
		clearAll();
		status.textContent = '';
		const errors = validate();
		if (Object.keys(errors).length) {
			showErrors(errors);
			return;
		}

		busy = true;
		button.disabled = true;
		button.setAttribute('aria-busy', 'true');
		label.textContent = data.i18n.sending;
		status.textContent = data.i18n.sending;

		const body = {};
		new FormData(form).forEach((value, key) => {
			body[key] = value;
		});
		body.consent = controlOf('consent').checked;
		body.fee_ack = controlOf('fee_ack') ? controlOf('fee_ack').checked : false;

		let stored = false;
		try {
			const response = await fetch(data.vendorEndpoint, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': data.restNonce },
				credentials: 'same-origin',
				body: JSON.stringify(body),
			});
			const json = await response.json().catch(() => ({}));
			if (response.ok && json.success) {
				stored = true;
				form.hidden = true;
				success.hidden = false;
				success.querySelector('[data-apply-success-text]').textContent = json.message || '';
				success.focus();
				success.scrollIntoView({ behavior: 'smooth', block: 'center' });
			} else if (json.errors && Object.keys(json.errors).length) {
				showErrors(json.errors);
				status.textContent = '';
			} else {
				status.textContent = json.message || data.i18n.genericError;
				status.classList.add('is-error');
			}
		} catch {
			status.textContent = data.i18n.genericError;
			status.classList.add('is-error');
		} finally {
			busy = false;
			if (!stored) {
				button.disabled = false;
				button.removeAttribute('aria-busy');
				label.textContent = data.i18n.vSubmit;
			}
		}
	});
}
