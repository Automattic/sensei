module.exports = {
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended', 'prettier' ],
	env: {
		'jest/globals': true,
	},
	globals: {
		wp: true,
	},
	rules: {
		'@wordpress/dependency-group': 'warn',
		'@wordpress/i18n-text-domain': [
			'error',
			{
				allowedTextDomain: 'sensei-lms',
			},
		],
		'import/no-extraneous-dependencies': 'off',
		'jsdoc-alignment/lines-alignment': 'error',
		'react-hooks/exhaustive-deps': 'warn',
	},
	plugins: [ 'jest', 'jsdoc-alignment' ],
};
