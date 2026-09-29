/**
 * WordPress dependencies
 */
const baseConfig = require( '@wordpress/scripts/config/jest-unit.config.js' );
const defaultPreset = require( '@wordpress/jest-preset-default' );

module.exports = {
	...baseConfig,
	preset: null,
	setupFiles: defaultPreset.setupFiles,
	setupFilesAfterEnv: [ './jest.setup.js' ],
	testPathIgnorePatterns: [
		'/node_modules/',
		'<rootDir>/build/',
		'<rootDir>/assets/dist/',
		'<rootDir>/tests/e2e/',
		'<rootDir>/tests/e2e-playwright/',
	],
	testEnvironment: 'jsdom',
	moduleNameMapper: {
		'\\.svg$': '<rootDir>/tests/__mocks__/svg.js',
		'\\.(gif|jpg|jpeg|png)$': '<rootDir>/tests/__mocks__/image.js',
	},
	coverageReporters: [ 'clover' ],
	transformIgnorePatterns: [
		'node_modules/(?!(client-zip|parsel-js|@wordpress)/)',
	],
	transform: {
		'^.+\\.m?jsx?$': 'babel-jest',
	},
};
