const path = require( 'path' );
const { jestConfig: baseE2Econfig } = require( '@woocommerce/e2e-environment' );
const { jestConfig: pptrConfig } = require( '@automattic/puppeteer-utils' );

module.exports = {
	...baseE2Econfig,
	...pptrConfig,

	// Path of your project's E2E tests.
	roots: [ path.resolve( __dirname, '../specs' ) ],

	// A list of paths to modules that run some code to configure or set up the testing framework before each test
	setupFilesAfterEnv: [
		...baseE2Econfig.setupFilesAfterEnv,
		...pptrConfig.setupFilesAfterEnv,
		path.resolve( __dirname, './jest.setup.js' ),
	],
};
