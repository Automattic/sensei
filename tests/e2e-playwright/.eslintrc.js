module.exports = {
	root: true,
	extends: [ 'plugin:playwright/playwright-test', '../../.eslintrc.js' ],
	rules: {
		'no-shadow': 'off',
		'jest/valid-expect': 'off',
		'no-useless-constructor': 'off',
	},
	parserOptions: {
		ecmaVersion: 'latest',
		sourceType: 'module',
	},
	env: {
		es6: true,
	},
};
