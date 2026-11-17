import { initNavigation } from './navigation.js';
import { initSearch } from './search.js';
import { initForms } from './forms.js';

const ready = (fn) => {
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', fn, { once: true });
	} else {
		fn();
	}
};

ready(() => {
	initNavigation();
	initSearch();
	initForms();
});
