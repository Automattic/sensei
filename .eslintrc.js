module.exports = {
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended', 'prettier' ],
	env: {
		'jest/globals': true,
	},
	globals: {
		wp: true,
	},
	rules: {
		'@wordpress/i18n-text-domain': [
			'error',
			{
				allowedTextDomain: 'sensei-lms',
			},
		],
		'react-hooks/exhaustive-deps': 'warn',
		'jsdoc-alignment/lines-alignment': 'error',
	},
	plugins: [ 'jest', 'jsdoc-alignment' ],
};
