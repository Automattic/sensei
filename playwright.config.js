/**
 * External dependencies
 */
// eslint-disable-next-line import/no-extraneous-dependencies
const { devices } = require( '@playwright/test' );

/**
 * @see https://playwright.dev/docs/test-configuration
 * @type {Object} PlaywrightTestConfig
 */
const config = {
	testDir: './tests/e2e-playwright/specs/',
	testMatch: /.*\.spec\.(js|ts)$/,
	/* Maximum time one test can run for. */
	timeout: 30 * 1000, // 30 seconds.
	globalSetup: require.resolve(
		'./tests/e2e-playwright/config/global-setup'
	),
	expect: {
		/**
		 * Maximum time expect() should wait for the condition to be met.
		 * For example in `await expect(locator).toHaveText();`
		 */
		timeout: 5 * 1000, // 5 seconds.
	},
	/* Fail the build on CI if you accidentally left test.only in the source code. */
	forbidOnly: !! process.env.CI,
	/* Retry on CI only */
	retries: process.env.CI ? 2 : 0,
	/* Opt out of parallel tests on CI. */
	workers: process.env.CI ? 1 : 2,
	/* Reporter to use. See https://playwright.dev/docs/test-reporters */
	reporter: process.env.CI ? 'github' : 'list',
	/* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
	use: {
		/* Maximum time each action such as `click()` can take. Defaults to 0 (no limit). */
		actionTimeout: 10 * 1000, // 10 seconds.
		/* Base URL to use in actions like `await page.goto('/')`. */
		baseURL: 'http://localhost:8889/',

		/* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
		trace: process.env.CI ? 'on-first-retry' : 'retain-on-failure',
		screenshot: 'only-on-failure',
		video: 'on-first-retry',
		viewport: { width: 1280, height: 1200 },
	},

	/* Configure projects for major browsers */
	projects: [
		{
			name: 'chromium',
			use: {
				...devices[ 'Desktop Chrome' ],
			},
		},
	],

	/* Folder for test artifacts such as screenshots, videos, traces, etc. */
	outputDir: './playwright-report/',
};

module.exports = config;
