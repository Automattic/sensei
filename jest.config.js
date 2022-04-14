/**
 * WordPress dependencies
 */
const baseConfig = require( '@wordpress/scripts/config/jest-unit.config.js' );

module.exports = {
	...baseConfig,
	preset: null,
	setupFilesAfterEnv: [ './jest.setup.js' ],
	testPathIgnorePatterns: [
		'/node_modules/',
		'<rootDir>/build/',
		'<rootDir>/assets/dist/',
		'<rootDir>/tests/e2e/',
	],
	moduleNameMapper: {
		'\\.svg$': '<rootDir>/tests/__mocks__/svg.js',
	},
};
