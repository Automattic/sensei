module.exports = {
	env: {
		browser: true,
		jquery: true,
		node: true,
		es6: true
	},
	parser: 'babel-eslint',
	plugins: [
		'react'
	],
	rules: {
		'@wordpress/i18n-text-domain': 'off',
		'@wordpress/no-unused-vars-before-return': 'off',
		'camelcase': 'off',
		'eqeqeq': 'off',
		'jsdoc/check-alignment': 'off',
		'jsdoc/check-param-names': 'off',
		'jsdoc/check-tag-names': 'off',
		'jsdoc/newline-after-description': 'off',
		'jsdoc/require-param': 'off',
		'jsdoc/require-param-type': 'off',
		'jsdoc/require-returns-check': 'off',
		'jsdoc/require-returns-type': 'off',
		'no-alert': 'off',
		'no-else-return': 'off',
		'no-lonely-if': 'off',
		'no-shadow': 'off',
		'no-undef': 'off',
		'no-unused-vars': 'off',
		'no-useless-escape': 'off',
		'no-useless-return': 'off',
		'no-var': 'off',
		'object-shorthand': 'off',
		'prettier/prettier': 'off',
		'react/no-deprecated': 'off',
		'prefer-const': 'off'
	}
};