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
	},
	plugins: [ 'jest' ],
	settings: {
		jsdoc: {
			// Override Gutenberg rules in favor of Prettier plugin.
			tagNamePreference: {
				returns: 'returns',
				yields: 'yields',
			},
		},
	},
};
