export default [
	{
		files: ['src/js/**/*.js'],
		languageOptions: { ecmaVersion: 2022, sourceType: 'module', globals: { window: 'readonly', document: 'readonly', fetch: 'readonly', console: 'readonly', navigator: 'readonly', CustomEvent: 'readonly' } },
		rules: { 'no-unused-vars': 'warn', 'no-undef': 'error', eqeqeq: 'error' },
	},
];
