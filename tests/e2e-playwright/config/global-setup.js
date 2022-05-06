/**
 * External dependencies
 */
const { chromium } = require( '@playwright/test' );
const { retry } = require( '@lifeomic/attempt' );

/**
 * Internal dependencies
 */
const { getContextByRole } = require( '../helpers/context' );
const { cleanAll: cleanDatabase } = require( '../helpers/database' );
module.exports = async () => {
	await cleanDatabase();

	// Retries if the wordpress is still not ready to open the admin page
	await retry( createAdminBrowserContext, {
		delay: 200,
		factor: 2,
		maxAttempts: 4,
	} );
};

const createAdminBrowserContext = async () => {
	const browser = await chromium.launch();
	const page = await browser.newPage();

	await page.goto( 'http://localhost:8889/wp-admin' );
	await page.locator( 'input[name="log"]' ).fill( 'admin' );
	await page.locator( 'input[name="pwd"]' ).fill( 'password' );
	await page.locator( 'text=Log In' ).click();
	await page.waitForNavigation();

	// it saves the request context
	await page.request.storageState( { path: getContextByRole( 'admin' ) } );
	await browser.close();
};
