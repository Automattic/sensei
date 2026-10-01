/**
 * External dependencies
 */
const playwright = require( 'eslint-plugin-playwright' );
const tseslint = require( 'typescript-eslint' );

/**
 * WordPress dependencies
 */
const wordpress = require( '@wordpress/eslint-plugin' );

module.exports = [
	...tseslint.configs.recommended.map( ( config ) => ( {
		...config,
		files: [ '**/*.ts', '**/*.tsx' ],
	} ) ),
	...wordpress.configs.recommended,
	playwright.configs[ 'flat/recommended' ],
	{
		rules: {
			'no-shadow': 'off',
			'no-useless-constructor': 'off',
			'no-duplicate-imports': 'off',
			'import/no-unresolved': 'off',
			'react-hooks/rules-of-hooks': 'off',
		},
		settings: {
			'import/resolver': {
				typescript: {
					alwaysTryTypes: true,
					project: './tests/e2e-playwright/tsconfig.json',
				},
			},
		},
	},
];
