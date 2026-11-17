import { initNavigation } from './navigation.js';
import { initSearch } from './search.js';
import { initForms } from './forms.js';
import { initConsoleWelcome, initScrollProgress, initBackToTop, initComingSoon } from './ui.js';
import { initLanguage } from './language.js';
import { initContactWidget } from './contact.js';

const ready = (fn) => {
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', fn, { once: true });
	} else {
		fn();
	}
};

ready(() => {
	initConsoleWelcome();
	initNavigation();
	initSearch();
	initForms();
	initScrollProgress();
	initBackToTop();
	initComingSoon();
	initLanguage();
	initContactWidget();
});
