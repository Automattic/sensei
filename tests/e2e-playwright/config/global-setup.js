/**
 * External dependencies
 */
const { chromium } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
const { getContextByRole } = require( '../helpers/context' );
module.exports = async () => {
	await createAdminBrowserContext();
};

const createAdminBrowserContext = async () => {
	const browser = await chromium.launch();
	const page = await browser.newPage();

	await page.goto( 'http://localhost:8889/wp-admin' );
	await page.locator( 'input[name="log"]' ).fill( 'admin' );
	await page.locator( 'input[name="pwd"]' ).fill( 'password' );
	await page.locator( 'text=Log In' ).click();

	// it saves the request context
	await page.request.storageState( { path: getContextByRole( 'admin' ) } );
	await browser.close();
};
