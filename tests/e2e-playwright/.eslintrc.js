module.exports = {
	extends: [ 'plugin:playwright/playwright-test' ],
	rules: {
		'jest/no-done-callback': 'off',
		'jest/valid-expect': 'off',
		'max-len': [ 'error', { code: 200 } ],
	},
};
