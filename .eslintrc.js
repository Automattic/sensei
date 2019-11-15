module.exports = {
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended', 'prettier' ],
	env: {
		'jest/globals': true,
	},
	globals: {
		wp: true,
	},
	plugins: [ 'jest' ],
	rules: {
		// '@wordpress/dependency-group': 'off',
		// 'valid-jsdoc': 'off',
		// yoda: [ 'error', 'never' ],
	},
};
