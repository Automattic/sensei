/**
 * External dependencies
 */
const globals = require( 'globals' );

/**
 * WordPress dependencies
 */
const wpScriptsConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...wpScriptsConfig,
	{
		ignores: [ 'assets/dist/**', 'assets/vendor/**', 'assets/chosen/**' ],
	},
	{
		languageOptions: {
			globals: {
				...globals.browser,
				...globals.jquery,
				wp: 'readonly',
				ajaxurl: 'readonly',
				ajax_object: 'readonly',
				sensei_log_event: 'readonly',
				sensei_event_logging: 'readonly',
			},
		},
		settings: {
			'import/resolver': {
				typescript: true,
			},
		},
		rules: {
			'import/no-unresolved': 'off',
			'@wordpress/dependency-group': 'warn',
			'@wordpress/i18n-translator-comments': 'warn',
			'@wordpress/i18n-text-domain': [
				'error',
				{
					allowedTextDomain: 'sensei-lms',
				},
			],
			'import/no-extraneous-dependencies': [
				'error',
				{
					devDependencies: [
						'**/*.test.js',
						'scripts/**/*.js',
						'tests/**/*.js',
						'webpack.config.js',
						'postcss.config.js',
						'jest.setup.js',
						'jest.config.js',
						'**/eslint.config.js',
					],
					peerDependencies: false,
					optionalDependencies: false,
					bundledDependencies: false,
					packageDir: __dirname,
				},
			],
			'jsdoc/check-line-alignment': [
				'warn',
				'always',
				{
					tags: [ 'param', 'arg', 'argument', 'property', 'prop' ],
					preserveMainDescriptionPostDelimiter: true,
				},
			],
			'jsdoc/no-undefined-types': [
				'error',
				{ definedTypes: [ 'JSX' ] },
			],
			'jsdoc/check-tag-names': [
				'error',
				{ definedTags: [ 'hook', 'usage' ] },
			],
			'jsdoc/require-yields': 'off',
			'jsdoc/tag-lines': 'off',
			'react-hooks/exhaustive-deps': 'warn',
		},
	},
	{
		files: [ 'assets/js/**' ],
		rules: {
			camelcase: 'off',
			eqeqeq: 'off',
			'no-alert': 'off',
		},
	},
];
