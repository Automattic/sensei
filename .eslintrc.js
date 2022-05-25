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
		'prefer-arrow-functions/prefer-arrow-functions': [
			'warn',
			{
				classPropertiesAllowed: false,
				disallowPrototype: false,
				returnStyle: 'implicit',
				singleReturnOnly: false,
			},
		],
		'prefer-arrow-callback': [ 'warn', { allowNamedFunctions: false } ],
		'func-style': [ 'warn', 'expression', { allowArrowFunctions: true } ],
		'jsdoc/require-yields': 'off',
		'jsdoc/tag-lines': 'off',
		'react-hooks/exhaustive-deps': 'warn',
	},
	plugins: [ 'jest', 'prefer-arrow-functions' ],
};
