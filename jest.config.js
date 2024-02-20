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
		'<rootDir>/tests/e2e-playwright/',
	],
	transformIgnorePatterns: [
		'/node_modules/(?!(memize|is-plain-obj|@wordpress/i18n|@wordpress/shortcode)/)',
	],
	testEnvironment: 'jsdom',
	moduleNameMapper: {
		'\\.svg$': '<rootDir>/tests/__mocks__/svg.js',
		'\\.(gif|jpg|jpeg|png)$': '<rootDir>/tests/__mocks__/image.js',
		'^@wordpress/i18n/(.*)$': '<rootDir>/__mocks__/@wordpress/i18n/$1',
	},
	coverageReporters: [ 'clover' ],
};
