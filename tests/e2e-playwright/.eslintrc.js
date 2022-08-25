module.exports = {
	root: true,
	extends: [ 'plugin:playwright/playwright-test' ],
	rules: {
		'max-len': [ 'error', { code: 200 } ],
	},
	parserOptions: {
		ecmaVersion: 'latest',
	},
	env: {
		es6: true,
	},
};
