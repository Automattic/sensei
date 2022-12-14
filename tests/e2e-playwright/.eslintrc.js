module.exports = {
	root: true,
	extends: [ 'plugin:playwright/playwright-test', 'plugin:@typescript-eslint/recommended' ],
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
	plugins: [ 'import', '@typescript-eslint' ],
	parser: '@typescript-eslint/parser',
	settings: {
		'import/parsers': {
			'@typescript-eslint/parser': [ '.ts', '.spec.ts' ],
		},
		'import/resolver': {
			typescript: {
				alwaysTryTypes: true,
				project: './tsconfig.json',
			},
		},
	},
};
