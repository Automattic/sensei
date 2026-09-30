/**
 * WordPress dependencies
 */
const defaultPreset = require( '@wordpress/jest-preset-default' );

module.exports = {
	preset: null,
	reporters: [ 'default', [ 'github-actions', { silent: false } ] ],
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
		'^@wordpress/hooks$': '<rootDir>/node_modules/@wordpress/hooks',
		'\\.svg$': '<rootDir>/tests/__mocks__/svg.js',
		'\\.(gif|jpg|jpeg|png)$': '<rootDir>/tests/__mocks__/image.js',
	},
	coverageReporters: [ 'clover' ],
	transformIgnorePatterns: [
		'node_modules/(?!(client-zip|marked|parsel-js|@wordpress)/)',
	],
	transform: defaultPreset.transform,
};
