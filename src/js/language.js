import { showToast } from './ui.js';

/**
 * Language selector (UI only). Disclosure button + list of option buttons.
 * Non-English choices only announce "coming soon"; the page language never changes.
 */
export function initLanguage() {
	const root = document.querySelector('[data-lang]');
	if (!root) {
		return;
	}
	const toggle = root.querySelector('[data-lang-toggle]');
	const menu = root.querySelector('.lang__menu');
	const options = [...root.querySelectorAll('[data-lang-option]')];
	const note = window.rivermereData?.i18n?.langSoon || 'Translations are coming soon.';

	const isOpen = () => toggle.getAttribute('aria-expanded') === 'true';

	const open = () => {
		menu.hidden = false;
		toggle.setAttribute('aria-expanded', 'true');
		(options.find((o) => o.hasAttribute('aria-current')) || options[0]).focus();
	};
	const close = (restoreFocus = false) => {
		menu.hidden = true;
		toggle.setAttribute('aria-expanded', 'false');
		if (restoreFocus) {
			toggle.focus();
		}
	};

	toggle.addEventListener('click', () => (isOpen() ? close() : open()));
	toggle.addEventListener('keydown', (event) => {
		if (event.key === 'ArrowDown' && !isOpen()) {
			event.preventDefault();
			open();
		}
	});

	options.forEach((option) => {
		option.addEventListener('click', () => {
			if (option.dataset.langReady === '1') {
				close(true);
				return;
			}
			showToast(`${option.querySelector('.lang__name').textContent}: ${note}`);
			close(true);
		});
	});

	menu.addEventListener('keydown', (event) => {
		const index = options.indexOf(document.activeElement);
		const move = (to) => {
			event.preventDefault();
			options[(to + options.length) % options.length].focus();
		};
		switch (event.key) {
			case 'ArrowDown':
				move(index + 1);
				break;
			case 'ArrowUp':
				move(index - 1);
				break;
			case 'Home':
				move(0);
				break;
			case 'End':
				move(options.length - 1);
				break;
			case 'Tab':
				close();
				break;
			default:
		}
	});

	root.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && isOpen()) {
			event.stopPropagation();
			close(true);
		}
	});

	document.addEventListener('click', (event) => {
		if (isOpen() && !root.contains(event.target)) {
			close();
		}
	});
}
